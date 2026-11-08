<?php

namespace App\Bookings;

use App\Core\Database;
use PDO;
use PDOException;

class BookingsRepository {
    private PDO $pdo;

    public function findById(int $id): ?array {
        $sql = "
            SELECT
                b.id,
                customer_id,
                vehicle_id,
                driver_id,
                booking_date
            FROM bookings b
            WHERE b.id = :id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}