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
if (empty($_POST['absence_ids']) || !is_array($_POST['absence_ids'])) {
    header("Location: student_dashboard.php?error=no_absences_selected");
    exit();
}
if (!isset($_FILES['justification_image']) || $_FILES['justification_image']['error'] !== UPLOAD_ERR_OK) {
    header("Location: student_dashboard.php?error=file_missing");
    exit();
}

$student_id = $_SESSION['student_id'];
$absence_ids = $_POST['absence_ids'];
$student_notes = trim($_POST['student_notes'] ?? '');
$image_path = null;

// Sanitize all absence IDs to ensure they are integers
$sanitized_absence_ids = array_map('intval', $absence_ids);

// --- HANDLE FILE UPLOAD ---
$file = $_FILES['justification_image'];
$upload_dir = 'uploads/';
// (Security checks are the same as before)
if ($file['size'] > 5 * 1024 * 1024) { header("Location: student_dashboard.php?error=file_too_large"); exit(); }
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, $allowed_types)) { header("Location: student_dashboard.php?error=invalid_file_type"); exit(); }

$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid('certif_multi_', true) . '.' . $file_extension;
$target_path = $upload_dir . $unique_filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $image_path = $target_path;
} else {
    header("Location: student_dashboard.php?error=upload_failed");
    exit();
}

// --- DATABASE TRANSACTION ---
// This is critical. If any part fails, the whole process is rolled back.
$conn->begin_transaction();

try {
    // Step 1: Create a single justification request
    $stmt1 = $conn->prepare(
        "INSERT INTO justification_requests (student_id, certificate_image_path, student_notes) VALUES (?, ?, ?)"
    );
    $stmt1->bind_param("iss", $student_id, $image_path, $student_notes);
    if (!$stmt1->execute()) {
        throw new Exception("Failed to create justification request.");
    }
    $new_request_id = $conn->insert_id; // Get the ID of the new request we just created
    $stmt1->close();

    // Step 2: Link all selected absences to this new request
    // Create a string of placeholders (?, ?, ?) for the IN clause
    $placeholders = implode(',', array_fill(0, count($sanitized_absence_ids), '?'));
    $sql_update = "UPDATE attendance SET justification_request_id = ? WHERE student_id = ? AND attendance_id IN ($placeholders)";
    
    // Prepare the parameters for binding
    $params_to_bind = array_merge([$new_request_id, $student_id], $sanitized_absence_ids);
    // Prepare the types string ('ii' + 'i' for each absence ID)
    $types = 'ii' . str_repeat('i', count($sanitized_absence_ids));

    $stmt2 = $conn->prepare($sql_update);
    $stmt2->bind_param($types, ...$params_to_bind);

    if (!$stmt2->execute()) {
        throw new Exception("Failed to link absences to the request.");
    }
    $stmt2->close();
    
    // If we reached here, everything was successful
    $conn->commit();
    header("Location: student_dashboard.php?success=request_sent");

} catch (Exception $e) {
    // If anything went wrong, roll back all database changes
    $conn->rollback();
    error_log("Multi-Justification Error: " . $e->getMessage());
    header("Location: student_dashboard.php?error=db_error");
}

$conn->close();
exit();
?>