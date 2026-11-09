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


/* ==========================================================================
   SEEDERS
   Default credentials:
   - owner / admin123
   - manager / admin123
   ========================================================================== */

INSERT INTO Users (username, password_hash, role) VALUES
('owner', '$2y$12$ugDKZ8Ij8OzG84X5ESjxpesBF1tqEuTZJJcUFw/K.ZD3a4yAGgKem', 'Owner'),
('manager', '$2y$12$ugDKZ8Ij8OzG84X5ESjxpesBF1tqEuTZJJcUFw/K.ZD3a4yAGgKem', 'Manager');

INSERT INTO Customers (first_name, last_name, phone_number, email, address, is_returning, is_vip, notes) VALUES
('Carlos', 'Mendoza', '09171234567', 'carlos.mendoza@gmail.com', '142 Rizal St., Makati City, Metro Manila', TRUE, TRUE, 'Preferred vehicle: Toyota Fortuner. Prefers assigned staff driver.'),
('Maria', 'Santos', '09189876543', 'maria.santos@yahoo.com', '88 Luna Ave., Quezon City, Metro Manila', TRUE, FALSE, 'Regular client for family weekend trips.'),
('Alexander', 'Cruz', '09205551234', 'alexander.cruz@outlook.com', '24 Bonifacio High St., Taguig City, Metro Manila', FALSE, TRUE, 'Corporate executive account. Requires official receipts.'),
('Jessica', 'Lim', '09951239876', 'jessica.lim@gmail.com', '77 Ortigas Ave., Pasig City, Metro Manila', TRUE, FALSE, 'Airport transfer and city rentals.'),
('Robert', 'Chen', '09228884321', 'robert.chen@asia-tech.com', '50 Ayala Ave., Makati City, Metro Manila', FALSE, FALSE, 'Visiting consultant for provincial manufacturing inspection.');

INSERT INTO Vehicles (make, model, year, license_plate, vin, type, seating_capacity, mileage, registration_expiry, insurance_expiry, status) VALUES
('Toyota', 'Fortuner GR-S 2.8', 2024, 'NGA-8821', 'VIN-TOY-FT-2024-001', 'SUV', 7, 18500, '2027-05-15', '2027-05-15', 'Available'),
('Toyota', 'HiAce Super Grandia', 2024, 'CAJ-5512', 'VIN-TOY-HA-2024-002', 'Van', 10, 24300, '2027-08-20', '2027-08-20', 'Rented'),
('Toyota', 'Innova 2.8 V', 2023, 'NEE-3190', 'VIN-TOY-IN-2023-003', 'MPV', 7, 36200, '2026-12-10', '2026-12-10', 'Available'),
('Toyota', 'Vios 1.5 G CVT', 2024, 'DAB-4491', 'VIN-TOY-VS-2024-004', 'Sedan', 5, 12400, '2027-03-30', '2027-03-30', 'Available'),
('Ford', 'Everest Titanium+ 4x4', 2024, 'NCF-7104', 'VIN-FRD-EV-2024-005', 'SUV', 7, 15800, '2027-06-25', '2027-06-25', 'Available'),
('Nissan', 'Urvan NV350 Commuter', 2023, 'TQI-9022', 'VIN-NIS-NV-2023-006', 'Van', 15, 41000, '2026-11-18', '2026-11-18', 'Available'),
('Mitsubishi', 'Montero Sport GLS', 2023, 'WXZ-6711', 'VIN-MIT-MS-2023-007', 'SUV', 7, 29800, '2027-01-14', '2027-01-14', 'Maintenance');

INSERT INTO Drivers (first_name, last_name, phone_number, license_number, license_expiry, status) VALUES
('Juan', 'Dela Cruz', '09193334444', 'N01-15-889012', '2028-09-15', 'Available'),
('Rodrigo', 'Tan', '09178889999', 'N02-18-654321', '2027-11-20', 'On Trip'),
('Eduardo', 'Ramos', '09204445555', 'N03-20-112233', '2029-04-10', 'Available'),
('Danilo', 'Bautista', '09227778888', 'N04-16-998877', '2027-02-28', 'On Leave');

INSERT INTO Bookings (booking_reference, customer_id, vehicle_id, driver_id, destination, number_of_passengers, start_date, end_date, booking_amount, booking_status, created_by) VALUES
('LR-202611-001', 1, 2, 2, 'Baguio City Heritage Tour', 8, '2026-11-08 07:00:00', '2026-11-12 18:00:00', 26000.00, 'Ongoing', 1),
('LR-202611-002', 2, 1, 1, 'Tagaytay Ridge & Highlands Resort', 5, '2026-11-15 08:30:00', '2026-11-17 17:00:00', 11400.00, 'Confirmed', 1),
('LR-202611-003', 3, 5, NULL, 'Subic Bay Freeport Business Summit', 4, '2026-11-20 06:00:00', '2026-11-22 20:00:00', 12900.00, 'Pending', 2),
('LR-202611-004', 4, 3, 3, 'Batangas Calatagan Beach Getaway', 6, '2026-11-01 06:00:00', '2026-11-03 19:00:00', 8400.00, 'Completed', 1),
('LR-202611-005', 5, 4, NULL, 'Metro Manila Corporate Site Visits', 2, '2026-11-05 09:00:00', '2026-11-06 18:00:00', 3600.00, 'Cancelled', 2);

INSERT INTO Maintenance (vehicle_id, service_type, service_date, mileage, notes) VALUES
(7, 'Brake Pad Replacement & Rotor Resurfacing', '2026-11-07', 29800, 'Front and rear brake pads replaced with genuine Mitsubishi parts.'),
(2, 'Periodic 20,000 KM Maintenance & Oil Change', '2026-10-15', 20150, 'Fully synthetic engine oil, oil filter, and air filter replaced.');


/* ==========================================================================
   SEEDERS
   Default credentials:
   - owner / admin123
   - manager / admin123
   ========================================================================== */

INSERT INTO Users (username, password_hash, role) VALUES
('owner', '$2y$12$ugDKZ8Ij8OzG84X5ESjxpesBF1tqEuTZJJcUFw/K.ZD3a4yAGgKem', 'Owner'),
('manager', '$2y$12$ugDKZ8Ij8OzG84X5ESjxpesBF1tqEuTZJJcUFw/K.ZD3a4yAGgKem', 'Manager');

INSERT INTO Customers (first_name, last_name, phone_number, email, address, is_returning, is_vip, notes) VALUES
('Carlos', 'Mendoza', '09171234567', 'carlos.mendoza@gmail.com', '142 Rizal St., Makati City, Metro Manila', TRUE, TRUE, 'Preferred vehicle: Toyota Fortuner. Prefers assigned staff driver.'),
('Maria', 'Santos', '09189876543', 'maria.santos@yahoo.com', '88 Luna Ave., Quezon City, Metro Manila', TRUE, FALSE, 'Regular client for family weekend trips.'),
('Alexander', 'Cruz', '09205551234', 'alexander.cruz@outlook.com', '24 Bonifacio High St., Taguig City, Metro Manila', FALSE, TRUE, 'Corporate executive account. Requires official receipts.'),
('Jessica', 'Lim', '09951239876', 'jessica.lim@gmail.com', '77 Ortigas Ave., Pasig City, Metro Manila', TRUE, FALSE, 'Airport transfer and city rentals.'),
('Robert', 'Chen', '09228884321', 'robert.chen@asia-tech.com', '50 Ayala Ave., Makati City, Metro Manila', FALSE, FALSE, 'Visiting consultant for provincial manufacturing inspection.');

INSERT INTO Vehicles (make, model, year, license_plate, vin, type, seating_capacity, mileage, registration_expiry, insurance_expiry, status) VALUES
('Toyota', 'Fortuner GR-S 2.8', 2024, 'NGA-8821', 'VIN-TOY-FT-2024-001', 'SUV', 7, 18500, '2027-05-15', '2027-05-15', 'Available'),
('Toyota', 'HiAce Super Grandia', 2024, 'CAJ-5512', 'VIN-TOY-HA-2024-002', 'Van', 10, 24300, '2027-08-20', '2027-08-20', 'Rented'),
('Toyota', 'Innova 2.8 V', 2023, 'NEE-3190', 'VIN-TOY-IN-2023-003', 'MPV', 7, 36200, '2026-12-10', '2026-12-10', 'Available'),
('Toyota', 'Vios 1.5 G CVT', 2024, 'DAB-4491', 'VIN-TOY-VS-2024-004', 'Sedan', 5, 12400, '2027-03-30', '2027-03-30', 'Available'),
('Ford', 'Everest Titanium+ 4x4', 2024, 'NCF-7104', 'VIN-FRD-EV-2024-005', 'SUV', 7, 15800, '2027-06-25', '2027-06-25', 'Available'),
('Nissan', 'Urvan NV350 Commuter', 2023, 'TQI-9022', 'VIN-NIS-NV-2023-006', 'Van', 15, 41000, '2026-11-18', '2026-11-18', 'Available'),
('Mitsubishi', 'Montero Sport GLS', 2023, 'WXZ-6711', 'VIN-MIT-MS-2023-007', 'SUV', 7, 29800, '2027-01-14', '2027-01-14', 'Maintenance');

INSERT INTO Drivers (first_name, last_name, phone_number, license_number, license_expiry, status) VALUES
('Juan', 'Dela Cruz', '09193334444', 'N01-15-889012', '2028-09-15', 'Available'),
('Rodrigo', 'Tan', '09178889999', 'N02-18-654321', '2027-11-20', 'On Trip'),
('Eduardo', 'Ramos', '09204445555', 'N03-20-112233', '2029-04-10', 'Available'),
('Danilo', 'Bautista', '09227778888', 'N04-16-998877', '2027-02-28', 'On Leave');

INSERT INTO Bookings (booking_reference, customer_id, vehicle_id, driver_id, destination, number_of_passengers, start_date, end_date, booking_amount, booking_status, created_by) VALUES
('LR-202611-001', 1, 2, 2, 'Baguio City Heritage Tour', 8, '2026-11-08 07:00:00', '2026-11-12 18:00:00', 26000.00, 'Ongoing', 1),
('LR-202611-002', 2, 1, 1, 'Tagaytay Ridge & Highlands Resort', 5, '2026-11-15 08:30:00', '2026-11-17 17:00:00', 11400.00, 'Confirmed', 1),
('LR-202611-003', 3, 5, NULL, 'Subic Bay Freeport Business Summit', 4, '2026-11-20 06:00:00', '2026-11-22 20:00:00', 12900.00, 'Pending', 2),
('LR-202611-004', 4, 3, 3, 'Batangas Calatagan Beach Getaway', 6, '2026-11-01 06:00:00', '2026-11-03 19:00:00', 8400.00, 'Completed', 1),
('LR-202611-005', 5, 4, NULL, 'Metro Manila Corporate Site Visits', 2, '2026-11-05 09:00:00', '2026-11-06 18:00:00', 3600.00, 'Cancelled', 2);

INSERT INTO Maintenance (vehicle_id, service_type, service_date, mileage, notes) VALUES
(7, 'Brake Pad Replacement & Rotor Resurfacing', '2026-11-07', 29800, 'Front and rear brake pads replaced with genuine Mitsubishi parts.'),
(2, 'Periodic 20,000 KM Maintenance & Oil Change', '2026-10-15', 20150, 'Fully synthetic engine oil, oil filter, and air filter replaced.');
