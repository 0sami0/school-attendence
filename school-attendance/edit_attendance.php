<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id'])) { header("Location: index.php"); exit(); }
$teacher_id = $_SESSION['teacher_id'];

if (!isset($_GET['session_id']) || !isset($_GET['date'])) { header("Location: select_correction.php?error=missing_info"); exit(); }
$session_id = (int)$_GET['session_id'];
$attendance_date = $_GET['date'];

$verify_query = $conn->prepare("SELECT group_id FROM timetable WHERE timetable_id = ? AND teacher_id = ?");
$verify_query->bind_param("ii", $session_id, $teacher_id);
$verify_query->execute();
$verify_result = $verify_query->get_result();
if ($verify_result->num_rows == 0) { header("Location: dashboard.php?error=access_denied"); exit(); }
$group_id = $verify_result->fetch_assoc()['group_id'];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_ids']) && isset($_POST['attendance'])) {
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO attendance (student_id, timetable_id, attendance_date, attendance_period, status) 
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status)";
            $stmt = $conn->prepare($sql);

            foreach ($_POST['student_ids'] as $student_id) {
                $status_p1 = $_POST['attendance'][$student_id][1] ?? 'Absent';
                $period1 = 1;
                $stmt->bind_param("iisis", $student_id, $session_id, $attendance_date, $period1, $status_p1);
                $stmt->execute();
                
                $status_p2 = $_POST['attendance'][$student_id][2] ?? 'Absent';
                $period2 = 2;
                $stmt->bind_param("iisis", $student_id, $session_id, $attendance_date, $period2, $status_p2);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            $message = '<div class="alert alert-success">La feuille d\'appel a été mise à jour avec succès.</div>';
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="alert alert-danger">Erreur: Impossible de mettre à jour la feuille d\'appel.</div>';
        }
    }
}

$info_query = $conn->prepare("SELECT m.module_name, c.class_name, g.group_name FROM timetable tt JOIN modules m ON tt.module_id=m.module_id JOIN `groups` g ON tt.group_id=g.group_id JOIN classes c ON g.class_id=c.class_id WHERE tt.timetable_id=?");
$info_query->bind_param("i", $session_id);
$info_query->execute();
$info = $info_query->get_result()->fetch_assoc();

$students_query = $conn->prepare("SELECT s.student_id, s.first_name, s.last_name, MAX(CASE WHEN a.attendance_period=1 THEN a.status END) as status_p1, MAX(CASE WHEN a.attendance_period=2 THEN a.status END) as status_p2 FROM students s LEFT JOIN attendance a ON s.student_id=a.student_id AND a.timetable_id=? AND a.attendance_date=? WHERE s.group_id=? GROUP BY s.student_id ORDER BY s.last_name, s.first_name");
$students_query->bind_param("isi", $session_id, $attendance_date, $group_id);
$students_query->execute();
$students_result = $students_query->get_result();
function getInitials($f, $l) { return strtoupper(mb_substr(trim($f),0,1).mb_substr(trim($l),0,1)); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'Appel - EMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root { --emg-blue: #00529b; --emg-yellow: #ffd100; --danger-color: #dc3545; --text-light: #ffffff; --background-light: #f8fafc; --border-color: #e5e7eb; --text-dark: #1f2937; --text-medium: #4b5563; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-light); color: var(--text-dark); }
        .emg-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light); }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: var(--text-light); color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600; transition: background-color 0.2s, color 0.2s; }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        .page-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: var(--text-dark); }
        .page-header p { color: var(--text-medium); }
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; border: 1px solid transparent; }
        .alert-success { background-color: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .alert-danger { background-color: #fecaca; color: #991b1b; border-color: #fca5a5; }
        .attendance-container { background-color: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid var(--border-color); }
        .list-header { display: grid; grid-template-columns: 3fr 1fr 1fr; padding: 15px 25px; background-color: #f9fafb; color: var(--text-medium); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .student-row { display: grid; grid-template-columns: 3fr 1fr 1fr; align-items: center; padding: 12px 25px; border-top: 1px solid var(--border-color); }
        .student-details { display: flex; align-items: center; gap: 15px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--emg-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; }
        .student-name { font-weight: 500; color: #111827; }
        .period-controls { display: flex; align-items: center; gap: 10px; }
        .btn-attendance-circle { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border-color); background-color: #fff; color: var(--text-medium); font-weight: bold; cursor: pointer; transition: all 0.2s ease-in-out; }
        .btn-attendance-circle:hover { border-color: var(--emg-blue); }
        .btn-attendance-circle.active.btn-present { background-color: var(--emg-blue); color: white; border-color: var(--emg-blue); }
        .btn-attendance-circle.active.btn-absent { background-color: var(--danger-color); color: white; border-color: var(--danger-color); }
        .form-actions { margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid transparent; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background-color: var(--emg-blue); color: white; }
        .btn-primary:hover { background-color: #00417a; }
        .btn-secondary { background-color: white; color: var(--emg-blue); border-color: var(--emg-blue); }
        .btn-secondary:hover { background-color: var(--emg-blue); color: white; }
    </style>
</head>
<body>
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="dashboard.php" class="btn-logout">Retour</a>
        </div>
    </header>
    <main class="page-container">
        <div class="page-header">
            <h1>Modifier la Feuille d'Appel</h1>
            <p><strong>Module:</strong> <?php echo htmlspecialchars($info['module_name']); ?> | <strong>Groupe:</strong> <?php echo htmlspecialchars($info['class_name'].' - '.$info['group_name']); ?> | <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($attendance_date)); ?></p>
        </div>
        <?php echo $message; ?>
        <form method="POST" action="">
            <div class="attendance-container">
                <div class="list-header"><span>Étudiant</span><span>1ère Période</span><span>2ème Période</span></div>
                <?php while($student = $students_result->fetch_assoc()):
                    $status_p1 = $student['status_p1'] ?? 'Present';
                    $status_p2 = $student['status_p2'] ?? 'Present';
                ?>
                    <div class="student-row">
                        <div class="student-details">
                            <div class="avatar"><?php echo getInitials($student['first_name'], $student['last_name']); ?></div>
                            <span class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                        </div>
                        <input type="hidden" name="student_ids[]" value="<?php echo $student['student_id']; ?>">
                        <div class="period-controls">
                            <button type="button" class="btn-attendance-circle btn-present <?php if($status_p1 === 'Present') echo 'active'; ?>">P</button>
                            <button type="button" class="btn-attendance-circle btn-absent <?php if($status_p1 === 'Absent') echo 'active'; ?>">A</button>
                            <input type="hidden" class="attendance-input" name="attendance[<?php echo $student['student_id']; ?>][1]" value="<?php echo $status_p1; ?>">
                        </div>
                        <div class="period-controls">
                            <button type="button" class="btn-attendance-circle btn-present <?php if($status_p2 === 'Present') echo 'active'; ?>">P</button>
                            <button type="button" class="btn-attendance-circle btn-absent <?php if($status_p2 === 'Absent') echo 'active'; ?>">A</button>
                            <input type="hidden" class="attendance-input" name="attendance[<?php echo $student['student_id']; ?>][2]" value="<?php echo $status_p2; ?>">
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="form-actions">
                <a href="select_correction.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Annuler</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer les Modifications</button>
            </div>
        </form>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.period-controls').forEach(control => {
            control.addEventListener('click', function(event) {
                const button = event.target.closest('.btn-attendance-circle');
                if (!button) return;
                const parent = button.parentElement;
                const hiddenInput = parent.querySelector('.attendance-input');
                const newStatus = button.classList.contains('btn-present') ? 'Present' : 'Absent';
                hiddenInput.value = newStatus;
                parent.querySelector('.btn-present').classList.remove('active');
                parent.querySelector('.btn-absent').classList.remove('active');
                button.classList.add('active');
            });
        });
    </script>
</body>
</html>