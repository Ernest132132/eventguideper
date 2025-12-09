<?php
session_start();
require 'db.php';

// 1. SECURITY & SESSION CHECK
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

// 2. LOAD DATA (Single User Only)
$stmt = $pdo->prepare("SELECT id, codename, role, status, bp_owned, bp_redeemed, rescue_code FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$operative = $stmt->fetch();

if (!$operative) { header("Location: index.php"); exit(); }

$qrData = "OP-" . $operative['id'];

// 3. VISUAL LOGIC
$pulseClass = ""; 
$statusColor = "var(--velvet-gold)";
$statusText = "CONTRACT PENDING";
$specialCardClass = ""; 
$instruction = "Show this to staff for upgrades/activity check-in.";

// Battle Pass Visual Override
if ($operative['bp_owned'] == 1) {
    $statusColor = "#FFD700"; // GOLD
    $statusText = "VIP / BATTLE PASS";
    $specialCardClass = "card-battle-pass"; // Trigger the fancy CSS
    $instruction = "Scan at Info Booth to claim upgraded rewards.";
    
    if ($operative['bp_redeemed'] == 1) {
        $statusText = "REWARDS CLAIMED";
        $statusColor = "#aaa";
        $instruction = "Battle Pass fully redeemed.";
    }
} else {
    // Standard ID visual logic
    if ($operative['status'] == 'active') {
        $pulseClass = "pulse-active"; // THE BREATHING ANIMATION
        $statusText = "ACTIVE";
        $instruction = "Collect stamps at stations.";
    } elseif ($operative['status'] == 'eligible') {
        $statusColor = "var(--velvet-green)"; 
        $statusText = "REWARD UNLOCKED"; 
        $instruction = "Go to Info Booth to redeem.";
    } elseif ($operative['status'] == 'redeemed') {
        $statusColor = "var(--velvet-red)"; 
        $statusText = "REDEEMED"; 
        $instruction = "Reward claimed.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Contract</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=36">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Restored Visual Styles */
        .card-battle-pass {
            background: linear-gradient(135deg, #4b0000 0%, #2a0a0a 100%);
            border: 3px solid #FFD700 !important;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.4), inset 0 0 20px rgba(0,0,0,0.8) !important;
            animation: bpPulse 4s infinite ease-in-out;
            position: relative;
            overflow: hidden;
        }
        .card-battle-pass::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 60%);
            animation: shimmerRotate 10s linear infinite;
            pointer-events: none;
        }
        .card-battle-pass h1 {
            color: #FFD700 !important;
            text-shadow: 0 0 10px #FFD700, 2px 2px 0 #000;
        }
        .card-battle-pass .status-badge {
            background: linear-gradient(90deg, #FFD700, #E1C12E) !important;
            color: #000 !important;
            box-shadow: 0 0 15px #FFD700;
            border: 1px solid #fff;
        }
        
        /* Pulse for Active Standard Cards */
        .pulse-active {
            animation: subtleGoldPulse 2s infinite ease-in-out;
        }

        /* Animations */
        @keyframes bpPulse {
            0% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.3); border-color: #FFD700; }
            50% { box-shadow: 0 0 40px rgba(255, 215, 0, 0.6); border-color: #FFF; }
            100% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.3); border-color: #FFD700; }
        }
        @keyframes shimmerRotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes subtleGoldPulse {
            0% { box-shadow: 0 0 15px rgba(212, 175, 55, 0.2); }
            50% { box-shadow: 0 0 25px rgba(212, 175, 55, 0.5); }
            100% { box-shadow: 0 0 15px rgba(212, 175, 55, 0.2); }
        }
    </style>
</head>
<body>

    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        
        <div class="profile-header" style="border-bottom: none; margin-bottom: 10px;">
            <h1 style="font-size: 1.8rem; margin-bottom: 5px;">MY CONTRACT</h1>
            <p style="font-size: 0.8rem; letter-spacing: 2px; color: var(--velvet-gold); margin: 0;">IDENTIFICATION</p>
        </div>

        <div class="card <?php echo $pulseClass . ' ' . $specialCardClass; ?>" style="text-align: center; border-color: <?php echo $statusColor; ?>; margin-bottom: 25px;">
            
            <h1 style="color: <?php echo $statusColor; ?>; margin: 0 0 15px 0; font-size: 1.8rem; text-shadow: 0 0 10px <?php echo $statusColor; ?>;">
                <?php echo htmlspecialchars($operative['codename']); ?>
            </h1>

            <div class="qr-frame" style="border: 4px solid <?php echo $statusColor; ?>; padding: 5px; background: white; display: inline-block; margin-bottom: 20px;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $qrData; ?>" alt="ID QR" style="width: 100%; max-width: 200px; height: auto; display: block;">
            </div>
            
            <br>

            <div class="status-badge" style="background: <?php echo $statusColor; ?>; color: <?php echo ($operative['bp_redeemed'] == 1) ? '#ccc' : 'black'; ?>; border: none; font-weight: bold; display: inline-block;">
                <?php echo $statusText; ?>
            </div>
            
            <p style="font-size: 0.8rem; margin-top: 15px; color: #aaa; text-shadow: 0 0 5px black;">
                <?php echo $instruction; ?>
            </p>

        </div>

        <?php if (!empty($operative['rescue_code']) && $operative['role'] !== 'observer'): ?>
            <div style="margin-top: 25px; margin-bottom: 5px; text-align: center; opacity: 0.8;">
                <div style="display: inline-block; border: 1px dashed #666; background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 4px;">
                    <i class="fa-solid fa-key" style="color: var(--velvet-gold); font-size: 0.8rem;"></i>
                    <span style="color: #aaa; font-size: 0.7rem; letter-spacing: 1px; margin: 0 5px;">RECOVERY CODE:</span>
                    <span style="font-family: monospace; color: #fff; font-size: 0.9rem; letter-spacing: 2px; text-shadow: 0 0 5px rgba(255,255,255,0.3);">
                        <?php echo htmlspecialchars($operative['rescue_code']); ?>
                    </span>
                </div>
                <p style="font-size: 0.6rem; color: #fff; margin-top: 5px; font-style: italic;">
                    (Save this code in case you need to recover your account and log into a different device.)
                </p>
            </div>
        <?php endif; ?>

        <br>
        
        <a href="home.php" class="btn-gold" style="display: inline-block; width: auto; min-width: 200px;">&larr; Return to Dashboard</a>
    </div>

</body>
</html>