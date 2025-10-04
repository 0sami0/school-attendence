<?php
session_start();
include 'db_connect.php';
// include 'check_admin.php';

if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Récupérer tous les professeurs pour la liste
$teachers_result = mysqli_query($conn, "SELECT teacher_id, first_name, last_name, email, role FROM teachers ORDER BY last_name, first_name");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gérer les Professeurs - EMG</title>
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
        
        /* --- Tableau de données --- */
        .table-container { background-color: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .data-table thead th { background-color: #f9fafb; font-weight: 600; font-size: 12px; text-transform: uppercase; color: var(--text-medium); }
        .data-table tbody tr:last-child td { border-bottom: none; }

        /* --- Styles spécifiques pour la table des professeurs --- */
        .user-cell { display: flex; align-items: center; gap: 1rem; }
        .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background-color: var(--emg-blue); color: var(--text-light);
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.9rem;
        }
        .user-details { display: flex; flex-direction: column; }
        .user-name { font-weight: 500; }
        .user-email { font-size: 0.9rem; color: var(--text-medium); }
        .role-badge {
            padding: 4px 10px; border-radius: 16px; font-size: 12px;
            font-weight: 600; text-transform: capitalize;
        }
        .role-admin { background-color: var(--emg-yellow); color: var(--text-dark); }
        .role-professeur { background-color: #e0e7ff; color: #3730a3; }
        .actions-cell { text-align: right; }
        .btn-icon { background: none; border: none; cursor: pointer; color: var(--text-medium); padding: 0.5rem; border-radius: 50%; font-size: 1rem; }
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
        <div class="logo"><img src="assets/logo-emg.png" alt="Logo EMG"></div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['teacher_name']); ?></span>
            <a href="dashboard.php" class="btn-logout">Retour</a>
        </div>
    </header>

    <main class="page-container">
        <div class="page-header">
            <h1>Gérer les Professeurs</h1>
            <button id="open-add-modal-btn" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter un Professeur</button>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th style="width: 120px; text-align: right;">Actions</th></tr></thead>
                <tbody>
                    <?php while($teacher = mysqli_fetch_assoc($teachers_result)): ?>
                    <?php
                        $firstNameInitial = !empty($teacher['first_name']) ? mb_substr($teacher['first_name'], 0, 1) : '';
                        $lastNameInitial = !empty($teacher['last_name']) ? mb_substr($teacher['last_name'], 0, 1) : '';
                        $initials = $firstNameInitial . $lastNameInitial;
                    ?>
                    <tr>
                        <td class="user-cell">
                            <div class="avatar"><?php echo htmlspecialchars(strtoupper($initials)); ?></div>
                            <div class="user-details">
                                <span class="user-name"><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></span>
                                <span class="user-email"><?php echo htmlspecialchars($teacher['email']); ?></span>
                            </div>
                        </td>
                        <td><span class="role-badge role-<?php echo htmlspecialchars($teacher['role']); ?>"><?php echo htmlspecialchars($teacher['role']); ?></span></td>
                        <td class="actions-cell">
                            <button class="btn-icon edit-teacher-btn" title="Modifier"
                                    data-id="<?php echo $teacher['teacher_id']; ?>"
                                    data-first_name="<?php echo htmlspecialchars($teacher['first_name'], ENT_QUOTES); ?>"
                                    data-last_name="<?php echo htmlspecialchars($teacher['last_name'], ENT_QUOTES); ?>"
                                    data-email="<?php echo htmlspecialchars($teacher['email'], ENT_QUOTES); ?>"
                                    data-role="<?php echo $teacher['role']; ?>">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <?php if ($teacher['teacher_id'] != 1): // Sécurité : Empêche la suppression du premier admin ?>
                            <a href="delete.php?type=teacher&id=<?php echo $teacher['teacher_id']; ?>" class="btn-icon btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modales (HTML inchangé) -->
    <div id="addTeacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Ajouter un Professeur</h2><span class="close-btn" data-modal="addTeacherModal">&times;</span></div>
            <form action="process_teacher.php" method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="form-group"><label for="add_first_name">Prénom :</label><input type="text" id="add_first_name" name="first_name" class="form-control" required></div>
                    <div class="form-group"><label for="add_last_name">Nom :</label><input type="text" id="add_last_name" name="last_name" class="form-control" required></div>
                    <div class="form-group"><label for="add_email">Email :</label><input type="email" id="add_email" name="email" class="form-control" required></div>
                    <div class="form-group"><label for="add_password">Mot de passe :</label><input type="password" id="add_password" name="password" class="form-control" required></div>
                    <div class="form-group">
                        <label for="add_role">Rôle :</label>
                        <select id="add_role" name="role" class="form-control" required>
                            <option value="professeur">Professeur</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal="addTeacherModal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <div id="editTeacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2>Modifier un Professeur</h2><span class="close-btn" data-modal="editTeacherModal">&times;</span></div>
            <form action="process_teacher.php" method="post">
                <input type="hidden" name="action" value="edit"><input type="hidden" id="edit_teacher_id" name="teacher_id">
                <div class="modal-body">
                    <div class="form-group"><label for="edit_first_name">Prénom :</label><input type="text" id="edit_first_name" name="first_name" class="form-control" required></div>
                    <div class="form-group"><label for="edit_last_name">Nom :</label><input type="text" id="edit_last_name" name="last_name" class="form-control" required></div>
                    <div class="form-group"><label for="edit_email">Email :</label><input type="email" id="edit_email" name="email" class="form-control" required></div>
                    <div class="form-group"><label for="edit_password">Nouveau mot de passe :</label><input type="password" id="edit_password" name="password" class="form-control" placeholder="Laisser vide pour ne pas changer"></div>
                    <div class="form-group">
                        <label for="edit_role">Rôle :</label>
                        <select id="edit_role" name="role" class="form-control" required>
                            <option value="professeur">Professeur</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal="editTeacherModal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Le JavaScript est inchangé
    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById('addTeacherModal'), editModal = document.getElementById('editTeacherModal');
        const openAddBtn = document.getElementById('open-add-modal-btn');
        const closeBtns = document.querySelectorAll('.close-btn, .btn-secondary');
        openAddBtn.onclick = () => addModal.style.display = 'block';
        closeBtns.forEach(btn => { btn.onclick = () => { document.getElementById(btn.getAttribute('data-modal')).style.display = 'none'; }; });
        
        document.body.addEventListener('click', function(event) {
            const editBtn = event.target.closest('.edit-teacher-btn');
            if (editBtn) {
                document.getElementById('edit_teacher_id').value = editBtn.dataset.id;
                document.getElementById('edit_first_name').value = editBtn.dataset.first_name;
                document.getElementById('edit_last_name').value = editBtn.dataset.last_name;
                document.getElementById('edit_email').value = editBtn.dataset.email;
                document.getElementById('edit_role').value = editBtn.dataset.role;
                editModal.style.display = 'block';
            }
        });

        window.onclick = (event) => { if (event.target == addModal || event.target == editModal) { addModal.style.display = 'none'; editModal.style.display = 'none'; } };
    });
    </script>
</body>
</html>