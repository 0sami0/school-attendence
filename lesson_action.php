<?php
/**
 * lesson_action.php
 * Gère les actions CRUD (Create, Update, Delete) pour les séances de l'emploi du temps.
 * Inclut la sécurité CSRF, la validation des conflits d'horaires et la logique de "Soft Delete".
 */

session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// --- 1. Vérifications de sécurité ---

if (!isset($_SESSION['teacher_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé. Vous devez être administrateur.']);
    exit();
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Jeton de sécurité invalide. Veuillez rafraîchir la page.']);
    exit();
}

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Aucune action spécifiée.']);
    exit();
}

$action = $_GET['action'];

// --- 2. Routeur d'actions ---

switch ($action) {
    case 'create':
        handle_create($conn);
        break;
    case 'update':
        handle_update($conn);
        break;
    case 'delete':
        handle_delete($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
        exit();
}

mysqli_close($conn);

// --- 3. Fonctions de gestion ---

function validate_input($data) {
    $required_fields = ['group_id', 'module_id', 'teacher_id', 'day_of_week', 'start_time', 'end_time'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return "Tous les champs sont obligatoires.";
        }
    }
    if ($data['start_time'] >= $data['end_time']) {
        return "L'heure de début doit être antérieure à l'heure de fin.";
    }
    return null;
}

function check_conflict($conn, $day, $start_time, $end_time, $teacher_id, $group_id, $exclude_id = 0) {
    // MODIFIED: Check for conflicts only with currently active sessions using the date range.
    $sql = "SELECT COUNT(timetable_id) as conflict_count FROM timetable 
            WHERE day_of_week = ? 
            AND (teacher_id = ? OR group_id = ?)
            AND start_time < ? 
            AND end_time > ?
            AND timetable_id != ?
            AND CURDATE() BETWEEN valid_from AND IFNULL(valid_until, '9999-12-31')";
            
    $stmt = mysqli_prepare($conn, $sql);
    
    mysqli_stmt_bind_param($stmt, "siissi", $day, $teacher_id, $group_id, $end_time, $start_time, $exclude_id);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row['conflict_count'] > 0;
}

function handle_create($conn) {
    $error = validate_input($_POST);
    if ($error) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }
    
    if (check_conflict($conn, $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time'], $_POST['teacher_id'], $_POST['group_id'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Conflit d\'horaire : Le professeur ou le groupe est déjà occupé sur ce créneau.']);
        exit();
    }

    // The 'valid_from' and 'valid_until' columns will use their default values upon creation.
    $sql = "INSERT INTO timetable (group_id, module_id, teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iissss", $_POST['group_id'], $_POST['module_id'], $_POST['teacher_id'], $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time']);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'La séance a été ajoutée avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'ajout de la séance.']);
    }
    mysqli_stmt_close($stmt);
}

function handle_update($conn) {
    if (empty($_POST['timetable_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identifiant de la séance manquant.']);
        exit();
    }

    $error = validate_input($_POST);
    if ($error) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $error]);
        exit();
    }

    if (check_conflict($conn, $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time'], $_POST['teacher_id'], $_POST['group_id'], $_POST['timetable_id'])) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Conflit d\'horaire : Le professeur ou le groupe est déjà occupé sur ce créneau.']);
        exit();
    }

    $sql = "UPDATE timetable SET group_id = ?, module_id = ?, teacher_id = ?, day_of_week = ?, start_time = ?, end_time = ? WHERE timetable_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iissssi", $_POST['group_id'], $_POST['module_id'], $_POST['teacher_id'], $_POST['day_of_week'], $_POST['start_time'], $_POST['end_time'], $_POST['timetable_id']);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'La séance a été modifiée avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la modification de la séance.']);
    }
    mysqli_stmt_close($stmt);
}

/**
 * Gère la suppression d'une séance (logique de "Soft Delete").
 */
function handle_delete($conn) {
    if (empty($_POST['timetable_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Identifiant de la séance manquant.']);
        exit();
    }

    // MODIFIED: Use the new Soft Delete logic by setting the 'valid_until' date to yesterday.
    $sql = "UPDATE timetable SET valid_until = CURDATE() - INTERVAL 1 DAY WHERE timetable_id = ? AND valid_until IS NULL";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_POST['timetable_id']);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'La séance a été annulée avec succès.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'annulation de la séance.']);
    }
    mysqli_stmt_close($stmt);
}
?>