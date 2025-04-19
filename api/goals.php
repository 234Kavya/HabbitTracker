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
    
    if (!isset($data['habit_id']) || !isset($data['title']) || !isset($data['target_value']) || !isset($data['deadline'])) {
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
        
        // Deactivate any existing active goals for this habit
        $stmt = $conn->prepare("
            UPDATE goals 
            SET status = 'completed' 
            WHERE habit_id = ? AND status = 'active'
        ");
        $stmt->execute([$data['habit_id']]);
        
        // Create new goal
        $stmt = $conn->prepare("
            INSERT INTO goals (
                user_id, habit_id, title, description, 
                target_value, deadline, status
            ) VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            getCurrentUserId(),
            $data['habit_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['target_value'],
            $data['deadline']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 