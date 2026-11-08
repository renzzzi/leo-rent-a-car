<?php

namespace App\Inventory;

use PDO;
use Exception;
use App\Core\Database;
use App\Bookings\BookingsRepository;

class InventoryService {
    private BookingsRepository $bookingsRepository;
    private PDO $pdo;

    public function __construct() {
        $this->bookingsRepository = new BookingsRepository();
        $this->pdo = Database::connect();
    }

    public function findBookingById(int $id): array {
        $booking = $this->bookingsRepository->findById($id);

        if (!$booking) {
            throw new Exception("Booking not found.");
        }

        return $booking;
    }
}