<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "University_complaint_system";

// Connect
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get input
$name = $_POST['name'];
$department = $_POST['department'];
$id_number = $_POST['id_number'];
$role = $_POST['role'];

// Save user
$sql = "INSERT INTO users (name, department, id_number, role) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $name, $department, $id_number, $role);
$stmt->execute();

// Get user ID
$user_id = $stmt->insert_id;

// Redirect to complaint page with user_id
header("Location: complaint.html?user_id=" . $user_id);
exit();
?>
