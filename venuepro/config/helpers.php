<?php
/**
 * VenuePro General Helpers & Utilities
 */

/**
 * Output JSON response and terminate script.
 */
function jsonResponse(bool $success, string $message = '', $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Escape string for safe HTML output (XSS protection).
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize clean string input.
 */
function sanitize(?string $input): string {
    return trim($input ?? '');
}

/**
 * Format currency.
 */
function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

/**
 * Format date nicely.
 */
function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Read JSON input body from POST request.
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
