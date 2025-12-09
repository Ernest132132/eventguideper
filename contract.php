<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// 1. RELOAD DATA
$stmt = $pdo->prepare("SELECT status, codename FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// --- NEW PROLOGUE INTERCEPT LOGIC ---
if ($user['status'] === 'pending') {
    // If returning from prologue (skipped or finished), set the session flag
    if (isset($_GET['done'])) {
        $_SESSION['prologue_seen'] = true;
        // Redirect to self to clean URL
        header("Location: contract.php");
        exit();
    }

    // If they haven't seen it yet, send them there
    if (!isset($_SESSION['prologue_seen'])) {
        header("Location: prologue.php");
        exit();
    }
}
// ------------------------------------

$transition_active = false;
$error = "";

// 2. HANDLE SIGNING (POST REQUEST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['passcode']);
    
    // Validate Code
    if (strtoupper($code) === "REALITY") {
        // STATE CHANGE: PENDING -> ACTIVE
        $pdo->prepare("UPDATE operatives SET status = 'active' WHERE id = ?")->execute([$_SESSION['user_id']]);
        $_SESSION['status'] = 'active';
        
        // TRIGGER TRANSITION (Allows us to bypass the redirect for this one load)
        $transition_active = true;
    } else {
        $error = "The contract rejects this code. Consult Akechi and Kasumi.";
    }
}

// 3. HANDLE REJECTION
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE operatives SET role = 'observer', status = 'active' WHERE id = ?")->execute([$_SESSION['user_id']]);
    $_SESSION['role'] = 'observer';
    header("Location: home.php");
    exit();
}

// 4. THE GATEKEEPER (Strict Logic Fix)
// We redirect to Home ONLY if:
// A) We are NOT currently showing the 'Just Signed' animation ($transition_active is false)
// B) AND the user is already marked 'active' in the database/session.
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
    <title>Sign Contract | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=39"> 
    <style>
        /* --- CONTRACT STYLES --- */
        .contract-rules {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--velvet-gold);
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
            position: relative;
        }
        .contract-rules::before {
            content: "STIPULATIONS";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--velvet-medium-blue);
            padding: 0 10px;
            color: var(--velvet-gold);
            font-family: 'Cinzel', serif;
            font-size: 0.8rem;
            letter-spacing: 2px;
        }
        .rule-row {
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            font-size: 0.9rem;
            color: #ddd;
            line-height: 1.4;
        }
        .rule-num {
            color: var(--velvet-gold); /* Changed to Gold */
            font-weight: bold;
            margin-right: 10px;
            font-family: 'Cinzel', serif;
            min-width: 25px;
        }
        .signature-line {
            border-bottom: 2px solid var(--velvet-gold);
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-family: 'Cinzel', serif;
            font-size: 1.6rem;
            color: white;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
        }

        /* --- MYSTICAL TRANSITION STYLES --- */
        .transition-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, #1a2140 0%, #000 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            animation: fadeInDeep 0.5s forwards; /* Quick fade to blue */
        }
        
        /* The main "Seal" Text */
        .seal-text {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: var(--velvet-gold-light);
            letter-spacing: 6px;
            text-transform: uppercase;
            
            /* Initial State */
            opacity: 0;
            filter: blur(10px);
            transform: scale(0.9);
            
            /* Animation: 2 seconds long */
            animation: manifestText 2s ease-out forwards 0.3s;
            text-shadow: 0 0 20px var(--velvet-gold), 0 0 40px var(--velvet-blue);
        }

        /* The Subtitle "Welcome" */
        .sub-text {
            font-family: 'Lato', sans-serif;
            color: #ccc;
            font-size: 0.9rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 15px;
            opacity: 0;
            /* Animation: 2.5s long, starts at 1s */
            animation: subTextCycle 2.5s ease-in-out forwards 1s;
        }

        @keyframes fadeInDeep {
            to { opacity: 1; }
        }

        /* Seal Text: Blur In -> Hold -> Blur Out */
        @keyframes manifestText {
            0% { opacity: 0; filter: blur(15px); transform: scale(0.95); }
            40% { opacity: 1; filter: blur(0px); transform: scale(1); }
            80% { opacity: 1; filter: blur(0px); transform: scale(1); }
            100% { opacity: 0; filter: blur(5px); transform: scale(1.05); } 
        }

        /* Sub Text: Float Up -> Hold -> Fade Out */
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

            <div class="seal-text">The contract is sealed</div>
            <div class="sub-text">Welcome to the Velvet Room</div>
        </div>
        
        <script>
            // 5000ms = 5 seconds total.
            setTimeout(function() {
                window.location.href = 'orientation.php';
            }, 4500);
        </script>

    <?php else: ?>
        <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
        <div class="container page-visible">
            <br>
            <h1 style="color: var(--velvet-gold); text-shadow: 0 0 10px var(--velvet-gold);">THE CONTRACT</h1>
            
            <div class="card" style="border-color: var(--velvet-gold); box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);">
                
                <div class="contract-rules">
                    <div class="rule-row">
                        <span class="rule-num">I.</span>
                        <span>Do not touch the shadows (performers) unless they've invited you to.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">II.</span>
                        <span>This experience is shared; let other guests also have their time talking with performers.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">III.</span>
                        <span>The shadows aren't able to break character, so play along with the illusion.</span>
                    </div>
                    <div class="rule-row">
                        <span class="rule-num">IV.</span>
                        <span>If anything feels wrong and/or you need help, find staff at the Info Booth immediately.</span>
                    </div>
                </div>

                <p style="font-family: 'Cinzel', serif; font-size: 1rem; line-height: 1.6; color: var(--velvet-gold-light); margin-bottom: 25px; font-style: italic;">
                    "I am thou, thou art I... Thou hast acquired a new vow.<br><br>
                    The events of this day are shaped by thine own cognition, with thine actions being yours, and yours alone.<br><br>
                    By sealing the contract, thou wilt experience a plight that wilt bring thee closer to the truth."
                </p>
                
                <?php if($error): ?>
                    <div class="alert" style="border-color: red; color: red;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <label style="color: var(--velvet-silver); font-size: 0.7rem; letter-spacing: 2px; text-align: center;">CONTRACTOR IDENTITY</label>
                        <div class="signature-line">
                            <?php echo htmlspecialchars($codename); ?>
                        </div>
                    </div>

                    <label style="color: var(--velvet-gold);">VELVET ROOM KEYWORD</label>
                    <input type="text" name="passcode" placeholder="Enter code..." required autocomplete="off" style="border-color: var(--velvet-gold); color: var(--velvet-gold);">
    
                    <button type="submit" class="btn-gold" style="background: var(--velvet-gold); color: #000; margin-top: 15px; width: 100%; border: 1px solid white;">
                        SEAL THE CONTRACT
                     </button>
                </form>
            </div>

            <div style="text-align: center; margin-top: 40px; margin-bottom: 20px; opacity: 0.8;">
                <div class="qr-frame" style="border: 2px solid var(--velvet-gold); padding: 5px; background: white; display: inline-block;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?php echo $qrData; ?>" alt="ID QR">
                </div>
                <p style="font-size: 0.75rem; color: var(--velvet-gold); margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;">
                    Show to Staff for RSVP Check-In / Experience Upgrades
                </p>
            </div>

            <div style="margin-top: 10px; text-align: center;">
                <a href="?reject=1" style="color: #fff; font-size: 0.8rem; text-decoration: none; border-bottom: 1px dashed #666;">
                    Reject Contract
                </a>
            </div>
        </div>
        <?php include 'footer.php'; ?>
    <?php endif; ?>
</body>
</html>