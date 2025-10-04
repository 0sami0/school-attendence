<?php
include 'check_admin.php';
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? ''; // Utiliser l'action pour déterminer l'opération
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($role)) {
        header("Location: manage_teachers.php?error=InvalidInput");
        exit();
    }

    if ($action == 'add') {
        if (empty($_POST['password'])) {
            header("Location: manage_teachers.php?error=PasswordRequired");
            exit();
        }
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO teachers (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $password, $role);

    } elseif ($action == 'edit' && !empty($_POST['teacher_id'])) {
        $teacher_id = (int)$_POST['teacher_id'];

        // Sécurité : Ne pas autoriser la modification du rôle de l'admin principal
        if ($teacher_id === 1 && $role !== 'admin') {
             header("Location: manage_teachers.php?error=CannotChangeAdminRole");
             exit();
        }

        // Mettre à jour le mot de passe SEULEMENT s'il est fourni
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, password = ?, role = ? WHERE teacher_id = ?");
            $stmt->bind_param("sssssi", $firstName, $lastName, $email, $password, $role, $teacher_id);
        } else {
            // Ne pas mettre à jour le mot de passe
            $stmt = $conn->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, role = ? WHERE teacher_id = ?");
            $stmt->bind_param("ssssi", $firstName, $lastName, $email, $role, $teacher_id);
        }
    } else {
        header("Location: manage_teachers.php?error=InvalidAction");
        exit();
    }

    if ($stmt->execute()) {
        header("Location: manage_teachers.php?success=true");
    } else {
        error_log("DB Error (Teachers): " . $stmt->error);
        header("Location: manage_teachers.php?error=database_error");
    }

    $stmt->close();
    $conn->close();
    exit();
}

header("Location: manage_teachers.php");
exit();
?>