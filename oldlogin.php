<?php 
// Backend PHP Code (Handles login logic)

// Start the session only if it isn't already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Correct path to the config.php file
include 'Admin/db_connection.php'; // Make sure the config.php file is in the correct path

// Handle login request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json'); // Ensure JSON response

    $response = array('status' => 'error', 'message' => 'Something went wrong!');

    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim(mysqli_real_escape_string($conn, $_POST['password']));

    if (!empty($username) && !empty($password)) {
        $sql = mysqli_query($conn, "SELECT * FROM users WHERE username = '{$username}'");
        if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_assoc($sql);
            if (password_verify($password, $row['password'])) {
                $status = "Active now";
                $sql2 = mysqli_query($conn, "UPDATE users SET status = '{$status}' WHERE id = {$row['id']}");
                if ($sql2) {
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['profile_path'] = $row['profile_path']; // Make sure 'profile_path' column exists in the DB

                    $response['status'] = 'success';
                    $response['message'] = 'Login successful!';
                } else {
                    $response['message'] = 'Something went wrong. Please try again!';
                }
            } else {
                $response['message'] = 'Username or Password is Incorrect!';
            }
        } else {
            $response['message'] = "$username - This username does not exist!";
        }
    } else {
        $response['message'] = 'All input fields are required!';
    }

    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
</head>
<body>
    
<!-- Header -->
<header>
    <div class="logo-name">
        <h1>Tech Web</h1>
    </div>

    <div class="search-bar">
        <form action="#" method="GET">
            <input type="text" placeholder="Search..." name="search" value="">
            <button type="submit" class="search-submit"></button> <!-- Hidden submit button -->
        </form>
    </div>

    <div class="user-profile-container">
        <div class="login-border">
            <a href="main.php"><-Back</a>
        </div>
    </div>
</header>

<!-- Login Form -->
<div class="login-container">
    <form id="loginForm" action="login.php" method="POST">
        <h2>Login</h2>
        <p id="error_message" style="color: red;"></p> <!-- Error message placeholder -->
        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Username" required>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Password" required>
        <a href="password_recovery.php">Forgot Password?</a>
        <button type="submit">Login</button>
        <p>Create Account <a href="register.php">Register here</a></p>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault(); // Prevent form from submitting the default way

            $.ajax({
                type: 'POST',
                url: 'login.php',
                data: $(this).serialize(), // Send form data
                dataType: 'json', // Expect a JSON response
                success: function(response) {
                    if (response.status === 'success') {
                        window.location.href = 'main.php'; // Redirect on success
                    } else {
                        $('#error_message').text(response.message); // Display error message
                    }
                },
                error: function() {
                    $('#error_message').text('An error occurred. Please try again.');
                }
            });
        });
    });
</script>
</body>
</html>
