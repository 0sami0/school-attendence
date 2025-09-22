<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['parent_email']) || empty($_POST['group_id'])) {
        // Handle error - maybe redirect with an error message
        header("Location: students.php?error=MissingFields");
        exit();
    }

    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $parentEmail = $_POST['parent_email'];
    $groupId = $_POST['group_id'];

    $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, parent_email, group_id) VALUES (?, ?, ?, ?)");
    // 'sssi' specifies the types of the variables: s = string, i = integer
    $stmt->bind_param("sssi", $firstName, $lastName, $parentEmail, $groupId);

    if ($stmt->execute()) {
        // Success
    } else {
        // Failure
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

// Redirect back to the students page after processing
header("Location: students.php");
exit();
?>