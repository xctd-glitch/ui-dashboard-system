<?php
/**
 * GeoIP Detection
 */

class GeoIP {
    /**
     * Detect country from IP address
     * Using a simple lookup (in production, use GeoIP2/MaxMind)
     */
    public static function getCountryFromIP($ip) {
        // For demo purposes, return a default
        // In production, integrate with GeoIP2 or MaxMind
        // For now, just validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // Placeholder: In real implementation, query GeoIP database
            return null; // null means country detection failed
        }
        return null;
    }

    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Handle comma-separated list
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Detect device type from User-Agent
     */
    public static function detectDeviceType($userAgent = null) {
        if (!$userAgent) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'WAP';
        } elseif (preg_match('/tablet|ipad|android/i', $userAgent)) {
            return 'WAP'; // Treat tablets as mobile
        }

        return 'WEB';
    }

    /**
     * Detect if traffic is from VPN/Proxy
     * Uses simple heuristics (in production, use dedicated VPN detection service)
     */
    public static function isVPN($ip) {
        // Placeholder: In real implementation, query VPN detection service
        // For demo, return false
        return false;
    }
}
