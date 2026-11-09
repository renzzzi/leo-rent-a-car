<?php

namespace App\Customers;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class CustomersRepository
 * Raw SQL queries for Customers entity.
 */
class CustomersRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all customers with optional search & filtering.
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                c.customer_id,
                c.first_name,
                c.last_name,
                c.phone_number,
                c.email,
                c.address,
                c.is_returning,
                c.is_vip,
                c.notes,
                c.created_at,
                (SELECT COUNT(*) FROM Bookings b WHERE b.customer_id = c.customer_id) AS total_bookings_count,
                (SELECT COALESCE(SUM(booking_amount), 0) FROM Bookings b WHERE b.customer_id = c.customer_id AND b.booking_status IN ('Completed', 'Ongoing')) AS total_spent
            FROM Customers c
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['filter'])) {
            if ($filters['filter'] === 'vip') {
                $sql .= " AND c.is_vip = 1";
            } elseif ($filters['filter'] === 'returning') {
                $sql .= " AND c.is_returning = 1";
            }
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND (
                c.first_name LIKE :search_fname
                OR c.last_name LIKE :search_lname
                OR CONCAT(c.first_name, ' ', c.last_name) LIKE :search_name
                OR c.phone_number LIKE :search_phone
                OR c.email LIKE :search_email
                OR c.address LIKE :search_addr
            )";
            $params['search_fname'] = $searchTerm;
            $params['search_lname'] = $searchTerm;
            $params['search_name']  = $searchTerm;
            $params['search_phone'] = $searchTerm;
            $params['search_email'] = $searchTerm;
            $params['search_addr']  = $searchTerm;
        }

        $sql .= " ORDER BY c.first_name ASC, c.last_name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve single customer by ID.
     */
    public function findById(int $customerId): ?array {
        $sql = "
            SELECT
                c.*,
                (SELECT COUNT(*) FROM Bookings b WHERE b.customer_id = c.customer_id) AS total_bookings_count,
                (SELECT COALESCE(SUM(booking_amount), 0) FROM Bookings b WHERE b.customer_id = c.customer_id AND b.booking_status IN ('Completed', 'Ongoing')) AS total_spent
            FROM Customers c
            WHERE c.customer_id = :customer_id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if email exists (excluding specific ID).
     */
    public function checkEmailExists(string $email, ?int $excludeId = null): bool {
        if (trim($email) === '') return false;

        $sql = "SELECT 1 FROM Customers WHERE email = :email";
        $params = ['email' => trim($email)];

        if ($excludeId !== null) {
            $sql .= " AND customer_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create a new customer.
     */
    public function create(array $data): int {
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
     * Update an existing customer.
     */
    public function update(int $customerId, array $data): bool {
        $sql = "
            UPDATE Customers
            SET
                first_name   = :first_name,
                last_name    = :last_name,
                phone_number = :phone_number,
                email        = :email,
                address      = :address,
                is_returning = :is_returning,
                is_vip       = :is_vip,
                notes        = :notes
            WHERE customer_id = :customer_id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'email'        => !empty($data['email']) ? $data['email'] : null,
            'address'      => !empty($data['address']) ? $data['address'] : null,
            'is_returning' => !empty($data['is_returning']) ? 1 : 0,
            'is_vip'       => !empty($data['is_vip']) ? 1 : 0,
            'notes'        => !empty($data['notes']) ? $data['notes'] : null,
            'customer_id'  => $customerId,
        ]);
    }

    /**
     * Delete customer record.
     */
    public function delete(int $customerId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Customers WHERE customer_id = :customer_id");
        return $stmt->execute(['customer_id' => $customerId]);
    }

    /**
     * Check if customer is linked to bookings.
     */
    public function hasBookings(int $customerId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$customerId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Retrieve booking history for a specific customer.
     */
    public function getCustomerBookings(int $customerId): array {
        $sql = "
            SELECT
                b.booking_id,
                b.booking_reference,
                b.destination,
                b.start_date,
                b.end_date,
                b.booking_amount,
                b.booking_status,
                v.make AS vehicle_make,
                v.model AS vehicle_model,
                v.license_plate AS vehicle_plate
            FROM Bookings b
            JOIN Vehicles v ON b.vehicle_id = v.vehicle_id
            WHERE b.customer_id = :customer_id
            ORDER BY b.start_date DESC
            LIMIT 10
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['customer_id' => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get KPI summary statistics for customers.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_customers,
                SUM(CASE WHEN is_vip = 1 THEN 1 ELSE 0 END) AS vip_count,
                SUM(CASE WHEN is_returning = 1 THEN 1 ELSE 0 END) AS returning_count,
                (SELECT COUNT(DISTINCT customer_id) FROM Bookings WHERE booking_status = 'Ongoing') AS currently_renting_count
            FROM Customers
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_customers'         => 0,
            'vip_count'               => 0,
            'returning_count'         => 0,
            'currently_renting_count' => 0,
        ];
    }
}

