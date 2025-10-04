<?php
include 'check_admin.php';
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['type'])) {
    $type = $_POST['type'];

    switch ($type) {
        case 'class':
            if (!empty($_POST['class_name'])) {
                $stmt = $conn->prepare("INSERT INTO classes (class_name) VALUES (?)");
                $stmt->bind_param("s", $_POST['class_name']);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: manage_classes.php");
            break;

        case 'group':
            if (!empty($_POST['group_name']) && !empty($_POST['class_id'])) {
                $stmt = $conn->prepare("INSERT INTO `groups` (group_name, class_id) VALUES (?, ?)");
                $stmt->bind_param("si", $_POST['group_name'], $_POST['class_id']);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: manage_classes.php");
            break;
            
        case 'module':
            if (!empty($_POST['module_name']) && !empty($_POST['class_id'])) {
                $stmt = $conn->prepare("INSERT INTO modules (module_name, class_id) VALUES (?, ?)");
                $stmt->bind_param("si", $_POST['module_name'], $_POST['class_id']);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: modules.php");
            break;
        // We will add a case for 'module' here later
    }
}
exit();
?>