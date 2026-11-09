<?php

namespace App\Drivers;

use Exception;
use DateTime;

/**
 * Class DriversService
 * Business logic for staff chauffeurs and drivers.
 * Contains NO SQL queries.
 */
class DriversService {
    private DriversRepository $repository;

    public function __construct(?DriversRepository $repository = null) {
        $this->repository = $repository ?? new DriversRepository();
    }

    /**
     * Retrieve all drivers with enriched status and license expiry warning tags.
     */
    public function getDrivers(array $filters = []): array {
        $drivers = $this->repository->findAll($filters);
        $today = new DateTime('today');

        foreach ($drivers as &$d) {
            $d['full_name']           = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
            $d['status_badge']        = $this->getStatusBadgeConfig($d['status'] ?? 'Available');
            $d['license_expiry_info'] = $this->checkLicenseExpiry($d['license_expiry'] ?? null, $today);
        }

        return $drivers;
    }

    /**
     * Retrieve single driver by ID.
     */
    public function getDriver(int $id): array {
        $driver = $this->repository->findById($id);
        if (!$driver) {
            throw new Exception("Driver #{$id} was not found.");
        }

        $today = new DateTime('today');
        $driver['full_name']           = trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? ''));
        $driver['status_badge']        = $this->getStatusBadgeConfig($driver['status'] ?? 'Available');
        $driver['license_expiry_info'] = $this->checkLicenseExpiry($driver['license_expiry'] ?? null, $today);

        return $driver;
    }

    /**
     * Create new staff driver.
     */
    public function createDriver(array $input): int {
        $this->validateDriverInput($input);

        $license = strtoupper(trim($input['license_number']));
        if ($this->repository->checkLicenseExists($license)) {
            throw new Exception("License number '{$license}' is already registered to another driver.");
        }

        $status = $input['status'] ?? 'Available';
        $allowed = ['Available', 'On Trip', 'On Leave'];
        if (!in_array($status, $allowed, true)) {
            $status = 'Available';
        }

        $driverData = [
            'first_name'     => trim($input['first_name']),
            'last_name'      => trim($input['last_name']),
            'phone_number'   => trim($input['phone_number']),
            'license_number' => $license,
            'license_expiry' => $input['license_expiry'],
            'status'         => $status,
        ];

        return $this->repository->create($driverData);
    }

    /**
     * Update an existing driver.
     */
    public function updateDriver(int $id, array $input): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Driver #{$id} was not found.");
        }

        $this->validateDriverInput($input);

        $license = strtoupper(trim($input['license_number']));
        if ($this->repository->checkLicenseExists($license, $id)) {
            throw new Exception("License number '{$license}' is already registered to another driver.");
        }

        $status = $input['status'] ?? $existing['status'];
        $allowed = ['Available', 'On Trip', 'On Leave'];
        if (!in_array($status, $allowed, true)) {
            $status = $existing['status'];
        }

        $driverData = [
            'first_name'     => trim($input['first_name']),
            'last_name'      => trim($input['last_name']),
            'phone_number'   => trim($input['phone_number']),
            'license_number' => $license,
            'license_expiry' => $input['license_expiry'],
            'status'         => $status,
        ];

        return $this->repository->update($id, $driverData);
    }

    /**
     * Change driver status directly.
     */
    public function updateStatus(int $id, string $status): bool {
        $allowed = ['Available', 'On Trip', 'On Leave'];
        if (!in_array($status, $allowed, true)) {
            throw new Exception("Invalid driver status: '{$status}'.");
        }

        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Driver #{$id} was not found.");
        }

        return $this->repository->updateStatus($id, $status);
    }

    /**
     * Delete driver record.
     */
    public function deleteDriver(int $id): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Driver #{$id} was not found.");
        }

        if ($this->repository->hasActiveTrip($id)) {
            throw new Exception("Cannot delete driver '{$existing['first_name']} {$existing['last_name']}'. They are currently dispatched on an ongoing trip.");
        }

        return $this->repository->delete($id);
    }

    /**
     * Get KPI statistics.
     */
    public function getStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_drivers'   => (int)($raw['total_drivers'] ?? 0),
            'available_count' => (int)($raw['available_count'] ?? 0),
            'on_trip_count'   => (int)($raw['on_trip_count'] ?? 0),
            'on_leave_count'  => (int)($raw['on_leave_count'] ?? 0),
        ];
    }

    /**
     * Input validation.
     */
    private function validateDriverInput(array $input): void {
        if (empty($input['first_name']) || trim($input['first_name']) === '') {
            throw new Exception("First name is required.");
        }
        if (empty($input['last_name']) || trim($input['last_name']) === '') {
            throw new Exception("Last name is required.");
        }
        if (empty($input['phone_number']) || trim($input['phone_number']) === '') {
            throw new Exception("Phone number is required.");
        }
        if (empty($input['license_number']) || trim($input['license_number']) === '') {
            throw new Exception("Driver license number is required.");
        }
        if (empty($input['license_expiry'])) {
            throw new Exception("Driver license expiry date is required.");
        }
    }

    /**
     * Check license expiry.
     */
    private function checkLicenseExpiry(?string $dateStr, DateTime $today): array {
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
            if ($diffDays <= 45) {
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
     * Badge styles.
     */
    private function getStatusBadgeConfig(string $status): array {
        return match ($status) {
            'Available' => [
                'bg'     => 'bg-emerald-50',
                'text'   => 'text-emerald-700',
                'border' => 'border-emerald-200',
                'dot'    => 'bg-emerald-500',
            ],
            'On Trip' => [
                'bg'     => 'bg-blue-50',
                'text'   => 'text-blue-700',
                'border' => 'border-blue-200',
                'dot'    => 'bg-blue-500',
            ],
            default => [ // On Leave
                'bg'     => 'bg-slate-100',
                'text'   => 'text-slate-700',
                'border' => 'border-slate-300',
                'dot'    => 'bg-slate-400',
            ],
        };
    }
}

