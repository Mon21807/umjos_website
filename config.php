<?php
// config.php - FIXED VERSION for XAMPP

// Only set JSON header for API responses (not here globally)
// CORS headers for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== DATABASE CONFIG - Change these if needed =====
$host     = 'localhost';
$dbname   = 'urban_ministry';
$username = 'root';
$password = ''; // XAMPP default is empty password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch(PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'hint'    => 'Make sure: 1) XAMPP MySQL is running 2) Database "urban_ministry" exists 3) Username/password are correct'
    ]));
}
?>