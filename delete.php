<?php
session_start();
include 'db_connect.php';
include 'check_admin.php';

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    header("Location: dashboard.php?error=invalid_request");
    exit();
}

$type = $_GET['type'];
$id = (int)$_GET['id'];
$redirect_page = 'dashboard.php';

try {
    switch ($type) {
        case 'class':
            // The database handles cascading deletes for groups and modules
            $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
            $redirect_page = 'manage_classes.php';
            break;

        case 'group':
            // The database handles cascading deletes for students
            $stmt = $conn->prepare("DELETE FROM `groups` WHERE group_id = ?");
            $redirect_page = 'manage_classes.php';
            break;

        case 'student':
            // Cascading delete for attendance should be configured in the DB if needed
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $redirect_page = 'students.php';
            break;

        case 'teacher':
            if ($id === 1) { throw new Exception("Impossible de supprimer l'admin principal."); }
            $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
            $redirect_page = 'manage_teachers.php';
            break;

        case 'timetable':
            // New Soft Delete Logic: End the session's validity as of yesterday.
            // This preserves the entry for historical queries.
            // We only update sessions that don't already have an end date.
            $stmt = $conn->prepare("UPDATE timetable SET valid_until = CURDATE() - INTERVAL 1 DAY WHERE timetable_id = ? AND valid_until IS NULL");
            $redirect_page = 'timetable.php';
            break;

        case 'module':
            $stmt = $conn->prepare("DELETE FROM modules WHERE module_id = ?");
            $redirect_page = 'modules.php';
            break;

        default:
            throw new Exception("Type de suppression non valide");
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la suppression.");
        }
    }
    
    header("Location: $redirect_page?success=deleted");

} catch (Exception $e) {
    // Handle constraint errors (e.g., a teacher cannot be deleted if they have classes)
    header("Location: $redirect_page?error=" . urlencode($e->getMessage()));
}

exit();
?>