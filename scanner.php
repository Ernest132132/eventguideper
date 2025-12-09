<?php
// 1. CONFIG & AUTH
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Keep user logged in for 24 hours
$timeout = 86400; 
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);

session_start();
require 'db.php';

// --- NEW: AJAX HANDLER FOR ROSTER FETCH ---
if (isset($_GET['ajax_roster'])) {
    if (!isset($_SESSION['staff_id'])) { echo json_encode(['error' => 'Auth Required']); exit; }

    $slot_id = (int)$_GET['ajax_roster'];
    try {
        $stmt = $pdo->prepare("
            SELECT b.booking_reference, b.booker_phone, b.booker_email, b.status, o.codename 
            FROM bookings b 
            LEFT JOIN operatives o ON b.operative_id = o.id 
            WHERE b.slot_id = ? AND b.status != 'cancelled'
            ORDER BY b.created_at ASC
        ");
        $stmt->execute([$slot_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo json_encode(['error' => 'DB Error']);
    }
    exit;
}
// ------------------------------------------

if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit();
}

$role = $_SESSION['staff_role'];

// --- ACCESS CONTROL ---
$show_dashboard_link = in_array($role, ['admin', 'checkin', 'redemption']);
$show_mode_selector = in_array($role, ['admin', 'checkin', 'redemption', 'station']); 

$default_mode = 'station';
if ($role == 'admin') $default_mode = 'station';
if ($role == 'checkin') $default_mode = 'child';
if ($role == 'redemption') $default_mode = 'redeem';

$current_mode = $_GET['mode'] ?? $default_mode;

// --- GEOLOCATION SAVE ---
if (isset($_POST['booth_name']) && isset($_POST['booth_lat'])) {
    $_SESSION['booth_name'] = htmlspecialchars($_POST['booth_name']);
    $_SESSION['booth_lat'] = htmlspecialchars($_POST['booth_lat']);
    $_SESSION['booth_lon'] = htmlspecialchars($_POST['booth_lon']);
}
$booth_name = $_SESSION['booth_name'] ?? 'Unassigned';

// --- VISUALS ---
$mode_text = [
    'station' => 'STAMPING MODE',
    'redeem' => 'PRIZE REDEMPTION',
    'child' => 'CHILD AUTH MODE',
    'bp_sale' => 'BATTLE PASS SALES',
    'bp_redeem' => 'BP REWARD PICKUP',
    'event_checkin' => 'EVENT CHECK-IN'
];
$mode_color = [
    'station' => 'gold',
    'redeem' => 'lime', 
    'child' => 'lightblue',
    'bp_sale' => '#E1306C', 
    'bp_redeem' => '#E60012',
    'event_checkin' => '#bf00ff' 
];

// --- UPCOMING EVENTS ---
$upcoming_events = [];
try {
    date_default_timezone_set('America/Los_Angeles');
    $stmt = $pdo->prepare("SELECT id, activity_name, start_time, capacity, 
        (SELECT COUNT(*) FROM bookings WHERE slot_id = event_slots.id AND status != 'cancelled') as booked 
        FROM event_slots 
        WHERE start_time >= NOW() 
        ORDER BY start_time ASC 
        LIMIT 3");
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mission Control | Staff</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=32">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        #reader { width: 100%; border: 4px solid var(--current-border-color, #ff4d4d); border-radius: 8px; margin-bottom: 20px; background: black; min-height: 300px;}
        #result-box { padding: 20px; text-align: center; font-family: 'Cinzel', serif; font-weight: bold; font-size: 1.5rem; display: none; margin-bottom: 20px; }
        .res-success { background: #00d26a; color: black; }
        .res-warning { background: #d4af37; color: black; }
        .res-error { background: #e60012; color: white; }
        .mode-selector { background: #333; border: 2px solid var(--current-border-color, #ff4d4d); padding: 10px; margin-bottom: 15px; text-align: center; font-size: 0.9rem; }
        
        #inventory-alert { display: none; background: #ffcc00; color: black; padding: 15px; font-weight: bold; text-align: center; border: 3px solid red; margin-bottom: 15px; border-radius: 8px; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }

        .upcoming-list { margin-top: 30px; text-align: left; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 8px; border: 1px solid #444; }
        .event-row { display: flex; justify-content: space-between; border-bottom: 1px dashed #333; padding: 10px 0; font-size: 0.85rem; color: #ccc; cursor: pointer; }
        .event-row:hover { background: rgba(255,255,255,0.1); }
        .event-time { color: var(--velvet-gold); font-weight: bold; min-width: 60px; }
        .event-capacity { color: #aaa; font-size: 0.75rem; text-align: right; min-width: 40px; }

        /* MANUAL INPUT STYLES */
        .manual-input-area { display: flex; gap: 5px; margin-bottom: 20px; }
        .manual-input { flex: 1; background: #000; border: 1px solid #666; color: white; padding: 12px; font-size: 1rem; border-radius: 4px; }
        .manual-btn { background: #444; color: white; border: 1px solid #aaa; padding: 0 20px; font-weight: bold; border-radius: 4px; cursor: pointer; }

        /* BOTTOM SHEET MODAL STYLES */
        .modal-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.8); z-index: 9999; display: none; 
            align-items: flex-end; /* Align to bottom */
            justify-content: center;
        }
        .modal-content { 
            width: 100%; max-width: 600px; 
            background: #111; 
            border-top: 2px solid var(--velvet-gold); 
            border-radius: 12px 12px 0 0; /* Rounded top corners */
            padding: 20px; 
            box-shadow: 0 -5px 20px rgba(0,0,0,0.8); 
            max-height: 80vh; 
            display: flex; flex-direction: column; 
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #444; padding-bottom: 15px; margin-bottom: 10px; }
        .modal-title { color: var(--velvet-gold); font-family: 'Cinzel', serif; font-size: 1.1rem; }
        .close-btn { color: #fff; font-size: 2rem; cursor: pointer; line-height: 1; padding: 0 10px; }
        .roster-list { overflow-y: auto; flex: 1; padding-bottom: 20px; }
        .roster-item { background: rgba(255,255,255,0.05); padding: 12px; margin-bottom: 8px; border-radius: 4px; font-size: 0.9rem; border-left: 3px solid #666; }
        .roster-item.checked-in { border-left-color: #00d26a; opacity: 0.5; }
        .r-ref { font-family: monospace; color: var(--velvet-gold); font-weight: bold; float: right; font-size: 0.8rem; }
        .r-name { color: #fff; display: block; font-weight: bold; font-size: 1rem; margin-bottom: 2px; }
        .r-contact { font-size: 0.8rem; color: #aaa; }
    </style>
</head>
<body style="--current-border-color: <?php echo $mode_color[$current_mode] ?? '#ff4d4d'; ?>;">

    <div class="container">
        <div class="profile-header" style="border-color: var(--current-border-color, #ff4d4d);">
            <p style="color: var(--current-border-color, #ff4d4d); font-size: 0.8rem;">LOGGED IN AS:</p>
            <h1><?php echo htmlspecialchars($_SESSION['staff_name']); ?></h1>
            <p style="font-size: 0.8rem; opacity: 0.7;">ROLE: <?php echo strtoupper($role); ?></p>
        </div>
        
        <?php if($show_dashboard_link): ?>
            <a href="admin_dashboard.php" style="display: block; width: 100%; padding: 12px; background: #333; color: var(--velvet-gold); text-decoration: none; border: 1px solid var(--velvet-gold); text-align: center; margin-bottom: 20px; font-weight: bold; box-sizing: border-box;">
                <i class="fa-solid fa-gauge-high"></i> OPEN DATA DASHBOARD
            </a>
        <?php endif; ?>
        
        <?php if($show_mode_selector): ?>
            <div class="mode-selector">
                <label for="mode_select" style="color: var(--current-border-color, #ff4d4d); margin-bottom: 5px;">MISSION MODE:</label>
                <select id="mode_select" onchange="window.location.href = 'scanner.php?mode=' + this.value"
                    style="background: transparent; border: 1px solid var(--current-border-color, #ff4d4d); color: white; padding: 5px; width: 90%;">
                    
                    <?php if($role == 'admin'): ?>
                        <option value="station" <?php echo ($current_mode == 'station') ? 'selected' : ''; ?>>Stamp (Station)</option>
                        <option value="event_checkin" <?php echo ($current_mode == 'event_checkin') ? 'selected' : ''; ?>>Event Check-In (Booking)</option>
                        <option value="redeem" <?php echo ($current_mode == 'redeem') ? 'selected' : ''; ?>>Redeem Standard (Scan ID)</option>
                        <option value="child" <?php echo ($current_mode == 'child') ? 'selected' : ''; ?>>Child (Auth)</option>
                        <option value="bp_sale" <?php echo ($current_mode == 'bp_sale') ? 'selected' : ''; ?>>Battle Pass (SELL)</option>
                        <option value="bp_redeem" <?php echo ($current_mode == 'bp_redeem') ? 'selected' : ''; ?>>Battle Pass Pickup (Scan ID)</option>
                    
                    <?php elseif($role == 'checkin'): ?>
                        <option value="child" <?php echo ($current_mode == 'child') ? 'selected' : ''; ?>>Child (Auth)</option>
                        <option value="bp_sale" <?php echo ($current_mode == 'bp_sale') ? 'selected' : ''; ?>>Battle Pass (SELL)</option>
                    
                    <?php elseif($role == 'redemption'): ?>
                        <option value="redeem" <?php echo ($current_mode == 'redeem') ? 'selected' : ''; ?>>Redeem Standard (Scan ID)</option>
                        <option value="bp_sale" <?php echo ($current_mode == 'bp_sale') ? 'selected' : ''; ?>>Battle Pass (SELL)</option> 
                        <option value="bp_redeem" <?php echo ($current_mode == 'bp_redeem') ? 'selected' : ''; ?>>Battle Pass Pickup (Scan ID)</option>

                    <?php elseif($role == 'station'): ?>
                        <option value="station" <?php echo ($current_mode == 'station') ? 'selected' : ''; ?>>Stamp (Station)</option>
                        <option value="event_checkin" <?php echo ($current_mode == 'event_checkin') ? 'selected' : ''; ?>>Event Check-In (Booking)</option>
                    <?php endif; ?>

                </select>
                
                <br><br>
                <label for="camera_select" style="color: white; font-size: 0.8rem;">CAMERA SOURCE:</label>
                <select id="camera_select" style="background: black; color: white; border: 1px solid #666; padding: 5px; width: 90%; margin-top: 5px;">
                    <option value="environment" selected>Auto (Back)</option>
                </select>
            </div>
        <?php endif; ?>

        <?php if (empty($booth_name)): ?>
        <div class="mode-selector" style="background: #2A3258; border-color: yellow;">
            <p style="color: yellow; margin-bottom: 5px;">SETUP REQUIRED:</p>
            <form method="POST" id="locationForm">
                <input type="text" name="booth_name" placeholder="Enter Booth Name (e.g., A-12)" required style="width: 90%; text-align: left; box-sizing: border-box;">
                <input type="hidden" name="booth_lat" id="booth_lat">
                <input type="hidden" name="booth_lon" id="booth_lon">
                <button type="submit" class="btn-gold" style="width: 90%; margin-top: 10px; background: yellow; color: black;">Submit Location</button>
            </form>
        </div>
        <?php endif; ?>

        <h3 style="color: var(--current-border-color, #ff4d4d); margin-top: 0;"><?php echo $mode_text[$current_mode] ?? 'UNKNOWN MODE'; ?></h3>
        <p style="font-size: 0.8rem; margin-top: -15px; margin-bottom: 15px; color: yellow;">
            STATION: <?php echo htmlspecialchars($booth_name); ?>
        </p>

        <div id="inventory-alert"></div>

        <div id="result-box">Waiting...</div>
        <div id="reader"></div>
        
        <div class="manual-input-area">
            <input type="text" id="manualInput" class="manual-input" placeholder="Scan Failed? Enter Codename or ID">
            <button class="manual-btn" onclick="submitManual()">GO</button>
        </div>

        <button onclick="initCameraSource()" class="btn-gold" style="margin-top: 15px; background: #444; border: 1px solid #aaa; color: white; font-size: 0.8rem;">
            <i class="fa-solid fa-camera"></i> Manual Camera Permission
        </button>
        
        <?php if (!empty($upcoming_events)): ?>
            <div class="upcoming-list">
                <p style="color: #fff; font-size: 0.9rem; border-bottom: 1px solid #666; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold;">UPCOMING SESSIONS (Tap to View Roster)</p>
                <?php foreach($upcoming_events as $evt): ?>
                    <div class="event-row" onclick="showRoster(<?php echo $evt['id']; ?>, '<?php echo addslashes($evt['activity_name']); ?>')">
                        <span class="event-time"><?php echo date('g:i A', strtotime($evt['start_time'])); ?></span>
                        <span style="flex:1; padding: 0 10px;"><?php echo htmlspecialchars($evt['activity_name']); ?></span>
                        <span class="event-capacity"><?php echo $evt['booked'] . '/' . $evt['capacity']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="staff_login.php" style="color: #666;">Logout</a>
    </div>

    <div id="roster-modal" class="modal-overlay" onclick="if(event.target === this) document.getElementById('roster-modal').style.display='none'">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modal-title" class="modal-title">Roster</span>
                <span class="close-btn" onclick="document.getElementById('roster-modal').style.display='none'">&times;</span>
            </div>
            <div id="roster-list-container" class="roster-list">
                <p style="text-align:center; color:#888;">Loading...</p>
            </div>
        </div>
    </div>

    <script>
        const resultBox = document.getElementById('result-box');
        const alertBox = document.getElementById('inventory-alert');
        const cameraSelect = document.getElementById('camera_select');
        const html5QrCode = new Html5Qrcode("reader");
        
        let isScanning = true;
        const currentMode = '<?php echo $current_mode; ?>'; 
        let staffLat = '';
        let staffLon = '';
        let currentCameraId = null;

        function submitManual() {
            const val = document.getElementById('manualInput').value;
            if(!val) return;
            // Send empty coordinates for manual entry
            sendScanRequest(val, null, null);
            document.getElementById('manualInput').value = '';
        }

        function showRoster(slotId, eventName) {
            document.getElementById('modal-title').innerText = eventName;
            document.getElementById('roster-list-container').innerHTML = "<p style='text-align:center; color:#888;'>Fetching data...</p>";
            document.getElementById('roster-modal').style.display = 'flex';

            fetch('scanner.php?ajax_roster=' + slotId)
                .then(response => response.json())
                .then(data => {
                    const listDiv = document.getElementById('roster-list-container');
                    listDiv.innerHTML = "";
                    if (data.length === 0) {
                        listDiv.innerHTML = "<p style='text-align:center; color:#888;'>No bookings found for this slot.</p>";
                        return;
                    }
                    data.forEach(guest => {
                        const div = document.createElement('div');
                        div.className = 'roster-item' + (guest.status === 'checked_in' ? ' checked-in' : '');
                        
                        const name = guest.codename ? `<span style="color:#00d26a;">${guest.codename}</span>` : 'Guest';
                        const contact = (guest.booker_phone || guest.booker_email) ? (guest.booker_phone + " " + guest.booker_email) : 'No Info';
                        
                        div.innerHTML = `
                            <span class="r-ref">${guest.booking_reference}</span>
                            <span class="r-name">${name}</span>
                            <span class="r-contact">${contact}</span>
                        `;
                        listDiv.appendChild(div);
                    });
                })
                .catch(err => {
                    document.getElementById('roster-list-container').innerHTML = "<p style='color:red;'>Error loading roster.</p>";
                });
        }

        if (navigator.geolocation && document.getElementById('locationForm')) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    staffLat = position.coords.latitude;
                    staffLon = position.coords.longitude;
                    document.getElementById('booth_lat').value = staffLat;
                    document.getElementById('booth_lon').value = staffLon;
                },
                (error) => { console.warn('GPS Error: ' + error.message); },
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 0 }
            );
        }

        function initCameraSource() {
            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    if (devices.length > 0) cameraSelect.innerHTML = "";
                    devices.forEach(device => {
                        const option = document.createElement("option");
                        option.value = device.id;
                        option.text = device.label || `Camera ${device.id}`;
                        cameraSelect.appendChild(option);
                    });
                    currentCameraId = devices[devices.length - 1].id;
                    cameraSelect.value = currentCameraId;
                    startScanning(currentCameraId);
                } else {
                    startScanning(null);
                }
            }).catch(err => {
                console.error("Camera Error", err);
                startScanning(null);
            });
        }

        initCameraSource();

        cameraSelect.addEventListener('change', (e) => {
            html5QrCode.stop().then(() => {
                currentCameraId = e.target.value;
                startScanning(currentCameraId);
            }).catch(err => console.error(err));
        });

        function startScanning(cameraId) {
            const config = { fps: 10, qrbox: 250 };
            const cameraConfig = cameraId ? { deviceId: { exact: cameraId } } : { facingMode: "environment" };
            
            html5QrCode.start(cameraConfig, config, onScanSuccess)
            .catch(err => {
                console.error("Start Failed", err);
                resultBox.innerText = "Camera Error: " + err;
                resultBox.style.display = "block";
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (!isScanning) return; 
            isScanning = false; 

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                (position) => {
                    staffLat = position.coords.latitude;
                    staffLon = position.coords.longitude;
                    sendScanRequest(decodedText, staffLat, staffLon);
                },
                (error) => { 
                    sendScanRequest(decodedText, null, null); 
                },
                { enableHighAccuracy: false, timeout: 1000, maximumAge: 60000 } 
            );
            } else {
                sendScanRequest(decodedText, null, null);
            }
        }
        
        function sendScanRequest(qrCode, lat, lon) {
            fetch('api_scan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    qr_code: qrCode, 
                    mode: currentMode,
                    lat: lat, 
                    lon: lon 
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult(data.status, data.message);
                if (data.inventory_warning) {
                    alertBox.innerText = data.inventory_warning;
                    alertBox.style.display = 'block';
                } else {
                    alertBox.style.display = 'none';
                }
                setTimeout(() => {
                    resultBox.style.display = 'none';
                    isScanning = true;
                }, 3000);
            })
            .catch(err => {
                showResult('error', 'Network Error');
                isScanning = true;
            });
        }

        function showResult(status, message) {
            resultBox.style.display = 'block';
            resultBox.className = ''; 
            resultBox.innerText = message;
            if (status === 'success') resultBox.classList.add('res-success');
            else if (status === 'warning') resultBox.classList.add('res-warning');
            else resultBox.classList.add('res-error');
        }
    </script>
</body>
</html>