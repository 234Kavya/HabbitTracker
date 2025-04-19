# Habit Tracker

A comprehensive web application for tracking and managing daily habits, built with PHP, MySQL, and modern web technologies.

## Features

- User Authentication (Sign Up, Login, Logout)
- Habit Management (Create, Edit, Delete)
- Custom Habit Frequencies (Daily, Weekly, Custom Days)
- Visual Calendar View
- Streak Tracking
- Priority Levels
- Tag System
- Analytics Dashboard with Charts
- Responsive Design

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/habit-tracker.git
cd habit-tracker
```

2. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database_name < config/schema.sql
```

3. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials:
     ```php
     private $host = "localhost";
     private $db_name = "your_database_name";
     private $username = "your_username";
     private $password = "your_password";
     ```

4. Set up your web server:
   - Point your web server's document root to the project directory
   - Ensure the web server has write permissions for any upload directories
   - Configure URL rewriting if needed

5. Access the application through your web browser:
```
http://localhost/habit-tracker
```

## Usage

### Creating a New Habit

1. Click the "Add Habit" button on the dashboard
2. Fill in the habit details:
   - Name
   - Description (optional)
   - Frequency (Daily, Weekly, or Custom Days)
   - Priority (High, Medium, Low)
   - Tags (optional)
3. Click "Save Habit"

### Tracking Habits

- Use the calendar view to mark habits as completed
- View your current streaks and progress
- Check the analytics dashboard for detailed statistics

### Managing Habits

- Edit habits by clicking the three dots menu on any habit card
- Delete habits through the same menu
- Update habit details as needed

## Project Structure

```
habit-tracker/
├── api/                  # API endpoints
│   ├── analytics.php
│   ├── habit_completion.php
│   └── habits.php
├── config/              # Configuration files
│   ├── database.php
│   └── schema.sql
├── css/                 # Stylesheets
│   └── style.css
├── includes/            # Common includes
│   ├── footer.php
│   └── header.php
├── js/                  # JavaScript files
│   └── main.js
├── index.php           # Home page
├── login.php           # Login page
├── register.php        # Registration page
├── dashboard.php       # Main dashboard
├── analytics.php       # Analytics page
└── README.md           # This file
```

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for all database queries
- Session management
- Input validation and sanitization
- CSRF protection
- XSS prevention

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the UI framework
- Chart.js for analytics visualization
- Font Awesome for icons 