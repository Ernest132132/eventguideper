<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tartarus Ascent | P3 Mini-Game</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="assets/style.css?v=78">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- P3 BLUE ACCENTS --- */
        :root {
            --p3-blue: #0066cc; /* Deep Persona 3 Blue */
            --p3-highlight: #3399ff; /* Bright Blue for Glow */
        }
        
        body {
            background-color: #000;
            background-image: url('assets/images/tartarus.webp');
            background-size: cover;
            background-position: center;
            overflow: hidden;
            touch-action: none;
            font-family: 'Cinzel', serif;
            user-select: none;
        }
        
        /* Blue Tint Overlay */
        .overlay-tint {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at center, rgba(0, 102, 204, 0.15) 0%, rgba(0,0,0,0.85) 90%);
            z-index: 0;
            pointer-events: none;
        }

        .game-container {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: 10;
        }

        .ui-layer {
            position: absolute;
            top: 20px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 20;
            pointer-events: none;
        }
        
        /* UI Elements */
        .score-box, .floor-indicator, .mute-btn {
            color: var(--p3-highlight); 
            font-size: 1.2rem;
            text-shadow: 0 0 10px var(--p3-blue);
            background: rgba(0,0,0,0.8);
            padding: 8px 15px;
            border-radius: 4px;
            border: 1px solid var(--p3-blue); 
            pointer-events: auto;
        }
        .floor-indicator { color: #ccc; border-color: #555; text-shadow: none; }
        
        .mute-btn {
            font-size: 1rem;
            cursor: pointer;
            margin-right: 10px;
            width: 40px;
            text-align: center;
            padding: 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* The Moving Target Container */
        #target-area {
            position: absolute;
            width: 300px;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            /* Remove CSS transition for position to prevent input lag desync */
        }

        /* Enemy Sprite */
        .shadow-sprite {
            width: 180px;
            height: 180px;
            object-fit: contain;
            filter: drop-shadow(0 0 10px rgba(0,0,0,0.8));
            position: relative;
            z-index: 2;
        }

        /* Fallback Placeholder (Ghost Image) */
        .shadow-fallback {
            width: 150px; 
            height: 150px; 
            background: #000; 
            border-radius: 50%; 
            border: 2px solid var(--p3-blue); 
            display: flex; 
            align-items: center; 
            justify-content: center;
            color: var(--p3-blue); 
            box-shadow: 0 0 20px #000;
        }

        .ghost-ring {
            position: absolute;
            width: 180px;
            height: 180px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            z-index: 1;
        }

        /* The Shrinking Reticle */
        .reticle {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            border: 4px solid var(--p3-blue); 
            border-radius: 50%;
            box-shadow: 0 0 15px var(--p3-blue), inset 0 0 10px var(--p3-blue);
            opacity: 0;
            z-index: 5;
        }

        .hit-effect {
            position: absolute;
            color: white;
            font-family: 'Cinzel', serif;
            font-weight: bold;
            font-size: 2.5rem;
            animation: floatUp 0.6s forwards;
            pointer-events: none;
            z-index: 30;
            text-shadow: 0 0 10px var(--p3-blue); 
        }
        @keyframes floatUp {
            0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
            20% { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
            100% { transform: translate(-40%, -150%) scale(1); opacity: 0; }
        }

        .overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        
        .p3-btn {
            background: #000;
            color: white;
            border: 2px solid var(--p3-blue); 
            font-family: 'Cinzel', serif;
            padding: 15px 40px;
            font-size: 1.2rem;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 0 15px var(--p3-blue); 
            transition: 0.2s;
            border-radius: 0 20px 0 20px;
            margin-top: 20px;
        }
        .p3-btn:active { transform: scale(0.95); background: var(--p3-blue); color: black; }
        .p3-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .leaderboard-box {
            width: 85%;
            max-width: 320px;
            background: rgba(0, 102, 204, 0.1);
            border: 1px solid var(--p3-blue);
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            text-align: left;
            max-height: 250px;
            overflow-y: auto;
        }
        .lb-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #333;
            padding: 8px 0;
            font-size: 0.9rem;
            color: #aaa;
        }
        .lb-row:nth-child(1) { color: #ffd700; font-weight: bold; } 
        .lb-row:nth-child(2) { color: #c0c0c0; } 
        .lb-row:nth-child(3) { color: #cd7f32; } 
    </style>
</head>
<body>

    <div class="overlay-tint"></div>
    <audio id="bgm" loop><source src="assets/audio/bgm_tartarus.mp3" type="audio/mpeg"></audio>

    <div class="ui-layer">
        <div style="display: flex;">
            <div class="mute-btn" onclick="toggleSound()">
                <i id="mute-icon" class="fa-solid fa-volume-high"></i>
            </div>
            <div class="score-box">SCORE: <span id="score">0</span></div>
        </div>
        <div class="floor-indicator">FLOOR: <span id="floor">1</span></div>
    </div>

    <div class="game-container" onmousedown="handleTap(event)" ontouchstart="handleTap(event)">
        <div id="target-area">
            <div class="ghost-ring"></div>
            <!-- Standard Image -->
            <img id="enemy-img" class="shadow-sprite" src="" alt="Shadow" onerror="this.style.display='none'; document.getElementById('enemy-fallback').style.display='flex';">
            
            <!-- Fallback Icon if image fails -->
            <div id="enemy-fallback" class="shadow-fallback" style="display: none;">
                <i class="fa-solid fa-ghost fa-3x"></i>
            </div>
            <div id="ring" class="reticle"></div>
        </div>
    </div>

    <div id="start-screen" class="overlay">
        <h1 style="color: var(--p3-blue); text-shadow: 0 0 20px var(--p3-blue); font-size: 2.5rem; margin-bottom: 5px;">TARTARUS</h1>
        <p style="color: #888; margin-bottom: 30px; letter-spacing: 3px;">MASS DESTRUCTION</p>
        <div style="text-align:center; color: #ccc; margin-bottom: 30px; font-size: 0.9rem; line-height: 1.6;">
            <p>Wait for the <b style="color:var(--p3-blue);">BLUE RING</b> to shrink.</p>
            <p>Tap <b style="color:white; border-bottom:1px solid white;">ANYWHERE</b> when it hits<br>the <span style="color:#aaa; border:1px dashed #aaa; padding:0 4px; border-radius:4px;">dashed circle</span>.</p>
        </div>
        <button id="start-btn" onclick="startGame()" class="p3-btn" disabled>LOADING...</button>
        <a href="arcade.php" style="color: #666; font-size: 0.8rem; margin-top: 20px; text-decoration: none;">RETREAT</a>
    </div>

    <div id="game-over-screen" class="overlay" style="display: none;">
        <h1 style="color: #ff4d4d; margin-bottom: 0;">DEFEAT</h1>
        <h2 id="final-score" style="color: white; font-size: 2rem; margin: 10px 0;">0</h2>
        
        <div class="leaderboard-box">
            <div style="text-align: center; color: var(--p3-blue); font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid var(--p3-blue); padding-bottom: 5px;">TOP OPERATIVES</div>
            <div id="lb-content">Loading...</div>
        </div>

        <button id="retry-btn" class="p3-btn" style="border-color: white;">TRY AGAIN</button>
        <a href="arcade.php" style="color: #666; font-size: 0.8rem; margin-top: 20px; text-decoration: none;">RETURN TO ENTRANCE</a>
    </div>

    <script>
        const IMAGE_PATH = 'assets/images/shad';
        const IMAGE_EXT = '.webp';
        const TOTAL_IMAGES = 7;
        const QUEUE_BUFFER = 5;
        const GAME_ID = 'p3';

        let score = 0;
        let floor = 1;
        let isPlaying = false;
        let animationFrame;
        
        // Time-Based Animation Variables
        let lastTime = 0;
        let shrinkRate = 1.5; // Pixels per ~16ms (Standard 60fps)
        let ringSize = 400;
        let isMuted = false;
        
        // Image Preloading
        let loadedCount = 0;
        const imagesCache = [];

        // DOM Elements
        const ringEl = document.getElementById('ring');
        const scoreEl = document.getElementById('score');
        const floorEl = document.getElementById('floor');
        const targetArea = document.getElementById('target-area');
        const enemyImg = document.getElementById('enemy-img');
        const enemyFallback = document.getElementById('enemy-fallback');
        const bgm = document.getElementById('bgm');
        const muteIcon = document.getElementById('mute-icon');
        const retryBtn = document.getElementById('retry-btn');
        const startBtn = document.getElementById('start-btn');
        
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

        // --- PRELOAD ASSETS ---
        function preloadAssets() {
            for(let i=1; i<=TOTAL_IMAGES; i++) {
                const img = new Image();
                img.src = `${IMAGE_PATH}${i}${IMAGE_EXT}`;
                img.onload = () => checkLoad();
                img.onerror = () => checkLoad(); // Count even if fails
                imagesCache.push(img);
            }
        }

        function checkLoad() {
            loadedCount++;
            if(loadedCount >= TOTAL_IMAGES) {
                startBtn.disabled = false;
                startBtn.innerText = "ENGAGE";
                startBtn.style.borderColor = "var(--p3-blue)";
                startBtn.style.color = "white";
            }
        }
        preloadAssets();

        // --- AUDIO LOGIC ---
        function toggleSound() {
            isMuted = !isMuted;
            bgm.muted = isMuted;
            
            if (isMuted) {
                muteIcon.classList.remove('fa-volume-high');
                muteIcon.classList.add('fa-volume-xmark');
            } else {
                muteIcon.classList.remove('fa-volume-xmark');
                muteIcon.classList.add('fa-volume-high');
                if (audioCtx.state === 'suspended') audioCtx.resume();
            }
        }

        function playSound(type) {
            if (isMuted) return;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            
            const osc = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            osc.connect(gainNode);
            gainNode.connect(audioCtx.destination);

            if (type === 'hit') {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(600, audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.15);
                gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
                osc.start(); osc.stop(audioCtx.currentTime + 0.15);
            } else if (type === 'miss') {
                osc.type = 'square';
                osc.frequency.setValueAtTime(100, audioCtx.currentTime);
                osc.frequency.linearRampToValueAtTime(50, audioCtx.currentTime + 0.3);
                gainNode.gain.setValueAtTime(0.2, audioCtx.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
                osc.start(); osc.stop(audioCtx.currentTime + 0.3);
            }
        }

        // --- GAME CONTROL ---
        function startGame() {
            retryBtn.onclick = null;
            
            if (!isMuted) {
                bgm.volume = 0.2;
                bgm.play().catch(e => console.log("Audio autoplay prevented"));
            }

            score = 0;
            floor = 1;
            
            scoreEl.innerText = score;
            floorEl.innerText = floor;
            
            document.getElementById('start-screen').style.display = 'none';
            document.getElementById('game-over-screen').style.display = 'none';
            
            isPlaying = true;
            lastTime = performance.now(); // Reset time tracker
            nextRound();
        }

        function nextRound() {
            if(!isPlaying) return;

            // Pick random image from cache directly
            const randIndex = Math.floor(Math.random() * TOTAL_IMAGES);
            const cachedImg = imagesCache[randIndex];
            
            if (cachedImg.complete && cachedImg.naturalHeight !== 0) {
                enemyImg.src = cachedImg.src;
                enemyImg.style.display = 'block';
                enemyFallback.style.display = 'none';
            } else {
                // Fallback if image failed loading
                enemyImg.style.display = 'none';
                enemyFallback.style.display = 'flex';
            }
            
            // Random Position
            const safeX = Math.max(0, window.innerWidth - 300);
            const safeY = Math.max(0, window.innerHeight - 300);
            const randX = Math.random() * safeX;
            const randY = (Math.random() * (safeY - 80)) + 80;

            targetArea.style.left = randX + 'px';
            targetArea.style.top = randY + 'px';
            
            // Reset Ring
            ringSize = 450;
            ringEl.style.width = ringSize + 'px';
            ringEl.style.height = ringSize + 'px';
            ringEl.style.opacity = 1;
            ringEl.style.borderColor = 'var(--p3-blue)'; 
            
            // Increase speed slightly per floor
            // START SPEED: 1.5
            // MAX SPEED: 6.0
            shrinkRate = 2.5 + (floor * 0.3);
            
            lastTime = performance.now(); // Reset delta timer
            animationFrame = requestAnimationFrame(animate);
        }

        // --- DELTA TIME ANIMATION LOOP ---
        function animate(timestamp) {
            if(!isPlaying) return;

            const deltaTime = timestamp - lastTime;
            lastTime = timestamp;

            // Normalize speed to 60 FPS (16.66ms per frame)
            // If lag occurs (deltaTime is high), movement increases proportionally
            const timeScale = deltaTime / 16.66; 
            
            ringSize -= (shrinkRate * timeScale);
            
            ringEl.style.width = ringSize + 'px';
            ringEl.style.height = ringSize + 'px';
            
            // Visual Feedback
            const TARGET_SIZE = 180;
            const TOLERANCE = 40;
            
            let diff = Math.abs(ringSize - TARGET_SIZE);

            if (diff < TOLERANCE) {
                ringEl.style.borderColor = '#fff'; 
                ringEl.style.boxShadow = '0 0 25px #fff, inset 0 0 10px #fff';
            } else if (ringSize < TARGET_SIZE - TOLERANCE) {
                // Too small = Miss
                triggerGameOver();
                return;
            } else {
                ringEl.style.borderColor = 'var(--p3-blue)'; 
                ringEl.style.boxShadow = '0 0 15px var(--p3-blue)'; 
            }

            if (ringSize > 0) {
                animationFrame = requestAnimationFrame(animate);
            }
        }

        let lastTap = 0;
        function handleTap(e) {
            // --- FIX START ---
            // If this is a touch event, prevent the mouse event from firing afterwards
            if (e && e.type === 'touchstart') {
                e.preventDefault(); // Prevents the phantom mousedown
            }
            // --- FIX END ---

            if (e && e.target.closest('.mute-btn')) return;

            if(!isPlaying) return;
            
            // Prevent Double Taps (Your existing debouncer is good, but preventDefault is safer)
            const now = Date.now();
            if (now - lastTap < 200) return; 
            lastTap = now;

            const TARGET_SIZE = 180;
            const TOLERANCE = 40; // 40px window
            const CRITICAL_ZONE = 10; // 10px window

            let diff = Math.abs(ringSize - TARGET_SIZE);
            
            if (diff <= TOLERANCE) {
                // SUCCESS
                cancelAnimationFrame(animationFrame);
                playSound('hit');
                
                let points = 0;
                let text = "";
                let color = "";

                if (diff <= CRITICAL_ZONE) {
                    points = 200 + (floor * 20);
                    text = "CRITICAL!";
                    color = "#fff"; 
                } else {
                    points = 100 + (floor * 10);
                    text = "HIT";
                    color = "var(--p3-blue)"; 
                }

                score += points;
                floor++;
                
                scoreEl.innerText = score;
                floorEl.innerText = floor;
                
                // Show Effect
                const rect = targetArea.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                showHitEffect(text, color, centerX, centerY);
                
                // Hit Animation
                const activeEl = (enemyImg.style.display !== 'none') ? enemyImg : enemyFallback;
                activeEl.style.transform = "scale(1.2)";
                activeEl.style.filter = "brightness(3)";
                setTimeout(() => {
                    activeEl.style.transform = "scale(1)";
                    activeEl.style.filter = (activeEl === enemyImg) ? "drop-shadow(0 0 10px rgba(0,0,0,0.8))" : "none";
                }, 100);

                setTimeout(nextRound, 300);
            } else {
                // FAIL (Tapped too early)
                if (ringSize > TARGET_SIZE + TOLERANCE) {
                   triggerGameOver();
                }
            }
        }

        function triggerGameOver() {
            isPlaying = false;
            cancelAnimationFrame(animationFrame);
            playSound('miss');
            bgm.pause();
            
            document.getElementById('final-score').innerText = score;
            document.getElementById('game-over-screen').style.display = 'flex';
            
            // Re-bind retry after delay to prevent accidental clicks
            retryBtn.onclick = null;
            setTimeout(() => {
                retryBtn.onclick = startGame;
            }, 500);
            
            submitScore(score);
        }

        function showHitEffect(text, color, x, y) {
            const effect = document.createElement('div');
            effect.className = 'hit-effect';
            effect.innerText = text;
            effect.style.color = color;
            effect.style.position = 'fixed'; 
            effect.style.left = x + 'px';
            effect.style.top = y + 'px';
            document.body.appendChild(effect);
            setTimeout(() => effect.remove(), 600);
        }

        function submitScore(finalScore) {
            const lbDiv = document.getElementById('lb-content');
            lbDiv.innerHTML = "Saving...";

            fetch('api_score_p3.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ score: finalScore, game_id: GAME_ID, icon_id: 1 })
            })
            .then(res => res.json())
            .then(data => { 
                if (data.status === 'db_error') {
                    lbDiv.innerHTML = `<span style="color:red; font-size: 0.9rem;">${data.message}</span>`;
                } else {
                    fetchLeaderboard(); 
                }
            })
            .catch(err => {
                lbDiv.innerHTML = `<span style="color:red; font-size: 0.9rem;">Network Error</span>`;
            });
        }

        function fetchLeaderboard() {
            fetch('api_score_p3.php?game_id=' + GAME_ID)
            .then(res => res.json())
            .then(data => {
                const lbDiv = document.getElementById('lb-content');
                if(data.scores && data.scores.length > 0) {
                    let html = '';
                    let rank = 1;
                    data.scores.forEach(row => {
                        html += `<div class=\"lb-row\"><span>#${rank} ${row.codename_display}</span><span>${row.score}</span></div>`;
                        rank++;
                    });
                    lbDiv.innerHTML = html;
                } else {
                    lbDiv.innerHTML = "No Data.";
                }
            });
        }
    </script>
<?php include 'footer.php'; ?>