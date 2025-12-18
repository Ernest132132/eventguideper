<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// TEMPLATE DATA: PERFORMERS
// Customize this array with your event's performers
// Organize by category (e.g., by role, time slot, location, etc.)
$performers = [
    'CATEGORY 1' => [
        [
            'name' => 'Performer Name',
            'character' => 'Character/Role Name',
            'handle' => 'instagram_handle',
            'desc' => 'Description of the performer or character they portray.',
            'image' => 'placeholder.jpg' // Place images in assets/images/
        ],
        // Add more performers in this category...
    ],
    'CATEGORY 2' => [
        [
            'name' => 'Another Performer',
            'character' => 'Another Character',
            'handle' => 'another_handle',
            'desc' => 'Another description here.',
            'image' => 'placeholder.jpg'
        ],
        // Add more performers...
    ],
    // Add more categories as needed...
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Performers | Event Guide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=24">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .category-header {
            color: var(--accent-gold-light);
            border-bottom: 1px solid var(--accent-gold);
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-align: left;
        }
        .confidant-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--accent-blue);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            text-align: left;
            gap: 20px;
            align-items: center;
        }

        @media (min-width: 600px) {
            .confidant-card {
                flex-direction: row;
                align-items: flex-start;
                text-align: left;
            }
        }

        .c-image-container {
            width: 100%;
            max-width: 200px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .c-image {
            width: 100%;
            aspect-ratio: 3 / 4;
            background-color: #333;
            border: 2px solid var(--accent-gold);
            border-radius: 8px;
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
            width: 100%;
        }

        .c-role {
            color: var(--accent-gold);
            font-weight: bold;
            font-size: 1.3rem;
            margin-bottom: 5px;
            font-family: 'Cinzel', serif;
        }
        .c-role a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s;
        }
        .c-role a:hover {
            color: var(--accent-gold-light);
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
            font-style: normal;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .c-link {
            color: white;
            background: #E1306C;
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
            <p style="font-size: 0.8rem;">Meet our special guests at the event.</p>
        </div>

        <?php foreach ($performers as $category => $list): ?>
            <h2 class="category-header"><?php echo $category; ?></h2>

            <?php foreach ($list as $p): ?>
                <?php
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
