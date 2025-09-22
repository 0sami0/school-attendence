<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$is_admin = ($_SESSION['role'] === 'admin');

// --- REQUÊTES POUR LES GRAPHIQUES ADMIN ---
if ($is_admin) {
    // 1. Taux d'absence global
    $total_absent_query = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE status = 'Absent'");
    $total_absent = $total_absent_query->fetch_assoc()['count'];
    $total_records_query = $conn->query("SELECT COUNT(*) as count FROM attendance");
    $total_records = $total_records_query->fetch_assoc()['count'];
    $absence_percentage = ($total_records > 0) ? round(($total_absent / $total_records) * 100) : 0;

    // 2. Top 5 filières
    $top_classes_query = $conn->query("SELECT c.class_name, COUNT(a.attendance_id) as absence_count FROM attendance a JOIN students s ON a.student_id = s.student_id JOIN `groups` g ON s.group_id = g.group_id JOIN classes c ON g.class_id = c.class_id WHERE a.status = 'Absent' GROUP BY c.class_id ORDER BY absence_count DESC LIMIT 5");
    $top_classes_labels = []; $top_classes_data = [];
    while ($row = $top_classes_query->fetch_assoc()) {
        $top_classes_labels[] = $row['class_name'];
        $top_classes_data[] = $row['absence_count'];
    }

    // 3. Absences des 7 derniers jours
    $last_7_days_query = $conn->query("SELECT DATE(attendance_date) as day, COUNT(*) as daily_absences FROM attendance WHERE status = 'Absent' AND attendance_date >= CURDATE() - INTERVAL 6 DAY GROUP BY day ORDER BY day ASC");
    $last_7_days_data_raw = [];
    while ($row = $last_7_days_query->fetch_assoc()) { $last_7_days_data_raw[$row['day']] = $row['daily_absences']; }
    $last_7_days_labels = []; $last_7_days_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $last_7_days_labels[] = date('D', strtotime($date));
        $last_7_days_data[] = $last_7_days_data_raw[$date] ?? 0;
    }
} else {
    // Logique pour la vue professeur
    $teacher_id = $_SESSION['teacher_id'];
    $timetable_query = $conn->prepare("SELECT tt.timetable_id, tt.day_of_week, tt.start_time, tt.end_time, m.module_name, c.class_name, g.group_name FROM timetable tt JOIN modules m ON tt.module_id=m.module_id JOIN `groups` g ON tt.group_id=g.group_id JOIN classes c ON g.class_id=c.class_id WHERE tt.teacher_id=? AND tt.is_active = 1 ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.start_time");
    $timetable_query->bind_param("i", $teacher_id);
    $timetable_query->execute();
    $sessions = $timetable_query->get_result();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - EMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* --- CHARTE GRAPHIQUE EMG --- */
        :root {
            --emg-blue: #00529b;
            --emg-yellow: #ffd100;
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

        /* --- En-tête Unifié --- */
        .emg-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background-color: var(--emg-blue);
            color: var(--text-light);
        }
        .emg-header .logo img { height: 40px; }
        .emg-header .user-info { display: flex; align-items: center; gap: 16px; }
        .emg-header .user-info span { font-weight: 500; }
        .btn-logout {
            display: inline-block; padding: 8px 16px; background-color: var(--text-light);
            color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        
        /* --- Boutons Génériques --- */
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid transparent; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background-color: var(--emg-blue); color: var(--text-light); }
        .btn-primary:hover { background-color: #00417a; }
        .btn-secondary { background-color: var(--text-light); color: var(--emg-blue); border-color: var(--emg-blue); }
        .btn-secondary:hover { background-color: var(--emg-blue); color: var(--text-light); }

        /* ======================================== */
        /* --- STYLE ADMIN (EMG) --- */
        /* ======================================== */
        .admin-nav { display: flex; gap: 24px; }
        .admin-nav a {
            text-decoration: none; color: var(--text-light); font-weight: 500;
            padding: 8px 0; border-bottom: 2px solid transparent; transition: border-color 0.2s;
        }
        .admin-nav a:hover { border-bottom-color: var(--emg-yellow); }
        .admin-container { padding: 40px; max-width: 1400px; margin: 0 auto; }
        .hero-section { margin-bottom: 40px; }
        .hero-section h1 { font-size: 36px; font-weight: 700; }
        .hero-section p { font-size: 18px; color: var(--text-medium); margin-top: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .stat-card { background-color: #fff; padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 16px; font-weight: 600; color: var(--text-medium); }
        .stat-card .stat-value { font-size: 48px; font-weight: 800; color: var(--emg-blue); margin-top: 8px; }
        .stat-card canvas { max-height: 200px; margin-top: 20px; }

        /* ======================================== */
        /* --- STYLE PROFESSEUR (EMG) --- */
        /* ======================================== */
        .page-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; }
        .day-header {
            margin-top: 2.5rem; margin-bottom: 1rem; font-size: 20px;
            color: var(--emg-blue); border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem;
        }
        .session-card {
            display: flex; justify-content: space-between; align-items: center;
            background-color: #fff; padding: 1.5rem; border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid var(--border-color);
            margin-bottom: 1rem; border-left: 5px solid var(--emg-blue);
        }
        .session-info { display: flex; flex-direction: column; gap: 0.75rem; }
        .session-info-item { display: flex; align-items: center; gap: 0.75rem; color: var(--text-medium); }
        .session-info-item i { width: 16px; text-align: center; color: var(--emg-blue); }
        .session-module { font-weight: 600; color: var(--text-dark); }
        .placeholder { text-align: center; padding: 3rem; color: var(--text-medium); }
        .btn-primary[disabled] {
            background-color: #ccc;
            cursor: not-allowed;
            border-color: #ccc;
        }
         
    </style>
</head>
<body>

<?php if ($is_admin): ?>
    <!-- ================== VUE ADMIN (STYLE EMG) ================== -->
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <nav class="admin-nav">
            <a href="manage_classes.php">Filières</a><a href="modules.php">Modules</a>
            <a href="manage_teachers.php">Professeurs</a><a href="students.php">Étudiants</a>
            <a href="timetable.php">Emploi du Temps</a><a href="report.php">Rapports</a>
        </nav>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>
    <main class="admin-container">
        <section class="hero-section"><h1>Tableau de Bord Administratif</h1><p>Vue d'ensemble des absences et de l'activité de l'établissement.</p></section>
        <section class="stats-grid">
            <div class="stat-card"><h3>Taux d'Absence Global</h3><div class="stat-value"><?php echo $absence_percentage; ?>%</div></div>
            <div class="stat-card"><h3>Filières les plus concernées</h3><canvas id="topClassesChart"></canvas></div>
            <div class="stat-card"><h3>Activité des 7 derniers jours</h3><canvas id="last7DaysChart"></canvas></div>
        </section>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('topClassesChart')) {
            const emgBlue = '#00529b';
            const topClassesCtx = document.getElementById('topClassesChart');
            new Chart(topClassesCtx, { type: 'bar', data: { labels: <?php echo json_encode($top_classes_labels); ?>, datasets: [{ label: 'Nombre d\'absences', data: <?php echo json_encode($top_classes_data); ?>, backgroundColor: emgBlue, borderRadius: 5 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
            const last7DaysCtx = document.getElementById('last7DaysChart');
            new Chart(last7DaysCtx, { type: 'line', data: { labels: <?php echo json_encode($last_7_days_labels); ?>, datasets: [{ label: 'Absences quotidiennes', data: <?php echo json_encode($last_7_days_data); ?>, fill: false, borderColor: emgBlue, tension: 0.1 }] }, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
        }
    });
    </script>

<?php else: ?>
    <!-- ================== VUE PROFESSEUR (STYLE EMG) ================== -->
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>
    <!-- ... code existant ... -->
        </section> <!-- Fin de stats-grid pour l'admin -->

        <!-- NOUVELLE SECTION DE COMMENTAIRE -->
        <section class="review-section">
            <div class="stat-card">
                <h3>Laissez un commentaire</h3>
                <p style="color:var(--text-medium); font-size: 14px; margin-top: 4px;">Vos retours nous aident à améliorer l'application.</p>
                <form action="submit_review.php" method="POST" style="margin-top: 1rem;">
                    <textarea name="comment" class="form-control" rows="3" placeholder="Votre avis..." required></textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Envoyer</button>
                </form>
            </div>
        </section>
    </main> <!-- Fin du main de l'admin -->
    <main class="page-container">
        <div class="page-header">
            <h1>Mes Séances de Cours</h1>
            <a href="select_correction.php" class="btn btn-secondary"><i class="fas fa-edit"></i> Corriger un appel passé</a>
        </div>
        <div class="sessions-list">
            <?php if ($sessions->num_rows > 0): ?>
                <?php 
                $current_day = '';
                $days_fr = ['Monday'=>'Lundi', 'Tuesday'=>'Mardi', 'Wednesday'=>'Mercredi', 'Thursday'=>'Jeudi', 'Friday'=>'Vendredi', 'Saturday'=>'Samedi'];
                $days_map = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                $current_day_numeric = date('N');

                while($session = $sessions->fetch_assoc()): 
                    $session_day_numeric = $days_map[$session['day_of_week']];
                    $is_session_today = ($session_day_numeric == $current_day_numeric);

                    if ($current_day != $session['day_of_week']): 
                        $current_day = $session['day_of_week'];
                        echo '<h2 class="day-header">' . $days_fr[$current_day] . '</h2>';
                    endif; 
                ?>
                <div class="session-card">
                    <div class="session-info">
                        <div class="session-info-item session-module">
                            <i class="fas fa-book"></i>
                            <span><?php echo htmlspecialchars($session['module_name']); ?></span>
                        </div>
                        <div class="session-info-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo date('H:i', strtotime($session['start_time'])).' - '.date('H:i', strtotime($session['end_time'])); ?></span>
                        </div>
                        <div class="session-info-item">
                            <i class="fas fa-users"></i>
                            <span><?php echo htmlspecialchars($session['class_name'].' - '.$session['group_name']); ?></span>
                        </div>
                    </div>
                    <a href="teacher_view.php?session_id=<?php echo $session['timetable_id']; ?>" class="btn btn-primary" <?php if (!$is_session_today) echo 'disabled'; ?>><i class="fas fa-user-check"></i> Faire l'Appel</a>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="placeholder"><p>Aucune séance n'est programmée pour vous actuellement.</p></div>
            <?php endif; ?>
        </div>
    </main>
<?php endif; ?>

</body>
</html>
