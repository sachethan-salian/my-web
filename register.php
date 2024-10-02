<?php
include 'Admin/db_connection.php';

// Start session
session_start();

$errorMessages = [];
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $inputUsername = $_POST['username'];
    $inputEmail = $_POST['email'];
    $inputPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $image = $_FILES['profile_path'];

    // Check if passwords match
    if ($inputPassword !== $confirmPassword) {
        $errorMessages['password'] = "Passwords do not match.";
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errorMessages['username'] = "Username already exists.";
    }
    $stmt->close();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $inputEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errorMessages['email'] = "Email already exists.";
    }
    $stmt->close();

    // If no errors, proceed with registration
    if (empty($errorMessages)) {
        // Hash the password
        $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $inputUsername, $inputEmail, $hashedPassword);

        if ($stmt->execute()) {
            // Get the last inserted ID
            $last_id = $stmt->insert_id;

            // Handle image upload
            $targetDir = "Profile/";
            if ($image['error'] === UPLOAD_ERR_NO_FILE) {
                // No image uploaded, use default image
                $targetFile = $targetDir . "default_profile_path.jpg";
            } else {
                $imageFileType = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
                $targetFile = $targetDir . $last_id . "_" . $inputUsername . "." . $imageFileType;
                $uploadOk = 1;

                // Check if image file is a actual image or fake image
                $check = getimagesize($image["tmp_name"]);
                if ($check !== false) {
                    $uploadOk = 1;
                } else {
                    $errorMessages['image'] = "File is not an image.";
                    $uploadOk = 0;
                }

                // Check file size (limit to 5MB)
                if ($image["size"] > 5000000000) {
                    $errorMessages['image'] = "Sorry, your file is too large.";
                    $uploadOk = 0;
                }

                // Allow certain file formats
                if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                    $errorMessages['image'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                    $uploadOk = 0;
                }

                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    $errorMessages['image'] = "Sorry, your file was not uploaded.";
                } else {
                    if (move_uploaded_file($image["tmp_name"], $targetFile)) {
                        // Update user's profile image path
                    } else {
                        $errorMessages['image'] = "Sorry, there was an error uploading your file.";
                    }
                }
            }

            // Update user's profile image path if no errors in image upload
            if (empty($errorMessages['image'])) {
                $stmt = $conn->prepare("UPDATE users SET profile_path = ? WHERE id = ?");
                $stmt->bind_param("si", $targetFile, $last_id);

                if ($stmt->execute()) {
                    $successMessage = "Registration successful. <a href='login.php'>Login here</a>";
                    $registrationSuccessful = true;
                } else {
                    $errorMessages['database'] = "Error: " . $stmt->error;
                }
            }

            $stmt->close();
           
        } else {
            $errorMessages['database'] = "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" type="text/css" href="css/register.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
        <?php include 'required/header.php'; 
$conn->close();
     ?>
    <div class="register-container">
        <form action="#" method="POST" enctype="multipart/form-data">
            <h2>Register</h2>
            <div class="image-container" id="imageContainer">
                <img id="imagePreview" src="Profile/default_profile_path.jpg" ondblclick="reselectImage()">
                <label for="imageInput" class="choose-image" id="chooseImageLabel">Choose Image</label>
                <input type="file" name="profile_path" id="imageInput" accept="image/*" onchange="previewImage(event)">
                <?php if (isset($errorMessages['image'])): ?>
                    <div class="error"><?php echo $errorMessages['image']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required value="<?php echo isset($inputUsername) ? htmlspecialchars($inputUsername) : ''; ?>">
                <?php if (isset($errorMessages['username'])): ?>
                    <div class="error"><?php echo $errorMessages['username']; ?></div>
                <?php else: ?>
                    <div class="error">&nbsp;</div> <!-- Keep space for the error message -->
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required value="<?php echo isset($inputEmail) ? htmlspecialchars($inputEmail) : ''; ?>">
                <?php if (isset($errorMessages['email'])): ?>
                    <div class="error"><?php echo $errorMessages['email']; ?></div>
                <?php else: ?>
                    <div class="error">&nbsp;</div> <!-- Keep space for the error message -->
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <?php if (isset($errorMessages['password'])): ?>
                    <div class="error"><?php echo $errorMessages['password']; ?></div>
                <?php else: ?>
                    <div class="error">&nbsp;</div> <!-- Keep space for the error message -->
                <?php endif; ?>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <?php if (isset($errorMessages['password'])): ?>
                    <div class="error"><?php echo $errorMessages['password']; ?></div>
                <?php else: ?>
                    <div class="error">&nbsp;</div> <!-- Keep space for the error message -->
                <?php endif; ?>
            </div>
            <button type="submit" id="registerButton">Register</button>
            
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <input type="hidden" id="registrationSuccessful" value="<?php echo isset($registrationSuccessful) ? 'true' : 'false'; ?>">
        </form>
    </div>
    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('imagePreview');
                output.src = reader.result;
                document.getElementById('chooseImageLabel').style.display = 'none';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function reselectImage() {
            document.getElementById('imageInput').click();
        }

        document.addEventListener('DOMContentLoaded', function() {
            var registrationSuccessful = document.getElementById('registrationSuccessful').value;
            if (registrationSuccessful === 'true') {
                var registerButton = document.getElementById('registerButton');
                registerButton.textContent = 'Register Successfully';
                registerButton.classList.add('button-success');
            }
        });
    </script>
</body>
</html>