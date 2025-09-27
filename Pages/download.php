<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied");
}

// Check if file parameters are provided
if (!isset($_GET['file']) || !isset($_GET['name'])) {
    header("HTTP/1.1 400 Bad Request");
    exit("Missing file parameters");
}

$stored_filename = $_GET['file'];
$original_filename = $_GET['name'];

// Security: Validate filename to prevent directory traversal
if (strpos($stored_filename, '..') !== false || strpos($stored_filename, '/') !== false || strpos($stored_filename, '\\') !== false) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid filename");
}

// Construct file path
$upload_dir = '../uploads/facility_documents/';
$file_path = $upload_dir . $stored_filename;

// Check if file exists
if (!file_exists($file_path)) {
    header("HTTP/1.1 404 Not Found");
    exit("File not found");
}

// Database connection to verify file ownership
$link = mysqli_connect('localhost', 'root', '', 'reqzone');
if ($link === false) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Database connection failed");
}

// Verify that the file belongs to the current user or user has permission to access it
$user_email = $_SESSION['email'] ?? '';
$stmt = mysqli_prepare($link, "SELECT id FROM fileInformation WHERE SenderEmail = ? AND UploadedFiles LIKE ?");
$search_pattern = '%' . $stored_filename . '%';
mysqli_stmt_bind_param($stmt, "ss", $user_email, $search_pattern);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    mysqli_close($link);
    header("HTTP/1.1 403 Forbidden");
    exit("You don't have permission to access this file");
}

mysqli_stmt_close($stmt);
mysqli_close($link);

// Get file info
$file_size = filesize($file_path);
$file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

// Set appropriate content type
$content_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'txt' => 'text/plain'
];

$content_type = $content_types[$file_extension] ?? 'application/octet-stream';

// Set headers for file download
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: private, must-revalidate');
header('Pragma: private');
header('Expires: 0');

// Clear any output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Read and output file
$handle = fopen($file_path, 'rb');
if ($handle === false) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Cannot read file");
}

while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}

fclose($handle);
exit;
?>