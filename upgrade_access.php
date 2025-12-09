<?php
session_start();
require 'db.php';

// PREVENT BROWSER CACHING (Fixes the "Old Form" issue)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Only Observers should see this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] == 'operative') {
    header("Location: home.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];

// 1. FETCH CURRENT CODENAME (Source of Truth)
$stmt = $pdo->prepare("SELECT codename FROM operatives WHERE id = ?");
$stmt->execute([$user_id]);
$current_codename = $stmt->fetchColumn();

// 2. DETERMINE STATUS
// If name starts with "Visitor-", they are new. Otherwise, they have an identity.
$is_visitor = (strpos($current_codename, 'Visitor-') === 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = trim($_POST['code']);
    
    // DECIDE NAME: Use existing if established, otherwise read input
    if ($is_visitor) {
        $newCodename = trim(htmlspecialchars($_POST['new_codename']));
    } else {
        $newCodename = $current_codename;
    }
    
    // VALIDATION
    if (strtoupper($code) !== "PERSONA") {
        $message = "INVALID ACCESS CODE.";
    } 
    elseif (empty($newCodename)) {
        $message = "YOU MUST CHOOSE A CODENAME.";
    }
    else {
        // Check Availability (Only if changing name)
        $name_taken = false;
        if ($newCodename !== $current_codename) {
            $stmt = $pdo->prepare("SELECT id FROM operatives WHERE codename = ?");
            $stmt->execute([$newCodename]);
            if ($stmt->fetch()) {
                $message = "CODENAME ALREADY TAKEN.";
                $name_taken = true;
            }
        }

        if (!$name_taken) {
            // UPGRADE SUCCESS: Force status to 'pending' to trigger contract
            $stmt = $pdo->prepare("UPDATE operatives SET role = 'operative', status = 'pending', codename = ? WHERE id = ?");
            $stmt->execute([$newCodename, $user_id]);
            
            $_SESSION['role'] = 'operative';
            $_SESSION['codename'] = $newCodename;
            
            header("Location: contract.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upgrade Access | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=27"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <br><br>
        <h1 style="color: var(--velvet-green);">FORGE A CONTRACT</h1>
        <p>To participate, obtain the access code from the Check-In Desk.</p>
        
        <div class="card">
            <?php if($message): ?>
                <div class="alert" style="border-color: red; color: red;"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>ENTER ACCESS CODE:</label>
                <input type="text" name="code" placeholder="Code from staff..." required autocomplete="off">
                
                <label>OPERATIVE CODENAME:</label>
                
                <?php if ($is_visitor): ?>
                    <input type="text" name="new_codename" placeholder="e.g. Fox" required autocomplete="off">
                
                <?php else: ?>
                    <input type="text" name="new_codename_display" value="<?php echo htmlspecialchars($current_codename); ?>" disabled
                           style="background: rgba(0,0,0,0.5); color: #888; border: 1px dashed #666; cursor: not-allowed; opacity: 0.7;">
                    
                    <p style="font-size: 0.75rem; color: #aaa; margin-top: -10px; margin-bottom: 20px;">
                        <i class="fa-solid fa-lock"></i> Identity Locked: <strong><?php echo htmlspecialchars($current_codename); ?></strong>
                    </p>
                <?php endif; ?>
                
                <button type="submit" class="btn-gold" style="background: var(--velvet-green); color: black; margin-top: 10px;">
                    BECOME A CONTRACTED GUEST
                </button>
            </form>
        </div>

        <br>
        <a href="home.php" style="color: #666;">&larr; Return to Observer Mode</a>
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