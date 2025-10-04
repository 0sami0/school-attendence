<?php
include 'db_connect.php';

echo "<h3>Script de dé-duplication et de correction de la table 'attendance'</h3>";
echo "<p>Ce script va nettoyer les enregistrements d'absences en double et ajouter une contrainte pour empêcher les futurs doublons.</p>";
echo "<hr>";

// --- ÉTAPE 1: Nettoyer les doublons existants ---
echo "<strong>Étape 1: Nettoyage des doublons...</strong><br>";

// On identifie les enregistrements à garder (le plus récent pour chaque combinaison unique)
$sql_find_duplicates = "
CREATE TEMPORARY TABLE IF NOT EXISTS `temp_attendance_ids` AS (
    SELECT MAX(attendance_id) as id
    FROM attendance
    GROUP BY student_id, original_timetable_id, attendance_date, attendance_period
);
";

if ($conn->query($sql_find_duplicates) === TRUE) {
    echo " -> Table temporaire des IDs uniques créée.<br>";
} else {
    die("Erreur lors de la création de la table temporaire: " . $conn->error);
}

// On supprime tous les enregistrements qui ne sont PAS dans notre liste d'IDs à garder
$sql_delete_duplicates = "
DELETE FROM attendance
WHERE attendance_id NOT IN (SELECT id FROM temp_attendance_ids);
";

$conn->query($sql_delete_duplicates);
$affected_rows = $conn->affected_rows;
echo " -> Nettoyage terminé. " . $affected_rows . " enregistrement(s) en double supprimé(s).<br>";


// --- ÉTAPE 2: Ajouter la contrainte UNIQUE ---
echo "<br><strong>Étape 2: Ajout de la contrainte d'unicité...</strong><br>";

// D'abord, on vérifie si la contrainte existe déjà pour éviter une erreur
$check_constraint_sql = "
SELECT COUNT(*) AS count
FROM information_schema.table_constraints
WHERE constraint_schema = DATABASE()
  AND table_name = 'attendance'
  AND constraint_name = 'uq_attendance_record';
";
$result = $conn->query($check_constraint_sql);
$constraint_exists = $result->fetch_assoc()['count'] > 0;

if ($constraint_exists) {
    echo " -> La contrainte 'uq_attendance_record' existe déjà. Aucune action n'est nécessaire.<br>";
} else {
    // Ajout de la contrainte UNIQUE sur les 4 colonnes qui définissent un enregistrement d'appel
    $sql_add_unique_constraint = "
    ALTER TABLE `attendance`
    ADD UNIQUE `uq_attendance_record` (`student_id`, `original_timetable_id`, `attendance_date`, `attendance_period`);
    ";

    if ($conn->query($sql_add_unique_constraint) === TRUE) {
        echo " -> Contrainte UNIQUE 'uq_attendance_record' ajoutée avec succès !<br>";
    } else {
        // Si cette étape échoue, c'est probablement parce qu'il reste des doublons.
        echo "<strong style='color:red;'>Erreur lors de l'ajout de la contrainte: " . $conn->error . "</strong><br>";
        echo "Cela signifie probablement qu'il reste des doublons que le script n'a pas pu nettoyer automatiquement. Une intervention manuelle pourrait être nécessaire.<br>";
    }
}

echo "<hr>";
echo "<h4>Opération terminée.</h4>";
echo "<p style='color:green; font-weight:bold;'>La base de données est maintenant configurée pour empêcher les absences en double.</p>";
echo "<p style='color:orange; font-weight:bold;'>Pour des raisons de sécurité, veuillez supprimer ce fichier (`fix_duplicates.php`) de votre serveur maintenant.</p>";

$conn->close();
?>
