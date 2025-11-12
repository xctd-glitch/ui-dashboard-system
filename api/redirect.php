<?php
/**
 * Redirect API Endpoint
 * GET /api/redirect.php?user_id=X&token=Y
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GeoIP.php';
require_once __DIR__ . '/../includes/RedirectLogic.php';
require_once __DIR__ . '/../includes/Validator.php';

// Get request parameters
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$token = isset($_GET['token']) ? $_GET['token'] : null;

if (!$userId || !$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or token']);
    exit;
}

// Verify token (simple API token validation)
// In production, use JWT or similar
$stmt = $pdo->prepare("
    SELECT id, username FROM users WHERE id = ? AND is_active = 1 LIMIT 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Gather client information
$clientIP = \GeoIP::getClientIP();
$deviceType = \GeoIP::detectDeviceType();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isVPN = \GeoIP::isVPN($clientIP);
$country = \GeoIP::getCountryFromIP($clientIP);

// Initialize redirect logic
$redirectLogic = new \RedirectLogic($pdo, $userId, $country, $deviceType, $isVPN);

// Get decision
$decision = $redirectLogic->decide();

// Log the redirect request
$stmt = $pdo->prepare("
    INSERT INTO redirect_logs
    (user_id, country_iso, device_type, ip_address, user_agent, is_vpn, rule_applied)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $userId,
    $country,
    $deviceType,
    $clientIP,
    $userAgent,
    $isVPN ? 1 : 0,
    $decision['rule_applied']
]);

$logId = $pdo->lastInsertId();

if ($decision['decision'] === 'redirect' && isset($decision['target'])) {
    // Update log with target URL
    $stmt = $pdo->prepare("UPDATE redirect_logs SET target_url = ? WHERE id = ?");
    $stmt->execute([$decision['target'], $logId]);

    echo json_encode([
        'status' => 'ok',
        'log_id' => $logId,
        'action' => 'redirect',
        'target' => $decision['target'],
        'rule' => $decision['rule_applied']
    ]);
} else {
    // Normal behavior - no redirect
    echo json_encode([
        'status' => 'ok',
        'log_id' => $logId,
        'action' => 'normal',
        'rule' => null
    ]);
}

http_response_code(200);
