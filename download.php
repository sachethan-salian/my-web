<?php
include 'Admin/db_connection.php';

if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $filePath = 'uploads/' . basename($file);

    if (file_exists($filePath)) {
        // Set headers for file download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        // Read the file and output it to the browser
        readfile($filePath);
        exit;
    } else {
        echo "File does not exist.";
    }
} else {
    echo "No file specified.";
}
?>
