<?php
/**
 * POST /api/services/delete.php — Requires authentication + CSRF.
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
$stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) {
    fail('Serviço não encontrado.', 404);
}
ok(['deleted' => $id]);
