<?php
require_once '../config.php';

// Verify token
$user = verifyToken();

if (!$user) {
    sendResponse(false, "Unauthorized", null, 401);
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['tournament_id'])) {
    sendResponse(false, "Tournament ID is required", null, 400);
}

$tournament_id = $data['tournament_id'];
$team_name = isset($data['team_name']) ? trim($data['team_name']) : null;
$user_id = $user['user_id'];

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if tournament exists and is open
$stmt = $conn->prepare("SELECT id, name, status, max_players FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse(false, "Tournament not found", null, 404);
}

$tournament = $result->fetch_assoc();

// Check if tournament is open for registration
if ($tournament['status'] !== 'upcoming') {
    sendResponse(false, "Tournament is not open for registration", null, 400);
}

// Check if already registered
$stmt = $conn->prepare("SELECT id FROM registrations WHERE tournament_id = ? AND user_id = ?");
$stmt->bind_param("ii", $tournament_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    sendResponse(false, "You are already registered for this tournament", null, 400);
}

// Check if tournament is full
$stmt = $conn->prepare("SELECT COUNT(*) as player_count FROM registrations WHERE tournament_id = ?");
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['player_count'] >= $tournament['max_players']) {
    sendResponse(false, "Tournament is full", null, 400);
}

// Register user for tournament
$stmt = $conn->prepare("INSERT INTO registrations (tournament_id, user_id, team_name, registration_date) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $tournament_id, $user_id, $team_name);

if ($stmt->execute()) {
    sendResponse(true, "Successfully registered for tournament", [
        'registration_id' => $conn->insert_id,
        'tournament_name' => $tournament['name'],
        'team_name' => $team_name
    ]);
} else {
    sendResponse(false, "Registration failed: " . $conn->error, null, 500);
}

$stmt->close();
$db->close();
?>