<?php
session_start();
include 'db_connect.php';
include 'check_admin.php';

$reviews_query = $conn->query("
    SELECT r.comment, r.created_at, r.role, t.first_name, t.last_name 
    FROM reviews r 
    JOIN teachers t ON r.teacher_id = t.teacher_id 
    ORDER BY r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Voir les Commentaires - EMG</title>
    <!-- Inclure ici le même CSS que les autres pages admin -->
</head>
<body>
    <!-- Inclure ici le même header admin que les autres pages -->
    <main class="page-container">
        <h1>Commentaires des Utilisateurs</h1>
        <!-- Boucle pour afficher chaque commentaire dans une carte stylisée -->
    </main>
</body>
</html>