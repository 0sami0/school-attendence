<?php
session_start();
include 'db_connect.php';
include 'check_admin.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // On reçoit maintenant un class_id au lieu d'un group_id
    $classId = (int)$_POST['class_id'];
    $moduleId = (int)$_POST['module_id'];
    $teacherId = (int)$_POST['teacher_id'];
    $dayOfWeek = $_POST['day_of_week'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];

    // Validation
    if (empty($classId) || empty($moduleId) || empty($teacherId) || empty($dayOfWeek) || empty($startTime) || empty($endTime)) {
        header("Location: timetable.php?error=MissingFields");
        exit();
    }
    
    // Démarrer une transaction pour assurer que tout réussit ou tout échoue
    mysqli_begin_transaction($conn);

    try {
        // 1. Trouver tous les groupes associés à la filière sélectionnée
        $stmt_groups = $conn->prepare("SELECT group_id FROM `groups` WHERE class_id = ?");
        $stmt_groups->bind_param("i", $classId);
        $stmt_groups->execute();
        $result = $stmt_groups->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Aucun groupe trouvé pour cette filière.");
        }

        // 2. Préparer la requête d'insertion
        $stmt_insert = $conn->prepare("INSERT INTO timetable (group_id, module_id, teacher_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");

        // 3. Boucler sur chaque groupe et insérer la séance
        while ($group = $result->fetch_assoc()) {
            $groupId = $group['group_id'];
            $stmt_insert->bind_param("iiisss", $groupId, $moduleId, $teacherId, $dayOfWeek, $startTime, $endTime);
            $stmt_insert->execute();
        }
        
        // Si tout s'est bien passé, valider la transaction
        mysqli_commit($conn);
        header("Location: timetable.php?success=true");

    } catch (Exception $e) {
        // En cas d'erreur, annuler toutes les insertions
        mysqli_rollback($conn);
        error_log("Erreur d'ajout à l'emploi du temps: " . $e->getMessage());
        header("Location: timetable.php?error=database_error");
    }

    exit();
}

header("Location: timetable.php");
exit();
?>