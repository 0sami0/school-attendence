<?php
// The PHP logic is correct and remains unchanged from our previous steps.
session_start();
include 'db_connect.php';
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') { header("Location: index.php"); exit(); }
$student_id = $_SESSION['student_id'];

$sql = "SELECT 
            a.attendance_id, a.attendance_date, a.attendance_period, 
            a.justification_request_id,
            jr.status as justification_status,
            tt.start_time, tt.end_time,
            m.module_name, t.last_name AS teacher_last_name
        FROM attendance a
        LEFT JOIN justification_requests jr ON a.justification_request_id = jr.request_id
        JOIN timetable tt ON a.timetable_id = tt.timetable_id
        JOIN modules m ON tt.module_id = m.module_id
        JOIN teachers t ON tt.teacher_id = t.teacher_id
        WHERE a.student_id = ? AND a.status = 'Absent'
        ORDER BY a.attendance_date DESC, a.attendance_period ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$absences_result = $stmt->get_result();

$total_absences = 0;
$unjustified_absences = 0;
$absences_list = [];
while ($row = $absences_result->fetch_assoc()) {
    $absences_list[] = $row;
    $total_absences++;
    if ($row['justification_status'] !== 'Approved') {
        $unjustified_absences++;
    }
}
$penalty_points = $unjustified_absences * 0.25;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Espace Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* FINAL COMBINED STYLES */
        :root {
            --emg-blue: #00529b; --emg-yellow: #ffd100; --danger-color: #dc3545; --text-light: #ffffff;
            --background-light: #f8fafc; --border-color: #e5e7eb; --text-dark: #1f2937; --text-medium: #4b5563;
            --success-color: #28a745; --pending-color: #fdba74;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--background-light); color: var(--text-dark); padding-bottom: 100px; /* Space for the footer */ }
        .emg-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light); }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .btn-logout { display: inline-block; padding: 8px 16px; background-color: var(--text-light); color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600; }
        .page-container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 28px; font-weight: 700; margin-bottom: 1rem; }
        h2 { font-size: 22px; font-weight: 600; margin-top: 2.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: 2rem; }
        .stat-card { background-color: #fff; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card .icon { font-size: 1.5rem; color: var(--emg-blue); margin-bottom: 1rem; }
        .stat-card .value { font-size: 2.5rem; font-weight: 700; color: var(--text-dark); }
        .stat-card .label { font-size: 0.9rem; color: var(--text-medium); margin-top: 0.25rem; }
        .absence-list { display: flex; flex-direction: column; gap: 1rem; }
        .absence-item { display: flex; align-items: center; gap: 1.5rem; background-color: #fff; padding: 1rem 1.5rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .absence-checkbox { width: 20px; height: 20px; flex-shrink: 0; margin-right: -0.5rem; }
        .date-badge { background-color: #f3f4f6; color: var(--text-dark); padding: 0.75rem; border-radius: 8px; text-align: center; font-weight: 600; line-height: 1.2; flex-shrink: 0; }
        .date-badge .day { font-size: 1.75rem; display: block; color: var(--emg-blue); }
        .date-badge .month { font-size: 0.8rem; text-transform: uppercase; display: block; }
        .absence-details { flex-grow: 1; }
        .module-name { font-weight: 600; font-size: 1.1rem; color: var(--text-dark); }
        .period-info { font-size: 0.9em; color: var(--text-medium); margin-top: 0.25rem; }
        .justification-status { flex-basis: 120px; text-align: center; flex-shrink: 0; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; color: white; }
        .status-badge.approved { background-color: var(--success-color); }
        .status-badge.pending { background-color: var(--pending-color); color: var(--text-dark); }
        .status-badge.rejected { background-color: var(--danger-color); }
        .no-absences-message { text-align: center; padding: 3rem; background-color: #fff; border-radius: 12px; border: 2px dashed var(--border-color); color: var(--text-medium); }
        .justify-footer { position: fixed; bottom: 0; left: 0; width: 100%; background-color: rgba(255,255,255,0.95); padding: 1rem; text-align: center; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); backdrop-filter: blur(5px); transform: translateY(100%); transition: transform 0.3s ease-in-out; }
        .justify-footer.visible { transform: translateY(0); }
        .btn-primary { background-color: var(--emg-blue); color:var(--text-light); border:0; padding: 12px 24px; border-radius: 8px; font-weight:600; font-size: 1rem; cursor: pointer; }
        .btn-primary:disabled { background-color: #ccc; cursor: not-allowed; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 0; width: 80%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { font-size: 1.25rem; margin:0; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; }
        .btn-secondary { background-color: #eee; color:var(--text-dark); border:0; padding: 10px 20px; border-radius: 8px; font-weight:600; }
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
        
        <form id="justificationForm" action="request_multi_justification.php" method="post" enctype="multipart/form-data">
            <h2>Historique de vos absences</h2>
            <div class="absence-list">
                <?php if (!empty($absences_list)): ?>
                    <?php foreach($absences_list as $absence): ?>
                    <div class="absence-item">
                        <?php if ($absence['justification_request_id'] === null): ?>
                            <input type="checkbox" name="absence_ids[]" value="<?php echo $absence['attendance_id']; ?>" class="absence-checkbox">
                        <?php else: ?>
                            <div class="absence-checkbox" style="visibility: hidden;"></div> <!-- Placeholder for alignment -->
                        <?php endif; ?>
                        
                        <div class="date-badge">
                            <span class="day"><?php echo date('d', strtotime($absence['attendance_date'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($absence['attendance_date'])); ?></span>
                        </div>
                        
                        <div class="absence-details">
                            <div class="module-name"><?php echo htmlspecialchars($absence['module_name']); ?></div>
                            <div class="period-info">
                                Période <?php echo $absence['attendance_period']; ?> - Prof. <?php echo htmlspecialchars($absence['teacher_last_name']); ?>
                            </div>
                        </div>
                        
                        <div class="justification-status">
                            <?php
                                switch ($absence['justification_status']) {
                                    case 'Approved': echo '<span class="status-badge approved">Approuvée</span>'; break;
                                    case 'Pending': echo '<span class="status-badge pending">En attente</span>'; break;
                                    case 'Rejected': echo '<span class="status-badge rejected">Rejetée</span>'; break;
                                    default: echo '<span style="color: var(--text-medium);">Non Justifiée</span>'; break;
                                }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-absences-message"><p>Aucune absence enregistrée.</p></div>
                <?php endif; ?>
            </div>

            <div id="justifyFooter" class="justify-footer">
                <button type="button" id="openModalBtn" class="btn-primary" disabled>Justifier les <span id="selectedCount">0</span> absences sélectionnées</button>
            </div>

            <div id="justificationModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header"><h2>Soumettre une Justification</h2><span class="close-btn">&times;</span></div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="student_notes">Dates couvertes par le justificatif (ex: "Du 6 au 8 octobre")</label>
                            <textarea name="student_notes" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="justification_image">Certificat (JPG, PNG, PDF - Max 5MB)</label>
                            <input type="file" name="justification_image" class="form-control" accept=".jpg, .jpeg, .png, .pdf" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary close-btn">Annuler</button>
                        <button type="submit" class="btn-primary">Envoyer la demande</button>
                    </div>
                </div>
            </div>
        </form>
    </main>
    <script>
        // The full, correct JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const justificationForm = document.getElementById('justificationForm');
            if (justificationForm) {
                const checkboxes = justificationForm.querySelectorAll('.absence-checkbox');
                const footer = document.getElementById('justifyFooter');
                const openModalBtn = document.getElementById('openModalBtn');
                const selectedCountSpan = document.getElementById('selectedCount');
                const modal = document.getElementById('justificationModal');
                const closeButtons = modal.querySelectorAll('.close-btn');

                function updateSelection() {
                    const checkedCheckboxes = justificationForm.querySelectorAll('.absence-checkbox:checked');
                    const count = checkedCheckboxes.length;
                    selectedCountSpan.textContent = count;
                    if (count > 0) {
                        footer.classList.add('visible');
                        openModalBtn.disabled = false;
                    } else {
                        footer.classList.remove('visible');
                        openModalBtn.disabled = true;
                    }
                }

                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelection);
                });

                openModalBtn.addEventListener('click', () => {
                    modal.style.display = 'block';
                });

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

                updateSelection();
            }
        });
    </script>
</body>
</html>