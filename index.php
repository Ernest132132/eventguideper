<?php
session_start();
require 'functions.php';

$error = "";

// Login Check
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Determine Role
    $role = isset($_POST['btn_observer']) ? 'observer' : 'operative';
    
    // BACKEND CLEANING: Remove anything that isn't a letter or number
    $raw_name = $_POST['codename'] ?? '';
    $codename = preg_replace("/[^a-zA-Z0-9]/", "", $raw_name);
    
    // Check validity
    if ($role === 'operative' && empty($codename)) {
        $error = "Codename must contain only letters and numbers.";
    } else {
        // Call the function (which now sets Status correctly)
        $result = loginUser($pdo, $codename, $role);
        
        // If we return, there was an error
        if ($result) {
            $error = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome | Lantern Rite 2025</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=52">
</head>
<body>

    <div class="container page-visible">
        <div style="text-align: center; padding: 20px 0;">
            <div style="font-size: 3rem; margin-bottom: 8px;">🏮</div>
            <h1 style="margin-bottom: 4px;">Lantern Rite</h1>
            <p style="font-size: 1.1rem; color: var(--liyue-red); font-weight: 600; margin: 0;">2025</p>
            <p style="margin-top: 12px;">Welcome, Traveler! Register to join the festivities.</p>
        </div>

        <div class="card">
            <h2>Liyue Registry</h2>
            <?php if($error): ?>
                <div class="alert"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="device_os" id="device_os">
                <input type="hidden" name="user_agent" id="user_agent">
                <input type="hidden" name="load_time" id="load_time">

                <label>Your Name / Alias</label>
                <input type="text"
                       name="codename"
                       placeholder="Enter your name..."
                       maxlength="20"
                       autocomplete="off"
                       pattern="[a-zA-Z0-9]+"
                       title="Only letters and numbers are allowed."
                       oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, '')">

                <button type="submit" name="btn_operative" class="btn-gold" style="margin-top: 8px;">🏮 Join the Festival</button>

                <div class="split-container">
                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 12px;">
                        <strong>Just visiting?</strong> Browse the schedule and vendors without registering.
                    </p>
                    <button type="submit" name="btn_observer" class="btn-outline" style="width: 100%; padding: 12px; background: transparent; border: 2px solid var(--paper-tan); border-radius: 10px; cursor: pointer; font-weight: 600;">
                        Continue as Visitor
                    </button>
                </div>
            </form>
        </div>

        <div style="text-align: center; padding: 16px 0;">
            <a href="recover.php" style="font-size: 0.9rem;">
                <i class="fa-solid fa-key"></i> Lost your pass? Recover it here
            </a>
        </div>

        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 20px;">
            By registering, you agree to our <a href="tos.php">Terms of Service</a>.
        </p>
    </div>

    <script>
        document.getElementById('device_os').value = navigator.platform;
        document.getElementById('user_agent').value = navigator.userAgent;
        document.getElementById('load_time').value = performance.now();

        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.container');
            document.querySelector('form').addEventListener('submit', () => {
               if(container) { container.classList.remove('page-visible'); container.classList.add('page-exit'); }
            });
        });
    </script>
<?php include 'footer.php'; ?>