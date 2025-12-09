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
                <input type="hidden" name="user_agent" id="user_agent">
                <input type="hidden" name="load_time" id="load_time">

                <h3 style="color: var(--velvet-gold); font-size: 1rem; margin-bottom: 10px;">PARTICIPATING GUEST</h3>
                
                <input type="text" 
                       name="codename" 
                       placeholder="Enter Alias (e.g. Joker)" 
                       maxlength="20" 
                       autocomplete="off"
                       pattern="[a-zA-Z0-9]+" 
                       title="Only letters and numbers are allowed."
                       oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g, '')">
                       
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
        
        <p style="font-size: 0.8rem; margin-top: 15px;">
            <a href="recover.php" style="color: var(--velvet-gold); text-decoration: none;">
                <i class="fa-solid fa-key"></i> Lost my ID? Recover Account
            </a>
        </p>

        <p style="font-size: 0.65rem; color: #666; margin-top: 25px; line-height: 1.4;">
            By entering the Velvet Room, you agree to our<br>
            <a href="tos.php" style="color: #888; text-decoration: underline;">Terms of Service</a>.
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