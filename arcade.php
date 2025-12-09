<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
// FETCH STATUS TO BLOCK PENDING USERS
$stmt = $pdo->prepare("SELECT status FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$status = $stmt->fetchColumn();

if ($status === 'pending') {
    header("Location: contract.php");
    exit();
}
// The game file name (Renamed from placeholder to match the Mementos Infiltration game)
$p5GameFile = 'game_p5.php'; 
?>
<!DOCTYPE html>
<html>
<head>
    <title>The Arcade | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=70">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .game-select-card {
            background: rgba(0, 0, 0, 0.6);
            border: 2px solid var(--velvet-gold);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .game-select-card:hover {
            transform: scale(1.02);
            box-shadow: 0 0 20px var(--velvet-gold);
        }
        
        /* THEME HOVER COLORS */
        .theme-p3:hover { border-color: #0066cc; box-shadow: 0 0 20px #0066cc; }
        .theme-p4:hover { border-color: #ffd700; box-shadow: 0 0 20px #ffd700; }
        .theme-p5:hover { border-color: #e60012; box-shadow: 0 0 20px #e60012; } /* Updated to Match P5 Red */

        .game-title {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            margin: 0;
            text-transform: uppercase;
        }
        .game-desc {
            font-size: 0.9rem;
            color: #ccc;
            margin-top: 5px;
        }
        .play-btn {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h1 style="font-size: 1.8rem;">THE ARCADE</h1>
            <p style="font-size: 0.8rem;">CHALLENGE YOUR COGNITION</p>
        </div>

        <div class="game-select-card theme-p3" onclick="window.location.href='game_p3.php'">
            <i class="fa-solid fa-play play-btn" style="color: #0066cc;"></i>
            <h2 class="game-title" style="color: #0066cc;">TARTARUS ASCENT</h2>
            <p class="game-desc">Reflex Challenge. Strike the shadows during the Dark Hour.</p>
        </div>

        <div class="game-select-card theme-p4" onclick="window.location.href='game_p4.php'">
            <i class="fa-solid fa-play play-btn" style="color: #ffd700;"></i>
            <h2 class="game-title" style="color: #ffd700;">MIDNIGHT CHANNEL</h2>
            <p class="game-desc">Observation Challenge. Find the lost souls in the fog.</p>
        </div>
        
        <div class="game-select-card theme-p5" onclick="window.location.href='<?php echo $p5GameFile; ?>'">
            <i class="fa-solid fa-play play-btn" style="color: #e60012;"></i>
            <h2 class="game-title" style="color: #e60012;">PHANTOM INFILTRATION</h2>
            <p class="game-desc">Memory Challenge. Test your skill with the Phantom Thieves.</p>
        </div>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
        </a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.container');
            const cards = document.querySelectorAll('.game-select-card');
            
            cards.forEach(card => {
                card.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Get the href from the onclick attribute for transition
                    const href = this.getAttribute('onclick').match(/window\.location\.href='(.*?)'/)[1];
                    if(container) {
                        container.classList.remove('page-visible');
                        container.classList.add('page-exit');
                    }
                    setTimeout(() => { window.location.href = href; }, 480);
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>