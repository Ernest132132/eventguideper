<?php
// Access gate removed - anyone can view prologue
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Prologue | Velvet Room</title>
    
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Crimson+Text:ital,wght@0,400;0,600;1,400&family=Share+Tech+Mono&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        /* --- CORE OVERRIDES --- */
        body {
            overflow: hidden; 
            background-color: var(--velvet-dark-blue);
            font-family: 'Crimson Text', serif;
        }

        /* --- LAYOUT CONTAINERS --- */
        #story-viewport {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 2, 10, 0.9);
            backdrop-filter: blur(5px);
            z-index: 50;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        #story-content {
            /* FIXED: Constrain width to prevent edge-to-edge text */
            width: 85%; 
            max-width: 480px; 
            height: 100%;
            overflow-y: auto;
            padding-top: 4rem;
            padding-bottom: 150px; 
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        #story-content::-webkit-scrollbar { display: none; }

        /* --- TYPOGRAPHY (MATCHING HTML) --- */
        .story-text {
            font-size: 1.15rem;
            line-height: 1.8;
            color: var(--velvet-light-text);
            margin-bottom: 1.5rem;
            opacity: 0;
            transform: translateY(15px);
            animation: fadeInUp 0.6s ease forwards;
        }
        .story-text em { color: var(--velvet-gold); font-style: italic; }
        .story-text strong { color: white; font-weight: 600; }
        
        .delayed-1 { animation-delay: 0.2s; }
        .delayed-2 { animation-delay: 0.4s; }
        .delayed-3 { animation-delay: 0.6s; }
        .delayed-4 { animation-delay: 0.8s; }

        .act-header {
            font-family: 'Outfit', sans-serif;
            font-weight: 300;
            font-size: 0.75rem;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: var(--velvet-silver);
            margin-bottom: 0.5rem;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.6s ease forwards;
        }

        .act-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 400;
            font-size: 1.5rem;
            color: var(--velvet-gold);
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.6s ease 0.1s forwards;
        }

        /* --- CHOICES & BUTTONS (MATCHING STYLES) --- */
        .choices-container {
            margin: 2rem 0;
            opacity: 0;
            transform: translateY(15px);
            animation: fadeInUp 0.6s ease 0.8s forwards;
        }

        /* Unified Button Style for Choices and Continue */
        .choice-button {
            display: block;
            width: 100%;
            text-align: left;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--velvet-light-text);
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            font-family: 'Crimson Text', serif;
            font-size: 1rem;
            line-height: 1.5;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-radius: 0; 
        }

        .choice-button::before {
            content: '';
            position: absolute;
            left: 0; top: 0;
            height: 100%; width: 2px;
            background: var(--velvet-gold);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .choice-button:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--velvet-silver);
            padding-left: 1.5rem;
        }

        .choice-button:hover::before {
            transform: scaleY(1);
        }

        .choice-button.disabled {
            pointer-events: none;
            opacity: 0.5;
        }

        /* --- WARNING OVERLAY (RESPONSIVE FIXES) --- */
        #warning-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--velvet-dark-blue);
            z-index: 1000;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: 20px; /* Reduced base padding */
            text-align: center;
            animation: fadeIn 2s ease-in-out;
            transition: opacity 1s ease-in-out;
            box-sizing: border-box; /* Ensure padding doesn't overflow */
        }
        
        .warning-box {
            border: 2px solid var(--velvet-gold);
            background: rgba(14, 22, 56, 0.95);
            padding: 30px; 
            max-width: 500px;
            width: 90%; /* Use percentage width on mobile */
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
            border-radius: 8px; position: relative; overflow: hidden;
            box-sizing: border-box;
        }
        
        .warning-box::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(212, 175, 55, 0.1), transparent);
            transform: skewX(-25deg); animation: shimmer-border 6s infinite; pointer-events: none;
        }

        .warning-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        /* Mobile Adjustments for Warning Screen */
        @media (max-width: 480px) {
            .warning-box {
                padding: 20px;
                width: 95%; /* Use almost full width */
            }
            .warning-actions {
                flex-direction: column; /* Stack buttons vertically */
                gap: 10px;
            }
            .warning-actions .choice-button {
                width: 100%;
                margin-bottom: 0;
                text-align: center;
            }
            #warning-screen h1 {
                font-size: 1.3rem; /* Slightly smaller title */
            }
            #warning-screen p {
                font-size: 1rem;
            }
        }
        
        /* --- HORROR FX (SUBTLE / FROM HTML) --- */
        
        #story-container.glitching {
            animation: screenGlitch 0.15s infinite;
        }

        @keyframes screenGlitch {
            0% { transform: translate(0); filter: none; }
            20% { transform: translate(-2px, 1px); filter: hue-rotate(90deg); }
            40% { transform: translate(2px, -1px); filter: saturate(2); }
            60% { transform: translate(-1px, 2px); filter: hue-rotate(-90deg); }
            80% { transform: translate(1px, -2px); filter: brightness(1.5); }
            100% { transform: translate(0); filter: none; }
        }

        .glitch-text {
            position: relative;
            animation: glitchText 0.3s infinite;
        }

        @keyframes glitchText {
            0% { opacity: 1; transform: translate(0); }
            20% { opacity: 0.8; transform: translate(-2px, 1px); }
            40% { opacity: 1; transform: translate(1px, -1px); }
            60% { opacity: 0.9; transform: translate(-1px, 0); }
            80% { opacity: 1; transform: translate(2px, 1px); }
            100% { opacity: 1; transform: translate(0); }
        }

        .glitch-choice {
            animation: glitchChoice 0.2s infinite;
            color: var(--velvet-red) !important;
            border-color: var(--velvet-red) !important;
            text-shadow: 0 0 10px rgba(196, 30, 58, 0.3);
        }

        @keyframes glitchChoice {
            0%, 100% { transform: translate(0); }
            25% { transform: translate(-3px, 2px); }
            50% { transform: translate(3px, -2px); }
            75% { transform: translate(-2px, -1px); }
        }

        .horror-flash {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: var(--velvet-red);
            opacity: 0; pointer-events: none; z-index: 50;
        }

        .horror-flash.active { animation: horrorFlash 0.1s ease; }

        @keyframes horrorFlash {
            0% { opacity: 0; }
            50% { opacity: 0.3; } 
            100% { opacity: 0; }
        }

        /* Typewriter */
        .typed-container { min-height: 3rem; font-family: 'Share Tech Mono', monospace; font-size: 1.1rem; color: #fff; }
        .typed-text { display: inline; }
        .cursor {
            display: inline-block; width: 2px; height: 1.2em;
            background: var(--velvet-light-text); margin-left: 2px;
            animation: blink 0.8s infinite; vertical-align: text-bottom;
        }
        @keyframes blink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

        /* Final Screen */
        .final-screen { text-align: center; padding: 2rem 0; }
        .final-screen .location {
            font-family: 'Share Tech Mono', monospace; font-size: 0.8rem;
            color: var(--velvet-silver); letter-spacing: 0.2em; margin-bottom: 1rem;
        }
        .final-screen .venue {
            font-family: 'Outfit', sans-serif; font-size: 1.5rem;
            font-weight: 400; margin-bottom: 2rem; color: var(--velvet-gold);
        }
        .final-screen .waiting { font-style: italic; color: var(--velvet-gold); }
        .scene-break {
            text-align: center; margin: 3rem 0; color: var(--text-dim);
            font-size: 1.5rem; letter-spacing: 0.5em; opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .btn-enter {
            display: inline-block;
            margin-top: 30px;
            background: var(--velvet-gold);
            color: #000;
            padding: 12px 30px;
            font-family: 'Cinzel', serif;
            font-weight: bold;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 2px;
            opacity: 0;
            animation: fadeInUp 1s ease 3s forwards;
            transition: transform 0.2s;
        }
        .btn-enter:hover { transform: scale(1.05); }

        .memory-flash {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-style: italic; color: var(--velvet-silver);
            font-size: 1.1rem; text-align: center;
            opacity: 0; pointer-events: none; z-index: 60;
            max-width: 280px;
        }
        .memory-flash.active { animation: memoryFlash 0.4s ease; }
        @keyframes memoryFlash {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            30% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            70% { opacity: 1; }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(1.1); }
        }

        #black-screen {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 90; opacity: 0;
            pointer-events: none; transition: opacity 0.5s ease;
        }
        #black-screen.active { opacity: 1; pointer-events: auto; }

        .scanlines {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: repeating-linear-gradient(0deg, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.1) 1px, transparent 1px, transparent 2px);
            pointer-events: none; z-index: 55; opacity: 0;
            transition: opacity 0.3s ease;
        }
        .scanlines.active { opacity: 1; }

        .static-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none; z-index: 56; opacity: 0;
            background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='static'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' seed='1'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23static)'/%3E%3C/svg%3E");
            mix-blend-mode: overlay;
        }
        .static-overlay.active { animation: staticFlicker 0.1s infinite; }
        @keyframes staticFlicker { 0% { opacity: 0.1; } 50% { opacity: 0.2; } 100% { opacity: 0.05; } }

        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    </style>
</head>
<body>
    
    <audio id="bgm" loop>
        <source src="assets/audio/prologue.mp3" type="audio/mpeg">
    </audio>

    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div></div>

    <div id="black-screen"></div>
    <div class="horror-flash" id="horror-flash"></div>
    <div class="scanlines" id="scanlines"></div>
    <div class="static-overlay" id="static-overlay"></div>
    <div class="memory-flash" id="memory-flash"></div>

    <div id="warning-screen">
        <div class="warning-box">
            <h1 style="color: var(--velvet-gold); font-size: 1.5rem; margin-bottom: 20px; letter-spacing: 4px;">
                PROLOGUE
            </h1>
            <p style="font-family: 'Crimson Text', serif; font-size: 1.1rem; color: #e0e0e0; margin-bottom: 30px;">
                The following content contains themes of grief, loss, isolation, and declining mental health. This content will not be portrayed outside of the prologue and epilogue<br><br>
                It is meant to be felt, not just read.
            </p>
            
            <p style="font-size: 0.9rem; color: var(--velvet-gold-light); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                [ This experience is optional ]
            </p>

            <div class="warning-actions">
                <button class="choice-button" id="start-btn" style="width: auto; text-align:center; padding: 10px 30px; background: var(--velvet-gold); color: #000; font-weight:bold;">
                    ENTER MEMORY
                </button>
                <button class="choice-button" id="skip-btn" style="width: auto; text-align:center; padding: 10px 30px; background: transparent; border: 1px solid var(--velvet-gold); color: var(--velvet-gold);">
                    SKIP
                </button>
            </div>
        </div>
    </div>

    <div id="story-viewport" style="display:none;">
        <div id="story-content">
            </div>
    </div>

    <button id="persistent-skip-btn" class="choice-button" style="position:fixed; bottom:80px; right:20px; z-index:200; width:auto; padding:8px 15px; font-size:0.8rem; display:none; background: var(--velvet-gold); color: #000; font-weight: bold; box-shadow: 0 0 15px rgba(0,0,0,0.5); margin-bottom: env(safe-area-inset-bottom);">
        SKIP PROLOGUE <i class="fa-solid fa-forward"></i>
    </button>

    <script>
        // --- TRACKING HELPERS ---
        function track(action) {
            const data = new FormData();
            data.append('page', 'PROLOGUE_' + action);
            fetch('api_ping.php', { method: 'POST', body: data }).catch(e=>{});
        }

        // --- STORY DATA (Full Act 1-4 with Pronoun Fixes) ---
        const StoryEngine = {
            choices: { reachOut: 0, cautious: 0, withdraw: 0 },
            currentScene: 0,
            scenes: [
                // ACT ONE: THE LOSS
                {
                    type: 'narrative', actHeader: 'Act One', actTitle: 'The Loss',
                    content: [
                        "Grandma's funeral was on a Tuesday.",
                        "You had a math test that day, but your teacher said you could make it up whenever, offering you condolences with that tight, careful smile that people use when they were at a loss.",
                        "You were, too.",
                        "Grandma was the one who bought your first Persona game, purchased secondhand for your scratched up console. She didn’t understand the story—not the demons, the dungeons, the teenagers saving the world—but she'd sit next to you on the couch, anyway, nested together among the starchy pillows and too soft cushions.",
                        "<em>\"Tell me about this one,\"</em> she'd say, listening as you rambled with all the clumsy, unbridled eagerness of youth, and she'd nod like it was the most interesting thing she'd ever heard.",
                        "You missed those days dearly.",
                        "But you missed your grandma even more."
                    ]
                },
                {
                    type: 'narrative',
                    content: [
                        "After her funeral, everyone moved on faster than you expected.",
                        "Your parents had paperwork. Phone calls with lawyers. Boxes to sort. They'd disappear into Grandma's house for hours, then come home too tired to talk.",
                        "Your classmates gave you space. So much space. A wide, empty circle that nobody dared to cross.",
                        "You waited for someone to ask how you were doing. Really ask. Not the hallway <em>\"you okay?\"</em> that doesn't wait for an answer.",
                        "You waited, and time flew on by without you, moving somehow too fast and too slow."
                    ]
                },
                {
                    type: 'choice',
                    content: ["A week later, your friend asks if you want to hang out after school.", "You haven't talked much since the funeral."],
                    choices: [
                        { text: "\"Yeah. I'd really like that.\"", type: 'reachOut' },
                        { text: "\"Maybe. I'll let you know.\"", type: 'cautious' },
                        { text: "\"I have a lot of homework. Sorry.\"", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "You go. You sit at the coffee shop and she talks about a boy in her chemistry class. You nod. You smile. She never asks about your grandmother. When you get home, you feel more tired than before.",
                        cautious: "You text her later: <em>\"Can't today, maybe next week?\"</em> She sends a thumbs up. Next week never comes. Neither of you mention it again.",
                        withdraw: "She nods. <em>\"No worries!\"</em> She doesn't ask again. You tell yourself it's fine. You weren't in the mood anyway."
                    },
                    followUp: "That night, after rifling through dust and discs, you boot up your favorite Persona game. The characters are still there. They always are. They ask you to spend time with them, and when you do, it <em>means</em> something. The music plays. The bonds deepen. It's not real. You know it's not real. But the game’s the only place where showing up feels like it matters. These people feel like they care."
                },
                
                // ACT TWO: THE INVISIBLE PERSON
                {
                    type: 'narrative', actHeader: 'Act Two', actTitle: 'The Invisible Person',
                    content: [
                        "You've spent months learning how to be a person from the games: How to talk to people; how to ask questions; how to listen. The protagonists are always so <em>good</em> at it.",
                        "You study the textboxes like you're cramming for a test.",
                        "There's a gaming club. They meet on Thursdays after school.",
                        "All you have to do is try."
                    ]
                },
                {
                    type: 'choice',
                    content: ["You walk into the first meeting of the year. People are already mid-conversation, laughing about something you missed.", "No one looks up."],
                    choices: [
                        { text: "Introduce yourself to the group.", type: 'reachOut' },
                        { text: "Wait for a lull and try to join naturally.", type: 'cautious' },
                        { text: "Find a seat in the corner and listen.", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "<em>\"Hey, I'm—\"</em> Someone laughs at a joke you didn't hear. The conversation continues over you. You stand there for a moment, then sit down. No one asks your name.",
                        cautious: "You wait. The lull never comes. Eventually you sit down, hoping someone will notice the new face. No one does.",
                        withdraw: "You find a seat. You listen. You laugh when they laugh. When the meeting ends, someone says, <em>\"See you next week!\"</em> to the room. Not to you specifically. To the room."
                    },
                    followUp: "The next week, someone—a guy with headphones around his neck—nods at you when you walk in. Your heart lifts for a moment, gone stupid with hope. Just a nod. Nothing else. It's such a small thing, you think giddily. But maybe this week will be different?"
                },
                {
                    type: 'narrative',
                    content: [
                        "It isn't.",
                        "Even so, you keep trying. You attend for three weeks, then four. You learn everyone’s names by listening.",
                        "They don't learn yours.",
                        "One day, headphones guy mentions a Persona game—he's stuck on a boss, he says, and you <em>know</em> that boss; you've beaten it twice. Eagerly, you open your mouth to say something—",
                        "Someone else jumps in. Gives advice. The conversation moves on.",
                        "You close your mouth."
                    ]
                },
                {
                    type: 'choice',
                    content: ["After the meeting, headphones guy is packing up alone. This is your chance."],
                    choices: [
                        { text: "\"Hey—I actually know a trick for that boss. If you want.\"", type: 'reachOut' },
                        { text: "Hover nearby, hoping he'll start a conversation.", type: 'cautious' },
                        { text: "Leave. He probably doesn't want to talk anyway.", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "He looks up. <em>\"Oh, cool.\"</em> You explain the strategy: the best party members, the best teams. He nods. <em>\"Thanks.\"</em> Then he puts his headphones on and walks out. That's it. You helped. It didn't change anything.",
                        cautious: "You pretend to look for something in your bag. He finishes packing. <em>\"Later,\"</em> he says to the room—to no one—and leaves.",
                        withdraw: "You leave first. Fast. On the walk home, you imagine the conversation you could have had. It goes perfectly in your head. It always does."
                    },
                    followUp: "You stop going to the gaming club after that. You tell yourself it's because you're busy. It’s easier than the truth."
                },
                {
                    type: 'narrative',
                    content: [
                        "Senior year is more of the same.",
                        "You're not bullied. You're not hated. You're just... unnoticed. A background character in everyone else's story.",
                        "You eat lunch in the library. You do group projects alone when partners conveniently forget to include you.",
                        "Persona is still there, waiting in the wings of your bedroom. You replay the games. You know every dialogue option by heart now. You know exactly what to say to make the characters love you.",
                        "But why does it only work in the games?",
                        "That night, you open your journal—the one carefully hidden inside of a bag inside of a drawer—and write: <em>\"I keep trying to be the protagonist. But I think I'm just an NPC. Maybe I don't have a route.\"</em>"
                    ]
                },

                // ACT THREE: THE FALSE HOPE
                {
                    type: 'narrative', actHeader: 'Act Three', actTitle: 'The False Hope',
                    content: [
                        "Graduation comes. You walk across the stage. Your parents clap. You smile for the camera.",
                        "College will be different. Everyone says so. <em>\"You'll find your people,\"</em> your mom says. <em>\"It's a fresh start.\"</em>",
                        "Among the handful of schools that sent you acceptance letters, you choose CSULA. It’s close enough to home to feel safe. Far enough to feel new.",
                        "You imagine yourself walking across campus, finally visible. Finally <em>someone</em>.",
                        "Move-in day is hot. Your roommate seems nice. They help you carry boxes. You talk about hometowns, majors, favorite shows."
                    ]
                },
                {
                    type: 'choice',
                    content: ["Your roommate mentions they've never played Persona. <em>\"I've heard of it though. It's like... a Japanese thing, right?\"</em>"],
                    choices: [
                        { text: "\"It's amazing. I can show you sometime if you want?\"", type: 'reachOut' },
                        { text: "\"Yeah, it's a game series. Pretty niche.\"", type: 'cautious' },
                        { text: "\"Something like that.\" Change the subject.", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "They smile. <em>\"Maybe!\"</em> It sounds like a real maybe. Your chest feels warm. Maybe this time. Maybe here. Maybe them.",
                        cautious: "They nod. <em>\"Cool.\"</em> The conversation moves on. Normal. Fine. You don't push it.",
                        withdraw: "You've learned not to get your hopes up. People say <em>\"that's cool\"</em> about things they'll never think about again."
                    },
                    followUp: "That night, alone in your new bed in your new room, you feel something you haven't felt in years. Hope. Fragile. Terrifying. But there. You think: <em>This could be it. This could be where everything changes.</em>"
                },

                // ACT FOUR: THE BREAKING POINT
                {
                    type: 'narrative', actHeader: 'Act Four', actTitle: 'The Breaking Point',
                    content: [
                        "It doesn't get better.",
                        "Your roommate is nice. Polite. But they have their own friends—people they knew from high school, people from their major.",
                        "They're gone most nights. When they're there, they're on FaceTime with their boyfriend.",
                        "You eat alone in the dining hall. You pick a different table each time, like that makes it less obvious.",
                        "There's a gaming club here too. You think about going. You don't."
                    ]
                },
                {
                    type: 'narrative',
                    content: [
                        "One day, you force yourself.",
                        "The club is in a cramped room in the student union. Posters on the walls. The smell of energy drinks.",
                        "You recognize the energy—the jokes, the references, the shorthand.",
                        "These are your people. They should be your people.",
                        "You sit down. Someone glances at you. Looks away. The conversation doesn't pause."
                    ]
                },
                {
                    type: 'choice',
                    content: ["They're talking about Persona. Finally—something you know. Someone mentions they've never beaten the final boss of one of the games."],
                    choices: [
                        { text: "\"Oh, I can help with that—I've done it a few times.\"", type: 'reachOut' },
                        { text: "Wait for someone to ask the group for advice.", type: 'cautious' },
                        { text: "Stay quiet. You don't want to seem like a know-it-all.", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "You say it. Your voice comes out quieter than you meant. Someone else talks over you, offering their own advice. The person asking nods at them. You're not sure anyone heard you at all.",
                        cautious: "No one asks the group. They just keep talking. You had the answer. You couldn't find the door into the conversation.",
                        withdraw: "You stay silent. Safe. Invisible."
                    },
                    followUp: "You don't go back. The pattern is too familiar. You know how this story goes."
                },
                {
                    type: 'narrative',
                    content: [
                        "October arrives. Your birthday.",
                        "It’s your first birthday away from home. Away from the empty chair where Grandma used to sit. Away from everything familiar.",
                        "You mentioned your birthday to your roommate during move-in. Just casually.",
                        "\"October 15th,\" you said when they asked for your zodiac sign. They'd nodded. You remember because you thought: <em>Maybe they'll remember.</em>"
                    ]
                },
                {
                    type: 'choice',
                    content: ["It’s the morning of your birthday. Your roommate is getting ready for class. They haven't said anything."],
                    choices: [
                        { text: "\"Hey, um—it's actually my birthday today.\"", type: 'reachOut' },
                        { text: "Wait. Maybe they're planning something. Maybe they'll remember at lunch.", type: 'cautious' },
                        { text: "Say nothing. You don't want to guilt them into caring.", type: 'withdraw' }
                    ],
                    outcomes: {
                        reachOut: "They look up from their mirror. <em>\"Oh! Happy birthday!\"</em> A pause. <em>\"Do you have plans?\"</em> You don't. They nod sympathetically and leave for class. That's it. That's all you get.",
                        cautious: "You wait. Through morning. Through lunch. Through the afternoon. They never mention it. They didn't remember.",
                        withdraw: "You don't say anything. Neither do they. Why would they? It's not their job to remember."
                    },
                    followUp: null // No monologue for this scene
                },
                {
                    type: 'narrative',
                    content: [
                        "That night, you sit in your dorm room alone. Your roommate is out. Again.",
                        "Your phone is quiet. You keep waiting for a message, a buzz, a call, but there’s been nothing except an automated text from your bank: <em>\"Happy Birthday! Enjoy 5% off your next purchase.\"</em>",
                        "Late into the night, Mom calls. She sounds tired. <em>\"Happy birthday, honey. How's school?\"</em> You say fine.",
                        "She says she misses you. Three minutes later, she has to go. Work thing, she says.",
                        "You stare at your phone after she hangs up."
                    ]
                },
                {
                    type: 'narrative',
                    content: [
                        "You open your laptop. Your Persona folder. All of them—every game, every save file. Hundreds of hours of your life.",
                        "You could play. You could load a save and hear familiar voices—from the people who are always happy to see you.",
                        "Who remember your name. Who <em>choose</em> to spend time with you.",
                        "But you've finished them all. Multiple times. You know every line. Every scene. They can't say anything new.",
                        "The worlds that saved you have nothing more to give.",
                        "The silence when the credits end is always unbearable, but worse is the reflection you find on the pitch-black screen.",
                        "You can’t look that misery in the eyes.",
                        "Robotically, you start a new game."
                    ]
                },
                {
                    type: 'narrative',
                    content: [
                        "Something cracks.",
                        "You're so <em>tired</em>. Tired of trying. Tired of reaching out into the dark and finding nothing.",
                        "You’ve become nothing but a ghost in your own life, looking at your Grandma’s ashes for guidance.",
                        "(You hear whispers sometimes—memories, maybe—but more than anything, you just wish you had someone to talk to.)",
                        "You think about every attempt: every time you’ve introduced yourself. Every time you helped. Every time you showed up and hoped and waited and put yourself out there and <em>tried</em>.",
                        "And failed—",
                        "What was even the point?",
                        "You did everything right, or you did everything wrong, but nothing ever worked. The result was the same.",
                        "No matter what you did, you were invisible.",
                        "Alone.",
                        "Always, always, <em>always</em>."
                    ]
                },
                {
                    type: 'narrative',
                    content: [
                        "You're scrolling through your phone. Numb. Looking for nothing.",
                        "Eventually, you find an ad. A Persona fan event. Something about a festival at a tea bar in San Gabriel.",
                        "With Characters you love. People who understand. A place where everyone speaks your language.",
                        "<em>Factory Tea Bar.</em>",
                        "You look at the photos. Cosplayers. Smiles. People taking pictures together. A community."
                    ]
                },
                {
                    type: 'choice',
                    content: ["You could go. Maybe this time will be different."],
                    choices: [
                        { text: "\"I'll go. One more try.\"", type: 'reachOut' },
                        { text: "\"I don't know if I can handle another disappointment.\"", type: 'cautious' },
                        { text: "\"...Maybe I should just stay home tonight.\"", type: 'withdraw' }
                    ],
                    isGlitchTrigger: true,
                    outcomes: {} 
                },
                
                // GLITCH SEQUENCE
                { type: 'glitch' },
                { type: 'final' }
            ],

            getEndingText: function() {
                const { reachOut, cautious, withdraw } = this.choices;
                if (reachOut >= cautious && reachOut >= withdraw) return "You tried. Every time, you tried. You reached out. It still wasn't enough. You're so tired of reaching into the dark.";
                if (cautious >= reachOut && cautious >= withdraw) return "You waited. You hoped. You gave people chances to see you. They never did. You're so tired of waiting.";
                return "You protected yourself. You asked for so little. And even that was too much. You're so tired of being invisible.";
            }
        };

        // --- UI CONTROLLER ---
        const UI = {
            elements: {
                warning: document.getElementById('warning-screen'),
                viewport: document.getElementById('story-viewport'),
                content: document.getElementById('story-content'),
                blackScreen: document.getElementById('black-screen'),
                horrorFlash: document.getElementById('horror-flash'),
                scanlines: document.getElementById('scanlines'),
                staticOverlay: document.getElementById('static-overlay'),
                memoryFlash: document.getElementById('memory-flash'),
                skipBtn: document.getElementById('persistent-skip-btn')
            },

            init: function() {
                // Warning Screen Handlers
                document.getElementById('start-btn').addEventListener('click', () => {
                    // --- PLAY BGM WITH DELAY ---
                    setTimeout(() => {
                        const bgm = document.getElementById('bgm');
                        if(bgm) {
                            bgm.volume = 0.3; // Gentle volume
                            bgm.play().catch(e => console.error("Audio autoplay prevented", e));
                        }
                    }, 2000); // 2 second delay
                    // ----------------

                    track('START');
                    this.elements.warning.style.opacity = '0';
                    setTimeout(() => {
                        this.elements.warning.style.display = 'none';
                        this.elements.viewport.style.display = 'flex';
                        this.elements.skipBtn.style.display = 'block';
                        this.renderScene(0);
                    }, 1000);
                });

                document.getElementById('skip-btn').addEventListener('click', () => this.skip());
                this.elements.skipBtn.addEventListener('click', () => this.skip());
            },

            skip: function() {
                track('SKIPPED');
                window.location.href = 'contract.php?done=1';
            },

            renderScene: function(index) {
                const scene = StoryEngine.scenes[index];
                if (!scene) return;
                StoryEngine.currentScene = index;

                if (scene.type === 'glitch') { this.triggerGlitchSequence(); return; }
                if (scene.type === 'final') { this.renderFinalScreen(); return; }

                let html = '';
                if (scene.actHeader) {
                    html += `<div class="act-header">${scene.actHeader}</div><div class="act-title">${scene.actTitle}</div>`;
                }

                if (scene.content) {
                    scene.content.forEach((text, i) => {
                        html += `<p class="story-text delayed-${Math.min(i, 4)}">${text}</p>`;
                    });
                }

                if (scene.type === 'choice') {
                    html += '<div class="choices-container">';
                    scene.choices.forEach((choice, i) => {
                        html += `<button class="choice-button" data-index="${i}" data-type="${choice.type}">${choice.text}</button>`;
                    });
                    html += '</div>';
                } else if (scene.type === 'narrative') {
                    // CONTINUE BUTTON
                    html += `<button class="choice-button" id="continue-btn" style="margin-top:2rem; opacity:0; animation:fadeInUp 0.6s ease 1s forwards">Continue</button>`;
                }

                this.elements.content.innerHTML = html;
                this.elements.viewport.scrollTop = 0;

                // Bind Events
                if (scene.type === 'choice') {
                    document.querySelectorAll('.choice-button').forEach(btn => {
                        btn.addEventListener('click', (e) => this.handleChoice(e, scene));
                    });
                } else if (scene.type === 'narrative') {
                    document.getElementById('continue-btn').addEventListener('click', () => this.nextScene());
                }
            },

            nextScene: function() {
                this.elements.content.style.opacity = 0;
                setTimeout(() => {
                    this.renderScene(StoryEngine.currentScene + 1);
                    this.elements.content.style.opacity = 1;
                }, 300);
            },

            handleChoice: function(e, scene) {
                const type = e.target.dataset.type;
                StoryEngine.choices[type]++;

                if (scene.isGlitchTrigger) {
                    this.triggerGlitchSequence();
                } else {
                    const outcome = scene.outcomes[type];
                    const followUp = scene.followUp;
                    
                    let html = this.elements.content.innerHTML;
                    html = html.replace(/<div class="choices-container">[\s\S]*<\/div>/, '');
                    html += `<p class="story-text" style="color:var(--velvet-gold); border-left:2px solid var(--velvet-gold); padding-left:10px;">${outcome}</p>`;
                    if(followUp) html += `<p class="story-text">${followUp}</p>`;
                    
                    html += `<button class="choice-button" id="continue-btn" style="margin-top:2rem;">Continue</button>`;
                    this.elements.content.innerHTML = html;
                    document.getElementById('continue-btn').addEventListener('click', () => this.nextScene());
                }
            },

            // --- GLITCH SEQUENCE ---
            triggerGlitchSequence: function() {
                const buttons = document.querySelectorAll('.choice-button');
                
                // 1. First Flicker
                setTimeout(() => {
                    this.elements.horrorFlash.classList.add('active');
                    setTimeout(() => this.elements.horrorFlash.classList.remove('active'), 100);
                }, 500);

                // 2. Glitch Text
                setTimeout(() => {
                    buttons.forEach(btn => btn.classList.add('glitch-text'));
                }, 1000);

                // 3. Scramble
                setTimeout(() => {
                    this.scrambleButtons(buttons, 0);
                }, 1500);
            },

            scrambleButtons: function(buttons, iteration) {
                const chars = '█▓▒░╔╗╚╝║═┼┴┬├┤#?!';
                const targetText = "They'll see me.";

                if (iteration < 8) {
                    buttons.forEach(btn => {
                        let text = '';
                        for (let i = 0; i < 20; i++) text += chars[Math.floor(Math.random() * chars.length)];
                        btn.textContent = text;
                    });
                    if (iteration % 2 === 0) {
                        this.elements.horrorFlash.classList.add('active');
                        setTimeout(() => this.elements.horrorFlash.classList.remove('active'), 50);
                    }
                    setTimeout(() => this.scrambleButtons(buttons, iteration + 1), 150);
                } else {
                    // Final state
                    buttons.forEach(btn => {
                        btn.textContent = targetText;
                        btn.classList.remove('glitch-text');
                        btn.classList.add('glitch-choice');
                    });

                    // Match HTML behavior: Scanlines + Static (no red shaking)
                    this.elements.scanlines.classList.add('active');
                    this.elements.staticOverlay.classList.add('active');
                    setTimeout(() => this.glitchPhase2(), 1500);
                }
            },

            glitchPhase2: function() {
                const memories = ["\"Nobody remembered.\"", "\"Three minutes.\"", "\"Background character.\"", "\"Invisible.\""];
                let idx = 0;
                
                const flash = () => {
                    if (idx < memories.length) {
                        this.elements.memoryFlash.textContent = memories[idx];
                        this.elements.memoryFlash.classList.add('active');
                        setTimeout(() => this.elements.memoryFlash.classList.remove('active'), 400);
                        idx++;
                        setTimeout(flash, 500);
                    } else {
                        this.glitchPhase3();
                    }
                };
                flash();
            },

            glitchPhase3: function() {
                // Final Flicker
                this.elements.horrorFlash.classList.add('active');
                setTimeout(() => {
                    this.elements.horrorFlash.classList.remove('active');
                    // Fade to Black
                    this.elements.scanlines.classList.remove('active');
                    this.elements.staticOverlay.classList.remove('active');
                    this.elements.blackScreen.classList.add('active');
                    // Set viewport background to black for the finale text
                    this.elements.viewport.style.backgroundColor = '#000';
                    setTimeout(() => this.renderTypedSequence(), 1500);
                }, 200);
            },

            renderTypedSequence: function() {
                // Prevent multiple calls
                if (this._typingInProgress) return;
                this._typingInProgress = true;

                this.elements.skipBtn.style.display = 'none';
                this.elements.content.style.opacity = 1; // Ensure visible
                this.elements.content.innerHTML = `
                    <div style="min-height: 80vh; display: flex; flex-direction: column; justify-content: center; text-align: center; color: white;">
                        <div class="typed-container" id="typed-1"></div>
                        <div class="typed-container" id="typed-2" style="margin-top: 2rem;"></div>
                        <div class="typed-container" id="typed-3" style="margin-top: 3rem; color: var(--velvet-silver); font-size: 0.9rem;"></div>
                    </div>
                `;

                // Remove black screen to reveal the black viewport with text
                this.elements.blackScreen.classList.remove('active');

                const ending = StoryEngine.getEndingText();

                setTimeout(() => {
                    this.typeText('typed-1', "If people won't see me... then I'll make a world where they'll have no choice.", 50, () => {
                        setTimeout(() => {
                            this.typeText('typed-2', ending, 30, () => {
                                setTimeout(() => {
                                    this.typeText('typed-3', "You know this isn't right. But you're so tired of being invisible.", 40, () => {
                                        setTimeout(() => {
                                            this._typingInProgress = false;
                                            this.renderFinalScreen();
                                        }, 3000);
                                    });
                                }, 2000);
                            });
                        }, 2000);
                    });
                }, 1000);
            },

            typeText: function(id, text, speed, callback) {
                const el = document.getElementById(id);
                let i = 0;
                el.innerHTML = '<span class="typed-text"></span><span class="cursor"></span>';
                const span = el.querySelector('.typed-text');
                
                function type() {
                    if (i < text.length) {
                        span.textContent += text.charAt(i);
                        i++;
                        setTimeout(type, speed);
                    } else {
                        el.querySelector('.cursor').remove();
                        if (callback) callback();
                    }
                }
                type();
            },

            renderFinalScreen: function() {
                // Prevent multiple calls
                if (this._finalScreenRendered) return;
                this._finalScreenRendered = true;

                track('FINISHED');

                // Fade out current content smoothly
                this.elements.content.style.opacity = 0;

                setTimeout(() => {
                    // --- NEW DATE FORMATTER LOGIC ---
                    const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                    const d = new Date();
                    let month = months[d.getMonth()];
                    let day = d.getDate();
                    let hour = d.getHours();
                    let min = d.getMinutes();
                    let ampm = hour >= 12 ? 'PM' : 'AM';
                    hour = hour % 12;
                    hour = hour ? hour : 12;
                    min = min < 10 ? '0'+min : min;
                    const dateString = month + ' ' + day + ', ' + hour + ':' + min + ' ' + ampm;
                    // --------------------------------

                    this.elements.content.innerHTML = `
                        <div class="final-screen" style="min-height: 80vh; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                            <div class="location" style="opacity: 0; animation: fadeInUp 0.6s ease 0.5s forwards;">SAN GABRIEL, CA</div>

                            <div class="venue" style="opacity: 0; animation: fadeInUp 0.6s ease 0.7s forwards;">
                                Factory Tea Bar<br>
                                <span style="font-size: 1rem; color: #aaa;">${dateString}</span>
                            </div>
                            <div class="scene-break" style="opacity: 0; animation: fadeInUp 0.6s ease 0.9s forwards;">◈</div>
                            <p class="story-text" style="text-align: center; opacity: 0; animation: fadeInUp 0.6s ease 1.1s forwards;">
                                You're no longer THEM.
                            </p>
                            <p class="story-text" style="text-align: center; opacity: 0; animation: fadeInUp 0.6s ease 1.4s forwards;">
                                You're back to yourself—standing outside, ticket in hand.
                            </p>
                            <p class="story-text" style="text-align: center; opacity: 0; animation: fadeInUp 0.6s ease 1.7s forwards;">
                                The festival awaits. The music is playing.
                            </p>
                            <p class="story-text" style="text-align: center; opacity: 0; animation: fadeInUp 0.6s ease 2s forwards;">
                                And somewhere inside—
                            </p>
                            <p class="waiting" style="text-align: center; margin-top: 2rem; opacity: 0; animation: fadeInUp 0.6s ease 2.5s forwards; color: var(--velvet-gold); font-size: 1.3rem;">
                                THEY'RE waiting for you.
                            </p>
                            <br><br>
                            <a href="contract.php?done=1" class="btn-enter">
                                ENTER THE VELVET ROOM
                            </a>
                        </div>
                    `;
                    this.elements.viewport.scrollTop = 0;

                    // Fade content back in
                    this.elements.content.style.opacity = 1;
                }, 500);
            }
        };

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            UI.init();
        });
    </script>
</body>
</html>