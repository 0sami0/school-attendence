<?php
session_start();
include 'db_connect.php';
include 'check_admin.php';

// Récupérer toutes les filières et les groupes pour les filtres
$classes_query = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name");
$classes = $classes_query->fetch_all(MYSQLI_ASSOC);

$groups_query = $conn->query("SELECT group_id, group_name, class_id FROM `groups` ORDER BY group_name");
$groups_by_class = [];
while ($group = $groups_query->fetch_assoc()) {
    $groups_by_class[$group['class_id']][] = $group;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Emplois du Temps</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="emg-header">
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <nav class="admin-nav">
            <a href="dashboard.php">Tableau de Bord</a>
            <a href="manage_classes.php">Filières</a>
            <a href="modules.php">Modules</a>
            <a href="manage_teachers.php">Professeurs</a>
            <a href="students.php">Étudiants</a>
            <a href="timetable.php">Emploi du Temps</a>
            <a href="history.php" class="active">Historique</a>
            <a href="report.php">Rapports</a>
        </nav>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="logout.php" class="btn-logout">Déconnexion</a>
        </div>
    </header>

    <main class="page-container">
        <div class="page-header">
            <h1>Historique des Emplois du Temps</h1>
        </div>

        <div class="selection-filters">
            <div class="form-group" style="flex: 1;">
                <label for="classFilter">Filière</label>
                <select id="classFilter" class="form-control">
                    <option value="">-- Choisissez une filière --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1;">
                <label for="groupFilter">Groupe</label>
                <select id="groupFilter" class="form-control" disabled>
                    <option value="">-- D'abord choisir une filière --</option>
                </select>
            </div>
        </div>

        <!-- This is the error div that was missing -->
        <div id="error-message" class="alert alert-danger" style="display: none; margin-top: 1rem;"></div>

        <div id="history-content">
            <div id="timetable-placeholder" class="table-placeholder" style="display: flex;">
                <i class="fas fa-history"></i>
                <p>Veuillez sélectionner une filière et un groupe pour afficher l'historique complet.</p>
            </div>
            <div id="timetable-display" style="display: none;">
                <!-- Le contenu de l'historique sera injecté ici -->
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const groupsByClass = <?php echo json_encode($groups_by_class); ?>;
        const classFilter = document.getElementById('classFilter');
        const groupFilter = document.getElementById('groupFilter');
        const display = document.getElementById('timetable-display');
        const placeholder = document.getElementById('timetable-placeholder');
        const errorDiv = document.getElementById('error-message'); // This now exists and is correct

        classFilter.addEventListener('change', function() {
            const classId = this.value;
            groupFilter.innerHTML = '<option value="">-- D\'abord choisir une filière --</option>';
            groupFilter.disabled = true;
            if (classId && groupsByClass[classId]) {
                groupFilter.innerHTML = '<option value="">-- Choisissez un groupe --</option>';
                groupsByClass[classId].forEach(group => {
                    const option = document.createElement('option');
                    option.value = group.group_id;
                    option.textContent = group.group_name;
                    groupFilter.appendChild(option);
                });
                groupFilter.disabled = false;
            }
            display.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.innerHTML = '<i class="fas fa-history"></i><p>Veuillez sélectionner un groupe pour afficher l\'historique.</p>';
            errorDiv.style.display = 'none'; // Hide errors on change
        });

        groupFilter.addEventListener('change', fetchHistory);

        function fetchHistory() {
            const classId = classFilter.value;
            const groupId = groupFilter.value;

            if (!groupId) {
                display.style.display = 'none';
                placeholder.style.display = 'flex';
                errorDiv.style.display = 'none';
                return;
            }

            placeholder.innerHTML = '<p>Chargement de l\'historique complet...</p>';
            placeholder.style.display = 'flex';
            display.style.display = 'none';
            errorDiv.style.display = 'none';

            fetch(`get_timetable_history.php?class_id=${classId}&group_id=${groupId}`)
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`Le serveur a répondu avec une erreur ${response.status}. Réponse: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        placeholder.style.display = 'none';
                        display.innerHTML = data.html;
                        display.style.display = 'block';
                    } else {
                        errorDiv.textContent = 'Erreur: ' + data.message;
                        errorDiv.style.display = 'block';
                        placeholder.style.display = 'flex';
                        placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i><p>Impossible de charger l\'historique.</p>';
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                    placeholder.style.display = 'flex';
                    placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i><p>Une erreur de communication est survenue.</p>';
                });
        }
    });
    </script>
</body>
</html>