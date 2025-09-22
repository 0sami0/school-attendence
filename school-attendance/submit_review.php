<?php
// Fichier : submit_review.php (Mis à jour pour l'UX)

session_start();
// Important : Définir le type de contenu en JSON
header('Content-Type: application/json');
include 'db_connect.php';

// Vérification de la session
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé.']);
    exit();
}

// Vérification de la méthode et des données
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty(trim($_POST['comment']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Le commentaire ne peut pas être vide.']);
    exit();
}

// Insertion en base de données
try {
    $comment = trim($_POST['comment']);
    $teacher_id = $_SESSION['teacher_id'];
    $role = $_SESSION['role'];

    $stmt = $conn->prepare("INSERT INTO reviews (teacher_id, role, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $teacher_id, $role, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Merci, votre commentaire a bien été envoyé !']);
    } else {
        throw new Exception('Erreur de base de données.');
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Erreur de soumission de commentaire: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Une erreur est survenue lors de l\'envoi.']);
}

exit();