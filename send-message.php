<?php
// Include your database connection file
include 'db.php'; 

/** @var mysqli $conn */

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user inputs to prevent injection
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $sql = "INSERT INTO inbox (name, email, message) VALUES ('$name', '$email', '$message')";
        
        if (mysqli_query($conn, $sql)) {
            // Redirect back with success message
            header("Location: index.php?success=1#contact");
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Please fill in all fields.";
    }
}
?>