CREATE DATABASE IF NOT EXISTS ict_support_system;
USE ict_support_system;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    employee_number VARCHAR(50) UNIQUE,
    phone VARCHAR(30),
    email VARCHAR(150) NOT NULL UNIQUE,
    job_title VARCHAR(120),
    department_id INT NULL,
    role ENUM('admin', 'ict', 'employee') NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code VARCHAR(40) NOT NULL UNIQUE,
    employee_id INT NOT NULL,
    department_id INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    assigned_to INT NULL,
    description TEXT,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Submitted', 'Assigned', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Submitted',
    resolution_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL,
    comment_text TEXT NOT NULL,
    is_timeline TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(120),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_sent TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT DEFAULT 0,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO departments (name) VALUES
('Administration'),
('Academics'),
('Finance'),
('Library'),
('Examinations')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO categories (name) VALUES
('Hardware'),
('Software'),
('Network'),
('Printer'),
('Internet'),
('Email')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO subcategories (category_id, name)
SELECT c.id, s.subname
FROM categories c
JOIN (
    SELECT 'Hardware' AS cname, 'Desktop Failure' AS subname UNION ALL
    SELECT 'Hardware', 'Laptop Battery' UNION ALL
    SELECT 'Software', 'Application Crash' UNION ALL
    SELECT 'Software', 'OS Update Error' UNION ALL
    SELECT 'Network', 'LAN Connection' UNION ALL
    SELECT 'Network', 'Wi-Fi Authentication' UNION ALL
    SELECT 'Printer', 'Paper Jam' UNION ALL
    SELECT 'Printer', 'Toner Issue' UNION ALL
    SELECT 'Internet', 'Slow Internet' UNION ALL
    SELECT 'Internet', 'No Internet Access' UNION ALL
    SELECT 'Email', 'Cannot Send Email' UNION ALL
    SELECT 'Email', 'Password Reset'
) s ON s.cname = c.name
WHERE NOT EXISTS (
    SELECT 1 FROM subcategories sc WHERE sc.category_id = c.id AND sc.name = s.subname
);

INSERT INTO users (full_name, employee_number, phone, email, job_title, department_id, role, password)
VALUES
('System Administrator', 'ADM-0001', '08000000001', 'admin@institution.edu', 'ICT Director', 1, 'admin', '$2y$10$F9JLAG4ziAJ9MWH3Mza11.kQDp/bLNu1/hq2ovSgJKRVoGCYMDvfa'),
('ICT Support Officer', 'ICT-0001', '08000000002', 'ict1@institution.edu', 'Support Engineer', 1, 'ict', '$2y$10$H5rH6Dz0C4PaElScehIAB.k/4/TVfes6U354svL0dXk9i7FAjZDGm'),
('Alice N. Employee', 'EMP-1001', '08000000003', 'alice.employee@institution.edu', 'Administrative Assistant', 1, 'employee', '$2y$10$L57knu8saNK2L2R1uQ9/DeKbJF7lhKP2rwxJ/RAQUmbWG6nnLzuWq'),
('Brian K. Employee', 'EMP-1002', '08000000004', 'brian.employee@institution.edu', 'Accounts Officer', 3, 'employee', '$2y$10$5PYAEtJD8qKJQxu9m.JJSuZcI03kfUAZyP1X73n9rGk0idtXg235a')
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);
