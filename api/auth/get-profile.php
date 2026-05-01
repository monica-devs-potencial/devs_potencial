<?php
/**
 * GET /api/auth/get-profile.php
 * Returns the logged-in admin's profile data (email only).
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    fail('Método não permitido.', 405);
}

$pdo    = getDb($config);
$userId = (int)$_SESSION['admin_id'];

$stmt = $pdo->prepare('SELECT email FROM admin_users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

ok(['email' => $user['email'] ?? '']);
