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
            // La base de données gère la suppression en cascade des groupes et modules
            $stmt = $conn->prepare("DELETE FROM classes WHERE class_id = ?");
            $redirect_page = 'manage_classes.php';
            break;

        case 'group':
            // La base de données gère la suppression en cascade des étudiants
            $stmt = $conn->prepare("DELETE FROM `groups` WHERE group_id = ?");
            $redirect_page = 'manage_classes.php';
            break;

        case 'student':
            // La suppression des absences est gérée en cascade si la BDD est mise à jour
            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $redirect_page = 'students.php';
            break;

        case 'teacher':
            if ($id === 1) { throw new Exception("Impossible de supprimer l'admin principal."); }
            $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
            $redirect_page = 'manage_teachers.php';
            break;

        case 'timetable':
            $stmt = $conn->prepare("UPDATE timetable SET is_active = 0 WHERE timetable_id = ?");
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
    // Gère les erreurs de contrainte (ex: un prof ne peut être supprimé s'il a des cours)
    header("Location: $redirect_page?error=" . urlencode($e->getMessage()));
}

exit();
?>