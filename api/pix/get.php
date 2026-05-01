<?php
/**
 * GET /api/pix/get.php — Public endpoint.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

$pdo = getDb($config);
$row = $pdo->query('SELECT pix_key, pix_hint_text, whatsapp_link FROM pix WHERE id = 1')->fetch();

if (!$row) {
    fail('Configuração PIX não encontrada.', 404);
}
ok($row);
