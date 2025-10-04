<?php
session_start();
include 'db_connect.php';

// Security: Ensure only an admin can access this script
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Check if the form was submitted correctly
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_justifications.php?error=invalid_request");
    exit();
}

// --- VALIDATE INPUT ---
if (!isset($_POST['attendance_id']) || !is_numeric($_POST['attendance_id']) || !isset($_POST['action'])) {
    header("Location: manage_justifications.php?error=missing_data");
    exit();
}

$attendance_id = (int)$_POST['attendance_id'];
$action = $_POST['action']; // 'approve' or 'reject'

// Determine the new status based on the action
$new_status = '';
if ($action === 'approve') {
    $new_status = 'Approved';
} elseif ($action === 'reject') {
    $new_status = 'Rejected';
} else {
    // If the action is not recognized, abort
    header("Location: manage_justifications.php?error=invalid_action");
    exit();
}

// --- UPDATE DATABASE ---
try {
    // We only update records that are currently 'Pending' to prevent accidental changes
    $stmt = $conn->prepare("
        UPDATE attendance 
        SET 
            justification_status = ?
        WHERE 
            attendance_id = ? AND justification_status = 'Pending'
    ");
    
    $stmt->bind_param("si", $new_status, $attendance_id);
    
    if ($stmt->execute()) {
        // Check if any row was actually changed
        if ($stmt->affected_rows > 0) {
            header("Location: manage_justifications.php?success=action_saved");
        } else {
            // This can happen if the request was already handled in another tab
            header("Location: manage_justifications.php?error=already_processed");
        }
    } else {
        throw new Exception("Database update failed.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Process Justification Error: " . $e->getMessage());
    header("Location: manage_justifications.php?error=db_error");
}

exit();