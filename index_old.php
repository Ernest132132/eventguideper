<?php
session_start();
require 'db.php';

$error = "";

// --- LOGIN CHECK ---
if (isset($_SESSION['user_id'])) {
    // Simply go to home. Home handles "Pending" vs "Active" logic now.
    header("Location: home.php");
    exit();
}

// --- FORM HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // PATH 1: OBSERVER (Instant Access - Active)
    if (isset($_POST['btn_observer'])) {
        $codename = "Visitor-" . rand(1000, 9999);
        $role = 'observer';
        $status = 'active'; 
        
        $sql = "INSERT INTO operatives (codename, role, status, auth_token, device_os) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $authToken = bin2hex(random_bytes(32));
            $stmt->execute([$codename, $role, $status, $authToken, $_POST['device_os'] ?? 'Unknown']);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['codename'] = $codename;
            $_SESSION['role'] = $role;
            setcookie("auth_token", $authToken, time() + (86400 * 30), "/");
            header("Location: home.php");
            exit();
        } catch (Exception $e) { $error = "Error: " . $e->getMessage(); }
    } 
    
    // PATH 2: PARTICIPANT (Create 'Pending' Account)
    else {
        $codename = trim(htmlspecialchars($_POST['codename']));
        if (empty($codename)) {
            $error = "Please sign your name.";
        } else {
            // Check availability
            $stmt = $pdo->prepare("SELECT id FROM operatives WHERE codename = ?");
            $stmt->execute([$codename]);
            if ($stmt->fetch()) {
                $error = "That Codename is already in use.";
            } else {
                // INSERT AS PENDING
                $role = 'operative';
                $status = 'pending';
                $authToken = bin2hex(random_bytes(32));
                $rescueCode = rand(1000, 9999);
                $os = $_POST['device_os'] ?? 'Unknown';

                $sql = "INSERT INTO operatives (codename, role, status, auth_token, rescue_code, device_os) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                try {
                    $stmt->execute([$codename, $role, $status, $authToken, $rescueCode, $os]);
                    $newId = $pdo->lastInsertId();

                    // Log in & Send to Home (Home will show the contract)
                    $_SESSION['user_id'] = $newId;
                    $_SESSION['codename'] = $codename;
                    $_SESSION['role'] = $role;
                    $_SESSION['new_user_rescue'] = $rescueCode;
                    setcookie("auth_token", $authToken, time() + (86400 * 30), "/");

                    header("Location: home.php");
                    exit();
                } catch (Exception $e) { $error = "System Error: " . $e->getMessage(); }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>The Registry | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=34"> 
    <style> .split-container { border-top: 1px solid var(--velvet-silver); margin-top: 20px; padding-top: 20px; } </style>
</head>
<body>
    
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        <br><br>
        <h1>Welcome to the<br>Velvet Room</h1>
        <p>State your intentions.</p>
        
        <div class="card">
            <h2>The Registry</h2>
            <?php if($error): ?>
                <div class="alert" style="border-color: red; color: red;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="device_os" id="device_os">

                <h3 style="color: var(--velvet-gold); font-size: 1rem; margin-bottom: 10px;">PARTICIPATING GUEST</h3>
                <input type="text" name="codename" placeholder="Create Codename (e.g. Joker)" maxlength="20" autocomplete="off">
                <button type="submit" name="btn_operative" class="btn-gold" style="width: 100%;">Create ID & Sign Contract</button>

                <div class="split-container">
                    <h3 style="color: #aaa; font-size: 1rem; margin-bottom: 10px;">JUST LOOKING</h3>
                    <p style="font-size: 0.8rem; margin-bottom: 15px;">Access maps and schedules only.</p>
                    <button type="submit" name="btn_observer" class="btn-gold" style="width: 100%; background: transparent; border: 1px solid var(--velvet-silver); color: var(--velvet-silver);">
                        Enter as Observer
                    </button>
                </div>
            </form>
        </div>
        <br>
        <p style="font-size: 0.8rem; margin-top: 10px;">
            <a href="recover.php" style="color: var(--velvet-gold);">Lost my ID / Clear Cache</a>
        </p>
    </div>

    <script>
        const userAgent = navigator.userAgent;
        let os = 'Other';
        if (userAgent.match(/Android/i)) os = 'Android';
        else if (userAgent.match(/iPhone|iPad|iPod/i)) os = 'iOS';
        else if (userAgent.match(/Windows/i)) os = 'Windows';
        else if (userAgent.match(/Macintosh|Mac OS X/i)) os = 'macOS';
        document.getElementById('device_os').value = os;

        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.container');
            document.querySelector('form').addEventListener('submit', () => {
               if(container) { container.classList.remove('page-visible'); container.classList.add('page-exit'); }
            });
        });
    </script>
</body>
</html>