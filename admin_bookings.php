<?php
session_start();
require 'db.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['staff_role']) || !in_array($_SESSION['staff_role'], ['admin', 'checkin', 'redemption'])) {
    header("Location: staff_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    // Delete the booking to free up capacity in reserve.php
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    echo "<div class='alert' style='background: #00d26a; color: black; text-align: center;'>Booking Deleted. Slot capacity updated.</div>";
}

// 2. FETCH DATA & HANDLE SEARCH
$search = $_GET['search'] ?? '';

try {
    // Get All Event Slots
    $slots_stmt = $pdo->query("SELECT id, activity_name, start_time, capacity FROM event_slots ORDER BY start_time ASC");
    $slots = $slots_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build Query
    $sql = "SELECT 
                b.id, 
                b.slot_id, 
                b.booking_reference, 
                b.booker_email, 
                b.booker_phone, 
                b.operative_id,
                o.codename
            FROM bookings b
            LEFT JOIN operatives o ON b.operative_id = o.id";
    
    $params = [];

    // Apply Search Filter if present
    if (!empty($search)) {
        $sql .= " WHERE b.booking_reference LIKE ? OR b.booker_phone LIKE ? OR b.booker_email LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY b.created_at ASC";

    $bookings_stmt = $pdo->prepare($sql);
    $bookings_stmt->execute($params);
    $all_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group Bookings by Slot ID
    $roster = [];
    foreach ($all_bookings as $b) {
        $roster[$b['slot_id']][] = $b;
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Roster | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=50">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- ROSTER SPECIFIC STYLES --- */
        .event-group {
            margin-bottom: 15px;
            border: 1px solid #444;
            background: rgba(255,255,255,0.02);
            border-radius: 4px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .event-group[open] {
            border-color: var(--velvet-gold);
        }

        summary {
            padding: 15px;
            background: rgba(0,0,0,0.6);
            cursor: pointer;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            list-style: none;
            color: var(--velvet-gold);
            user-select: none;
        }
        summary:hover { background: rgba(212, 175, 55, 0.1); }
        summary::-webkit-details-marker { display: none; }

        .event-info { display: flex; align-items: center; gap: 10px; text-align: left; }
        .event-time { color: #fff; font-family: 'Cinzel', serif; white-space: nowrap; }
        .event-name { line-height: 1.2; font-size: 0.95rem; }

        .capacity-badge {
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 12px;
            background: #333;
            color: #aaa;
            white-space: nowrap;
            margin-left: 10px;
        }
        .full-badge { background: #E60012; color: white; }

        /* --- TABLE STYLES --- */
        .roster-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            background: #0a0a0a;
        }
        
        .roster-table th {
            text-align: left;
            color: #666;
            padding: 12px 15px;
            border-bottom: 1px solid #333;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .roster-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #222;
            color: #ddd;
            vertical-align: top;
        }
        
        /* Status Badges */
        .status-linked {
            color: #00d26a; border: 1px solid #00d26a;
            padding: 2px 6px; border-radius: 3px;
            font-size: 0.7rem; text-transform: uppercase; font-weight: bold;
            display: inline-block;
        }
        .status-unlinked {
            color: #666; border: 1px solid #666;
            padding: 2px 6px; border-radius: 3px;
            font-size: 0.7rem; text-transform: uppercase; font-weight: bold;
            display: inline-block;
        }
        
        .ref-id { font-family: monospace; color: var(--velvet-gold); font-size: 1rem; }
        .contact-info { display: block; opacity: 0.9; margin-bottom: 3px; font-size: 0.85rem; }
        .contact-info i { width: 15px; color: #888; text-align: center; margin-right: 5px; }

        /* Linked operative name style */
        .linked-operative {
            margin-top: 6px; 
            padding-top: 6px;
            border-top: 1px dashed #333;
            color: #00d26a; 
            font-weight: bold; 
            font-size: 0.9rem;
            display: block;
        }

        /* --- MOBILE OPTIMIZATIONS --- */
        @media (max-width: 600px) {
            .roster-table thead { display: none; }
            .roster-table, .roster-table tbody, .roster-table tr, .roster-table td {
                display: block; width: 100%; box-sizing: border-box;
            }
            .roster-table tr {
                position: relative; margin-bottom: 10px; border-bottom: 1px solid #333;
                padding: 15px; background: rgba(255,255,255,0.02);
            }
            .roster-table td { padding: 5px 0; border: none; }
            .roster-table td:first-child {
                margin-bottom: 8px; font-size: 1.1rem; border-bottom: 1px dashed #333; padding-bottom: 8px;
            }
            .roster-table td:last-child {
                position: absolute; top: 15px; right: 15px; text-align: right; padding: 0;
            }
            .event-info { flex-direction: column; align-items: flex-start; gap: 2px; }
            .event-time { font-size: 0.8rem; color: var(--velvet-gold); }
            .event-name { font-size: 1rem; }
        }
    </style>
</head>
<body>

    <div class="container" style="max-width: 800px;">
        
        <div class="profile-header">
            <h1>EVENT BOOKINGS</h1>
            <a href="admin_dashboard.php" style="color: #666;">&larr; Return to Dashboard</a>
        </div>

        <form method="GET" style="margin-bottom: 25px; display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Search Ref ID, Email, or Phone..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 12px; background: rgba(0,0,0,0.5); border: 1px solid var(--velvet-gold); color: white; border-radius: 4px;">
            <button type="submit" class="btn-gold" style="width: auto; padding: 0 25px;"><i class="fa-solid fa-search"></i></button>
            <?php if(!empty($search)): ?>
                <a href="admin_bookings.php" class="btn-gold" style="display:flex; align-items:center; background: #E60012; border-color: #E60012; color: white; text-decoration: none; padding: 0 15px;">X</a>
            <?php endif; ?>
        </form>

        <div class="card" style="animation: none; text-align: left; padding: 0; background: transparent; border: none;">
            
            <?php if (empty($slots)): ?>
                <p style="text-align:center; padding: 20px; color: #888;">No event slots configured.</p>
            <?php else: ?>
                
                <?php 
                $has_results = false;
                foreach ($slots as $slot): 
                    $slot_id = $slot['id'];
                    $slot_bookings = $roster[$slot_id] ?? [];
                    $count = count($slot_bookings);
                    $capacity = $slot['capacity'];
                    $is_full = ($count >= $capacity);
                    
                    // IF SEARCHING: SKIP SLOTS WITH NO MATCHING BOOKINGS
                    if (!empty($search) && $count === 0) continue;
                    
                    $has_results = true;
                    $dateObj = new DateTime($slot['start_time']);
                    $timeDisplay = $dateObj->format('g:i A');
                    
                    // AUTO-OPEN IF SEARCHING
                    $openAttr = !empty($search) ? "open" : "";
                ?>

                    <details class="event-group" <?php echo $openAttr; ?>>
                        <summary>
                            <div class="event-info">
                                <span class="event-time"><?php echo $timeDisplay; ?></span>
                                <span class="event-name"><?php echo htmlspecialchars($slot['activity_name']); ?></span>
                            </div>
                            <span class="capacity-badge <?php echo $is_full ? 'full-badge' : ''; ?>">
                                <?php echo "$count / $capacity"; ?>
                            </span>
                        </summary>
                        
                        <?php if ($count > 0): ?>
                            <table class="roster-table">
                                <thead>
                                <tr>
                                    <th style="width: 20%;">Reference</th>
                                    <th style="width: 50%;">Guest Details</th>
                                    <th style="width: 20%; text-align: right;">Status</th>
                                    <th style="width: 10%;">Action</th> </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slot_bookings as $guest): ?>
                                        <tr>
                                            <td>
                                                <span class="ref-id"><?php echo htmlspecialchars($guest['booking_reference']); ?></span>
                                            </td>

                                            <td>
                                                <?php if($guest['booker_phone']): ?>
                                                    <span class="contact-info">
                                                        <i class="fa-solid fa-phone"></i> 
                                                        <a href="tel:<?php echo htmlspecialchars($guest['booker_phone']); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($guest['booker_phone']); ?></a>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if($guest['booker_email']): ?>
                                                    <span class="contact-info">
                                                        <i class="fa-solid fa-envelope"></i> 
                                                        <a href="mailto:<?php echo htmlspecialchars($guest['booker_email']); ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($guest['booker_email']); ?></a>
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($guest['operative_id'])): ?>
                                                    <span class="linked-operative">
                                                        <i class="fa-solid fa-user-check"></i> 
                                                        <?php echo htmlspecialchars($guest['codename']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <td style="text-align: right;">
                                                <?php if (!empty($guest['operative_id'])): ?>
                                                    <span class="status-linked">LINKED</span>
                                                <?php else: ?>
                                                    <span class="status-unlinked">UNLINKED</span>
                                                <?php endif; ?>
                                            </td>

                                            <td style="text-align: right;">
                                                <form method="POST" onsubmit="return confirm('WARNING: This will delete the booking and free up the slot. Continue?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $guest['id']; ?>">
                                                    <button type="submit" name="delete_booking" style="background: transparent; border: none; cursor: pointer; color: #E60012;">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div style="padding: 20px; text-align: center; color: #666; font-size: 0.9rem; font-style: italic;">
                                No reservations yet.
                            </div>
                        <?php endif; ?>
                    </details>

                <?php endforeach; ?>
                
                <?php if (!$has_results && !empty($search)): ?>
                    <div style="text-align:center; color:#aaa; margin-top:30px;">
                        <i class="fa-solid fa-circle-exclamation" style="font-size:2rem; margin-bottom:10px; color:#E60012;"></i>
                        <p>No bookings found matching "<b><?php echo htmlspecialchars($search); ?></b>"</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
        <br><br>
    </div>

</body>
</html>