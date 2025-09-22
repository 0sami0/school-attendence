<?php
// Fichier : add_student.php (MIS À JOUR)
session_start();
include 'db_connect.php';

// Le check_admin.php serait plus propre ici, mais on garde la logique existante
if (!isset($_SESSION['teacher_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validation étendue pour inclure les nouveaux champs
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['parent_email']) || empty($_POST['group_id']) || empty($_POST['student_email']) || empty($_POST['password'])) {
        header("Location: students.php?error=MissingFields");
        exit();
    }

    // 1. Récupérer toutes les données du formulaire
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $parentEmail = $_POST['parent_email'];
    $groupId = $_POST['group_id'];
    $studentEmail = $_POST['student_email']; // Nouvelle donnée
    $password = $_POST['password'];         // Nouvelle donnée (en clair pour l'instant)

    // 2. Hacher le mot de passe pour la sécurité
    // C'est l'étape la plus importante !
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 3. Préparer la requête SQL MISE À JOUR pour inclure les nouveaux champs
    $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, student_email, password, parent_email, group_id) VALUES (?, ?, ?, ?, ?, ?)");
    
    // 4. Lier les paramètres. Le type string "sssi" devient "sssssi"
    // s = string, i = integer
    $stmt->bind_param("sssssi", $firstName, $lastName, $studentEmail, $hashedPassword, $parentEmail, $groupId);

    if ($stmt->execute()) {
        // Succès : rediriger avec un message de succès
        header("Location: students.php?success=student_added");
    } else {
        // Échec : logguer l'erreur et rediriger avec un message d'erreur
        error_log("Erreur lors de l'ajout de l'étudiant : " . $stmt->error);
        header("Location: students.php?error=database_error");
    }

    $stmt->close();
    $conn->close();
} else {
    // Si la page est accédée sans méthode POST, on redirige simplement
    header("Location: students.php");
}

exit();
?>