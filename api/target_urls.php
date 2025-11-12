<?php
/**
 * Target URLs Management API
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
    case 'PUT':
        handlePut($action, $pdo, $session);
        break;
    case 'DELETE':
        handleDelete($action, $pdo, $session);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($action, $pdo, $session) {
    if ($action === 'admin_urls') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, url, created_at FROM admin_target_urls
            WHERE admin_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $urls = $stmt->fetchAll();

        echo json_encode(['urls' => $urls]);
    } elseif ($action === 'user_urls') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, url, created_at FROM user_target_urls
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $urls = $stmt->fetchAll();

        echo json_encode(['urls' => $urls]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add_admin_url') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL']);
            return;
        }

        if (!\Validator::isValidUrl($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO admin_target_urls (admin_id, url)
            VALUES (?, ?)
        ");
        $stmt->execute([$session['id'], $input['url']]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        http_response_code(201);
    } elseif ($action === 'add_user_url') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing URL']);
            return;
        }

        if (!\Validator::isValidUrl($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_target_urls (user_id, url)
            VALUES (?, ?)
        ");
        $stmt->execute([$session['id'], $input['url']]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        http_response_code(201);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update_admin_url') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['id'], $input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing fields']);
            return;
        }

        if (!\Validator::isValidUrl($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM admin_target_urls WHERE id = ? AND admin_id = ?");
        $stmt->execute([$input['id'], $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'URL not found']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE admin_target_urls SET url = ? WHERE id = ?");
        $stmt->execute([$input['url'], $input['id']]);

        echo json_encode(['success' => true]);
    } elseif ($action === 'update_user_url') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['id'], $input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing fields']);
            return;
        }

        if (!\Validator::isValidUrl($input['url'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URL format']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM user_target_urls WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['id'], $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'URL not found']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE user_target_urls SET url = ? WHERE id = ?");
        $stmt->execute([$input['url'], $input['id']]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete_admin_url') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM admin_target_urls WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'URL not found']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM admin_target_urls WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_user_url') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM user_target_urls WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'URL not found']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM user_target_urls WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}
