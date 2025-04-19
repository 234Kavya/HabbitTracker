-- Drop the existing table if it exists
DROP TABLE IF EXISTS habit_completions;

-- Recreate the habit_completions table with correct column names
CREATE TABLE habit_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    habit_id INT,
    completion_date DATE,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion (habit_id, completion_date)
); 