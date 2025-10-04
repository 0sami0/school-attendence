<?php
session_start();
include 'db_connect.php';
include 'check_admin.php'; // Important pour la sécurité

// S'assurer qu'un ID de groupe est fourni et est un nombre
if (!isset($_GET['group_id']) || !is_numeric($_GET['group_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de groupe manquant ou invalide.']);
    exit();
}

$groupId = (int)$_GET['group_id'];

$students = [];
// --- CORRECTION APPORTÉE ICI ---
// On ajoute 'student_email' à la requête SELECT.
$stmt = $conn->prepare("SELECT student_id, first_name, last_name, student_email, parent_email 
                        FROM students 
                        WHERE group_id = ? 
                        ORDER BY last_name, first_name");
$stmt->bind_param("i", $groupId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Échapper les données pour la sécurité avant de les envoyer
    $row['first_name'] = htmlspecialchars($row['first_name']);
    $row['last_name'] = htmlspecialchars($row['last_name']);
    $row['student_email'] = htmlspecialchars($row['student_email']); // On échappe aussi le nouvel email
    $row['parent_email'] = htmlspecialchars($row['parent_email']);
    $students[] = $row;
}

$stmt->close();
$conn->close();

// Renvoyer les données au format JSON
header('Content-Type: application/json');
echo json_encode($students);
?>