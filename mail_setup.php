<?php
// mail_setup.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 1. FILESYSTEM SETUP
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// 2. THE EMAIL FUNCTION
// UPDATED: Added $activityName to arguments
function sendConfirmationEmail($toEmail, $bookingRef, $eventDate, $phone, $activityName, $guestName = 'Operative') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Timeout = 5;   // Give up if connection takes > 5 seconds
        $mail->Timelimit = 5; // Give up if data transfer takes > 5 seconds
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@irisinteractive.org'; 
        $mail->Password   = 'epls accq wyjk vwku ';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('info@irisinteractive.org', 'Velvet Room Admin');
        $mail->addAddress($toEmail); 

        // Content
        $mail->isHTML(true); 
        $mail->Subject = "Booking Confirmed: $activityName";
        
        $bodyContent = "
        <div style='background: #0a1628; color: #fff; padding: 30px; font-family: \"Segoe UI\", sans-serif; max-width: 600px; margin: 0 auto;'>
        
        <h1 style='color: #c9a227; text-align: center; font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 5px;'>Portal to the Velvet Room:</h1>
        <h2 style='color: #c9a227; text-align: center; font-size: 16px; letter-spacing: 2px; text-transform: uppercase; margin-top: 0;'>All-Out Holiday</h2>
        
        <hr style='border: none; border-top: 1px solid #c9a227; width: 60%; margin: 20px auto;'>
        
        <p style='text-align: center; letter-spacing: 2px; text-transform: uppercase; color: #888; font-size: 12px;'>Contracted Guest</p>
        <h2 style='text-align: center; color: #fff; font-size: 28px; letter-spacing: 4px; margin: 5px 0 25px 0;'>We await your arrival</h2>
        
        <hr style='border: none; border-top: 3px solid #c9a227; width: 80px; margin: 0 auto 25px auto;'>
        
        <div style='background: #111d33; border: 1px solid #1a2d4a; border-radius: 12px; padding: 20px; margin-bottom: 15px;'>
            <p style='color: #c9a227; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; margin: 0 0 10px 0;'>📅 Your Reservation</p>
            
            <h3 style='margin: 0 0 10px 0; color: #fff; font-size: 1.4em;'>$activityName</h3>
            
            <p style='margin: 0;'><strong>Time:</strong> $eventDate</p>
            <p style='margin: 5px 0 0 0;'><strong>Reference ID:</strong> $bookingRef</p>
            
            <div style='margin-top: 15px; padding-top: 10px; border-top: 1px dashed rgba(201, 162, 39, 0.3); font-size: 0.9em; color: #ccc;'>
                <p style='margin: 0;'><strong>Details on File:</strong><br>Email: $toEmail<br>Phone: $phone</p>
                <p style='margin: 10px 0 0 0;'><em>You may use any of these (Ref ID, Email, or Phone) to link this booking to your badge on-site.</em></p>
            </div>
        </div>

        <div style='background: rgba(230, 0, 18, 0.15); border: 1px solid #e60012; border-radius: 12px; padding: 15px; margin-bottom: 15px;'>
            <p style='color: #ff9999; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 5px 0;'>⚠️ Important Advisory</p>
            <p style='margin: 0; font-size: 0.95em; line-height: 1.4;'>
                This confirmation is for the <strong>Activity Only</strong>. 
                It does <u>not</u> serve as a General Admission ticket. Please ensure you have RSVP'd for general entry separately to enter the venue.
            </p>
        </div>
        
        <div style='background: #111d33; border: 1px solid #1a2d4a; border-radius: 12px; padding: 20px; margin-bottom: 25px;'>
            <p style='color: #c9a227; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; margin: 0 0 10px 0;'>📍 Location</p>
            <p style='margin: 0; line-height: 1.6;'>Factory Tea Bar<br>323 S Mission Dr, San Gabriel, CA 91776</p>
            <p style='margin: 10px 0 0 0; font-style: italic; color: #aaa;'>Arrive 10 minutes before your slot start time.</p>
        </div>
        
        <p style='text-align: center; color: #888; font-style: italic;'>The Velvet Room awaits.</p>
        
        </div>
        ";
        
        $mail->Body = $bodyContent;
        $mail->AltBody = "Confirmed: $activityName at $eventDate. Ref: $bookingRef. This is not a GA ticket.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>