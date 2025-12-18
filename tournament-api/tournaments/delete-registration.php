<?php
require_once '../config.php';

// Verify token and check admin role
$user = verifyToken();

if (!$user) {
    sendResponse(false, "Unauthorized", null, 401);
}

if ($user['role'] !== 'admin') {
    sendResponse(false, "Forbidden: Admin access required", null, 403);
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['registration_id'])) {
    sendResponse(false, "Registration ID is required", null, 400);
}

$registration_id = $data['registration_id'];

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Delete registration
$stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
$stmt->bind_param("i", $registration_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        sendResponse(true, "Registration deleted successfully");
    } else {
        sendResponse(false, "Registration not found", null, 404);
    }
} else {
    sendResponse(false, "Failed to delete registration: " . $conn->error, null, 500);
}

$stmt->close();
$db->close();
?>
