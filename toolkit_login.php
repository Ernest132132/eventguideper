<?php
session_start();

// --- CONFIGURATION ---
// Map specific passwords to their destination URLs
$ACCESS_MAP = [
    "askyousee" => "https://personapvr.irisinteractive.org/ptt/s1/index.html",
    "closeeyes" => "https://personapvr.irisinteractive.org/ptt/s2/index.html",
    "eyeswide"  => "https://personapvr.irisinteractive.org/ptt/s3/index.html",
    "gateopen"  => "https://personapvr.irisinteractive.org/ptt/s4/index.html"
];

// 1. Check if already unlocked in this session
// If they are already logged in, send them to their specific stored destination
if (isset($_SESSION['toolkit_unlocked']) && $_SESSION['toolkit_unlocked'] === true && isset($_SESSION['toolkit_target'])) {
    header("Location: " . $_SESSION['toolkit_target']);
    exit();
}

$error = "";

// 2. Handle Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = trim($_POST['keyword']);
    $inputUpper = strtoupper($input);
    
    $matchFound = false;

    // Loop through the map to check if the input matches any password
    foreach ($ACCESS_MAP as $password => $url) {
        if (strtoupper($password) === $inputUpper) {
            $_SESSION['toolkit_unlocked'] = true; 
            $_SESSION['toolkit_target'] = $url; // Save the specific URL they unlocked
            header("Location: " . $url);
            exit();
        }
    }

    // If loop finishes without exiting, the password was wrong
    $error = "COGNITION REJECTED. ACCESS DENIED.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Restricted Area | Velvet Room</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=23">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .lock-icon {
            font-size: 3rem;
            color: var(--velvet-red);
            margin-bottom: 20px;
            animation: subtleGoldPulse 2s infinite;
        }
        .shake {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            50% { transform: translateX(10px); }
            75% { transform: translateX(-10px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="fabric-container">
        <div class="fabric-wave"></div>
        <div class="fabric-wave"></div>
    </div>
    <div class="fog-container">
        <div class="fog-layer"></div>
        <div class="fog-layer"></div>
    </div>
    <div class="container page-visible">
        <br><br>
        
        <i class="fa-solid fa-lock lock-icon"></i>
        
        <h1 style="color: var(--velvet-red); text-shadow: 0 0 10px red;">COGNITIVE BARRIER DETECTED</h1>
        <p style="font-size: 0.9rem; opacity: 0.8;">
            This knowledge is forbidden to the uninitiated.<br>
            Prove your intent to the Phantom Thieves.
        </p>

        <div class="card" style="border-color: var(--velvet-red); box-shadow: 0 0 20px rgba(230, 0, 18, 0.2);">
            
            <?php if($error): ?>
                <div class="alert shake" style="background: rgba(230,0,18,0.2); border-color: red; color: #ffcccc;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label style="color: var(--velvet-red);">ENTER THE KEYWORD:</label>
                <input type="text" name="keyword" placeholder="e.g. TREASURE" required autocomplete="off" 
                       style="border-color: var(--velvet-red); color: var(--velvet-red); font-size: 1.5rem; letter-spacing: 3px;">
                
                <br><br>
                <button type="submit" class="btn-gold" style="background: var(--velvet-red); color: white; width: 100%;">
                    BREAK THE SEAL
                </button>
            </form>
        </div>

        <br>
        <a href="home.php" style="color: #666; font-size: 0.8rem;">&larr; Retreat to Safety</a>

    </div>
    <div class="fabric-container">
        <div class="fabric-wave"></div>
        <div class="fabric-wave"></div>
    </div>
    <div class="fog-container">
        <div class="fog-layer"></div>
        <div class="fog-layer"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a');
            const container = document.querySelector('.container');

            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Check if it's an internal link AND not opening in a new tab
                    if (this.hostname === window.location.hostname && this.getAttribute('target') !== '_blank') {
                        e.preventDefault(); // Stop immediate load
                        const href = this.getAttribute('href');
                        
                        // Add the Exit Animation Class
                        if(container) {
                            container.classList.remove('page-visible');
                            container.classList.add('page-exit');
                        }
                        
                        // Wait 480ms for animation to finish, then go
                        setTimeout(() => {
                            window.location.href = href;
                        }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>