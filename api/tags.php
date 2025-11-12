<?php
/**
 * Tags Management API
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

if (!isset($_SESSION)) {
    session_start();
}

$session = \Auth::getSession();
if (!$session || $session['role'] !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : null;

switch ($method) {
    case 'GET':
        handleGet($action, $pdo);
        break;
    case 'POST':
        handlePost($action, $pdo, $session);
        break;
    case 'DELETE':
        handleDelete($action, $pdo, $session);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($action, $pdo) {
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, name, created_at FROM tags ORDER BY name");
        $stmt->execute();
        $tags = $stmt->fetchAll();

        echo json_encode(['tags' => $tags]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        if (!isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing name']);
            return;
        }

        $name = trim($input['name']);
        if (strlen($name) < 2) {
            http_response_code(400);
            echo json_encode(['error' => 'Tag name too short']);
            return;
        }

        // Check if tag exists
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Tag already exists']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $stmt->execute([$name]);

        logAction($pdo, 'superadmin', $session['id'], 'create_tag', 'tag', $pdo->lastInsertId(), ['name' => $name]);

        echo json_encode(['success' => true, 'tag_id' => $pdo->lastInsertId()]);
        http_response_code(201);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            return;
        }

        // Check if tag exists
        $stmt = $pdo->prepare("SELECT name FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        $tag = $stmt->fetch();

        if (!$tag) {
            http_response_code(404);
            echo json_encode(['error' => 'Tag not found']);
            return;
        }

        // Delete tag
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$id]);

        logAction($pdo, 'superadmin', $session['id'], 'delete_tag', 'tag', $id, ['name' => $tag['name']]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function logAction($pdo, $actorType, $actorId, $action, $resourceType, $resourceId, $details) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (actor_type, actor_id, action, resource_type, resource_id, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $actorType,
        $actorId,
        $action,
        $resourceType,
        $resourceId,
        json_encode($details),
        $ip
    ]);
}
