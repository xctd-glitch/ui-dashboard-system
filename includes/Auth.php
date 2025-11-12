<?php
/**
 * Authentication and Authorization
 */

class Auth {
    const SESSION_TIMEOUT = 3600; // 1 hour

    /**
     * Check if user is logged in and return session data
     */
    public static function getSession() {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        // Check session timeout
        if (time() - $_SESSION['last_activity'] > self::SESSION_TIMEOUT) {
            self::logout();
            return null;
        }

        $_SESSION['last_activity'] = time();

        return [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'username' => $_SESSION['username'],
        ];
    }

    /**
     * Login user
     */
    public static function login($username, $password, $role, $pdo) {
        if (!isset($_SESSION)) {
            session_start();
        }

        $table = match ($role) {
            'superadmin' => 'superadmins',
            'admin' => 'admins',
            'user' => 'users',
            default => null
        };

        if (!$table) {
            return false;
        }

        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM $table WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $account = $stmt->fetch();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $account['id'];
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $account['username'];
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * Logout user
     */
    public static function logout() {
        if (!isset($_SESSION)) {
            session_start();
        }
        session_destroy();
    }

    /**
     * Check if user has required role
     */
    public static function hasRole($role, $session) {
        return $session && $session['role'] === $role;
    }

    /**
     * Get all session data
     */
    public static function requireRole($role) {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }
    }
}
