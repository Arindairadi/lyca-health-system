<?php
// Include database connection
require_once __DIR__ . '/config/data_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    // Validate the email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    try {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO newsletters (email, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Temporarily skip sending email on localhost
        echo "Thank you for subscribing!"; 

    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // Duplicate email
            echo "Already subscribed";
        } else {
            echo "It is our fault: " . $e->getMessage();
        }
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
