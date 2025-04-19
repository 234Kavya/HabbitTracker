-- Create database
CREATE DATABASE IF NOT EXISTS habbit_tracker;
USE habbit_tracker;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Habits table
CREATE TABLE IF NOT EXISTS habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    frequency ENUM('daily', 'weekly', 'custom') NOT NULL,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Habit completion tracking
CREATE TABLE IF NOT EXISTS habit_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    completion_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_habit_date (habit_id, completion_date)
);

-- Tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Habit-Tag relationship table
CREATE TABLE IF NOT EXISTS habit_tags (
    habit_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (habit_id, tag_id),
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Custom frequency days for habits
CREATE TABLE IF NOT EXISTS habit_frequency_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE
); 