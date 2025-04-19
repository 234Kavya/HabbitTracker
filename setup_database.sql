-- Create users table if it doesn't exist
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create habits table if it doesn't exist
CREATE TABLE IF NOT EXISTS habits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    frequency ENUM('daily', 'weekly', 'custom') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create habit_completions table if it doesn't exist
CREATE TABLE IF NOT EXISTS habit_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    habit_id INT,
    user_id INT,
    completion_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create habit templates table if it doesn't exist
CREATE TABLE IF NOT EXISTS habit_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    frequency ENUM('daily', 'weekly', 'custom') NOT NULL,
    is_beginner_friendly BOOLEAN DEFAULT true
);

-- Insert default templates
INSERT INTO habit_templates (name, description, category, frequency) VALUES
('Drink Water', 'Drink 8 glasses of water daily', 'Health', 'daily'),
('Read 10 Pages', 'Read at least 10 pages of a book', 'Learning', 'daily'),
('Exercise', '30 minutes of physical activity', 'Health', 'daily'),
('Meditate', '10 minutes of mindfulness practice', 'Wellness', 'daily'),
('Journal', 'Write daily reflections', 'Personal Growth', 'daily');

-- Create goals table if it doesn't exist
CREATE TABLE IF NOT EXISTS goals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    habit_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    target_value INT,
    current_value INT DEFAULT 0,
    start_date DATE,
    deadline DATE,
    status ENUM('active', 'completed', 'failed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (habit_id) REFERENCES habits(id)
);

-- Create reviews table for weekly/monthly summaries if it doesn't exist
CREATE TABLE IF NOT EXISTS habit_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    review_type ENUM('weekly', 'monthly') NOT NULL,
    start_date DATE,
    end_date DATE,
    most_followed TEXT,
    most_missed TEXT,
    suggestions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
); 