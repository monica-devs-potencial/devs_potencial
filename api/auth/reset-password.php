<?php
/**
 * POST /api/auth/reset-password.php
 * Validates the reset token and sets a new password.
 *
 * Security:
 *  - Token is validated against its SHA-256 hash stored in DB.
 *  - Token expires after 1 hour.
 *  - Token is single-use: invalidated immediately after use.
 *  - New password must be at least 8 characters (enforced server-side).
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

$b        = jsonInput();
$token    = trim((string)($b['token']    ?? ''));
$password = (string)($b['password'] ?? '');

if ($token === '') {
    fail('Token inválido ou expirado.');
}

if (mb_strlen($password) < 8) {
    fail('A senha deve ter pelo menos 8 caracteres.');
}

$tokenHash = hash('sha256', $token);
$pdo       = getDb($config);

// Look up user with matching non-expired token
$stmt = $pdo->prepare(
    "SELECT id FROM admin_users
     WHERE reset_token_hash = ?
       AND reset_token_expires > NOW()
     LIMIT 1"
);
$stmt->execute([$tokenHash]);
$user = $stmt->fetch();

if (!$user) {
    fail('Token inválido ou expirado.');
}

// Update password and invalidate token in one query
$hash = password_hash($password, PASSWORD_DEFAULT);
$upd  = $pdo->prepare(
    "UPDATE admin_users
     SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL
     WHERE id = ?"
);
$upd->execute([$hash, $user['id']]);

ok(['message' => 'Senha redefinida com sucesso. Você já pode fazer login.']);
