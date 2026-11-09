<?php

namespace App\Maintenance;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class MaintenanceRepository
 * Raw SQL database operations for vehicle maintenance records.
 */
class MaintenanceRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all maintenance records with joined vehicle data.
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                m.maintenance_id,
                m.vehicle_id,
                m.service_type,
                m.service_date,
                m.mileage,
                m.notes,
                v.make AS vehicle_make,
                v.model AS vehicle_model,
                v.year AS vehicle_year,
                v.license_plate AS vehicle_plate,
                v.type AS vehicle_type,
                v.status AS vehicle_status
            FROM Maintenance m
            INNER JOIN Vehicles v ON m.vehicle_id = v.vehicle_id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['vehicle_id']) && $filters['vehicle_id'] !== 'all') {
            $sql .= " AND m.vehicle_id = :vehicle_id";
            $params['vehicle_id'] = (int)$filters['vehicle_id'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                m.service_type LIKE :search_type
                OR m.notes LIKE :search_notes
                OR v.make LIKE :search_make
                OR v.model LIKE :search_model
                OR v.license_plate LIKE :search_plate
            )";
            $params['search_type']  = $searchTerm;
            $params['search_notes'] = $searchTerm;
            $params['search_make']  = $searchTerm;
            $params['search_model'] = $searchTerm;
            $params['search_plate'] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND m.service_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND m.service_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY m.service_date DESC, m.maintenance_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve single maintenance log by ID.
     */
    public function findById(int $id): ?array {
        $sql = "
            SELECT
                m.*,
                v.make AS vehicle_make,
                v.model AS vehicle_model,
                v.year AS vehicle_year,
                v.license_plate AS vehicle_plate,
                v.type AS vehicle_type,
                v.status AS vehicle_status
            FROM Maintenance m
            INNER JOIN Vehicles v ON m.vehicle_id = v.vehicle_id
            WHERE m.maintenance_id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Create maintenance log.
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO Maintenance (
                vehicle_id,
                service_type,
                service_date,
                mileage,
                notes
            ) VALUES (
                :vehicle_id,
                :service_type,
                :service_date,
                :mileage,
                :notes
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'vehicle_id'   => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'service_date' => $data['service_date'],
            'mileage'      => $data['mileage'],
            'notes'        => !empty($data['notes']) ? $data['notes'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update maintenance log.
     */
    public function update(int $id, array $data): bool {
        $sql = "
            UPDATE Maintenance
            SET
                vehicle_id   = :vehicle_id,
                service_type = :service_type,
                service_date = :service_date,
                mileage      = :mileage,
                notes        = :notes
            WHERE maintenance_id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'vehicle_id'   => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'service_date' => $data['service_date'],
            'mileage'      => $data['mileage'],
            'notes'        => !empty($data['notes']) ? $data['notes'] : null,
            'id'           => $id,
        ]);
    }

    /**
     * Delete maintenance record.
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Maintenance WHERE maintenance_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update vehicle odometer mileage if maintenance mileage is higher.
     */
    public function updateVehicleMileageIfHigher(int $vehicleId, int $mileage): bool {
        $stmt = $this->pdo->prepare("UPDATE Vehicles SET mileage = GREATEST(mileage, :mileage) WHERE vehicle_id = :vehicle_id");
        return $stmt->execute([
            'mileage'    => $mileage,
            'vehicle_id' => $vehicleId,
        ]);
    }

    /**
     * Update vehicle status (e.g. into or out of Maintenance).
     */
    public function updateVehicleStatus(int $vehicleId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Vehicles SET status = :status WHERE vehicle_id = :vehicle_id");
        return $stmt->execute([
            'status'     => $status,
            'vehicle_id' => $vehicleId,
        ]);
    }

    /**
     * Get vehicles list for maintenance creation dropdown.
     */
    public function getAllVehicles(): array {
        $stmt = $this->pdo->query("SELECT vehicle_id, make, model, year, license_plate, mileage, status FROM Vehicles ORDER BY make ASC, model ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get KPI summary statistics.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_maintenance_logs,
                (SELECT COUNT(*) FROM Vehicles WHERE status = 'Maintenance') AS vehicles_in_maintenance,
                SUM(CASE WHEN MONTH(service_date) = MONTH(CURRENT_DATE()) AND YEAR(service_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) AS logs_this_month
            FROM Maintenance
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_maintenance_logs'  => 0,
            'vehicles_in_maintenance' => 0,
            'logs_this_month'         => 0,
        ];
    }
}

