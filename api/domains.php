<?php
/**
 * Domain Management API
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
    case 'DELETE':
        handleDelete($action, $pdo, $session);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet($action, $pdo, $session) {
    if ($action === 'admin_domains') {
        // Get domains managed by admin
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, domain, cloudflare_synced, cloudflare_sync_at, created_at
            FROM admin_parked_domains
            WHERE admin_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $domains = $stmt->fetchAll();

        echo json_encode(['domains' => $domains]);
    } elseif ($action === 'user_domains') {
        // Get domains managed by user
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, domain, cloudflare_synced, cloudflare_sync_at, created_at
            FROM user_parked_domains
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $domains = $stmt->fetchAll();

        echo json_encode(['domains' => $domains]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add_admin_domains') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['domains_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domains']);
            return;
        }

        $domains = \Validator::parseDomainList($input['domains_text']);

        if (empty($domains)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid domains provided']);
            return;
        }

        // Check total count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM admin_parked_domains WHERE admin_id = ?");
        $stmt->execute([$session['id']]);
        $current = $stmt->fetch();

        if (count($domains) + $current['cnt'] > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 10 domains allowed']);
            return;
        }

        // Add domains
        $stmt = $pdo->prepare("
            INSERT INTO admin_parked_domains (admin_id, domain)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE domain = domain
        ");

        $added = 0;
        foreach ($domains as $domain) {
            try {
                $stmt->execute([$session['id'], $domain]);
                $added += $stmt->rowCount();
            } catch (Exception $e) {
                // Duplicate or other error, continue
            }
        }

        echo json_encode(['success' => true, 'added' => $added]);
        http_response_code(201);
    } elseif ($action === 'add_user_domains') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        if (!isset($input['domains_text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domains']);
            return;
        }

        $domains = \Validator::parseDomainList($input['domains_text']);

        if (empty($domains)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid domains provided']);
            return;
        }

        // Check total count
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM user_parked_domains WHERE user_id = ?");
        $stmt->execute([$session['id']]);
        $current = $stmt->fetch();

        if (count($domains) + $current['cnt'] > 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 10 domains allowed']);
            return;
        }

        // Add domains
        $stmt = $pdo->prepare("
            INSERT INTO user_parked_domains (user_id, domain)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE domain = domain
        ");

        $added = 0;
        foreach ($domains as $domain) {
            try {
                $stmt->execute([$session['id'], $domain]);
                $added += $stmt->rowCount();
            } catch (Exception $e) {
                // Duplicate or other error, continue
            }
        }

        echo json_encode(['success' => true, 'added' => $added]);
        http_response_code(201);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete_admin_domain') {
        if ($session['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $domainId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$domainId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domain id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM admin_parked_domains WHERE id = ? AND admin_id = ?");
        $stmt->execute([$domainId, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Domain not found']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM admin_parked_domains WHERE id = ?");
        $stmt->execute([$domainId]);

        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_user_domain') {
        if ($session['role'] !== 'user') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $domainId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$domainId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing domain id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM user_parked_domains WHERE id = ? AND user_id = ?");
        $stmt->execute([$domainId, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Domain not found']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM user_parked_domains WHERE id = ?");
        $stmt->execute([$domainId]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}
