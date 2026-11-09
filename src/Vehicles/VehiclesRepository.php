<?php

namespace App\Vehicles;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class VehiclesRepository
 * Raw SQL database operations for Vehicles.
 * Must only be invoked by VehiclesService.
 */
class VehiclesRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all vehicles with optional filters (search, status, type).
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                v.vehicle_id,
                v.make,
                v.model,
                v.year,
                v.license_plate,
                v.vin,
                v.type,
                v.seating_capacity,
                v.mileage,
                v.registration_expiry,
                v.insurance_expiry,
                v.status,
                (SELECT COUNT(*) FROM Bookings b WHERE b.vehicle_id = v.vehicle_id) AS total_trips,
                (SELECT COUNT(*) FROM Bookings b WHERE b.vehicle_id = v.vehicle_id AND b.booking_status = 'Ongoing') AS has_ongoing_booking,
                (SELECT COUNT(*) FROM Maintenance m WHERE m.vehicle_id = v.vehicle_id) AS total_maintenance_logs
            FROM Vehicles v
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND v.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $sql .= " AND v.type = :type";
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                v.make LIKE :search_make
                OR v.model LIKE :search_model
                OR CONCAT(v.make, ' ', v.model) LIKE :search_fullname
                OR v.license_plate LIKE :search_plate
                OR v.vin LIKE :search_vin
            )";
            $params['search_make']     = $searchTerm;
            $params['search_model']    = $searchTerm;
            $params['search_fullname'] = $searchTerm;
            $params['search_plate']    = $searchTerm;
            $params['search_vin']      = $searchTerm;
        }

        $sql .= " ORDER BY v.make ASC, v.model ASC, v.year DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a single vehicle by ID.
     */
    public function findById(int $vehicleId): ?array {
        $sql = "SELECT * FROM Vehicles WHERE vehicle_id = :vehicle_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['vehicle_id' => $vehicleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if a license plate exists, excluding a specific vehicle ID if updating.
     */
    public function checkPlateExists(string $plate, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM Vehicles WHERE license_plate = :plate";
        $params = ['plate' => trim($plate)];

        if ($excludeId !== null) {
            $sql .= " AND vehicle_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Check if a VIN exists, excluding a specific vehicle ID if updating.
     */
    public function checkVinExists(string $vin, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM Vehicles WHERE vin = :vin";
        $params = ['vin' => trim($vin)];

        if ($excludeId !== null) {
            $sql .= " AND vehicle_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Insert a new vehicle.
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO Vehicles (
                make,
                model,
                year,
                license_plate,
                vin,
                type,
                seating_capacity,
                mileage,
                registration_expiry,
                insurance_expiry,
                status
            ) VALUES (
                :make,
                :model,
                :year,
                :license_plate,
                :vin,
                :type,
                :seating_capacity,
                :mileage,
                :registration_expiry,
                :insurance_expiry,
                :status
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'make'                => $data['make'],
            'model'               => $data['model'],
            'year'                => $data['year'],
            'license_plate'       => $data['license_plate'],
            'vin'                 => $data['vin'],
            'type'                => $data['type'],
            'seating_capacity'    => $data['seating_capacity'],
            'mileage'             => $data['mileage'],
            'registration_expiry' => $data['registration_expiry'],
            'insurance_expiry'    => $data['insurance_expiry'],
            'status'              => $data['status'] ?? 'Available',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing vehicle.
     */
    public function update(int $vehicleId, array $data): bool {
        $sql = "
            UPDATE Vehicles
            SET
                make                = :make,
                model               = :model,
                year                = :year,
                license_plate       = :license_plate,
                vin                 = :vin,
                type                = :type,
                seating_capacity    = :seating_capacity,
                mileage             = :mileage,
                registration_expiry = :registration_expiry,
                insurance_expiry    = :insurance_expiry,
                status              = :status
            WHERE vehicle_id = :vehicle_id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'make'                => $data['make'],
            'model'               => $data['model'],
            'year'                => $data['year'],
            'license_plate'       => $data['license_plate'],
            'vin'                 => $data['vin'],
            'type'                => $data['type'],
            'seating_capacity'    => $data['seating_capacity'],
            'mileage'             => $data['mileage'],
            'registration_expiry' => $data['registration_expiry'],
            'insurance_expiry'    => $data['insurance_expiry'],
            'status'              => $data['status'],
            'vehicle_id'          => $vehicleId,
        ]);
    }

    /**
     * Update vehicle status only.
     */
    public function updateStatus(int $vehicleId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Vehicles SET status = :status WHERE vehicle_id = :vehicle_id");
        return $stmt->execute([
            'status'     => $status,
            'vehicle_id' => $vehicleId,
        ]);
    }

    /**
     * Delete a vehicle by ID.
     */
    public function delete(int $vehicleId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Vehicles WHERE vehicle_id = :vehicle_id");
        return $stmt->execute(['vehicle_id' => $vehicleId]);
    }

    /**
     * Check if vehicle is bound to active/uncompleted bookings.
     */
    public function hasActiveBookings(int $vehicleId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE vehicle_id = ? AND booking_status IN ('Pending', 'Confirmed', 'Ongoing') LIMIT 1");
        $stmt->execute([$vehicleId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Check if vehicle has any bookings at all (RESTRICT constraint on delete).
     */
    public function hasAnyBookings(int $vehicleId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE vehicle_id = ? LIMIT 1");
        $stmt->execute([$vehicleId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get summary KPI statistics for vehicles.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_vehicles,
                SUM(CASE WHEN status = 'Available'   THEN 1 ELSE 0 END) AS available_count,
                SUM(CASE WHEN status = 'Rented'      THEN 1 ELSE 0 END) AS rented_count,
                SUM(CASE WHEN status = 'Maintenance' THEN 1 ELSE 0 END) AS maintenance_count,
                SUM(CASE WHEN status = 'Inactive'    THEN 1 ELSE 0 END) AS inactive_count,
                SUM(mileage) AS total_fleet_mileage
            FROM Vehicles
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_vehicles'      => 0,
            'available_count'     => 0,
            'rented_count'        => 0,
            'maintenance_count'   => 0,
            'inactive_count'      => 0,
            'total_fleet_mileage' => 0,
        ];
    }
}

