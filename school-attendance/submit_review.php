<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    $teacher_id = $_SESSION['teacher_id'];
    $role = $_SESSION['role'];

    $stmt = $conn->prepare("INSERT INTO reviews (teacher_id, role, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $teacher_id, $role, $comment);
    $stmt->execute();
}

header("Location: dashboard.php?success=review_sent");
exit();
?>