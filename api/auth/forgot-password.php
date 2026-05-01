<?php
/**
 * POST /api/auth/forgot-password.php
 * Generates a one-time password-reset token, stores its hash in DB,
 * and sends a reset link to the admin user's e-mail via PHP mail().
 *
 * Security:
 *  - Generic response (never reveals whether the user/e-mail exists).
 *  - Rate-limited: reuses the login_attempts table (5 req/15 min per IP).
 *  - Token is random, single-use, stored as SHA-256 hash, expires in 1 hour.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/mailer.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

$b       = jsonInput();
$input   = trim((string)($b['username'] ?? $b['email'] ?? ''));
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Rate-limit (reuse login_attempts, 5 per IP per 15 min)
$pdo = getDb($config);
if (isRateLimited($pdo, $ip, $input)) {
    // Still return generic message to avoid timing oracle
    ok(['message' => 'Se o usuário existir, um e-mail foi enviado.']);
}

// Find user by username OR email
$stmt = $pdo->prepare(
    "SELECT id, username, email FROM admin_users
     WHERE (username = ? OR email = ?) AND email <> '' LIMIT 1"
);
$stmt->execute([$input, $input]);
$user = $stmt->fetch();

// Record attempt (regardless of whether user was found)
recordLoginAttempt($pdo, $ip, $input);

if ($user && !empty($user['email'])) {
    // Generate cryptographically-random token
    $token     = bin2hex(random_bytes(32));   // 64-char hex = 256-bit
    $tokenHash = hash('sha256', $token);
    $expires   = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    // Store hash + expiry (invalidates any previous token)
    $upd = $pdo->prepare(
        "UPDATE admin_users SET reset_token_hash = ?, reset_token_expires = ? WHERE id = ?"
    );
    $upd->execute([$tokenHash, $expires, $user['id']]);

    // Build reset link
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    $resetUrl = $scheme . '://' . $host . $basePath . '/admin/reset-password.php?token=' . urlencode($token);

    $to      = $user['email'];
    $subject = 'Redefinição de senha — Painel Admin';
    // Strip any control characters from username before putting in email body
    $safeUsername = preg_replace('/[\x00-\x1F\x7F]/', '', (string)$user['username']);
    $body    = "Olá, {$safeUsername}!\n\n"
             . "Recebemos um pedido de redefinição de senha para a sua conta.\n\n"
             . "Clique no link abaixo (válido por 1 hora):\n"
             . $resetUrl . "\n\n"
             . "Se não foi você, ignore este e-mail.\n\n"
             . "— Sistema DEVS_POTENCIAL";

    sendMail($config, $to, $subject, $body);
}

// Always return the same generic message
ok(['message' => 'Se o usuário existir, um e-mail foi enviado.']);
