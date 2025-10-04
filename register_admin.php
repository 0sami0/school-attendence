<?php
// Include our database connection
include 'db_connect.php';

// --- Admin Credentials ---
// Change these details for your main admin account
$firstName = 'Admin';
$lastName  = 'User';
$email     = 'admin@school.com';
$password  = 'password123'; // Use a strong password here

// --- Hash the Password for Security ---
// We never store plain text passwords.
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// --- SQL Query to Insert the Admin ---
// Using a prepared statement to prevent SQL injection
$sql = "INSERT INTO teachers (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // Bind parameters: 'ssss' means four string parameters
    mysqli_stmt_bind_param($stmt, "ssss", $firstName, $lastName, $email, $hashedPassword);

    // Execute the statement
    if (mysqli_stmt_execute($stmt)) {
        echo "Admin user created successfully!<br>";
        echo "Email: " . htmlspecialchars($email) . "<br>";
        echo "Password: " . htmlspecialchars($password) . "<br>";
        echo "You can now delete this file.";
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }

    // Close the statement
    mysqli_stmt_close($stmt);
} else {
    echo "Error preparing statement: " . mysqli_error($conn);
}

// Close the connection
mysqli_close($conn);
?>