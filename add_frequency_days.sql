-- Custom frequency days table
CREATE TABLE IF NOT EXISTS habit_frequency_days (
    habit_id INT,
    day_of_week INT CHECK (day_of_week >= 0 AND day_of_week <= 6), -- 0 (Sunday) to 6 (Saturday)
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    PRIMARY KEY (habit_id, day_of_week)
); 