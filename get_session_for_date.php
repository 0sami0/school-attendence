
<?php
session_start();
include 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['teacher_id']) || !isset($_GET['date'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé ou date manquante.']);
    exit();
}

$teacher_id = $_SESSION['teacher_id'];
$date = $_GET['date'];

$query = $conn->prepare("
    SELECT DISTINCT tt.timetable_id, m.module_name, tt.start_time, tt.end_time
    FROM attendance a
    JOIN timetable tt ON a.timetable_id = tt.timetable_id
    JOIN modules m ON tt.module_id = m.module_id
    WHERE a.attendance_date = ? AND tt.teacher_id = ?
    ORDER BY tt.start_time
");
$query->bind_param("si", $date, $teacher_id);
$query->execute();
$result = $query->get_result();

$sessions = [];
while($row = $result->fetch_assoc()) {
    $row['start_time'] = date('H:i', strtotime($row['start_time']));
    $row['end_time'] = date('H:i', strtotime($row['end_time']));
    $sessions[] = $row;
}

echo json_encode(['success' => true, 'sessions' => $sessions]);
?>