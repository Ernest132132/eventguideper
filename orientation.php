<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT role FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Safety: Observers shouldn't really be here, but if they are, just let them through to home
if ($user['role'] === 'observer') {
    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Orientation | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=39"> 
    <style>
        .orientation-list {
            list-style: none; padding: 0; margin: 0;
            font-size: 0.95rem; color: #ccc; line-height: 1.6; text-align: left;
        }
        .orientation-list li {
            margin-bottom: 20px;
            position: relative;
            padding-left: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        .orientation-list li:last-child { border-bottom: none; }
        .orientation-list li::before {
            content: '♦'; color: var(--velvet-red);
            position: absolute; left: 0; top: 0;
        }
        strong { color: var(--velvet-gold); }
    </style>
</head>
<body>
    
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>

    <div class="container page-visible">
        <br>
        <h1 style="color: var(--velvet-gold); font-family: 'Cinzel', serif; font-size: 1.5rem; text-shadow: 0 0 10px rgba(212, 175, 55, 0.5);">
            ORIENTATION
        </h1>
        
        <div class="card" style="border-color: var(--velvet-gold);">
            
            <p style="font-style: italic; color: #fff; margin-bottom: 30px; opacity: 0.9;">
                Welcome to <strong>Portal to the Velvet Room: All-Out Holiday</strong>. This app is your digital companion for the event.
            </p>

            <ul class="orientation-list">
                <li>
                    <strong>ASSISTANCE:</strong> The Info Booth located at the end of the event (next to the Persona 3 area) will handle experience upgrades and answer all questions.
                </li>
                <li>
                    <strong>EVENT ID:</strong> Your Event ID is how your progress is tracked. Show your event ID for activity check-ins and reward redemption.
                </li>
                <li>
                    <strong>BOOKINGS:</strong> Reservations for the activity stations can be made in the Booking app. If no slots are available, physical waitlists are available at every station.
                </li>
                <li>
                    <strong>REWARDS:</strong> To receive your rewards, you must check in with at least one activity station and complete the activity.
                </li>
                <li>
                    <strong>INVESTIGATION:</strong> You and your friends will not be able to solve this investigation alone. In order to get the full picture, you must talk with attendees who participated in other activities and search your event guide. We wish you the best of luck in seeing the shadow.
                </li>
            </ul>

            <a href="home.php" class="btn-gold" style="display: block; width: 100%; box-sizing: border-box; text-align: center; margin-top: 25px; text-decoration: none; padding: 15px; font-weight: bold; border-radius: 4px;">
                UNDERSTOOD
            </a>
        </div>
    </div>
</body>
</html>