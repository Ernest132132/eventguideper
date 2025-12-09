<?php
// --- FORCE HTTPS ---
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    $location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit;
}

// db.php - The Key to the Velvet Room

$host = 'mysql.irisinteractive.org';
$db   = 'allout_portal';
$user = 'igor_admin';
$pass = 'YaEcnJjVKrb9';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If connection fails, show a simple error
     die("Velvet Room Connection Error: " . $e->getMessage());
}

// Start session if not already started, but DO NOT generate tokens
if (session_status() === PHP_SESSION_NONE) session_start();
?>