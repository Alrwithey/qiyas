<?php
session_start();
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../functions.php";
require_once __DIR__ . "/../db.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check login and request method
if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the posted data
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (isset($data['order']) && is_array($data['order'])) {
    $program_ids = array_map('intval', $data['order']);

    if (update_programs_order($conn, $program_ids)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order in database.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
}

$conn->close();
?>