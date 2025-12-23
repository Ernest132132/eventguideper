<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// LANTERN RITE VENDOR DATA
// Customize with your event's vendors
$all_vendors = [
    [
        'name' => 'Wanmin Restaurant',
        'instagram_handle' => 'vendor_handle',
        'description' => 'Delicious Liyue cuisine! Try our famous Jueyun Chili Chicken.',
        'icon_image' => 'placeholder.jpg',
        'booth_image' => null
    ],
    [
        'name' => 'Granny Shan\'s Toys',
        'instagram_handle' => 'another_vendor',
        'description' => 'Traditional Xiao Lanterns and festival toys.',
        'icon_image' => 'placeholder.jpg',
        'booth_image' => null
    ],
    [
        'name' => 'Xigu Antiques',
        'instagram_handle' => 'vendor_handle',
        'description' => 'Fine antiques, Rex Lapis figurines, and Liyue memorabilia.',
        'icon_image' => 'placeholder.jpg',
        'booth_image' => null
    ],
    [
        'name' => 'Bubu Pharmacy',
        'instagram_handle' => 'vendor_handle',
        'description' => 'Traditional medicine and health items.',
        'icon_image' => 'placeholder.jpg',
        'booth_image' => null
    ],
    // Add more vendors as needed...
];

// Sort alphabetically by name
usort($all_vendors, function($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vendors | Lantern Rite 2025</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=12">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .vendor-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--accent-blue);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            text-align: left;
            gap: 15px;
            position: relative;
        }

        @media (min-width: 600px) {
            .vendor-card {
                flex-direction: row;
                align-items: center;
            }
        }

        .v-image-container {
            width: 100%;
            max-width: 120px;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .v-image {
            width: 100px;
            height: 100px;
            background-color: #333;
            border: 2px solid var(--accent-gold);
            border-radius: 50%;
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
            color: var(--accent-gold);
            margin: 0;
            line-height: 1.2;
            font-weight: bold;
        }

        .v-handle {
            font-size: 0.85rem;
            color: #E1306C;
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
            <p style="font-size: 0.8rem;">On-site merchants at the event.</p>
        </div>

        <?php if (count($all_vendors) == 0): ?>
            <p>No vendors listed yet.</p>
        <?php endif; ?>

        <?php foreach ($all_vendors as $v): ?>
            <div class="vendor-card">

                <div class="v-image-container">
                    <div class="v-image">
                        <?php if (isset($v['icon_image']) && file_exists("assets/images/" . $v['icon_image'])): ?>
                            <img src="assets/images/<?php echo htmlspecialchars($v['icon_image']); ?>" alt="Icon">
                        <?php else: ?>
                            <i class="fa-solid fa-shop fa-2x" style="color: var(--accent-gold);"></i>
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
