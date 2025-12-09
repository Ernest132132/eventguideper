<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    // Fail silently if not logged in
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$operativeId = $_SESSION['user_id'];
$pageName = $_POST['page'] ?? 'Unknown';

try {
    $stmt = $pdo->prepare("INSERT INTO portal_activity (operative_id, page_name) VALUES (?, ?)");
    $stmt->execute([$operativeId, $pageName]);
    
    echo json_encode(['status' => 'success', 'message' => 'Ping recorded']);
} catch (Exception $e) {
    // Log error but inform the client
    error_log("Ping failed: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
}
?>