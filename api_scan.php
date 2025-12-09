<?php
// 1. START BUFFERING & SUPPRESS DISPLAY ERRORS
// This prevents "Network Error" caused by PHP warnings or stray whitespace
ob_start(); 
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();
require 'db.php';
header('Content-Type: application/json');

// --- HELPER TO FLUSH JSON AND EXIT ---
function send_json($data) {
    ob_clean(); // Clear any buffered text/warnings
    echo json_encode($data);
    exit();
}

// 2. Security: Must be Staff
if (!isset($_SESSION['staff_id'])) {
    send_json(['status' => 'error', 'message' => 'Unauthorized']);
}

// 3. Get Data
$data = json_decode(file_get_contents('php://input'), true);
$qrRaw = $data['qr_code'] ?? ''; 
$mode = $data['mode'] ?? 'station'; 
$lat = $data['lat'] ?? null; 
$lon = $data['lon'] ?? null; 

$role = $_SESSION['staff_role'];
$staffName = $_SESSION['staff_name'];

// ==========================================
// FIX: RESOLVE MANUAL INPUTS (Codename/ID)
// ==========================================
if (!empty($qrRaw) && strpos($qrRaw, 'OP-') === false && strpos($qrRaw, 'AUTH:') === false) {
    $cleanInput = trim($qrRaw);
    
    // 1. If it's just a number, assume it's an ID
    if (ctype_digit($cleanInput)) {
        $qrRaw = 'OP-' . $cleanInput;
    } 
    // 2. Otherwise, treat it as a Codename and look up the ID
    else {
        try {
            // Case-insensitive search for convenience
            $stmt = $pdo->prepare("SELECT id FROM operatives WHERE codename LIKE ? LIMIT 1");
            $stmt->execute([$cleanInput]);
            $foundId = $stmt->fetchColumn();
            
            if ($foundId) {
                $qrRaw = 'OP-' . $foundId;
            } else {
                // If name not found, stop here to avoid confusing downstream logic
                send_json(['status' => 'error', 'message' => "CODENAME '$cleanInput' NOT FOUND"]);
            }
        } catch (Exception $e) {
            send_json(['status' => 'error', 'message' => 'DB LOOKUP ERROR']);
        }
    }
}

// ==========================================
// CHECK STANDARD REWARD INVENTORY LEVEL
// ==========================================
$inv_msg = null;
try {
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'standard_reward_limit'");
    $std_limit = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM operatives WHERE status = 'redeemed' AND bp_owned = 0");
    $std_redeemed = (int)$stmt->fetchColumn();
    
    $remaining = $std_limit - $std_redeemed;
    
    if ($remaining <= 25) {
        $inv_msg = "⚠ LOW STOCK: Only {$remaining} rewards left. Warn guest!";
        if ($remaining <= 0) {
            $inv_msg = "⚠ OUT OF STOCK ({$remaining}). Warn guest rewards are gone.";
        }
    }
} catch (Exception $e) { } // Ignore inventory errors


try {
    // ==========================================
    // LOGIC A: BATTLE PASS SALES (OP-)
    // Allowed: Admin, Check-In, Redemption
    // ==========================================
    if ($mode == 'bp_sale' && strpos($qrRaw, 'OP-') === 0) {
        if (!in_array($role, ['admin', 'checkin', 'redemption'])) {
            send_json(['status' => 'error', 'message' => 'ACCESS DENIED']);
        }

        $operativeId = (int)str_replace('OP-', '', $qrRaw);

        // Check ownership AND Parent Status
        $stmt = $pdo->prepare("SELECT bp_owned, parent_id FROM operatives WHERE id = ?");
        $stmt->execute([$operativeId]);
        $user = $stmt->fetch();

        if (!$user) {
            send_json(['status' => 'error', 'message' => 'USER NOT FOUND']);
        }

        if (!empty($user['parent_id'])) {
            send_json(['status' => 'error', 'message' => 'CHILD ACCOUNT: NO BP ALLOWED']);
        }

        if ($user['bp_owned'] == 1) {
            send_json(['status' => 'warning', 'message' => 'ALREADY HAS BATTLE PASS']);
        }

        // Check Inventory
        $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'battle_pass_limit'");
        $limit = (int)$stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM operatives WHERE bp_owned = 1");
        $sold = (int)$stmt->fetchColumn();

        if ($sold >= $limit) {
            send_json(['status' => 'error', 'message' => 'SOLD OUT']);
        }

        $pdo->prepare("UPDATE operatives SET bp_owned = 1 WHERE id = ?")->execute([$operativeId]);
        send_json(['status' => 'success', 'message' => 'BATTLE PASS ACTIVATED']);
    }

    // ==========================================
    // LOGIC B: BATTLE PASS REDEMPTION (OP-)
    // Allowed: Admin, Redemption
    // ==========================================
    if ($mode == 'bp_redeem' && strpos($qrRaw, 'OP-') === 0) {
        if (!in_array($role, ['admin', 'redemption'])) {
            send_json(['status' => 'error', 'message' => 'ACCESS DENIED']);
        }

        $operativeId = (int)str_replace('OP-', '', $qrRaw);

        $stmt = $pdo->prepare("SELECT bp_owned, bp_redeemed FROM operatives WHERE id = ?");
        $stmt->execute([$operativeId]);
        $user = $stmt->fetch();

        if (!$user || $user['bp_owned'] == 0) {
            send_json(['status' => 'error', 'message' => 'NO BATTLE PASS FOUND']);
        }
        
        if ($user['bp_redeemed'] == 1) {
            send_json(['status' => 'error', 'message' => 'ALREADY REDEEMED']);
        }

        $pdo->prepare("UPDATE operatives SET bp_redeemed = 1 WHERE id = ?")->execute([$operativeId]);
        send_json(['status' => 'success', 'message' => 'BP REWARDS ISSUED']);
    }


    // ==========================================
    // LOGIC C: CHILD AUTHORIZATION (AUTH:)
    // Allowed: Admin, Check-In
    // ==========================================
    if (strpos($qrRaw, 'AUTH:') === 0) {
        if (!in_array($role, ['admin', 'checkin'])) {
            send_json(['status' => 'error', 'message' => 'ACCESS DENIED']);
        }

        $parts = explode(':', $qrRaw);
        if (count($parts) < 3) {
            send_json(['status' => 'error', 'message' => 'Bad Code']);
        }

        $parentId = (int)$parts[1];
        $childName = urldecode($parts[2]);

        $stmt = $pdo->prepare("SELECT id FROM operatives WHERE codename = ?");
        $stmt->execute([$childName]);
        if ($stmt->fetch()) {
            send_json(['status' => 'error', 'message' => 'NAME TAKEN']);
        }

        $authToken = bin2hex(random_bytes(32));
        $rescueCode = rand(1000, 9999);
        
        $sql = "INSERT INTO operatives (codename, auth_token, rescue_code, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$childName, $authToken, $rescueCode, $parentId]);

        send_json(['status' => 'success', 'message' => 'RECRUIT APPROVED']);
    }


    // ==========================================
    // LOGIC D: STANDARD OPERATIONS (OP-)
    // ==========================================
    if (strpos($qrRaw, 'OP-') === 0) {
        $operativeId = (int)str_replace('OP-', '', $qrRaw);

        // --- D1. PRIZE REDEMPTION (Admin, Redemption) ---
        if ($mode == 'redeem') {
            if (!in_array($role, ['admin', 'redemption'])) {
                send_json(['status' => 'error', 'message' => 'ACCESS DENIED']);
            }

            $stmt = $pdo->prepare("SELECT status, bp_owned FROM operatives WHERE id = ?");
            $stmt->execute([$operativeId]);
            $user = $stmt->fetch();

            if (!$user) {
                send_json(['status' => 'error', 'message' => 'USER NOT FOUND']);
            }

            if ($user['bp_owned'] == 1) {
                send_json(['status' => 'warning', 'message' => 'HAS BATTLE PASS. USE BP REDEEM.']);
            }

            if ($user['status'] == 'redeemed') {
                send_json(['status' => 'error', 'message' => 'ALREADY CLAIMED']);
            }
            if ($user['status'] == 'active') {
                send_json(['status' => 'error', 'message' => 'NOT ELIGIBLE']);
            }

            $pdo->prepare("UPDATE operatives SET status = 'redeemed' WHERE id = ?")->execute([$operativeId]);
            
            send_json(['status' => 'success', 'message' => 'PRIZE REDEEMED', 'inventory_warning' => $inv_msg]);
        }

        // --- D2. STATION STAMP (Admin, Station) ---
        if ($mode == 'station') {
            if (!in_array($role, ['admin', 'station'])) {
                send_json(['status' => 'error', 'message' => 'ACCESS DENIED']);
            }

            $stmt = $pdo->prepare("SELECT status, bp_owned FROM operatives WHERE id = ?");
            $stmt->execute([$operativeId]);
            $uStat = $stmt->fetch();

            if (!$uStat) {
                send_json(['status' => 'error', 'message' => 'USER NOT FOUND']);
            }

            if ($uStat['status'] == 'pending') {
                send_json(['status' => 'error', 'message' => 'MUST SIGN CONTRACT FIRST']);
            }

            $stmt = $pdo->prepare("SELECT id FROM mission_logs WHERE operative_id = ? AND station_name = ?");
            $stmt->execute([$operativeId, $staffName]);
            if ($stmt->fetch()) {
                send_json(['status' => 'warning', 'message' => 'ALREADY SCANNED', 'inventory_warning' => $inv_msg]);
            }

            $sql = "INSERT INTO mission_logs (operative_id, station_name, staff_lat, staff_lon) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$operativeId, $staffName, $lat, $lon]); 

            $pdo->prepare("UPDATE operatives SET status = 'eligible' WHERE id = ? AND status = 'active'")->execute([$operativeId]);

            $msg = 'STAMP CONFIRMED';
            if ($uStat['bp_owned'] == 1) {
                $msg .= ". Thank you for fulfilling your contract.";
            }

            send_json(['status' => 'success', 'message' => $msg, 'inventory_warning' => $inv_msg]);
        }
    }

    // ==========================================
    // LOGIC E: EVENT CHECK-IN
    // ==========================================
    if ($mode == 'event_checkin' && strpos($qrRaw, 'OP-') === 0) {
        $operativeId = (int)str_replace('OP-', '', $qrRaw);

        $sql = "SELECT b.id, s.activity_name, s.start_time 
                FROM bookings b 
                JOIN event_slots s ON b.slot_id = s.id 
                WHERE b.operative_id = ? 
                AND b.status NOT IN ('checked_in', 'cancelled')
                ORDER BY s.start_time ASC 
                LIMIT 1";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$operativeId]);
        $booking = $stmt->fetch();

        if ($booking) {
            $pdo->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?")->execute([$booking['id']]);
            $time = date('g:i A', strtotime($booking['start_time']));
            send_json(['status' => 'success', 'message' => "CONFIRMED: {$booking['activity_name']} @ $time"]);
        } else {
            $checkUsed = $pdo->prepare("SELECT id FROM bookings WHERE operative_id = ? AND status = 'checked_in'");
            $checkUsed->execute([$operativeId]);
            if ($checkUsed->fetch()) {
                send_json(['status' => 'warning', 'message' => 'ALREADY CHECKED IN']);
            } else {
                send_json(['status' => 'error', 'message' => 'NO ACTIVE BOOKING FOUND']);
            }
        }
    }
    
    // If we get here, the code wasn't handled by any mode
    send_json(['status' => 'error', 'message' => 'Unknown QR/Mode']);

} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
?>