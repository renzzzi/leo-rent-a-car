<?php

namespace App\Vehicles;

use Exception;
use DateTime;

/**
 * Class VehiclesService
 * Business logic and validations for fleet management.
 * Contains NO SQL queries.
 */
class VehiclesService {
    private VehiclesRepository $repository;

    public function __construct(?VehiclesRepository $repository = null) {
        $this->repository = $repository ?? new VehiclesRepository();
    }

    /**
     * Retrieve all vehicles with enriched metadata.
     */
    public function getVehicles(array $filters = []): array {
        $vehicles = $this->repository->findAll($filters);

        $today = new DateTime('today');

        foreach ($vehicles as &$v) {
            $v['full_name']          = trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? '') . ' (' . ($v['year'] ?? '') . ')');
            $v['formatted_mileage']  = number_format((int)($v['mileage'] ?? 0)) . ' km';
            $v['status_badge']       = $this->getStatusBadgeConfig($v['status'] ?? 'Available');

            // Registration expiry check
            $v['reg_expiry_info'] = $this->checkExpiryStatus($v['registration_expiry'] ?? null, $today);
            // Insurance expiry check
            $v['ins_expiry_info'] = $this->checkExpiryStatus($v['insurance_expiry'] ?? null, $today);
        }

        return $vehicles;
    }

    /**
     * Retrieve single vehicle by ID.
     */
    public function getVehicle(int $id): array {
        $vehicle = $this->repository->findById($id);
        if (!$vehicle) {
            throw new Exception("Vehicle #{$id} was not found.");
        }

        $today = new DateTime('today');
        $vehicle['full_name']         = trim(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '') . ' (' . ($vehicle['year'] ?? '') . ')');
        $vehicle['formatted_mileage'] = number_format((int)($vehicle['mileage'] ?? 0)) . ' km';
        $vehicle['status_badge']      = $this->getStatusBadgeConfig($vehicle['status'] ?? 'Available');
        $vehicle['reg_expiry_info']   = $this->checkExpiryStatus($vehicle['registration_expiry'] ?? null, $today);
        $vehicle['ins_expiry_info']   = $this->checkExpiryStatus($vehicle['insurance_expiry'] ?? null, $today);

        return $vehicle;
    }

    /**
     * Create a new vehicle.
     */
    public function createVehicle(array $input): int {
        $this->validateVehicleInput($input);

        $plate = strtoupper(trim($input['license_plate']));
        if ($this->repository->checkPlateExists($plate)) {
            throw new Exception("License plate '{$plate}' is already registered to another vehicle.");
        }

        $vin = strtoupper(trim($input['vin']));
        if ($this->repository->checkVinExists($vin)) {
            throw new Exception("VIN '{$vin}' is already registered to another vehicle.");
        }

        $status = $input['status'] ?? 'Available';
        $allowedStatuses = ['Available', 'Rented', 'Maintenance', 'Inactive'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'Available';
        }

        $vehicleData = [
            'make'                => trim($input['make']),
            'model'               => trim($input['model']),
            'year'                => (int)$input['year'],
            'license_plate'       => $plate,
            'vin'                 => $vin,
            'type'                => trim($input['type']),
            'seating_capacity'    => (int)$input['seating_capacity'],
            'mileage'             => max(0, (int)($input['mileage'] ?? 0)),
            'registration_expiry' => $input['registration_expiry'],
            'insurance_expiry'    => $input['insurance_expiry'],
            'status'              => $status,
        ];

        return $this->repository->create($vehicleData);
    }

    /**
     * Update an existing vehicle.
     */
    public function updateVehicle(int $id, array $input): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Vehicle #{$id} was not found.");
        }

        $this->validateVehicleInput($input);

        $plate = strtoupper(trim($input['license_plate']));
        if ($this->repository->checkPlateExists($plate, $id)) {
            throw new Exception("License plate '{$plate}' is already registered to another vehicle.");
        }

        $vin = strtoupper(trim($input['vin']));
        if ($this->repository->checkVinExists($vin, $id)) {
            throw new Exception("VIN '{$vin}' is already registered to another vehicle.");
        }

        $status = $input['status'] ?? $existing['status'];
        $allowedStatuses = ['Available', 'Rented', 'Maintenance', 'Inactive'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = $existing['status'];
        }

        $vehicleData = [
            'make'                => trim($input['make']),
            'model'               => trim($input['model']),
            'year'                => (int)$input['year'],
            'license_plate'       => $plate,
            'vin'                 => $vin,
            'type'                => trim($input['type']),
            'seating_capacity'    => (int)$input['seating_capacity'],
            'mileage'             => max(0, (int)($input['mileage'] ?? 0)),
            'registration_expiry' => $input['registration_expiry'],
            'insurance_expiry'    => $input['insurance_expiry'],
            'status'              => $status,
        ];

        return $this->repository->update($id, $vehicleData);
    }

    /**
     * Change vehicle status directly.
     */
    public function updateStatus(int $id, string $status): bool {
        $allowed = ['Available', 'Rented', 'Maintenance', 'Inactive'];
        if (!in_array($status, $allowed, true)) {
            throw new Exception("Invalid vehicle status: '{$status}'.");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Vehicle #{$id} was not found.");
        }

        return $this->repository->updateStatus($id, $status);
    }

    /**
     * Delete a vehicle record.
     */
    public function deleteVehicle(int $id): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Vehicle #{$id} was not found.");
        }

        if ($this->repository->hasActiveBookings($id)) {
            throw new Exception("Cannot delete vehicle '{$existing['license_plate']}'. It is assigned to active or upcoming bookings.");
        }

        if ($this->repository->hasAnyBookings($id)) {
            throw new Exception("Cannot delete vehicle '{$existing['license_plate']}' because historical bookings are linked to it. Consider setting its status to 'Inactive' instead.");
        }

        return $this->repository->delete($id);
    }

    /**
     * Get dashboard summary statistics.
     */
    public function getStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_vehicles'      => (int)($raw['total_vehicles'] ?? 0),
            'available_count'     => (int)($raw['available_count'] ?? 0),
            'rented_count'        => (int)($raw['rented_count'] ?? 0),
            'maintenance_count'   => (int)($raw['maintenance_count'] ?? 0),
            'inactive_count'      => (int)($raw['inactive_count'] ?? 0),
            'total_fleet_mileage' => number_format((int)($raw['total_fleet_mileage'] ?? 0)) . ' km',
        ];
    }

    /**
     * Input validation.
     */
    private function validateVehicleInput(array $input): void {
        if (empty($input['make']) || trim($input['make']) === '') {
            throw new Exception("Vehicle Make (e.g. Toyota, Ford) is required.");
        }
        if (empty($input['model']) || trim($input['model']) === '') {
            throw new Exception("Vehicle Model (e.g. Fortuner, Vios) is required.");
        }

        $year = (int)($input['year'] ?? 0);
        $currentYear = (int)date('Y');
        if ($year < 1990 || $year > ($currentYear + 2)) {
            throw new Exception("Vehicle year must be between 1990 and " . ($currentYear + 2) . ".");
        }

        if (empty($input['license_plate']) || trim($input['license_plate']) === '') {
            throw new Exception("License plate is required.");
        }
        if (empty($input['vin']) || trim($input['vin']) === '') {
            throw new Exception("VIN / Chassis number is required.");
        }
        if (empty($input['type']) || trim($input['type']) === '') {
            throw new Exception("Vehicle type (e.g. SUV, Van, Sedan) is required.");
        }

        $capacity = (int)($input['seating_capacity'] ?? 0);
        if ($capacity < 1 || $capacity > 60) {
            throw new Exception("Seating capacity must be at least 1 passenger.");
        }

        if (empty($input['registration_expiry'])) {
            throw new Exception("Registration expiry date is required.");
        }
        if (empty($input['insurance_expiry'])) {
            throw new Exception("Insurance expiry date is required.");
        }
    }

    /**
     * Compute expiry warning status.
     */
    private function checkExpiryStatus(?string $dateStr, DateTime $today): array {
        if (!$dateStr) {
            return ['status' => 'unknown', 'text' => '-', 'class' => 'text-slate-400'];
        }

        try {
            $expDate = new DateTime($dateStr);
            $diffDays = (int)$today->diff($expDate)->format('%r%a');

            if ($diffDays < 0) {
                return [
                    'status' => 'expired',
                    'text'   => 'EXPIRED (' . abs($diffDays) . 'd ago)',
                    'class'  => 'text-red-700 bg-red-100 font-bold px-1.5 py-0.5 rounded-[2px] border border-red-200',
                ];
            }
            if ($diffDays <= 30) {
                return [
                    'status' => 'expiring_soon',
                    'text'   => 'Expiring in ' . $diffDays . 'd',
                    'class'  => 'text-amber-800 bg-amber-100 font-bold px-1.5 py-0.5 rounded-[2px] border border-amber-200',
                ];
            }
            return [
                'status' => 'valid',
                'text'   => $expDate->format('M d, Y'),
                'class'  => 'text-slate-700 font-medium',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'unknown', 'text' => $dateStr, 'class' => 'text-slate-400'];
        }
    }

    /**
     * Badge configuration with sharp styling tokens.
     */
    private function getStatusBadgeConfig(string $status): array {
        return match ($status) {
            'Available' => [
                'bg'     => 'bg-emerald-50',
                'text'   => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'dot'    => 'bg-emerald-500',
            ],
            'Rented' => [
                'bg'     => 'bg-blue-50',
                'text'   => 'text-blue-700',
                'border' => 'border-blue-200',
                'dot'    => 'bg-blue-500',
            ],
            'Maintenance' => [
                'bg'     => 'bg-amber-50',
                'text'   => 'text-amber-800',
                'border' => 'border-amber-200',
                'dot'    => 'bg-amber-500',
            ],
            default => [ // Inactive
                'bg'     => 'bg-slate-100',
                'text'   => 'text-slate-600',
                'border' => 'border-slate-300',
                'dot'    => 'bg-slate-400',
            ],
        };
    }
}

