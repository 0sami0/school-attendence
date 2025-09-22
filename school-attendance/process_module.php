<?php
include 'check_admin.php';
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $moduleName = trim($_POST['module_name']);
    $classId = (int)$_POST['class_id'];
    $action = $_POST['action'];

    // Validation
    if (empty($moduleName) || empty($classId) || empty($action)) {
        header("Location: modules.php?error=MissingFields");
        exit();
    }

    if ($action == 'add') {
        // Logique d'AJOUT
        $stmt = $conn->prepare("INSERT INTO modules (module_name, class_id) VALUES (?, ?)");
        $stmt->bind_param("si", $moduleName, $classId);
    } elseif ($action == 'edit' && !empty($_POST['module_id'])) {
        // Logique de MODIFICATION
        $moduleId = (int)$_POST['module_id'];
        $stmt = $conn->prepare("UPDATE modules SET module_name = ?, class_id = ? WHERE module_id = ?");
        $stmt->bind_param("sii", $moduleName, $classId, $moduleId);
    } else {
        header("Location: modules.php?error=InvalidAction");
        exit();
    }

    if ($stmt->execute()) {
        header("Location: modules.php?success=true");
    } else {
        error_log("Erreur de base de données (modules): " . $stmt->error);
        header("Location: modules.php?error=database_error");
    }

    $stmt->close();
    $conn->close();
    exit();
}

header("Location: modules.php");
exit();
?>