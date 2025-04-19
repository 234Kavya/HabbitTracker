<?php
require_once '../config/database.php';
startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['habit_id']) || !isset($data['date'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // Verify habit belongs to user
        $stmt = $conn->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['habit_id'], getCurrentUserId()]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Habit not found']);
            exit;
        }
        
        // Check if completion already exists
        $stmt = $conn->prepare("SELECT id FROM habit_completions WHERE habit_id = ? AND completion_date = ?");
        $stmt->execute([$data['habit_id'], $data['date']]);
        
        if ($stmt->fetch()) {
            // Update existing completion
            $stmt = $conn->prepare("UPDATE habit_completions SET notes = ? WHERE habit_id = ? AND completion_date = ?");
            $stmt->execute([$data['notes'] ?? null, $data['habit_id'], $data['date']]);
        } else {
            // Create new completion
            $stmt = $conn->prepare("INSERT INTO habit_completions (habit_id, completion_date, notes) VALUES (?, ?, ?)");
            $stmt->execute([$data['habit_id'], $data['date'], $data['notes'] ?? null]);
        }
        
        // Update goal progress if exists
        $stmt = $conn->prepare("
            UPDATE goals 
            SET current_value = current_value + 1 
            WHERE habit_id = ? AND status = 'active'
        ");
        $stmt->execute([$data['habit_id']]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 