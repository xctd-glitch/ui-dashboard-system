<?php
/**
 * Authentication API
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/Auth.php';

if (!isset($_SESSION)) {
    session_start();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action === 'logout') {
    \Auth::logout();
    header('Location: /');
    exit;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
