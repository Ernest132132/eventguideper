<?php
session_start();
require 'db.php';

// Prevent Back Button Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// 1. RELOAD USER FROM DB
$stmt = $pdo->prepare("SELECT role, status, bp_owned, codename FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) { session_destroy(); header("Location: index.php"); exit(); }

// GATEKEEPER
if ($user['status'] === 'pending') {
    header("Location: contract.php");
    exit();
}

$_SESSION['role'] = $user['role'];
$role = $user['role'];
$codename = $user['codename'];
$bp_owned = $user['bp_owned']; 

$rescueCode = "";
if (isset($_SESSION['new_user_rescue'])) {
    $rescueCode = $_SESSION['new_user_rescue'];
    unset($_SESSION['new_user_rescue']); 
}

// 2. CHECK FOR TRUE ENDING (Shadow Guess = Yukiko Amagi)
$hasTrueEnding = false;
try {
    $stmtGuess = $pdo->prepare("SELECT guessed_char FROM shadow_guesses WHERE operative_id = ?");
    $stmtGuess->execute([$_SESSION['user_id']]);
    $guess = $stmtGuess->fetchColumn();
    if ($guess === 'Yukiko Amagi') {
        $hasTrueEnding = true;
    }
} catch (Exception $e) {
    // Fail safe
}

// 3. CHECK FOR NOTIFICATION TRIGGER
$showNotification = isset($_GET['letter_received']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=51">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        /* --- SLIDE-IN NOTIFICATION --- */
        .letter-notification {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-200%);
            background: #000; border: 2px solid var(--velvet-gold);
            width: 90%; max-width: 400px;
            padding: 20px; z-index: 10001;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            border-radius: 8px;
            text-align: center;
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.19, 1, 0.22, 1);
        }
        .letter-notification.active {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        .notif-icon { color: #ff99cc; font-size: 2rem; margin-bottom: 10px; }
        .notif-title { color: white; font-family: 'Cinzel', serif; font-size: 1.2rem; margin-bottom: 5px; }
        .notif-btn { 
            background: #ff99cc; color: #000; border: none; 
            padding: 10px 25px; font-weight: bold; margin-top: 15px; 
            cursor: pointer; text-transform: uppercase; letter-spacing: 1px;
            box-shadow: 0 0 10px #ff99cc;
            text-decoration: none; display: inline-block;
        }
    </style>
</head>
<body>

    <div id="letter-notify" class="letter-notification">
        <div class="notif-icon"><i class="fa-solid fa-envelope"></i></div>
        <div class="notif-title">YOU RECEIVED A LETTER</div>
        <p style="color: #aaa; font-size: 0.9rem; margin: 0;">A message from someone familiar...</p>
        <a href="letter.php" class="notif-btn">READ</a>
    </div>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h2 style="font-family: 'Cinzel', serif; font-size: 0.8rem; color: var(--velvet-gold); margin-bottom: 10px; line-height: 1.4;">
                PORTAL TO THE VELVET ROOM:<br>ALL-OUT HOLIDAY
            </h2>
            <hr style="border: 0; border-top: 1px solid rgba(255,215,0,0.3); width: 60%; margin: 10px auto;">
            <p style="font-size: 0.7rem; letter-spacing: 2px; opacity: 0.7;">
                <?php echo ($role == 'observer') ? 'ATTENTIVE OBSERVER' : 'CONTRACTED GUEST'; ?>
            </p>
            <h1><?php echo htmlspecialchars($codename); ?></h1>
        </div>

        <?php if ($rescueCode && $role !== 'observer'): ?>
            <div class="rescue-box" id="rescue-card" style="padding: 20px; background: #1a1a1a; border: 1px solid var(--velvet-red); border-radius: 5px; text-align: center; margin-bottom: 20px;">
                <h2 style="color: var(--velvet-red); margin-top: 0;">⚠ ID RECOVERY CODE ⚠</h2>
                <p style="color: #ccc; font-size: 0.8rem;">Don't lose this code! It's the only way to recover your ID and/or log into a different device.</p>
                <div style="background: #000; padding: 10px; border: 1px dashed #444; margin: 10px 0;">
                    <span class="rescue-code" style="font-size: 1.2rem; font-weight: bold; letter-spacing: 3px; color: #fff;"><?php echo $rescueCode; ?></span>
                </div>
                
                <button id="btn-save-code" class="save-img-btn">
                    <i class="fa-solid fa-camera"></i> Save as Image
                </button>
            </div>
        <?php endif; ?>
        
        <div class="app-grid">
            
            <?php if ($role == 'operative'): ?>
                <a href="id_card.php" class="app-btn">
                    <i class="fa-solid fa-id-card"></i><span>My ID</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <?php 
                $stmtBookCheck = $pdo->prepare("SELECT id FROM bookings WHERE operative_id = ? AND status != 'cancelled' LIMIT 1");
                $stmtBookCheck->execute([$_SESSION['user_id']]);
                $isBooked = $stmtBookCheck->fetch();
                ?>

                <?php if ($isBooked): ?>
                    <a href="booking.php" class="app-btn" style="border-color: #00d26a; color: #00d26a; background: rgba(0, 210, 106, 0.1);">
                        <i class="fa-solid fa-calendar-check"></i><span>My Reservation</span>
                    </a>
                <?php else: ?>
                    <a href="booking.php" class="app-btn">
                        <i class="fa-solid fa-calendar-days"></i><span>Reservations</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <a href="confidants.php" class="app-btn"><i class="fa-solid fa-masks-theater"></i><span>Performers</span></a>

            <a href="intel.php" class="app-btn"><i class="fa-solid fa-map-location-dot"></i><span>Vendors</span></a>
            
            <?php if ($role == 'operative'): ?>
                <a href="arcade.php" class="app-btn"><i class="fa-solid fa-gamepad"></i><span>Arcade</span></a>
            <?php endif; ?>

            <a href="cognitions.php" class="app-btn"><i class="fa-solid fa-book-atlas"></i><span>About Persona</span></a>
            
            <a href="sos.php" class="app-btn"><i class="fa-solid fa-circle-question"></i><span>FAQ</span></a>

            <a href="https://www.instagram.com/iris.interactive/" target="_blank" class="app-btn"><i class="fa-brands fa-instagram"></i><span>Our Group</span></a>

            <?php if ($role == 'operative'): ?>
                <a href="feltcraft.php" class="app-btn" style="border-color: #0066cc; color: #0066cc; background: rgba(0, 102, 204, 0.1);">
                    <i class="fa-solid fa-scissors"></i><span>Feltcraft</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <a href="toolkit_login.php" class="app-btn" style="border-color: #e60012; color: #e60012; background: rgba(230, 0, 18, 0.1);">
                    <i class="fa-solid fa-toolbox"></i><span>Toolkit</span>
                </a>
            <?php endif; ?>
            
            <?php if ($role == 'operative'): ?>
                <a href="shadow_guess.php" class="app-btn" style="grid-column: span 2; border-color: #9900ff; color: #9900ff; background: rgba(153, 0, 255, 0.1);">
                    <i class="fa-solid fa-eye"></i><span>Submit The False Shadow</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'observer'): ?>
                <a href="upgrade_access.php" class="app-btn" style="grid-column: span 2; border-color: var(--velvet-green); color: var(--velvet-green); background: rgba(0, 210, 106, 0.1);">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Join Experience</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <?php if ($bp_owned): ?>
                    <a href="id_card.php" class="app-btn" style="grid-column: span 2; border-color: #FFD700; color: #FFD700; background: rgba(212, 175, 55, 0.15);">
                        <i class="fa-solid fa-crown"></i><span>BATTLE PASS (OWNED)</span>
                    </a>
                <?php else: ?>
                    <a href="id_card.php?action=upgrade" class="app-btn" style="grid-column: span 2; border-color: #E1306C; background: rgba(225, 48, 108, 0.15); color: #E1306C;">
                        <i class="fa-solid fa-cart-plus"></i> <span>GET UPGRADE</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($hasTrueEnding): ?>
                <a href="letter.php" class="app-btn" style="grid-column: span 2; border-color: #ff99cc; background: rgba(255, 153, 204, 0.15); color: #ff99cc; cursor: pointer; text-decoration: none;">
                    <i class="fa-solid fa-envelope-open-text"></i><span>Letter from Kukiyo</span>
                </a>
            <?php endif; ?>

        </div>

        <br><br>
        <p style="font-size: 0.7rem; opacity: 0.5; margin-bottom: 5px;">VELVET ROOM SECURE CONNECTION</p>
        <a href="tos.php" style="font-size: 0.6rem; color: #444; text-decoration: none;">[ TERMS OF SERVICE ]</a>
    </div>

    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // --- NOTIFICATION TRIGGER ---
            <?php if ($showNotification): ?>
            setTimeout(() => {
                const notif = document.getElementById('letter-notify');
                if (notif) notif.classList.add('active');
            }, 1000); // 1 second delay
            <?php endif; ?>

            // --- SAVE RECOVERY CODE AS IMAGE ---
            const saveBtn = document.getElementById('btn-save-code');
            const cardToSave = document.getElementById('rescue-card');

            if (saveBtn && cardToSave) {
                saveBtn.addEventListener('click', function() {
                    // Visual feedback
                    const originalText = saveBtn.innerHTML;
                    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> SAVING...';

                    // Use html2canvas
                    html2canvas(cardToSave, {
                        backgroundColor: "#1a1a1a", // Force dark background for the image
                        scale: 2, // Better quality
                        ignoreElements: (element) => {
                            // Don't include the button itself in the picture
                            return element.id === 'btn-save-code';
                        }
                    }).then(canvas => {
                        // Trigger download
                        const link = document.createElement('a');
                        link.download = 'All-Out-Recovery-Code.png';
                        link.href = canvas.toDataURL("image/png");
                        link.click();

                        // Reset button
                        setTimeout(() => {
                            saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> SAVED!';
                        }, 500);
                        setTimeout(() => {
                            saveBtn.innerHTML = originalText;
                        }, 2500);
                    }).catch(err => {
                        console.error("Screenshot failed", err);
                        saveBtn.innerText = "ERROR SAVING";
                    });
                });
            }


            // --- PAGE TRANSITIONS ---
            const links = document.querySelectorAll('a');
            const container = document.querySelector('.container');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.hostname === window.location.hostname && this.getAttribute('target') !== '_blank') {
                        e.preventDefault();
                        const href = this.getAttribute('href');
                        if(container) {
                            container.classList.remove('page-visible');
                            container.classList.add('page-exit');
                        }
                        setTimeout(() => { window.location.href = href; }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>