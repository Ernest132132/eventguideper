<?php
session_start();
require 'db.php';

// Security: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle Creation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parentId = $_SESSION['user_id'];
    
    // 1. Determine Name (Auto-increment if empty)
    $nameInput = trim($_POST['codename'] ?? '');
    
    if (empty($nameInput)) {
        // Count existing children to make "Recruit 1", "Recruit 2"
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM operatives WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $count = $stmt->fetchColumn();
        $finalName = "Recruit " . ($count + 1);
    } else {
        $finalName = htmlspecialchars($nameInput);
    }

    // 2. Insert Immediately (Status: Eligible or Active)
    // We set status='active' so they are ready to get stamps immediately.
    // They skip 'pending' because they cannot sign a contract.
    $auth = bin2hex(random_bytes(16));
    $rescue = rand(1000, 9999);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO operatives (codename, role, status, parent_id, auth_token, rescue_code) VALUES (?, 'recruit', 'active', ?, ?, ?)");
        $stmt->execute([$finalName, $parentId, $auth, $rescue]);
        
        // 3. Redirect back to ID Card (viewing the new child)
        $newId = $pdo->lastInsertId();
        header("Location: id_card.php?view_id=" . $newId);
        exit();
        
    } catch (Exception $e) {
        $error = "Error adding recruit: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Recruit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <h1>ADD RECRUIT</h1>
            <p>Create a reward-only account.</p>
        </div>

        <div class="card">
            <form method="POST">
                <label>RECRUIT NAME (OPTIONAL)</label>
                <input type="text" name="codename" placeholder="e.g. Recruit 1" autocomplete="off">
                <p style="font-size: 0.8rem; color: #aaa;">Leave blank to auto-generate.</p>
                
                <button type="submit" class="btn-gold" style="width: 100%; margin-top: 15px;">
                    CREATE ACCOUNT
                </button>
            </form>
        </div>
        <br>
        <a href="id_card.php" style="color: #666;">&larr; Cancel</a>
    </div>
</body>
</html>