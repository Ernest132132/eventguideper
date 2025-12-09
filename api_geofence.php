<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    exit(); // Fail silently
}

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);
$lat = $data['lat'] ?? null;
$lon = $data['lon'] ?? null;

if ($lat && $lon) {
    try {
        $stmt = $pdo->prepare("INSERT INTO location_pings (operative_id, lat, lon) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $lat, $lon]);
        echo json_encode(['status' => 'logged']);
    } catch (Exception $e) {
        // Ignore errors
    }
}
?>