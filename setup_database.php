<?php
include 'db_connect.php';

echo "Starting database setup...<br>";

// 1. Create timetable_history table
$sql_timetable_history = "
CREATE TABLE IF NOT EXISTS `timetable_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `timetable_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `week_start_date` date NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `week_start_date` (`week_start_date`),
  KEY `timetable_id` (`timetable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conn->query($sql_timetable_history) === TRUE) {
    echo "Table 'timetable_history' created successfully or already exists.<br>";
} else {
    echo "Error creating table 'timetable_history': " . $conn->error . "<br>";
}

// 2. Add week_start_date to attendance table
$sql_alter_attendance = "
ALTER TABLE `attendance`
ADD COLUMN IF NOT EXISTS `week_start_date` DATE NULL DEFAULT NULL AFTER `attendance_date`,
ADD KEY IF NOT EXISTS `idx_week_start_date` (`week_start_date`);
";

if ($conn->query($sql_alter_attendance) === TRUE) {
    echo "Table 'attendance' altered successfully (added week_start_date).<br>";
} else {
    echo "Error altering table 'attendance': " . $conn->error . "<br>";
}

// 3. Add original_timetable_id to attendance table
$sql_alter_attendance_2 = "
ALTER TABLE `attendance`
ADD COLUMN IF NOT EXISTS `original_timetable_id` INT(11) NULL DEFAULT NULL AFTER `timetable_id`;
";

if ($conn->query($sql_alter_attendance_2) === TRUE) {
    echo "Column 'original_timetable_id' added to 'attendance' table successfully.<br>";
} else {
    echo "Error adding column 'original_timetable_id' to 'attendance': " . $conn->error . "<br>";
}

echo "Database setup finished.<br>";

$conn->close();
?>
