-- Database Schema for Calendar and Task Management System

CREATE DATABASE IF NOT EXISTS calendar_tasks;
USE calendar_tasks;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    due_time TIME,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    category_id INT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority)
);

-- Insert default categories
INSERT INTO categories (name, color) VALUES
('Work', '#007bff'),
('Personal', '#28a745'),
('Shopping', '#ffc107'),
('Health', '#dc3545'),
('Other', '#6c757d');

-- Sample tasks for testing
INSERT INTO tasks (title, description, due_date, due_time, priority, category_id, status) VALUES
('Team Meeting', 'Weekly sync with development team', CURDATE(), '10:00:00', 'high', 1, 'pending'),
('Grocery Shopping', 'Buy vegetables and fruits', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'medium', 3, 'pending'),
('Gym Workout', 'Cardio and strength training', CURDATE(), '18:00:00', 'low', 4, 'pending'),
('Project Deadline', 'Submit final project deliverables', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 'high', 1, 'pending'),
('Doctor Appointment', 'Annual health checkup', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:30:00', 'high', 4, 'pending');