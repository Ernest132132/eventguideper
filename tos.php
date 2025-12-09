<?php
session_start();

// Handle Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    if (isset($_COOKIE['auth_token'])) {
        setcookie("auth_token", "", time() - 3600, "/"); // Clear the cookie
    }
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Terms of Service | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=1">
    <style>
        .tos-content {
            text-align: left;
            font-size: 0.9rem;
            color: #ccc;
            line-height: 1.6;
        }
        .tos-content h3 {
            color: var(--velvet-gold);
            font-size: 1rem;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
            margin-top: 20px;
        }
        .tos-content ul {
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="fabric-container"><div class="fabric-wave"></div><div class="fabric-wave"></div></div>
    <div class="fog-container"><div class="fog-layer"></div><div class="fog-layer"></div></div>
    <div class="container page-visible">
        
        <div class="profile-header">
            <h1 style="font-size: 1.8rem;">TERMS OF SERVICE</h1>
            <p style="font-size: 0.8rem;">PROTOCOL AGREEMENT</p>
        </div>
        <div class="card tos-content">
            <p><strong>Effective Date:</strong> December 2024</p>
            <p>By accessing or using the <strong>All-Out Holiday Portal</strong> ("the App"), you agree to be bound by these terms.</p>
            
            <h3>1. NATURE OF SERVICE</h3>
            <p>This App is a fan-made companion tool for an immersive event. It is designed for entertainment purposes only.</p>
            
            <h3>2. AGE REQUIREMENTS</h3>
            <p>You must be at least 13 years of age to use this App. If you are under 18, you represent that you have obtained parental or guardian consent to use this App and agree to these Terms of Service. We do not knowingly collect personal information from children under 13.</p>
            
            <h3>3. DATA COLLECTION</h3>
            <p>We collect minimal data to facilitate gameplay:</p>
            <ul>
                <li><strong>Alias:</strong> A public display name chosen by you.</li>
                <li><strong>Game Data:</strong> Scores, timestamps, and item redemption status.</li>
                <li><strong>Contact Info:</strong> Email/Phone numbers provided for bookings are stored securely and used solely for reservation notifications.</li>
            </ul>
            
            <h3>4. COOKIES & STORAGE</h3>
            <p>We use local storage and essential cookies to maintain your login session (keeping you signed in as your Alias ID). We do not use third-party tracking cookies.</p>
            
            <h3>5. USER CONDUCT & ACCOUNT TERMINATION</h3>
            <p>You agree not to attempt to hack, exploit, or disrupt the App. We reserve the right to suspend, disable, or permanently delete your account at our sole discretion, with or without notice, for any reason including but not limited to:</p>
            <ul>
                <li>Violation of these Terms of Service</li>
                <li>Evidence of tampering, cheating, or exploitation</li>
                <li>Conduct that disrupts the experience for other users</li>
                <li>Abuse of event staff or volunteers</li>
            </ul>
            <p>Account termination may result in disqualification from event prizes and forfeiture of any accumulated points or rewards.</p>
            
            <h3>6. SERVICE AVAILABILITY</h3>
            <p>The App is provided on an "as-is" and "as-available" basis. We do not guarantee uninterrupted or error-free operation. The App may be temporarily or permanently unavailable due to maintenance, technical issues, or discontinuation of the service. We reserve the right to modify, suspend, or discontinue the App at any time without prior notice.</p>
            
            <h3>7. PRIZES & GIVEAWAYS</h3>
            <p>If prizes or giveaways are offered through this App:</p>
            <ul>
                <li>No purchase is necessary to participate.</li>
                <li>Winners are selected based on criteria disclosed at the time of the promotion (e.g., leaderboard ranking, random drawing).</li>
                <li>Prizes are subject to availability and may be substituted with items of equal or greater value.</li>
                <li>You may be required to be present at the event to claim prizes.</li>
                <li>Event organizers' decisions regarding prize eligibility and winners are final.</li>
            </ul>
            
            <h3>8. USER-GENERATED CONTENT</h3>
            <p>If you submit any content through the App (including but not limited to photos, comments, or usernames), you grant us a non-exclusive, royalty-free license to use, display, and share such content in connection with the event and its promotion. You represent that any content you submit does not infringe on the rights of others.</p>
            
            <h3>9. LIMITATION OF LIABILITY</h3>
            <p>To the fullest extent permitted by law, IRIS Interactive Foundation and its volunteers, officers, and affiliates shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of or inability to use the App. This includes but is not limited to damages for loss of data, service interruptions, or any errors or omissions in content. Our total liability shall not exceed the amount you paid to use the App (which is zero, as this is a free service).</p>
            
            <h3>10. CHANGES TO TERMS</h3>
            <p>We reserve the right to modify these Terms of Service at any time. Changes will be effective immediately upon posting to the App. Your continued use of the App after any changes constitutes your acceptance of the revised terms. We encourage you to review these terms periodically.</p>
            
            <h3>11. DISCLAIMER</h3>
            <p>This is an unofficial, fan-run project operated by IRIS Interactive Foundation, a 501(c)(3) nonprofit organization. It is not affiliated with, endorsed by, or connected to Atlus, SEGA, or their partners. All character likenesses and game references are the property of their respective owners.</p>
            
            <h3>12. CONTACT</h3>
            <p>If you have questions about these Terms of Service, please contact us at the event or through our official communication channels.</p>
        </div>
        <br>
        <button onclick="history.back()" class="btn-gold" style="width: auto; padding: 10px 30px;">
            &larr; RETURN
        </button>

        <div style="margin-top: 40px; margin-bottom: 20px; opacity: 0.4;">
            <a href="?logout=1" style="color: #888; font-size: 0.7rem; text-decoration: none; border-bottom: 1px dashed #555; padding-bottom: 2px;">
                [ TERMINATE SESSION ]
            </a>
        </div>
    </div>
</body>
</html>