<?php
session_start();
include 'db_connect.php';

// Security: Ensure only an admin can access this script
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect non-admins or logged-out users
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// Check if the form was submitted correctly
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: report.php?error=invalid_request");
    exit();
}

// --- VALIDATE INPUT ---
if (!isset($_POST['attendance_id']) || !is_numeric($_POST['attendance_id'])) {
    header("Location: report.php?error=missing_id");
    exit();
}

$attendance_id = (int)$_POST['attendance_id'];
$notes = trim($_POST['justification_notes']) ?? '';
$image_path = null;

// --- HANDLE FILE UPLOAD ---
// Check if a file was uploaded without errors
if (isset($_FILES['justification_image']) && $_FILES['justification_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['justification_image'];
    $upload_dir = 'uploads/'; // The folder we created earlier
    
    // Security: Check file size (e.g., max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        header("Location: report.php?error=file_too_large");
        exit();
    }
    
    // Security: Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        header("Location: report.php?error=invalid_file_type");
        exit();
    }
    
    // Create a unique filename to prevent overwriting existing files
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid('certif_', true) . '.' . $file_extension;
    $target_path = $upload_dir . $unique_filename;
    
    // Move the uploaded file from the temporary directory to our uploads folder
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $image_path = $target_path;
    } else {
        header("Location: report.php?error=upload_failed");
        exit();
    }
}

// --- UPDATE DATABASE ---
// At least notes or an image must be provided
if (empty($notes) && $image_path === null) {
    header("Location: report.php?error=no_justification_provided");
    exit();
}

try {
    $stmt = $conn->prepare("
        UPDATE attendance 
        SET 
            justification_status = 'Approved', 
            justification_image_path = ?, 
            justification_notes = ? 
        WHERE 
            attendance_id = ?
    ");
    
    $stmt->bind_param("ssi", $image_path, $notes, $attendance_id);
    
    if ($stmt->execute()) {
        header("Location: report.php?success=justification_saved");
    } else {
        throw new Exception("Database update failed.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Log the error for debugging and redirect with a generic error
    error_log("Justification Error: " . $e->getMessage());
    header("Location: report.php?error=db_error");
}

exit();
?>