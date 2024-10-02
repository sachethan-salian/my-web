<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'Admin/db_connection.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['profile_path'])) {
    header("Location: login.php");
    exit();
}

// Get user details from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'];
$user_profile_path = $_SESSION['profile_path'];

// Initialize response array
$response = [
    'status' => 'success',
    'messages' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDir = "uploads/";
    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));

    // Generate unique filename
    $uniqueFilename = $user_id . "_" . $user_name . "_" . date("YmdHis") . "." . $imageFileType;
    $targetFile = $targetDir . $uniqueFilename;
    $uploadOk = 1;

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        $uploadOk = 0;
        $response['status'] = 'error';
        $response['messages'][] = "File is not an image.";
    }

    // Check if file already exists
    if (file_exists($targetFile)) {
        $uploadOk = 0;
        $response['status'] = 'error';
        $response['messages'][] = "Sorry, file already exists.";
    }

    // Check file size
    if ($_FILES["image"]["size"] > 5000000000) {
        $uploadOk = 0;
        $response['status'] = 'error';
        $response['messages'][] = "Sorry, your file is too large.";
    }

    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $uploadOk = 0;
        $response['status'] = 'error';
        $response['messages'][] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0 && empty($response['messages'])) {
        $response['messages'][] = "Sorry, your file was not uploaded.";
    } elseif ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO post_data (user_profile, user_name, image, caption, description, data_time) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $user_profile_path, $user_name, $uniqueFilename, $caption, $description);

            // Set parameters and execute
            $caption = htmlspecialchars($_POST['caption']);
            $description = htmlspecialchars($_POST['description']);

            if ($stmt->execute()) {
                $response['messages'][] = "Your post has been uploaded.";
            } else {
                $response['status'] = 'error';
                $response['messages'][] = "Sorry, there was an error uploading your file.";
            }

            $stmt->close();
        } else {
            $response['status'] = 'error';
            $response['messages'][] = "Sorry, there was an error uploading your file.";
        }
    }

    $conn->close();

    echo json_encode($response);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Post</title>
    <link rel="stylesheet" type="text/css" href="CSS/upload.css">
    <link rel="stylesheet" href="https://unpkg.com/cropperjs/dist/cropper.css">
</head>
<body>
    <!-- User Profile Section -->
    <div class="user-profile-container">
        <img src="<?php echo htmlspecialchars($user_profile_path); ?>" alt="User Profile" style="width:40px;height:40px;border-radius:50%;">
        <div class="dropdown">
            <span><?php echo htmlspecialchars($user_name); ?></span>
            <div class="dropdown-content">
                <a href="Admin/admin_dashboard.php?page=post_details">admin_dashbord</a><br>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
       <div class="container">
        <div class="image-container" id="imageContainer">
            <span id="browsePhotoText">Browse Photo</span>
            <img id="imagePreview" src="" alt="Image Preview">
            <input type="file" name="image" id="imageInput" accept="image/*">
        </div>
        <div class="content">
            <form id="postForm" action="upload.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="caption" placeholder="Enter Caption..." required>
                <textarea name="description" placeholder="Enter Description..."></textarea>
                <button type="submit">Upload</button>
                <p><a href="main.php"> << Back to main page</a></p>
            </form>
        </div>
    </div>
    <div id="popup" class="popup"></div>

    <script src="https://unpkg.com/cropperjs"></script>
    <script>
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const browsePhotoText = document.getElementById('browsePhotoText');
        const textarea = document.querySelector("textarea");
        let cropper;
        let imageSelected = false; // Flag to track if an image is selected

        // Function to handle image selection and preview
        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    browsePhotoText.style.display = 'none';
                    imageSelected = true; // Set flag to true

                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(imagePreview, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 1, // Ensure the image is fully visible within the cropper
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Click event to trigger file input dialog
        document.getElementById('imageContainer').addEventListener('click', () => {
            if (!imageSelected) { // Only trigger if no image is selected
                imageInput.click();
            }
        });

        // Double-click event to trigger file input dialog
        document.getElementById('imageContainer').addEventListener('dblclick', () => {
            imageInput.click();
        });

        function showPopup(message) {
            const popup = document.getElementById('popup');
            popup.textContent = message;
            popup.style.display = 'block';
            setTimeout(() => {
                popup.style.display = 'none';
            }, 3000);
        }

        // Handle form submission with AJAX
        const postForm = document.getElementById('postForm');
        postForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent the default form submission

            if (cropper) {
                cropper.getCroppedCanvas().toBlob((blob) => {
                    const formData = new FormData(postForm);
                    formData.set('image', blob, 'cropped.png');

                    fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'error') {
                            data.messages.forEach(message => showPopup(message));
                        } else {
                            showPopup(data.messages.join(' '));
                            // Optionally reset form here if needed
                            postForm.reset();
                            if (cropper) {
                                cropper.destroy();
                            }
                            imagePreview.src = '';
                            imagePreview.style.display = 'none';
                            browsePhotoText.style.display = 'block';
                            imageSelected = false; // Reset flag after reset
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showPopup('An error occurred while uploading.');
                    });
                });
            }
        });

textarea.addEventListener("keyup", e => {
    textarea.style.height = "100px"; // Reset height to default to allow shrinking
    let scHeight = e.target.scrollHeight;
    textarea.style.height = `${scHeight}px`; // Use backticks for template literal
});


        // Debugging: Check if JavaScript is working
        console.log('JavaScript loaded');
    </script>
</body>
</html>
