<?php
// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "university_complaint_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data
$name = $_POST['name'];
$department = $_POST['department'];
$id_number = $_POST['id_number'];
$role = $_POST['role'];
$title = $_POST['title'];
$description = $_POST['description'];

// First, insert user if not exists
$user_check = $conn->prepare("SELECT id FROM users WHERE id_number = ?");
$user_check->bind_param("s", $id_number);
$user_check->execute();
$user_result = $user_check->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];
} else {
    $stmt = $conn->prepare("INSERT INTO users (name, department, id_number, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $department, $id_number, $role);
    $stmt->execute();
    $user_id = $stmt->insert_id;
    $stmt->close();
}
$user_check->close();

// Check for duplicate complaint by same user and title
$check = $conn->prepare("SELECT id FROM complaints WHERE user_id = ? AND title = ?");
$check->bind_param("is", $user_id, $title);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "Duplicate complaint already submitted.";
} else {
    // Insert complaint
    $stmt = $conn->prepare("INSERT INTO complaints (user_id, title, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $description);
    $stmt->execute();
    echo "Complaint submitted successfully!";
    $stmt->close();
}

$check->close();
$conn->close();
?>
