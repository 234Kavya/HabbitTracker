-- Add notes to habit_completions table
ALTER TABLE habit_completions
ADD notes TEXT;

-- Create habit templates table
CREATE TABLE habit_templates (
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

-- Create goals table
CREATE TABLE goals (
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

-- Create reviews table for weekly/monthly summaries
CREATE TABLE habit_reviews (
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