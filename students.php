<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$filiere_query = mysqli_query($conn, "SELECT class_id, class_name FROM classes ORDER BY class_name");
$filieres = [];
while ($filiere = mysqli_fetch_assoc($filiere_query)) {
    $filieres[] = $filiere;
}

$group_query = mysqli_query($conn, "SELECT group_id, group_name, class_id FROM `groups` ORDER BY group_name");
$groups_by_class = [];
while ($group = mysqli_fetch_assoc($group_query)) {
    $groups_by_class[$group['class_id']][] = $group;
}

$groups_json = json_encode($groups_by_class);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Étudiants - EMG</title>
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
        .page-container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid transparent; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background-color: var(--emg-blue); color: var(--text-light); }
        .btn-primary:hover { background-color: #00417a; }
        .btn-secondary { background-color: var(--text-light); color: var(--emg-blue); border-color: var(--emg-blue); }
        .btn-secondary:hover { background-color: var(--emg-blue); color: var(--text-light); }
        .selection-filters { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .table-container { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table thead th { background-color: #f9fafb; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-medium); }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--emg-blue); color: var(--text-light); display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; }
        .user-details { display: flex; flex-direction: column; }
        .user-name { font-weight: 500; }
        .user-email { font-size: 0.9rem; color: var(--text-medium); }
        .actions-cell { text-align: right; }
        .btn-icon { background: none; border: none; cursor: pointer; color: var(--text-medium); padding: 0.5rem; border-radius: 50%; font-size: 1rem; }
        .btn-icon:hover { background-color: #f0f0f0; }
        .btn-danger { color: var(--danger-color); }
        .table-placeholder { display: flex; justify-content: center; align-items: center; flex-direction: column; gap: 1rem; min-height: 150px; color: var(--text-medium); font-style: italic; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { font-size: 1.25rem; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: var(--text-dark); }
        .modal-body { padding: 1.5rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); }
        
        /* --- CORRECTION APPORTÉE ICI --- */
        .modal-footer .btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: var(--emg-blue); box-shadow: 0 0 0 3px rgba(0, 82, 155, 0.1); }
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
            <h1>Gestion des Étudiants</h1>
            <button id="open-student-modal-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un Étudiant</button>
        </div>

        <div class="selection-filters">
            <div class="form-group" style="flex: 1;">
                <label for="filiereFilter">1. Choisissez une Filière</label>
                <select id="filiereFilter" class="form-control">
                    <option value="">-- Sélectionnez une filière --</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?php echo $filiere['class_id']; ?>"><?php echo htmlspecialchars($filiere['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="groupFilter">2. Choisissez un Groupe</label>
                <select id="groupFilter" class="form-control" disabled>
                    <option value="">-- D'abord choisir une filière --</option>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Email (Connexion)</th>
                        <th>Email Parent</th>
                        <th style="width: 100px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentListBody"></tbody>
            </table>
            <div id="tablePlaceholder" class="table-placeholder">
                <i class="fas fa-mouse-pointer" style="font-size: 2rem;"></i>
                <p>Veuillez sélectionner une filière et un groupe pour afficher les étudiants.</p>
            </div>
        </div>
    </main>

    <!-- Modale d'ajout d'étudiant MISE À JOUR -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Ajouter un Nouvel Étudiant</h2><span class="close-btn" data-modal="addStudentModal">&times;</span></div>
            <form action="add_student.php" method="post">
                <div class="modal-body">
                    <div class="form-group"><label for="modalFiliereSelect">Filière :</label><select id="modalFiliereSelect" class="form-control" required><option value="">-- Sélectionnez une filière --</option><?php foreach ($filieres as $filiere): ?><option value="<?php echo $filiere['class_id']; ?>"><?php echo htmlspecialchars($filiere['class_name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="modalGroupSelect">Groupe :</label><select id="modalGroupSelect" name="group_id" class="form-control" required disabled><option value="">-- D'abord choisir une filière --</option></select></div>
                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid var(--border-color);">
                    <div class="form-group"><label for="first_name">Prénom :</label><input type="text" id="first_name" name="first_name" class="form-control" required></div>
                    <div class="form-group"><label for="last_name">Nom :</label><input type="text" id="last_name" name="last_name" class="form-control" required></div>
                    <div class="form-group"><label for="student_email">Email Étudiant (identifiant de connexion) :</label><input type="email" id="student_email" name="student_email" class="form-control" required></div>
                    <div class="form-group"><label for="password">Mot de passe :</label><input type="password" id="password" name="password" class="form-control" required></div>
                    <div class="form-group"><label for="parent_email">Email des Parents :</label><input type="email" id="parent_email" name="parent_email" class="form-control" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-btn" data-modal="addStudentModal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    const groupsByClass = <?php echo $groups_json; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const mainFiliereSelect = document.getElementById('filiereFilter');
        const mainGroupSelect = document.getElementById('groupFilter');
        const studentListBody = document.getElementById('studentListBody');
        const placeholder = document.getElementById('tablePlaceholder');
        const addModal = document.getElementById('addStudentModal');
        const openAddBtn = document.getElementById('open-student-modal-btn');
        const modalFiliereSelect = document.getElementById('modalFiliereSelect');
        const modalGroupSelect = document.getElementById('modalGroupSelect');
        const closeBtns = document.querySelectorAll('.close-btn');

        function populateGroupSelect(filiereSelect, groupSelect) {
            const selectedClassId = filiereSelect.value;
            groupSelect.innerHTML = '<option value="">-- D\'abord choisir une filière --</option>';
            groupSelect.disabled = true;
            if (selectedClassId && groupsByClass[selectedClassId]) {
                groupSelect.innerHTML = '<option value="">-- Choisissez un groupe --</option>';
                groupsByClass[selectedClassId].forEach(group => {
                    const option = document.createElement('option');
                    option.value = group.group_id;
                    option.textContent = group.group_name;
                    groupSelect.appendChild(option);
                });
                groupSelect.disabled = false;
            }
        }
        
        function fetchAndDisplayStudents(groupId) {
            studentListBody.innerHTML = '';
            if (!groupId) {
                placeholder.style.display = 'flex';
                placeholder.innerHTML = '<p>Veuillez sélectionner une filière et un groupe pour afficher les étudiants.</p>';
                return;
            }
            placeholder.style.display = 'flex';
            placeholder.innerHTML = '<p>Chargement...</p>';

            fetch(`get_students.php?group_id=${groupId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.json();
                })
                .then(students => {
                    if (students.error) throw new Error(students.error);
                    if (students.length > 0) {
                        placeholder.style.display = 'none';
                        students.forEach(student => {
                            const initials = (student.first_name?.[0] || '') + (student.last_name?.[0] || '');
                            studentListBody.innerHTML += `
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div class="avatar">${escapeHTML(initials.toUpperCase())}</div>
                                            <div class="user-details">
                                                <span class="user-name">${escapeHTML(student.first_name)} ${escapeHTML(student.last_name)}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="user-email">${escapeHTML(student.student_email)}</span></td>
                                    <td><span class="user-email">${escapeHTML(student.parent_email)}</span></td>
                                    <td class="actions-cell">
                                        <a href="delete.php?type=student&id=${student.student_id}" class="btn-icon btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        placeholder.innerHTML = '<p>Aucun étudiant trouvé dans ce groupe.</p>';
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    placeholder.innerHTML = `<p style="color:var(--danger-color);">Erreur lors du chargement des étudiants.</p>`;
                });
        }

        mainFiliereSelect.addEventListener('change', () => {
            populateGroupSelect(mainFiliereSelect, mainGroupSelect);
            fetchAndDisplayStudents(null);
        });
        mainGroupSelect.addEventListener('change', () => fetchAndDisplayStudents(mainGroupSelect.value));
        openAddBtn.addEventListener('click', () => { addModal.style.display = 'block'; });
        closeBtns.forEach(btn => btn.addEventListener('click', () => { document.getElementById(btn.getAttribute('data-modal')).style.display = 'none'; }));
        modalFiliereSelect.addEventListener('change', () => populateGroupSelect(modalFiliereSelect, modalGroupSelect));
        window.addEventListener('click', (event) => { if (event.target == addModal) addModal.style.display = 'none'; });

        function escapeHTML(str) {
            const p = document.createElement("p");
            p.appendChild(document.createTextNode(str));
            return p.innerHTML;
        }
    });
    </script>
</body>
</html>