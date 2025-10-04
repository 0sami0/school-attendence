<?php
$dbHost     = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName     = "attendance_system";

$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

// if (!$conn) {
//     die("Connection failed: " . mysqli_connect_error());
// }

mysqli_set_charset($conn, "utf8mb4");
mysqli_set_charset($conn, "utf8mb4");
