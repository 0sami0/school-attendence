<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $_SESSION['role'] = $user['role'];

    $sql = "SELECT teacher_id, first_name, password, role FROM teachers WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['teacher_id'] = $user['teacher_id'];
                $_SESSION['teacher_name'] = $user['first_name'];
                $_SESSION['role'] = $user['role'];
                header("Location: dashboard.php");
                exit();
            } else {
                // Mot de passe incorrect
                header("Location: index.php?error=Email ou mot de passe incorrect");
                exit();
            }
        } else {
            // Utilisateur non trouvé
            header("Location: index.php?error=Email ou mot de passe incorrect");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
} else {
    header("Location: index.php");
    exit();
}
?>