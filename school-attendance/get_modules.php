<?php
session_start();
include 'db_connect.php';
include 'check_admin.php';

if (!isset($_GET['class_id']) || !is_numeric($_GET['class_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de filière manquant ou invalide.']);
    exit();
}

$classId = (int)$_GET['class_id'];

$modules = [];
$stmt = $conn->prepare("SELECT module_id, module_name, class_id 
                        FROM modules 
                        WHERE class_id = ? 
                        ORDER BY module_name");
$stmt->bind_param("i", $classId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Échapper les données pour la sécurité
    $row['module_name'] = htmlspecialchars($row['module_name']);
    $modules[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($modules);
?>