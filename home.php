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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Traveler Hub | Lantern Rite 2025</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=52">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>

    <div class="container page-visible">

        <div class="profile-header">
            <div style="font-size: 2rem; margin-bottom: 4px;">🏮</div>
            <p style="font-size: 0.7rem; letter-spacing: 2px; margin: 0 0 4px 0;">
                <?php echo ($role == 'observer') ? 'VISITOR' : 'TRAVELER'; ?>
            </p>
            <h1><?php echo htmlspecialchars($codename); ?></h1>
            <p style="font-size: 0.75rem; opacity: 0.9; margin: 4px 0 0 0;">Lantern Rite 2025</p>
        </div>

        <?php if ($rescueCode && $role !== 'observer'): ?>
            <div class="rescue-box" id="rescue-card">
                <h2>📋 Save Your Recovery Code</h2>
                <p>Keep this code safe! You'll need it to recover your account or log in on another device.</p>
                <div class="rescue-code"><?php echo $rescueCode; ?></div>
                <button id="btn-save-code" class="save-img-btn">
                    <i class="fa-solid fa-camera"></i> Save as Image
                </button>
            </div>
        <?php endif; ?>
        
        <div class="app-grid">
            
            <?php if ($role == 'operative'): ?>
                <a href="id_card.php" class="app-btn">
                    <i class="fa-solid fa-id-card"></i><span>Festival Pass</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <?php 
                $stmtBookCheck = $pdo->prepare("SELECT id FROM bookings WHERE operative_id = ? AND status != 'cancelled' LIMIT 1");
                $stmtBookCheck->execute([$_SESSION['user_id']]);
                $isBooked = $stmtBookCheck->fetch();
                ?>

                <?php if ($isBooked): ?>
                    <a href="booking.php" class="app-btn" style="background: #E8F5E9; border: 2px solid #4CAF50;">
                        <i class="fa-solid fa-calendar-check" style="color: #4CAF50;"></i><span>My Reservation</span>
                    </a>
                <?php else: ?>
                    <a href="booking.php" class="app-btn">
                        <i class="fa-solid fa-calendar-days"></i><span>Reservations</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <a href="confidants.php" class="app-btn"><i class="fa-solid fa-masks-theater"></i><span>Performers</span></a>

            <a href="intel.php" class="app-btn"><i class="fa-solid fa-map-location-dot"></i><span>Vendors</span></a>

            <a href="sos.php" class="app-btn"><i class="fa-solid fa-circle-question"></i><span>FAQ</span></a>

            <!-- Add your social media link here -->
            <!-- <a href="https://www.instagram.com/your_handle/" target="_blank" class="app-btn"><i class="fa-brands fa-instagram"></i><span>Adventurer's Guild</span></a> -->

            <?php if ($role == 'observer'): ?>
                <a href="upgrade_access.php" class="app-btn" style="grid-column: span 2; background: #E8F5E9; border: 2px solid #4CAF50;">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="color: #4CAF50;"></i><span style="color: #2E7D32;">Become a Traveler</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <?php if ($bp_owned): ?>
                    <a href="id_card.php" class="app-btn" style="grid-column: span 2; background: linear-gradient(135deg, #FFF8E1, #FFECB3); border: 2px solid #D4A017;">
                        <i class="fa-solid fa-crown" style="color: #D4A017;"></i><span style="color: #8B6914;">🌟 GENESIS CRYSTAL PASS</span>
                    </a>
                <?php else: ?>
                    <a href="id_card.php?action=upgrade" class="app-btn" style="grid-column: span 2; background: linear-gradient(135deg, #FFF3E0, #FFE0B2); border: 2px solid #E67E22;">
                        <i class="fa-solid fa-gem" style="color: #E67E22;"></i><span style="color: #D35400;">GET PREMIUM PASS</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>


        </div>

        <div style="text-align: center; margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--paper-tan);">
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">🏮 Liyue Harbor</p>
            <a href="tos.php" style="font-size: 0.75rem; color: var(--text-muted);">Terms of Service</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

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
                        link.download = 'LanternRite-Recovery-Code.png';
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