<?php
require_once '../config.php';

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    sendResponse(false, "All fields are required", null, 400);
}

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, "Invalid email format", null, 400);
}

// Validate password length
if (strlen($password) < 6) {
    sendResponse(false, "Password must be at least 6 characters", null, 400);
}

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    sendResponse(false, "Username already exists", null, 400);
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    sendResponse(false, "Email already exists", null, 400);
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    
    // Generate token
    $token = generateToken($userId, $username, 'user');
    
    sendResponse(true, "Registration successful", [
        'token' => $token,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'user'
        ]
    ], 201);
} else {
    sendResponse(false, "Registration failed. Please try again", null, 500);
}

$stmt->close();
$db->close();
?>