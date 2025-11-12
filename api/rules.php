<?php
/**
 * Redirect Rules Management API
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
if (!$session || $session['role'] !== 'user') {
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
        $stmt = $pdo->prepare("
            SELECT id, rule_type, is_enabled, target_url, mute_duration_on, mute_duration_off
            FROM redirect_rules
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$session['id']]);
        $rules = $stmt->fetchAll();

        echo json_encode(['rules' => $rules]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePost($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create') {
        if (!isset($input['rule_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing rule type']);
            return;
        }

        if (!\Validator::isValidRuleType($input['rule_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid rule type']);
            return;
        }

        // Validate target URL for certain rule types
        if ($input['rule_type'] === 'static_route' && isset($input['target_url'])) {
            if (!\Validator::isValidUrl($input['target_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid target URL']);
                return;
            }
        }

        $ruleType = $input['rule_type'];
        $isEnabled = isset($input['is_enabled']) ? (int)$input['is_enabled'] : 1;
        $muteOnDuration = $input['mute_duration_on'] ?? 120;
        $muteOffDuration = $input['mute_duration_off'] ?? 300;
        $targetUrl = $input['target_url'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO redirect_rules
            (user_id, rule_type, is_enabled, mute_duration_on, mute_duration_off, target_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $session['id'],
            $ruleType,
            $isEnabled,
            $muteOnDuration,
            $muteOffDuration,
            $targetUrl
        ]);

        $ruleId = $pdo->lastInsertId();

        // Initialize rule state for mute/unmute rules
        if ($ruleType === 'mute_unmute') {
            $stmt = $pdo->prepare("
                INSERT INTO rule_state (rule_id, last_state_change, is_muted)
                VALUES (?, NOW(), 0)
            ");
            $stmt->execute([$ruleId]);
        }

        echo json_encode(['success' => true, 'rule_id' => $ruleId]);
        http_response_code(201);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePut($action, $pdo, $session) {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update') {
        if (!isset($input['rule_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing rule_id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM redirect_rules WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['rule_id'], $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Rule not found']);
            return;
        }

        $updates = [];
        $params = [];

        if (isset($input['is_enabled'])) {
            $updates[] = 'is_enabled = ?';
            $params[] = (int)$input['is_enabled'];
        }

        if (isset($input['target_url'])) {
            if (!\Validator::isValidUrl($input['target_url'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid target URL']);
                return;
            }
            $updates[] = 'target_url = ?';
            $params[] = $input['target_url'];
        }

        if (isset($input['mute_duration_on'])) {
            $updates[] = 'mute_duration_on = ?';
            $params[] = (int)$input['mute_duration_on'];
        }

        if (isset($input['mute_duration_off'])) {
            $updates[] = 'mute_duration_off = ?';
            $params[] = (int)$input['mute_duration_off'];
        }

        if (empty($updates)) {
            echo json_encode(['success' => true]);
            return;
        }

        $params[] = $input['rule_id'];
        $stmt = $pdo->prepare("UPDATE redirect_rules SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($params);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}

function handleDelete($action, $pdo, $session) {
    if ($action === 'delete') {
        $ruleId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if (!$ruleId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing rule id']);
            return;
        }

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM redirect_rules WHERE id = ? AND user_id = ?");
        $stmt->execute([$ruleId, $session['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Rule not found']);
            return;
        }

        // Delete rule state if exists
        $stmt = $pdo->prepare("DELETE FROM rule_state WHERE rule_id = ?");
        $stmt->execute([$ruleId]);

        $stmt = $pdo->prepare("DELETE FROM redirect_rules WHERE id = ?");
        $stmt->execute([$ruleId]);

        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
}
