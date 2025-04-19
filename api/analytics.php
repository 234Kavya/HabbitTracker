<?php
require_once '../config/database.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $userId = getCurrentUserId();
    $data = [];
    
    // Get completion rate for the last 30 days
    $stmt = $conn->prepare("
        SELECT 
            h.id,
            h.name,
            COUNT(hc.id) as completions,
            COUNT(DISTINCT hc.completion_date) as days_completed,
            DATEDIFF(CURRENT_DATE, DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)) as total_days
        FROM habits h
        LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
            AND hc.completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        WHERE h.user_id = ?
        GROUP BY h.id, h.name
    ");
    $stmt->execute([$userId]);
    $data['completion_rates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get weekly completion data for the last 8 weeks
    $stmt = $conn->prepare("
        SELECT 
            YEARWEEK(hc.completion_date) as week,
            COUNT(DISTINCT hc.habit_id) as habits_completed,
            COUNT(DISTINCT hc.completion_date) as days_completed
        FROM habit_completions hc
        JOIN habits h ON h.id = hc.habit_id
        WHERE h.user_id = ?
            AND hc.completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 8 WEEK)
        GROUP BY YEARWEEK(hc.completion_date)
        ORDER BY week DESC
        LIMIT 8
    ");
    $stmt->execute([$userId]);
    $data['weekly_completions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current streaks
    $stmt = $conn->prepare("
        SELECT 
            h.id,
            h.name,
            (
                SELECT COUNT(*)
                FROM (
                    SELECT completion_date
                    FROM habit_completions
                    WHERE habit_id = h.id
                    ORDER BY completion_date DESC
                    LIMIT 30
                ) as recent_completions
                WHERE completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY)
            ) as current_streak,
            (
                SELECT MAX(streak)
                FROM (
                    SELECT COUNT(*) as streak
                    FROM (
                        SELECT completion_date,
                               @prev_date := @prev_date,
                               @streak := IF(DATEDIFF(completion_date, @prev_date) = 1, @streak + 1, 1) as streak,
                               @prev_date := completion_date
                        FROM habit_completions,
                             (SELECT @prev_date := NULL, @streak := 0) as vars
                        WHERE habit_id = h.id
                        ORDER BY completion_date
                    ) as streaks
                    GROUP BY streak
                ) as max_streaks
            ) as best_streak
        FROM habits h
        WHERE h.user_id = ?
    ");
    $stmt->execute([$userId]);
    $data['streaks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completion by priority
    $stmt = $conn->prepare("
        SELECT 
            h.priority,
            COUNT(DISTINCT hc.id) as completions,
            COUNT(DISTINCT h.id) as total_habits
        FROM habits h
        LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
            AND hc.completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        WHERE h.user_id = ?
        GROUP BY h.priority
    ");
    $stmt->execute([$userId]);
    $data['priority_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tag statistics
    $stmt = $conn->prepare("
        SELECT 
            t.name as tag,
            COUNT(DISTINCT h.id) as total_habits,
            COUNT(DISTINCT hc.id) as completions
        FROM tags t
        JOIN habit_tags ht ON t.id = ht.tag_id
        JOIN habits h ON h.id = ht.habit_id
        LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
            AND hc.completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        WHERE h.user_id = ?
        GROUP BY t.id, t.name
    ");
    $stmt->execute([$userId]);
    $data['tag_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 