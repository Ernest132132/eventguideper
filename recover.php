<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codename = trim(htmlspecialchars($_POST['codename']));
    $rescueCode = trim(htmlspecialchars($_POST['rescue_code']));

    if (empty($codename) || empty($rescueCode)) {
        $error = "Please enter both Codename and Rescue Code.";
    } else {
        // 1. Check DB for matching Codename AND Rescue Code
        $stmt = $pdo->prepare("SELECT id, codename FROM operatives WHERE codename = ? AND rescue_code = ?");
        $stmt->execute([$codename, $rescueCode]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. SUCCESS! Generate new security token (Velvet Key)
            $newAuthToken = bin2hex(random_bytes(32));

            // 3. Update the database with the new key (Invalidates old sessions)
            $updateStmt = $pdo->prepare("UPDATE operatives SET auth_token = ? WHERE id = ?");
            $updateStmt->execute([$newAuthToken, $user['id']]);

            // 4. Set the new cookie on the user's browser
            setcookie("auth_token", $newAuthToken, time() + (86400 * 30), "/");

            // 5. Log them in and redirect to the dashboard
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['codename'] = $user['codename'];
            
            header("Location: home.php");
            exit();
        } else {
            $error = "Access Denied. Codename or Rescue Code is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recover ID | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=12">
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
        <div class="card">
            <h2 style="color: #ff4d4d;"><i class="fa-solid fa-triangle-exclamation"></i> Recover ID</h2>
            <p style="font-size: 0.9rem; color: #aaa; margin-bottom: 20px;">
                Enter your Codename and the 4-digit Rescue Code you saved when you first registered.
            </p>
            
            <?php if($error): ?>
                <div class="alert" style="border-color: red; color: red;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Codename:</label>
                <input type="text" name="codename" placeholder="e.g. Joker" maxlength="20" required autocomplete="off">

                <label>Rescue Code:</label>
                <input type="number" name="rescue_code" placeholder="XXXX" pattern="[0-9]{4}" required autocomplete="off">
                
                <br><br>
                <button type="submit" class="btn-gold" style="background: #ff4d4d; border-color: #ff4d4d; color: white;">
                    RECOVER IDENTITY
                </button>
            </form>
        </div>

        <br>
        <a href="index.php" style="color: #666;">&larr; Return to Registry</a>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.container');
            document.querySelector('form').addEventListener('submit', () => {
               if(container) { container.classList.remove('page-visible'); container.classList.add('page-exit'); }
            });
        });
    </script>
</body>
</html>