<?php
include 'Admin/db_connection.php';
session_start();

// Variables to store user profile data if logged in
$user_profile_path = '';
$user_name = '';
$user_role = ''; // Add this variable to store user role

if (isset($_SESSION['username']) && isset($_SESSION['profile_path']) && isset($_SESSION['role'])) {
    // User is logged in
    $user_name = $_SESSION['username'];
    $user_profile_path = $_SESSION['profile_path'];
    $user_role = $_SESSION['role']; // Get the user role from session
} else {
    // User is not logged in
    $user_name = null;
}

// Search functionality
$searchQuery = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $stmt = $conn->prepare("SELECT user_profile, user_name, image, caption, description, data_time FROM post_data WHERE caption LIKE ? OR description LIKE ?");
    $searchTerm = "%$searchQuery%";
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT user_profile, user_name, image, caption, description, data_time FROM post_data");
}
$stmt->execute();
$post_result = $stmt->get_result();

if ($post_result === false) {
    echo "Post Query Error: " . $conn->error;
    exit();
}

// Fetching the brand name and message from the database
$stmt = $conn->prepare("SELECT brand_name, msg FROM main_page WHERE id = ?");
$stmt->bind_param('i', $id);
$id = 1; // Adjust the ID as needed
$stmt->execute();
$brand_result = $stmt->get_result();

if ($brand_result === false) {
    echo "Brand Query Error: " . $conn->error;
    exit();
}

$brand_name = "Default Brand";
$msg = "No message available.";
if ($brand_result->num_rows > 0) {
    while ($row = $brand_result->fetch_assoc()) {
        $brand_name = $row["brand_name"];
        $msg = $row["msg"];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive Professional Layout</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo-name">
<h1><?php echo htmlspecialchars($brand_name); ?></h1>
        </div>

        <div class="search-bar">
    <form action="#" method="GET">
        <input type="text" placeholder="Search..." name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <button type="submit" class="search-submit"></button> <!-- Hidden submit button -->
    </form>
</div>
        <div class="user-profile-container">
            <?php if ($user_name): ?>
                <img src="<?php echo htmlspecialchars($user_profile_path); ?>" alt="User Profile">
                <div class="dropdown">
                    <span><?php echo htmlspecialchars($user_name); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">Edit Profile</a>
                        <a href="settings.php">Settings</a>
                        <?php if ($user_role === 'admin'): ?>
                            <a href="Admin/admin_dashboard.php?page=overview">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="login-border">
                    <a href="login.php">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </header>
                <div class="box">
        <div class="m-box">
            <div class="fade">
                <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
 <?php
        if ($post_result->num_rows > 0) {
            while ($row = $post_result->fetch_assoc()) {
                echo '<div class="post-container">';
                echo '<div class="user-profile">';
                echo '<img src="' . htmlspecialchars($row['user_profile']) . '" alt="Profile Picture">';
                echo '<p>' . htmlspecialchars($row['user_name']) . '</p>';
                echo '</div>';
                echo '<div class="post-image">';
                echo '<img src="uploads/' . htmlspecialchars($row['image']) . '" alt="Post Image">';
                if ($user_name): // Check if user is logged in
                    echo '<a href="download.php?file=' . urlencode(htmlspecialchars($row['image'])) . '" class="download-button" download>Download</a>';
                endif;
                echo '</div>';
                echo '<div class="post-content">';
                echo '<p><strong>Caption:</strong> ' . htmlspecialchars($row['caption']) . '</p>';
                echo '<p>Description:<br><strong>' . htmlspecialchars($row['description']) . '</strong></p>';
                echo '</div>';
                echo '<div class="post-time">';
                echo '<p>' . htmlspecialchars($row['data_time']) . '</p>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>No posts available.</p>';
        }

        ?>
</div>

    <!-- Footer -->
    <footer>
<?php if ($user_name): ?>
        <button class="footer-btn">Home</button>
<a href="chat_box/"><button class="footer-btn">Messages</button></a>
        <button class="footer-btn">Settings</button>
    <?php else: ?>
        <a href="login.php"><button class="footer-btn">Login for more featurs</button></a>
        <?php endif; 
            $conn->close();
            ?>
    </footer>

</body>
            
    <script>
        document.querySelector('.user-profile-container .dropdown').addEventListener('click', function() {
            this.classList.toggle('active');
        });
    </script>
</html>
