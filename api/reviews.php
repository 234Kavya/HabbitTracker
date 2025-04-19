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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = $_GET['type'] ?? 'weekly';
    $userId = getCurrentUserId();
    
    try {
        // Calculate date range
        if ($type === 'weekly') {
            $startDate = date('Y-m-d', strtotime('-1 week'));
            $endDate = date('Y-m-d');
        } else {
            $startDate = date('Y-m-d', strtotime('-1 month'));
            $endDate = date('Y-m-d');
        }
        
        // Get most followed habits
        $stmt = $conn->prepare("
            SELECT h.name, COUNT(hc.id) as completion_count
            FROM habits h
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id
            WHERE h.user_id = ?
            AND hc.completion_date BETWEEN ? AND ?
            GROUP BY h.id
            ORDER BY completion_count DESC
            LIMIT 3
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $mostFollowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get most missed habits
        $stmt = $conn->prepare("
            SELECT h.name, 
                   DATEDIFF(?, ?) - COUNT(hc.id) as missed_days
            FROM habits h
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id
            AND hc.completion_date BETWEEN ? AND ?
            WHERE h.user_id = ? AND h.frequency = 'daily'
            GROUP BY h.id
            ORDER BY missed_days DESC
            LIMIT 3
        ");
        $stmt->execute([$endDate, $startDate, $startDate, $endDate, $userId]);
        $mostMissed = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate review text
        $mostFollowedText = "Great job with: \n";
        foreach ($mostFollowed as $habit) {
            $mostFollowedText .= "- {$habit['name']} ({$habit['completion_count']} times)\n";
        }
        
        $mostMissedText = "Could improve: \n";
        foreach ($mostMissed as $habit) {
            $mostMissedText .= "- {$habit['name']} (missed {$habit['missed_days']} days)\n";
        }
        
        $suggestions = "Suggestions:\n";
        if (!empty($mostMissed)) {
            $suggestions .= "- Try setting reminders for habits you often miss\n";
            $suggestions .= "- Consider adjusting the frequency of some habits\n";
            $suggestions .= "- Break down challenging habits into smaller steps\n";
        } else {
            $suggestions .= "- You're doing great! Consider adding new habits\n";
            $suggestions .= "- Try increasing the difficulty of existing habits\n";
        }
        
        // Save review
        $stmt = $conn->prepare("
            INSERT INTO habit_reviews (
                user_id, review_type, start_date, end_date,
                most_followed, most_missed, suggestions
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $type,
            $startDate,
            $endDate,
            $mostFollowedText,
            $mostMissedText,
            $suggestions
        ]);
        
        echo json_encode([
            'success' => true,
            'most_followed' => nl2br($mostFollowedText),
            'most_missed' => nl2br($mostMissedText),
            'suggestions' => nl2br($suggestions)
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 