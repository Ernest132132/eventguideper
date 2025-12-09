<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$operativeId = $_SESSION['user_id'];
$playerNameCensored = substr($_SESSION['codename'] ?? 'Guest', 0, 2) . '***';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Phantom Infiltration | Mementos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="assets/style.css?v=72">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- CORE P5 THEME VARIABLES --- */
        :root {
            --p5-red: #e60012;
            --p5-black: #1a1a1a;
            --p5-yellow: #ffd700;
            --p5-white: #f0f0f0;
        }

        body {
            background-color: #000;
            /* Dynamic Mementos Pattern */
            background-image: 
                linear-gradient(45deg, #111 25%, transparent 25%, transparent 75%, #111 75%, #111),
                linear-gradient(45deg, #111 25%, transparent 25%, transparent 75%, #111 75%, #111),
                radial-gradient(circle at 50% 50%, #2a0000 0%, #000 90%);
            background-size: 60px 60px, 60px 60px, 100% 100%;
            background-position: 0 0, 30px 30px, 0 0;
            animation: mementosFlow 20s linear infinite;
            
            overflow: hidden; 
            touch-action: none; 
            font-family: 'Cinzel', serif; /* Keeps the classic feel, but impact font would be ideal if available */
            color: var(--p5-white); 
            user-select: none;
        }

        @keyframes mementosFlow {
            0% { background-position: 0 0, 30px 30px, 0 0; }
            100% { background-position: 60px 60px, 90px 90px, 0 0; }
        }

        .game-wrapper {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }

        /* --- UI ELEMENTS (SKEWED STYLE) --- */
        .ui-header {
            position: absolute; top: 20px; left: 0; width: 100%;
            display: flex; justify-content: space-between; padding: 0 20px;
            box-sizing: border-box; z-index: 100;
        }

        .score-box, .level-box {
            background: var(--p5-red); 
            border: 3px solid var(--p5-white); 
            padding: 5px 25px;
            font-size: 1.1rem; 
            color: var(--p5-white); 
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            /* The P5 Skew */
            transform: skewX(-15deg);
            box-shadow: 5px 5px 0px rgba(0,0,0,0.8);
        }
        
        /* Counter-skew the text so it stays readable */
        .score-box span, .level-box span {
            display: inline-block;
            transform: skewX(15deg);
        }

        .mute-btn {
            background: var(--p5-black); 
            border: 2px solid #666; 
            color: #fff;
            cursor: pointer;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            margin-right: 15px;
            transform: skewX(-15deg);
        }
        .mute-btn i { transform: skewX(15deg); }

        /* --- SEQUENCE DISPLAY (THE "CALLING CARD" BOX) --- */
        #sequence-display {
            width: 90%; max-width: 380px; min-height: 110px;
            background: rgba(0,0,0,0.85); 
            border: 4px solid var(--p5-white);
            /* Jagged shape using clip-path could be cool, but border is safer for sizing */
            transform: rotate(-2deg); /* Slight rotation for style */
            border-radius: 4px; 
            margin-bottom: 40px;
            display: flex; align-items: center; justify-content: center;
            flex-wrap: wrap; padding: 15px;
            box-shadow: 10px 10px 0 var(--p5-red);
            position: relative; 
            z-index: 10;
        }
        
        .arrow-icon { 
            font-size: 2.2rem; 
            color: var(--p5-red); 
            margin: 5px; 
            filter: drop-shadow(2px 2px 0 #fff);
            transition: transform 0.1s; 
        }
        .player-icon { 
            height: 40px; width: 40px; 
            margin-right: 10px; 
            border-radius: 50%; 
            border: 2px solid var(--p5-white);
            box-shadow: 0 0 10px var(--p5-red);
        }

        /* --- INPUT GRID --- */
        .input-grid {
            width: 260px; height: 260px;
            display: grid; grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr); gap: 12px;
            pointer-events: none; opacity: 0.6;
            z-index: 20;
            transform: rotate(2deg); /* Counter-rotate against display */
        }
        .input-grid-active { pointer-events: auto; opacity: 1; }

        .input-btn {
            background: #222; 
            border: 2px solid #555; 
            /* Diamond/Star shape hint using border-radius */
            border-radius: 10px 0 10px 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #888; cursor: pointer;
            transition: all 0.1s;
            box-shadow: 3px 3px 0 #000;
        }
        
        .input-btn:active, .input-btn.pressed { 
            background: var(--p5-red); 
            color: white; 
            border-color: white;
            transform: scale(0.9); 
            box-shadow: 0 0 15px var(--p5-red);
        }

        /* Specific Icons colors */
        .input-btn i { filter: drop-shadow(2px 2px 0px #000); }

        /* Mapping Grid */
        .btn-up { grid-column: 2 / 3; grid-row: 1 / 2; }
        .btn-left { grid-column: 1 / 2; grid-row: 2 / 3; }
        .btn-center { 
            grid-column: 2 / 3; grid-row: 2 / 3; 
            background: var(--p5-black);
            border: 2px solid var(--p5-red);
            color: var(--p5-red);
        } 
        .btn-right { grid-column: 3 / 4; grid-row: 2 / 3; }
        .btn-down { grid-column: 2 / 3; grid-row: 3 / 4; }

        /* --- TIMER (MOVED) --- */
        #timer-ring {
            width: 90px; height: 90px; 
            position: absolute;
            top: 100px; /* Positioned above grid */
            left: 50%; 
            transform: translateX(-50%); 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; 
            color: var(--p5-yellow);
            font-weight: bold;
            text-shadow: 2px 2px 0 var(--p5-red);
            z-index: 5; 
            pointer-events: none; 
            background: rgba(0,0,0,0.6);
            border: 2px solid var(--p5-red);
            box-shadow: 0 0 15px var(--p5-red);
        }
        
        /* --- SCREENS --- */
        .overlay-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.92); 
            /* Red stripes overlay */
            background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(230,0,18,0.05) 10px, rgba(230,0,18,0.05) 20px);
            display: flex;
            flex-direction: column; align-items: center; justify-content: center;
            z-index: 200; text-align: center;
        }

        /* --- CHARACTER GRID CSS --- */
        .char-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            transform: skewX(-5deg);
            border: 2px solid #333;
        }
        .char-icon {
            width: 70px; height: 70px;
            border-radius: 0; /* Square for P5 style */
            border: 2px solid var(--p5-white);
            cursor: pointer;
            transition: 0.2s;
            object-fit: cover;
            background: #000;
            transform: skewX(5deg); /* Counter skew image */
            box-shadow: 3px 3px 0 #000;
        }
        .char-icon:hover { 
            transform: skewX(5deg) scale(1.1); 
            border-color: var(--p5-red); 
        }
        .char-icon.selected {
            border-color: var(--p5-red);
            box-shadow: 0 0 0 3px var(--p5-yellow);
            transform: skewX(5deg) scale(1.15);
            z-index: 10;
        }

        /* --- BUTTONS (MAIN) --- */
        .p5-btn {
            background: var(--p5-black);
            color: var(--p5-white);
            border: 2px solid var(--p5-white);
            padding: 15px 50px;
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 1.4rem;
            margin-top: 25px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
            
            /* The Shape */
            transform: skewX(-20deg) rotate(-2deg);
            box-shadow: 10px 10px 0 var(--p5-red);
            transition: 0.2s;
        }
        
        .p5-btn:hover {
            background: var(--p5-white);
            color: var(--p5-red);
            box-shadow: 15px 15px 0 var(--p5-red);
            transform: skewX(-20deg) rotate(-2deg) translate(-2px, -2px);
        }

        .p5-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* --- LEADERBOARD FORMATTING --- */
        .leaderboard-box {
            width: 85%; max-width: 340px; 
            background: #000;
            border: 2px solid var(--p5-white); 
            padding: 15px; margin-top: 20px;
            text-align: left; max-height: 250px;
            overflow-y: auto;
            transform: rotate(1deg); /* Slight messy rotation */
            box-shadow: -10px 10px 0 rgba(230,0,18,0.3);
        }
        .lb-row {
            display: flex;
            justify-content: space-between; 
            align-items: center;
            border-bottom: 1px solid #333;
            padding: 10px 5px;
            font-size: 0.95rem;
            color: #ddd;
            font-family: sans-serif; /* Cleaner font for reading list */
            font-weight: bold;
        }
        /* Name Column */
        .lb-row span:nth-child(1) {
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis; 
            white-space: nowrap;
            margin-right: 15px; 
            flex: 1;
            display: flex; align-items: center;
        }
        .lb-row span:nth-child(1) img {
            width: 25px; height: 25px; margin-right: 8px; border-radius: 50%; border: 1px solid white;
        }
        /* Score Column */
        .lb-row span:nth-child(2) {
            text-align: right;
            min-width: 60px; 
            color: var(--p5-red);
            font-size: 1.1rem;
        }
        
        .lb-row:nth-child(1) { color: var(--p5-yellow); border-bottom: 2px solid var(--p5-yellow); }
    </style>
</head>
<body>

    <audio id="bgm" loop><source src="assets/audio/bgm_thieves.mp3" type="audio/mpeg"></audio>

    <div class="game-wrapper">
        
        <div class="ui-header">
            <div style="display: flex;">
                <div class="mute-btn" onclick="toggleSound()">
                    <i id="mute-icon" class="fa-solid fa-volume-high"></i>
                </div>
                <div class="score-box"><span>SCORE: <span id="score">0</span></span></div>
            </div>
            <div class="level-box"><span>PHASE: <span id="level">1</span></span></div>
        </div>

        <div id="timer-ring" style="display: none;">
            <svg viewBox="0 0 40 40" style="position: absolute; width: 100%; height: 100%; transform: rotate(-90deg);">
                <circle cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke="#000" stroke-width="4"></circle>
                <circle id="progress-circle" cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke="#e60012" stroke-width="3" stroke-dasharray="100 100" stroke-dashoffset="100" style="transition: stroke-dashoffset linear;"></circle>
            </svg>
            <span id="timer-text"></span>
        </div>

        <div id="sequence-display">
            <p style="color: var(--p5-yellow); font-weight: bold; font-size: 1.2rem; text-shadow: 2px 2px 0 #000;">MEMORIZE THE PATH</p>
        </div>

        <div class="input-grid" id="input-grid">
            <div style="grid-column: 1 / 2; grid-row: 1 / 2;"></div>
            <div style="grid-column: 3 / 4; grid-row: 1 / 2;"></div>
            <div style="grid-column: 1 / 2; grid-row: 3 / 4;"></div>
            <div style="grid-column: 3 / 4; grid-row: 3 / 4;"></div>

            <button class="input-btn btn-up" data-dir="U" onmousedown="handleInput('U', event)" ontouchstart="handleInput('U', event)">
                <i class="fa-solid fa-arrow-up"></i>
            </button>
            <button class="input-btn btn-left" data-dir="L" onmousedown="handleInput('L', event)" ontouchstart="handleInput('L', event)">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <button class="input-btn btn-center" data-dir="T" onmousedown="handleInput('T', event)" ontouchstart="handleInput('T', event)">
                <i class="fa-solid fa-mask"></i>
            </button>
            <button class="input-btn btn-right" data-dir="R" onmousedown="handleInput('R', event)" ontouchstart="handleInput('R', event)">
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button class="input-btn btn-down" data-dir="D" onmousedown="handleInput('D', event)" ontouchstart="handleInput('D', event)">
                <i class="fa-solid fa-arrow-down"></i>
            </button>
        </div>

    </div>

    <div id="start-screen" class="overlay-screen">
        <h1 style="color: var(--p5-white); background: var(--p5-black); padding: 10px 20px; transform: skewX(-10deg) rotate(-5deg); box-shadow: 5px 5px 0 var(--p5-red); font-size: 2.5rem; margin-bottom: 30px;">
            MEMENTOS <span style="color:var(--p5-red)">INFILTRATION</span>
        </h1>
        <p style="color: #ccc; margin: 20px; font-weight: bold; text-shadow: 1px 1px 0 #000;">
            A quick test of focus and memory.<br>
            Repeat the sequence to infiltrate deeper.
        </p>
        <button class="p5-btn" onclick="showCharacterSelect()">START MISSION</button>
        <br>
        <a href="arcade.php" style="color: #888; margin-top: 20px; display:block; font-weight:bold; text-decoration:none;">RETURN TO ARCADE</a>
    </div>
    
    <div id="character-select-screen" class="overlay-screen" style="display: none;">
        <h2 style="color: white; margin-bottom: 20px; background:var(--p5-red); padding: 5px 15px; transform: skewX(-15deg);">CONFIDANT SELECTION</h2>
        <div class="char-grid" id="char-grid">
            </div>
        <p style="color: var(--p5-yellow); font-size: 0.9rem; font-style: italic;">(Select your Phantom Thief)</p>
        <button class="p5-btn" onclick="initGame()" disabled id="select-btn">CONFIRM</button>
    </div>

    <div id="game-over-modal" class="overlay-screen" style="display: none;">
        <h1 style="color: var(--p5-red); font-size: 3.5rem; text-shadow: 4px 4px 0 #fff; transform: rotate(-5deg); margin-bottom: 0;">SECURITY ALERT</h1>
        <h2 id="final-score" style="color: white; font-size: 2.5rem; margin: 10px 0; font-weight: 900;">0</h2>
        <p style="color: var(--p5-yellow);">PHASE <span id="final-level"></span> COMPLETE</p>
        
        <div class="leaderboard-box">
            <div style="text-align: center; color: var(--p5-black); background: var(--p5-white); font-weight: bold; margin-bottom: 10px; padding: 5px; transform: skewX(-10deg);">TOP INFILTRATORS</div>
            <div id="lb-content">Loading...</div>
        </div>

        <button class="p5-btn" onclick="showCharacterSelect()">RETRY</button>
        <a href="arcade.php" style="color: #aaa; margin-top: 25px; display:block; text-decoration: none; font-weight: bold; letter-spacing: 2px;">RETREAT</a>
    </div>

    <script>
        // R: Right, L: Left, U: Up, D: Down, T: Thief (Mask)
        const ACTIONS_MAP = { 'U': 'arrow-up', 'D': 'arrow-down', 'L': 'arrow-left', 'R': 'arrow-right', 'T': 'mask' };
        const ACTIONS = Object.keys(ACTIONS_MAP); 
        const GAME_ID = 'p5';
        const TOTAL_CHARACTERS = 9;
        
        // --- GAME STATE ---
        let score = 0;
        let level = 1;
        let currentSequence = [];
        let playerSequence = [];
        let isMemorizing = true;
        let isInputting = false;
        let selectedCharacter = null;
        let inputStartTime = 0; // For time bonus

        // --- TIMING/DIFFICULTY ---
        const BASE_SEQ_LENGTH = 3;
        const BASE_MEMORIZE_TIME = 2000;
        const BASE_INPUT_TIME = 1500; 
        let requiredInputTime = 0;
        
        // --- DOM / AUDIO ---
        const DOM = {
            score: document.getElementById('score'), level: document.getElementById('level'),
            display: document.getElementById('sequence-display'), inputGrid: document.getElementById('input-grid'),
            timerRing: document.getElementById('timer-ring'), progressCircle: document.getElementById('progress-circle'),
            timerText: document.getElementById('timer-text'), modal: document.getElementById('game-over-modal'),
            finalScore: document.getElementById('final-score'), finalLevel: document.getElementById('final-level'),
            bgm: document.getElementById('bgm'), selectBtn: document.getElementById('select-btn')
        };
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        let isMuted = localStorage.getItem('isMutedP5') === 'true';

        let timerTimeout;
        let timerInterval;

        // --- AUDIO FUNCTIONS ---
        function updateMuteIcon() {
            document.getElementById('mute-icon').className = isMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
            DOM.bgm.muted = isMuted;
        }

        function toggleSound() {
            isMuted = !isMuted;
            localStorage.setItem('isMutedP5', isMuted);
            updateMuteIcon();
            if (!isMuted && audioCtx.state === 'suspended') audioCtx.resume();
        }
        
        function playSFX(type) {
            if (isMuted) return;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            
            const osc = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            osc.connect(gainNode); gainNode.connect(audioCtx.destination);

            if (type === 'correct') {
                osc.type = 'triangle'; osc.frequency.setValueAtTime(800, audioCtx.currentTime); 
                gainNode.gain.setValueAtTime(0.15, audioCtx.currentTime); gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.08);
                osc.start(); osc.stop(audioCtx.currentTime + 0.08);
            } else if (type === 'fail') {
                osc.type = 'sawtooth'; osc.frequency.setValueAtTime(100, audioCtx.currentTime); 
                gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime); gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
                osc.start(); osc.stop(audioCtx.currentTime + 0.3);
            } else if (type === 'round_success') {
                const now = audioCtx.currentTime;
                const notes = [600, 750, 900, 1100]; 
                notes.forEach((freq, i) => {
                    const osc = audioCtx.createOscillator();
                    const gainNode = audioCtx.createGain();
                    osc.connect(gainNode); gainNode.connect(audioCtx.destination);
                    
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, now + i * 0.05);
                    gainNode.gain.setValueAtTime(0.15, now + i * 0.05);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, now + i * 0.05 + 0.15);
                    
                    osc.start(now + i * 0.05);
                    osc.stop(now + i * 0.05 + 0.15);
                });
            }
        }

        // --- CHARACTER SELECT ---
        function showCharacterSelect() {
            document.getElementById('start-screen').style.display = 'none';
            document.getElementById('game-over-modal').style.display = 'none';
            document.getElementById('character-select-screen').style.display = 'flex';
            
            const charGrid = document.getElementById('char-grid');
            if (charGrid.children.length === 0) {
                for (let i = 1; i <= TOTAL_CHARACTERS; i++) {
                    const img = document.createElement('img');
                    img.className = 'char-icon';
                    img.src = `assets/images/${i}.webp`;
                    img.setAttribute('data-id', i);
                    img.onclick = () => selectCharacter(i, img);
                    charGrid.appendChild(img);
                }
            }
            
            if(selectedCharacter) {
                selectCharacter(selectedCharacter);
            } else {
                 DOM.selectBtn.disabled = true;
            }
        }
        
        function selectCharacter(id, el) {
            document.querySelectorAll('.char-icon').forEach(icon => icon.classList.remove('selected'));
            selectedCharacter = id;
            if(el) el.classList.add('selected');
            else document.querySelector(`.char-icon[data-id="${id}"]`).classList.add('selected');
            DOM.selectBtn.disabled = false;
        }
        
        // --- GAME FLOW ---
        function initGame() {
            if (!selectedCharacter) return;
            document.getElementById('character-select-screen').style.display = 'none';
            
            // --- ADD THIS LINE HERE ---
            DOM.bgm.volume = 0.2;
            
            if (!isMuted) DOM.bgm.play().catch(e => console.log("BGM autoplay blocked"));
            
            score = 0;
            level = 1;
            
            DOM.score.innerText = score;
            DOM.level.innerText = level;
            
            nextLevel();
        }

        function calculateDifficulty() {
            const seqLength = BASE_SEQ_LENGTH + Math.floor(level / 3);
            const memorizeTime = Math.max(700, BASE_MEMORIZE_TIME - (level * 100)); 
            const inputTimePerItem = 1500 + (level * 50);
            requiredInputTime = 2000 + (seqLength * inputTimePerItem); 

            return { seqLength, memorizeTime };
        }

        function nextLevel() {
            isMemorizing = true;
            
            const difficulty = calculateDifficulty();
            currentSequence = [];
            playerSequence = [];

            // 1. Generate Sequence
            for (let i = 0; i < difficulty.seqLength; i++) {
                currentSequence.push(ACTIONS[Math.floor(Math.random() * ACTIONS.length)]);
            }

            // 2. Display Sequence
            const sequenceHTML = `<img src="assets/images/${selectedCharacter}.webp" class="player-icon" alt="Player Icon">` + 
                                 currentSequence.map(dir => `<i class="fa-solid fa-${ACTIONS_MAP[dir]} arrow-icon" data-dir="${dir}"></i>`).join('');
            DOM.display.innerHTML = sequenceHTML;
            DOM.display.style.border = '3px solid #ff4d4d';
            
            // 3. Start Memorize Timer
            DOM.inputGrid.classList.remove('input-grid-active');
            clearTimeout(timerTimeout);
            timerTimeout = setTimeout(startInputPhase, difficulty.memorizeTime);
        }

        function startInputPhase() {
            isMemorizing = false;
            isInputting = true;
            
            // 1. Clear visualization
            DOM.display.innerHTML = `<p style="color:white; font-size: 1.2rem;">INPUT PATH (0/${currentSequence.length})</p>`;
            DOM.display.style.border = '3px solid #ffd700';
            
            // 2. Start Time Tracking (Used for bonus)
            inputStartTime = Date.now();

            // 3. Show Timer
            DOM.timerRing.style.display = 'flex';
            DOM.progressCircle.style.strokeDashoffset = '100';
            DOM.progressCircle.style.transition = 'none';
            
            setTimeout(() => {
                DOM.progressCircle.style.strokeDashoffset = '0';
                DOM.progressCircle.style.transition = `stroke-dashoffset ${requiredInputTime}ms linear`;
            }, 50);

            let startTime = Date.now();
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                let timeLeft = (requiredInputTime - (Date.now() - startTime)) / 1000;
                DOM.timerText.innerText = Math.max(0, timeLeft).toFixed(1);
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    handleFail("TIME UP. Security detected your presence.");
                }
            }, 100);

            DOM.inputGrid.classList.add('input-grid-active');
        }

        function handleInput(dir, e) {
            // --- FIX START ---
            // 1. Block Ghost Clicks
            if (e && e.type === 'touchstart') {
                e.preventDefault();
            }

            // 2. Manually trigger visual "Press" effect 
            // (Since preventDefault kills the CSS :active state)
            if (e) {
                const btn = e.target.closest('.input-btn');
                if (btn) {
                    btn.classList.add('pressed');
                    setTimeout(() => btn.classList.remove('pressed'), 150);
                }
            }
            // --- FIX END ---

            if (!isInputting) return;

            const expectedDir = currentSequence[playerSequence.length];
            
            if (dir === expectedDir) {
                playSFX('correct');
                playerSequence.push(dir);
                
                DOM.display.innerHTML = `<p style="color:#00d26a;">Correct! (${playerSequence.length}/${currentSequence.length})</p>`;
                
                if (playerSequence.length === currentSequence.length) {
                    handleSuccess();
                }
            } else {
                handleFail("INCORRECT PATH. Security alarm triggered!");
            }
        }

        function handleSuccess() {
            isInputting = false;
            clearTimeout(timerTimeout);
            clearInterval(timerInterval);
            playSFX('round_success');

            const timeSpent = Date.now() - inputStartTime;
            const timeRemaining = requiredInputTime - timeSpent;
            const timeBonus = Math.floor(Math.max(0, timeRemaining) / 10); 
            
            const basePoints = currentSequence.length * 500; 
            const totalPoints = basePoints + timeBonus;

            score += totalPoints;
            level++;
            
            DOM.score.innerText = score;
            
            DOM.display.innerHTML = `<p style="color:#ffd700;">INFILTRATION SUCCESS! (+${basePoints} BASE +${timeBonus} TIME)</p>`;
            DOM.display.style.border = '3px solid #00d26a';
            DOM.timerRing.style.display = 'none';
            DOM.inputGrid.classList.remove('input-grid-active');

            setTimeout(nextLevel, 1500);
        }

        function handleFail(message) {
            isInputting = false;
            clearTimeout(timerTimeout);
            clearInterval(timerInterval);
            playSFX('fail');
            DOM.bgm.pause();
            
            DOM.timerRing.style.display = 'none';
            DOM.inputGrid.classList.remove('input-grid-active');
            
            DOM.display.innerHTML = `<p style="color:#ff4d4d;">${message}</p>`;
            DOM.display.style.border = '3px solid #ff4d4d';

            DOM.finalScore.innerText = score;
            DOM.finalLevel.innerText = level;

            submitScore(score);
        }
        
        // --- LEADERBOARD & SUBMIT ---
        function submitScore(finalScore) {
            const lbDiv = document.getElementById('lb-content');
            lbDiv.innerHTML = "Saving...";

            fetch('api_score_p5.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ score: finalScore, game_id: GAME_ID, icon_id: selectedCharacter })
            })
            .then(res => res.json())
            .then(data => { fetchLeaderboard(); });
        }

        function fetchLeaderboard() {
            fetch(`api_score_p5.php?game_id=${GAME_ID}`)
            .then(res => res.json())
            .then(data => {
                const lbDiv = document.getElementById('lb-content');
                if(data.scores && data.scores.length > 0) {
                    let html = '';
                    let rank = 1;
                    const displayCount = Math.min(10, data.scores.length); 

                    for(let i = 0; i < displayCount; i++) {
                        const row = data.scores[i];
                        html += `<div class="lb-row"><span><img src="assets/images/${row.icon_id || 1}.webp" class="player-icon" alt="Icon"> ${row.codename_display}</span><span>${row.score}</span></div>`;
                        rank++;
                    }
                    lbDiv.innerHTML = html;
                } else {
                    lbDiv.innerHTML = "No Data.";
                }
                
                DOM.modal.style.display = 'flex';
            });
        }
        
        // --- INIT ---
        document.addEventListener('DOMContentLoaded', () => {
             updateMuteIcon();
             if (audioCtx.state === 'suspended') audioCtx.resume();
             document.getElementById('start-screen').style.display = 'flex';
        });
    </script>
<?php include 'footer.php'; ?>