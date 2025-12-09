<?php
session_start();
require 'db.php';
require 'mail_setup.php'; 

// --- CONFIGURATION ---
// Set Timezone to ensure server clock matches event expectations (PST)
date_default_timezone_set('America/Los_Angeles');
$UNLOCK_DATE = '2025-12-07 12:00:00';
$current_time = time();
$unlock_timestamp = strtotime($UNLOCK_DATE);
$is_server_locked = ($current_time < $unlock_timestamp);

// --- 1. HANDLE ACCESS CODE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'submit_code') {
    $code = strtolower(trim($_POST['access_code'] ?? ''));
    if ($code === 'personaadmin') {
        $_SESSION['presale_access'] = 'admin';
        $_SESSION['flash_reservation'] = ['text' => "ADMIN OVERRIDE.\nALL SLOTS UNLOCKED.", 'type' => 'success'];
    } elseif ($code === 'inaba') {
        $_SESSION['presale_access'] = 'inaba';
        $_SESSION['flash_reservation'] = ['text' => "EARLY ACCESS CODE ACCEPTED.\nSELECT SLOTS AVAILABLE. ૮₍ ˶ᵔ ᵕ ᵔ˶ ₎ა", 'type' => 'success'];
    } else {
        $_SESSION['flash_reservation'] = ['text' => "ERROR: INVALID ACCESS CODE.", 'type' => 'error'];
    }
    // FIX: Redirect back to SELF instead of a hardcoded filename to prevent 404s
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// --- 2. HANDLE CANCELLATION (REVOKE ENTRY) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_booking') {
    $ref_id = trim($_POST['ref_id'] ?? '');
    $raw_ident = trim($_POST['identifier'] ?? '');
    
    // Prepare phone version (strip non-digits, take last 10)
    $clean_phone = substr(preg_replace('/[^0-9]/', '', $raw_ident), -10);
    
    if (empty($ref_id) || empty($raw_ident)) {
        $_SESSION['flash_reservation'] = ['text' => "ERROR: MISSING INFORMATION.", 'type' => 'error'];
    } else {
        try {
            // Check for booking matching REF + (Email OR Phone)
            $stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_reference = ? AND (booker_email = ? OR booker_phone LIKE ?)");
            // Use wildcard for phone to be safe
            $phone_search = "%" . $clean_phone; 
            
            $stmt->execute([$ref_id, $raw_ident, $phone_search]);
            $booking = $stmt->fetch();
            
            if ($booking) {
                $del = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $del->execute([$booking['id']]);
                $_SESSION['flash_reservation'] = ['text' => "RESERVATION REVOKED.\nSLOT RELEASED.", 'type' => 'success'];
            } else {
                $_SESSION['flash_reservation'] = ['text' => "ERROR: NO MATCHING RESERVATION FOUND.", 'type' => 'error'];
            }
        } catch (Exception $e) {
            $_SESSION['flash_reservation'] = ['text' => "SYSTEM ERROR: " . $e->getMessage(), 'type' => 'error'];
        }
    }
    // FIX: Redirect back to SELF to prevent 404s
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}

$access_level = $_SESSION['presale_access'] ?? 'none';

// --- 3. CHECK FOR FLASH MESSAGES (From Redirect) ---
$msg_text = "";
$msg_type = ""; 

if (isset($_SESSION['flash_reservation'])) {
    $msg_text = $_SESSION['flash_reservation']['text'];
    $msg_type = $_SESSION['flash_reservation']['type'];
    unset($_SESSION['flash_reservation']); 
}

// --- HELPER: CHECK IF SLOT IS UNLOCKED ---
function isSlotUnlocked($slot, $access_level, $is_server_locked, $p3_ids, $p4_ids, $p5_ids) {
    // 1. If global lock is OFF, everything is open
    if (!$is_server_locked) return true;

    // 2. Admin overrides everything
    if ($access_level === 'admin') return true;

    // 3. Inaba Logic - Parse session number from activity_name
    if ($access_level === 'inaba') return true;

    return false; // Default blocked if server is locked and no code/invalid code
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

// A. Sort and Index for Logic
// We need strict time ordering per category to determine "2nd session", "2nd half", etc.
$p3_slots = []; 
$p4_slots = [];
$p5_slots = [];

foreach ($raw_slots as $s) {
    if (stripos($s['activity_name'], 'Persona 3') !== false || stripos($s['activity_name'], 'Sew') !== false) {
        $p3_slots[] = $s;
    } elseif (stripos($s['activity_name'], 'Persona 5') !== false || stripos($s['activity_name'], 'Phantom') !== false) {
        $p5_slots[] = $s;
    } else {
        // Assume P4 for the rest (Trivia, Poll Mine, etc) based on file context
        $p4_slots[] = $s;
    }
}

// Sort helpers
$sortByTime = fn($a, $b) => strcmp($a['start_time'], $b['start_time']);
usort($p3_slots, $sortByTime);
usort($p4_slots, $sortByTime);
usort($p5_slots, $sortByTime);

// Extract IDs for easy lookup
$p3_ids = array_column($p3_slots, 'id');
$p4_ids = array_column($p4_slots, 'id');
$p5_ids = array_column($p5_slots, 'id');

// B. Build Final Slot List with Status
$slots = [];
foreach($raw_slots as $s) {
    $remaining = $s['capacity'] - $s['booked_count'];
    $s['remaining'] = max(0, $remaining);
    $s['is_full'] = ($remaining <= 0);
    
    // Check Lock Status
    $s['locked'] = !isSlotUnlocked($s, $access_level, $is_server_locked, $p3_ids, $p4_ids, $p5_ids);
    
    $slots[] = $s;
}

// --- 5. HANDLE BOOKING SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'book') {
    $slot_id = (int)$_POST['slot_id'];
    
    // Double Check Lock Logic Server Side
    $target_slot = null;
    foreach($slots as $s) { if ($s['id'] == $slot_id) { $target_slot = $s; break; } }

    $flash_msg = "";
    $flash_type = "";

    if (!$target_slot || $target_slot['locked']) {
        $flash_msg = "ERROR: SLOT IS LOCKED OR UNAVAILABLE.";
        $flash_type = "error";
    } else {
        // Proceed with booking logic
        $raw_phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
        $phone = substr($raw_phone, -10);
        $email = trim($_POST['email']);

        if (empty($phone) || strlen($phone) !== 10) {
            $flash_msg = "ERROR: INVALID PHONE NUMBER. 10 DIGITS REQUIRED.";
            $flash_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash_msg = "ERROR: INVALID EMAIL FORMAT.";
            $flash_type = "error";
        } elseif (empty($slot_id)) {
            $flash_msg = "ERROR: MISSING SLOT SELECTION.";
            $flash_type = "error";
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM bookings WHERE booker_email = ? OR booker_phone = ?");
                $check->execute([$email, $phone]);
                
                if ($check->fetch()) {
                    $flash_msg = "ACCESS DENIED: IDENTITY ALREADY REGISTERED.\nONE RESERVATION PER GUEST.";
                    $flash_type = "error";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id = ?");
                    $stmt->execute([$slot_id]);
                    $count = $stmt->fetchColumn();

                    if ($count >= $target_slot['capacity']) {
                        $flash_msg = "ERROR: SLOT CAPACITY EXCEEDED.";
                        $flash_type = "error";
                    } else {
                    // 1. Generate Reference ID
                    $ref_id = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 6));

                    // 2. INSERT THE BOOKING (This is the core logic)
                    $stmt = $pdo->prepare("INSERT INTO bookings (slot_id, booker_phone, booker_email, booking_reference) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$slot_id, $phone, $email, $ref_id]);
                    
                    // 3. Send Confirmation Email
                    try {
                        $emailSent = sendConfirmationEmail($email, $ref_id, $target_slot['start_time'], $phone, $target_slot['activity_name']);
                    } catch (Exception $e) { $emailSent = false; }

                    // 4. Set Success Message
                    if ($emailSent) {
                        $flash_msg = "SUCCESS: SLOT SECURED.\nREF: $ref_id\nCHECK YOUR EMAIL.";
                    } else {
                        $flash_msg = "SUCCESS: SLOT SECURED.\nREF: $ref_id\n(EMAIL SYSTEM OFFLINE - SAVE THIS ID)";
                    }
                    $flash_type = "success";
}
                }
            } catch (PDOException $e) {
                $flash_msg = "SYSTEM FAILURE: DATABASE ERROR (" . $e->getMessage() . ")";
                $flash_type = "error";
            }
        }
    }

    $_SESSION['flash_reservation'] = ['text' => $flash_msg, 'type' => $flash_type];
    // FIX: Redirect back to SELF to prevent 404s
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IRIS // INTERACTIVE BOOKING PORTAL</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=VT323&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --p5-green: #39ff14;
            --p5-dim-green: rgba(57, 255, 20, 0.2);
            --p5-black: #050505;
            --p5-alert: #ff2a2a;
            --p3-blue: #2aeaff; 
            --p4-yellow: #fefe22;
        }

        * { box-sizing: border-box; user-select: none; -webkit-tap-highlight-color: transparent; }

        body {
            margin: 0; padding: 0;
            background-color: var(--p5-black);
            color: var(--p5-green);
            font-family: 'Share Tech Mono', monospace;
            overflow-x: hidden;
            font-size: 16px;
        }

        #boot-layer {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 9999;
            padding: 20px; font-family: 'VT323', monospace; font-size: 1.2rem;
            color: var(--p5-green); overflow: hidden;
            display: flex; flex-direction: column; justify-content: flex-end; 
        }
        .boot-line { margin-bottom: 2px; text-shadow: 0 0 5px var(--p5-green); }

        body::before {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), 
                        linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 90; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }

        @keyframes textShadow {
            0% { text-shadow: 0.438px 0 1px rgba(0,30,255,0.5), -0.438px 0 1px rgba(255,0,80,0.3), 0 0 3px; }
            5% { text-shadow: 2.79px 0 1px rgba(0,30,255,0.5), -2.79px 0 1px rgba(255,0,80,0.3), 0 0 3px; }
            100% { text-shadow: 0.438px 0 1px rgba(0,30,255,0.5), -0.438px 0 1px rgba(255,0,80,0.3), 0 0 3px; }
        }
        .crt-text { animation: textShadow 1.6s infinite; }

        @keyframes promptPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 rgba(57,255,20,0); }
            50% { transform: scale(1.05); box-shadow: 3px 3px 0 rgba(0,0,0,0.5); }
            100% { transform: scale(1); box-shadow: 0 0 0 rgba(57,255,20,0); }
        }

        .page-container {
            max-width: 1200px; margin: 0 auto; padding: 20px;
            padding-bottom: 80px; 
            min-height: 100vh; display: none; 
            flex-direction: column; position: relative;
        }

        header {
            border-bottom: 2px solid var(--p5-green); padding-bottom: 10px; margin-bottom: 25px;
            display: flex; justify-content: space-between; align-items: flex-end;
            flex-wrap: wrap; gap: 10px;
        }

        h1 {
            font-size: clamp(1.5rem, 5vw, 2.5rem);
            margin: 0; transform: skewX(-10deg);
            background: var(--p5-green); color: var(--p5-black);
            padding: 5px 15px; display: inline-block;
            box-shadow: 5px 5px 0px rgba(57, 255, 20, 0.3);
        }

        .code-btn {
            background: #000; border: 1px solid var(--p5-green);
            color: var(--p5-green); font-family: 'Share Tech Mono', monospace;
            padding: 5px 10px; cursor: pointer; text-transform: uppercase;
            font-size: 0.8rem; letter-spacing: 1px;
        }
        .code-btn:hover { background: var(--p5-green); color: #000; }

        .futaba-memo {
            position: relative;
            background: rgba(0, 20, 0, 0.7);
            border-left: 4px solid var(--p5-green);
            padding: 15px; margin-bottom: 30px;
            font-family: 'VT323', monospace; font-size: 1.3rem;
            color: #d0ffcc; box-shadow: 0 0 15px var(--p5-dim-green);
            line-height: 1.4;
        }
        .futaba-memo::before {
            content: "WELCOME_MSG.txt"; position: absolute; top: -12px; left: 10px;
            background: var(--p5-black); padding: 0 8px; font-size: 0.8rem;
            color: var(--p5-green); border: 1px solid var(--p5-green);
        }
        .futaba-avatar { float: right; font-size: 2.2rem; margin-left: 15px; filter: drop-shadow(0 0 5px var(--p5-green)); }

        .rsvp-section {
            border: 2px dashed var(--p5-green);
            background: rgba(0, 30, 0, 0.4);
            padding: 20px; padding-bottom: 40px; margin-bottom: 40px;
            position: relative; cursor: pointer; transition: all 0.3s ease;
        }
        .rsvp-section:hover {
            background: rgba(0, 50, 0, 0.6);
            box-shadow: 0 0 20px var(--p5-dim-green); transform: scale(1.01);
        }
        .rsvp-label {
            background: var(--p5-alert); color: white; padding: 2px 10px;
            font-weight: bold; display: inline-block; transform: skewX(-10deg);
            margin-bottom: 10px; font-size: 1.2rem; box-shadow: 3px 3px 0 rgba(0,0,0,0.5);
        }
        .rsvp-text { font-family: 'VT323', monospace; font-size: 1.4rem; color: white; line-height: 1.3; }
        .click-prompt {
            position: absolute; bottom: 15px; right: 15px;
            background: var(--p5-green); color: var(--p5-black);
            font-family: 'Share Tech Mono', monospace; font-weight: bold;
            font-size: 1.1rem; padding: 4px 12px; transform: skewX(-10deg);
            animation: promptPulse 1.5s infinite ease-in-out; pointer-events: none;
        }

        #activity-sector { position: relative; transition: all 0.5s ease; }
        .sector-locked { filter: grayscale(100%) contrast(0.8); pointer-events: none; opacity: 0.6; }

        #lock-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: 50;
            display: none; justify-content: center; align-items: flex-start; padding-top: 100px;
        }
        .lock-msg-box {
            background: var(--p5-black); border: 2px solid var(--p5-alert);
            padding: 20px; max-width: 90%; width: 500px; text-align: center;
            box-shadow: 0 0 30px var(--p5-alert); transform: skewX(-5deg); pointer-events: auto;
        }
        .lock-icon { font-size: 3rem; margin-bottom: 10px; display: block; animation: textShadow 0.5s infinite; }

        .category-block { margin-bottom: 50px; }
        .cat-title {
            font-size: 2rem; font-weight: bold; margin-bottom: 15px;
            padding-left: 10px; border-bottom: 4px solid; width: 100%;
            display: flex; align-items: center; gap: 10px;
            text-transform: uppercase; letter-spacing: 2px;
        }
        
        .style-p3 .cat-title { color: var(--p3-blue); border-color: var(--p3-blue); text-shadow: 0 0 10px var(--p3-blue); }
        .style-p3 .service-card:hover { border-color: var(--p3-blue); box-shadow: 0 0 15px var(--p3-blue); }
        .style-p4 .cat-title { color: var(--p4-yellow); border-color: var(--p4-yellow); text-shadow: 0 0 10px var(--p4-yellow); }
        .style-p4 .service-card:hover { border-color: var(--p4-yellow); box-shadow: 0 0 15px var(--p4-yellow); }
        .style-p5 .cat-title { color: var(--p5-alert); border-color: var(--p5-alert); text-shadow: 0 0 10px var(--p5-alert); }
        .style-p5 .service-card:hover { border-color: var(--p5-alert); box-shadow: 0 0 15px var(--p5-alert); }
        
        .service-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .service-card {
            border: 1px solid var(--p5-green); padding: 20px;
            background: rgba(0, 20, 0, 0.6); position: relative;
            cursor: pointer; transition: all 0.2s;
            display: flex; flex-direction: column; justify-content: flex-start;
            min-height: 140px;
        }
        .service-card:hover { transform: scale(1.02) skewX(-2deg); z-index: 10; background: rgba(0,0,0,0.9); }
        .svc-name { font-size: 1.4rem; font-weight: bold; line-height: 1.2; text-align: center; margin-bottom: 10px; }
        .svc-desc {
            font-family: 'VT323', monospace;
            font-size: 1rem;
            color: #aaa;
            text-align: center;
            line-height: 1.3;
            flex-grow: 1;
        }
        .svc-desc .warning {
            color: var(--p5-alert);
            font-weight: bold;
            display: block;
            margin-top: 8px;
        }
        .svc-link-text { 
            font-size: 0.9rem; color: var(--p5-alert); font-family: 'VT323', monospace; 
            text-align: center; margin-top: 10px; font-weight: bold; letter-spacing: 1px;
            animation: textShadow 1s infinite;
        }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); z-index: 2000;
            display: none; justify-content: center; align-items: center;
        }
        .terminal-window {
            width: 90%; max-width: 550px;
            border: 2px solid var(--p5-green); background: rgba(0, 10, 0, 1);
            padding: 30px; position: relative; box-shadow: 0 0 30px var(--p5-dim-green);
        }
        .term-header {
            border-bottom: 2px solid var(--p5-green); padding-bottom: 10px; margin-bottom: 20px;
            display: flex; justify-content: space-between; font-weight: bold;
        }
        .stage { display: none; }
        .stage.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .input-group { display: flex; gap: 10px; margin: 20px 0; }
        .booking-input {
            width: 100%; background: black; border: 1px solid var(--p5-green); color: var(--p5-green); 
            padding: 10px; font-family: 'Share Tech Mono', monospace; font-size: 1rem; 
            margin-bottom: 15px; outline: none; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;
        }
        .booking-input:focus { border-color: white; box-shadow: 0 0 10px var(--p5-green); }
        .booking-input.invalid { border-color: var(--p5-alert); box-shadow: 0 0 10px var(--p5-alert); }
        .booking-label { display: block; margin-bottom: 5px; color: #ccc; font-size: 0.9rem; }

        .char-box {
            width: 100%; height: 50px; background: black; border: 2px solid var(--p5-green);
            color: var(--p5-green); font-family: 'Courier New', monospace; font-size: 2rem;
            text-align: center; text-transform: uppercase; outline: none;
        }
        .char-box:focus { background: #002200; box-shadow: 0 0 10px var(--p5-green); }

        .term-btn {
            background: transparent; color: var(--p5-green); border: 2px solid var(--p5-green);
            font-family: 'Share Tech Mono', monospace; font-size: 1.2rem; font-weight: bold;
            padding: 10px; width: 100%; cursor: pointer; text-transform: uppercase; margin-top: 10px;
        }
        .term-btn:hover { background: var(--p5-green); color: black; }

        .dir-btn {
            display: block; width: 100%; text-align: left; background: black;
            color: var(--p5-green); border: 1px solid var(--p5-green); padding: 10px;
            margin-bottom: 5px; cursor: pointer; font-family: 'VT323'; font-size: 1.2rem;
        }
        .dir-btn:hover { background: #002200; padding-left: 20px; }

        .glitch-box {
            border: 1px dashed var(--p5-green); padding: 10px; margin-bottom: 10px; color: #ccc;
            font-family: 'Courier New'; font-size: 0.9rem;
        }
        .error-msg { color: var(--p5-alert); text-align: center; margin-top: 10px; display: none; text-shadow: 0 0 5px var(--p5-alert); }

        footer {
            position: fixed; bottom: 0; left: 0; width: 100%;
            background: var(--p5-black); border-top: 2px solid var(--p5-green);
            padding: 10px 20px; font-size: 0.9rem; color: var(--p5-green);
            z-index: 100; display: flex; justify-content: space-between;
        }
    </style>
</head>
<body>

    <div id="boot-layer"></div>
    <div id="slot-data" style="display:none;"><?php echo json_encode($slots); ?></div>
    
    <div id="result-modal" class="modal-overlay" style="display:none;">
        <div class="terminal-window">
            <div class="term-header">
                <span>// SYSTEM_NOTIFICATION</span>
            </div>
            <div class="stage active" style="text-align:center;">
                <h2 id="res-title" style="margin-top:0; color:var(--p5-green); text-shadow: 0 0 5px var(--p5-green);"></h2>
                <p id="res-msg" style="font-size:1.1rem; line-height:1.4; color:white; white-space: pre-line;"></p>
                <button class="term-btn" onclick="closeResult()">ACKNOWLEDGE</button>
            </div>
        </div>
    </div>

    <div id="cancel-modal" class="modal-overlay" style="display:none;">
        <div class="terminal-window">
            <div class="term-header">
                <span>// REVOKE_ACCESS</span>
                <span style="color:var(--p5-alert)">DESTRUCTIVE</span>
            </div>
            <div style="position:absolute; top:10px; right:10px; cursor:pointer;" onclick="document.getElementById('cancel-modal').style.display='none'">[X]</div>
            <div class="stage active">
                <p style="color: var(--p5-alert)">WARNING: THIS CANNOT BE UNDONE.</p>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="cancel_booking">
                    
                    <label class="booking-label">BOOKING REFERENCE (REF-XXXXXX):</label>
                    <input type="text" name="ref_id" class="booking-input" required placeholder="REF-..." autocomplete="off">
                    
                    <label class="booking-label">VERIFICATION (PHONE OR EMAIL):</label>
                    <input type="text" name="identifier" class="booking-input" required placeholder="Used for booking..." autocomplete="off">
                    
                    <button type="submit" class="term-btn" style="border-color: var(--p5-alert); color: var(--p5-alert);">>> CONFIRM DELETION <<</button>
                </form>
            </div>
        </div>
    </div>

    <div id="code-modal" class="modal-overlay" style="display:none;">
        <div class="terminal-window">
            <div class="term-header">
                <span>// ACCESS_OVERRIDE</span>
                <span style="color:var(--p5-alert)">SECURE</span>
            </div>
            <div style="position:absolute; top:10px; right:10px; cursor:pointer;" onclick="document.getElementById('code-modal').style.display='none'">[X]</div>
            <div class="stage active">
                <p>ENTER PRE-SALE ACCESS CODE:</p>
                <form method="POST">
                    <input type="hidden" name="action" value="submit_code">
                    <input type="text" name="access_code" class="booking-input" style="text-align:center; font-size:1.5rem;" autocomplete="off" placeholder="CODE">
                    <button type="submit" class="term-btn">AUTHENTICATE</button>
                </form>
            </div>
        </div>
    </div>

    <div class="page-container" id="main-interface">
        <header>
            <h1 class="crt-text">VELVET_ROOM_RESERVATION</h1>
            <div style="display:flex; gap:10px;">
                <button class="code-btn" onclick="document.getElementById('cancel-modal').style.display='flex'" style="border-color:var(--p5-alert); color:var(--p5-alert);">REVOKE RESERVATION</button>
                <button class="code-btn" onclick="document.getElementById('code-modal').style.display='flex'">ENTER ACCESS CODE</button>
            </div>
        </header>

        <div class="futaba-memo crt-text">
            <span class="futaba-avatar">(⌐■_■)</span>
            <strong>ORACLE:</strong> Welcome to the reservation hub! <br>
            All systems are green. Select your destination from the archives below.<br><br>
            Due to high expected attendance, online registration is limited to one activity per phone number and email. Every person, regardless of general RSVP, must register with their own information. Want to try more? Physical waitlists will be available on-site. One activity participation is required for the free reward.<br><br>
            You may choose to delete your previous booking with the button "REVOKE RESERVATION" located on the top of the page. This will allow you to free up your phone number and email to use for another booking.<br><br>
            <span style="color:var(--p5-alert)">WARNING:</span> The Phantom Thieves data is encrypted. You'll need to prove you're one of us to access that file.
        </div>

        <div class="rsvp-section" onclick="openLink('https://www.zeffy.com/en-US/ticketing/portal-to-the-velvet-room-all-out-holiday')">
            <div class="rsvp-label">>> PRIORITY MISSION</div>
            <div class="rsvp-text">
                General Admission RSVP is LIVE.<br>
                Secure your permit to the Velvet Room now.<br>
                <span style="color:var(--p4-yellow); font-weight:bold;">
                    BONUS LOOT: RSVP ASAP to secure a free Morgana sticker!
                </span>
            </div>
            <div class="click-prompt">[ CLICK_TO_SECURE_SPOT ]</div>
        </div>

        <div id="activity-sector">
            
            <div id="lock-overlay">
                <div class="lock-msg-box">
                    <span class="lock-icon">🔒</span>
                    <h2 style="color:var(--p5-alert); margin:0;">SYSTEM LOCK</h2>
                    <p style="font-family:'VT323'; font-size:1.3rem; color:white;">
                        "Hold up! These activity nodes are currently encrypted. ദ്ദി(˵ •̀ ᴗ - ˵ ) ✧"
                    </p>
                    <p style="font-family:'VT323'; font-size:1.2rem; color:var(--p5-green);">
                        >> UNLOCK PROTOCOL ACTIVATES: <br>
                        <strong>12/7 @ 12:00 PM PST</strong>
                    </p>
                    <?php if ($access_level !== 'none'): ?>
                        <p style="color:#fefe22; font-size:1rem; margin-top:10px;">
                            [ CODE ACCEPTED: PARTIAL SYSTEMS UNLOCKED ]
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="category-block style-p3">
                <div class="cat-title"><span>PERSONA 3</span></div>
                <div class="service-grid">
                    <div class="service-card" onclick="openBooking('Persona 3')">
                        <div class="svc-name">Sew the Threads of Fate (Felt Craft)</div>
                        <div class="svc-desc">Create your own feltcraft of Koromaru, SEES' beloved Shiba Inu. Take home a handmade companion to remember your journey.</div>
                    </div>
                </div>
            </div>

            <div class="category-block style-p4">
                <div class="cat-title"><span>PERSONA 4</span></div>
                <div class="service-grid">
                    <div class="service-card" onclick="openBooking('Trivia')">
                        <div class="svc-name">Trivia Murder Party</div>
                        <div class="svc-desc">Answer trivia to survive. Get it wrong? Face the Killing Floor and fight for your life in twisted minigames.</div>
                    </div>
                    <div class="service-card" onclick="openBooking('Poll Mine')">
                        <div class="svc-name">Poll Mine</div>
                        <div class="svc-desc">A team game of hidden opinions. Guess how your group answered secret polls to escape the mine before it collapses!</div>
                    </div>
                    <div class="service-card" onclick="openBooking('Split')">
                        <div class="svc-name">Split the Room</div>
                        <div class="svc-desc">Create hypothetical scenarios designed to divide the audience. The more split the vote, the more points you earn.</div>
                    </div>
                    <div class="service-card" onclick="openBooking('Talking')">
                        <div class="svc-name">Talking Points</div>
                        <div class="svc-desc">Give a presentation using slides you've never seen before. Your partner controls the slides—can you keep up?</div>
                    </div>
                    <div class="service-card" onclick="openBooking('Dodoremi')">
                        <div class="svc-name">Dodoremi</div>
                        <div class="svc-desc">It's like Guitar Hero but with Birds! Play together with your team to get the highest score.</div>
                    </div>
                </div>
            </div>

            <div class="category-block style-p5">
                <div class="cat-title"><span>PERSONA 5</span></div>
                <div class="service-grid">
                    <div class="service-card" onclick="openTerminal()">
                        <div class="svc-name">Phantom Thieves Puzzle Heist</div>
                        <div class="svc-desc">
                            A 1-hour immersive puzzle experience. Help the Phantom Thieves and solve challenges to uncover the truth of the Palace. This is a two team cooperative exercise. 16+.
                            <span class="warning">⚠ ADVANCED DIFFICULTY: Requires active engagement and critical thinking. Not recommended for casual audiences.</span>
                        </div>
                        <div class="svc-link-text"><span>🔒</span> ATTEMPT_HACK</div>
                    </div>
                </div>
            </div>
        </div> 
        
        <footer>
            <div>>> IRIS_INTERACTIVE // SECURE_CONNECTION</div>
            <div>[TAKE YOUR TIME]</div>
        </footer>
    </div>

    <div class="modal-overlay" id="term-modal">
        <div class="terminal-window" id="term-win">
            <div class="term-header"><span>// ORACLE_NAV_SYSTEM</span><span style="color:var(--p5-alert)">LOCKED</span></div>
            <div style="position:absolute; top:10px; right:10px; cursor:pointer;" onclick="closeTerminal()">[X]</div>
            <div id="intro-screen" class="stage active">
                <p>"Hold it. This event is for the Phantom Thieves only.<br><br>Prove you aren't a shadow. Complete the sequence."</p>
                <button class="term-btn" onclick="nextStage('intro-screen','stage-1')">>> INITIALIZE TEST <<</button>
            </div>
            <div id="stage-1" class="stage">
                <p style="color:#ccc; font-size:0.9rem;">PROTOCOL_01: IDENTITY</p>
                <p>What is the Leader's code name? (5 Letters)</p>
                <div class="input-group">
                    <input type="text" class="char-box" maxlength="1" oninput="autoTab(this, 0)">
                    <input type="text" class="char-box" maxlength="1" oninput="autoTab(this, 1)">
                    <input type="text" class="char-box" maxlength="1" oninput="autoTab(this, 2)">
                    <input type="text" class="char-box" maxlength="1" oninput="autoTab(this, 3)">
                    <input type="text" class="char-box" maxlength="1" oninput="autoTab(this, 4)">
                </div>
                <button class="term-btn" onclick="checkStage1()">VERIFY_ID()</button>
                <div id="err-1" class="error-msg">>> ERROR: UNRECOGNIZED ID</div>
            </div>
            <div id="stage-2" class="stage">
                <p style="color:#ccc; font-size:0.9rem;">PROTOCOL_02: SAFE_HAVEN</p>
                <p>Scanning for a secure rally point. Which one is the Safe Zone?</p>
                <div style="margin-top:15px;">
                    <button class="dir-btn" onclick="failStage('err-2')">[1] KAMOSHIDA'S CASTLE</button>
                    <button class="dir-btn" onclick="failStage('err-2')">[2] MUSEUM OF VANITY</button>
                    <button class="dir-btn" onclick="nextStage('stage-2', 'stage-3')">[3] CAFÉ LEBLANC</button>
                    <button class="dir-btn" onclick="failStage('err-2')">[4] BANK OF GLUTTONY</button>
                </div>
                <div id="err-2" class="error-msg">>> WARNING: HIGH DISTORTION LEVEL</div>
            </div>
            <div id="stage-3" class="stage">
                <p style="color:#ccc; font-size:0.9rem;">PROTOCOL_03: DECRYPT</p>
                <p>Corrupted message detected. <strong>Letters</strong> hide the truth.</p>
                <div class="glitch-box">RAW_DATA:<br><br>"We must <strong>S</strong>teal the treasure to save <strong>T</strong>he world. Our <strong>A</strong>ctions will <strong>R</strong>eveal the <strong>T</strong>ruth."</div>
                <input type="text" id="input-3" class="char-box" style="font-size:1.5rem;" placeholder="ENTER KEYWORD">
                <button class="term-btn" onclick="checkStage3()" style="margin-top:10px;">EXECUTE()</button>
                <div id="err-3" class="error-msg">>> ERROR: INCORRECT KEYWORD</div>
            </div>
            <div id="win-screen" class="stage" style="text-align: center;">
                <h2 style="font-size: 2rem; color:var(--p3-blue);">ACCESS GRANTED</h2>
                <p>"Specs verified. I'm patching you through to the network now."</p>
                <button class="term-btn" style="background:var(--p5-green); color:black; margin-top:20px;" onclick="closeTerminal(); openBooking('Phantom Thieves');">>> PROCEED TO BOOKING <<</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="booking-modal">
        <div class="terminal-window">
            <div class="term-header"><span>// SLOT_RESERVATION</span><span style="color:var(--p5-green)">ACTIVE</span></div>
            <div style="position:absolute; top:10px; right:10px; cursor:pointer;" onclick="closeBooking()">[X]</div>
            <div class="stage active">
                <p id="booking-title" style="color:#ccc; border-bottom:1px solid #333; padding-bottom:5px; font-weight:bold;"></p>
                <form method="POST" id="booking-form" onsubmit="return validateForm()">
                    <input type="hidden" name="action" value="book">
                    <label class="booking-label">AVAILABLE TIME SLOTS:</label>
                    <select name="slot_id" id="slot-select" class="booking-input" required></select>
                    <label class="booking-label">OPERATIVE NUMBER (Phone):</label>
                    <input type="tel" name="phone" id="phone-input" class="booking-input" placeholder="(555) 555-5555" maxlength="14" required autocomplete="off">
                    <label class="booking-label">CONFIRMATION RELAY (Email):</label>
                    <input type="email" name="email" id="email-input" class="booking-input" placeholder="phantom@thief.com" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" required autocomplete="off">
                    <button type="submit" class="term-btn" style="background:var(--p5-green); color:black;">>> CONFIRM RESERVATION <<</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const phoneInput = document.getElementById('phone-input');
        const emailInput = document.getElementById('email-input');
        phoneInput.addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
        function validateForm() {
            let valid = true;
            if (phoneInput.value.replace(/\D/g, '').length !== 10) {
                alert("ERROR: PHONE NUMBER MUST BE 10 DIGITS.");
                phoneInput.classList.add('invalid'); valid = false;
            } else phoneInput.classList.remove('invalid');
            
            if (!/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(emailInput.value)) {
                alert("ERROR: INVALID EMAIL FORMAT.");
                emailInput.classList.add('invalid'); valid = false;
            } else emailInput.classList.remove('invalid');
            return valid;
        }

        // --- BOOT SEQUENCE ---
        (function runBoot() {
            const bootLayer = document.getElementById('boot-layer');
            const mainInterface = document.getElementById('main-interface');
            const phpMsg = `<?php echo addslashes($msg_text); ?>`;
            const phpType = `<?php echo $msg_type; ?>`;
            let lines = ["> CONNECTING TO SERVER...", "> DOWNLOADING ASSETS...", "> VERIFYING SECURITY...", "> LOADING IRIS_UI..."];
            if (phpMsg) lines = ["> PROCESSING REQUEST...", "> STATUS CODE: RECEIVED."];
            else lines.push("> ACCESS GRANTED.");

            let i = 0;
            function typeLine() {
                if (i < lines.length) {
                    const div = document.createElement('div');
                    div.className = 'boot-line'; div.textContent = lines[i];
                    bootLayer.appendChild(div); i++; setTimeout(typeLine, 200); 
                } else {
                    setTimeout(() => {
                        bootLayer.style.display = 'none';
                        mainInterface.style.display = 'flex';
                        if (phpMsg) {
                            document.getElementById('res-title').innerText = (phpType === 'error') ? 'SYSTEM ERROR' : 'CONFIRMATION';
                            document.getElementById('res-title').style.color = (phpType === 'error') ? 'var(--p5-alert)' : 'var(--p5-green)';
                            document.getElementById('res-msg').innerText = phpMsg;
                            document.getElementById('result-modal').style.display = 'flex';
                        }
                    }, 1500);
                }
            }
            typeLine();
        })();

        function closeResult() { document.getElementById('result-modal').style.display = 'none'; }

        // --- TIME LOCK LOGIC ---
        (function checkLock() {
            const isServerLocked = <?php echo $is_server_locked ? 'true' : 'false'; ?>;
            const accessLevel = '<?php echo $access_level; ?>';
            const sector = document.getElementById('activity-sector');
            const overlay = document.getElementById('lock-overlay');

            // Lock visual if global time not met AND no partial access (admin/inaba)
            if (isServerLocked && accessLevel === 'none') {
                sector.classList.add('sector-locked');
                overlay.style.display = 'flex';
            } else {
                sector.classList.remove('sector-locked');
                overlay.style.display = 'none';
            }
        })();

        function openLink(url) { window.open(url, '_blank'); }

        // --- BOOKING LOGIC ---
        const allSlots = JSON.parse(document.getElementById('slot-data').innerText);
        const bookingModal = document.getElementById('booking-modal');
        const slotSelect = document.getElementById('slot-select');
        const bookingTitle = document.getElementById('booking-title');

        function openBooking(filterText) {
            bookingModal.style.display = 'flex';
            slotSelect.innerHTML = '';
            let baseName = "ACTIVITY SELECTION";
            const exampleSlot = allSlots.find(s => s.activity_name.includes(filterText));
            if (exampleSlot) {
                const nameMatch = exampleSlot.activity_name.match(/^(.*?)(?=\s*(\(|Session))/);
                if (nameMatch && nameMatch[1]) baseName = nameMatch[1].trim(); 
                else baseName = exampleSlot.activity_name;
            }
            bookingTitle.innerText = "BOOKING: " + baseName.toUpperCase();

            let found = false;
            allSlots.forEach(slot => {
                if (slot.activity_name.includes(filterText)) {
                    // Skip blocked slots
                    if (slot.locked) return;

                    const opt = document.createElement('option');
                    opt.value = slot.id;
                    let sessionStr = "General";
                    const sessionMatch = slot.activity_name.match(/Session.*$/); 
                    if (sessionMatch) sessionStr = sessionMatch[0].replace(')', ''); 
                    
                    let text = `${slot.time_str} - ${sessionStr}`;
                    if (slot.is_full) {
                        text += " (FULL)"; opt.disabled = true; opt.style.color = "#555";
                    } else text += ` (${slot.remaining} LEFT)`;
                    
                    opt.text = text;
                    slotSelect.appendChild(opt);
                    found = true;
                }
            });

            if (!found) {
                const opt = document.createElement('option');
                opt.text = "NO SLOTS AVAILABLE / LOCKED";
                slotSelect.appendChild(opt);
            }
        }
        function closeBooking() { bookingModal.style.display = 'none'; }

        // --- TERMINAL LOGIC ---
        const termModal = document.getElementById('term-modal');
        const inputs = document.querySelectorAll('#stage-1 .char-box');
        function openTerminal() { termModal.style.display = 'flex'; }
        function closeTerminal() { termModal.style.display = 'none'; }
        function nextStage(curr, next) {
            document.getElementById(curr).classList.remove('active');
            setTimeout(() => document.getElementById(next).classList.add('active'), 200);
        }
        function failStage(errId) {
            const err = document.getElementById(errId);
            const win = document.getElementById('term-win');
            err.style.display = 'block';
            win.style.borderColor = 'var(--p5-alert)';
            setTimeout(() => { err.style.display = 'none'; win.style.borderColor = 'var(--p5-green)'; }, 1000);
        }
        function autoTab(field, index) {
            field.value = field.value.toUpperCase();
            if (field.value.length === 1 && index < 4) inputs[index + 1].focus();
        }
        function checkStage1() {
            let word = ""; inputs.forEach(i => word += i.value);
            if(word === "JOKER") nextStage('stage-1', 'stage-2'); else failStage('err-1');
        }
        function checkStage3() {
            if(document.getElementById('input-3').value.toUpperCase().trim() === "START") nextStage('stage-3', 'win-screen');
            else failStage('err-3');
        }
    </script>
</body>
</html>