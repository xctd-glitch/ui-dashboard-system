<?php
/**
 * Input Validation Utilities
 */

class Validator {
    /**
     * Validate ISO 2-letter country code
     */
    public static function isValidISOCountryCode($code) {
        return preg_match('/^[A-Z]{2}$/', $code) === 1;
    }

    /**
     * Validate and sanitize domain
     */
    public static function isValidDomain($domain) {
        // Basic domain validation: alphanumeric, dots, hyphens
        return preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*$/', $domain) === 1;
    }

    /**
     * Validate URL
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate device scope
     */
    public static function isValidDeviceScope($scope) {
        return in_array($scope, ['WAP', 'WEB', 'ALL']);
    }

    /**
     * Validate rule type
     */
    public static function isValidRuleType($type) {
        return in_array($type, ['mute_unmute', 'random_route', 'static_route']);
    }

    /**
     * Parse comma-separated ISO codes
     */
    public static function parseCountryList($input) {
        $codes = array_map('trim', explode(',', $input));
        $codes = array_filter($codes, function($code) {
            return self::isValidISOCountryCode($code);
        });
        return array_unique($codes);
    }

    /**
     * Parse domain list from textarea
     */
    public static function parseDomainList($input) {
        $domains = array_map('trim', explode("\n", $input));
        $domains = array_filter($domains, function($domain) {
            return !empty($domain) && self::isValidDomain($domain);
        });
        return array_unique($domains);
    }

    /**
     * Sanitize username
     */
    public static function sanitizeUsername($username) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $username);
    }

    /**
     * Validate username length and format
     */
    public static function isValidUsername($username) {
        return strlen($username) >= 3 && strlen($username) <= 255 && preg_match('/^[a-zA-Z0-9._-]+$/', $username);
    }

    /**
     * Validate password strength
     */
    public static function isValidPassword($password) {
        return strlen($password) >= 8;
    }

    /**
     * Validate email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitize JSON input
     */
    public static function sanitizeJson($data) {
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }
}
