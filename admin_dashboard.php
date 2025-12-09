<?php
// 1. CONFIG & AUTH
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Keep user logged in for 24 hours (86400 seconds)
$timeout = 86400; 
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);

session_start();
require 'db.php';

// Security Check: Redirect instead of DIE
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['admin', 'checkin', 'redemption'])) {
    header("Location: staff_login.php");
    exit();
}

$role = $_SESSION['staff_role'];
$is_super_admin = ($role == 'admin');
$message = "";
$modal_open = false; 

// --- 2. ACTION HANDLERS (POST) ---

// A. GRANT BATTLE PASS
if ($is_super_admin && isset($_POST['grant_bp'])) {
    $target_id = (int)$_POST['target_id'];
    $pdo->prepare("UPDATE operatives SET bp_owned = 1 WHERE id = ?")->execute([$target_id]);
    $message = "BATTLE PASS GRANTED TO OPERATIVE #$target_id";
    $_GET['view_user'] = $target_id; // Keep modal open
}

// B. REVOKE BATTLE PASS (NEW)
if ($is_super_admin && isset($_POST['revoke_bp'])) {
    $target_id = (int)$_POST['target_id'];
    $pdo->prepare("UPDATE operatives SET bp_owned = 0 WHERE id = ?")->execute([$target_id]);
    $message = "BATTLE PASS REVOKED FROM OPERATIVE #$target_id";
    $_GET['view_user'] = $target_id; // Keep modal open
}

// C. MANUAL STAMP
if ($is_super_admin && isset($_POST['manual_stamp'])) {
    $target_id = (int)$_POST['target_id'];
    $station = $_POST['station_name'];
    
    // Check if already scanned
    $check = $pdo->prepare("SELECT id FROM mission_logs WHERE operative_id = ? AND station_name = ?");
    $check->execute([$target_id, $station]);
    
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO mission_logs (operative_id, station_name, created_at) VALUES (?, ?, NOW())")->execute([$target_id, $station]);
        $pdo->prepare("UPDATE operatives SET status = 'eligible' WHERE id = ? AND status = 'active'")->execute([$target_id]);
        $message = "MANUAL STAMP '$station' APPLIED.";
    } else {
        $message = "STAMP ALREADY EXISTS.";
    }
    $_GET['view_user'] = $target_id;
}

// D. DELETE USER
if ($is_super_admin && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['target_id'];
    $pdo->prepare("DELETE FROM operatives WHERE id = ?")->execute([$target_id]);
    $message = "OPERATIVE #$target_id TERMINATED.";
    unset($_GET['view_user']); 
}

// E. INVENTORY UPDATES
if ($is_super_admin && isset($_POST['update_inventory'])) {
    if (isset($_POST['bp_limit'])) {
        $val = (int)$_POST['bp_limit'];
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('battle_pass_limit', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$val, $val]);
    }
    if (isset($_POST['std_limit'])) {
        $val = (int)$_POST['std_limit'];
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('standard_reward_limit', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$val, $val]);
    }
    $message = "INVENTORY LIMITS UPDATED.";
}

// F. WIPE SCORES
if ($is_super_admin && isset($_POST['wipe_scores'])) {
    try {
        $pdo->exec("TRUNCATE TABLE high_scores_p3");
        $pdo->exec("TRUNCATE TABLE high_scores_p5");
        $message = "LEADERBOARDS WIPED.";
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// --- 3. DATA FETCHING ---

// A. GENERAL STATS
try {
    $counts = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM operatives) AS total,
        (SELECT COUNT(*) FROM operatives WHERE role = 'operative') AS players,
        (SELECT COUNT(*) FROM operatives WHERE role = 'observer') AS observers,
        (SELECT COUNT(*) FROM operatives WHERE status = 'eligible') AS eligible,
        (SELECT COUNT(*) FROM operatives WHERE status = 'redeemed') AS redeemed,
        (SELECT COUNT(*) FROM operatives WHERE bp_owned = 1) AS bp_holders
    ")->fetch();
    
    // Inventory Data
    $bp_limit = (int)$pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'battle_pass_limit'")->fetchColumn();
    $std_limit = (int)$pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'standard_reward_limit'")->fetchColumn();
    $std_redeemed = (int)$pdo->query("SELECT COUNT(*) FROM operatives WHERE status = 'redeemed' AND bp_owned = 0")->fetchColumn();
    
    $bp_remaining = $bp_limit - $counts['bp_holders'];
    $std_remaining = $std_limit - $std_redeemed;

    // Capacity (Active 2 Hours)
    $active_window = "INTERVAL 120 MINUTE";
    $sql_capacity = "SELECT COUNT(DISTINCT o.id) FROM operatives o LEFT JOIN location_pings p ON o.id = p.operative_id WHERE (o.last_active >= NOW() - $active_window) OR (p.timestamp >= NOW() - $active_window)";
    $estimated_capacity = $pdo->query($sql_capacity)->fetchColumn();
    $projected_live = ceil($estimated_capacity * 1.3);

    // Metrics
    $device_os_counts = $pdo->query("SELECT device_os, COUNT(id) as count FROM operatives GROUP BY device_os ORDER BY count DESC")->fetchAll();
    $insta_clicks = $pdo->query("SELECT target_name, COUNT(id) as count FROM instagram_clicks WHERE link_type = 'PERFORMER' GROUP BY target_name ORDER BY count DESC LIMIT 5")->fetchAll();

} catch (Exception $e) { die("DB Error: " . $e->getMessage()); }

// B. USER LIST QUERY
$filter_status = $_GET['status'] ?? 'all';
$search_term = $_GET['search'] ?? '';
$where_clauses = []; $params = [];

if ($filter_status !== 'all') { $where_clauses[] = "status = ?"; $params[] = $filter_status; }
if ($search_term) { $where_clauses[] = "codename LIKE ?"; $params[] = '%' . $search_term . '%'; }
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// UPDATED: Added LIMIT 25
$stmt_list = $pdo->prepare("SELECT id, codename, status, role, bp_owned, last_active FROM operatives $where_sql ORDER BY created_at DESC LIMIT 25");
$stmt_list->execute($params);
$user_list = $stmt_list->fetchAll();

// --- 4. DETAIL VIEW FETCHING ---
$d_user = null; $d_scores = []; $d_logs = []; $d_booking = null; $d_shadow_status = "DID NOT SUBMIT";

if (isset($_GET['view_user'])) {
    $uid = (int)$_GET['view_user'];
    $modal_open = true;

    $stmt = $pdo->prepare("SELECT * FROM operatives WHERE id = ?");
    $stmt->execute([$uid]);
    $d_user = $stmt->fetch();

    if ($d_user) {
        // Scores
        $s3 = $pdo->prepare("SELECT score FROM high_scores_p3 WHERE operative_id = ?"); $s3->execute([$uid]);
        $d_scores['p3'] = $s3->fetchColumn() ?: 'N/A';
        
        $s5 = $pdo->prepare("SELECT score FROM high_scores_p5 WHERE operative_id = ?"); $s5->execute([$uid]);
        $d_scores['p5'] = $s5->fetchColumn() ?: 'N/A';
        
        $s4 = $pdo->prepare("SELECT id FROM high_scores_p4 WHERE operative_id = ?"); $s4->execute([$uid]);
        $d_scores['p4'] = $s4->fetch() ? 'CLEARED' : 'Incomplete';

        // FALSE SHADOW GUESS (NEW)
        $sg = $pdo->prepare("SELECT guessed_char FROM shadow_guesses WHERE operative_id = ?");
        $sg->execute([$uid]);
        $guess = $sg->fetchColumn();
        if ($guess) {
            if ($guess === 'Yukiko Amagi') {
                $d_shadow_status = "<span style='color:#00d26a'>CORRECT (Yukiko Amagi)</span>";
            } else {
                $d_shadow_status = "<span style='color:#E60012'>INCORRECT (" . htmlspecialchars($guess) . ")</span>";
            }
        }

        // Logs (Using created_at)
        $sl = $pdo->prepare("SELECT station_name, DATE_FORMAT(created_at, '%h:%i %p') as time FROM mission_logs WHERE operative_id = ? ORDER BY created_at DESC");
        $sl->execute([$uid]);
        $d_logs = $sl->fetchAll();

        // Booking
        $sb = $pdo->prepare("SELECT b.*, s.activity_name, s.start_time FROM bookings b LEFT JOIN event_slots s ON b.slot_id = s.id WHERE b.operative_id = ?");
        $sb->execute([$uid]);
        $d_booking = $sb->fetch();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Command | Velvet Room</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=50">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- DASHBOARD OVERRIDES --- */
        .compact-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .compact-table th { text-align: left; color: var(--velvet-gold); border-bottom: 1px solid #444; padding: 10px; }
        .compact-table td { padding: 12px 10px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .compact-table tr:hover { background: rgba(255,255,255,0.05); cursor: pointer; }

        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-active { background: #00d26a; box-shadow: 0 0 5px #00d26a; }
        .dot-offline { background: #444; }

        .bp-icon { color: #FFD700; text-shadow: 0 0 5px #FFD700; }
        .bp-none { color: #333; }

        /* --- FIXED MODAL STYLES (TOP ALIGN) --- */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); 
            z-index: 9999; 
            display: flex; justify-content: center; 
            align-items: flex-start; /* Align to top for better mobile view */
            padding-top: 60px; /* Add space from top */
            overflow-y: auto; /* Allow overlay to scroll */
        }
        .dossier-window {
            width: 95%; max-width: 600px; 
            background-color: #0e1638; /* Force solid color */
            border: 2px solid var(--velvet-gold);
            border-radius: 8px; 
            box-shadow: 0 0 50px rgba(0,0,0,0.9);
            display: flex; flex-direction: column;
            position: relative; 
            z-index: 10000;
            color: white;
            margin-bottom: 50px; /* Space at bottom */
        }
        .dossier-header {
            background: #1a2140;
            padding: 15px; border-bottom: 1px solid var(--velvet-gold);
            display: flex; justify-content: space-between; align-items: center;
            flex-shrink: 0;
        }
        .dossier-body { padding: 20px; }

        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .data-box { background: rgba(0,0,0,0.3); padding: 10px; border-radius: 4px; border: 1px solid #333; }
        .data-label { font-size: 0.75rem; color: #aaa; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .data-val { font-size: 1.1rem; color: white; font-weight: bold; }

        .logs-list { max-height: 150px; overflow-y: auto; border: 1px solid #444; padding: 10px; background: #000; }
        .log-item { font-size: 0.85rem; border-bottom: 1px dashed #333; padding: 5px 0; display: flex; justify-content: space-between; }
        
        .action-panel { border-top: 1px solid var(--velvet-gold); padding-top: 20px; margin-top: 20px; }
        .action-btn { width: 100%; padding: 10px; margin-bottom: 10px; cursor: pointer; font-weight: bold; text-transform: uppercase; border: 1px solid; background: transparent; }
        .btn-grant { border-color: #FFD700; color: #FFD700; }
        .btn-grant:hover { background: #FFD700; color: black; }
        .btn-nuke { border-color: #E60012; color: #E60012; }
        .btn-nuke:hover { background: #E60012; color: white; }

        /* --- ADMIN SECTIONS --- */
        .admin-section { border-top: 2px solid #333; margin-top: 40px; padding-top: 20px; }
        .section-title { font-size: 1.2rem; color: var(--velvet-gold); margin-bottom: 20px; font-weight: bold; text-transform: uppercase; }
        
        .inv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .inv-card { background: rgba(255,255,255,0.05); border: 1px solid #555; padding: 15px; border-radius: 6px; text-align: center; }
        .inv-form { display: flex; gap: 5px; margin-top: 10px; }
        .inv-form input { flex: 1; padding: 5px; background: #000; border: 1px solid #555; color: white; text-align: center; }
        .inv-form button { padding: 5px 10px; cursor: pointer; font-weight: bold; }

        .tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .tool-btn { display: block; text-align: center; padding: 15px; background: rgba(255,255,255,0.1); color: var(--velvet-gold); text-decoration: none; border: 1px solid var(--velvet-gold); border-radius: 4px; }
    </style>
</head>
<body>

    <div class="container" style="max-width: 800px;">
        
        <div class="profile-header">
            <h1>COMMAND CENTER</h1>
            <a href="scanner.php" style="color: #666;">&larr; BACK TO SCANNER</a>
        </div>

        <?php if($message): ?>
            <div class="alert" style="border-color: #00d26a; color: #00d26a; text-align:center;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-evenly; align-items: center; margin-bottom: 10px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
            <div style="text-align:center;">
                <span style="display:block; font-size:1.8rem; color:white; font-weight:bold;"><?php echo $estimated_capacity; ?></span>
                <span style="font-size:0.7rem; color:#aaa; letter-spacing:1px;">ACTIVE (2HR)</span>
            </div>
            
            <div style="width: 1px; height: 40px; background: #444;"></div>

            <div style="text-align:center;">
                <span style="display:block; font-size:1.8rem; color:#ff4d4d; font-weight:bold;"><?php echo $projected_live; ?></span>
                <span style="font-size:0.7rem; color:#aaa; letter-spacing:1px;">EST. CROWD</span>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 25px; background: rgba(255,255,255,0.05); padding: 15px 10px; border-radius: 6px;">
            <div style="text-align:center; flex: 1;">
                <span style="display:block; font-size:1.6rem; color:white; font-weight:bold; line-height: 1.2;"><?php echo $counts['total']; ?></span>
                <span style="font-size:0.8rem; color:#aaa; font-weight:bold;">TOTAL</span>
            </div>
            
            <div style="text-align:center; flex: 1; border-left: 1px solid rgba(255,255,255,0.1); border-right: 1px solid rgba(255,255,255,0.1);">
                <span style="display:block; font-size:1.6rem; color:var(--velvet-green); font-weight:bold; line-height: 1.2;"><?php echo $counts['eligible']; ?></span>
                <span style="font-size:0.8rem; color:#aaa; font-weight:bold;">ELIGIBLE</span>
            </div>
            
            <div style="text-align:center; flex: 1;">
                <span style="display:block; font-size:1.6rem; color:#FFD700; font-weight:bold; line-height: 1.2;"><?php echo $counts['bp_holders']; ?></span>
                <span style="font-size:0.8rem; color:#aaa; font-weight:bold;">VIPs</span>
            </div>
        </div>

        <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
            <input type="text" name="search" placeholder="Search Name..." value="<?php echo htmlspecialchars($search_term); ?>" style="margin:0;">
            <button type="submit" class="btn-gold" style="width: auto; padding: 10px 20px;">FILTER</button>
        </form>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>STATUS</th>
                        <th>CODENAME</th>
                        <th style="text-align:center;">BP</th>
                        <th style="text-align:right;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($user_list as $u): ?>
                        <?php 
                            $isOnSite = false;
                            if ($u['last_active']) {
                                $diff = time() - strtotime($u['last_active']);
                                if ($diff < 7200) $isOnSite = true; 
                            }
                        ?>
                        <tr onclick="window.location.href='?view_user=<?php echo $u['id']; ?>'">
                            <td>
                                <div class="status-dot <?php echo $isOnSite ? 'dot-active' : 'dot-offline'; ?>" title="<?php echo $isOnSite ? 'On Site' : 'Offline'; ?>"></div>
                                <span style="font-size:0.8rem; color: #aaa;"><?php echo strtoupper($u['status']); ?></span>
                            </td>
                            <td style="font-weight: bold; color: white;">
                                <?php echo htmlspecialchars($u['codename']); ?>
                                <?php if($u['role'] == 'observer') echo ' <small>(Obs)</small>'; ?>
                            </td>
                            <td style="text-align:center;">
                                <i class="fa-solid fa-crown <?php echo ($u['bp_owned'] == 1) ? 'bp-icon' : 'bp-none'; ?>"></i>
                            </td>
                            <td style="text-align:right;">
                                <a href="?view_user=<?php echo $u['id']; ?>" style="color: var(--velvet-gold); text-decoration: none; font-size: 0.8rem;">[VIEW]</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <a href="admin_bookings.php" class="btn-gold" style="display:block; width: 100%; box-sizing: border-box; text-align:center; margin-top:15px; margin-bottom:30px; text-decoration:none;">VIEW ALL EVENT BOOKINGS</a>

        <?php if ($is_super_admin): ?>
        <div class="admin-section">
            <div class="section-title">INVENTORY & METRICS</div>
            <div class="inv-grid">
                <div class="inv-card" style="border-color: #E1306C;">
                    <p style="margin:0 0 10px; color:#E1306C; font-weight:bold;">BATTLE PASS</p>
                    <div style="font-size:0.8rem; margin-bottom:10px;">Sold: <b><?php echo $counts['bp_holders']; ?></b> / Left: <b><?php echo $bp_remaining; ?></b></div>
                    <form method="POST" class="inv-form">
                        <input type="number" name="bp_limit" value="<?php echo $bp_limit; ?>" placeholder="Limit">
                        <button type="submit" name="update_inventory" style="background:#E1306C; color:white; border:none;">SET</button>
                    </form>
                </div>
                <div class="inv-card" style="border-color: #E60012;">
                    <p style="margin:0 0 10px; color:#E60012; font-weight:bold;">REWARDS</p>
                    <div style="font-size:0.8rem; margin-bottom:10px;">Gone: <b><?php echo $std_redeemed; ?></b> / Left: <b><?php echo $std_remaining; ?></b></div>
                    <form method="POST" class="inv-form">
                        <input type="number" name="std_limit" value="<?php echo $std_limit; ?>" placeholder="Limit">
                        <button type="submit" name="update_inventory" style="background:#E60012; color:white; border:none;">SET</button>
                    </form>
                </div>
            </div>

            <div class="card" style="animation:none; text-align:left; margin-bottom:20px;">
                <p style="font-weight:bold; color:var(--velvet-gold);">DEVICE STATS</p>
                <table class="compact-table" style="margin-bottom:20px;">
                    <?php foreach($device_os_counts as $os): ?>
                    <tr><td><?php echo $os['device_os']; ?></td><td style="text-align:right;"><?php echo $os['count']; ?></td></tr>
                    <?php endforeach; ?>
                </table>
                <p style="font-weight:bold; color:var(--velvet-gold);">TOP PERFORMERS</p>
                <table class="compact-table">
                    <?php foreach($insta_clicks as $ic): ?>
                    <tr><td><?php echo $ic['target_name']; ?></td><td style="text-align:right;"><?php echo $ic['count']; ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="section-title">SYSTEM MAINTENANCE</div>
            <div class="tools-grid">
                <a href="register_staff.php" class="tool-btn">ADD STAFF</a>
                <form method="POST" onsubmit="return confirm('WIPE ALL LEADERBOARDS?');">
                    <input type="hidden" name="wipe_scores" value="1">
                    <button class="tool-btn" style="width:100%; background:rgba(230,0,18,0.1); color:#E60012; border-color:#E60012;">WIPE SCORES</button>
                </form>
            </div>
            
            <?php 
            $ALLOWED_NUKERS = ['Ellen', 'AdminEru', 'AdminBen'];
            if(in_array($_SESSION['staff_name'] ?? '', $ALLOWED_NUKERS)): 
            ?>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="admin_reset.php" style="color: #500; text-decoration: none; font-family: monospace;">
                        <i class="fa-solid fa-skull"></i> FACTORY RESET SYSTEM
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php if ($modal_open && !empty($d_user)): ?>
    <div class="modal-overlay">
        <div class="dossier-window">
            <div class="dossier-header">
                <div>
                    <h2 style="margin:0; color:white; font-size: 1.5rem;"><?php echo htmlspecialchars($d_user['codename']); ?></h2>
                    <span style="font-size:0.8rem; color:var(--velvet-gold);">ID: #<?php echo $d_user['id']; ?> | CODE: <?php echo $d_user['rescue_code']; ?></span>
                </div>
                <a href="admin_dashboard.php" style="color:#ff4d4d; font-size:1.5rem; text-decoration:none; font-weight:bold; padding: 10px;"><i class="fa-solid fa-xmark"></i></a>
            </div>

            <div class="dossier-body">
                <div class="data-grid">
                    <div class="data-box">
                        <span class="data-label">Last Seen</span>
                        <span class="data-val" style="font-size:0.9rem;">
                            <?php echo $d_user['last_active'] ? date('g:i A', strtotime($d_user['last_active'])) : 'Never'; ?>
                        </span>
                    </div>
                    <div class="data-box">
                        <span class="data-label">Device</span>
                        <span class="data-val" style="font-size:0.9rem;"><?php echo $d_user['device_os'] ?: 'Unknown'; ?></span>
                    </div>
                    <div class="data-box">
                        <span class="data-label">Booking</span>
                        <span class="data-val" style="font-size:0.9rem; color: #00d26a;">
                            <?php echo $d_booking ? date('g:i A', strtotime($d_booking['start_time'])) : 'None'; ?>
                        </span>
                    </div>
                    <div class="data-box">
                        <span class="data-label">Battle Pass</span>
                        <span class="data-val" style="color: <?php echo ($d_user['bp_owned'])?'#FFD700':'#555'; ?>">
                            <?php echo ($d_user['bp_owned']) ? 'OWNED' : 'NONE'; ?>
                        </span>
                    </div>
                </div>

                <div class="data-box" style="margin-bottom: 20px; border-color: #9900ff;">
                    <span class="data-label">False Shadow Guess</span>
                    <div style="font-size: 1rem; font-weight: bold;">
                        <?php echo $d_shadow_status; ?>
                    </div>
                </div>

                <h3 style="font-size:1rem; border-bottom:1px solid #444; padding-bottom:5px; margin-top:20px;">MINI-GAME STATUS</h3>
                <div style="display:flex; justify-content:space-between; margin-bottom:20px; text-align:center;">
                    <div><span class="data-label">P3 Tartarus</span><span class="data-val" style="color:#2aeaff;"><?php echo $d_scores['p3']; ?></span></div>
                    <div><span class="data-label">P4 TV World</span><span class="data-val" style="color:#fefe22;"><?php echo $d_scores['p4']; ?></span></div>
                    <div><span class="data-label">P5 Mementos</span><span class="data-val" style="color:#ff2a2a;"><?php echo $d_scores['p5']; ?></span></div>
                </div>

                <h3 style="font-size:1rem; border-bottom:1px solid #444; padding-bottom:5px;">STATION LOGS</h3>
                <div class="logs-list">
                    <?php if (empty($d_logs)): ?>
                        <p style="color:#666; text-align:center; font-size:0.8rem;">No activity recorded.</p>
                    <?php else: ?>
                        <?php foreach ($d_logs as $log): ?>
                            <div class="log-item">
                                <span style="color:white;"><?php echo htmlspecialchars($log['station_name']); ?></span>
                                <span style="color:#aaa;"><?php echo $log['time']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($is_super_admin): ?>
                <div class="action-panel">
                    <p style="color:var(--velvet-silver); font-size:0.8rem; margin-bottom:10px;">ADMIN OVERRIDE CONTROLS</p>
                    
                    <?php if ($d_user['bp_owned'] == 0): ?>
                        <form method="POST">
                            <input type="hidden" name="target_id" value="<?php echo $d_user['id']; ?>">
                            <button type="submit" name="grant_bp" class="action-btn btn-grant"><i class="fa-solid fa-crown"></i> GRANT BATTLE PASS</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('REVOKE Battle Pass?');">
                            <input type="hidden" name="target_id" value="<?php echo $d_user['id']; ?>">
                            <button type="submit" name="revoke_bp" class="action-btn btn-nuke">REVOKE BATTLE PASS</button>
                        </form>
                    <?php endif; ?>

                    <form method="POST" style="display:flex; gap:10px;">
                        <input type="hidden" name="target_id" value="<?php echo $d_user['id']; ?>">
                        <select name="station_name" style="background:black; color:white; border:1px solid #666; padding:10px; flex:1;">
                            <option value="Admin Override">General Override</option>
                            <option value="P3 Station">P3 Station</option>
                            <option value="P4 Station">P4 Station</option>
                            <option value="P5 Station">P5 Station</option>
                        </select>
                        <button type="submit" name="manual_stamp" class="btn-gold" style="width:auto; font-size:0.8rem;">STAMP</button>
                    </form>

                    <form method="POST" onsubmit="return confirm('PERMANENTLY DELETE THIS USER?');" style="margin-top:20px;">
                        <input type="hidden" name="target_id" value="<?php echo $d_user['id']; ?>">
                        <input type="hidden" name="delete_user" value="1">
                        <button type="submit" class="action-btn btn-nuke">TERMINATE USER</button>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>