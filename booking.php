<?php
// booking.php
session_start();
require 'db.php';
// Email logic removed

// Prevent Caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = ""; 
$myBooking = false;

// 1. HANDLE FORM SUBMISSION (CONNECT BOOKING)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect_booking'])) {
    try {
        $identifier = trim($_POST['identifier']);
        $clean_phone = preg_replace('/[^0-9]/', '', $identifier);

        // A. FIND BOOKING
        $sql = "SELECT id, booking_reference 
                FROM bookings 
                WHERE (booker_email = ? OR booking_reference = ? OR booker_phone LIKE ?) 
                AND operative_id IS NULL 
                LIMIT 1";
                
        $stmt = $pdo->prepare($sql);
        
        // Use strict match for email/ref, but loose match for phone if digits exist
        $phone_search = !empty($clean_phone) ? "%$clean_phone%" : "NO_MATCH";
        
        $stmt->execute([$identifier, $identifier, $phone_search]);
        
        $foundBooking = $stmt->fetch();

        if ($foundBooking) {
            // B. UPDATE BOOKING (LINK IT)
            $update = $pdo->prepare("UPDATE bookings SET operative_id = ? WHERE id = ?");
            $update->execute([$user_id, $foundBooking['id']]);
            
            $message = "Cognition established. Reservation " . htmlspecialchars($foundBooking['booking_reference']) . " connected.";
            $messageType = "success";
        } else {
            $message = "No unlinked reservation found. Check your details or ensure you haven't already linked it.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "System Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// 2. CHECK CURRENT BOOKING STATUS
try {
    $stmt = $pdo->prepare("SELECT b.*, s.start_time, s.activity_name
                           FROM bookings b 
                           LEFT JOIN event_slots s ON b.slot_id = s.id
                           WHERE b.operative_id = ? AND b.status != 'cancelled' 
                           ORDER BY s.start_time ASC LIMIT 1");
    $stmt->execute([$user_id]);
    $myBooking = $stmt->fetch();
} catch (Exception $e) {
    $message = "Database Error: " . $e->getMessage();
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Booking Hub | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=36">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .date-display {
            font-size: 1.5rem;
            color: white;
            font-weight: bold;
            margin: 15px 0;
            font-family: 'Cinzel', serif;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }
        .time-highlight {
            font-size: 2rem;
            color: var(--velvet-gold);
            display: block;
            margin-top: 5px;
        }
        .or-divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: var(--velvet-silver);
            margin: 25px 0;
            font-size: 0.8rem;
            letter-spacing: 2px;
        }
        .or-divider::before, .or-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .or-divider::before { margin-right: 10px; }
        .or-divider::after { margin-left: 10px; }
        
        .ref-code {
            font-family: monospace;
            background: rgba(0,0,0,0.3);
            padding: 5px 10px;
            border-radius: 4px;
            color: var(--velvet-gold);
            border: 1px dashed var(--velvet-gold);
        }
        
        .qr-note {
            background: rgba(0, 210, 106, 0.1); 
            border: 1px solid #00d26a; 
            color: #bbfadd;
            padding: 10px;
            margin-top: 20px;
            font-size: 0.85rem;
            border-radius: 4px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        
        <div class="profile-header">
            <h1>RESERVATION HUB</h1>
            <p style="font-size: 0.8rem; letter-spacing: 2px; color: var(--velvet-gold);">SECURE YOUR TIMELINE</p>
        </div>

        <?php if ($message): ?>
            <div class="alert" style="border-color: <?php echo ($messageType == 'success') ? '#00d26a' : '#E60012'; ?>; color: <?php echo ($messageType == 'success') ? '#00d26a' : '#E60012'; ?>;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($myBooking): ?>
            
            <div class="card" style="text-align: center; border-color: #00d26a; box-shadow: 0 0 20px rgba(0, 210, 106, 0.2);">
                <i class="fa-solid fa-circle-check" style="color: #00d26a; font-size: 3rem; margin-bottom: 15px; filter: drop-shadow(0 0 10px #00d26a);"></i>
                <h2 style="color: #00d26a; margin: 0;">SLOT SECURED</h2>
                <p style="font-size: 0.9rem; color: #aaa; margin-top: 5px;"><?php echo htmlspecialchars($myBooking['activity_name']); ?></p>
                
                <?php 
                    $timeStr = $myBooking['start_time'] ?? 'Now';
                    $dateObj = new DateTime($timeStr); 
                ?>
                <div class="date-display">
                    <?php echo $dateObj->format('F jS'); ?>
                    <span class="time-highlight"><?php echo $dateObj->format('g:i A'); ?></span>
                </div>
                
                <p style="font-size: 0.8rem; opacity: 0.8;">REFERENCE ID:<br>
                <span class="ref-code"><?php echo htmlspecialchars($myBooking['booking_reference'] ?? 'N/A'); ?></span>
                </p>

                <div class="qr-note">
                    <i class="fa-solid fa-id-card"></i> <strong>DATA SYNCED:</strong><br>
                    You do NOT need to show this screen.<br>
                    Simply scan your <strong>My ID</strong> QR code at the activity.
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="home.php" class="btn-gold">
                    <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
                </a>
            </div>

        <?php else: ?>

            <div class="card">
                <h2 style="color: var(--velvet-gold);">Link Existing Booking</h2>
                <p style="font-size: 0.9rem; color: #ccc; margin-bottom: 20px;">
                    Made a booking already? Enter your Email or Phone Number to link it to your ID Card!<br>
                </p>

                <form method="POST" action="booking.php">
                    <input type="hidden" name="connect_booking" value="1">
                    
                    <label style="color: var(--velvet-gold);">IDENTIFIER</label>
                    <input type="text" name="identifier" placeholder="RSVP'd Email or Phone #" required 
                           style="background: #000; border: 1px solid #555; color: white; width: 100%; padding: 12px; font-size: 1rem; border-radius: 4px; box-sizing: border-box;">
                    
                    <button type="submit" class="btn-gold" style="width: 100%; margin-top: 20px;">
                        ESTABLISH CONNECTION
                    </button>
                </form>
            </div>

            <div class="or-divider">  If you have not yet booked a reservation for an activity, please open the booking terminal below, create your booking, and return to this page to link it to your account. If no bookings are avaliable, physical waitlists are avaliable at each location.</div>

            <div class="card" style="border-style: dashed; border-color: rgba(255,255,255,0.3); background: rgba(0,0,0,0.3);">
                <h3 style="color: #fff;">New Booking</h3>
                <p style="font-size: 0.85rem; color: #aaa;">Secure a new time slot via the terminal.</p>
                
                <a href="reserve.php" class="btn-gold" style="display: block; width: 100%; box-sizing: border-box; text-align: center; text-decoration: none; background: transparent; border: 2px solid var(--velvet-gold); color: var(--velvet-gold); margin-top: 15px;">
                    <i class="fa-solid fa-calendar-plus"></i> OPEN BOOKING TERMINAL
                </a>
            </div>

            <br>
            <a href="home.php" class="btn-gold" style="width: 100%; box-sizing: border-box;">
                <i class="fa-solid fa-arrow-left"></i> Return to Dashboard
            </a>

        <?php endif; ?>

    </div>

</body>
</html>