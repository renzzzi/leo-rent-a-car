<?php

namespace App\Customers;

use Exception;

/**
 * Class CustomersService
 * Business logic for customer profile and history management.
 * Contains NO SQL queries.
 */
class CustomersService {
    private CustomersRepository $repository;

    public function __construct(?CustomersRepository $repository = null) {
        $this->repository = $repository ?? new CustomersRepository();
    }

    /**
     * Retrieve all customers with enriched display fields.
     */
    public function getCustomers(array $filters = []): array {
        $customers = $this->repository->findAll($filters);

        foreach ($customers as &$c) {
            $c['full_name']           = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
            $c['formatted_spent']     = '₱ ' . number_format((float)($c['total_spent'] ?? 0), 2);
            $c['registered_date_fmt'] = !empty($c['created_at']) ? date('M d, Y', strtotime($c['created_at'])) : '-';
        }

        return $customers;
    }

    /**
     * Retrieve single customer by ID with their booking history.
     */
    public function getCustomer(int $id): array {
        $customer = $this->repository->findById($id);
        if (!$customer) {
            throw new Exception("Customer #{$id} was not found.");
        }

        $customer['full_name']           = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $customer['formatted_spent']     = '₱ ' . number_format((float)($customer['total_spent'] ?? 0), 2);
        $customer['registered_date_fmt'] = !empty($customer['created_at']) ? date('M d, Y', strtotime($customer['created_at'])) : '-';
        $customer['booking_history']     = $this->repository->getCustomerBookings($id);

        return $customer;
    }

    /**
     * Create a new customer.
     */
    public function createCustomer(array $input): int {
        $this->validateCustomerInput($input);

        $email = trim($input['email'] ?? '');
        if ($email !== '' && $this->repository->checkEmailExists($email)) {
            throw new Exception("Email address '{$email}' is already associated with another customer.");
        }

        $customerData = [
            'first_name'   => trim($input['first_name']),
            'last_name'    => trim($input['last_name']),
            'phone_number' => trim($input['phone_number']),
            'email'        => $email,
            'address'      => trim($input['address'] ?? ''),
            'is_returning' => !empty($input['is_returning']) ? 1 : 0,
            'is_vip'       => !empty($input['is_vip']) ? 1 : 0,
            'notes'        => trim($input['notes'] ?? ''),
        ];

        return $this->repository->create($customerData);
    }

    /**
     * Update an existing customer.
     */
    public function updateCustomer(int $id, array $input): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Customer #{$id} was not found.");
        }

        $this->validateCustomerInput($input);

        $email = trim($input['email'] ?? '');
        if ($email !== '' && $this->repository->checkEmailExists($email, $id)) {
            throw new Exception("Email address '{$email}' is already associated with another customer.");
        }

        $customerData = [
            'first_name'   => trim($input['first_name']),
            'last_name'    => trim($input['last_name']),
            'phone_number' => trim($input['phone_number']),
            'email'        => $email,
            'address'      => trim($input['address'] ?? ''),
            'is_returning' => !empty($input['is_returning']) ? 1 : 0,
            'is_vip'       => !empty($input['is_vip']) ? 1 : 0,
            'notes'        => trim($input['notes'] ?? ''),
        ];

        return $this->repository->update($id, $customerData);
    }

    /**
     * Delete customer record.
     */
    public function deleteCustomer(int $id): bool {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new Exception("Customer #{$id} was not found.");
        }

        if ($this->repository->hasBookings($id)) {
            throw new Exception("Cannot delete customer '{$existing['first_name']} {$existing['last_name']}' because they have rental bookings on record.");
        }

        return $this->repository->delete($id);
    }

    /**
     * Get KPI statistics for dashboard cards.
     */
    public function getStats(): array {
        $raw = $this->repository->getStats();
        return [
            'total_customers'         => (int)($raw['total_customers'] ?? 0),
            'vip_count'               => (int)($raw['vip_count'] ?? 0),
            'returning_count'         => (int)($raw['returning_count'] ?? 0),
            'currently_renting_count' => (int)($raw['currently_renting_count'] ?? 0),
        ];
    }

    /**
     * Validate customer inputs.
     */
    private function validateCustomerInput(array $input): void {
        if (empty($input['first_name']) || trim($input['first_name']) === '') {
            throw new Exception("First name is required.");
        }
        if (empty($input['last_name']) || trim($input['last_name']) === '') {
            throw new Exception("Last name is required.");
        }
        if (empty($input['phone_number']) || trim($input['phone_number']) === '') {
            throw new Exception("Phone number is required.");
        }

        $email = trim($input['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("The email address provided is not valid.");
        }
    }
}

