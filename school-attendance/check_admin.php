<?php
// Si la session n'est pas déjà démarrée, on la démarre.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================================
// ÉTAPE 1 : AUTHENTIFICATION (L'utilisateur est-il connecté ?)
// =====================================================================
// On vérifie une information de base comme l'ID de l'utilisateur.
if (!isset($_SESSION['teacher_id'])) {
    // Si l'utilisateur n'est PAS connecté, on le renvoie à la page de connexion.
    header("Location: index.php"); 
    exit(); // Très important d'arrêter le script ici.
}

// =====================================================================
// ÉTAPE 2 : AUTORISATION (L'utilisateur a-t-il les bons droits ?)
// =====================================================================
// À ce stade, on sait que l'utilisateur est connecté. On vérifie son rôle.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // S'il est connecté mais n'est PAS un admin, on le renvoie à son propre
    // tableau de bord avec un message d'erreur.
    // CECI CASSE LA BOUCLE DE REDIRECTION.
    header("Location: dashboard.php?error=access_denied");
    exit(); // Très important d'arrêter le script ici.
}

// Si le script arrive jusqu'ici, cela signifie que l'utilisateur est bien
// connecté ET qu'il a le rôle 'admin'. La page peut se charger normalement.
?>