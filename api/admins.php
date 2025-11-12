<?php
/**
 * Admin Management API
 * Requires superadmin authentication
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Validator.php';

// Start session and verify superadmin
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

function handleGet($action, $pdo) {
    if ($action === 'list') {
        // Get all admins
        $stmt = $pdo->prepare("
            SELECT id, username, email, created_at, is_active FROM admins ORDER BY created_at DESC
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll();

        // Load tags for each admin
        foreach ($admins as &$admin) {
            $stmt = $pdo->prepare("
                SELECT t.id, t.name FROM tags t
                INNER JOIN admin_tags at ON t.id = at.tag_id
                WHERE at.admin_id = ?
            ");
            $stmt->execute([$admin['id']]);
            $admin['tags'] = $stmt->fetchAll();
        }

        echo json_encode(['admins' => $admins]);
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
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$input['username']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }

        // Create admin
        $passwordHash = password_hash($input['password'], PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("
            INSERT INTO admins (username, password_hash, email, created_by_superadmin_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$input['username'], $passwordHash, $input['email'], $session['id']]);
        $adminId = $pdo->lastInsertId();

        // Assign tags if provided
        if (isset($input['tags']) && is_array($input['tags'])) {
            foreach ($input['tags'] as $tagId) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_tags (admin_id, tag_id) VALUES (?, ?)
                ");
                $stmt->execute([$adminId, (int)$tagId]);
            }
        }

        // Log action
        logAction($pdo, 'superadmin', $session['id'], 'create_admin', 'admin', $adminId, ['username' => $input['username']]);

        echo json_encode(['success' => true, 'admin_id' => $adminId]);
        http_response_code(201);
    } elseif ($action === 'reset_password') {
        if (!isset($input['admin_id'], $input['new_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        if (!\Validator::isValidPassword($input['new_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password too short']);
            return;
        }

        $passwordHash = password_hash($input['new_password'], PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $stmt->execute([$passwordHash, $input['admin_id']]);

        logAction($pdo, 'superadmin', $session['id'], 'reset_admin_password', 'admin', $input['admin_id'], []);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update') {
        if (!isset($input['admin_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing admin_id']);
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

        $params[] = $input['admin_id'];
        $stmt = $pdo->prepare("UPDATE admins SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);

        logAction($pdo, 'superadmin', $session['id'], 'update_admin', 'admin', $input['admin_id'], $input);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete') {
        $adminId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$adminId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing admin id']);
            return;
        }

        // Soft delete
        $stmt = $pdo->prepare("UPDATE admins SET is_active = 0 WHERE id = ?");
        $stmt->execute([$adminId]);

        logAction($pdo, 'superadmin', $session['id'], 'delete_admin', 'admin', $adminId, []);

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
