<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';  // Adjust the path if needed

include 'Admin/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if the email exists in the users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate a token
        $token = bin2hex(random_bytes(50));

        // Insert token into password_resets table
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();

        // Send email with reset link using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';  // Gmail SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'mrsscreation09@gmail.com';  // Your Gmail address
            $mail->Password = 'nypw kwao dbss gzhd';  // Use App Password if 2-Step Verification is enabled
            $mail->SMTPSecure = 'tls';  // Use TLS encryption
            $mail->Port = 587;  // Port 587 for TLS

            // Recipients
            $mail->setFrom('mrsscreation09@gmail.com', 'Salian Tech');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $mail->Body    = 'Click the link to reset your password: <a href="https://salian-web.infinityfreeapp.com/my-web/new_password.php?token=' . $token . '">Reset Password</a>';

            $mail->send();
            echo "A password reset link has been sent to your email.";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Email not found in the database.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <h2>Forgot Password</h2>
    <form action="password_recovery.php" method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</body>
</html>
