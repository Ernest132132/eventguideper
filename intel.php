<?php
session_start();
require 'db.php'; // Kept for session check

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// HARDCODED DATA: VENDORS
$all_vendors = [
    [
        'name' => 'Sams Wares',
        'instagram_handle' => 'samswares',
        'description' => 'Whimsical illustrations, custom wares, and character art.',
        'icon_image' => 'samswares.jpeg',
        'booth_image' => null
    ],
    [
        'name' => 'Scoot Does Art',
        'instagram_handle' => 'scootdoesart',
        'description' => 'Vibrant digital art, stickers, and fan merchandise.',
        'icon_image' => 'scootdoesart.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Artpudding',
        'instagram_handle' => 'Artpudding_',
        'description' => 'Sweet and soft art style prints and stationery.',
        'icon_image' => 'artpudding.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'The Wrabbit Hole',
        'instagram_handle' => 'thewrabbithole',
        'description' => 'Fantasy inspired art, prints, and magical goods.',
        'icon_image' => 'thewrabbithole.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Eelsilog',
        'instagram_handle' => 'eelsilog',
        'description' => 'Original character art, fan works, and charming merch.',
        'icon_image' => 'eelsilog.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Marp',
        'instagram_handle' => 'Marpaparp',
        'description' => 'Playful character illustrations and colorful accessories.',
        'icon_image' => 'marpaparp.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Pookerluffs',
        'instagram_handle' => 'pookerluffs',
        'description' => 'Fluffy, cute, and cozy art prints and stickers.',
        'icon_image' => 'pookerluffs.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Handsome',
        'instagram_handle' => 'Handsomecloset_official',
        'description' => 'Stylish apparel, fashion accessories, and closet essentials.',
        'icon_image' => 'handsomecloset_official.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Lemon N\' Lime Shop',
        'instagram_handle' => 'lemonnlimeshop',
        'description' => 'Zesty and fresh art designs, stickers, and charms.',
        'icon_image' => 'lemonlimeshop.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Elusive Lisa',
        'instagram_handle' => 'elusivelisa',
        'description' => 'Unique art pieces and creative visual works.',
        'icon_image' => 'elusivelisa.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Rrocktype',
        'instagram_handle' => 'rrocktype',
        'description' => 'Bold character designs and dynamic art prints.',
        'icon_image' => 'rrocktype.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Mochijam',
        'instagram_handle' => 'mochijam',
        'description' => 'Mochi-themed cute goods, keychains, and stationery.',
        'icon_image' => 'mochijam.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Babylonholic',
        'instagram_handle' => 'babylonholic',
        'description' => 'Eclectic and detailed illustrations and fan art.',
        'icon_image' => 'babylonholic.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'Deltamiyo',
        'instagram_handle' => 'deltamiyo',
        'description' => 'High-energy character art and vibrant prints.',
        'icon_image' => 'deltamiyo.jpeg', 
        'booth_image' => null
    ],
    [
        'name' => 'K1rmizi',
        'instagram_handle' => 'k1rmizi',
        'description' => 'Striking art style with a focus on bold colors and composition.',
        'icon_image' => 'kirmizi.jpeg', 
        'booth_image' => null
    ]
];

// Sort alphabetically by name
usort($all_vendors, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendors | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=12">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Shared Styles from Confidants */
        .vendor-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--velvet-accent-blue);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column; /* Default stack for mobile */
            text-align: left;
            gap: 15px;
            position: relative; /* For status icon */
        }
        
        /* Larger screens: Side by side */
        @media (min-width: 600px) {
            .vendor-card {
                flex-direction: row;
                align-items: center;
            }
        }

        .v-image-container {
            width: 100%;
            max-width: 120px; /* Limit image width on desktop */
            flex-shrink: 0;
            display: flex;
            justify-content: center; /* Center image in its container */
            align-items: flex-start;
        }
        
        /* The Image Placeholder */
        .v-image {
            width: 100px;
            height: 100px;
            background-color: #333; /* Dark grey placeholder */
            border: 2px solid var(--velvet-gold);
            border-radius: 50%; /* Circle shape */
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.8rem;
            overflow: hidden;
        }
        
        .v-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .v-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .v-name {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: var(--velvet-gold);
            margin: 0;
            line-height: 1.2;
            font-weight: bold;
        }

        .v-handle {
            font-size: 0.85rem;
            color: #E1306C; /* Instagram Pink */
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 5px;
        }
        .v-handle:hover { text-decoration: underline; color: white; }

        .v-desc {
            font-size: 0.9rem;
            color: #ccc;
            line-height: 1.4;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .booth-status {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
            color: #00d26a;
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
            <h1 style="font-size: 1.8rem;">VENDORS</h1>
            <p style="font-size: 0.8rem;">On-site Merchants to suit all of your commercial needs.</p>
        </div>

        <?php if (count($all_vendors) == 0): ?>
            <p>No intel gathered yet.</p>
        <?php endif; ?>

        <?php foreach ($all_vendors as $v): ?>
            <div class="vendor-card">
                
                <div class="v-image-container">
                    <div class="v-image">
                        <?php if (isset($v['icon_image']) && file_exists("assets/images/" . $v['icon_image'])): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($v['icon_image']); ?>" alt="Icon">
                        <?php else: ?>
                            <i class="fa-solid fa-shop fa-2x" style="color: var(--velvet-gold);"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="v-info">
                    <div class="v-name"><?php echo htmlspecialchars($v['name']); ?></div>
                    
                    <?php if (!empty($v['instagram_handle'])): ?>
                        <a href="https://instagram.com/<?php echo htmlspecialchars($v['instagram_handle']); ?>" target="_blank" class="v-handle">
                            <i class="fa-brands fa-instagram"></i> @<?php echo htmlspecialchars($v['instagram_handle']); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($v['description'])): ?>
                        <div class="v-desc">
                            <?php echo nl2br(htmlspecialchars($v['description'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($v['booth_image']) && $v['booth_image']): ?>
                    <div class="booth-status" title="Booth Photo Captured">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to dashboard
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
                    // Check if it's an internal link AND not opening in a new tab
                    if (this.hostname === window.location.hostname && this.getAttribute('target') !== '_blank') {
                        e.preventDefault(); // Stop immediate load
                        const href = this.getAttribute('href');
                        
                        // Add the Exit Animation Class
                        if(container) {
                            container.classList.remove('page-visible');
                            container.classList.add('page-exit');
                        }
                        
                        // Wait 480ms for animation to finish, then go
                        setTimeout(() => {
                            window.location.href = href;
                        }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>