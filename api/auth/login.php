<?php
/**
 * POST /api/auth/login.php
 * Body: { "username": "...", "password": "..." }
 * Implements brute-force rate limiting per IP and username.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

$body     = jsonInput();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

// Generic error message (do not reveal if user exists)
if ($username === '' || $password === '') {
    fail('Usuário ou senha inválidos.', 401);
}

$pdo = getDb($config);
$ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip  = trim(explode(',', $ip)[0]);

// ── Rate limit check ──────────────────────────────────────────────────────────
if (isRateLimited($pdo, $ip, $username)) {
    fail('Muitas tentativas. Aguarde alguns minutos e tente novamente.', 429);
}

// ── Lookup user ───────────────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1'
);
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Record failed attempt before responding
    recordLoginAttempt($pdo, $ip, $username);
    fail('Usuário ou senha inválidos.', 401);
}

// ── Successful login ──────────────────────────────────────────────────────────
session_regenerate_id(true);
$_SESSION['admin_id']       = (int)$user['id'];
$_SESSION['admin_username'] = $user['username'];

// Update last_login_at
$pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')
    ->execute([$user['id']]);

// Generate a fresh CSRF token for this session
csrfToken();

ok(['username' => $user['username']]);
