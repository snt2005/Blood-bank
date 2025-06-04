SET FOREIGN_KEY_CHECKS = 0;

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS bloodbank;
USE bloodbank;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS emergency_requests;
DROP TABLE IF EXISTS blood_requests;
DROP TABLE IF EXISTS blood_units;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS donors;
DROP TABLE IF EXISTS recipients;
DROP TABLE IF EXISTS hospitals;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS hospital_inventory_counts;

-- Create users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'hospital', 'donor', 'recipient') NOT NULL,
    email VARCHAR(100) UNIQUE,
    status ENUM('pending', 'active', 'inactive', 'suspended') DEFAULT 'pending',
    full_name VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    profile_picture VARCHAR(255) DEFAULT NULL
);

-- Create donors table
CREATE TABLE donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    age INT CHECK (age >= 18),
    weight INT CHECK (weight >= 50),
    blood_group VARCHAR(3) NOT NULL,
    last_donation_date DATE NULL,
    contact VARCHAR(15) NOT NULL,
    city VARCHAR(50) NOT NULL,
    address TEXT,
    medical_conditions TEXT,
    medications TEXT,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create recipients table
CREATE TABLE recipients (
    recipient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    age INT,
    blood_group VARCHAR(3) NOT NULL,
    contact VARCHAR(15) NOT NULL,
    city VARCHAR(50) NOT NULL,
    address TEXT,
    medical_condition TEXT,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create hospitals table
CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    contact VARCHAR(15) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    operating_hours TEXT,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create appointments table
CREATE TABLE appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    hospital_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
);

-- Create blood_units table
CREATE TABLE blood_units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NULL,
    hospital_id INT NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    component_type ENUM('Whole', 'RBC', 'Plasma', 'Platelets') DEFAULT 'Whole',
    volume_ml INT DEFAULT 450,
    donation_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('available', 'reserved', 'used', 'expired', 'in-transit', 'discarded') DEFAULT 'available',
    test_results TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id) ON DELETE SET NULL,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
);

-- Create blood_requests table
CREATE TABLE blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    hospital_id INT NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    units_required INT NOT NULL,
    component_type ENUM('Whole', 'RBC', 'Plasma', 'Platelets') DEFAULT 'Whole',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    required_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'fulfilled') DEFAULT 'pending',
    priority ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
    reason TEXT,
    notes TEXT,
    FOREIGN KEY (recipient_id) REFERENCES recipients(recipient_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE
);

-- Create emergency_requests table
CREATE TABLE emergency_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(100) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    units_required INT NOT NULL,
    urgency_level ENUM('high', 'critical') NOT NULL,
    reason TEXT,
    hospital_id INT,
    request_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'fulfilled', 'rejected') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE SET NULL
);

-- Create notifications table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create hospital_inventory_counts table
CREATE TABLE hospital_inventory_counts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hospital_id INT NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    component_type ENUM('Whole', 'RBC', 'Plasma', 'Platelets') DEFAULT 'Whole',
    available_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE CASCADE,
    UNIQUE KEY unique_inventory (hospital_id, blood_group, component_type)
);

-- Insert sample admin user
INSERT INTO users (username, password, role, email, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@bloodbank.com', 'active');

-- Insert sample hospital
INSERT INTO users (username, password, role, email, status) VALUES 
('cityhospital', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hospital', 'hospital@bloodbank.com', 'active');

INSERT INTO hospitals (user_id, name, address, city, contact, license_number, operating_hours) VALUES 
(2, 'City General Hospital', '123 Main Street', 'New York', '555-0123', 'HOSP123456', '24/7');

-- Insert sample donor
INSERT INTO users (username, password, role, email, status) VALUES 
('johndoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'donor', 'john@example.com', 'active');

INSERT INTO donors (user_id, name, age, weight, blood_group, contact, city) VALUES 
(3, 'John Doe', 25, 70, 'O+', '555-0124', 'New York');

-- Insert sample recipient
INSERT INTO users (username, password, role, email, status) VALUES 
('janedoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recipient', 'jane@example.com', 'active');

INSERT INTO recipients (user_id, name, age, blood_group, contact, city) VALUES 
(4, 'Jane Doe', 30, 'A+', '555-0125', 'New York');

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE recipients MODIFY medical_condition TEXT NULL;