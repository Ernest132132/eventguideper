<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FAQ | All-Out Holiday</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* FAQ Accordion Style */
        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--velvet-gold);
            margin-bottom: 15px;
            border-radius: 4px;
            overflow: hidden;
        }
        .faq-question {
            padding: 15px;
            font-family: 'Cinzel', serif;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            line-height: 1.4;
            color: var(--velvet-gold);
            text-align: left; /* Ensure explicit left alignment */
        }
        .faq-question:hover { background: rgba(212, 175, 55, 0.2); }
        .faq-answer {
            padding: 0 15px;
            max-height: 0; /* Hidden by default */
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            color: #ccc;
            font-size: 0.95rem;
            line-height: 1.6; /* More elegant spacing */
            border-top: 1px solid transparent;
            text-align: left; /* FIX: Prevents inheritance of center alignment */
        }
        /* Open State */
        .faq-item.active .faq-answer {
            padding: 15px;
            max-height: 500px; /* Allow enough space for longer answers */
            border-top: 1px solid rgba(212, 175, 55, 0.3);
        }
        .faq-item.active .fa-chevron-down { transform: rotate(180deg); transition: transform 0.3s; }
    </style>
</head>
<body>
    <div class="fabric-container">
        <div class="fabric-wave"></div>
        <div class="fabric-wave"></div>
    </div>
    <div class="fog-container">
        <div class="fog-layer"></div>
        <div class="fog-layer"></div>
    </div>
    <div class="container">
        
        <div class="profile-header">
            <h1 style="font-size: 1.8rem;">FAQ</h1>
            <p style="font-size: 0.8rem;">FREQUENTLY ASKED QUESTIONS</p>
        </div>

        <!-- 1. ESSENTIALS -->
<div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                What are the event hours?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                The event runs from <strong>12:00 PM</strong> to <strong>7:00 PM</strong>.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Where are the restrooms located?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Restrooms are located inside the main entrance, just before the double doors.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                My ID is not appearing.
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Please refresh your browser. If you have cleared your cache, enter the 4-digit <strong>Rescue Code</strong> provided during registration to restore access.
            </div>
        </div>

        <!-- THE EXPERIENCE -->
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                What is the "Theater"?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Throughout the venue, you will encounter performers portraying characters from the Persona universe. They will engage with you in character as part of the immersive experience.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                When does the Symphony perform?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Performances take place throughout the day. Please visit the red-tiled outdoor terrace for the full schedule.
            </div>
        </div>

        <!-- REWARDS & PARTICIPATION -->
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                How do I claim a reward?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Present your <strong>My ID</strong> code at any participating Vendor or Activity Station. A single digital stamp grants access to the Prize Counter at the Info Booth.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Where do I submit a completed Contract?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Please bring your completed Contract to the <strong>Info Booth</strong>.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                I purchased a Battle Pass. Where can I collect my rewards?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Battle Pass rewards are available for pickup at the <strong>Info Booth</strong>.
            </div>
        </div>

        <!-- GENERAL ASSISTANCE -->

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                Where is lost and found?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Lost and found inquiries are handled at the <strong>Info Booth</strong>.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                When is the next event?
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Follow us on Instagram for future event announcements.
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question" onclick="toggleFaq(this)">
                I have a question not listed here.
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                Please visit the <strong>Info Booth</strong>, located outdoors near the Persona 3 sector.
            </div>
        </div>

        <br>
        <a href="home.php" class="btn-gold">
            <i class="fa-solid fa-arrow-left"></i> Return to dashboard
        </a>

    </div>

    <script>
        function toggleFaq(element) {
            // Toggle the 'active' class on the parent container
            element.parentElement.classList.toggle('active');
        }
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const links = document.querySelectorAll('a');
            const container = document.querySelector('.container');

            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Check if it's an internal link AND not opening in a new tab
                    if (this.hostname === window.location.hostname && this.getAttribute('target') !== '_blank') {
                        e.preventDefault(); // Stop immediate load
                        const href = this.getAttribute('href');
                        
                        // Add the Exit Animation Class
                        if(container) {
                            container.classList.remove('page-visible');
                            container.classList.add('page-exit');
                        }
                        
                        // Wait 480ms for animation to finish, then go
                        setTimeout(() => {
                            window.location.href = href;
                        }, 480); 
                    }
                });
            });
        });
    </script>
<?php include 'footer.php'; ?>