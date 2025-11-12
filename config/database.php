<?php
/**
 * Database Configuration
 */

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'ui_dashboard';
const DB_USER = 'root';
const DB_PASS = 'password';

/**
 * Get PDO database connection
 */
function getDatabaseConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Initialize connection for this request
$pdo = getDatabaseConnection();
