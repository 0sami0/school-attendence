<?php
session_start();
include 'db_connect.php';
include 'get_week_dates.php'; // Helper for dates

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['session_id']) || !is_numeric($_GET['session_id'])) {
    header("Location: dashboard.php?error=invalid_session");
    exit();
}

$session_id = (int)$_GET['session_id'];

// --- NEW LOGIC FOR SESSION AND PRE-FILLING ---

// 1. Retrieve session information, ensuring it's currently valid for the teacher.
$session_query = $conn->prepare("
    SELECT tt.group_id, tt.start_time, tt.end_time, tt.day_of_week, m.module_name, c.class_name, g.group_name
    FROM timetable tt
    JOIN modules m ON tt.module_id = m.module_id
    JOIN `groups` g ON tt.group_id = g.group_id
    JOIN classes c ON g.class_id = c.class_id
    WHERE tt.timetable_id = ? 
      AND tt.teacher_id = ? 
      AND CURDATE() BETWEEN tt.valid_from AND IFNULL(tt.valid_until, '9999-12-31')
");
$session_query->bind_param("ii", $session_id, $_SESSION['teacher_id']);
$session_query->execute();
$session_result = $session_query->get_result();
if ($session_result->num_rows === 0) {
    // This session is either not for this teacher or is no longer active.
    header("Location: dashboard.php?error=access_denied");
    exit();
}
$session = $session_result->fetch_assoc();
$group_id = $session['group_id'];
$day_of_week = $session['day_of_week']; // e.g., 'Monday'

// 2. Determine the exact date of this session for the current week.
$attendance_date_for_query = getDateForDayOfWeek($day_of_week);

// 3. Retrieve any existing attendance for THIS session on THIS specific date.
// The `timetable_id` now directly links to the correct schedule entry.
$existing_attendance = [];
$attendance_query = $conn->prepare("
    SELECT student_id, attendance_period, status 
    FROM attendance 
    WHERE timetable_id = ? AND attendance_date = ?
");
$attendance_query->bind_param("is", $session_id, $attendance_date_for_query);
$attendance_query->execute();
$attendance_result = $attendance_query->get_result();
while ($row = $attendance_result->fetch_assoc()) {
    $existing_attendance[$row['student_id']][$row['attendance_period']] = $row['status'];
}
$attendance_query->close();

// --- END OF NEW LOGIC ---

$students_query = $conn->prepare("SELECT student_id, first_name, last_name FROM students WHERE group_id = ? ORDER BY last_name, first_name");
$students_query->bind_param("i", $group_id);
$students_query->execute();
$students_result = $students_query->get_result();

function getInitials($firstName, $lastName) {
    $first = !empty($firstName) ? mb_substr(trim($firstName), 0, 1) : '';
    $last = !empty($lastName) ? mb_substr(trim($lastName), 0, 1) : '';
    return strtoupper($first . $last);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Faire l'Appel - EMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* --- CHARTE GRAPHIQUE EMG --- */
        :root {
            --emg-blue: #00529b;
            --emg-yellow: #ffd100;
            --danger-color: #dc3545;
            --text-light: #ffffff;
            --background-light: #f8fafc;
            --border-color: #e5e7eb;
            --text-dark: #1f2937;
            --text-medium: #4b5563;
        }

        /* --- Styles Généraux --- */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
        }

        /* --- En-tête de page --- */
        .emg-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light);
        }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout {
            display: inline-block; padding: 8px 16px; background-color: var(--text-light);
            color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        
        /* --- Conteneur & En-tête de contenu --- */
        .page-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; color: var(--text-dark); }
        .page-header p { color: var(--text-medium); }
        
        /* --- Style de la liste d'appel --- */
        .attendance-container {
            background-color: #fff; border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .list-header {
            display: grid; grid-template-columns: 3fr 1fr 1fr;
            padding: 15px 25px; background-color: #f9fafb;
            color: var(--text-medium); font-weight: 600; font-size: 0.8rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .student-row {
            display: grid; grid-template-columns: 3fr 1fr 1fr;
            align-items: center; padding: 12px 25px;
            border-top: 1px solid var(--border-color);
        }
        .student-details { display: flex; align-items: center; gap: 15px; }
        .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background-color: var(--emg-blue); color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.9rem;
        }
        .student-name { font-weight: 500; color: #111827; }
        .period-controls { display: flex; align-items: center; gap: 10px; }
        .btn-attendance-circle {
            width: 32px; height: 32px; border-radius: 50%;
            border: 1px solid var(--border-color); background-color: #fff;
            color: var(--text-medium); font-weight: bold; cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .btn-attendance-circle:hover { border-color: var(--emg-blue); }
        .btn-attendance-circle.active.btn-present {
            background-color: var(--emg-blue); color: white; border-color: var(--emg-blue);
        }
        .btn-attendance-circle.active.btn-absent {
            background-color: var(--danger-color); color: white; border-color: var(--danger-color);
        }

        /* --- Bouton d'enregistrement --- */
        .form-actions { margin-top: 2rem; text-align: right; }
        .btn-submit {
            padding: 12px 28px; border-radius: 8px; border: none;
            background-color: var(--emg-blue); color: white;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover { background-color: #00417a; }
        
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
            <h1><?php echo htmlspecialchars($session['module_name']); ?></h1>
            <p><?php echo htmlspecialchars($session['class_name'] . ' - ' . $session['group_name']); ?> | <?php echo date('H:i', strtotime($session['start_time'])) . ' - ' . date('H:i', strtotime($session['end_time'])); ?></p>
            
        </div>

        <form action="process_attendance.php" method="post">
            <input type="hidden" name="session_id" value="<?php echo $session_id; ?>">
            <div class="attendance-container">
                <div class="list-header">
                    <span>Étudiant</span><span>1ère Période</span><span>2ème Période</span>
                </div>
                <?php while($student = $students_result->fetch_assoc()): 
                    $student_id = $student['student_id'];
                    // Status for period 1
                    $status_p1 = $existing_attendance[$student_id][1] ?? 'Present';
                    // Status for period 2
                    $status_p2 = $existing_attendance[$student_id][2] ?? 'Present';
                ?>
                    <div class="student-row">
                        <div class="student-details">
                            <div class="avatar"><?php echo getInitials($student['first_name'], $student['last_name']); ?></div>
                            <span class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                        </div>
                        <input type="hidden" name="student_ids[]" value="<?php echo $student['student_id']; ?>">
                        
                        <!-- Période 1 -->
                        <div class="period-controls">
                            <button type="button" class="btn-attendance-circle btn-present <?php echo $status_p1 === 'Present' ? 'active' : ''; ?>">P</button>
                            <button type="button" class="btn-attendance-circle btn-absent <?php echo $status_p1 === 'Absent' ? 'active' : ''; ?>">A</button>
                            <input type="hidden" class="attendance-input" name="attendance[<?php echo $student_id; ?>][1]" value="<?php echo $status_p1; ?>">
                        </div>
                        <!-- Période 2 -->
                        <div class="period-controls">
                            <button type="button" class="btn-attendance-circle btn-present <?php echo $status_p2 === 'Present' ? 'active' : ''; ?>">P</button>
                            <button type="button" class="btn-attendance-circle btn-absent <?php echo $status_p2 === 'Absent' ? 'active' : ''; ?>">A</button>
                            <input type="hidden" class="attendance-input" name="attendance[<?php echo $student_id; ?>][2]" value="<?php echo $status_p2; ?>">
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Enregistrer l'Appel</button>
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
    });
    </script>
</body>
</html>