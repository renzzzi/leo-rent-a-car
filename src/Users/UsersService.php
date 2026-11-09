<?php

namespace App\Users;

use Exception;

/**
 * Class UsersService
 * Business logic for Owner management of Manager and Owner accounts.
 * Contains NO SQL queries.
 */
class UsersService {
    private UsersRepository $repository;

    public function __construct(?UsersRepository $repository = null) {
        $this->repository = $repository ?? new UsersRepository();
    }

    /**
     * Retrieve all accounts.
     */
    public function getUsers(array $filters = []): array {
        $users = $this->repository->findAll($filters);

        foreach ($users as &$u) {
            $u['created_at_fmt'] = !empty($u['created_at']) ? date('M d, Y h:i A', strtotime($u['created_at'])) : '-';
        }

        return $users;
    }

    /**
     * Retrieve single account.
     */
    public function getUser(int $id): array {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new Exception("Account #{$id} was not found.");
        }
        $user['created_at_fmt'] = !empty($user['created_at']) ? date('M d, Y h:i A', strtotime($user['created_at'])) : '-';

        return $user;
    }

    /**
     * Create a new manager or owner account.
     */
    public function createUser(array $input): int {
        $username = strtolower(trim($input['username'] ?? ''));
        $password = $input['password'] ?? '';
        $role     = trim($input['role'] ?? 'Manager');

        $this->validateUsername($username);

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        if (!in_array($role, ['Owner', 'Manager'], true)) {
            $role = 'Manager';
        }

        if ($this->repository->checkUsernameExists($username)) {
            throw new Exception("Username '{$username}' is already in use.");
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        return $this->repository->create([
            'username'      => $username,
            'password_hash' => $passwordHash,
            'role'          => $role,
        ]);
    }

    /**
     * Update account details (username, role).
     */
    public function updateUser(int $id, array $input, int $currentUserId): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Account #{$id} was not found.");
        }

        $username = strtolower(trim($input['username'] ?? ''));
        $role     = trim($input['role'] ?? $existing['role']);

        $this->validateUsername($username);

        if (!in_array($role, ['Owner', 'Manager'], true)) {
            $role = $existing['role'];
        }

        if ($this->repository->checkUsernameExists($username, $id)) {
            throw new Exception("Username '{$username}' is already in use by another account.");
        }

        // Prevent demoting the last Owner
        if ($existing['role'] === 'Owner' && $role !== 'Owner' && $this->repository->countOwners() <= 1) {
            throw new Exception("Cannot change role. The system must have at least one active Owner.");
        }

        return $this->repository->update($id, [
            'username' => $username,
            'role'     => $role,
        ]);
    }

    /**
     * Reset password for an account.
     */
    public function resetPassword(int $id, string $newPassword): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Account #{$id} was not found.");
        }

        if (strlen($newPassword) < 6) {
            throw new Exception("New password must be at least 6 characters long.");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->repository->updatePassword($id, $passwordHash);
    }

    /**
     * Delete an account.
     */
    public function deleteUser(int $id, int $currentUserId): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Account #{$id} was not found.");
        }

        // Prevent self-deletion
        if ($id === $currentUserId) {
            throw new Exception("You cannot delete your own currently logged-in account.");
        }

        // Prevent deleting the last owner
        if ($existing['role'] === 'Owner' && $this->repository->countOwners() <= 1) {
            throw new Exception("Cannot delete the last Owner account in the system.");
        }

        // Check if foreign key prevents deletion
        if ($this->repository->hasBookings($id)) {
            throw new Exception("Cannot delete user '{$existing['username']}' because historical bookings are attributed to them.");
        }

        return $this->repository->delete($id);
    }

    /**
     * KPI Stats.
     */
    public function getStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_users'   => (int)($raw['total_users'] ?? 0),
            'owner_count'   => (int)($raw['owner_count'] ?? 0),
            'manager_count' => (int)($raw['manager_count'] ?? 0),
        ];
    }

    /**
     * Username format validation.
     */
    private function validateUsername(string $username): void {
        if ($username === '') {
            throw new Exception("Username is required.");
        }
        if (strlen($username) < 3 || strlen($username) > 30) {
            throw new Exception("Username must be between 3 and 30 characters.");
        }
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            throw new Exception("Username may only contain letters, numbers, underscores, dashes, and periods.");
        }
    }
}

