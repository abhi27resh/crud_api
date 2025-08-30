<?php
// Start output buffering to catch accidental output
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
    // Get input data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mobile = $_POST['mobile'] ?? '';
        $device_id = $_POST['device_id'] ?? '';
    } else {
        $mobile = $_GET['mobile'] ?? '';
        $device_id = $_GET['device_id'] ?? '';
    }

    // Clean inputs
    $device_id = trim(str_replace('"', '', $device_id));
    $mobile = trim($mobile);

    // Validate inputs
    if (empty($mobile) || empty($device_id)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Mobile number and device ID are required.',
            'received_mobile' => $mobile,
            'received_device_id' => $device_id
        ]);
        ob_end_flush();
        exit;
    }

    // Validate mobile number format
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid mobile number format. Must be 10 digits.',
            'received_mobile' => $mobile
        ]);
        ob_end_flush();
        exit;
    }

    // Database connection
    $conn = new mysqli("sql111.ezyro.com", "ezyro_39808097", "0af0587cbccb90", "ezyro_39808097_users");

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Sanitize inputs
    $mobile = $conn->real_escape_string($mobile);
    $device_id = $conn->real_escape_string($device_id);

    // Check if user exists
    $sql = "SELECT * FROM users WHERE mobile = '$mobile'";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['device_id'] !== $device_id) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error',
                'message' => 'Cannot login the same mobile number on multiple devices.'
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful.'
            ]);
        }
    } else {
        // New user, insert into DB
        $sql_insert = "INSERT INTO users (mobile, device_id) VALUES ('$mobile', '$device_id')";
        if ($conn->query($sql_insert)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Registration successful.'
            ]);
        } else {
            throw new Exception('Failed to register user: ' . $conn->error);
        }
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
    error_log("API Error: " . $e->getMessage());
}

// Clean buffer and ensure only JSON is sent
ob_end_flush();
