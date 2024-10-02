<?php
// Define the routes
$routes = [
    '' => 'main.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'upload' => 'upload.php',
    'logout' => 'logout.php',
    'display' => 'display.php',
    'admin' => 'Admin/admin_dashboard.php',
    // Add more routes as needed
];

// Get the requested page from the URL
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Check if the route exists, otherwise use a default file
if (array_key_exists($page, $routes)) {
    include $routes[$page];
} else {
    include 'main.php'; // Default page
}
?>
