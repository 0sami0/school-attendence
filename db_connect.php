<?php

// --- Database Credentials ---
// Replace with your database connection details.
$dbHost     = "localhost"; // Usually 'localhost' on XAMPP
$dbUsername = "root";      // Default username for XAMPP
$dbPassword = "";          // Default password for XAMPP is empty
$dbName     = "attendance_system"; // The name of the database we created

// --- Create a Database Connection ---
// The mysqli_connect() function attempts to open a new connection to the MySQL server.
$conn = mysqli_connect($dbHost, $dbUsername, $dbPassword, $dbName);

// --- Check the Connection ---
// If the connection fails, the script will stop and display an error message.
// This is crucial for debugging during development.
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: Set character set to utf8mb4 to support a wide range of characters.
mysqli_set_charset($conn, "utf8mb4");

?>