<?php
/**
 * Countries Management API
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

if (!isset($_SESSION)) {
    session_start();
}

$session = \Auth::getSession();
if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($method) {
    case 'GET':
        handleGet($action, $pdo, $session);
        break;
    case 'POST':
        handlePost($action, $pdo, $session);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($action, $pdo, $session) {
    if ($action === 'admin_countries') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT iso_code FROM admin_countries
            WHERE admin_id = ?
            ORDER BY iso_code
        ");
        $stmt->execute([$session['id']]);
        $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['countries' => $countries]);
    } elseif ($action === 'user_countries') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT iso_code FROM user_countries
            WHERE user_id = ?
            ORDER BY iso_code
        ");
        $stmt->execute([$session['id']]);
        $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode(['countries' => $countries]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_admin_countries') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['countries_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing countries']);
            return;
        }

        $countries = \Validator::parseCountryList($input['countries_text']);

        // Clear existing and add new
        $stmt = $pdo->prepare("DELETE FROM admin_countries WHERE admin_id = ?");
        $stmt->execute([$session['id']]);

        $stmt = $pdo->prepare("
            INSERT INTO admin_countries (admin_id, iso_code)
            VALUES (?, ?)
        ");

        foreach ($countries as $code) {
            $stmt->execute([$session['id'], $code]);
        }

        echo json_encode(['success' => true, 'count' => count($countries)]);
    } elseif ($action === 'update_user_countries') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['countries_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing countries']);
            return;
        }

        $countries = \Validator::parseCountryList($input['countries_text']);

        // Clear existing and add new
        $stmt = $pdo->prepare("DELETE FROM user_countries WHERE user_id = ?");
        $stmt->execute([$session['id']]);

        $stmt = $pdo->prepare("
            INSERT INTO user_countries (user_id, iso_code)
            VALUES (?, ?)
        ");

        foreach ($countries as $code) {
            $stmt->execute([$session['id'], $code]);
        }

        echo json_encode(['success' => true, 'count' => count($countries)]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}
