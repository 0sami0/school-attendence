<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$sql = "SELECT 
            a.attendance_date, a.attendance_period, tt.start_time, tt.end_time,
            m.module_name, t.first_name AS teacher_first_name, t.last_name AS teacher_last_name
        FROM attendance a
        JOIN timetable tt ON a.timetable_id = tt.timetable_id
        JOIN modules m ON tt.module_id = m.module_id
        JOIN teachers t ON tt.teacher_id = t.teacher_id
        WHERE a.student_id = ? AND a.status = 'Absent'
        ORDER BY a.attendance_date DESC, a.attendance_period ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$absences_result = $stmt->get_result();
$total_absences = $absences_result->num_rows;
$penalty_points = $total_absences * 0.25;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Espace Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --emg-blue: #00529b; --emg-yellow: #ffd100; --danger-color: #dc3545; --text-light: #ffffff;
            --background-light: #f8fafc; --border-color: #e5e7eb; --text-dark: #1f2937; --text-medium: #4b5563;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-light); color: var(--text-dark); }
        .emg-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light); }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: var(--text-light); color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600; transition: background-color 0.2s, color 0.2s; }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        .page-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .page-container h1 { font-size: 28px; font-weight: 700; margin-bottom: 1rem; }
        .page-container h2 { font-size: 22px; font-weight: 600; margin-top: 2.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .stat-card { background-color: #fff; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card .icon { font-size: 1.5rem; color: var(--emg-blue); margin-bottom: 1rem; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; border-radius: 8px; }
        .stat-card .value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); }
        .stat-card .label { font-size: 0.9rem; color: var(--text-medium); margin-top: 0.25rem; }
        .absence-list { display: flex; flex-direction: column; gap: 1rem; }
        .absence-item { display: flex; align-items: center; gap: 1.5rem; background-color: #fff; padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .absence-item:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.07); }
        .date-badge { background-color: #f3f4f6; color: var(--text-dark); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 600; line-height: 1.2; flex-shrink: 0; }
        .date-badge .day { font-size: 1.75rem; display: block; color: var(--emg-blue); }
        .date-badge .month { font-size: 0.8rem; text-transform: uppercase; display: block; }
        .absence-details { flex-grow: 1; }
        .module-name { font-weight: 600; font-size: 1.1rem; color: var(--text-dark); }
        .period-info { font-size: 0.9em; color: var(--text-medium); margin-top: 0.25rem; }
        .no-absences-message { text-align: center; padding: 3rem; background-color: #fff; border-radius: 12px; border: 2px dashed var(--border-color); color: var(--text-medium); }
        .no-absences-message i { font-size: 2rem; margin-bottom: 1rem; color: #10b981; }
    </style>
</head>
<body>
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <main class="page-container">
        <h1>Mon Tableau de Bord</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-user-times"></i></div>
                <div class="value"><?php echo $total_absences; ?></div>
                <div class="label">Absences enregistrées</div>
            </div>
            <div class="stat-card">
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="value" style="color: var(--danger-color);">-<?php echo number_format($penalty_points, 2); ?> pts</div>
                <div class="label">Pénalité estimée</div>
            </div>
        </div>

        <h2>Historique de vos absences</h2>
        <div class="absence-list">
            <?php if ($total_absences > 0): ?>
                <?php while($absence = $absences_result->fetch_assoc()): ?>
                <div class="absence-item">
                    <div class="date-badge">
                        <span class="day"><?php echo date('d', strtotime($absence['attendance_date'])); ?></span>
                        <span class="month"><?php echo date('M', strtotime($absence['attendance_date'])); ?></span>
                    </div>
                    <div class="absence-details">
                        <div class="module-name"><?php echo htmlspecialchars($absence['module_name']); ?></div>
                        <div class="period-info">
                            Période <?php echo $absence['attendance_period']; ?> (<?php echo date('H:i', strtotime($absence['start_time'])); ?>-<?php echo date('H:i', strtotime($absence['end_time'])); ?>)
                             - Prof. <?php echo htmlspecialchars($absence['teacher_last_name']); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-absences-message">
                    <i class="fas fa-check-circle"></i>
                    <p>Félicitations, vous n'avez aucune absence enregistrée !</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>