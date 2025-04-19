<?php
class Database {
    private $host = "localhost";
    private $db_name = "habbit_tracker";
    private $username = "root";
    private $password = "root";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}

// Helper function to hash passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Helper function to verify passwords
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Helper function to start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Helper function to check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}
?> 