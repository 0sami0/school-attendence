<?php
session_start();
include 'db_connect.php';
include 'get_week_dates.php';

// --- FUNCTION: To check for absence threshold and send email ---
/**
 * Checks a student's unjustified absence count and sends a notification if a threshold is met.
 *
 * @param mysqli $conn The database connection object.
 * @param int $student_id The ID of the student to check.
 * @return void
 */
function checkAndSendAbsenceNotification($conn, $student_id) {
    // Step 1: Check if the notification has already been sent to avoid spam.
    $stmt_check = $conn->prepare("SELECT absence_notification_sent FROM students WHERE student_id = ?");
    $stmt_check->bind_param("i", $student_id);
    $stmt_check->execute();
    $notification_sent = $stmt_check->get_result()->fetch_assoc()['absence_notification_sent'];
    $stmt_check->close();

    if ($notification_sent == 1) {
        return; // Email already sent, do nothing.
    }

    // Step 2: Count the total number of UNJUSTIFIED absences for this student.
    $stmt_count = $conn->prepare("
        SELECT COUNT(a.attendance_id) as unjustified_count
        FROM attendance a
        LEFT JOIN justification_requests jr ON a.justification_request_id = jr.request_id
        WHERE a.student_id = ? 
          AND a.status = 'Absent' 
          AND (jr.status IS NULL OR jr.status != 'Approved')
    ");
    $stmt_count->bind_param("i", $student_id);
    $stmt_count->execute();
    $count = $stmt_count->get_result()->fetch_assoc()['unjustified_count'];
    $stmt_count->close();
    
    // Step 3: If the count reaches the threshold (6 or more), send the email.
    if ($count >= 6) {
        // Get student and parent details
        $stmt_details = $conn->prepare("SELECT first_name, last_name, parent_email FROM students WHERE student_id = ?");
        $stmt_details->bind_param("i", $student_id);
        $stmt_details->execute();
        $student = $stmt_details->get_result()->fetch_assoc();
        $stmt_details->close();
        
        if ($student && !empty($student['parent_email'])) {
            $to = $student['parent_email'];
            $student_full_name = $student['first_name'] . ' ' . $student['last_name'];
            $subject = "Avis d'absences répétées pour " . $student_full_name;
            
            // --- NOUVEAU CONTENU DE L'EMAIL ---
            $message = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #00529b;'>Avis d'Absences - École EMG</h2>
                    <p>Bonjour,</p>
                    <p>Ceci est un email d'information automatique concernant l'assiduité de <strong>{$student_full_name}</strong>.</p>
                    <p>Notre système a enregistré que votre fils ou fille a atteint un total de <strong>{$count} absences non justifiées</strong> à ce jour.</p>
                    <p>Nous tenons à vous assurer que ceci est une notification à titre informatif pour vous tenir au courant du suivi scolaire de votre fils ou fille.</p>
                    <p>Pour un suivi détaillé, nous vous encourageons à en discuter avec votre fils ou fille. Il ou elle peut se connecter à son <strong>espace étudiant</strong> personnel pour consulter la liste complète de ses absences et soumettre une justification si nécessaire.</p>
                    <p>Pour toute question, n'hésitez pas à contacter l'administration de l'école.</p>
                    <p>Cordialement,<br>L'équipe de l'école EMG</p>
                </div>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: Administration EMG <no-reply@emgschool.com>' . "\r\n";
            
            // Send the email
            if (mail($to, $subject, $message, $headers)) {
                // Step 4: If email is sent successfully, update the flag.
                $stmt_update = $conn->prepare("UPDATE students SET absence_notification_sent = 1 WHERE student_id = ?");
                $stmt_update->bind_param("i", $student_id);
                $stmt_update->execute();
                $stmt_update->close();
            }
        }
    }
}


// --- MAIN SCRIPT LOGIC (Unchanged) ---
if (!isset($_SESSION['teacher_id'])) { header("Location: index.php"); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['session_id']) || !isset($_POST['student_ids']) || !isset($_POST['attendance'])) { header("Location: dashboard.php?error=missing_data"); exit(); }

$session_id = (int)$_POST['session_id'];
$student_ids = $_POST['student_ids'];
$attendance_data = $_POST['attendance'];
$students_marked_absent = [];

$conn->begin_transaction();
try {
    $session_query = $conn->prepare("SELECT day_of_week FROM timetable WHERE timetable_id = ?");
    $session_query->bind_param("i", $session_id);
    $session_query->execute();
    $session_result = $session_query->get_result();
    if ($session_result->num_rows === 0) { throw new Exception("Session invalide."); }
    $day_of_week = $session_result->fetch_assoc()['day_of_week'];
    $session_query->close();
    
    $attendance_date = getDateForDayOfWeek($day_of_week);
    
    $sql = "INSERT INTO attendance (student_id, timetable_id, attendance_date, attendance_period, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status)";
    $stmt = $conn->prepare($sql);

    foreach ($student_ids as $student_id) {
        $was_marked_absent = false;
        foreach ([1, 2] as $period) {
            if (isset($attendance_data[$student_id][$period])) {
                $status = $attendance_data[$student_id][$period];
                $stmt->bind_param("iisis", $student_id, $session_id, $attendance_date, $period, $status);
                $stmt->execute();
                if ($status === 'Absent') { $was_marked_absent = true; }
            }
        }
        if ($was_marked_absent) { $students_marked_absent[] = $student_id; }
    }
    $stmt->close();
    $conn->commit();

    foreach (array_unique($students_marked_absent) as $student_id_to_check) {
        checkAndSendAbsenceNotification($conn, $student_id_to_check);
    }
    
    header("Location: dashboard.php?success=attendance_saved");

} catch (Exception $e) {
    $conn->rollback();
    error_log("Attendance Error: " . $e->getMessage());
    header("Location: dashboard.php?error=db_error&msg=" . urlencode($e->getMessage()));
} finally {
    $conn->close();
}
exit();
?>