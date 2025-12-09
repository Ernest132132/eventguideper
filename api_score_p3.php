<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// 1. POST: Save Score for P3 (Only if Highest)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newScore = (int)($data['score'] ?? 0);
    $iconId = (int)($data['icon_id'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    if ($newScore > 0) {
        $realName = $_SESSION['codename'] ?? 'Unknown';
        $displayName = substr($realName, 0, 2) . '***';
        
        try {
            // A. Check for existing score
            $stmt = $pdo->prepare("SELECT score FROM high_scores_p3 WHERE operative_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update ONLY if new score is higher
                if ($newScore > $existing['score']) {
                    $update = $pdo->prepare("UPDATE high_scores_p3 SET score = ?, codename_display = ?, icon_id = ? WHERE operative_id = ?");
                    $update->execute([$newScore, $displayName, $iconId, $userId]);
                }
                // If lower, do nothing (keep high score)
            } else {
                // Insert new record
                $insert = $pdo->prepare("INSERT INTO high_scores_p3 (operative_id, codename_display, score, icon_id) VALUES (?, ?, ?, ?)");
                $insert->execute([$userId, $displayName, $newScore, $iconId]);
            }
            
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log("P3 DB Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'db_error', 'message' => 'Failed to save P3 score.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Score must be positive']);
    }
    exit();
}

// 2. GET: Retrieve Leaderboard for P3
$limit = 10; 

try {
    // Simple Select because we now enforce 1 row per user in logic above
    $sql = "SELECT codename_display, score, icon_id
            FROM high_scores_p3
            ORDER BY score DESC 
            LIMIT :limit";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'scores' => $scores]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to load P3 leaderboard.']);
}
?>