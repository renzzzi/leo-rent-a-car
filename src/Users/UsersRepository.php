<?php

namespace App\Users;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Class UsersRepository
 * Raw SQL database operations for administrative and manager accounts.
 */
class UsersRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    /**
     * Retrieve all accounts.
     */
    public function findAll(array $filters = []): array {
        $sql = "
            SELECT
                u.user_id,
                u.username,
                u.role,
                u.created_at,
                (SELECT COUNT(*) FROM Bookings b WHERE b.created_by = u.user_id) AS total_created_bookings
            FROM Users u
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $sql .= " AND u.username LIKE :search";
            $params['search'] = $searchTerm;
        }

        $sql .= " ORDER BY (u.role = 'Owner') DESC, u.username ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieve user by ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT user_id, username, role, created_at FROM Users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Check if a username is already taken.
     */
    public function checkUsernameExists(string $username, ?int $excludeId = null): bool {
        $sql = "SELECT 1 FROM Users WHERE username = :username";
        $params = ['username' => trim($username)];

        if ($excludeId !== null) {
            $sql .= " AND user_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql . " LIMIT 1");
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Create a new user account.
     */
    public function create(array $data): int {
        $sql = "
            INSERT INTO Users (username, password_hash, role)
            VALUES (:username, :password_hash, :role)
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'username'      => $data['username'],
            'password_hash' => $data['password_hash'],
            'role'          => $data['role'] ?? 'Manager',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update username and role.
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE Users SET username = :username, role = :role WHERE user_id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'username' => $data['username'],
            'role'     => $data['role'],
            'id'       => $id,
        ]);
    }

    /**
     * Update user password hash.
     */
    public function updatePassword(int $id, string $passwordHash): bool {
        $stmt = $this->pdo->prepare("UPDATE Users SET password_hash = :hash WHERE user_id = :id");
        return $stmt->execute([
            'hash' => $passwordHash,
            'id'   => $id,
        ]);
    }

    /**
     * Delete user account.
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM Users WHERE user_id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Count owners in the system.
     */
    public function countOwners(): int {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'Owner'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if user created any bookings (RESTRICT foreign key check).
     */
    public function hasBookings(int $userId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Bookings WHERE created_by = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Summary stats.
     */
    public function getStats(): array {
        $sql = "
            SELECT
                COUNT(*) AS total_users,
                SUM(CASE WHEN role = 'Owner' THEN 1 ELSE 0 END) AS owner_count,
                SUM(CASE WHEN role = 'Manager' THEN 1 ELSE 0 END) AS manager_count
            FROM Users
        ";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_users'   => 0,
            'owner_count'   => 0,
            'manager_count' => 0,
        ];
    }
}

