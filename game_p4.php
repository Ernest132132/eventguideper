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
    <title>Midnight Tuner | P4 Mini-Game</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="assets/style.css?v=60">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #111;
            background-image: repeating-linear-gradient(
                45deg,
                #1a1a1a,
                #1a1a1a 10px,
                #222 10px,
                #222 20px
            );
            overflow: hidden;
            touch-action: none;
            font-family: 'Cinzel', serif;
            color: #ffd700;
            user-select: none;
        }

        .game-wrapper {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* --- THE TV SET --- */
        .tv-frame {
            position: relative;
            width: 340px;
            height: 620px;
            background: #2b2b2b;
            border-radius: 30px;
            padding: 20px;
            box-shadow: 
                inset 0 0 20px #000,
                0 0 50px rgba(255, 215, 0, 0.1);
            border: 4px solid #444;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .screen-container {
            width: 100%;
            height: 360px; /* 3:4 Aspect */
            background: #000;
            border-radius: 60% 60% 50% 50% / 10%; /* CRT Curve */
            position: relative;
            overflow: hidden;
            border: 2px solid #111;
            box-shadow: inset 0 0 40px rgba(0,0,0,0.8);
        }

        .target-image {
            width: 100%; height: 100%;
            object-fit: cover;
            filter: blur(20px) grayscale(100%) brightness(0.5);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .static-noise {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAECAYAAACp8Z5+AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAACNJREFUeNpi/P//PwMlgImBQjAAMA4jKwCDQAx0AegAwwACEwE7DOr91AAAAABJRU5ErkJggg==');
            opacity: 0.9;
            pointer-events: none;
            z-index: 5;
        }

        .scanlines {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%);
            background-size: 100% 4px;
            z-index: 20; /* Above text */
            pointer-events: none;
        }

        /* --- ENDING TEXT (Inside TV) --- */
        #tv-ending-layer {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 15; /* Below scanlines */
            opacity: 0;
            transition: opacity 2s ease-in;
            background: #000;
        }
        .spooky-text {
            font-family: 'Courier New', monospace;
            color: red;
            font-size: 1.4rem;
            text-align: center;
            text-shadow: 2px 2px 5px #000;
            padding: 20px;
        }

        /* --- CONTROLS AREA --- */
        .controls-area {
            flex-grow: 1;
            width: 100%;
            position: relative;
            padding-top: 15px;
        }

        /* Knobs Container (Active Game) */
        #knobs-wrapper {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            width: 100%;
            transition: opacity 0.5s;
        }

        /* Ending Buttons (Post Game) */
        #ending-controls {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s;
        }

        .knob-container {
            width: 80px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .knob {
            width: 70px; height: 70px;
            background: radial-gradient(#444, #111);
            border-radius: 50%;
            border: 2px solid #666;
            position: relative;
            margin-bottom: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            cursor: grab;
            touch-action: none;
        }
        .knob::after {
            content: '';
            position: absolute; top: 5px; left: 50%;
            width: 4px; height: 15px;
            background: #ffd700; transform: translateX(-50%);
            border-radius: 2px;
        }
        .knob-label {
            font-size: 0.7rem; color: #888;
            font-family: sans-serif; text-transform: uppercase;
            letter-spacing: 1px; margin-bottom: 5px;
        }

        .manual-controls { display: flex; gap: 10px; }
        .tune-btn {
            background: #333; color: #ffd700; border: 1px solid #555;
            width: 30px; height: 30px; border-radius: 50%;
            font-weight: bold; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
        }
        .tune-btn:active { background: #ffd700; color: black; }

        .ui-bar {
            position: absolute; top: 15px; left: 20px; right: 20px;
            display: flex; justify-content: space-between;
            z-index: 100; pointer-events: none;
        }
        .mute-btn, .stage-indicator, .back-btn {
            pointer-events: auto; color: #ffd700;
            background: rgba(0,0,0,0.6); padding: 5px 10px;
            border-radius: 4px; border: 1px solid #ffd700;
        }
        .stage-indicator { font-weight: bold; padding: 5px 15px; }
        
        .back-btn {
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            margin-right: 10px;
        }

        /* Start Screen Overlay */
        .overlay-screen {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            z-index: 200; text-align: center;
        }
        
        .p4-btn {
            background: #ffd700; color: black; border: none;
            padding: 15px 40px; font-family: 'Cinzel', serif;
            font-weight: bold; font-size: 1.2rem;
            transform: rotate(-2deg); cursor: pointer;
            box-shadow: 5px 5px 0px #333; transition: 0.2s; margin-top: 20px;
        }
        .p4-btn:active { transform: rotate(0deg) scale(0.95); box-shadow: 2px 2px 0px #333; }
        
        .end-btn {
            background: #333; color: #ffd700; border: 1px solid #ffd700;
            padding: 12px 30px; width: 80%; margin-bottom: 10px;
            font-family: 'Cinzel', serif; text-transform: uppercase;
            cursor: pointer;
        }
        .end-btn:hover { background: #ffd700; color: black; }

    </style>
</head>
<body>

    <audio id="bgm" loop><source src="assets/audio/bgm_midnight.mp3" type="audio/mpeg"></audio>

    <div class="ui-bar">
        <div style="display: flex;">
            <a href="arcade.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            
            <div class="mute-btn" onclick="toggleSound()">
                <i id="mute-icon" class="fa-solid fa-volume-high"></i>
            </div>
        </div>
        <div class="stage-indicator">STAGE <span id="stage-num">1</span>/5</div>
    </div>

    <div class="game-wrapper">
        <div class="tv-frame">
            
            <div class="screen-container">
                <img id="tv-image" class="target-image" src="" alt="Signal">
                
                <div id="tv-ending-layer">
                    <div class="spooky-text">"Isn't she a little odd?"</div>
                </div>

                <div class="static-noise" id="static-layer"></div>
                <div class="scanlines"></div>
                <div id="flash-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; background:white; opacity:0; pointer-events:none; transition:opacity 0.2s;"></div>
            </div>

            <div class="controls-area">
                
                <div id="knobs-wrapper">
                    </div>

                <div id="ending-controls">
                    <button class="end-btn" onclick="location.reload()">REPLAY TAPE</button>
                    <button class="end-btn" style="border-color: #666; color: #aaa;" onclick="window.location.href='arcade.php'">LEAVE</button>
                </div>

            </div>

        </div>
    </div>

    <div id="start-screen" class="overlay-screen">
        <h1 style="color: #ffd700; text-shadow: 2px 2px 0 #333; font-size: 2.5rem; transform: rotate(-3deg);">MIDNIGHT CHANNEL</h1>
        <p style="color: #ccc; margin: 20px;">
            The fog is thick tonight.<br>
            Use dials to clear the static.
        </p>
        <button class="p4-btn" onclick="startGame()">TUNE IN</button>
        <br>
        <a href="arcade.php" style="color: #666; margin-top: 20px; display:block;">Return to Arcade</a>
    </div>

    <script>
        // --- ASSETS ---
        const TOTAL_STAGES = 5;
        const IMAGE_PATH = 'assets/images/mc'; 
        
        let currentStage = 1;
        let isMuted = false;
        let isPlaying = false;
        let dials = []; 
        let audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        let noiseNode = null;
        let noiseGain = null;

        const tvImage = document.getElementById('tv-image');
        const staticLayer = document.getElementById('static-layer');
        const knobsWrapper = document.getElementById('knobs-wrapper');
        const endingControls = document.getElementById('ending-controls');
        const tvEndingLayer = document.getElementById('tv-ending-layer');
        const stageNum = document.getElementById('stage-num');
        const bgm = document.getElementById('bgm');
        const flash = document.getElementById('flash-overlay');

        // --- PRELOAD ---
        function preloadImages() {
            for(let i=1; i<=TOTAL_STAGES; i++) {
                new Image().src = `${IMAGE_PATH}${i}.webp`;
            }
        }
        preloadImages();

        // --- AUDIO ---
        function toggleSound() {
            isMuted = !isMuted;
            bgm.muted = isMuted;
            document.getElementById('mute-icon').className = isMuted ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
            
            if(!isMuted && audioCtx.state === 'suspended') audioCtx.resume();
            if(isMuted && noiseGain) noiseGain.gain.value = 0;
        }

        function initNoise() {
            const bufferSize = audioCtx.sampleRate * 2; 
            const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }

            noiseNode = audioCtx.createBufferSource();
            noiseNode.buffer = buffer;
            noiseNode.loop = true;
            
            const filter = audioCtx.createBiquadFilter();
            filter.type = 'highpass';
            filter.frequency.value = 1000;

            noiseGain = audioCtx.createGain();
            noiseGain.gain.value = 0;

            noiseNode.connect(filter);
            filter.connect(noiseGain);
            noiseGain.connect(audioCtx.destination);
            noiseNode.start();
        }

        // --- GAME LOGIC ---

        function startGame() {
            document.getElementById('start-screen').style.display = 'none';
            if(!isMuted) {
                bgm.volume = 0.2;
                bgm.play().catch(e=>console.log("Autoplay blocked"));
                if(!noiseNode) initNoise();
            }
            currentStage = 1;
            loadStage();
        }

        function loadStage() {
            isPlaying = true;
            stageNum.innerText = currentStage;
            tvImage.src = `${IMAGE_PATH}${currentStage}.webp`;
            
            tvImage.style.filter = "blur(20px) grayscale(100%) brightness(0.5)";
            tvImage.style.opacity = 0;
            staticLayer.style.opacity = 0.9;

            let knobCount = (currentStage >= 4) ? 3 : 2;
            setupKnobs(knobCount);
            updateSignal(); 
        }

        function setupKnobs(count) {
            knobsWrapper.innerHTML = '';
            dials = [];
            const labels = ["TUNE", "FINE", "FOCUS"];

            for(let i=0; i<count; i++) {
                let target = Math.floor(Math.random() * 300) + 30; 
                let startVal = (target + 180) % 360; 

                const kContainer = document.createElement('div');
                kContainer.className = 'knob-container';
                
                const knob = document.createElement('div');
                knob.className = 'knob';
                knob.style.transform = `rotate(${startVal}deg)`;
                
                // Manual Buttons
                const btnRow = document.createElement('div');
                btnRow.className = 'manual-controls';
                
                const btnLeft = document.createElement('button');
                btnLeft.className = 'tune-btn';
                btnLeft.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
                btnLeft.onclick = () => moveKnob(i, -10);

                const btnRight = document.createElement('button');
                btnRight.className = 'tune-btn';
                btnRight.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
                btnRight.onclick = () => moveKnob(i, 10);

                btnRow.appendChild(btnLeft);
                btnRow.appendChild(btnRight);

                addKnobListeners(knob, i);

                const label = document.createElement('div');
                label.className = 'knob-label';
                label.innerText = labels[i];

                kContainer.appendChild(knob);
                kContainer.appendChild(label);
                kContainer.appendChild(btnRow);
                knobsWrapper.appendChild(kContainer);

                dials.push({
                    id: i, value: startVal, target: target, element: knob
                });
            }
        }

        function moveKnob(index, delta) {
            let newVal = (dials[index].value + delta) % 360;
            if(newVal < 0) newVal += 360;
            dials[index].value = newVal;
            dials[index].element.style.transform = `rotate(${newVal}deg)`;
            updateSignal();
        }

        function addKnobListeners(knob, index) {
            const updateRotation = (clientX, clientY) => {
                const rect = knob.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                const deltaX = clientX - centerX;
                const deltaY = clientY - centerY;
                let angle = Math.atan2(deltaY, deltaX) * (180 / Math.PI);
                angle = (angle + 90) % 360; 
                if (angle < 0) angle += 360;

                dials[index].value = angle;
                dials[index].element.style.transform = `rotate(${angle}deg)`;
                updateSignal();
            };

            const onStart = () => document.body.style.cursor = 'grabbing';
            const onMove = (e) => {
                const x = e.touches ? e.touches[0].clientX : e.clientX;
                const y = e.touches ? e.touches[0].clientY : e.clientY;
                updateRotation(x, y);
            };
            const onEnd = () => {
                document.body.style.cursor = 'default';
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onEnd);
                document.removeEventListener('touchmove', onMove);
                document.removeEventListener('touchend', onEnd);
            };

            knob.addEventListener('mousedown', (e) => {
                onStart();
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onEnd);
            });
            knob.addEventListener('touchstart', (e) => {
                e.preventDefault();
                onStart();
                document.addEventListener('touchmove', onMove, {passive: false});
                document.addEventListener('touchend', onEnd);
            });
        }

        function updateSignal() {
            if(!isPlaying) return;

            let totalDiff = 0;
            dials.forEach(d => {
                let diff = Math.abs(d.value - d.target);
                if(diff > 180) diff = 360 - diff;
                totalDiff += diff;
            });

            const maxError = dials.length * 180;
            const errorRatio = totalDiff / maxError; 
            const clarity = 1 - errorRatio; 

            const staticOp = Math.max(0.1, errorRatio); 
            staticLayer.style.opacity = staticOp;

            const imgOp = Math.pow(clarity, 3); 
            tvImage.style.opacity = imgOp;
            
            const blurVal = Math.floor(20 * errorRatio);
            const grayVal = Math.floor(100 * errorRatio); 
            tvImage.style.filter = `blur(${blurVal}px) grayscale(${grayVal}%) brightness(${0.5 + (clarity/2)})`;

            if(!isMuted && noiseGain) noiseGain.gain.value = (errorRatio * 0.15); 

            if (totalDiff < 15) triggerWin();
        }

        function triggerWin() {
            isPlaying = false;
            staticLayer.style.opacity = 0;
            tvImage.style.opacity = 1;
            tvImage.style.filter = "none";
            flash.style.opacity = 0.5;
            setTimeout(() => flash.style.opacity = 0, 200);

            if(!isMuted && noiseGain) noiseGain.gain.value = 0;

            setTimeout(() => {
                currentStage++;
                if(currentStage > TOTAL_STAGES) {
                    triggerEnding();
                } else {
                    tvImage.style.opacity = 0;
                    setTimeout(loadStage, 500);
                }
            }, 2000);
        }

        // --- SUBMIT COMPLETION API ---
        function submitCompletion() {
            fetch('api_score_p4.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({}) // No data needed, just the POST
            }).then(r => r.json()).then(d => {
                console.log("Completion Saved", d);
            }).catch(e => console.error("Save failed", e));
        }

        function triggerEnding() {
            bgm.pause();
            
            // 1. SAVE THE COMPLETION
            submitCompletion();

            // 2. Fade out game elements inside screen
            tvImage.style.opacity = 0;
            staticLayer.style.opacity = 0; // Or low static for effect
            
            // 3. Fade in spooky layer inside screen
            tvEndingLayer.style.opacity = 1;

            // 4. Swap Knobs for Buttons in Control Area
            knobsWrapper.style.opacity = 0;
            setTimeout(() => {
                knobsWrapper.style.display = 'none';
                endingControls.style.display = 'flex';
                // Trigger reflow
                void endingControls.offsetWidth; 
                endingControls.style.opacity = 1;
            }, 500);
        }
    </script>
<?php include 'footer.php'; ?>