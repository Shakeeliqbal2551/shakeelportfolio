<?php
date_default_timezone_set('Asia/Karachi');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config.php';
require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = htmlspecialchars($_POST['name'] ?? '');
    $email   = htmlspecialchars($_POST['email'] ?? '');
    $phone   = htmlspecialchars($_POST['phone'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }

    // Get visitor information
    $ip_address = getClientIP();
    $country = getCountryFromIP($ip_address);
    $location = getLocationFromIP($ip_address);
    $city = $location['city'] ?? 'Unknown';
    $region = $location['region'] ?? 'Unknown';
    $latitude = $location['latitude'] ?? '';
    $longitude = $location['longitude'] ?? '';
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser = getBrowserName($user_agent);
    $os = getOS($user_agent);
    $device_type = getDeviceType($user_agent);
    $submission_time = date('Y-m-d H:i:s');

    // Save to database using prepared statement (SAFE)
    $stmt = $conn->prepare(
        "INSERT INTO contact_messages 
        (name, email, phone, message, ip_address, country, city, region, latitude, longitude, user_agent, browser, os, device_type, submission_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    // Bind parameters: 15 parameters for 15 columns
    $stmt->bind_param(
        "sssssssssssssss",
        $name, $email, $phone, $message, $ip_address, $country, $city, $region, $latitude, $longitude, $user_agent, $browser, $os, $device_type, $submission_time
    );

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save message to database']);
        exit;
    }

    $stmt->close();

    // Send email
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@shakeeliqbal.com';
        $mail->Password   = '@iFt[f2?sA';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Email Content
        $mail->setFrom('no-reply@shakeeliqbal.com', "Shakeel's Portfolio Contact");
        $mail->addAddress('contact@shakeeliqbal.com');
        $mail->addReplyTo($email, $name);

        $mail->Subject = 'New Message via Shakeeliqbal.com Portfolio Contact Form';
        
        // Build HTML email with all information
        $mapLink = (!empty($latitude) && !empty($longitude)) ? 
            "https://maps.google.com/?q={$latitude},{$longitude}" : '#';
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { padding: 20px; background-color: #f5f5f5; }
                .section { background-color: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #f00a77; }
                .section h3 { color: #f00a77; margin-top: 0; }
                .field { margin: 10px 0; }
                .field strong { color: #f00a77; }
                a { color: #007bff; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='section'>
                    <h3>📧 Contact Information</h3>
                    <div class='field'><strong>Name:</strong> " . $name . "</div>
                    <div class='field'><strong>Email:</strong> " . $email . "</div>
                    <div class='field'><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</div>
                    <div class='field'><strong>Message:</strong><br>" . nl2br($message) . "</div>
                </div>
                
                <div class='section'>
                    <h3>🌐 Visitor Information</h3>
                    <div class='field'><strong>IP Address:</strong> " . $ip_address . "</div>
                    <div class='field'><strong>Country:</strong> " . $country . "</div>
                    <div class='field'><strong>City:</strong> " . $city . "</div>
                    <div class='field'><strong>Region:</strong> " . $region . "</div>
                    <div class='field'><strong>Coordinates:</strong> " . $latitude . ", " . $longitude;
                    if (!empty($latitude) && !empty($longitude)) {
                        $emailBody .= " - <a href='{$mapLink}'>View on Google Maps</a>";
                    }
                    $emailBody .= "</div>
                    <div class='field'><strong>Browser:</strong> " . $browser . "</div>
                    <div class='field'><strong>Operating System:</strong> " . $os . "</div>
                    <div class='field'><strong>Device Type:</strong> " . $device_type . "</div>
                    <div class='field'><strong>User Agent:</strong> " . $user_agent . "</div>
                    <div class='field'><strong>Submission Time:</strong> " . $submission_time . "</div>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->isHTML(true);
        $mail->Body = $emailBody;
        $mail->send();
        
        echo json_encode(['status' => 'success', 'message' => 'Your message has been received, I will contact you soon.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Email sending failed: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>