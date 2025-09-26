<?php
// Saves/clears the API key into the PHP session (server-side).
// IMPORTANT: This file DOES NOT log or reveal the key. Do NOT commit session data to public storage.
session_start();
header('Content-Type: application/json; charset=utf-8');

// Only accept POST JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON input.']);
    exit;
}

if (isset($data['action']) && $data['action'] === 'clear') {
    unset($_SESSION['gemini_api_key']);
    echo json_encode(['ok' => true]);
    exit;
}

if (isset($data['api_key'])) {
    $key = trim($data['api_key']);
    if ($key === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Empty API key.']);
        exit;
    }
    // Store in session only. Do NOT log, echo, or persist to files.
    $_SESSION['gemini_api_key'] = $key;
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Missing api_key or action.']);