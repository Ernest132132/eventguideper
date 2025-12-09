<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
    $my_id = $_SESSION['user_id'];

    // Find booking
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE phone_number = ? AND operative_id IS NULL");
    $stmt->execute([$phone]);
    $booking = $stmt->fetch();

    if ($booking) {
        $pdo->prepare("UPDATE bookings SET operative_id = ? WHERE id = ?")->execute([$my_id, $booking['id']]);
        $msg = "SUCCESS: Reservation linked to your ID Card.";
    } else {
        $msg = "ERROR: No unclaimed booking found for that number.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container page-visible">
        <h1 style="color: var(--velvet-gold);">LINK RESERVATION</h1>
        <?php if($msg): ?><div class="alert"><?php echo $msg; ?></div><?php endif; ?>
        
        <div class="card">
            <p>Enter the phone number used during reservation.</p>
            <form method="POST">
                <input type="tel" name="phone" placeholder="Digits only..." required>
                <button type="submit" class="btn-gold">LINK NOW</button>
            </form>
        </div>
        <br><a href="home.php" class="btn-gold">&larr; Dashboard</a>
    </div>
</body>
</html>