<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

// Fetch pending requests and group the associated absences
$sql = "
    SELECT 
        jr.request_id, jr.student_id, jr.certificate_image_path, jr.student_notes,
        s.first_name, s.last_name,
        a.attendance_date, m.module_name
    FROM justification_requests jr
    JOIN students s ON jr.student_id = s.student_id
    JOIN attendance a ON jr.request_id = a.justification_request_id
    JOIN timetable tt ON a.timetable_id = tt.timetable_id
    JOIN modules m ON tt.module_id = m.module_id
    WHERE jr.status = 'Pending'
    ORDER BY jr.request_id, a.attendance_date;
";

$result = $conn->query($sql);
$requests = [];
while ($row = $result->fetch_assoc()) {
    $request_id = $row['request_id'];
    if (!isset($requests[$request_id])) {
        $requests[$request_id] = [
            'info' => [
                'student_name' => $row['first_name'] . ' ' . $row['last_name'],
                'image_path' => $row['certificate_image_path'],
                'student_notes' => $row['student_notes']
            ],
            'absences' => []
        ];
    }
    $requests[$request_id]['absences'][] = [
        'date' => $row['attendance_date'],
        'module_name' => $row['module_name']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Justifications - EMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styles are identical to the previous step */
        :root {--emg-blue: #00529b; --emg-yellow: #ffd100; --danger-color: #dc3545; --text-light: #ffffff; --background-light: #f8fafc; --border-color: #e5e7eb; --text-dark: #1f2937; --text-medium: #4b5563; --success-color: #28a745;}
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-light); color: var(--text-dark); }
        .emg-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light); }
        .admin-nav { display: flex; align-items: center; gap: 24px; }
        .admin-nav a { text-decoration: none; color: var(--text-light); font-weight: 500; padding: 8px 0; border-bottom: 2px solid transparent; transition: border-color 0.2s; }
        .admin-nav a:hover, .admin-nav a.active { border-bottom-color: var(--emg-yellow); }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: var(--text-light); color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600; }
        .container { padding: 32px 40px; max-width: 1200px; margin: 0 auto; }
        .page-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 2rem; }
        .requests-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; }
        .request-card { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .card-header h3 { font-size: 1.2rem; margin: 0; }
        .card-body { padding: 1.5rem; }
        .info-group { margin-bottom: 1rem; }
        .info-group strong { display: block; color: var(--text-medium); font-size: 0.9rem; margin-bottom: 0.25rem; }
        .absence-list-item { display: flex; justify-content: space-between; font-size: 0.95rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
        .absence-list-item:last-child { border-bottom: none; }
        .card-footer { padding: 1rem 1.5rem; background-color: #f9fafb; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 0.5rem; }
        .btn { padding: 8px 16px; font-size: 14px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 500; }
        .btn-success { background-color: var(--success-color); color: white; }
        .btn-danger { background-color: var(--danger-color); color: white; }
        .btn-secondary { background-color: var(--text-medium); color: white; }
    </style>
</head>
<body>
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        
        <!-- HEADER NAVIGATION ADDED HERE -->
        <nav class="admin-nav">
            <a href="dashboard.php">Tableau de Bord</a>
            <a href="manage_classes.php">Filières</a>
            <a href="timetable.php">Emploi du Temps</a>
            <a href="manage_justifications.php" class="active">Justifications</a>
            <a href="report.php">Rapports</a>
        </nav>
        
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>
    <main class="container">
        <div class="page-header"><h1>Demandes de Justification en Attente</h1></div>
        <div class="requests-grid">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request_id => $request): ?>
                    <div class="request-card">
                        <div class="card-header"><h3><?php echo htmlspecialchars($request['info']['student_name']); ?></h3></div>
                        <div class="card-body">
                            <div class="info-group">
                                <strong>Justificatif fourni :</strong>
                                <a href="<?php echo htmlspecialchars($request['info']['image_path']); ?>" target="_blank" class="btn btn-secondary"><i class="fas fa-eye"></i> Voir le document</a>
                            </div>
                            <div class="info-group">
                                <strong>Dates indiquées par l'étudiant :</strong>
                                <p><?php echo htmlspecialchars($request['info']['student_notes']); ?></p>
                            </div>
                            <div class="info-group">
                                <strong>Absences concernées :</strong>
                                <div class="absence-list">
                                    <?php foreach ($request['absences'] as $absence): ?>
                                        <div class="absence-list-item">
                                            <span><?php echo htmlspecialchars($absence['module_name']); ?></span>
                                            <strong><?php echo date("d/m/Y", strtotime($absence['date'])); ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <form action="process_justification_request.php" method="POST">
                                <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-danger">Rejeter</button>
                                <button type="submit" name="action" value="approve" class="btn btn-success">Approuver</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune demande de justification en attente.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>