<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// 1. RELOAD DATA
$stmt = $pdo->prepare("SELECT status, codename FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$transition_active = false;
$error = "";

// 2. HANDLE SIGNING (POST REQUEST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['passcode']);

    // Validate Code - Lantern Rite keyword
    if (strtoupper($code) === "MINGXIAO") {
        // STATE CHANGE: PENDING -> ACTIVE
        $pdo->prepare("UPDATE operatives SET status = 'active' WHERE id = ?")->execute([$_SESSION['user_id']]);
        $_SESSION['status'] = 'active';

        // TRIGGER TRANSITION
        $transition_active = true;
    } else {
        $error = "Invalid code. Please check with the Millelith or event staff.";
    }
}

// 3. HANDLE REJECTION
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE operatives SET role = 'observer', status = 'active' WHERE id = ?")->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = 'observer';
    header("Location: home.php");
    exit();
}

// 4. THE GATEKEEPER
if (!$transition_active && $user['status'] !== 'pending') {
    header("Location: home.php");
    exit();
}

$codename = $user['codename'];
$qrData = "OP-" . $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Festival Agreement | Lantern Rite 2025</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=52">
    <style>
        /* --- FESTIVAL CONTRACT STYLES --- */
        .contract-rules {
            background: var(--paper-warm);
            border: 2px dashed var(--paper-tan);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            text-align: left;
        }
        .rules-title {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--liyue-red);
            margin-bottom: 16px;
        }
        .rule-row {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            font-size: 0.9rem;
            color: var(--text-medium);
            line-height: 1.5;
        }
        .rule-num {
            color: var(--liyue-red);
            font-weight: 700;
            margin-right: 12px;
            min-width: 24px;
        }
        .signature-box {
            background: var(--paper-warm);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .signature-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            border-bottom: 2px solid var(--liyue-gold);
            padding-bottom: 4px;
            display: inline-block;
        }

        /* --- TRANSITION OVERLAY --- */
        .transition-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, var(--liyue-red) 0%, var(--liyue-red-dark) 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }

        .seal-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: white;
            letter-spacing: 2px;
            text-align: center;
            opacity: 0;
            animation: fadeInUp 1.5s ease-out forwards 0.3s;
            padding: 0 20px;
        }

        .sub-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 12px;
            opacity: 0;
            animation: fadeInUp 1.5s ease-out forwards 0.8s;
        }

        .lantern-float {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: gentleBob 2s ease-in-out infinite;
        }

        @keyframes gentleBob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <?php if ($transition_active): ?>
        <div class="transition-overlay">
            <div class="lantern-float">🏮</div>
            <div class="seal-text">May Your Wishes Come True</div>
            <div class="sub-text">Welcome to Lantern Rite</div>
        </div>

        <script>
            setTimeout(function() {
                window.location.href = 'home.php';
            }, 3500);
        </script>

    <?php else: ?>
        <div class="container page-visible">
            <div style="text-align: center; padding: 20px 0;">
                <div style="font-size: 3rem; margin-bottom: 8px;">🏮</div>
                <h1>Festival Agreement</h1>
                <p>Please review our guidelines before joining.</p>
            </div>

            <div class="card">
                <div class="contract-rules">
                    <div class="rules-title">Festival Guidelines</div>
                    <div class="rule-row">
                        <span class="rule-num">1.</span>
                        <span>Treat all performers, vendors, and fellow Travelers with respect.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">2.</span>
                        <span>The spirit of Lantern Rite is one of unity; please be mindful of other guests.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">3.</span>
                        <span>Follow all posted event rules and Millelith (staff) instructions.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">4.</span>
                        <span>For assistance, seek the Adventurer's Guild booth or any staff member.</span>
                    </div>
                </div>

                <p style="text-align: center; font-style: italic; color: var(--text-light); margin-bottom: 24px;">
                    "May the flames of wisdom spread to all, and never be extinguished."
                </p>

                <?php if($error): ?>
                    <div class="alert"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="signature-box">
                        <label style="margin: 0 0 8px 0; display: block;">Traveler Name</label>
                        <div class="signature-name"><?php echo htmlspecialchars($codename); ?></div>
                    </div>

                    <label>Festival Keyword</label>
                    <input type="text" name="passcode" placeholder="Enter the keyword..." required autocomplete="off">

                    <button type="submit" class="btn-gold" style="margin-top: 16px;">
                        🏮 Light My Lantern
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 32px;">
                <div class="qr-frame">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $qrData; ?>" alt="Festival Pass QR" style="width: 200px; height: 200px;">
                </div>
                <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 12px;">
                    Show to staff for check-in or upgrades
                </p>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <a href="?reject=1" style="font-size: 0.85rem; color: var(--text-muted);">
                    Skip for now →
                </a>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    <?php endif; ?>
</body>
</html>
