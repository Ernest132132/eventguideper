<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// Check status
$stmt = $pdo->prepare("SELECT bp_owned, bp_redeemed FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['bp_owned'] == 0) {
    header("Location: home.php");
    exit();
}

$qrData = "BP-" . $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Battle Pass | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=26">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <br>
        <h1 style="color: #E1306C; text-shadow: 0 0 10px #E1306C;">BATTLE PASS</h1>
        <p style="font-size: 0.9rem; letter-spacing: 2px;">PREMIUM ACCESS GRANTED</p>

        <div class="card" style="border-color: #E1306C; box-shadow: 0 0 20px rgba(225, 48, 108, 0.3);">
            
            <div class="qr-frame" style="border-color: #E1306C;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $qrData; ?>" alt="BP QR">
            </div>

            <br><br>

            <?php if ($user['bp_redeemed'] == 1): ?>
                <div class="status-badge" style="background: #333; color: #888; border-color: #555;">REWARDS CLAIMED</div>
                <p style="font-size: 0.8rem; margin-top: 10px; color: #888;">Thank you for your support.</p>
            <?php else: ?>
                <div class="status-badge" style="background: #E1306C; color: white; border-color: #E1306C; box-shadow: 0 0 15px #E1306C;">READY TO REDEEM</div>
                <p style="font-size: 0.9rem; margin-top: 10px; color: white;">Show this code at the Check-Out Booth.</p>
            <?php endif; ?>

        </div>

        <br><br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return
        </a>
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