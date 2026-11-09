<?php

namespace App\Bookings;

use Exception;
use DateTime;

/**
 * Class BookingsService
 * Handles business logic, validations, calculations, and orchestrates
 * database calls through BookingsRepository. Contains NO raw SQL queries.
 */
class BookingsService {
    private BookingsRepository $repository;

    public function __construct(?BookingsRepository $repository = null) {
        $this->repository = $repository ?? new BookingsRepository();
    }

    /**
     * Retrieve and enrich all bookings according to provided filter criteria.
     */
    public function getBookings(array $filters = []): array {
        $bookings = $this->repository->findAll($filters);

        // Enrich with business calculations and display helpers
        foreach ($bookings as &$b) {
            $b['customer_name']    = trim(($b['customer_first_name'] ?? '') . ' ' . ($b['customer_last_name'] ?? ''));
            $b['driver_name']      = !empty($b['driver_id']) ? trim(($b['driver_first_name'] ?? '') . ' ' . ($b['driver_last_name'] ?? '')) : 'Self-Drive (No Driver)';
            $b['vehicle_name']     = trim(($b['vehicle_make'] ?? '') . ' ' . ($b['vehicle_model'] ?? '') . ' (' . ($b['vehicle_year'] ?? '') . ')');
            $b['formatted_amount'] = '₱ ' . number_format((float)($b['booking_amount'] ?? 0), 2);
            $b['duration_text']    = $this->calculateDuration($b['start_date'] ?? '', $b['end_date'] ?? '');
            $b['status_badge']     = $this->getStatusBadgeConfig($b['booking_status'] ?? 'Pending');
        }

        return $bookings;
    }

    /**
     * Retrieve single enriched booking by ID.
     */
    public function getBooking(int $id): array {
        $booking = $this->repository->findById($id);
        if (!$booking) {
            throw new Exception("Booking #{$id} was not found.");
        }

        $booking['customer_name']    = trim(($booking['customer_first_name'] ?? '') . ' ' . ($booking['customer_last_name'] ?? ''));
        $booking['driver_name']      = !empty($booking['driver_id']) ? trim(($booking['driver_first_name'] ?? '') . ' ' . ($booking['driver_last_name'] ?? '')) : 'Self-Drive (No Driver)';
        $booking['vehicle_name']     = trim(($booking['vehicle_make'] ?? '') . ' ' . ($booking['vehicle_model'] ?? '') . ' (' . ($booking['vehicle_year'] ?? '') . ')');
        $booking['formatted_amount'] = '₱ ' . number_format((float)($booking['booking_amount'] ?? 0), 2);
        $booking['duration_text']    = $this->calculateDuration($booking['start_date'] ?? '', $booking['end_date'] ?? '');
        $booking['status_badge']     = $this->getStatusBadgeConfig($booking['booking_status'] ?? 'Pending');

        return $booking;
    }

    /**
     * Create a new booking with comprehensive business validation.
     */
    public function createBooking(array $input, int $userId): int {
        $this->validateBookingInput($input);

        // Verify vehicle passenger capacity
        $vehicle = $this->repository->getVehicleById((int)$input['vehicle_id']);
        if (!$vehicle) {
            throw new Exception("Selected vehicle does not exist.");
        }

        $passengers = (int)$input['number_of_passengers'];
        if ($passengers > (int)$vehicle['seating_capacity']) {
            throw new Exception("Passenger count ({$passengers}) exceeds vehicle seating capacity ({$vehicle['seating_capacity']}).");
        }

        // Generate unique booking reference if not provided
        $reference = trim($input['booking_reference'] ?? '');
        if ($reference === '') {
            $reference = $this->generateUniqueReference();
        } elseif ($this->repository->checkReferenceExists($reference)) {
            throw new Exception("Booking reference '{$reference}' is already in use.");
        }

        $status = $input['booking_status'] ?? 'Pending';
        $allowedStatuses = ['Pending', 'Confirmed', 'Ongoing', 'Completed', 'Cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Pending';
        }

        $bookingData = [
            'booking_reference'    => $reference,
            'customer_id'          => (int)$input['customer_id'],
            'vehicle_id'           => (int)$input['vehicle_id'],
            'driver_id'            => !empty($input['driver_id']) ? (int)$input['driver_id'] : null,
            'destination'          => trim($input['destination']),
            'number_of_passengers' => $passengers,
            'start_date'           => $input['start_date'],
            'end_date'             => $input['end_date'],
            'booking_amount'       => (float)$input['booking_amount'],
            'booking_status'       => $status,
            'created_by'           => $userId,
        ];

        $bookingId = $this->repository->create($bookingData);

        // Synchronize vehicle and driver status if booking is immediately Ongoing
        if ($status === 'Ongoing') {
            $this->repository->updateVehicleStatus((int)$input['vehicle_id'], 'Rented');
            if (!empty($input['driver_id'])) {
                $this->repository->updateDriverStatus((int)$input['driver_id'], 'On Trip');
            }
        }

        return $bookingId;
    }

    /**
     * Update an existing booking.
     */
    public function updateBooking(int $bookingId, array $input): bool {
        $existing = $this->repository->findById($bookingId);
        if (!$existing) {
            throw new Exception("Booking #{$bookingId} does not exist.");
        }

        $this->validateBookingInput($input);

        $vehicle = $this->repository->getVehicleById((int)$input['vehicle_id']);
        if (!$vehicle) {
            throw new Exception("Selected vehicle does not exist.");
        }

        $passengers = (int)$input['number_of_passengers'];
        if ($passengers > (int)$vehicle['seating_capacity']) {
            throw new Exception("Passenger count ({$passengers}) exceeds vehicle seating capacity ({$vehicle['seating_capacity']}).");
        }

        $newStatus = $input['booking_status'] ?? $existing['booking_status'];
        $oldStatus = $existing['booking_status'];

        $bookingData = [
            'customer_id'          => (int)$input['customer_id'],
            'vehicle_id'           => (int)$input['vehicle_id'],
            'driver_id'            => !empty($input['driver_id']) ? (int)$input['driver_id'] : null,
            'destination'          => trim($input['destination']),
            'number_of_passengers' => $passengers,
            'start_date'           => $input['start_date'],
            'end_date'             => $input['end_date'],
            'booking_amount'       => (float)$input['booking_amount'],
            'booking_status'       => $newStatus,
        ];

        $updated = $this->repository->update($bookingId, $bookingData);

        // Synchronize vehicle/driver statuses if status transitioned
        $this->syncStatusTransitions($existing, $bookingData);

        return $updated;
    }

    /**
     * Update status of a booking directly.
     */
    public function updateBookingStatus(int $bookingId, string $newStatus): bool {
        $allowed = ['Pending', 'Confirmed', 'Ongoing', 'Completed', 'Cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception("Invalid booking status: '{$newStatus}'.");
        }

        $existing = $this->repository->findById($bookingId);
        if (!$existing) {
            throw new Exception("Booking #{$bookingId} does not exist.");
        }

        $success = $this->repository->updateStatus($bookingId, $newStatus);

        $dummyTarget = $existing;
        $dummyTarget['booking_status'] = $newStatus;
        $this->syncStatusTransitions($existing, $dummyTarget);

        return $success;
    }

    /**
     * Delete / Cancel a booking.
     */
    public function deleteBooking(int $bookingId): bool {
        $existing = $this->repository->findById($bookingId);
        if (!$existing) {
            throw new Exception("Booking #{$bookingId} does not exist.");
        }

        // If the booking was actively ongoing, release the vehicle and driver back to available
        if ($existing['booking_status'] === 'Ongoing') {
            $this->repository->updateVehicleStatus((int)$existing['vehicle_id'], 'Available');
            if (!empty($existing['driver_id'])) {
                $this->repository->updateDriverStatus((int)$existing['driver_id'], 'Available');
            }
        }

        return $this->repository->delete($bookingId);
    }

    /**
     * Quick-add a new customer from the booking creation flow.
     */
    public function createCustomer(array $input): int {
        $firstName = trim($input['first_name'] ?? '');
        $lastName  = trim($input['last_name'] ?? '');
        $phone     = trim($input['phone_number'] ?? '');

        if ($firstName === '' || $lastName === '') {
            throw new Exception("Customer first name and last name are required.");
        }
        if ($phone === '') {
            throw new Exception("Customer phone number is required.");
        }

        return $this->repository->createCustomer([
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone_number' => $phone,
            'email'        => trim($input['email'] ?? ''),
            'address'      => trim($input['address'] ?? ''),
            'is_returning' => !empty($input['is_returning']) ? 1 : 0,
            'is_vip'       => !empty($input['is_vip']) ? 1 : 0,
            'notes'        => trim($input['notes'] ?? ''),
        ]);
    }

    /**
     * Form support data (customers, vehicles, drivers).
     */
    public function getFormData(?int $currentVehicleId = null, ?int $currentDriverId = null): array {
        return [
            'customers' => $this->repository->getAllCustomers(),
            'vehicles'  => $this->repository->getVehicles(false),
            'drivers'   => $this->repository->getDrivers(false),
        ];
    }

    /**
     * Dashboard statistics summary.
     */
    public function getDashboardStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_bookings'   => (int)($raw['total_bookings'] ?? 0),
            'pending_count'    => (int)($raw['pending_count'] ?? 0),
            'confirmed_count'  => (int)($raw['confirmed_count'] ?? 0),
            'ongoing_count'    => (int)($raw['ongoing_count'] ?? 0),
            'completed_count'  => (int)($raw['completed_count'] ?? 0),
            'cancelled_count'  => (int)($raw['cancelled_count'] ?? 0),
            'total_revenue'    => (float)($raw['total_revenue'] ?? 0),
            'formatted_revenue'=> '₱ ' . number_format((float)($raw['total_revenue'] ?? 0), 2),
        ];
    }

    /**
     * Generate an elegant, human-readable unique reference like LR-202611-042.
     */
    private function generateUniqueReference(): string {
        $prefix = 'LR-' . date('Ym') . '-';
        do {
            $num = str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $candidate = $prefix . $num;
        } while ($this->repository->checkReferenceExists($candidate));

        return $candidate;
    }

    /**
     * Basic validation for input fields.
     */
    private function validateBookingInput(array $input): void {
        if (empty($input['customer_id'])) {
            throw new Exception("Please select a customer.");
        }
        if (empty($input['vehicle_id'])) {
            throw new Exception("Please select a vehicle.");
        }
        if (empty($input['destination']) || trim($input['destination']) === '') {
            throw new Exception("Destination is required.");
        }
        if (empty($input['number_of_passengers']) || (int)$input['number_of_passengers'] < 1) {
            throw new Exception("Please enter a valid number of passengers (at least 1).");
        }
        if (empty($input['start_date']) || empty($input['end_date'])) {
            throw new Exception("Both start date and end date are required.");
        }

        $startDate = new DateTime($input['start_date']);
        $endDate   = new DateTime($input['end_date']);

        if ($startDate >= $endDate) {
            throw new Exception("End date and time must be after the start date and time.");
        }

        if (!isset($input['booking_amount']) || (float)$input['booking_amount'] < 0) {
            throw new Exception("Booking amount must be a positive number.");
        }
    }

    /**
     * Synchronize vehicle and driver availability when booking status changes.
     */
    private function syncStatusTransitions(array $oldBooking, array $newBooking): void {
        $oldStatus = $oldBooking['booking_status'];
        $newStatus = $newBooking['booking_status'];

        $oldVehicle = (int)$oldBooking['vehicle_id'];
        $newVehicle = (int)$newBooking['vehicle_id'];
        $oldDriver  = !empty($oldBooking['driver_id']) ? (int)$oldBooking['driver_id'] : null;
        $newDriver  = !empty($newBooking['driver_id']) ? (int)$newBooking['driver_id'] : null;

        // If newly Ongoing
        if ($newStatus === 'Ongoing') {
            $this->repository->updateVehicleStatus($newVehicle, 'Rented');
            if ($newDriver) {
                $this->repository->updateDriverStatus($newDriver, 'On Trip');
            }
        }

        // If transitioned out of Ongoing into Completed or Cancelled
        if ($oldStatus === 'Ongoing' && in_array($newStatus, ['Completed', 'Cancelled'], true)) {
            $this->repository->updateVehicleStatus($oldVehicle, 'Available');
            if ($oldDriver) {
                $this->repository->updateDriverStatus($oldDriver, 'Available');
            }
        }

        // If vehicle was changed while ongoing
        if ($oldStatus === 'Ongoing' && $oldVehicle !== $newVehicle) {
            $this->repository->updateVehicleStatus($oldVehicle, 'Available');
            if ($newStatus === 'Ongoing') {
                $this->repository->updateVehicleStatus($newVehicle, 'Rented');
            }
        }
    }

    /**
     * Calculate human-readable duration between two dates.
     */
    private function calculateDuration(string $start, string $end): string {
        try {
            $d1 = new DateTime($start);
            $d2 = new DateTime($end);
            $interval = $d1->diff($d2);

            $days  = $interval->days;
            $hours = $interval->h;

            if ($days === 0) {
                return $hours . ' hr' . ($hours > 1 ? 's' : '');
            }
            if ($hours === 0) {
                return $days . ' day' . ($days > 1 ? 's' : '');
            }
            return $days . 'd ' . $hours . 'h';
        } catch (\Throwable $e) {
            return '-';
        }
    }

    /**
     * Badge configuration with sharp styling tokens.
     */
    private function getStatusBadgeConfig(string $status): array {
        return match ($status) {
            'Confirmed' => [
                'bg'     => 'bg-blue-50',
                'text'   => 'text-blue-700',
                'border' => 'border-blue-200',
                'dot'    => 'bg-blue-500',
            ],
            'Ongoing' => [
                'bg'     => 'bg-emerald-50',
                'text'   => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'dot'    => 'bg-emerald-500',
            ],
            'Completed' => [
                'bg'     => 'bg-slate-100',
                'text'   => 'text-slate-700',
                'border' => 'border-slate-300',
                'dot'    => 'bg-slate-500',
            ],
            'Cancelled' => [
                'bg'     => 'bg-red-50',
                'text'   => 'text-red-700',
                'border' => 'border-red-200',
                'dot'    => 'bg-red-500',
            ],
            default => [ // Pending
                'bg'     => 'bg-amber-50',
                'text'   => 'text-amber-800',
                'border' => 'border-amber-200',
                'dot'    => 'bg-amber-500',
            ],
        };
    }
}
