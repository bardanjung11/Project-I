<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once '../config.php';

// Get and verify token
$token = getBearerToken();
$user = verifyToken($token);

if (!$user) {
    sendResponse(false, "Unauthorized", null, 401);
}

if ($user['role'] !== 'admin') {
    sendResponse(false, "Forbidden: Admin access required", null, 403);
}

// Create database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get all registrations with user and tournament details
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.team_name,
            r.registration_date,
            r.tournament_id,
            u.id as user_id,
            u.username,
            u.email,
            t.name as tournament_name,
            t.game,
            t.start_date
        FROM registrations r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN tournaments t ON r.tournament_id = t.id
        ORDER BY r.registration_date DESC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }

    $stmt->close();
    $db->close();

    sendResponse(true, "All registrations retrieved successfully", [
        'registrations' => $registrations,
        'count' => count($registrations)
    ]);
    
} catch (Exception $e) {
    if (isset($stmt)) $stmt->close();
    $db->close();
    sendResponse(false, "Error: " . $e->getMessage(), null, 500);
}
?>