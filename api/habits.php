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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        // Create new habit
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $conn->beginTransaction();
            
            // Check if creating from template
            if (isset($data['template_id'])) {
                // Get template details
                $stmt = $conn->prepare("SELECT name, description, frequency, category FROM habit_templates WHERE id = ?");
                $stmt->execute([$data['template_id']]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$template) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Template not found']);
                    exit;
                }
                
                // Use template data
                $data['name'] = $template['name'];
                $data['description'] = $template['description'];
                $data['frequency'] = $template['frequency'];
            }
            
            // Validate required parameters
            if (!isset($data['name']) || !isset($data['frequency'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Insert habit
            $stmt = $conn->prepare("
                INSERT INTO habits (user_id, name, description, frequency, priority)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                getCurrentUserId(),
                $data['name'],
                $data['description'] ?? '',
                $data['frequency'],
                $data['priority'] ?? 'medium'
            ]);
            
            $habitId = $conn->lastInsertId();
            
            // Insert custom frequency days if applicable
            if ($data['frequency'] === 'custom' && isset($data['custom_days'])) {
                $stmt = $conn->prepare("
                    INSERT INTO habit_frequency_days (habit_id, day_of_week)
                    VALUES (?, ?)
                ");
                
                foreach ($data['custom_days'] as $day) {
                    $stmt->execute([$habitId, $day]);
                }
            }
            
            // Insert tags if provided
            if (isset($data['tags']) && is_array($data['tags'])) {
                $stmt = $conn->prepare("
                    INSERT IGNORE INTO tags (name) VALUES (?)
                ");
                $tagStmt = $conn->prepare("
                    INSERT INTO habit_tags (habit_id, tag_id)
                    SELECT ?, id FROM tags WHERE name = ?
                ");
                
                foreach ($data['tags'] as $tag) {
                    $stmt->execute([$tag]);
                    $tagStmt->execute([$habitId, $tag]);
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'habit_id' => $habitId]);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'PUT':
        // Update habit
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['habit_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing habit_id']);
            exit;
        }
        
        try {
            $conn->beginTransaction();
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM habits WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['habit_id'], getCurrentUserId()]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Habit not found or access denied']);
                exit;
            }
            
            // Update habit
            $updateFields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $data['description'];
            }
            if (isset($data['frequency'])) {
                $updateFields[] = "frequency = ?";
                $params[] = $data['frequency'];
            }
            if (isset($data['priority'])) {
                $updateFields[] = "priority = ?";
                $params[] = $data['priority'];
            }
            
            if (!empty($updateFields)) {
                $params[] = $data['habit_id'];
                $stmt = $conn->prepare("
                    UPDATE habits
                    SET " . implode(', ', $updateFields) . "
                    WHERE id = ?
                ");
                $stmt->execute($params);
            }
            
            // Update custom frequency days if applicable
            if (isset($data['frequency']) && $data['frequency'] === 'custom' && isset($data['custom_days'])) {
                $stmt = $conn->prepare("DELETE FROM habit_frequency_days WHERE habit_id = ?");
                $stmt->execute([$data['habit_id']]);
                
                $stmt = $conn->prepare("INSERT INTO habit_frequency_days (habit_id, day_of_week) VALUES (?, ?)");
                foreach ($data['custom_days'] as $day) {
                    $stmt->execute([$data['habit_id'], $day]);
                }
            }
            
            // Update tags if provided
            if (isset($data['tags'])) {
                $stmt = $conn->prepare("DELETE FROM habit_tags WHERE habit_id = ?");
                $stmt->execute([$data['habit_id']]);
                
                if (!empty($data['tags'])) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                    $tagStmt = $conn->prepare("
                        INSERT INTO habit_tags (habit_id, tag_id)
                        SELECT ?, id FROM tags WHERE name = ?
                    ");
                    
                    foreach ($data['tags'] as $tag) {
                        $stmt->execute([$tag]);
                        $tagStmt->execute([$data['habit_id'], $tag]);
                    }
                }
            }
            
            $conn->commit();
            echo json_encode(['success' => true]);
            
        } catch (PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    case 'DELETE':
        // Delete habit
        $habitId = $_GET['id'] ?? null;
        
        if (!$habitId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing habit_id']);
            exit;
        }
        
        try {
            $stmt = $conn->prepare("DELETE FROM habits WHERE id = ? AND user_id = ?");
            $stmt->execute([$habitId, getCurrentUserId()]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Habit not found or access denied']);
            }
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?> 