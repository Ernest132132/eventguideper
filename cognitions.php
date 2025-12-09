<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fixed Data Set for Game Blurbs
$games = [
    [
        'title' => 'PERSONA 3',
        'subtitle' => 'The Dark Hour',
        'image_file' => 'p3.jpg', 
        'blurb' => 'As more and more people get affected by a mysterious "Apathy Syndrome" that takes people\'s wills to live, the afterschool club "S.E.E.S." take it upon themselves to eliminate the shadows causing it. During a hidden time slot between one day and the next called the Dark Hour, these students use their personas to help those awaiting their deaths to live life again.'
    ],
    [
        'title' => 'PERSONA 4',
        'subtitle' => 'The Midnight Channel',
        'image_file' => 'p4.jpg', 
        'blurb' => 'When a mysterious fog and a series of bizarre murders grip a rural town, a group of high school students discovers they can enter a world broadcast on a foggy television channel. They use their Personas to save victims trapped in the Midnight Channel and hunt for the killer.'
    ],
    [
        'title' => 'PERSONA 5',
        'subtitle' => 'The Metaverse',
        'image_file' => 'p5.jpg', 
        'blurb' => 'A group of students outcasted by society awaken to their personas after learning how to rebel against those who take advantage of the innocent, forming the secret vigilante group of The Phantom Thieves of Hearts. They explore the Metaverse, a supernatural realm representing humanity\'s subconscious, to change the hearts of corrupt adults and force them into realizing the error of their ways.'
    ],
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>About Persona | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=39">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- Enhanced Game Card Style --- */
        .game-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--velvet-gold);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            flex-direction: column; /* Default: Stacked (Mobile) */
            align-items: center;
            gap: 20px;
            text-align: left;
            transition: transform 0.2s, background 0.2s;
        }

        .game-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }

        .game-card img {
            width: 100%;
            max-width: 160px; /* Limit size */
            height: auto;
            border-radius: 4px;
            border: 2px solid var(--velvet-gold-light);
            box-shadow: 0 4px 12px rgba(0,0,0,0.6);
            flex-shrink: 0;
        }

        .game-blurb {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .game-blurb h3 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 1.4rem;
            color: var(--velvet-gold);
            font-family: 'Cinzel', serif;
        }

        .game-blurb p {
            font-size: 0.95rem;
            line-height: 1.6;
            color: #e0e0e0;
            margin-top: 10px;
        }

        /* Desktop Layout: Side-by-Side */
        @media (min-width: 600px) {
            .game-card {
                flex-direction: row;
                align-items: flex-start;
            }
            .game-card img {
                margin-bottom: 0;
            }
        }

        /* Regal Intro Box */
        .regal-intro {
            background: rgba(10, 5, 30, 0.6);
            border: 2px solid var(--velvet-gold);
            padding: 25px;
            margin-bottom: 40px;
            border-radius: 4px;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
            text-align: center;
        }
        .regal-intro h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.6rem;
            text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
        }
        .regal-intro p {
            font-size: 1rem;
            line-height: 1.8;
            color: #f0f0f0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="fabric-container">
        <div class="fabric-wave"></div>
        <div class="fabric-wave"></div>
    </div>
    <div class="fog-container">
        <div class="fog-layer"></div>
        <div class="fog-layer"></div>
    </div>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h1 style="font-size: 1.8rem;">ABOUT PERSONA</h1>
            <p style="font-size: 0.8rem; letter-spacing: 2px; color: var(--velvet-gold);">MEMOIRS OF THE INMATES</p>
        </div>

        <div class="regal-intro">
            <h2>WHAT IS PERSONA?</h2>
            <p>
                Persona is a JRPG series by Atlus that blends dungeon-crawling combat with social simulation, where players balance everyday life (school, relationships, part-time jobs) with supernatural adventures. The core concept revolves around "Personas" - manifestations of one's inner psyche that characters summon to fight Shadows, typically tied to Jungian psychology and tarot archetypes. The modern entries (Persona 3-5) are particularly beloved for their stylish aesthetics, memorable characters, and themes exploring identity, rebellion, and confronting societal/personal darkness.
            </p>
        </div>

        <?php foreach ($games as $game): ?>
            <div class="game-card">
                <img src="assets/images/<?php echo htmlspecialchars($game['image_file']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?> Box Art">
                
                <div class="game-blurb">
                    <h3><?php echo htmlspecialchars($game['title']); ?></h3>
                    <p style="font-size: 0.8rem; color: var(--velvet-red); margin: 0; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;">
                        <?php echo htmlspecialchars($game['subtitle']); ?>
                    </p>
                    <p><?php echo htmlspecialchars($game['blurb']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to dashboard
        </a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a');
            const container = document.querySelector('.container');

            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.hostname === window.location.hostname && this.getAttribute('target') !== '_blank') {
                        e.preventDefault(); 
                        const href = this.getAttribute('href');
                        
                        if(container) {
                            container.classList.remove('page-visible');
                            container.classList.add('page-exit');
                        }
                        
                        setTimeout(() => {
                            window.location.href = href;
                        }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>