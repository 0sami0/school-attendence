<?php
session_start();
include 'db_connect.php';
// include 'check_admin.php'; // Assurez-vous que ce fichier existe

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// 1. Récupérer toutes les filières
$classes_result = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name");
$classes = [];
while($class = mysqli_fetch_assoc($classes_result)) {
    $classes[] = $class;
}

// 2. Récupérer tous les groupes et les organiser par filière
$groups_result = mysqli_query($conn, "SELECT g.*, c.class_name FROM `groups` g JOIN classes c ON g.class_id = c.class_id ORDER BY c.class_name, g.group_name");
$groups_by_class = [];
while($group = mysqli_fetch_assoc($groups_result)) {
    $groups_by_class[$group['class_id']][] = $group;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer Filières & Groupes - EMG</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background-color: var(--emg-blue);
            color: var(--text-light);
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
        .page-actions { display: flex; gap: 1rem; }

        /* --- Boutons Génériques --- */
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 600; border: 2px solid transparent; transition: all 0.2s; cursor: pointer; }
        .btn-primary { background-color: var(--emg-blue); color: var(--text-light); }
        .btn-primary:hover { background-color: #00417a; }
        .btn-secondary { background-color: var(--text-light); color: var(--emg-blue); border-color: var(--emg-blue); }
        .btn-secondary:hover { background-color: var(--emg-blue); color: var(--text-light); }

        /* --- Style de l'Accordéon --- */
        .accordion-list { display: flex; flex-direction: column; gap: 1rem; }
        .accordion-item { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .accordion-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; cursor: pointer; }
        .accordion-title { font-size: 1.1rem; font-weight: 600; }
        .accordion-actions { display: flex; align-items: center; gap: 1rem; }
        .accordion-chevron { font-size: 1rem; color: var(--text-medium); transition: transform 0.3s ease; }
        .accordion-header.active .accordion-chevron { transform: rotate(180deg); }
        .accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .group-list { list-style: none; padding: 0; margin: 0; }
        .group-list li { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1.5rem; border-top: 1px solid var(--border-color); }
        .no-groups-msg { padding: 1rem 1.5rem; color: var(--text-medium); font-style: italic; }
        .btn-icon { background: none; border: none; cursor: pointer; color: var(--text-medium); padding: 0.5rem; border-radius: 50%; }
        .btn-icon:hover { background-color: #f0f0f0; }
        .btn-danger { color: var(--danger-color); }

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
            <h1>Gérer les Filières et Groupes</h1>
            <div class="page-actions">
                <button id="open-class-modal-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter une Filière</button>
                <button id="open-group-modal-btn" class="btn btn-secondary"><i class="fas fa-plus"></i> Ajouter un Groupe</button>
            </div>
        </div>

        <div class="accordion-list">
            <?php if (empty($classes)): ?>
                <p>Aucune filière n'a été créée pour le moment.</p>
            <?php else: ?>
                <?php foreach($classes as $class): ?>
                <div class="accordion-item">
                    <div class="accordion-header">
                        <span class="accordion-title"><?php echo htmlspecialchars($class['class_name']); ?></span>
                        <div class="accordion-actions">
                            <a href="delete.php?type=class&id=<?php echo $class['class_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Attention: La suppression d\'une filière supprimera aussi tous ses groupes. Continuer ?')"><i class="fas fa-trash-alt"></i></a>
                            <i class="fas fa-chevron-down accordion-chevron"></i>
                        </div>
                    </div>
                    <div class="accordion-content">
                        <?php if (isset($groups_by_class[$class['class_id']])): ?>
                            <ul class="group-list">
                                <?php foreach($groups_by_class[$class['class_id']] as $group): ?>
                                <li>
                                    <span><?php echo htmlspecialchars($group['group_name']); ?></span>
                                    <a href="delete.php?type=group&id=<?php echo $group['group_id']; ?>" class="btn-icon btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?')"><i class="fas fa-trash-alt"></i></a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="no-groups-msg">Aucun groupe dans cette filière.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modales (HTML inchangé, mais sera stylisé par le CSS ci-dessus) -->
    <div id="addClassModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Ajouter une Filière</h2><span class="close-btn" id="closeClassModal">&times;</span></div>
            <div class="modal-body">
                <form id="addClassForm" action="process_entity.php" method="post">
                    <input type="hidden" name="type" value="class"><input type="hidden" id="final_class_name" name="class_name">
                    <div class="form-group">
                        <label for="filiere_principale">Filière Principale :</label>
                        <select id="filiere_principale" class="form-control" required>
                            <option value="">-- Choisissez une filière --</option>
                            <option value="Génie Informatique">Génie Informatique</option>
                            <option value="Génie Industriel">Génie Industriel</option>
                            <option value="Génie Civil">Génie Civil</option>
                            <option value="Génie Électrique">Génie Électrique</option>
                            <option value="Classes Préparatoires">Classes Préparatoires</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee">Année :</label>
                        <select id="annee" class="form-control" required disabled><option value="">-- D'abord choisir une filière --</option></select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelClassModal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="addGroupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Ajouter un Groupe</h2><span class="close-btn" id="closeGroupModal">&times;</span></div>
            <div class="modal-body">
                <form action="process_entity.php" method="post">
                    <input type="hidden" name="type" value="group">
                    <div class="form-group">
                        <label for="class_id">Sélectionnez une Filière :</label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">-- Choisissez --</option>
                            <?php foreach($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="group_name">Nom du Groupe :</label>
                        <input type="text" id="group_name" name="group_name" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelGroupModal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   <script>
    // --- Le JavaScript est inchangé, il fonctionnera avec le nouveau style ---
    const addClassModal = document.getElementById('addClassModal'), addGroupModal = document.getElementById('addGroupModal');
    const openClassBtn = document.getElementById('open-class-modal-btn'), openGroupBtn = document.getElementById('open-group-modal-btn');
    const cancelClassBtn = document.getElementById('cancelClassModal'), cancelGroupBtn = document.getElementById('cancelGroupModal');
    const closeBtns = document.querySelectorAll('.close-btn');
    function closeAllModals() { addClassModal.style.display = 'none'; addGroupModal.style.display = 'none'; }
    openClassBtn.onclick = () => { addClassModal.style.display = 'block'; };
    openGroupBtn.onclick = () => { addGroupModal.style.display = 'block'; };
    closeBtns.forEach(btn => { btn.onclick = closeAllModals; });
    cancelClassBtn.onclick = closeAllModals;
    cancelGroupBtn.onclick = closeAllModals;
    window.onclick = function(event) { if (event.target == addClassModal || event.target == addGroupModal) closeAllModals(); }
    
    document.querySelectorAll('.accordion-header').forEach(item => {
        item.addEventListener('click', event => {
            if (event.target.closest('.btn-icon')) return;
            const content = item.nextElementSibling, chevron = item.querySelector('.accordion-chevron');
            item.classList.toggle('active');
            if (content.style.maxHeight) { content.style.maxHeight = null; } else { content.style.maxHeight = content.scrollHeight + "px"; }
        });
    });

    const filiereSelect = document.getElementById('filiere_principale'), anneeSelect = document.getElementById('annee');
    const addClassForm = document.getElementById('addClassForm'), finalClassNameInput = document.getElementById('final_class_name');
    const anneeOptions = { 'Génie Informatique': ['1ère année', '2ème année', '3ème année'], 'Génie Industriel': ['1ère année', '2ème année', '3ème année'], 'Génie Civil': ['1ère année', '2ème année', '3ème année'], 'Génie Électrique': ['1ère année', '2ème année', '3ème année'], 'Classes Préparatoires': ['1ère année', '2ème année'] };
    filiereSelect.addEventListener('change', function() {
        const selectedFiliere = this.value;
        anneeSelect.innerHTML = '<option value="">-- D\'abord choisir une filière --</option>';
        anneeSelect.disabled = true;
        if (selectedFiliere && anneeOptions[selectedFiliere]) {
            anneeSelect.disabled = false;
            anneeSelect.innerHTML = '<option value="">-- Choisissez une année --</option>';
            anneeOptions[selectedFiliere].forEach(function(annee) {
                const option = document.createElement('option');
                option.value = annee; option.textContent = annee;
                anneeSelect.appendChild(option);
            });
        }
    });
    addClassForm.addEventListener('submit', function(event) {
        const selectedFiliere = filiereSelect.value, selectedAnnee = anneeSelect.value;
        if (selectedFiliere && selectedAnnee) {
            finalClassNameInput.value = `${selectedFiliere} - ${selectedAnnee}`;
        } else {
            event.preventDefault();
            alert('Veuillez sélectionner une filière ET une année.');
        }
    });
</script>
</body>
</html>