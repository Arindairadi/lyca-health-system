<?php
// Database connection (PDO). Include this file from other scripts.
// Update the DSN / user / password for your environment.

$DB_HOST = '127.0.0.1';
$DB_NAME = 'lyca';
$DB_USER = 'root';
$DB_PASS = ''; // set your DB password
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // In production hide details and log the error instead.
    http_response_code(500);
    echo "Database connection failed.";
    exit;
}