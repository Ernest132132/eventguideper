<?php
session_start();
require 'db.php';

// 1. Security: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Logic: If they are already active, bounce them to home
$stmt = $pdo->prepare("SELECT status FROM operatives WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$status = $stmt->fetchColumn();

if ($status !== 'pending') {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Welcome | Event Guide</title>

    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            overflow: hidden;
            font-family: 'Cinzel', serif;
        }

        .welcome-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            text-align: center;
            box-sizing: border-box;
        }

        .welcome-box {
            border: 2px solid var(--accent-gold);
            background: rgba(14, 22, 56, 0.95);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.3);
            border-radius: 8px;
        }

        .welcome-box h1 {
            color: var(--accent-gold);
            font-size: 1.8rem;
            margin-bottom: 20px;
            letter-spacing: 4px;
        }

        .welcome-box p {
            font-size: 1.1rem;
            color: #e0e0e0;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .welcome-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-welcome {
            padding: 12px 30px;
            font-family: 'Cinzel', serif;
            font-weight: bold;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 2px;
            cursor: pointer;
            transition: transform 0.2s;
            border: none;
        }

        .btn-welcome:hover {
            transform: scale(1.05);
        }

        .btn-primary {
            background: var(--accent-gold);
            color: #000;
        }

        @media (max-width: 480px) {
            .welcome-box {
                padding: 20px;
                width: 95%;
            }
            .welcome-actions {
                flex-direction: column;
                gap: 10px;
            }
            .btn-welcome {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div></div>

    <div class="welcome-container">
        <div class="welcome-box">
            <h1>WELCOME</h1>

            <p>
                Thank you for joining us at this event!
            </p>

            <p>
                Before you proceed, please review and agree to the event guidelines on the next page.
            </p>

            <p style="font-size: 0.9rem; color: var(--accent-gold-light); text-transform: uppercase; letter-spacing: 1px;">
                [ Customize this introduction for your event ]
            </p>

            <div class="welcome-actions">
                <a href="contract.php?done=1" class="btn-welcome btn-primary">
                    Continue
                </a>
            </div>
        </div>
    </div>

</body>
</html>
