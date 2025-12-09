<?php
require_once 'db.php';

function loginUser($pdo, $codename, $role) {
    // 1. Prepare Data
    $role = ($role === 'observer') ? 'observer' : 'operative'; 
    $codename = trim(htmlspecialchars($codename));

    // --- OBSERVER PATH (No Contract) ---
    if ($role === 'observer') {
        $codename = "Visitor-" . rand(1000, 9999);
        $status = 'active'; // Observers start as active (skip contract)
    } 
    // --- OPERATIVE PATH (Must Sign Contract) ---
    else {
        if (empty($codename)) return "Please sign your name.";
        $status = 'pending'; // <--- THIS IS THE KEY FIX
        
        // Check if user exists (Login logic)
        $stmt = $pdo->prepare("SELECT * FROM operatives WHERE codename = ?");
        $stmt->execute([$codename]);
        $user = $stmt->fetch();

        if ($user) {
            // Cookie Check
            if (isset($_COOKIE['auth_token']) && $_COOKIE['auth_token'] === $user['auth_token']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['codename'] = $user['codename'];
                $_SESSION['role'] = $user['role']; 
                
                // Redirect based on current status
                if ($user['status'] === 'pending') {
                    header("Location: contract.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                return "Codename taken. Use 'Recover ID' if this is you.";
            }
        }
    }

    // --- CREATE NEW USER ---
    $authToken = bin2hex(random_bytes(32));
    $rescueCode = rand(1000, 9999);
    $os = $_POST['device_os'] ?? 'Unknown';
    $agent = $_POST['user_agent'] ?? 'Unknown';
    $load_time = $_POST['load_time'] ?? 0;

    $sql = "INSERT INTO operatives (codename, role, status, auth_token, rescue_code, device_os, user_agent, initial_load_ms) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$codename, $role, $status, $authToken, $rescueCode, $os, $agent, $load_time]);
        $newId = $pdo->lastInsertId();

        setcookie("auth_token", $authToken, time() + (86400 * 30), "/");
        $_SESSION['user_id'] = $newId;
        $_SESSION['codename'] = $codename;
        $_SESSION['role'] = $role;
        $_SESSION['new_user_rescue'] = $rescueCode; 

        // ROUTING LOGIC
        if ($role === 'operative') {
            header("Location: contract.php"); // Go sign contract
        } else {
            header("Location: home.php"); // Go to dashboard
        }
        exit();

    } catch (Exception $e) {
        return "System Error: " . $e->getMessage();
    }
}
?>