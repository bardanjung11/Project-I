<?php
require_once '../config.php';

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['username']) || !isset($data['password'])) {
    sendResponse(false, "Username and password are required", null, 400);
}

$username = trim($data['username']);
$password = $data['password'];

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Get user from database
$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    sendResponse(false, "Invalid username or password", null, 401);
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    sendResponse(false, "Invalid username or password", null, 401);
}

// Generate token
$token = generateToken($user['id'], $user['username'], $user['role']);

sendResponse(true, "Login successful", [
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);

$stmt->close();
$db->close();
?>