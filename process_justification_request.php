<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: manage_justifications.php"); exit(); }

if (!isset($_POST['request_id']) || !is_numeric($_POST['request_id']) || !isset($_POST['action'])) {
    header("Location: manage_justifications.php?error=missing_data");
    exit();
}

$request_id = (int)$_POST['request_id'];
$action = $_POST['action'];

$new_status = '';
if ($action === 'approve') {
    $new_status = 'Approved';
} elseif ($action === 'reject') {
    $new_status = 'Rejected';
} else {
    header("Location: manage_justifications.php?error=invalid_action");
    exit();
}

// Update the status of the entire request
$stmt = $conn->prepare("UPDATE justification_requests SET status = ? WHERE request_id = ? AND status = 'Pending'");
$stmt->bind_param("si", $new_status, $request_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    header("Location: manage_justifications.php?success=action_saved");
} else {
    header("Location: manage_justifications.php?error=db_error_or_processed");
}

$stmt->close();
$conn->close();
exit();