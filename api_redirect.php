<?php
session_start();
require 'db.php';

// Operative ID is 0 if the user is not logged in (though they should be to view Confidants)
$operativeId = $_SESSION['user_id'] ?? 0; 
$targetName = $_GET['name'] ?? 'Unknown';
$destination = $_GET['dest'] ?? 'https://www.instagram.com/'; // Destination is the full Instagram URL

// Log the click
try {
    $stmt = $pdo->prepare("INSERT INTO instagram_clicks (operative_id, link_type, target_name) VALUES (?, ?, ?)");
    // link_type is fixed to 'PERFORMER' as requested
    $stmt->execute([$operativeId, 'PERFORMER', $targetName]); 
} catch (Exception $e) {
    // Log error but continue to redirect
    error_log("Instagram click logging failed: " . $e->getMessage());
}

// Redirect the user immediately
header("Location: " . $destination);
exit();
?>