<?php
// Affiche les erreurs pour faciliter le débogage. À retirer en production.
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

// Vérifie si l'utilisateur est un administrateur connecté
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$class_filter = $_GET['class_id'] ?? null;
$group_filter = $_GET['group_id'] ?? null;
$search_name = $_GET['student_name'] ?? '';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// --- REQUÊTE FINALE CORRIGÉE ---
// On joint maintenant la table justification_requests pour obtenir le statut
$sql = "
    SELECT 
        s.student_id, s.first_name, s.last_name,
        c.class_name, g.group_name,
        a.attendance_id, a.attendance_date,
        m.module_name,
        jr.status as justification_status -- On récupère le statut depuis la nouvelle table
    FROM students s
    JOIN `groups` g ON s.group_id = g.group_id
    JOIN classes c ON g.class_id = c.class_id
    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.status = 'Absent'
    -- On joint les requêtes de justification pour obtenir leur statut
    LEFT JOIN justification_requests jr ON a.justification_request_id = jr.request_id
    LEFT JOIN timetable tt ON a.timetable_id = tt.timetable_id
    LEFT JOIN modules m ON tt.module_id = m.module_id
";

// Le reste de la logique de filtrage et d'assemblage des données reste identique
$where_clauses = []; $params = []; $param_types = '';
if (!empty($class_filter)) { $where_clauses[] = "c.class_id = ?"; $params[] = $class_filter; $param_types .= 'i'; }
if (!empty($group_filter)) { $where_clauses[] = "g.group_id = ?"; $params[] = $group_filter; $param_types .= 'i'; }
if (!empty($search_name)) { $where_clauses[] = "(s.first_name LIKE ? OR s.last_name LIKE ?)"; $search_term = "%" . $search_name . "%"; $params[] = $search_term; $params[] = $search_term; $param_types .= 'ss'; }
if (!empty($start_date)) { $sql = str_replace("LEFT JOIN attendance a ON s.student_id = a.student_id AND a.status = 'Absent'", "LEFT JOIN attendance a ON s.student_id = a.student_id AND a.status = 'Absent' AND a.attendance_date >= ?", $sql); array_unshift($params, $start_date); $param_types = 's' . $param_types; }
if (!empty($end_date)) { $sql = str_replace("AND a.attendance_date >= ?", "AND a.attendance_date >= ? AND a.attendance_date <= ?", $sql); $sql = str_replace("LEFT JOIN attendance a ON s.student_id = a.student_id AND a.status = 'Absent'", "LEFT JOIN attendance a ON s.student_id = a.student_id AND a.status = 'Absent' AND a.attendance_date <= ?", $sql); $startDatePos = array_search($start_date, $params); if ($startDatePos !== false) { array_splice($params, $startDatePos + 1, 0, $end_date); } else { array_unshift($params, $end_date); } $param_types = str_replace("s", "ss", $param_types, $count); if($count == 0) $param_types = 's' . $param_types; }
if (!empty($where_clauses)) { $sql .= " WHERE " . implode(' AND ', $where_clauses); }
$sql .= " ORDER BY s.last_name, s.first_name, a.attendance_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Erreur de préparation de la requête: " . $conn->error); }
if (!empty($params)) { $stmt->bind_param($param_types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$students_report = [];
while ($row = $result->fetch_assoc()) {
    $student_id = $row['student_id'];
    if (!isset($students_report[$student_id])) {
        $students_report[$student_id] = [
            'info' => ['first_name' => $row['first_name'],'last_name' => $row['last_name'],'class_name' => $row['class_name'],'group_name' => $row['group_name']],
            'absences' => []
        ];
    }
    if ($row['attendance_id'] !== null) {
        $students_report[$student_id]['absences'][] = ['attendance_id' => $row['attendance_id'],'date' => $row['attendance_date'],'module_name' => $row['module_name'],'justification_status' => $row['justification_status']];
    }
}
$classes = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");
$groups = $conn->query("SELECT group_id, group_name, class_id FROM `groups` ORDER BY group_name");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'Absences - EMG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* CSS from previous step is identical */
        :root { --emg-blue: #00529b; --emg-yellow: #ffd100; --danger-color: #dc3545; --text-light: #ffffff; --background-light: #f8fafc; --border-color: #e5e7eb; --text-dark: #1f2937; --text-medium: #4b5563; --success-color: #28a745; --pending-color: #fdba74;}
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-light); color: var(--text-dark); }
        .emg-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light); }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: var(--text-light); color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600; transition: background-color 0.2s, color 0.2s; }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        .container { padding: 32px 40px; max-width: 1400px; margin: 0 auto; }
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; }
        .report-filters { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; background-color: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-weight: 500; margin-bottom: 8px; font-size: 14px; }
        .filter-group select, .filter-group input, .filter-group button { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 16px; }
        .filter-group button { background-color: var(--emg-blue); color: white; border: none; cursor: pointer; font-weight: 600; }
        .report-container { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .report-header, .student-row { display: grid; grid-template-columns: 50px 3fr 2fr 1fr 50px; align-items: center; padding: 16px 24px; }
        .report-header { background-color: #f9fafb; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-medium); border-bottom: 1px solid var(--border-color); }
        .student-row { border-top: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s; }
        .student-row:hover { background-color: #f9fafb; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--emg-blue); color: var(--text-light); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; flex-shrink: 0; }
        .total-absences { text-align: center; font-weight: 500; }
        .total-absences.high { color: var(--danger-color); font-weight: 700; }
        .toggle-icon { text-align: right; color: var(--text-medium); }
        .student-details-panel { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; background-color: #f9fafb; }
        .student-details-panel.is-open { max-height: 1000px; transition: max-height 0.4s ease-in; }
        .details-content { padding: 24px 48px; border-top: 1px solid var(--border-color); }
        .details-content table { width: 100%; border-collapse: collapse; }
        .details-content th, .details-content td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .details-content th { font-weight: 600; color: var(--text-medium); }
        .btn-justify { padding: 5px 10px; font-size: 12px; border: none; border-radius: 6px; cursor: pointer; color: white; background-color: var(--emg-blue); }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; color: white; }
        .status-badge.approved { background-color: var(--success-color); }
        .status-badge.pending { background-color: var(--pending-color); color: var(--text-dark); }
        .status-badge.rejected { background-color: var(--danger-color); }
        
        /* --- NOUVEAUX STYLES POUR LA MODALE --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { font-size: 1.25rem; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: var(--text-dark); }
        .modal-body { padding: 1.5rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
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

    <main class="container">
        <!-- The filter form and report container HTML is identical to the previous step -->
        <div class="page-header"><h1>Rapport d'Absences Global</h1></div>
        <form class="report-filters" method="GET" action="">
             <div class="filter-group"><label for="student_name">Rechercher par Nom</label><input type="text" name="student_name" id="student_name" placeholder="Nom ou prénom..." value="<?php echo htmlspecialchars($search_name); ?>"></div>
            <div class="filter-group"><label for="class_id">Filtrer par Filière</label><select name="class_id" id="class_id"><option value="">Toutes les filières</option><?php while($class = $classes->fetch_assoc()): ?><option value="<?php echo $class['class_id']; ?>" <?php echo ($class_filter == $class['class_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option><?php endwhile; ?></select></div>
            <div class="filter-group"><label for="group_id">Filtrer par Groupe</label><select name="group_id" id="group_id"><option value="">Tous les groupes</option><?php $groups->data_seek(0); while($group = $groups->fetch_assoc()): ?><option value="<?php echo $group['group_id']; ?>" data-class-id="<?php echo $group['class_id']; ?>" <?php echo ($group_filter == $group['group_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($group['group_name']); ?></option><?php endwhile; ?></select></div>
            <div class="filter-group"><label for="start_date">Date de début :</label><input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date ?? ''); ?>"></div>
            <div class="filter-group"><label for="end_date">Date de fin :</label><input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date ?? ''); ?>"></div>
            <div class="filter-group" style="align-self: flex-end;"><button type="submit"><i class="fas fa-search"></i> Appliquer</button></div>
        </form>
        <div class="report-container">
            <div class="report-header">
                <span></span><span>Étudiant</span><span>Filière - Groupe</span><span style="text-align: center;">Total Absences</span><span class="toggle-icon"></span>
            </div>
            <?php if (!empty($students_report)): ?>
                <?php foreach ($students_report as $student_id => $student_data): ?>
                    <?php
                        $total_unjustified_absences = 0;
                        foreach ($student_data['absences'] as $absence) { if ($absence['justification_status'] !== 'Approved') { $total_unjustified_absences++; } }
                        $initials = (isset($student_data['info']['first_name'][0]) ? $student_data['info']['first_name'][0] : '') . (isset($student_data['info']['last_name'][0]) ? $student_data['info']['last_name'][0] : '');
                    ?>
                    <div class="student-row">
                        <div class="avatar"><?php echo htmlspecialchars(strtoupper($initials)); ?></div>
                        <span class="student-name"><?php echo htmlspecialchars($student_data['info']['first_name'] . ' ' . $student_data['info']['last_name']); ?></span>
                        <span class="student-group"><?php echo htmlspecialchars($student_data['info']['class_name'] . ' - ' . $student_data['info']['group_name']); ?></span>
                        <span class="total-absences <?php echo ($total_unjustified_absences >= 4) ? 'high' : ''; ?>"><?php echo $total_unjustified_absences; ?></span>
                        <span class="toggle-icon"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="student-details-panel">
                        <div class="details-content">
                            <?php if (!empty($student_data['absences'])): ?>
                                <table>
                                    <thead><tr><th>Module</th><th>Date de l'absence</th><th>Statut</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($student_data['absences'] as $absence): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($absence['module_name']); ?></td>
                                            <td><?php echo date("d/m/Y", strtotime($absence['date'])); ?></td>
                                            <td><?php switch ($absence['justification_status']) { case 'Approved': echo '<span class="status-badge approved">Approuvée</span>'; break; case 'Pending': echo '<span class="status-badge pending">En attente</span>'; break; case 'Rejected': echo '<span class="status-badge rejected">Rejetée</span>'; break; default: $penalty = 0.25; echo '<span style="color: var(--danger-color); font-weight: 500;">Non justifiée (-'.number_format($penalty, 2).' pts)</span>'; } ?></td>
                                            <td><?php if ($absence['justification_status'] === 'None' || $absence['justification_status'] === 'Rejected'): ?><button class="btn-justify" data-attendance-id="<?php echo $absence['attendance_id']; ?>">Justifier</button><?php endif; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-medium);">Aucune absence enregistrée pour cet étudiant<?php echo (!empty($start_date) || !empty($end_date)) ? ' dans la période sélectionnée' : ''; ?>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 24px; text-align: center;">Aucun étudiant trouvé pour les filtres sélectionnés.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- NOUVELLE MODALE DE JUSTIFICATION -->
    <div id="justificationModal" class="modal">
        <div class="modal-content">
            <form action="justify_absence.php" method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h2>Justifier une Absence</h2>
                    <span class="close-btn">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_attendance_id" name="attendance_id" value="">
                    
                    <div class="form-group">
                        <label for="justification_image">Certificat (JPG, PNG, PDF - Max 5MB)</label>
                        <input type="file" id="justification_image" name="justification_image" class="form-control" accept=".jpg, .jpeg, .png, .pdf">
                    </div>
                    
                    <div class="form-group">
                        <label for="justification_notes">Notes (Optionnel si un certificat est fourni)</label>
                        <textarea id="justification_notes" name="justification_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary close-btn">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer la Justification</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // JS for filters and accordion is identical to previous step
        const classSelect = document.getElementById('class_id');
        const groupSelect = document.getElementById('group_id');
        const allGroupOptions = Array.from(groupSelect.options);
        classSelect.addEventListener('change', function() {
            const selectedClassId = this.value; 
            const currentGroupValue = groupSelect.value;
            groupSelect.innerHTML = '';
            allGroupOptions.forEach(option => { 
                if (option.value === "" || !selectedClassId || option.dataset.classId === selectedClassId) { 
                    groupSelect.add(option.cloneNode(true)); 
                } 
            });
            const newOptionExists = Array.from(groupSelect.options).some(opt => opt.value === currentGroupValue);
            if (newOptionExists) { groupSelect.value = currentGroupValue; }
        });
        classSelect.dispatchEvent(new Event('change'));
        document.querySelectorAll('.student-row').forEach(row => { 
            const panel = row.nextElementSibling;
            if (panel) {
                row.addEventListener('click', (e) => {
                    // Do not toggle accordion if a button inside the details was clicked
                    if(e.target.closest('.details-content')) return;
                    const icon = row.querySelector('.toggle-icon i'); 
                    panel.classList.toggle('is-open'); 
                    icon.classList.toggle('fa-chevron-down'); 
                    icon.classList.toggle('fa-chevron-up'); 
                }); 
            }
        });

        // --- NOUVEAU JAVASCRIPT POUR LA MODALE DE JUSTIFICATION ---
        const modal = document.getElementById('justificationModal');
        const closeButtons = modal.querySelectorAll('.close-btn');
        const attendanceIdInput = document.getElementById('modal_attendance_id');

        // Open modal when a "Justifier" button is clicked
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('btn-justify')) {
                const attendanceId = event.target.dataset.attendanceId;
                attendanceIdInput.value = attendanceId; // Set the hidden input value
                modal.style.display = 'block';
            }
        });

        // Close modal functionality
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });

        window.addEventListener('click', (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>