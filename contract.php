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
    <link rel="stylesheet" href="assets/style.css?v=50">
    <style>
        /* --- FESTIVAL CONTRACT STYLES --- */
        .contract-rules {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--accent-gold);
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }
        .contract-rules::before {
            content: "✦ FESTIVAL GUIDELINES ✦";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-dark-blue);
            padding: 0 10px;
            color: var(--accent-gold);
            font-family: 'Cinzel', serif;
            font-size: 0.75rem;
            letter-spacing: 2px;
            white-space: nowrap;
        }
        .rule-row {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            font-size: 0.9rem;
            color: var(--accent-silver);
            line-height: 1.4;
        }
        .rule-num {
            color: var(--accent-gold);
            font-weight: bold;
            margin-right: 10px;
            font-family: 'Cinzel', serif;
            min-width: 25px;
        }
        .signature-line {
            border-bottom: 2px solid var(--accent-gold);
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            color: white;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }

        /* --- LANTERN TRANSITION STYLES --- */
        .transition-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, #2B1111 0%, #0D0505 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            animation: fadeInDeep 0.5s forwards;
        }

        .seal-text {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: var(--accent-gold-light);
            letter-spacing: 6px;
            text-transform: uppercase;
            opacity: 0;
            filter: blur(10px);
            transform: scale(0.9);
            animation: manifestText 2s ease-out forwards 0.3s;
            text-shadow: 0 0 20px var(--accent-gold), 0 0 40px var(--lantern-crimson);
        }

        .sub-text {
            font-family: 'Lato', sans-serif;
            color: var(--accent-silver);
            font-size: 0.9rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 15px;
            opacity: 0;
            animation: subTextCycle 2.5s ease-in-out forwards 1s;
        }

        .lantern-float {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: lanternBob 2s ease-in-out infinite;
        }

        @keyframes lanternBob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes fadeInDeep {
            to { opacity: 1; }
        }

        @keyframes manifestText {
            0% { opacity: 0; filter: blur(15px); transform: scale(0.95); }
            40% { opacity: 1; filter: blur(0px); transform: scale(1); }
            80% { opacity: 1; filter: blur(0px); transform: scale(1); }
            100% { opacity: 0; filter: blur(5px); transform: scale(1.05); }
        }

        @keyframes subTextCycle {
            0% { opacity: 0; transform: translateY(10px); }
            20% { opacity: 0.8; transform: translateY(0); }
            70% { opacity: 0.8; }
            100% { opacity: 0; }
        }
    </style>
</head>
<body>

    <?php if ($transition_active): ?>
        <div class="transition-overlay">
            <div class="fog-container" style="z-index: -1;">
                <div class="fog-layer"></div>
                <div class="fog-layer"></div>
            </div>

            <div class="lantern-float">🏮</div>
            <div class="seal-text">May Your Wishes Come True</div>
            <div class="sub-text">Welcome to Lantern Rite</div>
        </div>

        <script>
            setTimeout(function() {
                window.location.href = 'home.php';
            }, 4500);
        </script>

    <?php else: ?>
        <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
        <div class="container page-visible">
            <br>
            <div style="font-size: 2rem; margin-bottom: 10px;">🏮</div>
            <h1 style="color: var(--accent-gold); text-shadow: 0 0 10px var(--accent-gold);">FESTIVAL AGREEMENT</h1>

            <div class="card" style="border-color: var(--accent-gold); box-shadow: 0 0 20px rgba(212, 160, 23, 0.3);">

                <div class="contract-rules">
                    <div class="rule-row">
                        <span class="rule-num">I.</span>
                        <span>Treat all performers, vendors, and fellow Travelers with respect.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">II.</span>
                        <span>The spirit of Lantern Rite is one of unity; please be mindful of other guests.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">III.</span>
                        <span>Follow all posted event rules and Millelith (staff) instructions.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">IV.</span>
                        <span>For assistance, seek the Adventurer's Guild booth or any staff member.</span>
                    </div>
                </div>

                <p style="font-family: 'Cinzel', serif; font-size: 1rem; line-height: 1.6; color: var(--accent-gold-light); margin-bottom: 25px; font-style: italic;">
                    "May the flames of wisdom spread to all, and never be extinguished."
                </p>

                <?php if($error): ?>
                    <div class="alert" style="border-color: var(--accent-red); color: #ff6b6b;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <label style="color: #aaa; font-size: 0.7rem; letter-spacing: 2px; text-align: center;">TRAVELER NAME</label>
                        <div class="signature-line">
                            <?php echo htmlspecialchars($codename); ?>
                        </div>
                    </div>

                    <label style="color: var(--accent-gold);">FESTIVAL KEYWORD</label>
                    <input type="text" name="passcode" placeholder="Enter the keyword..." required autocomplete="off" style="border-color: var(--accent-gold); color: var(--accent-gold);">

                    <button type="submit" class="btn-gold" style="background: var(--accent-gold); color: #000; margin-top: 15px; width: 100%; border: 1px solid white;">
                        🏮 LIGHT MY LANTERN
                    </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 40px; margin-bottom: 20px; opacity: 0.8;">
                <div class="qr-frame" style="border: 2px solid var(--accent-gold); padding: 5px; background: white; display: inline-block;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?php echo $qrData; ?>" alt="Festival Pass QR">
                </div>
                <p style="font-size: 0.75rem; color: var(--accent-gold); margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;">
                    Show to Staff for Check-In / Upgrades
                </p>
            </div>

            <div style="margin-top: 10px; text-align: center;">
                <a href="?reject=1" style="color: #fff; font-size: 0.8rem; text-decoration: none; border-bottom: 1px dashed #666;">
                    Skip Festival Pass
                </a>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    <?php endif; ?>
</body>
</html>
