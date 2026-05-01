<?php
/**
 * POST /api/contact/mark-read.php — Admin only. Mark contact as read.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}
requireAuth();
validateCsrf();

$b  = jsonInput();
$id = (int)($b['id'] ?? 0);
if ($id <= 0) {
    fail('ID inválido.');
}

$pdo  = getDb($config);
$stmt = $pdo->prepare('UPDATE contacts SET read_at = NOW() WHERE id = ? AND read_at IS NULL');
$stmt->execute([$id]);

ok(['marked' => $id]);
