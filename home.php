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
    <link rel="stylesheet" href="assets/style.css?v=51">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</head>
<body>


    <div class="container page-visible">

        <div class="profile-header">
            <div style="font-size: 1.5rem; margin-bottom: 5px;">🏮</div>
            <h2 style="font-family: 'Cinzel', serif; font-size: 0.8rem; color: var(--accent-gold); margin-bottom: 10px; line-height: 1.4;">
                LANTERN RITE 2025
            </h2>
            <hr style="border: 0; border-top: 1px solid rgba(255,215,0,0.3); width: 60%; margin: 10px auto;">
            <p style="font-size: 0.7rem; letter-spacing: 2px; opacity: 0.7;">
                <?php echo ($role == 'observer') ? 'VISITOR' : 'TRAVELER'; ?>
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

            <a href="sos.php" class="app-btn"><i class="fa-solid fa-circle-question"></i><span>FAQ</span></a>

            <!-- Add your social media link here -->
            <!-- <a href="https://www.instagram.com/your_handle/" target="_blank" class="app-btn"><i class="fa-brands fa-instagram"></i><span>Adventurer's Guild</span></a> -->

            <?php if ($role == 'observer'): ?>
                <a href="upgrade_access.php" class="app-btn" style="grid-column: span 2; border-color: var(--accent-green); color: var(--accent-green); background: rgba(76, 175, 80, 0.1);">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i><span>Become a Traveler</span>
                </a>
            <?php endif; ?>

            <?php if ($role == 'operative'): ?>
                <?php if ($bp_owned): ?>
                    <a href="id_card.php" class="app-btn" style="grid-column: span 2; border-color: #FFD700; color: #FFD700; background: rgba(212, 175, 55, 0.15);">
                        <i class="fa-solid fa-crown"></i><span>🌟 GENESIS CRYSTAL PASS</span>
                    </a>
                <?php else: ?>
                    <a href="id_card.php?action=upgrade" class="app-btn" style="grid-column: span 2; border-color: var(--lantern-orange); background: rgba(255, 140, 66, 0.15); color: var(--lantern-orange);">
                        <i class="fa-solid fa-gem"></i> <span>GET PREMIUM PASS</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>


        </div>

        <br><br>
        <p style="font-size: 0.7rem; opacity: 0.5; margin-bottom: 5px;">✦ Liyue Harbor ✦</p>
        <a href="tos.php" style="font-size: 0.6rem; color: #444; text-decoration: none;">[ TERMS OF SERVICE ]</a>
    </div>

    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

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