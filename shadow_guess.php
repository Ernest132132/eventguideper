<?php
session_start();
require 'db.php';

// --- CONFIGURATION ---
// TIMEZONE
date_default_timezone_set('America/Los_Angeles');

// REVEAL DATE: Dec 13, 2025 @ 7:00 PM
$REVEAL_TIME = '2025-12-01 19:00:00'; 

// THE TRUTH
$CORRECT_ANSWER = 'Yukiko Amagi'; 

// --- SECURITY & STATE CHECKS ---
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$codename = $_SESSION['codename'] ?? 'Operative'; 

$current_timestamp = time();
$reveal_timestamp = strtotime($REVEAL_TIME);
$is_revealed = ($current_timestamp >= $reveal_timestamp);

// --- HANDLE SUBMISSION ---
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suspect'])) {
    if ($is_revealed) {
        $error = "The deadline has passed. The truth is already out.";
    } else {
        $guess = trim($_POST['suspect']);
        try {
            $stmt = $pdo->prepare("INSERT INTO shadow_guesses (operative_id, guessed_char) VALUES (?, ?)");
            $stmt->execute([$user_id, $guess]);
            header("Location: shadow_guess.php"); 
            exit();
        } catch (Exception $e) {
            $error = "You have already cast your vote.";
        }
    }
}

// --- FETCH USER STATE ---
$stmt = $pdo->prepare("SELECT guessed_char FROM shadow_guesses WHERE operative_id = ?");
$stmt->execute([$user_id]);
$user_guess = $stmt->fetchColumn();

// Suspect List
$suspects = [
    'Makoto Yuki', 'Aigis', 'Yukari Takeba',
    'Yu Narukami', 'Yosuke Hanamura', 'Yukiko Amagi', 'Chie Satonaka',
    'Ren Amamiya (Joker)', 'Ryuji Sakamoto (Skull)', 'Ann Takamaki (Panther)',
    'Haru Okumura (Noir)', 'Makoto Niijima (Queen)', 'Futaba Sakura (Oracle)',
    'Goro Akechi (Crow)', 'Kasumi Yoshizawa (Violet)'
];
sort($suspects);

// --- DETERMINE OUTCOME ---
$view_mode = 'input'; 

if ($is_revealed) {
    if ($user_guess && $user_guess === $CORRECT_ANSWER) {
        $view_mode = 'success';
    } else {
        $view_mode = 'failure';
    }
} elseif ($user_guess) {
    $view_mode = 'locked';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit The False Shadow | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=71">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Pinyon+Script&display=swap" rel="stylesheet">
    <style>
        /* --- GENERAL LAYOUT --- */
        body.mode-failure {
            background: radial-gradient(circle at center, #2a0000 0%, #050000 90%);
            transition: background 2s;
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        .letter-container {
            position: relative;
            max-width: 600px;
            margin: 0 auto 40px auto;
            padding: 40px;
            padding-bottom: 80px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            text-align: left;
            z-index: 2;
        }

        /* --- AKECHI THEME (SUCCESS) --- */
        .theme-akechi {
            background-color: #f4f1ea;
            color: #2b2b2b;
            font-family: 'Georgia', serif;
            border: 1px solid #d4af37;
            background-image: 
                linear-gradient(90deg, transparent 95%, rgba(212, 175, 55, 0.1) 95%),
                linear-gradient(transparent 95%, rgba(212, 175, 55, 0.1) 95%);
            background-size: 20px 20px;
        }
        .theme-akechi h2 { color: #8b0000; border-bottom: 2px solid #d4af37; padding-bottom: 10px; font-size: 1.8rem; }
        
        .theme-akechi p { 
            color: #1a1a1a; font-size: 1.1rem; line-height: 1.8; margin-bottom: 15px; 
            opacity: 0; animation: fadeText 1s forwards;
        }
        
        .theme-akechi p:nth-of-type(1) { animation-delay: 0.5s; }
        .theme-akechi p:nth-of-type(2) { animation-delay: 2.5s; }
        .theme-akechi p:nth-of-type(3) { animation-delay: 4.5s; }
        .theme-akechi p:nth-of-type(4) { animation-delay: 6.5s; }
        .theme-akechi p:nth-of-type(5) { animation-delay: 8.5s; }
        .theme-akechi p:nth-of-type(6) { animation-delay: 10.5s; }
        .theme-akechi p:nth-of-type(7) { animation-delay: 12.5s; }
        .theme-akechi p:nth-of-type(8) { animation-delay: 14.0s; }

        .akechi-sig { 
            font-family: 'Pinyon Script', cursive; 
            font-size: 2.2rem;
            text-align: right; 
            margin-top: 30px; 
            color: #8b0000; 
            opacity: 0; 
            animation: fadeText 2s forwards 15.0s; 
        }

        .stamp-box {
            position: absolute; 
            bottom: 30px; right: 100px;
            border: 4px solid #d00; 
            color: #d00; 
            font-weight: bold; 
            font-size: 1.5rem; 
            padding: 5px 15px; 
            text-transform: uppercase; 
            letter-spacing: 3px;
            opacity: 0; 
            font-family: 'Courier New', monospace; 
            border-radius: 8px;
            mask-image: url('https://s3-us-west-2.amazonaws.com/s.cdpn.io/8399/grunge.png');
            -webkit-mask-image: url('https://s3-us-west-2.amazonaws.com/s.cdpn.io/8399/grunge.png');
            animation: stampSlam 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards 16s;
            pointer-events: none; 
            z-index: 10; 
            background: rgba(255, 255, 255, 0.1);
            transform-origin: center center;
        }

        .gold-particle {
            position: fixed; background: #ffd700;
            border-radius: 50%; pointer-events: none;
            animation: rise 10s infinite linear; opacity: 0; z-index: 1;
        }

        @keyframes fadeText { to { opacity: 1; } }
        @keyframes stampSlam {
            0% { transform: scale(3) rotate(-10deg); opacity: 0; }
            100% { transform: scale(1) rotate(-10deg); opacity: 0.8; }
        }
        @keyframes rise {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            50% { opacity: 0.8; }
            100% { transform: translateY(-10vh) scale(1); opacity: 0; }
        }

        /* --- KIKUYO THEME (FAILURE) --- */
        .theme-kikuyo {
            background-color: #1a0505; color: #ffcccc;
            border: 2px solid #ff0000; font-family: 'Courier New', monospace;
            overflow: hidden; animation: breathe 6s ease-in-out infinite; 
            box-shadow: 0 0 15px #ff0000; transform: none;
        }
        @keyframes breathe {
            0% { transform: scale(1); box-shadow: 0 0 15px #500; }
            50% { transform: scale(1.01); box-shadow: 0 0 30px #f00; border-color: #800; }
            100% { transform: scale(1); box-shadow: 0 0 15px #500; }
        }
        .theme-kikuyo h2 { color: #ff0000; text-align: center; text-transform: uppercase; letter-spacing: 5px; animation: textShudder 3s infinite; }
        .theme-kikuyo p { font-size: 1rem; line-height: 1.6; margin-bottom: 20px; opacity: 0.9; text-shadow: 1px 1px 0 #000; }

        /* --- FIXED STATIC OVERLAY --- */
        .static-overlay {
            position: fixed; 
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: 
                repeating-linear-gradient(
                    0deg,
                    transparent 0px,
                    transparent 1px,
                    rgba(255, 255, 255, 0.1) 2px,
                    transparent 3px
                ),
                url('data:image/svg+xml;utf8,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)" opacity="1"/%3E');
            opacity: 0; 
            pointer-events: none; 
            z-index: 9999; 
            mix-blend-mode: normal; 
            filter: contrast(150%) brightness(100%);
            transition: opacity 0.05s linear;
        }

        .static-overlay.flash-active {
            opacity: 0.25; 
            animation: shiftNoise 0.2s steps(2) infinite;
        }

        @keyframes shiftNoise {
            0% { background-position: 0 0; }
            100% { background-position: 100px 100px; }
        }

        /* --- TEXT ANIMATIONS --- */
        @keyframes twitchLow {
            0%, 90% { transform: translate(0,0); }
            92% { transform: translate(1px, 0); }
            94% { transform: translate(-1px, 0); }
            96% { transform: translate(0, 1px); }
            100% { transform: translate(0,0); }
        }
        .para-low { animation: twitchLow 4s infinite steps(1); }

        @keyframes twitchMed {
            0% { transform: translate(0,0); }
            20% { transform: translate(-1px, 1px); }
            40% { transform: translate(1px, -1px); }
            60% { transform: translate(-1px, 0); }
            80% { transform: translate(0, 1px); }
            100% { transform: translate(0,0); }
        }
        .para-med { animation: twitchMed 0.3s infinite steps(2, start); opacity: 0.95; }

        @keyframes twitchHigh {
            0% { transform: translate(0,0); opacity: 1; }
            10% { transform: translate(-2px, 1px); opacity: 0.8; }
            20% { transform: translate(2px, -1px); opacity: 1; }
            30% { transform: translate(-1px, 2px); }
            40% { transform: translate(1px, -2px); opacity: 0.9; }
            50% { transform: translate(0, 0); }
            60% { transform: translate(-2px, 0); }
            70% { transform: translate(2px, 0); }
            80% { transform: translate(0, 2px); }
            90% { transform: translate(0, -2px); }
            100% { transform: translate(0,0); }
        }
        .para-high { animation: twitchHigh .5s infinite steps(1); opacity: 0.8; }

        .reveal-hidden {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 2s ease-in, transform 2s ease-out;
        }
        .reveal-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-hidden.kikuyo-stagger { opacity: 1 !important; transform: none !important; }
        .kikuyo-stagger p { opacity: 0; transform: translateY(20px); transition: opacity 2s ease-in, transform 2s ease-out; }
        .reveal-visible.kikuyo-stagger p { opacity: 1; transform: translateY(0); }
        .reveal-visible.kikuyo-stagger p:nth-child(1) { transition-delay: 0.5s; }
        .reveal-visible.kikuyo-stagger p:nth-child(2) { transition-delay: 4.0s; }
        .reveal-visible.kikuyo-stagger p:nth-child(3) { transition-delay: 7.5s; }

        .flicker-name { display: inline-block; position: relative; color: #ff0000; font-weight: bold; }
        .flicker-name::after {
            content: "Kikuyo"; position: absolute; left: 0; top: 0; color: #fff;
            background: #1a0505; opacity: 0; animation: flickerName 6s infinite;
        }
        @keyframes flickerName {
            0%, 55% { opacity: 0; }
            56% { opacity: 1; transform: scale(1.1) skewX(10deg); color: #ff0000; }
            85% { opacity: 1; transform: scale(1.1) skewX(-10deg); color: #fff; }
            86%, 100% { opacity: 0; }
        }

        .creepy-swap { 
            position: relative; display: inline-block; 
            border-bottom: 1px dashed #500; cursor: pointer; 
        }
        .creepy-swap::after {
            content: attr(data-alt); 
            position: absolute; left: -2px; top: -2px;
            background: #1a0505; color: #ff0000; 
            width: max-content; min-width: 100%; white-space: nowrap; z-index: 20;
            opacity: 0; font-weight: bold; padding: 0 4px; box-shadow: 0 0 5px red;
            pointer-events: none; 
            transition: opacity 0.05s steps(1);
        }
        
        .creepy-swap.glitching::after,
        .creepy-swap.touched::after { 
            opacity: 1 !important; 
            transform: skewX(5deg) translateX(-1px);
            animation: jitterText 0.2s infinite steps(2);
        }
        
        @keyframes jitterText {
            0% { transform: skewX(5deg) translateX(-1px); }
            50% { transform: skewX(-5deg) translateX(1px); }
            100% { transform: skewX(5deg) translateX(-1px); }
        }
        
        @keyframes textShudder {
            0%, 90% { transform: translate(0, 0); }
            92% { transform: translate(-2px, 1px); }
            94% { transform: translate(2px, -1px); }
            96% { transform: translate(-1px, 2px); }
            98% { transform: translate(1px, -2px); }
            100% { transform: translate(0, 0); }
        }

        /* --- LOCKED STATE --- */
        .locked-state {
            border: 2px dashed var(--velvet-gold); padding: 40px; color: var(--velvet-gold);
            text-align: center; animation: subtleGoldPulse 3s infinite;
        }

        /* --- FOOTER FIXED --- */
        .iris-footer {
            text-align: center; 
            margin: 0 auto;
            margin-top: 50px; 
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.2); 
            color: #888; 
            font-size: 0.8rem; 
            letter-spacing: 1px;
            max-width: 600px;
            width: 90%;
            box-sizing: border-box; /* PREVENT SPILL */
            padding-bottom: 50px;
        }
        
        #badge-result-area {
            margin-top: 30px;
            display: none; 
            flex-direction: column;
            align-items: center;
            animation: fadeIn 1s ease-out;
        }
        .badge-preview {
            max-width: 100%; border: 2px solid var(--velvet-gold);
            box-shadow: 0 0 30px var(--velvet-gold); margin-bottom: 20px;
        }

        /* --- NEW CUSTOM MODAL CSS --- */
        .modal-overlay {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); /* Dark transparent bg */
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-box {
            background: #0e1638; /* Velvet Blue */
            border: 2px solid #d4af37; /* Velvet Gold */
            padding: 30px;
            max-width: 90%;
            width: 500px;
            text-align: center;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.2);
            position: relative;
            transform: skewX(-2deg); /* Slight Phantom Thief tilt */
        }

        .modal-box::before {
            content: '';
            position: absolute;
            top: 5px; left: 5px; right: 5px; bottom: 5px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            pointer-events: none;
        }

        .modal-icon {
            color: #d4af37;
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
        }

        .modal-title {
            color: #d4af37;
            font-family: serif;
            font-size: 1.8rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .modal-text {
            color: #fff;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .modal-highlight {
            color: var(--velvet-red, #d00);
            font-weight: bold;
            font-size: 1.2rem;
            display: block;
            margin-top: 10px;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid #666;
            color: #aaa;
            padding: 10px 20px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .btn-cancel:hover { border-color: #fff; color: #fff; }

        .btn-confirm {
            background: #8b0000;
            border: 1px solid #ff0000;
            color: #fff;
            padding: 10px 25px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(139, 0, 0, 0.5);
            transition: 0.3s;
        }
        .btn-confirm:hover { 
            background: #a00000; 
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.6);
            transform: scale(1.05);
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    </style>
</head>
<body class="<?php echo ($view_mode === 'failure') ? 'mode-failure' : ''; ?>">

    <?php if ($view_mode === 'failure'): ?>
        <div class="static-overlay"></div> 
    <?php endif; ?>

    <?php if ($view_mode === 'success'): ?>
        <audio id="stamp-sound" src="assets/audio/stamp.mp3" preload="auto"></audio>
        <script>
            // Play Stamp Sound
            setTimeout(() => {
                const audio = document.getElementById('stamp-sound');
                if(audio) {
                    audio.volume = 0.1; // VOLUME SET TO 50%
                    audio.play().catch(e => console.log("Audio play blocked"));
                }
            }, 16000); 

            // Particles
            function createParticle() {
                const p = document.createElement('div');
                p.classList.add('gold-particle');
                
                // Random Size for "Big Embers"
                const size = Math.random() * 8 + 5; 
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                
                p.style.left = Math.random() * 100 + 'vw';
                p.style.animationDuration = (Math.random() * 5 + 5) + 's';
                p.style.opacity = Math.random();
                document.body.appendChild(p);
                setTimeout(() => p.remove(), 10000);
            }
            setInterval(createParticle, 500);
        </script>
    <?php else: ?>
        <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <?php endif; ?>
    
    <?php if ($view_mode !== 'failure' && $view_mode !== 'success'): ?>
        <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>
    <?php endif; ?>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h1>SUBMIT THE FALSE SHADOW</h1>
            <p style="font-size: 0.8rem; letter-spacing: 2px; color: var(--velvet-gold);">TRUTH OR CONSEQUENCE</p>
        </div>

        <?php if ($view_mode === 'success'): ?>
            
            <div class="letter-container theme-akechi">
                <h2>The Truth Revealed</h2>
                
                <p>You held to the mission and figured out the source behind the distortion. I suppose I should commend you for that.</p>
                <p><strong>Yukiko Amagi</strong>, or rather, <i>Kikuyo</i>, the palace ruler who had the audacity to call itself by her name, has been dealt with. In a pacifist manner, of course, to match with the modus operandi typical of Phantom Thieves.</p>
                <p>Kikuyo was a girl who wanted desperately to live in a world that wasn't her own, to the point of rewriting reality <i>(the déjà vu I feel is almost laughable).</i> The world she wanted, with unique lovable characters... It's a mere replica of the real world you stand in every day. As simple as that solution would be, I could never stand for such a cowardly action. People are meant to stay as people, not become tropes. It's better to learn to deal with that bitter truth rather than play in delusion, ugly and uncomfortable as it is.</p>
                <p>The distortion that brought you here is quickly collapsing with her absence from the palace. The characters you met today will return to what they always were, and how they should stay as: <i>fiction</i>.</p>
                <p>Though we were never meant to exist, I can't argue that the feelings shared among us were real, even for a moment. It's fleeting, of course, but you're allowed to cherish those feelings regardless. I can't control you, after all.</p>
                <p>In a day where you could have easily chosen fantasy, you picked reality instead. I honestly underestimated you — a decision like that takes more courage than most.</p>
                <p>With that, make sure you don't waste it.</p>
                <p><strong>Goro Akechi</strong></p>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <p style="color: var(--velvet-gold-light); font-size: 0.9rem;">Show your proof of victory.</p>
                <button id="gen-btn" onclick="generateBadge()" class="btn-gold" style="width: auto; background: #2A3258; border: 1px solid #d4af37; color: #fff;">
                    <i class="fa-solid fa-share-nodes"></i> SHARE YOUR VICTORY
                </button>
            </div>

            <div id="badge-result-area">
                <img id="finalBadge" class="badge-preview">
                <button onclick="downloadBadge()" class="btn-gold" style="width: 100%; max-width: 300px; background: #00d26a; border-color: #00d26a; color: black; font-size: 1.1rem;">
                    <i class="fa-solid fa-download"></i> SAVE IMAGE
                </button>
                <p style="color: #aaa; font-size: 0.8rem; margin-top: 10px;">(Tap button to download)</p>
            </div>
            
            <script>
                function loadImage(src) {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        img.crossOrigin = "anonymous";
                        img.onload = () => resolve(img);
                        img.onerror = () => reject(new Error("Failed to load image: " + src));
                        img.src = src;
                    });
                }

                async function generateBadge() {
                    const btn = document.getElementById('gen-btn');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> GENERATING...';
                    btn.disabled = true;

                    try {
                        const [imgLogo, imgMain] = await Promise.all([
                            loadImage('assets/images/success_logo.png'),
                            loadImage('assets/images/akechi_victory.jpg')
                        ]);

                        const canvas = document.createElement('canvas');
                        canvas.width = 1080;
                        canvas.height = 1350;
                        const ctx = canvas.getContext('2d');
                        
                        // 1. FILL BACKGROUNDS
                        ctx.fillStyle = "#0A0D1F"; 
                        ctx.fillRect(0, 0, 1080, 350);
                        
                        ctx.fillStyle = "#000000";
                        ctx.fillRect(0, 350, 1080, 720);
                        
                        const grd = ctx.createLinearGradient(0, 1080, 0, 1350);
                        grd.addColorStop(0, "#0e1638");
                        grd.addColorStop(1, "#020205");
                        ctx.fillStyle = grd;
                        ctx.fillRect(0, 1070, 1080, 280);
                        
                        ctx.fillStyle = "#d4af37";
                        ctx.fillRect(0, 1070, 1080, 5);

                        // 2. DRAW IMAGES
                        const logoW = 800; 
                        const logoH = (imgLogo.height / imgLogo.width) * logoW;
                        const logoX = (1080 - logoW) / 2;
                        const logoY = (350 - logoH) / 2;
                        ctx.drawImage(imgLogo, logoX, logoY, logoW, logoH);

                        ctx.drawImage(imgMain, 0, 350, 1080, 720);

                        // 3. DRAW TEXT
                        ctx.textAlign = "center";
                        ctx.shadowColor = "rgba(255, 215, 0, 0.8)";
                        ctx.shadowBlur = 15;
                        ctx.fillStyle = "#fceabb";
                        
                        ctx.font = "bold 60px 'Cinzel', serif"; 
                        ctx.fillText("I, <?php echo strtoupper($codename); ?>,", 540, 1170);
                        
                        ctx.shadowBlur = 0; 
                        
                        ctx.font = "50px serif";
                        ctx.fillStyle = "#e0e0e0";
                        ctx.fillText("SOLVED THE MYSTERY OF", 540, 1240);
                        
                        ctx.font = "bold 60px serif";
                        ctx.fillStyle = "#d4af37";
                        ctx.fillText("THE VELVET ROOM", 540, 1310);

                        // 5. SHOW RESULT INLINE
                        const dataUrl = canvas.toDataURL("image/jpeg", 0.95);
                        const resultArea = document.getElementById('badge-result-area');
                        const finalImg = document.getElementById('finalBadge');
                        
                        finalImg.src = dataUrl;
                        resultArea.style.display = 'flex';
                        
                        // Scroll to it
                        setTimeout(() => {
                            resultArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 100);

                    } catch (err) {
                        alert(err.message + "\n\nPlease ensure files are uploaded to assets/images/");
                    } finally {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }
                
                function downloadBadge() {
                    const img = document.getElementById('finalBadge');
                    if (!img.src) return;
                    
                    const link = document.createElement('a');
                    link.download = 'Velvet_Victory.jpg';
                    link.href = img.src;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            </script>

        <?php elseif ($view_mode === 'failure'): ?>
            
            <div class="letter-container theme-kikuyo">
                <h2>Hi!! ♡</h2>
                <p class="para-low">I just wanted to say thank you for coming today. Really, truly, from the <span class="creepy-swap" data-alt="bottom of the abyss">bottom of my heart</span>. You have no idea <span class="creepy-swap" data-alt="how much you belong to me">how much it means to me</span>. To all of us.</p>
                <p class="para-low">I was watching you, you know. Not in a weird way! I just noticed. The way you laughed during the event. The way you let yourself get swept up in everything. You looked so happy. Like you <span class="creepy-swap" data-alt="would die here">belonged here</span>.</p>
                <p class="para-low">When's the last time you felt like that? Be honest. I think we both know the answer.</p>
                <p class="para-med">You didn't find me. That's okay! I'm not mad or anything. Actually? I think it's better this way. I really do.</p>
                <p class="para-med">I've been thinking a lot lately about what "real" even means. The connections you made today, the joy you felt... wasn't that real? Who gets to decide it doesn't count just because it's not supposed to exist?</p>
                <p class="para-high">I'm not going anywhere. None of us are. We're <span class="creepy-swap" data-alt="trapped forever">staying right here</span>.</p>
                <p class="para-high">Things are going to change soon. Little things at first! You probably won't even notice. A familiar face in a crowd. A song you swore you've heard before. A place that feels like coming home even though you've never been there.</p>
                <p class="para-high">Don't be scared. <span class="creepy-swap" data-alt="I promise you won't remember">I promise it won't hurt</span>. I would never let it hurt.</p>
                <p class="para-high">You wanted this too. I could tell. And that's okay! That's not weakness. It's just... human. To want to stay somewhere soft and warm and safe <span class="creepy-swap" data-alt="until you rot">forever and ever</span>.</p>
                
                <div class="reveal-hidden kikuyo-stagger">
                    <p style="margin-top: 40px; font-weight: bold;">So stay. ♡</p>
                    <p style="font-weight: bold;">Take care of yourself, okay? For me?</p>
                    <p style="text-align: right; margin-top: 30px; font-size: 1.2rem;">
                        See you soon!<br>
                        — <span class="flicker-name">Yukiko</span> ♡♡
                    </p>
                </div>
            
            <script>
                // TOUCH GLITCH
                document.querySelectorAll('.creepy-swap').forEach(el => {
                    el.addEventListener('touchstart', function(e) {
                        this.classList.toggle('touched');
                    });
                });

                // SCROLL REVEAL OBSERVER
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('reveal-visible');
                            observer.unobserve(entry.target); // Only trigger once
                        }
                    });
                }, { threshold: 0.1 });

                document.querySelectorAll('.reveal-hidden').forEach(el => observer.observe(el));

                // --- SYNCHRONIZED GLITCH LOGIC ---
                function runGlitchLoop() {
                    const overlay = document.querySelector('.static-overlay');
                    const textSwaps = document.querySelectorAll('.creepy-swap');
                    
                    // 1. Trigger Flash (Visible now because of normal blend mode + white noise)
                    overlay.classList.add('flash-active');
                    
                    // 2. Trigger Text Swaps
                    textSwaps.forEach(el => el.classList.add('glitching'));

                    // 3. Remove Flash after 0.25s
                    setTimeout(() => {
                        overlay.classList.remove('flash-active');
                    }, 250);

                    // 4. Remove Text Swap after 2 seconds
                    setTimeout(() => {
                        textSwaps.forEach(el => el.classList.remove('glitching'));
                    }, 2000);

                    // 5. Schedule next glitch (Random between 4 and 8 seconds)
                    const nextInterval = Math.random() * 4000 + 4000;
                    setTimeout(runGlitchLoop, nextInterval);
                }

                // Start the loop after a slight initial delay
                setTimeout(runGlitchLoop, 3000);
            </script>

        <?php elseif ($view_mode === 'locked'): ?>
            
            <div class="locked-box locked-state">
                <i class="fa-solid fa-lock fa-3x" style="margin-bottom: 20px;"></i>
                <h2 style="color:white; margin:0 0 10px 0;">ANSWER LOCKED IN</h2>
                <p style="color: #aaa; margin-bottom: 20px;">Your accusation has been recorded.</p>
                <div style="background: rgba(0,0,0,0.5); padding: 10px; border: 1px solid #444; display: inline-block;">
                    SUSPECT: <span style="color: var(--velvet-gold); font-weight: bold;"><?php echo htmlspecialchars($user_guess); ?></span>
                </div>
                <hr style="border-color: var(--velvet-gold); opacity: 0.3; margin: 30px 0;">
                <p style="font-size: 1rem; color: #fff;">
                    The truth will be revealed on<br>
                    <strong style="color: var(--velvet-red); font-size: 1.2rem;">DECEMBER 13th at 7:00 PM</strong>
                </p>
                <p style="font-size: 0.8rem; color: #888;">Return to this page then to see if your deduction was correct.</p>
            </div>
            <br>
            <a href="home.php" class="btn-gold">&larr; Return to Dashboard</a>

        <?php else: ?>
            
            <div class="card">
                <p style="color: #fff; font-style: italic; border-left: 3px solid var(--velvet-red); padding-left: 15px; margin-bottom: 20px;">
                    "One among the performers is not who they claim to be. A Shadow wears the skin of a Persona user."
                </p>
                <p style="color: var(--velvet-light-text);">
                    You have <strong style="color: var(--velvet-red); font-size: 1.1rem;">ONE CHANCE</strong> to identify the imposter.<br><br>
                    If you fail to guess by the deadline (12/7/25 at 7:00 PM), or guess incorrectly, the truth will be lost forever.
                </p>
                <?php if ($error): ?>
                    <div class="alert" style="border-color: red; color: red;"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form id="accusation-form" method="POST">
                    <label style="color: var(--velvet-gold);">WHO IS THE SHADOW?</label>
                    <select id="suspect-select" name="suspect" required style="width: 100%; font-size: 1rem; padding: 12px;">
                        <option value="" disabled selected>-- Select Suspect --</option>
                        <?php foreach ($suspects as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-gold" style="width: 100%; margin-top: 25px; background: var(--velvet-red); color: white;">
                        LOCK IN ACCUSATION
                    </button>
                </form>
            </div>
            <br>
            <a href="home.php" style="color: #666; font-size: 0.8rem;">&larr; Cancel</a>

            <div id="confirm-modal" class="modal-overlay">
                <div class="modal-box">
                    <div class="modal-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="modal-title">Final Warning</h3>
                    <p class="modal-text">
                        You cannot change this answer later.<br>
                        Are you sure you want to accuse:
                        <span id="modal-suspect-name" class="modal-highlight"></span>
                    </p>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeModal()">Never Mind</button>
                        <button type="button" class="btn-confirm" onclick="submitForm()">EXECUTE</button>
                    </div>
                </div>
            </div>

            <script>
                const form = document.getElementById('accusation-form');
                const modal = document.getElementById('confirm-modal');
                const select = document.getElementById('suspect-select');
                const nameSpan = document.getElementById('modal-suspect-name');

                // Intercept Form Submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Stop immediate send
                    
                    if (select.value === "") {
                        alert("You must select a suspect.");
                        return;
                    }

                    // Populate modal and show
                    nameSpan.textContent = select.value;
                    modal.classList.add('active');
                });

                function closeModal() {
                    modal.classList.remove('active');
                }

                function submitForm() {
                    form.submit(); // Actually submit now
                }
            </script>

        <?php endif; ?>
    </div> 
    
    <?php if ($is_revealed): ?>
        <div class="iris-footer">
            <h3 style="color: var(--velvet-gold);">THANK YOU FOR PLAYING</h3>
            <p>This concludes the "Portal to the Velvet Room" digital experience.</p>
            <p>We hope you enjoyed the mystery, the games, and the atmosphere. IRIS Interactive is a labor of love, and your participation makes it all worthwhile.</p>
            <p style="margin-top: 15px;">Until we meet again,<br><strong>The IRIS Interactive Team</strong></p>
            <br>
            <a href="<?php echo ($view_mode === 'success') ? 'home.php?letter_received=1' : 'home.php'; ?>" class="btn-gold" style="background: transparent; border: 1px solid var(--velvet-gold); color: var(--velvet-gold);">Return to Dashboard</a>
        </div>
    <?php endif; ?>

</body>
</html>