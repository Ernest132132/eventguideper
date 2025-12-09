<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // SET SESSION VARIABLES REQUIRED BY DASHBOARD
        $_SESSION['staff_id'] = $user['id'];
        $_SESSION['staff_name'] = $user['username'];
        $_SESSION['staff_role'] = $user['role'];
        
        // Redirect to Scanner Hub
        header("Location: scanner.php");
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Login | Mission Control</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=35"> 
</head>
<body>
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        <br><br>
        <h1 style="color: var(--velvet-gold);">MISSION CONTROL</h1>
        <p>Authorized Personnel Only</p>
        
        <div class="card">
            <?php if($error): ?>
                <div class="alert" style="border-color: red; color: red;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>OPERATIVE NAME:</label>
                <input type="text" name="username" required autofocus>
                
                <label>PASSPHRASE:</label>
                <input type="password" name="password" required style="background: rgba(0,0,0,0.3); border: 1px solid var(--velvet-gold); color: white; width: calc(100% - 22px); padding: 10px; text-align: center; margin-bottom: 15px;">
                
                <button type="submit" class="btn-gold">ACCESS TERMINAL</button>
            </form>
        </div>
        
        <br>
        <a href="index.php" style="color: #666; font-size: 0.8rem;">&larr; Guest Portal</a>
    </div>
</body>
</html>