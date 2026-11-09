CREATE DATABASE IF NOT EXISTS car_rental_db;
USE car_rental_db;

CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Manager') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE,
    license_number VARCHAR(50) UNIQUE, -- Removed NOT NULL to allow non-driving payers
    address TEXT,
    status ENUM('Active', 'Blacklisted') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vin VARCHAR(50) UNIQUE NOT NULL,
    daily_rate DECIMAL(10,2) NOT NULL,
    status ENUM('Available', 'Rented', 'Maintenance', 'Inactive') DEFAULT 'Available'
);

CREATE TABLE Drivers (
    -- These are your staff drivers/chauffeurs, not the customers
    driver_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    daily_fee DECIMAL(10,2) NOT NULL,
    status ENUM('Available', 'On Trip', 'On Leave') DEFAULT 'Available'
);

CREATE TABLE Bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT, -- Staff driver, if the customer hired one
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Pending', 'Partial', 'Paid', 'Refunded') DEFAULT 'Pending',
    booking_status ENUM('Reserved', 'Active', 'Completed', 'Cancelled') DEFAULT 'Reserved',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES Drivers(driver_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);

CREATE TABLE Additional_Drivers (
    -- Tracks the actual people permitted to drive the rented vehicle
    additional_driver_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    license_number VARCHAR(50) NOT NULL, -- Mandatory for anyone getting behind the wheel
    phone_number VARCHAR(20),
    FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id) ON DELETE CASCADE
);

CREATE TABLE Maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    start_date DATE NOT NULL,
    completion_date DATE,
    status ENUM('Scheduled', 'In Progress', 'Completed') DEFAULT 'Scheduled',
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE CASCADE
);

CREATE TABLE Transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('Income', 'Expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reference_id INT, 
    description TEXT,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logged_by INT NOT NULL,
    FOREIGN KEY (logged_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);