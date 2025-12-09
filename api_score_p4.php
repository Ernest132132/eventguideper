<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Only handle POST requests for completion tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    // We can use a simple table or modify an existing one. 
    // Assuming we want a simple tracker, we'll check if a table exists or just log it.
    // For simplicity, let's assume we have a table `game_completions` or similar, 
    // OR we can add a column to `high_scores` if you prefer.
    
    // Since you mentioned "track in the future", let's create/use a dedicated simple table if it doesn't exist,
    // OR we can re-use the high_scores table structure but with a fixed score of '1' to mean 'completed'.
    
    try {
        // Check if user already completed it to avoid duplicates (optional, but good for unique count)
        $stmt = $pdo->prepare("SELECT id FROM high_scores_p4 WHERE operative_id = ?");
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            // First time completion
            $realName = $_SESSION['codename'] ?? 'Unknown';
            $displayName = substr($realName, 0, 2) . '***';
            
            // Insert completion record. Score = 1 represents "Completed".
            // We assume high_scores_p4 exists similar to p3/p5. If not, you'll need to create it.
            $insert = $pdo->prepare("INSERT INTO high_scores_p4 (operative_id, codename_display, score, icon_id) VALUES (?, ?, 1, 1)");
            $insert->execute([$userId, $displayName]);
        }
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        // If table doesn't exist, fail silently or log
        error_log("P4 Completion Track Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Tracking failed']);
    }
    exit();
}
?>