<?php
/* CraveDrip — Shared API helpers (security, session, CSRF, JSON) */

function session_secure_start(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

function set_security_headers(): void {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

function csrf_token(): string {
    session_secure_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_meta(): string {
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function api_require_auth(): void {
    session_secure_start();
    if (empty($_SESSION['user_id'])) {
        api_json(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}

function api_require_csrf(): void {
    $token   = trim($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? ''));
    $session = $_SESSION['csrf_token'] ?? '';
    if (empty($session) || !hash_equals($session, $token)) {
        api_json(['ok' => false, 'error' => 'Invalid request token'], 403);
    }
}

function api_json(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* Input helpers — always read from $_POST */
function v_str(string $key, int $maxLen = 255): string {
    return mb_substr(trim($_POST[$key] ?? ''), 0, $maxLen);
}

function v_float(string $key): float {
    return (float)($_POST[$key] ?? 0);
}

function v_int(string $key, int $min = 0): int {
    return max($min, (int)($_POST[$key] ?? 0));
}
