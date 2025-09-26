<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    if (empty($name) || empty($phone) || empty($description) || empty($latitude) || empty($longitude)) {
        die("⚠️ All fields including location are required.");
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO emergency_requests (name, phone, email, description, latitude, longitude, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssdd", $name, $phone, $email, $description, $latitude, $longitude);

    if ($stmt->execute()) {
        echo "✅ Emergency request submitted successfully! Help is on the way.";
    } else {
        echo "❌ Something went wrong: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "❌ Invalid request.";
}
