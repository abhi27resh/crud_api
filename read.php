<?php
// Start output buffering
ob_start();

// Prevent HTML error output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Register shutdown function to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Fatal error: ' . $error['message']
        ]);
        ob_end_clean();
        exit;
    }
});

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

// Log the request
error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

try {
    // Connect to the database
    $conn = new mysqli("sql111.ezyro.com", "ezyro_39808097", "0af0587cbccb90", "ezyro_39808097_users");

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Query all mobile numbers
    $sql = "SELECT mobile FROM users";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $mobiles = [];
    while ($row = $result->fetch_assoc()) {
        $mobiles[] = $row['mobile'];
    }

    // Return JSON response
    echo json_encode([
        'status' => 'success',
        'mobiles' => $mobiles
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    error_log("API Error: " . $e->getMessage());
}

// End output buffering
ob_end_flush();
