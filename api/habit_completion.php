<?php
require_once '../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['habit_id']) || !isset($data['date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Check if habit belongs to user
    $stmt = $conn->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['habit_id'], getCurrentUserId()]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Habit not found or access denied']);
        exit;
    }
    
    // Check if completion already exists
    $stmt = $conn->prepare("SELECT id FROM habit_completions WHERE habit_id = ? AND completion_date = ?");
    $stmt->execute([$data['habit_id'], $data['date']]);
    
    if ($stmt->fetch()) {
        // Delete completion
        $stmt = $conn->prepare("DELETE FROM habit_completions WHERE habit_id = ? AND completion_date = ?");
        $stmt->execute([$data['habit_id'], $data['date']]);
        $completed = false;
    } else {
        // Add completion
        $stmt = $conn->prepare("INSERT INTO habit_completions (habit_id, completion_date) VALUES (?, ?)");
        $stmt->execute([$data['habit_id'], $data['date']]);
        $completed = true;
    }
    
    // Calculate current streak
    $stmt = $conn->prepare("
        SELECT COUNT(*) as streak
        FROM (
            SELECT completion_date
            FROM habit_completions
            WHERE habit_id = ?
            ORDER BY completion_date DESC
            LIMIT 30
        ) as recent_completions
        WHERE completion_date >= DATE_SUB(?, INTERVAL 1 DAY)
    ");
    $stmt->execute([$data['habit_id'], $data['date']]);
    $streak = $stmt->fetch()['streak'];
    
    echo json_encode([
        'success' => true,
        'completed' => $completed,
        'streak' => $streak
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 