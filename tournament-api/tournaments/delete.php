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
if (!isset($data['tournament_id'])) {
    sendResponse(false, "Tournament ID is required", null, 400);
}

$tournament_id = $data['tournament_id'];

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Start transaction
$conn->begin_transaction();

try {
    // First delete all registrations for this tournament
    $stmt = $conn->prepare("DELETE FROM registrations WHERE tournament_id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    
    // Then delete the tournament
    $stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        sendResponse(true, "Tournament deleted successfully");
    } else {
        $conn->rollback();
        sendResponse(false, "Tournament not found", null, 404);
    }
} catch (Exception $e) {
    $conn->rollback();
    sendResponse(false, "Failed to delete tournament: " . $e->getMessage(), null, 500);
}

$stmt->close();
$db->close();
?>