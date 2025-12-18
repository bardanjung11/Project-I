
<?php
require_once '../config.php';

// Verify token
$user = verifyToken();

if (!$user) {
    sendResponse(false, "Unauthorized", null, 401);
}

$user_id = $user['user_id'];

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Get user's registrations with tournament details
$stmt = $conn->prepare("
    SELECT 
        r.id,
        r.team_name,
        r.registration_date,
        t.id as tournament_id,
        t.name,
        t.game,
        t.match_type,
        t.start_date,
        t.end_date,
        t.max_players,
        t.prize_pool,
        t.entry_fee,
        t.map,
        t.status,
        COUNT(DISTINCT r2.id) as current_players
    FROM registrations r
    INNER JOIN tournaments t ON r.tournament_id = t.id
    LEFT JOIN registrations r2 ON t.id = r2.tournament_id
    WHERE r.user_id = ?
    GROUP BY r.id, t.id
    ORDER BY r.registration_date DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[] = [
        'id' => $row['id'],
        'team_name' => $row['team_name'],
        'registration_date' => $row['registration_date'],
        'tournament' => [
            'id' => $row['tournament_id'],
            'name' => $row['name'],
            'game' => $row['game'],
            'match_type' => $row['match_type'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'max_players' => $row['max_players'],
            'current_players' => $row['current_players'],
            'prize_pool' => $row['prize_pool'],
            'entry_fee' => $row['entry_fee'],
            'map' => $row['map'],
            'status' => $row['status']
        ]
    ];
}

sendResponse(true, "Registrations retrieved successfully", [
    'registrations' => $registrations,
    'count' => count($registrations)
]);

$stmt->close();
$db->close();
?>