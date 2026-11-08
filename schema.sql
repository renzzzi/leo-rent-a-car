-- dbname: leo_rent_a_car_db

DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS bookings;


CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    booking_date DATETIME NOT NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
)

CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE, -- nullable
    contact_number VARCHAR(255) UNIQUE -- nullable
)

CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plate_number VARCHAR(255) NOT NULL
)

CREATE TABLE drivers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    license_number VARCHAR(255) NOT NULL,
    contact_number VARCHAR(255) NOT NULL
)



/* SEEDERS */

INSERT INTO customers (first_name, last_name, email, contact_number) VALUES
('John', 'Doe', 'john.doe@gmail.com', '09171234567'),
('Jane', 'Smith', 'jane.smith@gmail.com', '09181234567'),
('Michael', 'Johnson', 'michael.j@gmail.com', '09191234567');

INSERT INTO vehicles (plate_number) VALUES
('ABC-1234'),
('XYZ-5678'),
('DEF-9012');

INSERT INTO drivers (first_name, last_name, license_number, contact_number) VALUES
('Robert', 'Garcia', 'N01-23-456789', '09201234567'),
('David', 'Santos', 'N02-34-567890', '09211234567'),
('James', 'Reyes', 'N03-45-678901', '09221234567');

INSERT INTO bookings (customer_id, vehicle_id, driver_id, booking_date) VALUES
(1, 1, 1, '2026-11-01 08:00:00'),
(2, 2, 2, '2026-11-02 10:30:00'),
(3, 3, 3, '2026-11-03 14:00:00');

