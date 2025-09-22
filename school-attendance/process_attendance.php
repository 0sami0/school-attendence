<?php
session_start();
include 'db_connect.php';
include 'get_week_dates.php'; // Inclure notre nouveau fichier helper

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['session_id']) || !isset($_POST['student_ids']) || !isset($_POST['attendance'])) {
    header("Location: dashboard.php?error=missing_data");
    exit();
}

$session_id = (int)$_POST['session_id'];
$student_ids = $_POST['student_ids'];
$attendance_data = $_POST['attendance'];

// --- NOUVELLE LOGIQUE D'ARCHIVAGE ET DE DATE ---

// 1. Obtenir les informations de la session et le jour de la semaine
$session_query = $conn->prepare("SELECT day_of_week FROM timetable WHERE timetable_id = ?");
$session_query->bind_param("i", $session_id);
$session_query->execute();
$session_result = $session_query->get_result();
if ($session_result->num_rows === 0) {
    header("Location: dashboard.php?error=invalid_session");
    exit();
}
$session_info = $session_result->fetch_assoc();
$day_of_week = $session_info['day_of_week']; // Ex: 'Lundi'

// 2. Déterminer la date correcte pour la séance
$attendance_date = getDateForDayOfWeek($day_of_week); // Ex: '2023-10-23'

// 3. Déterminer le début de la semaine pour l'archivage
$week_dates = getWeekRange(new DateTime($attendance_date));
$week_start_date = $week_dates['start']; // Ex: '2023-10-23'

$conn->begin_transaction();

try {
    // 4. Vérifier si l'emploi du temps de cette semaine a déjà été archivé
    $history_check = $conn->prepare("SELECT COUNT(*) as count FROM timetable_history WHERE week_start_date = ?");
    $history_check->bind_param("s", $week_start_date);
    $history_check->execute();
    $history_exists = $history_check->get_result()->fetch_assoc()['count'] > 0;
    $history_check->close();

    // 5. Si non, archiver l'emploi du temps actif
    if (!$history_exists) {
        $archive_sql = "
            INSERT INTO timetable_history (timetable_id, teacher_id, module_id, group_id, day_of_week, start_time, end_time, week_start_date)
            SELECT timetable_id, teacher_id, module_id, group_id, day_of_week, start_time, end_time, ?
            FROM timetable
            WHERE is_active = 1
        ";
        $archive_stmt = $conn->prepare($archive_sql);
        $archive_stmt->bind_param("s", $week_start_date);
        $archive_stmt->execute();
        $archive_stmt->close();
    }

    // 6. Préparer la requête d'insertion/mise à jour des présences
    // Notez les nouvelles colonnes : original_timetable_id et week_start_date
    $sql = "INSERT INTO attendance (student_id, timetable_id, original_timetable_id, attendance_date, week_start_date, attendance_period, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)";
    
    $stmt = $conn->prepare($sql);

    foreach ($student_ids as $student_id) {
        foreach ([1, 2] as $period) {
            if (isset($attendance_data[$student_id][$period])) {
                $status = $attendance_data[$student_id][$period];
                // Lier les nouveaux paramètres
                $stmt->bind_param("iiissis", $student_id, $session_id, $session_id, $attendance_date, $week_start_date, $period, $status);
                $stmt->execute();
            }
        }
    }
    
    $conn->commit();
    header("Location: dashboard.php?success=attendance_saved");

} catch (Exception $e) {
    $conn->rollback();
    error_log("Attendance Error: " . $e->getMessage()); // Log l'erreur pour le débogage
    header("Location: dashboard.php?error=db_error&msg=" . urlencode($e->getMessage()));
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
exit();
?>