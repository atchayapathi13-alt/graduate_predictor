<?php
/**
 * Application Helpers & Utility Functions
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/database.php';

/**
 * Sanitize user input for HTML display
 */
function sanitize(?string $str): string {
    return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8');
}

/**
 * Get dynamic application base URL
 */
function baseUrl(string $path = ''): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Determine base folder path, typically /graduate_predictor
    $pos = strpos($scriptName, '/graduate_predictor');
    if ($pos !== false) {
        $base = '/graduate_predictor';
    } else {
        $base = '';
    }
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Fetch system settings from DB
 */
function getSettings(): array {
    static $settingsCache = null;
    if ($settingsCache !== null) {
        return $settingsCache;
    }
    
    $db = getDB();
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    return $settingsCache;
}

/**
 * Get specific setting with default fallback
 */
function getSetting(string $key, $default = null) {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

/**
 * Generate Readiness Category Badge HTML
 */
function getReadinessBadge(string $category): string {
    $clean = trim($category);
    if ($clean === 'High Readiness') {
        return '<span class="badge badge-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill font-weight-600"><i class="fas fa-check-circle me-1"></i> High Readiness</span>';
    } elseif ($clean === 'Moderate Readiness') {
        return '<span class="badge badge-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill font-weight-600"><i class="fas fa-exclamation-circle me-1"></i> Moderate Readiness</span>';
    } else {
        return '<span class="badge badge-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill font-weight-600"><i class="fas fa-arrow-circle-up me-1"></i> Needs Improvement</span>';
    }
}

/**
 * Generate Priority Badge HTML
 */
function getPriorityBadge(string $priority): string {
    $p = strtolower(trim($priority));
    if ($p === 'high') {
        return '<span class="badge bg-danger text-white rounded-pill px-2 py-1"><i class="fas fa-fire me-1"></i> High Priority</span>';
    } elseif ($p === 'medium') {
        return '<span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-clock me-1"></i> Medium</span>';
    } else {
        return '<span class="badge bg-info text-dark rounded-pill px-2 py-1"><i class="fas fa-info-circle me-1"></i> Low</span>';
    }
}

/**
 * Return formatted JSON response and terminate
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
