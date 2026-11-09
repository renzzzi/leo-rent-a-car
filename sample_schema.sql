CREATE DATABASE IF NOT EXISTS car_rental_db;
USE car_rental_db;

CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Owner', 'Manager') DEFAULT 'Owner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE,
    address TEXT,
    is_returning BOOLEAN DEFAULT FALSE,
    is_vip BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Vehicles (
    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vin VARCHAR(50) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    seating_capacity INT NOT NULL,
    mileage INT NOT NULL DEFAULT 0,
    registration_expiry DATE NOT NULL,
    insurance_expiry DATE NOT NULL,
    status ENUM('Available', 'Rented', 'Maintenance', 'Inactive') DEFAULT 'Available'
);

CREATE TABLE Drivers (
    driver_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_expiry DATE NOT NULL,
    status ENUM('Available', 'On Trip', 'On Leave') DEFAULT 'Available'
);

CREATE TABLE Bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT, 
    destination VARCHAR(255) NOT NULL,
    number_of_passengers INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    booking_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('Pending', 'Confirmed', 'Ongoing', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES Customers(customer_id) ON DELETE RESTRICT,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE RESTRICT,
    FOREIGN KEY (driver_id) REFERENCES Drivers(driver_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);

CREATE TABLE Maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    service_date DATE NOT NULL,
    mileage INT NOT NULL,
    notes TEXT,
    FOREIGN KEY (vehicle_id) REFERENCES Vehicles(vehicle_id) ON DELETE CASCADE
);