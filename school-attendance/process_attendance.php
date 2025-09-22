<?php
session_start();
include 'db_connect.php';

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
$attendance_date = date('Y-m-d'); 

$conn->begin_transaction();
try {
    // Cette requête est maintenant parfaite pour votre nouvelle structure de DB.
    $sql = "INSERT INTO attendance (student_id, timetable_id, attendance_date, attendance_period, status) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)";
    
    $stmt = $conn->prepare($sql);

    foreach ($student_ids as $student_id) {
        // Période 1
        if (isset($attendance_data[$student_id][1])) {
            $status_p1 = $attendance_data[$student_id][1];
            $period1 = 1;
            $stmt->bind_param("iisis", $student_id, $session_id, $attendance_date, $period1, $status_p1);
            $stmt->execute();
        }
        
        // Période 2
        if (isset($attendance_data[$student_id][2])) {
            $status_p2 = $attendance_data[$student_id][2];
            $period2 = 2;
            $stmt->bind_param("iisis", $student_id, $session_id, $attendance_date, $period2, $status_p2);
            $stmt->execute();
        }
    }
    
    $conn->commit();
    header("Location: dashboard.php?success=attendance_saved");

} catch (Exception $e) {
    $conn->rollback();
    // Pour le débogage : error_log($e->getMessage());
    header("Location: dashboard.php?error=db_error");
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
exit();
?>