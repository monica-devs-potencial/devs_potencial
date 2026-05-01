<?php
/**
 * app/db.php — Shared bootstrap: PDO, session, CSRF, security headers, helpers.
 * Include ONCE at the top of every API/admin endpoint.
 *
 * Usage:  require_once __DIR__ . '/../../app/db.php';   (from api/auth/)
 *         require_once __DIR__ . '/../app/db.php';       (from admin/)
 */
declare(strict_types=1);

// ── Session ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $sessionParams = [
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
    ];
    // Enable Secure flag when running over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $sessionParams['cookie_secure'] = true;
    }
    session_start($sessionParams);
}

// ── Config ────────────────────────────────────────────────────────────────────
// Config file lives at repo root (one level up from app/)
$configFile = __DIR__ . '/../config.php';
if (!file_exists($configFile)) {
    // Return a friendly 500 without leaking paths
    http_response_code(500);
    // Only send JSON Content-Type if this is an API call (not a page include)
    if (defined('API_ENDPOINT')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Server configuration missing. Copy config.example.php to config.php.']);
    } else {
        echo '<h1>Configuração do servidor ausente.</h1><p>Copie <code>config.example.php</code> para <code>config.php</code> e preencha as credenciais.</p>';
    }
    exit;
}
$config = require $configFile;

// ── PDO singleton ─────────────────────────────────────────────────────────────
function getDb(array $config): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_name']
    );
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

// ── Security headers ──────────────────────────────────────────────────────────
function sendSecurityHeaders(bool $isApi = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://fonts.gstatic.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; media-src 'self'; connect-src 'self'; frame-ancestors 'none';");
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
    }
}

// ── Auth helpers ──────────────────────────────────────────────────────────────
function isLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_username']);
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Não autenticado.']);
        exit;
    }
}

function requireAuthPage(string $loginUrl = '../admin/login.php'): void
{
    if (!isLoggedIn()) {
        header('Location: ' . $loginUrl);
        exit;
    }
}

// ── CSRF helpers ──────────────────────────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!hash_equals((string)csrfToken(), (string)$token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido.']);
        exit;
    }
}

// ── Brute-force protection ────────────────────────────────────────────────────
/**
 * Record a failed login attempt.
 */
function recordLoginAttempt(PDO $pdo, string $ip, string $username): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)'
    );
    $stmt->execute([$ip, $username]);
    // Purge old attempts (>1 hour) to keep table small
    $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)')
        ->execute();
}

/**
 * Returns true if the IP or username is currently rate-limited.
 * Max 10 attempts per 15 minutes per IP, 5 per username.
 */
function isRateLimited(PDO $pdo, string $ip, string $username): bool
{
    $window = 'DATE_SUB(NOW(), INTERVAL 15 MINUTE)';
    $stmtIp = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > $window"
    );
    $stmtIp->execute([$ip]);
    if ((int)$stmtIp->fetchColumn() >= 10) {
        return true;
    }
    $stmtUser = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts WHERE username = ? AND attempted_at > $window"
    );
    $stmtUser->execute([$username]);
    if ((int)$stmtUser->fetchColumn() >= 5) {
        return true;
    }
    return false;
}

// ── JSON helpers ──────────────────────────────────────────────────────────────
function jsonInput(): array
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function ok(mixed $data): void
{
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// ── Site settings helper ──────────────────────────────────────────────────────
function getSiteSetting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare("SELECT `value` FROM site_settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['value'] : $default;
}

function getAllSiteSettings(PDO $pdo): array
{
    $rows = $pdo->query("SELECT `key`, `value` FROM site_settings")->fetchAll();
    $out  = [];
    foreach ($rows as $row) {
        $out[$row['key']] = $row['value'];
    }
    return $out;
}

// ── XSS helper ────────────────────────────────────────────────────────────────
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Validate URL (allow empty, block javascript:) ─────────────────────────────
function safeUrl(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https', ''], true)) {
        return '';
    }
    return $url;
}
