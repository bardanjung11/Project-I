<?php
require_once '../config.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Get optional status filter
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Build query
$query = "SELECT 
    t.*,
    COUNT(DISTINCT r.id) as current_players
FROM tournaments t
LEFT JOIN registrations r ON t.id = r.tournament_id
";

if ($status) {
    $query .= " WHERE t.status = ?";
}

$query .= " GROUP BY t.id ORDER BY t.start_date DESC";

$stmt = $conn->prepare($query);

if ($status) {
    $stmt->bind_param("s", $status);
}

$stmt->execute();
$result = $stmt->get_result();

$tournaments = [];
while ($row = $result->fetch_assoc()) {
    $tournaments[] = $row;
}

sendResponse(true, "Tournaments retrieved successfully", [
    'tournaments' => $tournaments,
    'count' => count($tournaments)
]);

$stmt->close();
$db->close();
?>