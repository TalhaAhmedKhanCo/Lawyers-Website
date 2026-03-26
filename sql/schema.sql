-- Create the project database if it does not already exist.
CREATE DATABASE IF NOT EXISTS lawyer_portal;
USE lawyer_portal;

-- Store all users with a shared login table and role-based access.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer', 'lawyer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Store extended information only for lawyer accounts.
CREATE TABLE IF NOT EXISTS lawyer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialization VARCHAR(150) NOT NULL,
    location VARCHAR(120) NOT NULL,
    bio TEXT,
    meeting_place VARCHAR(255),
    experience_years INT DEFAULT 0,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bar_registration_no VARCHAR(100),
    photo_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lawyer_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Store the appointment slots that each lawyer publishes.
CREATE TABLE IF NOT EXISTS appointment_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lawyer_id INT NOT NULL,
    slot_date DATE NOT NULL,
    slot_time TIME NOT NULL,
    is_booked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_slot_lawyer FOREIGN KEY (lawyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Store booked appointments between customers and lawyers.
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    lawyer_id INT NOT NULL,
    slot_id INT NOT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointment_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_lawyer FOREIGN KEY (lawyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_appointment_slot FOREIGN KEY (slot_id) REFERENCES appointment_slots(id) ON DELETE CASCADE
);

-- Insert a default administrator account.
-- Password: admin123
INSERT INTO users (name, email, password, role)
SELECT 'System Admin', 'admin@lawyerportal.com', '$2y$12$DMfnVdO9lbUFUZY7vNwybuuS.URGQ4jM1iXOboufcDSX6d2OUTnSy', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@lawyerportal.com'
);

-- Create table for contact form submissions.
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(120),
    subject VARCHAR(150),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create table for newsletter subscriptions.
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(120) UNIQUE NOT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
