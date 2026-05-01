<?php
/**
 * GET /api/services/list.php — Public endpoint. No auth required.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

$pdo  = getDb($config);
$rows = $pdo->query(
    'SELECT id, title, description, price_text, badge_text, badge_color,
            whatsapp_link, image_url, sort_order, active
     FROM services
     WHERE active = 1
     ORDER BY sort_order ASC, id ASC'
)->fetchAll();

foreach ($rows as &$row) {
    $row['active'] = (bool)$row['active'];
}
unset($row);

ok($rows);
