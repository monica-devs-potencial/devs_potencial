<?php
/**
 * POST /api/pix/update.php — Requires authentication + CSRF.
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

$b             = jsonInput();
$pix_key       = trim((string)($b['pix_key']       ?? ''));
$pix_hint_text = trim((string)($b['pix_hint_text'] ?? ''));
$whatsapp      = safeUrl(trim((string)($b['whatsapp_link'] ?? '')));

if ($pix_key === '') {
    fail('A chave PIX é obrigatória.');
}

$pdo  = getDb($config);
$stmt = $pdo->prepare(
    'INSERT INTO pix (id, pix_key, pix_hint_text, whatsapp_link)
     VALUES (1, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
         pix_key       = VALUES(pix_key),
         pix_hint_text = VALUES(pix_hint_text),
         whatsapp_link = VALUES(whatsapp_link)'
);
$stmt->execute([$pix_key, $pix_hint_text, $whatsapp]);

ok(['updated' => true]);
