<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Essayer de se connecter en tant que professeur/admin
    $stmt = $conn->prepare("SELECT teacher_id, first_name, password, role FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Connexion réussie en tant que professeur/admin
            $_SESSION['teacher_id'] = $user['teacher_id'];
            $_SESSION['teacher_name'] = $user['first_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php"); // Redirige vers le tableau de bord admin/prof
            exit();
        }
    }
    
    // 2. Si ce n'est pas un professeur, essayer en tant qu'étudiant
    $stmt = $conn->prepare("SELECT student_id, first_name, password FROM students WHERE student_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Connexion réussie en tant qu'étudiant
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['student_name'] = $user['first_name'];
            $_SESSION['role'] = 'student'; // Rôle crucial pour la redirection
            header("Location: student_dashboard.php"); // Redirige vers le NOUVEAU tableau de bord étudiant
            exit();
        }
    }

    // 3. Si aucune connexion n'a réussi
    header("Location: index.php?error=invalid_credentials");
    exit();
}
?>