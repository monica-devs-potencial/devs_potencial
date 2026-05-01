<?php
/**
 * POST /api/auth/update-profile.php
 * Updates the logged-in admin's email and/or password.
 *
 * Security:
 *  - Requires active session (requireAuth).
 *  - Current password must be confirmed before any change is saved.
 *  - E-mail uniqueness is enforced (cannot reuse another user's e-mail).
 *  - New password must be at least 8 characters.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

validateCsrf();

$b               = jsonInput();
$currentPassword = (string)($b['current_password'] ?? '');
$newEmail        = trim((string)($b['email'] ?? ''));
$newPassword     = (string)($b['new_password'] ?? '');

if ($currentPassword === '') {
    fail('Informe sua senha atual.');
}

$pdo    = getDb($config);
$userId = (int)$_SESSION['admin_id'];

// Fetch current user record
$stmt = $pdo->prepare('SELECT password_hash, email FROM admin_users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, (string)$user['password_hash'])) {
    fail('Senha atual incorreta.');
}

// Validate fields
if ($newEmail === '' && $newPassword === '') {
    fail('Nenhuma alteração informada.');
}

if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    fail('E-mail inválido.');
}

if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
    fail('A nova senha deve ter pelo menos 8 caracteres.');
}

// Check e-mail uniqueness (if changing)
if ($newEmail !== '' && strtolower($newEmail) !== strtolower((string)$user['email'])) {
    $chk = $pdo->prepare('SELECT id FROM admin_users WHERE email = ? AND id <> ? LIMIT 1');
    $chk->execute([$newEmail, $userId]);
    if ($chk->fetch()) {
        fail('Este e-mail já está em uso.');
    }
}

// Build UPDATE
$fields = [];
$params = [];

if ($newEmail !== '') {
    $fields[] = 'email = ?';
    $params[]  = $newEmail;
}

if ($newPassword !== '') {
    $fields[] = 'password_hash = ?';
    $params[]  = password_hash($newPassword, PASSWORD_DEFAULT);
}

$params[] = $userId;
$sql = 'UPDATE admin_users SET ' . implode(', ', $fields) . ' WHERE id = ?';
$pdo->prepare($sql)->execute($params);

ok(['message' => 'Perfil atualizado com sucesso.']);
