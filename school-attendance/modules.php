<?php
session_start();
include 'db_connect.php';
// include 'check_admin.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// 1. Récupérer uniquement les filières pour les menus déroulants
$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$filieres = [];
while ($filiere = mysqli_fetch_assoc($classes_result)) {
    $filieres[] = $filiere;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Modules - EMG</title>
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

        /* --- En-tête de page (Style Admin) --- */
        .emg-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 40px; background-color: var(--emg-blue); color: var(--text-light);
        }
        .emg-header .logo img { height: 40px; }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .user-info span { font-weight: 500; }
        .btn-logout {
            display: inline-block; padding: 8px 16px; background-color: var(--text-light);
            color: var(--emg-blue); text-decoration: none; border-radius: 8px; font-weight: 600;
            transition: background-color 0.2s, color 0.2s;
        }
        .btn-logout:hover { background-color: var(--emg-yellow); color: var(--text-dark); }
        
        /* --- Conteneur & En-tête de contenu --- */
        .page-container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h1 { font-size: 28px; font-weight: 700; }

        /* --- Boutons Génériques --- */
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid transparent; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background-color: var(--emg-blue); color: var(--text-light); }
        .btn-primary:hover { background-color: #00417a; }
        .btn-secondary { background-color: var(--text-light); color: var(--emg-blue); border-color: var(--emg-blue); }
        .btn-secondary:hover { background-color: var(--emg-blue); color: var(--text-light); }

        /* --- Filtres --- */
        .selection-filters { max-width: 400px; margin-bottom: 2rem; }

        /* --- Tableau de données --- */
        .table-container { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table thead th { background-color: #f9fafb; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-medium); }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .actions-cell { text-align: right; }
        .btn-icon { background: none; border: none; cursor: pointer; color: var(--text-medium); padding: 0.5rem; border-radius: 50%; font-size: 1rem; }
        .btn-icon:hover { background-color: #f0f0f0; }
        .btn-danger { color: var(--danger-color); }
        .table-placeholder { display: flex; justify-content: center; align-items: center; min-height: 150px; color: var(--text-medium); font-style: italic; }

        /* --- Style des Modales --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(-20px);} to {opacity: 1; transform: translateY(0);} }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .modal-header h2 { font-size: 1.25rem; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: var(--text-dark); }
        .modal-body { padding: 1.5rem; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); }
        
        /* --- Style des Formulaires --- */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; }
        .form-control:focus { outline: none; border-color: var(--emg-blue); box-shadow: 0 0 0 3px rgba(0, 82, 155, 0.1); }
    </style>
</head>
<body>
    <!-- Header de la page (Style EMG) -->
    <header class="emg-header">
        <div class="logo">
            <img src="assets/logo-emg.png" alt="Logo EMG">
        </div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="dashboard.php" class="btn-logout">Retour</a>
        </div>
    </header>

    <main class="page-container">
        <div class="page-header">
            <h1>Gestion des Modules</h1>
            <button id="open-add-modal-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un Module</button>
        </div>

        <div class="selection-filters">
            <div class="form-group">
                <label for="filiereFilter">Afficher les modules de la filière :</label>
                <select id="filiereFilter" class="form-control">
                    <option value="">-- Sélectionnez une filière --</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?php echo $filiere['class_id']; ?>"><?php echo htmlspecialchars($filiere['class_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nom du Module</th>
                        <th style="width: 120px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="moduleListBody">
                    <!-- Les lignes seront insérées ici par JavaScript -->
                </tbody>
            </table>
            <div id="tablePlaceholder" class="table-placeholder">
                <p>Veuillez sélectionner une filière pour voir ses modules.</p>
            </div>
        </div>
    </main>

    <!-- Modales (HTML inchangé, mais stylisé par le CSS ci-dessus) -->
    <div id="addModuleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Ajouter un Nouveau Module</h2><span class="close-btn" data-modal="addModuleModal">&times;</span></div>
            <form action="process_module.php" method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="module_name">Nom du Module :</label>
                        <input type="text" id="module_name" name="module_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="class_id">Associer à la Filière :</label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">-- Choisissez une filière --</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?php echo $filiere['class_id']; ?>"><?php echo htmlspecialchars($filiere['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal="addModuleModal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editModuleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Modifier le Module</h2><span class="close-btn" data-modal="editModuleModal">&times;</span></div>
            <form action="process_module.php" method="post">
                <input type="hidden" name="action" value="edit"><input type="hidden" id="edit_module_id" name="module_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_module_name">Nom du Module :</label>
                        <input type="text" id="edit_module_name" name="module_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_class_id">Associer à la Filière :</label>
                        <select id="edit_class_id" name="class_id" class="form-control" required>
                            <option value="">-- Choisissez une filière --</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?php echo $filiere['class_id']; ?>"><?php echo htmlspecialchars($filiere['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal="editModuleModal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Le JavaScript est inchangé, il fonctionnera avec le nouveau style
    document.addEventListener('DOMContentLoaded', function() {
        const filiereSelect = document.getElementById('filiereFilter');
        const moduleListBody = document.getElementById('moduleListBody');
        const placeholder = document.getElementById('tablePlaceholder');

        filiereSelect.addEventListener('change', function() {
            const selectedClassId = this.value;
            moduleListBody.innerHTML = '';
            if (!selectedClassId) { placeholder.style.display = 'flex'; placeholder.innerHTML = '<p>Veuillez sélectionner une filière pour voir ses modules.</p>'; return; }
            placeholder.innerHTML = '<p>Chargement...</p>';
            placeholder.style.display = 'flex';

            fetch(`get_modules.php?class_id=${selectedClassId}`)
                .then(response => response.json())
                .then(modules => {
                    if (modules.length > 0) {
                        placeholder.style.display = 'none';
                        modules.forEach(module => {
                            moduleListBody.innerHTML += `
                                <tr>
                                    <td>${escapeHTML(module.module_name)}</td>
                                    <td class="actions-cell">
                                        <button class="btn-icon edit-module-btn" title="Modifier" data-id="${module.module_id}" data-name="${escapeHTML(module.module_name)}" data-class-id="${module.class_id}"><i class="fas fa-pencil-alt"></i></button>
                                        <a href="delete.php?type=module&id=${module.module_id}" class="btn-icon btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr ?')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>`;
                        });
                    } else { placeholder.innerHTML = '<p>Aucun module dans cette filière.</p>'; }
                }).catch(error => { console.error('Erreur:', error); placeholder.innerHTML = '<p>Erreur lors du chargement des données.</p>'; });
        });

        const addModal = document.getElementById('addModuleModal'), editModal = document.getElementById('editModuleModal');
        const openAddBtn = document.getElementById('open-add-modal-btn');
        const closeBtns = document.querySelectorAll('.close-btn, .btn-secondary');
        openAddBtn.onclick = () => addModal.style.display = 'block';
        closeBtns.forEach(btn => { btn.onclick = () => { document.getElementById(btn.getAttribute('data-modal')).style.display = 'none'; }; });
        
        document.body.addEventListener('click', function(event) {
            const editBtn = event.target.closest('.edit-module-btn');
            if (editBtn) {
                document.getElementById('edit_module_id').value = editBtn.dataset.id;
                document.getElementById('edit_module_name').value = editBtn.dataset.name;
                document.getElementById('edit_class_id').value = editBtn.dataset.classId;
                editModal.style.display = 'block';
            }
        });

        window.onclick = (event) => { if (event.target == addModal || event.target == editModal) { addModal.style.display = 'none'; editModal.style.display = 'none'; } };
        
        function escapeHTML(str) {
            var p = document.createElement("p");
            p.appendChild(document.createTextNode(str));
            return p.innerHTML;
        }
    });
    </script>
</body>
</html>