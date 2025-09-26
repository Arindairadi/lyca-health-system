<?php
require_once __DIR__ . '/config/data_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $image_path = null;

    // Validate required fields
    if (!$name || !$email || !$description || !$latitude || !$longitude) {
        echo "All fields including location are required.";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        exit;
    }

    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/outbreaks/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_path = $targetFile;
        } else {
            echo "Failed to upload image.";
            exit;
        }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO outbreak_reports (name, email, latitude, longitude, description, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $name, $email, $latitude, $longitude, $description, $image_path);

    if ($stmt->execute()) {
        echo "Report submitted successfully!";
    } else {
        echo "Error submitting report: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
