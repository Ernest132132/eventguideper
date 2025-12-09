<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

// SECURITY CHECK: Reload user status to ensure they are an operative
$stmt = $pdo->prepare("SELECT status, role FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Redirect if not logged in or if status is pending (Contract not signed)
if (!$user || $user['status'] === 'pending') {
    header("Location: contract.php");
    exit();
}

// Redirect if Observer (Operatives only)
if ($user['role'] === 'observer') {
    header("Location: home.php");
    exit();
}

// Data Structure for Steps
$steps = [
    [
        'img' => 'assets/images/koro1.jpg', // UPDATED PATH
        'char' => 'Junpei Iori',
        'color' => '#f5a623', 
        'quote' => "Dude, those little red eyes are killing me, Koro-chan's already lookin' cuter than I ever will!"
    ],
    [
        'img' => 'assets/images/koro2.jpg', // UPDATED PATH
        'char' => 'Mitsuru Kirijo',
        'color' => '#c00000', 
        'quote' => "Excellent. The placement must be symmetrical; Koromaru deserves nothing less than perfection."
    ],
    [
        'img' => 'assets/images/koro3.jpg', // UPDATED PATH
        'char' => 'Akihiko Sanada',
        'color' => '#cccccc', 
        'quote' => "Stuffing it firm gives it structure. Same principle as building muscle, really."
    ],
    [
        'img' => 'assets/images/koro4.jpg', // UPDATED PATH
        'char' => 'Fuuka Yamagishi',
        'color' => '#00a19d', 
        'quote' => "His collar is what makes him one of us... I'm glad you included it!"
    ],
    [
        'img' => 'assets/images/koro5.jpg', // UPDATED PATH
        'char' => 'Koromaru',
        'color' => '#ffffff', 
        'quote' => "Arf!"
    ]
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feltcraft | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=34">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .step-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--velvet-gold);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .step-img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            border: 2px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            margin-bottom: 15px;
        }
        .dialogue-box {
            background: rgba(0, 0, 0, 0.8);
            border-left: 4px solid var(--velvet-gold);
            padding: 10px 15px;
            text-align: left;
            position: relative;
        }
        .char-name {
            font-family: 'Cinzel', serif;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }
        .quote-text {
            font-family: 'Lato', sans-serif;
            font-style: italic;
            color: #e0e0e0;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        .quote-text::before { content: '"'; }
        .quote-text::after { content: '"'; }
    </style>
</head>
<body>
    
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h1 style="font-size: 1.8rem;">FELTCRAFT</h1>
            <p style="font-size: 0.8rem;">A TEST OF PROFICIENCY</p>
        </div>

        <div class="card" style="margin-bottom: 25px; text-align: left;">
            <p style="color: var(--velvet-gold-light); font-style: italic; text-align: center; margin-bottom: 0;">
                "Welcome to the Atelier of the Soul. Here, you shall weave bonds not just with others, but with the materials themselves. <br><br>
                Demonstrate your Diligence and Proficiency by constructing the form of the loyal beast who guards our hours."
            </p>
        </div>

        <?php foreach ($steps as $index => $step): ?>
            <div class="step-card">
                <h3 style="color: white; margin-top: 0; margin-bottom: 10px; font-size: 1rem; border-bottom: 1px solid #444; padding-bottom: 5px;">
                    STEP <?php echo $index + 1; ?>
                </h3>
                
                <img src="<?php echo htmlspecialchars($step['img']); ?>" class="step-img" alt="Step <?php echo $index + 1; ?>">
                
                <div class="dialogue-box" style="border-left-color: <?php echo $step['color']; ?>;">
                    <span class="char-name" style="color: <?php echo $step['color']; ?>;">
                        <?php echo $step['char']; ?>
                    </span>
                    <span class="quote-text"><?php echo $step['quote']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
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
                        setTimeout(() => { window.location.href = href; }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>