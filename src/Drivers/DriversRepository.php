<?php

namespace App\Drivers;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class DriversRepository
 * Raw SQL database operations for Drivers entity.
 */
class DriversRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all drivers with optional search & filtering.
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                d.driver_id,
                d.first_name,
                d.last_name,
                d.phone_number,
                d.license_number,
                d.license_expiry,
                d.status,
                (SELECT COUNT(*) FROM Bookings b WHERE b.driver_id = d.driver_id) AS total_trips,
                (SELECT COUNT(*) FROM Bookings b WHERE b.driver_id = d.driver_id AND b.booking_status = 'Ongoing') AS has_ongoing_trip
            FROM Drivers d
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND d.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                d.first_name LIKE :search_fname
                OR d.last_name LIKE :search_lname
                OR CONCAT(d.first_name, ' ', d.last_name) LIKE :search_name
                OR d.phone_number LIKE :search_phone
                OR d.license_number LIKE :search_license
            )";
            $params['search_fname']   = $searchTerm;
            $params['search_lname']   = $searchTerm;
            $params['search_name']    = $searchTerm;
            $params['search_phone']   = $searchTerm;
            $params['search_license'] = $searchTerm;
        }

        $sql .= " ORDER BY d.first_name ASC, d.last_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve single driver by ID.
     */
    public function findById(int $driverId): ?array {
        $sql = "
            SELECT
                d.*,
                (SELECT COUNT(*) FROM Bookings b WHERE b.driver_id = d.driver_id) AS total_trips,
                (SELECT COUNT(*) FROM Bookings b WHERE b.driver_id = d.driver_id AND b.booking_status = 'Ongoing') AS has_ongoing_trip
            FROM Drivers d
            WHERE d.driver_id = :driver_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if a license number already exists.
     */
    public function checkLicenseExists(string $license, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM Drivers WHERE license_number = :license";
        $params = ['license' => trim($license)];

        if ($excludeId !== null) {
            $sql .= " AND driver_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Insert a new driver.
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO Drivers (
                first_name,
                last_name,
                phone_number,
                license_number,
                license_expiry,
                status
            ) VALUES (
                :first_name,
                :last_name,
                :phone_number,
                :license_number,
                :license_expiry,
                :status
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'phone_number'   => $data['phone_number'],
            'license_number' => $data['license_number'],
            'license_expiry' => $data['license_expiry'],
            'status'         => $data['status'] ?? 'Available',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing driver.
     */
    public function update(int $driverId, array $data): bool {
        $sql = "
            UPDATE Drivers
            SET
                first_name     = :first_name,
                last_name      = :last_name,
                phone_number   = :phone_number,
                license_number = :license_number,
                license_expiry = :license_expiry,
                status         = :status
            WHERE driver_id = :driver_id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'phone_number'   => $data['phone_number'],
            'license_number' => $data['license_number'],
            'license_expiry' => $data['license_expiry'],
            'status'         => $data['status'],
            'driver_id'      => $driverId,
        ]);
    }

    /**
     * Update status only.
     */
    public function updateStatus(int $driverId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Drivers SET status = :status WHERE driver_id = :driver_id");
        return $stmt->execute([
            'status'    => $status,
            'driver_id' => $driverId,
        ]);
    }

    /**
     * Delete driver.
     */
    public function delete(int $driverId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Drivers WHERE driver_id = :driver_id");
        return $stmt->execute(['driver_id' => $driverId]);
    }

    /**
     * Check if driver has ongoing bookings.
     */
    public function hasActiveTrip(int $driverId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE driver_id = ? AND booking_status = 'Ongoing' LIMIT 1");
        $stmt->execute([$driverId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Get KPI summary statistics.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_drivers,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) AS available_count,
                SUM(CASE WHEN status = 'On Trip'   THEN 1 ELSE 0 END) AS on_trip_count,
                SUM(CASE WHEN status = 'On Leave'  THEN 1 ELSE 0 END) AS on_leave_count
            FROM Drivers
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_drivers'   => 0,
            'available_count' => 0,
            'on_trip_count'   => 0,
            'on_leave_count'  => 0,
        ];
    }
}

