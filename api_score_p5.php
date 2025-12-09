<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// 1. POST: Save Score (UPSERT LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $newScore = (int)($data['score'] ?? 0);
    $iconId = (int)($data['icon_id'] ?? 1);
    $userId = $_SESSION['user_id'];
    
    if ($newScore > 0) {
        $realName = $_SESSION['codename'] ?? 'Unknown';
        $displayName = substr($realName, 0, 2) . '***';
        
        try {
            // Check for existing score
            $stmt = $pdo->prepare("SELECT score FROM high_scores_p5 WHERE operative_id = ?");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // UPDATE only if the new score is higher
                if ($newScore > $existing['score']) {
                    $update = $pdo->prepare("UPDATE high_scores_p5 SET score = ?, codename_display = ?, icon_id = ? WHERE operative_id = ?");
                    $update->execute([$newScore, $displayName, $iconId, $userId]);
                }
            } else {
                // INSERT if this is their first game
                $insert = $pdo->prepare("INSERT INTO high_scores_p5 (operative_id, codename_display, score, icon_id) VALUES (?, ?, ?, ?)");
                $insert->execute([$userId, $displayName, $newScore, $iconId]);
            }
            
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log("P5 DB Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'db_error']);
        }
    }
    exit();
}

// 2. GET: Retrieve Leaderboard (Simplified Query)
// Since we now only have 1 row per user, we don't need the complex GROUP BY/JOIN anymore!
$limit = 10; 
try {
    $sql = "SELECT codename_display, score, icon_id
            FROM high_scores_p5
            ORDER BY score DESC 
            LIMIT :limit";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'scores' => $scores]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to load leaderboard.']);
}
?>