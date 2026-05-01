<?php
/**
 * POST /api/plans/create.php — Requires authentication + CSRF.
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

$b           = jsonInput();
$title       = trim((string)($b['title']         ?? ''));
$description = trim((string)($b['description']   ?? ''));
$price_text  = trim((string)($b['price_text']    ?? ''));
$features    = $b['features'] ?? [];
$featured    = (int)(bool)($b['featured']     ?? false);
$badge_text  = trim((string)($b['badge_text']    ?? ''));
$whatsapp    = safeUrl(trim((string)($b['whatsapp_link'] ?? '')));
$image_url   = safeUrl(trim((string)($b['image_url']     ?? '')));
$sort_order  = (int)($b['sort_order'] ?? 0);
$active      = isset($b['active']) ? (int)(bool)$b['active'] : 1;

if ($title === '')       { fail('O campo título é obrigatório.'); }
if ($description === '') { fail('O campo descrição é obrigatório.'); }
if (!is_array($features)) { $features = []; }

$features_json = json_encode(array_values(array_map('strval', $features)));

$pdo  = getDb($config);
$stmt = $pdo->prepare(
    'INSERT INTO plans
        (title, description, price_text, features_json, featured, badge_text, whatsapp_link, image_url, sort_order, active)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([$title, $description, $price_text, $features_json, $featured, $badge_text, $whatsapp, $image_url, $sort_order, $active]);

ok(['id' => (int)$pdo->lastInsertId()]);
