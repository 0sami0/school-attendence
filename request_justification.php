<?php
session_start();
include 'db_connect.php';

// Security: Ensure a student is logged in
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_dashboard.php?error=invalid_request");
    exit();
}

// --- VALIDATE INPUT ---
if (!isset($_POST['attendance_id']) || !is_numeric($_POST['attendance_id'])) {
    header("Location: student_dashboard.php?error=missing_id");
    exit();
}
if (!isset($_FILES['justification_image']) || $_FILES['justification_image']['error'] !== UPLOAD_ERR_OK) {
    // For students, the image is mandatory
    header("Location: student_dashboard.php?error=file_missing");
    exit();
}

$attendance_id = (int)$_POST['attendance_id'];
$student_id = $_SESSION['student_id'];
$image_path = null;

// --- VERIFY OWNERSHIP ---
// Security: Make sure the student is only modifying their own absence record
$verify_stmt = $conn->prepare("SELECT student_id FROM attendance WHERE attendance_id = ?");
$verify_stmt->bind_param("i", $attendance_id);
$verify_stmt->execute();
$result = $verify_stmt->get_result();
$record = $result->fetch_assoc();
if (!$record || $record['student_id'] != $student_id) {
    // If the record doesn't exist or doesn't belong to this student, deny access
    header("Location: student_dashboard.php?error=permission_denied");
    exit();
}
$verify_stmt->close();

// --- HANDLE FILE UPLOAD ---
$file = $_FILES['justification_image'];
$upload_dir = 'uploads/';

// Security checks (size, type)
if ($file['size'] > 5 * 1024 * 1024) { // Max 5MB
    header("Location: student_dashboard.php?error=file_too_large");
    exit();
}
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, $allowed_types)) {
    header("Location: student_dashboard.php?error=invalid_file_type");
    exit();
}

// Create unique filename and move the file
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid('request_', true) . '.' . $file_extension;
$target_path = $upload_dir . $unique_filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $image_path = $target_path;
} else {
    header("Location: student_dashboard.php?error=upload_failed");
    exit();
}

// --- UPDATE DATABASE ---
try {
    $stmt = $conn->prepare("
        UPDATE attendance 
        SET 
            justification_status = 'Pending', 
            justification_image_path = ?
        WHERE 
            attendance_id = ? AND student_id = ?
    ");
    
    // Bind the image path, the attendance ID, and the student ID again for safety
    $stmt->bind_param("sii", $image_path, $attendance_id, $student_id);
    
    if ($stmt->execute()) {
        header("Location: student_dashboard.php?success=request_sent");
    } else {
        throw new Exception("Database update failed.");
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log("Justification Request Error: " . $e->getMessage());
    header("Location: student_dashboard.php?error=db_error");
}

exit();
?>