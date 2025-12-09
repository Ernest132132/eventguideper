<?php
session_start();
require 'db.php'; // Kept for session check/db connection if needed elsewhere

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// HARDCODED DATA: PERFORMERS
$performers = [
    'PERSONA 3' => [
        [
            'name' => 'Kirisakii',
            'character' => 'Makoto Yuki',
            'handle' => 'kirisakii__',
            'desc' => 'The blue-haired leader of S.E.E.S. who fights during the Dark Hour to uncover the mystery of Tartarus.',
            'image' => 'makoto2.jpg' 
        ],
        [
            'name' => 'Ashten',
            'character' => 'Aigis',
            'handle' => 'ashtenpassion',
            'desc' => 'An anti-shadow suppression weapon with a human heart, sworn to protect her precious friends.',
            'image' => 'Aigis.jpg'
        ],
        [
            'name' => 'krylixi',
            'character' => 'Yukari Takeba',
            'handle' => 'krylixi',
            'desc' => 'A popular archer and S.E.E.S. member who hides her resolve beneath a cheerful exterior.',
            'image' => 'Yukari.jpg'
        ]
    ],
    'PERSONA 4' => [
        [
            'name' => 'Serii',
            'character' => 'Yu Narukami',
            'handle' => 'seriiluna',
            'desc' => 'The silver-haired transfer student who leads the Investigation Team into the TV world.',
            'image' => 'Yu.jpg'
        ],
        [
            'name' => 'Eru',
            'character' => 'Yosuke Hanamura',
            'handle' => 'princess.eru',
            'desc' => 'The clumsy but loyal partner of the Investigation Team who commands the wind.',
            'image' => 'Yosuke.jpg'
        ],
        [
            'name' => 'Grimoireal',
            'character' => 'Yukiko Amagi',
            'handle' => 'grimoireal',
            'desc' => 'The elegant heir to the Amagi Inn who wields fire and fans with deadly grace.',
            'image' => 'Yukiko.jpg'
        ],
        [
            'name' => 'Aokka',
            'character' => 'Chie Satonaka',
            'handle' => 'aokka',
            'desc' => 'A kung-fu loving meat enthusiast who kicks shadows into oblivion.',
            'image' => 'chie.jpg'
        ]
    ],
    'PERSONA 5' => [
        [
            'name' => 'Wren',
            'character' => 'Ren Amamiya (Joker)',
            'handle' => 'bakucos__',
            'desc' => 'The leader of the Phantom Thieves who steals distorted desires to reform society.',
            'image' => 'Ren.jpg'
        ],
        [
            'name' => 'Deftknot',
            'character' => 'Ryuji Sakamoto (Skull)',
            'handle' => 'deftknot_',
            'desc' => 'A rebellious former track star who smashes through obstacles with electric fury.',
            'image' => 'Ryuji.jpg'
        ],
        [
            'name' => 'Kir',
            'character' => 'Ann Takamaki (Panther)',
            'handle' => 'kir_wuff',
            'desc' => 'A stunning model and thief who burns away corruption with her whip and fire.',
            'image' => 'Ann.jpg'
        ],
        [
            'name' => 'Cian',
            'character' => 'Haru Okumura (Noir)',
            'handle' => 'hyliancream',
            'desc' => 'The gentle gardener who hides a grenade launcher and a sadistic streak for justice.',
            'image' => 'Haru.jpg'
        ],
        [
            'name' => 'Raikai',
            'character' => 'Makoto Niijima (Queen)',
            'handle' => 'raikai_cos',
            'desc' => 'The student council president turned strategist who rides a motorcycle into battle.',
            'image' => 'Makoto.jpg'
        ],
        [
            'name' => 'Clover',
            'character' => 'Futaba Sakura (Oracle)',
            'handle' => 'cloverleaf.clemency',
            'desc' => 'The genius hacker and navigator who guides the thieves from the shadows.',
            'image' => 'Futaba.jpg'
        ],
        [
            'name' => 'Reagan',
            'character' => 'Goro Akechi (Crow)',
            'handle' => 'mantareagan',
            'desc' => 'The charismatic detective prince whose keen intellect rivals even the leader of the Thieves.',
            'image' => 'Akechi.jpg'
        ],
        [
            'name' => 'Dawn',
            'character' => 'Kasumi Yoshizawa (Violet)',
            'handle' => 'dawnscosplay',
            'desc' => 'A talented gymnast who awakens to her rebellious spirit to chase her true dreams.',
            'image' => 'Kasumi.jpg'
        ]
    ]
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Performers | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=24"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .category-header {
            color: var(--velvet-gold-light);
            border-bottom: 1px solid var(--velvet-gold);
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-align: left;
        }
        .confidant-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--velvet-accent-blue);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            flex-direction: column; /* Mobile: Stack Vertically */
            text-align: left;
            gap: 20px;
            align-items: center; /* Center content on mobile */
        }
        
        /* Larger screens: Side by side */
        @media (min-width: 600px) {
            .confidant-card {
                flex-direction: row;
                align-items: flex-start; /* Align top */
                text-align: left;
            }
        }

        .c-image-container {
            width: 100%;
            max-width: 200px; /* Wider container for portrait */
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        /* UPDATED: Portrait Style */
        .c-image {
            width: 100%;
            aspect-ratio: 3 / 4; /* Portrait Ratio */
            background-color: #333; 
            border: 2px solid var(--velvet-gold);
            border-radius: 8px; /* Soft edges, not circle */
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.8rem;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        
        .c-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .c-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            width: 100%; /* Ensure text takes full width */
        }

        .c-role {
            color: var(--velvet-gold);
            font-weight: bold;
            font-size: 1.3rem; /* Slightly larger title */
            margin-bottom: 5px;
            font-family: 'Cinzel', serif;
        }
        .c-role a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }
        .c-role a:hover {
            color: var(--velvet-gold-light);
            text-decoration: underline;
        }

        .c-name {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 15px;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 5px;
            display: inline-block;
        }
        .c-name a {
            color: inherit;
            text-decoration: underline;
        }
        
        .c-desc {
            color: #ddd;
            font-size: 0.95rem;
            font-style: normal; /* Removed italics for better readability */
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        /* New Button Style for Instagram Link */
        .c-link {
            color: white;
            background: #E1306C; /* Insta Brand Color */
            text-decoration: none;
            font-size: 0.9rem;
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: bold;
            transition: 0.2s;
        }
        .c-link:hover {
            background: #C11050;
            box-shadow: 0 0 10px #E1306C;
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
            <h1 style="font-size: 1.8rem;">PERFORMERS</h1>
            <p style="font-size: 0.8rem;">Special guests who may or may not be as they seem.</p>
        </div>

        <?php foreach ($performers as $category => $list): ?>
            <h2 class="category-header"><?php echo $category; ?></h2>
            
            <?php foreach ($list as $p): ?>
                <?php 
                    // Tracking URL logic included
                    $trackUrl = "api_redirect.php?name=" . urlencode($p['character']) . "&dest=" . urlencode("https://instagram.com/" . $p['handle']); 
                ?>
                <div class="confidant-card">
                    
                    <div class="c-image-container">
                        <div class="c-image">
                            <?php if (file_exists("assets/images/" . $p['image'])): ?>
                                <img src="assets/images/<?php echo $p['image']; ?>" alt="<?php echo $p['character']; ?>">
                            <?php else: ?>
                                <i class="fa-solid fa-user-secret fa-2x"></i>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="c-info">
                        <div class="c-role">
                            <a href="<?php echo $trackUrl; ?>" target="_blank">
                                <?php echo $p['character']; ?>
                            </a>
                        </div>
                        
                        <div class="c-name">
                            Portrayed by <a href="<?php echo $trackUrl; ?>" target="_blank"><?php echo $p['name']; ?></a>
                        </div>
                        
                        <div class="c-desc"><?php echo $p['desc']; ?></div>
                        
                        <a href="<?php echo $trackUrl; ?>" target="_blank" class="c-link">
                            <i class="fa-brands fa-instagram"></i> Follow on Instagram
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endforeach; ?>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
        </a>
    </div>
    <div class="fabric-container">
        <div class="fabric-wave"></div>
        <div class="fabric-wave"></div>
    </div>
    <div class="fog-container">
        <div class="fog-layer"></div>
        <div class="fog-layer"></div>
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
                        setTimeout(() => { window.location.href = href; }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>