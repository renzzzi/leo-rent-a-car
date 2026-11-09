<?php

namespace App\Bookings;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class BookingsRepository
 * Responsible for raw SQL database operations relating to Bookings.
 * Must only be called by BookingsService.
 */
class BookingsRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all bookings with joined customer, vehicle, driver, and creator information.
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                b.booking_id,
                b.booking_reference,
                b.customer_id,
                b.vehicle_id,
                b.driver_id,
                b.destination,
                b.number_of_passengers,
                b.start_date,
                b.end_date,
                b.booking_amount,
                b.booking_status,
                b.created_by,
                b.created_at,
                c.first_name AS customer_first_name,
                c.last_name  AS customer_last_name,
                c.phone_number AS customer_phone,
                c.email AS customer_email,
                c.is_vip AS customer_is_vip,
                c.is_returning AS customer_is_returning,
                v.make AS vehicle_make,
                v.model AS vehicle_model,
                v.year AS vehicle_year,
                v.license_plate AS vehicle_plate,
                v.type AS vehicle_type,
                v.seating_capacity AS vehicle_seating_capacity,
                v.status AS vehicle_status,
                d.first_name AS driver_first_name,
                d.last_name  AS driver_last_name,
                d.phone_number AS driver_phone,
                d.status AS driver_status,
                u.username AS created_by_username,
                u.role AS created_by_role
            FROM Bookings b
            INNER JOIN Customers c ON b.customer_id = c.customer_id
            INNER JOIN Vehicles  v ON b.vehicle_id  = v.vehicle_id
            LEFT  JOIN Drivers   d ON b.driver_id   = d.driver_id
            INNER JOIN Users     u ON b.created_by  = u.user_id
            WHERE 1=1
        ";

        $params = [];

        // Filter by booking status
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND b.booking_status = :status";
            $params['status'] = $filters['status'];
        }

        // Search by reference, customer name, vehicle plate, or destination
        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                b.booking_reference LIKE :search_ref
                OR c.first_name LIKE :search_cfname
                OR c.last_name LIKE :search_clname
                OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search_cname
                OR v.license_plate LIKE :search_plate
                OR v.model LIKE :search_model
                OR b.destination LIKE :search_dest
            )";
            $params['search_ref']    = $searchTerm;
            $params['search_cfname'] = $searchTerm;
            $params['search_clname'] = $searchTerm;
            $params['search_cname']  = $searchTerm;
            $params['search_plate']  = $searchTerm;
            $params['search_model']  = $searchTerm;
            $params['search_dest']   = $searchTerm;
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(b.start_date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(b.end_date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        // Order by start_date descending
        $sql .= " ORDER BY b.start_date DESC, b.booking_id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve a single booking by ID with joined details.
     */
    public function findById(int $bookingId): ?array {
        $sql = "
            SELECT
                b.booking_id,
                b.booking_reference,
                b.customer_id,
                b.vehicle_id,
                b.driver_id,
                b.destination,
                b.number_of_passengers,
                b.start_date,
                b.end_date,
                b.booking_amount,
                b.booking_status,
                b.created_by,
                b.created_at,
                c.first_name AS customer_first_name,
                c.last_name  AS customer_last_name,
                c.phone_number AS customer_phone,
                c.email AS customer_email,
                c.address AS customer_address,
                c.is_vip AS customer_is_vip,
                c.is_returning AS customer_is_returning,
                c.notes AS customer_notes,
                v.make AS vehicle_make,
                v.model AS vehicle_model,
                v.year AS vehicle_year,
                v.license_plate AS vehicle_plate,
                v.vin AS vehicle_vin,
                v.type AS vehicle_type,
                v.seating_capacity AS vehicle_seating_capacity,
                v.status AS vehicle_status,
                d.first_name AS driver_first_name,
                d.last_name  AS driver_last_name,
                d.phone_number AS driver_phone,
                d.license_number AS driver_license,
                d.status AS driver_status,
                u.username AS created_by_username,
                u.role AS created_by_role
            FROM Bookings b
            INNER JOIN Customers c ON b.customer_id = c.customer_id
            INNER JOIN Vehicles  v ON b.vehicle_id  = v.vehicle_id
            LEFT  JOIN Drivers   d ON b.driver_id   = d.driver_id
            INNER JOIN Users     u ON b.created_by  = u.user_id
            WHERE b.booking_id = :booking_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['booking_id' => $bookingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if a booking reference exists in the database.
     */
    public function checkReferenceExists(string $reference): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE booking_reference = ? LIMIT 1");
        $stmt->execute([$reference]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Insert a new booking record.
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO Bookings (
                booking_reference,
                customer_id,
                vehicle_id,
                driver_id,
                destination,
                number_of_passengers,
                start_date,
                end_date,
                booking_amount,
                booking_status,
                created_by
            ) VALUES (
                :booking_reference,
                :customer_id,
                :vehicle_id,
                :driver_id,
                :destination,
                :number_of_passengers,
                :start_date,
                :end_date,
                :booking_amount,
                :booking_status,
                :created_by
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'booking_reference'    => $data['booking_reference'],
            'customer_id'          => $data['customer_id'],
            'vehicle_id'           => $data['vehicle_id'],
            'driver_id'            => !empty($data['driver_id']) ? $data['driver_id'] : null,
            'destination'          => $data['destination'],
            'number_of_passengers' => $data['number_of_passengers'],
            'start_date'           => $data['start_date'],
            'end_date'             => $data['end_date'],
            'booking_amount'       => $data['booking_amount'],
            'booking_status'       => $data['booking_status'] ?? 'Pending',
            'created_by'           => $data['created_by'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update an existing booking record.
     */
    public function update(int $bookingId, array $data): bool {
        $sql = "
            UPDATE Bookings
            SET
                customer_id          = :customer_id,
                vehicle_id           = :vehicle_id,
                driver_id            = :driver_id,
                destination          = :destination,
                number_of_passengers = :number_of_passengers,
                start_date           = :start_date,
                end_date             = :end_date,
                booking_amount       = :booking_amount,
                booking_status       = :booking_status
            WHERE booking_id = :booking_id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'customer_id'          => $data['customer_id'],
            'vehicle_id'           => $data['vehicle_id'],
            'driver_id'            => !empty($data['driver_id']) ? $data['driver_id'] : null,
            'destination'          => $data['destination'],
            'number_of_passengers' => $data['number_of_passengers'],
            'start_date'           => $data['start_date'],
            'end_date'             => $data['end_date'],
            'booking_amount'       => $data['booking_amount'],
            'booking_status'       => $data['booking_status'],
            'booking_id'           => $bookingId,
        ]);
    }

    /**
     * Update booking status.
     */
    public function updateStatus(int $bookingId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Bookings SET booking_status = :status WHERE booking_id = :booking_id");
        return $stmt->execute([
            'status'     => $status,
            'booking_id' => $bookingId,
        ]);
    }

    /**
     * Delete a booking by ID.
     */
    public function delete(int $bookingId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Bookings WHERE booking_id = :booking_id");
        return $stmt->execute(['booking_id' => $bookingId]);
    }

    /**
     * Get summary KPI statistics.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_bookings,
                SUM(CASE WHEN booking_status = 'Pending'   THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN booking_status = 'Confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
                SUM(CASE WHEN booking_status = 'Ongoing'   THEN 1 ELSE 0 END) AS ongoing_count,
                SUM(CASE WHEN booking_status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN booking_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
                COALESCE(SUM(CASE WHEN booking_status IN ('Completed', 'Ongoing') THEN booking_amount ELSE 0 END), 0) AS total_revenue
            FROM Bookings
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_bookings'  => 0,
            'pending_count'   => 0,
            'confirmed_count' => 0,
            'ongoing_count'   => 0,
            'completed_count' => 0,
            'cancelled_count' => 0,
            'total_revenue'   => 0.00,
        ];
    }

    /**
     * Retrieve all customers for selection.
     */
    public function getAllCustomers(): array {
        $sql = "SELECT customer_id, first_name, last_name, phone_number, email, is_vip, is_returning FROM Customers ORDER BY first_name ASC, last_name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve vehicles. Optionally only available or include a specific ID.
     */
    public function getVehicles(bool $onlyAvailable = false, ?int $includeVehicleId = null): array {
        $sql = "SELECT vehicle_id, make, model, year, license_plate, type, seating_capacity, mileage, status FROM Vehicles";
        $conditions = [];
        $params = [];

        if ($onlyAvailable) {
            if ($includeVehicleId !== null) {
                $conditions[] = "(status = 'Available' OR vehicle_id = :inc_id)";
                $params['inc_id'] = $includeVehicleId;
            } else {
                $conditions[] = "status = 'Available'";
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY make ASC, model ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve vehicle by ID.
     */
    public function getVehicleById(int $vehicleId): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM Vehicles WHERE vehicle_id = ? LIMIT 1");
        $stmt->execute([$vehicleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retrieve drivers. Optionally only available or include a specific ID.
     */
    public function getDrivers(bool $onlyAvailable = false, ?int $includeDriverId = null): array {
        $sql = "SELECT driver_id, first_name, last_name, phone_number, license_number, status FROM Drivers";
        $conditions = [];
        $params = [];

        if ($onlyAvailable) {
            if ($includeDriverId !== null) {
                $conditions[] = "(status = 'Available' OR driver_id = :inc_id)";
                $params['inc_id'] = $includeDriverId;
            } else {
                $conditions[] = "status = 'Available'";
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY first_name ASC, last_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Quick-create a new customer.
     */
    public function createCustomer(array $data): int {
        $sql = "
            INSERT INTO Customers (
                first_name,
                last_name,
                phone_number,
                email,
                address,
                is_returning,
                is_vip,
                notes
            ) VALUES (
                :first_name,
                :last_name,
                :phone_number,
                :email,
                :address,
                :is_returning,
                :is_vip,
                :notes
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'email'        => !empty($data['email']) ? $data['email'] : null,
            'address'      => !empty($data['address']) ? $data['address'] : null,
            'is_returning' => !empty($data['is_returning']) ? 1 : 0,
            'is_vip'       => !empty($data['is_vip']) ? 1 : 0,
            'notes'        => !empty($data['notes']) ? $data['notes'] : null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update vehicle status (Available, Rented, Maintenance, Inactive).
     */
    public function updateVehicleStatus(int $vehicleId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Vehicles SET status = :status WHERE vehicle_id = :vehicle_id");
        return $stmt->execute([
            'status'     => $status,
            'vehicle_id' => $vehicleId,
        ]);
    }

    /**
     * Update driver status (Available, On Trip, On Leave).
     */
    public function updateDriverStatus(int $driverId, string $status): bool {
        $stmt = $this->pdo->prepare("UPDATE Drivers SET status = :status WHERE driver_id = :driver_id");
        return $stmt->execute([
            'status'    => $status,
            'driver_id' => $driverId,
        ]);
    }
}
