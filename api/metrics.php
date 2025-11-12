<?php
/**
 * Metrics and Reporting API
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

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

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Parse date parameters
$dateRange = isset($_GET['range']) ? $_GET['range'] : 'today';
$startDate = null;
$endDate = date('Y-m-d H:i:s');

switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        break;
    case 'yesterday':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $endDate = date('Y-m-d 00:00:00');
        break;
    case 'weekly':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case 'custom':
        if (isset($_GET['start']) && isset($_GET['end'])) {
            $startDate = $_GET['start'] . ' 00:00:00';
            $endDate = $_GET['end'] . ' 23:59:59';
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing start or end date']);
            exit;
        }
        break;
    default:
        $startDate = date('Y-m-d 00:00:00');
}

switch ($action) {
    case 'summary':
        getSummary($pdo, $session, $startDate, $endDate);
        break;
    case 'by_country':
        getByCountry($pdo, $session, $startDate, $endDate);
        break;
    case 'by_device':
        getByDevice($pdo, $session, $startDate, $endDate);
        break;
    case 'by_ip':
        getByIP($pdo, $session, $startDate, $endDate);
        break;
    case 'clicks':
        getClicks($pdo, $session, $startDate, $endDate);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

function getSummary($pdo, $session, $startDate, $endDate) {
    if ($session['role'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total_clicks,
                COUNT(DISTINCT ip_address) as unique_ips,
                COUNT(DISTINCT country_iso) as countries,
                COUNT(DISTINCT DATE(created_at)) as days
            FROM redirect_logs
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$session['id'], $startDate, $endDate]);
    } else {
        // Admin can see all users' data
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized for this action']);
        return;
    }

    $result = $stmt->fetch();
    echo json_encode($result);
}

function getByCountry($pdo, $session, $startDate, $endDate) {
    if ($session['role'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT
                country_iso,
                COUNT(*) as clicks,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM redirect_logs
            WHERE user_id = ? AND created_at BETWEEN ? AND ? AND country_iso IS NOT NULL
            GROUP BY country_iso
            ORDER BY clicks DESC
        ");
        $stmt->execute([$session['id'], $startDate, $endDate]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized for this action']);
        return;
    }

    $results = $stmt->fetchAll();
    echo json_encode(['data' => $results]);
}

function getByDevice($pdo, $session, $startDate, $endDate) {
    if ($session['role'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT
                device_type,
                COUNT(*) as clicks,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM redirect_logs
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY device_type
            ORDER BY clicks DESC
        ");
        $stmt->execute([$session['id'], $startDate, $endDate]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized for this action']);
        return;
    }

    $results = $stmt->fetchAll();
    echo json_encode(['data' => $results]);
}

function getByIP($pdo, $session, $startDate, $endDate) {
    if ($session['role'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT
                ip_address,
                COUNT(*) as clicks,
                device_type,
                country_iso,
                MAX(created_at) as last_seen
            FROM redirect_logs
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY ip_address
            ORDER BY clicks DESC
            LIMIT 100
        ");
        $stmt->execute([$session['id'], $startDate, $endDate]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized for this action']);
        return;
    }

    $results = $stmt->fetchAll();
    echo json_encode(['data' => $results]);
}

function getClicks($pdo, $session, $startDate, $endDate) {
    if ($session['role'] === 'user') {
        $stmt = $pdo->prepare("
            SELECT
                DATE(created_at) as date,
                COUNT(*) as clicks
            FROM redirect_logs
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$session['id'], $startDate, $endDate]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized for this action']);
        return;
    }

    $results = $stmt->fetchAll();
    echo json_encode(['data' => $results]);
}
