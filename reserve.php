<?php
session_start();
require 'db.php';
require 'mail_setup.php';

// --- CONFIGURATION ---
// Set Timezone to match event expectations
date_default_timezone_set('America/Los_Angeles');

// Optional: Set unlock date for early access restrictions
// $UNLOCK_DATE = '2025-12-07 12:00:00';
// $current_time = time();
// $unlock_timestamp = strtotime($UNLOCK_DATE);
// $is_server_locked = ($current_time < $unlock_timestamp);
$is_server_locked = false; // Set to true and configure above to enable time-locking

// --- 1. HANDLE ACCESS CODE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_code') {
    $code = strtolower(trim($_POST['access_code'] ?? ''));
    // TEMPLATE: Add your access codes here
    if ($code === 'admin') {
        $_SESSION['presale_access'] = 'admin';
        $_SESSION['flash_reservation'] = ['text' => "Admin access granted.", 'type' => 'success'];
    } elseif ($code === 'earlybird') {
        $_SESSION['presale_access'] = 'early';
        $_SESSION['flash_reservation'] = ['text' => "Early access code accepted.", 'type' => 'success'];
    } else {
        $_SESSION['flash_reservation'] = ['text' => "Invalid access code.", 'type' => 'error'];
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- 2. HANDLE CANCELLATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_booking') {
    $ref_id = trim($_POST['ref_id'] ?? '');
    $raw_ident = trim($_POST['identifier'] ?? '');
    $clean_phone = substr(preg_replace('/[^0-9]/', '', $raw_ident), -10);

    if (empty($ref_id) || empty($raw_ident)) {
        $_SESSION['flash_reservation'] = ['text' => "Missing information.", 'type' => 'error'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_reference = ? AND (booker_email = ? OR booker_phone LIKE ?)");
            $phone_search = "%" . $clean_phone;
            $stmt->execute([$ref_id, $raw_ident, $phone_search]);
            $booking = $stmt->fetch();

            if ($booking) {
                $del = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $del->execute([$booking['id']]);
                $_SESSION['flash_reservation'] = ['text' => "Reservation cancelled. Slot released.", 'type' => 'success'];
            } else {
                $_SESSION['flash_reservation'] = ['text' => "No matching reservation found.", 'type' => 'error'];
            }
        } catch (Exception $e) {
            $_SESSION['flash_reservation'] = ['text' => "System error: " . $e->getMessage(), 'type' => 'error'];
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$access_level = $_SESSION['presale_access'] ?? 'none';

// --- 3. CHECK FOR FLASH MESSAGES ---
$msg_text = "";
$msg_type = "";
if (isset($_SESSION['flash_reservation'])) {
    $msg_text = $_SESSION['flash_reservation']['text'];
    $msg_type = $_SESSION['flash_reservation']['type'];
    unset($_SESSION['flash_reservation']);
}

// --- HELPER: CHECK IF SLOT IS UNLOCKED ---
function isSlotUnlocked($slot, $access_level, $is_server_locked) {
    if (!$is_server_locked) return true;
    if ($access_level === 'admin') return true;
    if ($access_level === 'early') return true;
    return false;
}

// --- 4. FETCH & PRE-PROCESS SLOTS ---
$sql = "SELECT
            s.id,
            s.activity_name,
            s.start_time,
            DATE_FORMAT(s.start_time, '%h:%i %p') as time_str,
            s.capacity,
            (SELECT COUNT(*) FROM bookings b WHERE b.slot_id = s.id) as booked_count
        FROM event_slots s
        ORDER BY s.activity_name, s.start_time";

$raw_slots = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Build Final Slot List with Status
$slots = [];
$activities = [];
foreach($raw_slots as $s) {
    $remaining = $s['capacity'] - $s['booked_count'];
    $s['remaining'] = max(0, $remaining);
    $s['is_full'] = ($remaining <= 0);
    $s['locked'] = !isSlotUnlocked($s, $access_level, $is_server_locked);
    $slots[] = $s;

    // Extract activity name for grouping
    $actName = preg_replace('/\s*\(Session.*$/', '', $s['activity_name']);
    if (!in_array($actName, $activities)) {
        $activities[] = $actName;
    }
}

// --- 5. HANDLE BOOKING SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'book') {
    $slot_id = (int)$_POST['slot_id'];
    $target_slot = null;
    foreach($slots as $s) { if ($s['id'] == $slot_id) { $target_slot = $s; break; } }

    $flash_msg = "";
    $flash_type = "";

    if (!$target_slot || $target_slot['locked']) {
        $flash_msg = "Slot is locked or unavailable.";
        $flash_type = "error";
    } else {
        $raw_phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
        $phone = substr($raw_phone, -10);
        $email = trim($_POST['email']);

        if (empty($phone) || strlen($phone) !== 10) {
            $flash_msg = "Invalid phone number. 10 digits required.";
            $flash_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash_msg = "Invalid email format.";
            $flash_type = "error";
        } elseif (empty($slot_id)) {
            $flash_msg = "Missing slot selection.";
            $flash_type = "error";
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM bookings WHERE booker_email = ? OR booker_phone = ?");
                $check->execute([$email, $phone]);

                if ($check->fetch()) {
                    $flash_msg = "This phone/email already has a reservation. One per guest.";
                    $flash_type = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id = ?");
                    $stmt->execute([$slot_id]);
                    $count = $stmt->fetchColumn();

                    if ($count >= $target_slot['capacity']) {
                        $flash_msg = "Slot capacity exceeded.";
                        $flash_type = "error";
                    } else {
                        $ref_id = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 6));
                        $stmt = $pdo->prepare("INSERT INTO bookings (slot_id, booker_phone, booker_email, booking_reference) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$slot_id, $phone, $email, $ref_id]);

                        try {
                            $emailSent = sendConfirmationEmail($email, $ref_id, $target_slot['start_time'], $phone, $target_slot['activity_name']);
                        } catch (Exception $e) { $emailSent = false; }

                        if ($emailSent) {
                            $flash_msg = "Reservation confirmed!\nRef: $ref_id\nCheck your email for confirmation.";
                        } else {
                            $flash_msg = "Reservation confirmed!\nRef: $ref_id\n(Please save this reference ID)";
                        }
                        $flash_type = "success";
                    }
                }
            } catch (PDOException $e) {
                $flash_msg = "Database error: " . $e->getMessage();
                $flash_type = "error";
            }
        }
    }

    $_SESSION['flash_reservation'] = ['text' => $flash_msg, 'type' => $flash_type];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Reservations | Event Guide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=40">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .reserve-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .activity-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .activity-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--accent-gold);
            padding: 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .activity-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }
        .activity-card h3 { color: var(--accent-gold); margin: 0 0 10px 0; font-size: 1.2rem; }
        .activity-card p { color: #ccc; margin: 0; font-size: 0.9rem; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 2000;
            display: none; justify-content: center; align-items: center;
        }
        .modal-content {
            background: var(--accent-dark-blue);
            border: 2px solid var(--accent-gold);
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border-radius: 8px;
            position: relative;
        }
        .modal-close {
            position: absolute; top: 10px; right: 15px;
            color: #aaa; cursor: pointer; font-size: 1.5rem;
        }
        .modal-close:hover { color: white; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: var(--accent-gold); margin-bottom: 5px; font-size: 0.9rem; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--accent-gold);
            color: white;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group select option { background: #1a1a2e; }

        .action-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .action-btn {
            padding: 10px 20px;
            border: 1px solid var(--accent-gold);
            background: transparent;
            color: var(--accent-gold);
            cursor: pointer;
            font-size: 0.9rem;
            border-radius: 4px;
        }
        .action-btn:hover { background: var(--accent-gold); color: #000; }
        .action-btn.danger { border-color: #ff4d4d; color: #ff4d4d; }
        .action-btn.danger:hover { background: #ff4d4d; color: white; }

        .info-box {
            background: rgba(0,0,0,0.4);
            border-left: 4px solid var(--accent-gold);
            padding: 15px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            color: #ddd;
            line-height: 1.5;
        }

        .flash-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            white-space: pre-line;
        }
        .flash-success { background: rgba(0, 210, 106, 0.2); border: 1px solid var(--accent-green); color: var(--accent-green); }
        .flash-error { background: rgba(255, 77, 77, 0.2); border: 1px solid #ff4d4d; color: #ff4d4d; }

        .locked-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex; justify-content: center; align-items: center;
            color: #888; font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div id="slot-data" style="display:none;"><?php echo json_encode($slots); ?></div>

    <div class="container page-visible">
        <br>
        <h1 style="color: var(--accent-gold); text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);">
            ACTIVITY RESERVATIONS
        </h1>
        <p style="color: #aaa; margin-bottom: 20px;">Select an activity to reserve your spot</p>

        <?php if($msg_text): ?>
            <div class="flash-message flash-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg_text); ?></div>
        <?php endif; ?>

        <div class="action-buttons">
            <button class="action-btn danger" onclick="document.getElementById('cancel-modal').style.display='flex'">
                <i class="fa-solid fa-xmark"></i> Cancel Reservation
            </button>
            <button class="action-btn" onclick="document.getElementById('code-modal').style.display='flex'">
                <i class="fa-solid fa-key"></i> Enter Access Code
            </button>
        </div>

        <div class="info-box">
            <strong>How It Works:</strong><br>
            1. Select an activity below to see available time slots<br>
            2. Enter your phone and email to reserve<br>
            3. You'll receive a confirmation with your reference ID<br>
            <em style="color: #888; font-size: 0.85rem;">One reservation per phone/email. Show your reference ID at check-in.</em>
        </div>

        <?php if($is_server_locked && $access_level === 'none'): ?>
            <div style="text-align: center; padding: 40px; color: #888;">
                <i class="fa-solid fa-lock" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <h3>Reservations Not Yet Open</h3>
                <p>Check back later or enter an early access code.</p>
            </div>
        <?php else: ?>
            <div class="activity-grid">
                <?php foreach($activities as $actName): ?>
                    <div class="activity-card" onclick="openBooking('<?php echo addslashes($actName); ?>')">
                        <h3><i class="fa-solid fa-calendar-check"></i> <?php echo htmlspecialchars($actName); ?></h3>
                        <p>Click to view available time slots</p>
                    </div>
                <?php endforeach; ?>

                <?php if(empty($activities)): ?>
                    <p style="color: #888; grid-column: 1/-1; text-align: center;">No activities available for booking.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br><br>
        <a href="home.php" class="btn-gold"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
    </div>

    <!-- Booking Modal -->
    <div class="modal-overlay" id="booking-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeBooking()">&times;</span>
            <h2 id="booking-title" style="color: var(--accent-gold); margin-top: 0;"></h2>
            <form method="POST" id="booking-form">
                <input type="hidden" name="action" value="book">

                <div class="form-group">
                    <label>Time Slot:</label>
                    <select name="slot_id" id="slot-select" required></select>
                </div>

                <div class="form-group">
                    <label>Phone Number:</label>
                    <input type="tel" name="phone" id="phone-input" placeholder="(555) 555-5555" maxlength="14" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="email-input" placeholder="you@email.com" required>
                </div>

                <button type="submit" class="btn-gold" style="width: 100%; margin-top: 10px;">Confirm Reservation</button>
            </form>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal-overlay" id="cancel-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="document.getElementById('cancel-modal').style.display='none'">&times;</span>
            <h2 style="color: #ff4d4d; margin-top: 0;">Cancel Reservation</h2>
            <p style="color: #ccc;">Enter your booking details to cancel.</p>
            <form method="POST">
                <input type="hidden" name="action" value="cancel_booking">

                <div class="form-group">
                    <label>Reference ID:</label>
                    <input type="text" name="ref_id" placeholder="REF-XXXXXX" required>
                </div>

                <div class="form-group">
                    <label>Phone or Email (used for booking):</label>
                    <input type="text" name="identifier" placeholder="Phone or email..." required>
                </div>

                <button type="submit" class="btn-gold" style="width: 100%; background: #ff4d4d; border-color: #ff4d4d;">Cancel Reservation</button>
            </form>
        </div>
    </div>

    <!-- Access Code Modal -->
    <div class="modal-overlay" id="code-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="document.getElementById('code-modal').style.display='none'">&times;</span>
            <h2 style="color: var(--accent-gold); margin-top: 0;">Enter Access Code</h2>
            <form method="POST">
                <input type="hidden" name="action" value="submit_code">

                <div class="form-group">
                    <label>Code:</label>
                    <input type="text" name="access_code" placeholder="Enter code..." required style="text-align: center; text-transform: uppercase;">
                </div>

                <button type="submit" class="btn-gold" style="width: 100%;">Submit</button>
            </form>
        </div>
    </div>

    <script>
        const phoneInput = document.getElementById('phone-input');
        phoneInput.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });

        const allSlots = JSON.parse(document.getElementById('slot-data').innerText);
        const bookingModal = document.getElementById('booking-modal');
        const slotSelect = document.getElementById('slot-select');
        const bookingTitle = document.getElementById('booking-title');

        function openBooking(filterText) {
            bookingModal.style.display = 'flex';
            slotSelect.innerHTML = '';
            bookingTitle.innerText = filterText;

            let found = false;
            allSlots.forEach(slot => {
                if (slot.activity_name.includes(filterText)) {
                    if (slot.locked) return;

                    const opt = document.createElement('option');
                    opt.value = slot.id;

                    let text = slot.time_str;
                    if (slot.is_full) {
                        text += " (FULL)";
                        opt.disabled = true;
                    } else {
                        text += ` (${slot.remaining} spots left)`;
                    }

                    opt.text = text;
                    slotSelect.appendChild(opt);
                    found = true;
                }
            });

            if (!found) {
                const opt = document.createElement('option');
                opt.text = "No slots available";
                opt.disabled = true;
                slotSelect.appendChild(opt);
            }
        }

        function closeBooking() { bookingModal.style.display = 'none'; }
    </script>
<?php include 'footer.php'; ?>
