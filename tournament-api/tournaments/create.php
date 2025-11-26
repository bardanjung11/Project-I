<?php
require_once '../config.php';

// Get bearer token
$token = getBearerToken();

// Verify token
$userData = verifyToken($token);
if (!$userData) {
    sendResponse(false, "Unauthorized access", null, 401);
}

// Check if user is admin
if ($userData['role'] !== 'admin') {
    sendResponse(false, "Only admins can create tournaments", null, 403);
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['game']) || !isset($data['name']) || !isset($data['max_players']) || 
    !isset($data['match_type']) || !isset($data['entry_fee']) || 
    !isset($data['prize_pool']) || !isset($data['start_date'])) {
    sendResponse(false, "All fields are required", null, 400);
}

$game = trim($data['game']);
$name = trim($data['name']);
$maxPlayers = intval($data['max_players']);
$matchType = $data['match_type'];
$entryFee = floatval($data['entry_fee']);
$prizePool = floatval($data['prize_pool']);
$startDate = $data['start_date'];

// Validate match type
$validMatchTypes = ['Solo', 'Duo', 'Squad'];
if (!in_array($matchType, $validMatchTypes)) {
    sendResponse(false, "Invalid match type", null, 400);
}

// Validate max players
if ($maxPlayers < 2) {
    sendResponse(false, "Maximum players must be at least 2", null, 400);
}

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Insert tournament
$stmt = $conn->prepare("INSERT INTO tournaments (game, name, max_players, match_type, entry_fee, prize_pool, start_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisddsi", $game, $name, $maxPlayers, $matchType, $entryFee, $prizePool, $startDate, $userData['user_id']);

if ($stmt->execute()) {
    $tournamentId = $conn->insert_id;
    
    sendResponse(true, "Tournament created successfully", [
        'tournament_id' => $tournamentId,
        'game' => $game,
        'name' => $name,
        'max_players' => $maxPlayers,
        'match_type' => $matchType,
        'entry_fee' => $entryFee,
        'prize_pool' => $prizePool,
        'start_date' => $startDate
    ], 201);
} else {
    sendResponse(false, "Failed to create tournament", null, 500);
}

$stmt->close();
$db->close();
?>