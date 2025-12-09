<?php
session_start();
require 'db.php';

// --- CONFIGURATION: SUPER USERS ONLY ---
$ALLOWED_NUKERS = ['Ellen', 'AdminEru', 'AdminBen'];

// 1. SECURITY CHECK
if (!isset($_SESSION['staff_role']) || 
    $_SESSION['staff_role'] !== 'admin' || 
    !in_array($_SESSION['staff_name'], $ALLOWED_NUKERS)) {
    
    // Log the attempt (optional, but good for security)
    error_log("Unauthorized Nuke Attempt by: " . ($_SESSION['staff_name'] ?? 'Unknown'));
    die("ACCESS DENIED: You do not have clearance level for this protocol.");
}

$message = "";
$status = "";

// 2. HANDLE RESET
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid Security Token. Please refresh the page.");
    }
    $password = $_POST['confirmation'];
    
    // SAFETY CHECK
    if ($password !== 'CONFIRM') {
        $message = "Incorrect confirmation code. Aborted.";
        $status = "error";
    } else {
        try {
            // A. RESET GUESTS & LOGS
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0"); 
            $pdo->exec("TRUNCATE TABLE operatives");
            $pdo->exec("TRUNCATE TABLE mission_logs");
            $pdo->exec("TRUNCATE TABLE location_pings");
            $pdo->exec("TRUNCATE TABLE instagram_clicks");
            $pdo->exec("TRUNCATE TABLE bookings"); 
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // B. RESET INVENTORY TO DEFAULTS
            $default_bp = 100;
            $default_std = 500;
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('battle_pass_limit', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$default_bp, $default_bp]);
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('standard_reward_limit', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$default_std, $default_std]);

            $message = "SYSTEM WIPE SUCCESSFUL. READY FOR LIVE.";
            $status = "success";
        } catch (Exception $e) {
            $message = "CRITICAL ERROR: " . $e->getMessage();
            $status = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Reset | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=30">
    <style>
        body { background: #1a0505; } 
        .danger-box { border: 2px solid red; padding: 20px; background: rgba(255, 0, 0, 0.1); text-align: center; }
        .btn-danger { background: red; color: white; border: 1px solid white; width: 100%; padding: 15px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <h1 style="color: red;">DANGER ZONE</h1>
            <p>AUTHORIZED: <?php echo htmlspecialchars($_SESSION['staff_name']); ?></p>
        </div>

        <?php if($message): ?>
            <div class="alert" style="border-color: <?php echo ($status=='error')?'red':'lime'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="danger-box">
            <h2 style="color: white;">WIPE ALL EVENT DATA</h2>
            <p style="color: #ffcccc; font-size: 0.9rem;">
                This will permanently delete:<br>
                - All Guest Accounts (Operatives/Observers)<br>
                - All Scan Logs & History<br>
                - All GPS & Metric Data<br>
                - Reset Inventory Counts
            </p>
            <p style="color: lime; font-size: 0.9rem;">
                * Staff Accounts, Vendors, and Performers will NOT be deleted.
            </p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="wipe">
                <label style="color: red;">Type "CONFIRM" to execute:</label>
                <input type="text" name="confirmation" required autocomplete="off" style="border: 1px solid red; color: red;">
                <br><br>
                <button type="submit" class="btn-danger">NUKE DATABASE</button>
            </form>
        </div>

        <br><br>
        <a href="admin_dashboard.php" class="btn-gold">&larr; Return to Safety</a>
    </div>
</body>
</html>