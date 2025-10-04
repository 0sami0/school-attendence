<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Use your actual database connection file
    include 'db_connect.php'; 

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    if (!isset($_GET['group_id'])) {
        throw new Exception('Group ID is missing from the request.');
    }
    $group_id = (int)$_GET['group_id'];

    // SQL to fetch timetable entries active during a specific week
    $sql = "SELECT 
                tt.day_of_week,
                tt.start_time,
                tt.end_time,
                m.module_name,
                CONCAT(t.first_name, ' ', t.last_name) as professor_name
            FROM timetable tt
            JOIN modules m ON tt.module_id = m.module_id
            JOIN teachers t ON tt.teacher_id = t.teacher_id
            WHERE tt.group_id = ? 
              AND ? BETWEEN tt.valid_from AND IFNULL(tt.valid_until, '9999-12-31')
            ORDER BY 
                FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
                tt.start_time";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL statement preparation failed: ' . $conn->error);
    }

    $weekly_timetables = [];
    $today = new DateTime();

    // Let's fetch the history for the last 12 weeks
    for ($i = 0; $i < 12; $i++) {
        $date_to_check = (clone $today)->modify("-$i weeks");
        
        // Find the Monday of that week
        $week_start_obj = (clone $date_to_check)->modify('monday this week');
        $week_start_str = $week_start_obj->format('Y-m-d');

        // Bind parameters: group_id and the start date of the week to check
        $stmt->bind_param("is", $group_id, $week_start_str);
        
        if (!$stmt->execute()) {
            throw new Exception('SQL statement execution failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        $entries = $result->fetch_all(MYSQLI_ASSOC);

        // If there were any entries for that week, add them to our history
        if (!empty($entries)) {
            $weekly_timetables[$week_start_str] = $entries;
        }
    }

    $stmt->close();
    $conn->close();

    if (empty($weekly_timetables)) {
        echo json_encode(['success' => true, 'html' => '<div class="alert alert-info text-center">Aucun historique trouvé pour cette sélection.</div>']);
        exit;
    }

    // Sort weeks in descending order (most recent first)
    krsort($weekly_timetables);

    $html = '';
    foreach ($weekly_timetables as $week_start => $entries) {
        $week_start_obj = new DateTime($week_start);
        $week_end_obj = (clone $week_start_obj)->modify('+6 days'); // Sunday
        
        $html .= '<h3>Semaine du ' . $week_start_obj->format('d/m/Y') . ' au ' . $week_end_obj->format('d/m/Y') . '</h3>';
        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped">';
        $html .= '<thead class="thead-dark"><tr><th>Jour</th><th>Heure</th><th>Module</th><th>Professeur</th></tr></thead><tbody>';

        // Group entries by day for better display
        $days = [];
        foreach($entries as $entry) {
            $days[$entry['day_of_week']][] = $entry;
        }

        foreach ($days as $day => $day_entries) {
            foreach ($day_entries as $entry) {
                 $html .= '<tr>';
                 $html .= '<td>' . htmlspecialchars($entry['day_of_week']) . '</td>';
                 $html .= '<td>' . htmlspecialchars(substr($entry['start_time'], 0, 5)) . ' - ' . htmlspecialchars(substr($entry['end_time'], 0, 5)) . '</td>';
                 $html .= '<td>' . htmlspecialchars($entry['module_name']) . '</td>';
                 $html .= '<td>' . htmlspecialchars($entry['professor_name']) . '</td>';
                 $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></div>';
    }

    echo json_encode(['success' => true, 'html' => $html]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>