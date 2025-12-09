<?php
session_start();
require 'db.php';

// fast exit if not logged in
if (!isset($_SESSION['user_id'])) exit();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    
    try {
        // Update the last_active timestamp
        $stmt = $pdo->prepare("UPDATE operatives SET last_active = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        // Silently fail, it's just a heartbeat
    }
}
?>