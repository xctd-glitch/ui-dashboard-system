<?php
/**
 * User Management API
 * Requires admin authentication
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

// Start session and verify admin
if (!isset($_SESSION)) {
    session_start();
}

$session = \Auth::getSession();
if (!$session || $session['role'] !== 'admin') {
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
    if ($action === 'list') {
        // Get all users created by this admin
        $stmt = $pdo->prepare("
            SELECT id, username, email, created_at, is_active FROM users
            WHERE created_by_admin_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $users = $stmt->fetchAll();

        // Load tags for each user
        foreach ($users as &$user) {
            $stmt = $pdo->prepare("
                SELECT t.id, t.name FROM tags t
                INNER JOIN user_tags ut ON t.id = ut.tag_id
                WHERE ut.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $user['tags'] = $stmt->fetchAll();
        }

        echo json_encode(['users' => $users]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        // Validate input
        if (!isset($input['username'], $input['password'], $input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        if (!\Validator::isValidUsername($input['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid username format']);
            return;
        }

        if (!\Validator::isValidPassword($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password too short']);
            return;
        }

        if (!\Validator::isValidEmail($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email']);
            return;
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }

        // Create user
        $passwordHash = password_hash($input['password'], PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, email, created_by_admin_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$input['username'], $passwordHash, $input['email'], $session['id']]);
        $userId = $pdo->lastInsertId();

        // Create default routing config
        $stmt = $pdo->prepare("
            INSERT INTO user_routing_config (user_id, device_scope)
            VALUES (?, 'ALL')
        ");
        $stmt->execute([$userId]);

        // Create default domain selection
        $stmt = $pdo->prepare("
            INSERT INTO user_domain_selection (user_id, selection_type)
            VALUES (?, 'random_global')
        ");
        $stmt->execute([$userId]);

        // Assign tags if provided
        if (isset($input['tags']) && is_array($input['tags'])) {
            foreach ($input['tags'] as $tagId) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_tags (user_id, tag_id) VALUES (?, ?)
                ");
                $stmt->execute([$userId, (int)$tagId]);
            }
        }

        logAction($pdo, 'admin', $session['id'], 'create_user', 'user', $userId, ['username' => $input['username']]);

        echo json_encode(['success' => true, 'user_id' => $userId]);
        http_response_code(201);
    } elseif ($action === 'reset_password') {
        if (!isset($input['user_id'], $input['new_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        if (!\Validator::isValidPassword($input['new_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password too short']);
            return;
        }

        // Verify user belongs to this admin
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$input['user_id'], $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $passwordHash = password_hash($input['new_password'], PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $input['user_id']]);

        logAction($pdo, 'admin', $session['id'], 'reset_user_password', 'user', $input['user_id'], []);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update') {
        if (!isset($input['user_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user_id']);
            return;
        }

        // Verify user belongs to this admin
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$input['user_id'], $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $updates = [];
        $params = [];

        if (isset($input['email'])) {
            if (!\Validator::isValidEmail($input['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email']);
                return;
            }
            $updates[] = 'email = ?';
            $params[] = $input['email'];
        }

        if (empty($updates)) {
            echo json_encode(['success' => true]);
            return;
        }

        $params[] = $input['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);

        logAction($pdo, 'admin', $session['id'], 'update_user', 'user', $input['user_id'], $input);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete') {
        $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing user id']);
            return;
        }

        // Verify user belongs to this admin
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND created_by_admin_id = ?");
        $stmt->execute([$userId, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        // Soft delete
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$userId]);

        logAction($pdo, 'admin', $session['id'], 'delete_user', 'user', $userId, []);

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
