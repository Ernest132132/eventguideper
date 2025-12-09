<?php
session_start();
require 'db.php';

// 1. SECURITY: Only Admins can access this page
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'admin') {
    header("Location: staff_login.php");
    exit();
}

$message = "";
$msg_type = ""; 

// --- HANDLE ACTIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. CREATE NEW STAFF
    if (isset($_POST['create_staff'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        $stmt = $pdo->prepare("SELECT id FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $message = "Error: Username '$username' already exists.";
            $msg_type = "error";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO staff (username, password_hash, role) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hash, $role])) {
                $message = "Success! Staff member '$username' created.";
                $msg_type = "success";
            } else {
                $message = "Database error.";
                $msg_type = "error";
            }
        }
    }

    // B. RESET PASSWORD
    if (isset($_POST['reset_password'])) {
        $id = (int)$_POST['staff_id'];
        $new_pass = trim($_POST['new_pass']);
        
        if (!empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE staff SET password_hash = ? WHERE id = ?")->execute([$hash, $id]);
            $message = "Password updated successfully.";
            $msg_type = "success";
        } else {
            $message = "Password cannot be empty.";
            $msg_type = "error";
        }
    }

    // C. UPDATE ROLE
    if (isset($_POST['update_role'])) {
        $id = (int)$_POST['staff_id'];
        $new_role = $_POST['role_select'];

        if ($id == $_SESSION['staff_id']) {
            $message = "Safety Protocol: You cannot change your own role.";
            $msg_type = "error";
        } else {
            $pdo->prepare("UPDATE staff SET role = ? WHERE id = ?")->execute([$new_role, $id]);
            $message = "Staff role updated successfully.";
            $msg_type = "success";
        }
    }

    // D. DELETE STAFF
    if (isset($_POST['delete_staff'])) {
        $id = (int)$_POST['staff_id'];
        
        if ($id == $_SESSION['staff_id']) {
            $message = "You cannot delete your own account.";
            $msg_type = "error";
        } else {
            $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$id]);
            $message = "Staff member deleted.";
            $msg_type = "success";
        }
    }
}

// --- FETCH AND CATEGORIZE STAFF ---
$admins = $pdo->query("SELECT * FROM staff WHERE role = 'admin' ORDER BY username")->fetchAll();
$stations = $pdo->query("SELECT * FROM staff WHERE role = 'station' ORDER BY username")->fetchAll();
$redemptions = $pdo->query("SELECT * FROM staff WHERE role NOT IN ('admin', 'station') ORDER BY role, username")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Management | Admin Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=19">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- Page Layout & Typography --- */
        .staff-section { margin-bottom: 40px; }
        .section-label { 
            color: var(--velvet-gold); 
            font-family: 'Cinzel', serif; 
            border-bottom: 1px solid #333; 
            padding-bottom: 8px; 
            margin-bottom: 15px;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        /* --- Staff Card Container --- */
        .staff-card {
            background: rgba(255,255,255,0.03); 
            padding: 15px;
            border: 1px solid #444; 
            margin-bottom: 15px; 
            border-radius: 6px;
            display: flex;
            flex-direction: row; /* Default Desktop */
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            transition: border-color 0.3s ease;
        }
        
        .staff-card:hover { border-color: #666; }

        .staff-info { 
            flex: 0 0 auto;
            min-width: 150px; 
        }
        .staff-name { 
            font-weight: bold; 
            font-size: 1.15rem; 
            color: white; 
            display: block; 
            margin-bottom: 2px;
        }
        .staff-role-label { 
            font-size: 0.75rem; 
            color: #aaa; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }

        /* --- Action Controls Form --- */
        .staff-actions { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            flex-wrap: wrap;
            justify-content: flex-end;
            flex: 1;
        }

        /* Separator line on desktop */
        .desktop-separator {
            border-left: 1px solid #444; 
            height: 30px; 
            margin: 0 5px;
        }

        /* Inputs */
        .pass-input, .role-select, .create-input, .create-select {
            background: #000; 
            border: 1px solid #555; 
            color: white;
            padding: 10px; 
            font-size: 0.9rem;
            border-radius: 4px;
        }
        
        .role-select { border-color: var(--velvet-gold); }
        .pass-input { width: 120px; text-align: center; }

        /* Buttons */
        .action-btn {
            padding: 10px 14px; 
            cursor: pointer; 
            border: none; 
            font-weight: bold; 
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-update { background: var(--velvet-gold); color: black; }
        .btn-save { background: #2A3258; color: white; border: 1px solid var(--velvet-gold); }
        .btn-delete { background: rgba(230,0,18,0.1); border: 1px solid #E60012; color: #E60012; }
        .btn-create { background: #00d26a; color: black; font-weight: 800; border: none; padding: 12px; }

        /* --- Create Form Layout --- */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        /* --- MOBILE RESPONSIVENESS (Max-Width 768px) --- */
        @media (max-width: 768px) {
            .container { padding: 15px; width: 100%; box-sizing: border-box; }
            
            /* Stack the Create Form */
            .form-row { grid-template-columns: 1fr; }
            
            /* Stack the Staff Card */
            .staff-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px 15px;
            }
            
            .staff-info {
                width: 100%;
                margin-bottom: 15px;
                border-bottom: 1px solid #333;
                padding-bottom: 10px;
            }

            /* Organize Actions into a Grid for evenness */
            .staff-actions {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr auto; /* Input takes space, button takes minimal */
                gap: 10px;
            }

            /* Hide the vertical separator on mobile */
            .desktop-separator { display: none; }

            /* Make specific elements span full rows if needed */
            .role-select { grid-column: 1 / 2; }
            .btn-save { grid-column: 2 / 3; }
            
            .pass-input { grid-column: 1 / 2; width: 100%; box-sizing: border-box; }
            .btn-update { grid-column: 2 / 3; }

            /* Delete button takes full width at bottom */
            .btn-delete { grid-column: 1 / -1; margin-top: 5px; }
        }

        /* Alert Styles */
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
        .alert-success { border-color: #00d26a; color: #00d26a; background: rgba(0, 210, 106, 0.1); }
        .alert-error { border-color: #E60012; color: #E60012; background: rgba(230, 0, 18, 0.1); }
    </style>
</head>
<body>
    
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible" style="max-width: 900px; margin: 0 auto;">
        
        <div class="profile-header">
            <h1>STAFF MANAGER</h1>
            <a href="admin_dashboard.php" style="color: #666; text-decoration: none;">&larr; Back to Command</a>
        </div>

        <?php if($message): ?>
            <div class="alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="border-color: #00d26a; margin-bottom: 40px;">
            <h2 style="color: #00d26a; margin-top: 0;">REGISTER STAFF</h2>
            <form method="POST" style="text-align: left;">
                <input type="hidden" name="create_staff" value="1">
                
                <div class="form-row">
                    <div>
                        <label style="display:block; margin-bottom:5px; color:#aaa;">Username</label>
                        <input type="text" name="username" class="create-input" required autocomplete="off" style="width: 100%; box-sizing: border-box;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; color:#aaa;">Password</label>
                        <input type="text" name="password" class="create-input" required autocomplete="off" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>
                
                <label style="display:block; margin-bottom:5px; color:#aaa;">Clearance Level</label>
                <select name="role" class="create-select" style="width: 100%; box-sizing: border-box; margin-bottom: 20px; border-color: #00d26a;">
                    <option value="station">Station (Gives Stamps)</option>
                    <option value="redemption">Redemption (Gives Prizes)</option>
                    <option value="checkin">Check-In (Door)</option>
                    <option value="admin">Admin (Full Access)</option>
                </select>
                
                <button type="submit" class="action-btn btn-create" style="width: 100%;">
                    CREATE ACCOUNT
                </button>
            </form>
        </div>

        <?php 
        function renderStaffRow($s, $color) {
            $isMe = ($s['id'] == $_SESSION['staff_id']);
            ?>
            <div class="staff-card">
                <div class="staff-info">
                    <span class="staff-name"><?php echo htmlspecialchars($s['username']); ?></span>
                    <span class="staff-role-label" style="color: <?php echo $color; ?>;"><?php echo strtoupper($s['role']); ?></span>
                </div>
                
                <form method="POST" class="staff-actions">
                    <input type="hidden" name="staff_id" value="<?php echo $s['id']; ?>">
                    
                    <select name="role_select" class="role-select">
                        <option value="admin" <?php if($s['role']=='admin') echo 'selected'; ?>>Admin</option>
                        <option value="station" <?php if($s['role']=='station') echo 'selected'; ?>>Station</option>
                        <option value="redemption" <?php if($s['role']=='redemption') echo 'selected'; ?>>Redemption</option>
                        <option value="checkin" <?php if($s['role']=='checkin') echo 'selected'; ?>>Check-In</option>
                    </select>
                    <button type="submit" name="update_role" class="action-btn btn-save" title="Save Role">
                        <i class="fa-solid fa-floppy-disk"></i>
                    </button>

                    <span class="desktop-separator"></span>

                    <input type="text" name="new_pass" placeholder="New Pass" class="pass-input">
                    <button type="submit" name="reset_password" class="action-btn btn-update" title="Reset Password">
                        <i class="fa-solid fa-key"></i>
                    </button>
                    
                    <?php if(!$isMe): ?>
                        <button type="submit" name="delete_staff" class="action-btn btn-delete" onclick="return confirm('Delete <?php echo $s['username']; ?>?');" title="Delete User">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    <?php else: ?>
                        <div style="display:none;"></div> 
                    <?php endif; ?>
                </form>
            </div>
            <?php
        }
        ?>

        <div class="staff-section">
            <div class="section-label">ADMINISTRATORS</div>
            <?php foreach($admins as $s) renderStaffRow($s, '#FFD700'); ?>
        </div>

        <div class="staff-section">
            <div class="section-label">STATION AGENTS</div>
            <?php if(empty($stations)) echo "<p style='color:#666; font-style:italic;'>No station staff assigned.</p>"; ?>
            <?php foreach($stations as $s) renderStaffRow($s, '#2aeaff'); ?>
        </div>

        <div class="staff-section">
            <div class="section-label">OTHER ROLES</div>
            <?php if(empty($redemptions)) echo "<p style='color:#666; font-style:italic;'>No other staff assigned.</p>"; ?>
            <?php foreach($redemptions as $s) renderStaffRow($s, '#E60012'); ?>
        </div>

    </div>
</body>
</html>