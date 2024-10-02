<?php
include 'Admin/db_connection.php';
session_start();

$errorMessages = [];
$successMessage = "";

// Assuming user is already logged in, and their user ID is stored in session
$userId = $_SESSION['id'];

// Fetch existing user data
$stmt = $conn->prepare("SELECT username, email, profile_path FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($existingUsername, $existingEmail, $existingProfilePath);
$stmt->fetch();
$stmt->close();

// Check if form is submitted to update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUsername = $_POST['username'];
    $inputEmail = $_POST['email'];
    $inputPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $image = $_FILES['profile_path'];

    // Validate password match if provided
    if (!empty($inputPassword) && $inputPassword !== $confirmPassword) {
        $errorMessages['password'] = "Passwords do not match.";
    }

    // Check if username already exists (other than the current user)
    if ($inputUsername !== $existingUsername) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $inputUsername, $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errorMessages['username'] = "Username already exists.";
        }
        $stmt->close();
    }

    // Check if email already exists (other than the current user)
    if ($inputEmail !== $existingEmail) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $inputEmail, $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errorMessages['email'] = "Email already exists.";
        }
        $stmt->close();
    }

    // If no errors, proceed with updating profile
    if (empty($errorMessages)) {
        // If password is provided, update it
        if (!empty($inputPassword)) {
            $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $inputUsername, $inputEmail, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $inputUsername, $inputEmail, $userId);
        }
        $stmt->execute();
        $stmt->close();

        // Handle image upload
        if (!empty($image['name'])) {
            $targetDir = "Profile/";
            $imageFileType = strtolower(pathinfo($image["name"], PATHINFO_EXTENSION));
            $targetFile = $targetDir . $userId . "_" . $inputUsername . "." . $imageFileType;
            $uploadOk = 1;

            // Check if image file is a valid image
            $check = getimagesize($image["tmp_name"]);
            if ($check === false) {
                $errorMessages['image'] = "File is not an image.";
                $uploadOk = 0;
            }

            // Limit file size to 5MB
            if ($image["size"] > 5000000000) {
                $errorMessages['image'] = "File is too large.";
                $uploadOk = 0;
            }

            // Allow only specific file formats
            if (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $errorMessages['image'] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }

            // If an old image exists, delete it
            if (file_exists($targetFile)) {
                unlink($targetFile);  // Delete the old image
            }

            // Upload the new image
            if ($uploadOk && move_uploaded_file($image["tmp_name"], $targetFile)) {
                $stmt = $conn->prepare("UPDATE users SET profile_path = ? WHERE id = ?");
                $stmt->bind_param("si", $targetFile, $userId);
                $stmt->execute();
                $stmt->close();
            } else {
                // Log upload errors
                error_log("Image upload failed. Error: " . print_r(error_get_last(), true));
                $errorMessages['image'] = "Image upload failed.";
            }
        }

        // If successful, show a success message
        if (empty($errorMessages)) {
            $successMessage = "Profile updated successfully.";
        }
    }
}
?>

<!-- HTML Form for updating profile settings -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <?php if (!empty($successMessage)) : ?>
            <p class="success-message"><?php echo $successMessage; ?></p>
        <?php endif; ?>
        <h2>Update Profile Settings</h2>
        <form action="#" method="POST" enctype="multipart/form-data">
            <div class="image-container" id="imageContainer">
                <img id="imagePreview" src="<?php echo $existingProfilePath ?: 'Profile/default_profile_path.jpg'; ?>" ondblclick="reselectImage()">
                <input type="file" name="profile_path" id="imageInput" accept="image/*" onchange="previewImage(event)">
                <?php if (isset($errorMessages['image'])): ?>
                    <div class="error"><?php echo $errorMessages['image']; ?></div>
                <?php endif; ?>
            </div>

            <div>
                <label for="username">Username</label>
                <input type="text" name="username" value="<?php echo $existingUsername; ?>" required>
                <div class="error-message"><?php echo $errorMessages['username'] ?? ''; ?></div>
            </div>

            <div>
                <label for="email">Email</label>
                <input type="email" name="email" value="<?php echo $existingEmail; ?>" required>
                <div class="error-message"><?php echo $errorMessages['email'] ?? ''; ?></div>
            </div>

            <div>
                <label for="password">New Password (Optional)</label>
                <input type="password" name="password">
            </div>

            <div>
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" name="confirm_password">
                <div class="error-message"><?php echo $errorMessages['password'] ?? ''; ?></div>
            </div>

            <button type="submit">Update Profile</button>
                    <p><a href="main.php"> << Back to main page</a></p>
        </form>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('imagePreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
