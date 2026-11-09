<?php

namespace App\Maintenance;

use Exception;

/**
 * Class MaintenanceService
 * Business logic for vehicle servicing, repairs, and fleet upkeep.
 * Contains NO SQL queries.
 */
class MaintenanceService {
    private MaintenanceRepository $repository;

    public function __construct(?MaintenanceRepository $repository = null) {
        $this->repository = $repository ?? new MaintenanceRepository();
    }

    /**
     * Retrieve all maintenance logs with display enrichments.
     */
    public function getLogs(array $filters = []): array {
        $logs = $this->repository->findAll($filters);

        foreach ($logs as &$l) {
            $l['vehicle_name']      = trim(($l['vehicle_make'] ?? '') . ' ' . ($l['vehicle_model'] ?? '') . ' (' . ($l['vehicle_year'] ?? '') . ')');
            $l['formatted_mileage'] = number_format((int)($l['mileage'] ?? 0)) . ' km';
            $l['service_date_fmt']  = !empty($l['service_date']) ? date('M d, Y', strtotime($l['service_date'])) : '-';
        }

        return $logs;
    }

    /**
     * Retrieve single maintenance log.
     */
    public function getLog(int $id): array {
        $log = $this->repository->findById($id);
        if (!$log) {
            throw new Exception("Maintenance log #{$id} was not found.");
        }

        $log['vehicle_name']      = trim(($log['vehicle_make'] ?? '') . ' ' . ($log['vehicle_model'] ?? '') . ' (' . ($log['vehicle_year'] ?? '') . ')');
        $log['formatted_mileage'] = number_format((int)($log['mileage'] ?? 0)) . ' km';
        $log['service_date_fmt']  = !empty($log['service_date']) ? date('M d, Y', strtotime($log['service_date'])) : '-';

        return $log;
    }

    /**
     * Create maintenance log.
     */
    public function createLog(array $input): int {
        $this->validateMaintenanceInput($input);

        $vehicleId = (int)$input['vehicle_id'];
        $mileage   = max(0, (int)$input['mileage']);

        $data = [
            'vehicle_id'   => $vehicleId,
            'service_type' => trim($input['service_type']),
            'service_date' => $input['service_date'],
            'mileage'      => $mileage,
            'notes'        => trim($input['notes'] ?? ''),
        ];

        $id = $this->repository->create($data);

        // Update vehicle odometer if logged mileage is greater
        $this->repository->updateVehicleMileageIfHigher($vehicleId, $mileage);

        // If user opted to change vehicle status
        if (!empty($input['vehicle_status_after'])) {
            $allowed = ['Available', 'Maintenance', 'Inactive'];
            if (in_array($input['vehicle_status_after'], $allowed, true)) {
                $this->repository->updateVehicleStatus($vehicleId, $input['vehicle_status_after']);
            }
        }

        return $id;
    }

    /**
     * Update existing maintenance log.
     */
    public function updateLog(int $id, array $input): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Maintenance log #{$id} was not found.");
        }

        $this->validateMaintenanceInput($input);

        $vehicleId = (int)$input['vehicle_id'];
        $mileage   = max(0, (int)$input['mileage']);

        $data = [
            'vehicle_id'   => $vehicleId,
            'service_type' => trim($input['service_type']),
            'service_date' => $input['service_date'],
            'mileage'      => $mileage,
            'notes'        => trim($input['notes'] ?? ''),
        ];

        $updated = $this->repository->update($id, $data);
        $this->repository->updateVehicleMileageIfHigher($vehicleId, $mileage);

        return $updated;
    }

    /**
     * Delete maintenance record.
     */
    public function deleteLog(int $id): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Maintenance log #{$id} was not found.");
        }

        return $this->repository->delete($id);
    }

    /**
     * Get vehicles list for form selector.
     */
    public function getVehicles(): array {
        return $this->repository->getAllVehicles();
    }

    /**
     * Get KPI statistics.
     */
    public function getStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_maintenance_logs'  => (int)($raw['total_maintenance_logs'] ?? 0),
            'vehicles_in_maintenance' => (int)($raw['vehicles_in_maintenance'] ?? 0),
            'logs_this_month'         => (int)($raw['logs_this_month'] ?? 0),
        ];
    }

    /**
     * Input validation.
     */
    private function validateMaintenanceInput(array $input): void {
        if (empty($input['vehicle_id'])) {
            throw new Exception("Please select a vehicle.");
        }
        if (empty($input['service_type']) || trim($input['service_type']) === '') {
            throw new Exception("Service type (e.g. Oil Change, Brake Overhaul) is required.");
        }
        if (empty($input['service_date'])) {
            throw new Exception("Service date is required.");
        }
        if (!isset($input['mileage']) || (int)$input['mileage'] < 0) {
            throw new Exception("Mileage at service must be a non-negative number.");
        }
    }
}

