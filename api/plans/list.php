<?php
/**
 * GET /api/plans/list.php — Public endpoint. No auth required.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

$pdo  = getDb($config);
$rows = $pdo->query(
    'SELECT id, title, description, price_text, features_json, featured,
            badge_text, whatsapp_link, image_url, sort_order, active
     FROM plans
     WHERE active = 1
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();

foreach ($rows as &$row) {
    $row['features'] = json_decode($row['features_json'] ?: '[]', true) ?: [];
    $row['featured']  = (bool)$row['featured'];
    $row['active']    = (bool)$row['active'];
    unset($row['features_json']);
}
unset($row);

ok($rows);
