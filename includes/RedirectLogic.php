<?php
/**
 * Redirect Decision Logic
 */

class RedirectLogic {
    private $pdo;
    private $userId;
    private $country;
    private $deviceType;
    private $isVPN;

    public function __construct($pdo, $userId = null, $country = null, $deviceType = 'WEB', $isVPN = false) {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->country = $country;
        $this->deviceType = $deviceType;
        $this->isVPN = $isVPN;
    }

    /**
     * Main decision logic based on specification
     */
    public function decide() {
        // Check if system is enabled
        $stmt = $this->pdo->prepare("SELECT system_on FROM system_config WHERE id = 1");
        $stmt->execute();
        $config = $stmt->fetch();

        if (!$config || !$config['system_on']) {
            return ['decision' => 'normal', 'rule_applied' => null];
        }

        if (!$this->userId) {
            return ['decision' => 'normal', 'rule_applied' => null];
        }

        // Get applicable rules for user
        $stmt = $this->pdo->prepare("
            SELECT id, rule_type, is_enabled, target_url, mute_duration_on, mute_duration_off
            FROM redirect_rules
            WHERE user_id = ? AND is_enabled = 1
        ");
        $stmt->execute([$this->userId]);
        $rules = $stmt->fetchAll();

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule);
            if ($result) {
                return $result;
            }
        }

        return ['decision' => 'normal', 'rule_applied' => null];
    }

    /**
     * Evaluate individual rule
     */
    private function evaluateRule($rule) {
        switch ($rule['rule_type']) {
            case 'mute_unmute':
                return $this->evaluateMuteUnmuteRule($rule);
            case 'random_route':
                return $this->evaluateRandomRouteRule($rule);
            case 'static_route':
                return $this->evaluateStaticRouteRule($rule);
        }

        return null;
    }

    /**
     * Mute/Unmute: 2 min on, 5 min off cycle
     */
    private function evaluateMuteUnmuteRule($rule) {
        $stmt = $this->pdo->prepare("SELECT last_state_change, is_muted FROM rule_state WHERE rule_id = ?");
        $stmt->execute([$rule['id']]);
        $state = $stmt->fetch();

        if (!$state) {
            // Initialize state
            $stmt = $this->pdo->prepare("
                INSERT INTO rule_state (rule_id, last_state_change, is_muted)
                VALUES (?, NOW(), 0)
            ");
            $stmt->execute([$rule['id']]);
            return null;
        }

        $lastChange = strtotime($state['last_state_change']);
        $now = time();
        $elapsed = $now - $lastChange;

        $onDuration = $rule['mute_duration_on'] * 60; // Convert to seconds
        $offDuration = $rule['mute_duration_off'] * 60;

        if ($state['is_muted'] === 0) {
            // Currently enforcing rules (on)
            if ($elapsed > $onDuration) {
                // Switch to off
                $stmt = $this->pdo->prepare("
                    UPDATE rule_state SET is_muted = 1, last_state_change = NOW() WHERE rule_id = ?
                ");
                $stmt->execute([$rule['id']]);
                return null; // Now behave normally (no decision)
            }
            // Still in on period - return target
            $target = $this->getTargetForUser();
            return ['decision' => 'redirect', 'target' => $target, 'rule_applied' => 'mute_unmute'];
        } else {
            // Currently not enforcing (off)
            if ($elapsed > $offDuration) {
                // Switch back to on
                $stmt = $this->pdo->prepare("
                    UPDATE rule_state SET is_muted = 0, last_state_change = NOW() WHERE rule_id = ?
                ");
                $stmt->execute([$rule['id']]);
                $target = $this->getTargetForUser();
                return ['decision' => 'redirect', 'target' => $target, 'rule_applied' => 'mute_unmute'];
            }
            return null; // Still in off period
        }
    }

    /**
     * Random route: randomize target for eligible traffic
     */
    private function evaluateRandomRouteRule($rule) {
        if ($this->passesFilters()) {
            // Get all available target URLs for user
            $stmt = $this->pdo->prepare("
                SELECT url FROM user_target_urls WHERE user_id = ? ORDER BY RAND() LIMIT 1
            ");
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch();

            if ($result) {
                return ['decision' => 'redirect', 'target' => $result['url'], 'rule_applied' => 'random_route'];
            }
        }

        return null;
    }

    /**
     * Static route: always configured target
     */
    private function evaluateStaticRouteRule($rule) {
        if ($this->passesFilters() && $rule['target_url']) {
            return ['decision' => 'redirect', 'target' => $rule['target_url'], 'rule_applied' => 'static_route'];
        }

        return null;
    }

    /**
     * Check if traffic passes user filters (country, device, VPN)
     */
    private function passesFilters() {
        // Check country filter
        if ($this->country) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as cnt FROM user_countries WHERE user_id = ? AND iso_code = ?
            ");
            $stmt->execute([$this->userId, $this->country]);
            $result = $stmt->fetch();

            if ($result['cnt'] === 0) {
                return false; // Country not in allowed list
            }
        }

        // Check device scope
        $stmt = $this->pdo->prepare("
            SELECT device_scope FROM user_routing_config WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $config = $stmt->fetch();

        if ($config) {
            $scope = $config['device_scope'];
            if ($scope !== 'ALL' && $scope !== $this->deviceType) {
                return false; // Device type doesn't match
            }
        }

        // Could add VPN check here if needed
        // if ($this->isVPN) return false;

        return true;
    }

    /**
     * Get target URL for user (based on domain selection)
     */
    private function getTargetForUser() {
        $stmt = $this->pdo->prepare("
            SELECT selection_type, specific_domain FROM user_domain_selection WHERE user_id = ?
        ");
        $stmt->execute([$this->userId]);
        $selection = $stmt->fetch();

        if (!$selection) {
            return null;
        }

        $domain = null;

        switch ($selection['selection_type']) {
            case 'random_global':
                $domain = $this->getRandomAdminDomain();
                break;
            case 'random_user':
                $domain = $this->getRandomUserDomain();
                break;
            case 'specific':
                $domain = $selection['specific_domain'];
                break;
        }

        if (!$domain) {
            return null;
        }

        // Get target URL for user
        $stmt = $this->pdo->prepare("
            SELECT url FROM user_target_urls WHERE user_id = ? LIMIT 1
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch();

        if ($result) {
            // Construct URL with domain
            return str_replace('{domain}', $domain, $result['url']);
        }

        return null;
    }

    /**
     * Get random domain from admin's tagged domains
     */
    private function getRandomAdminDomain() {
        // Get user's admin and their tags
        $stmt = $this->pdo->prepare("
            SELECT created_by_admin_id FROM users WHERE id = ?
        ");
        $stmt->execute([$this->userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        $adminId = $user['created_by_admin_id'];

        // Get random domain from admin's parked domains
        $stmt = $this->pdo->prepare("
            SELECT domain FROM admin_parked_domains
            WHERE admin_id = ?
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch();

        return $result ? $result['domain'] : null;
    }

    /**
     * Get random domain from user's parked domains
     */
    private function getRandomUserDomain() {
        $stmt = $this->pdo->prepare("
            SELECT domain FROM user_parked_domains
            WHERE user_id = ?
            ORDER BY RAND()
            LIMIT 1
        ");
        $stmt->execute([$this->userId]);
        $result = $stmt->fetch();

        return $result ? $result['domain'] : null;
    }
}
