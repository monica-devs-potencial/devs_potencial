<?php
/**
 * GET /api/contact/list.php — Admin only. Lists contact form submissions.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

requireAuth();

$pdo  = getDb($config);
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;
$offset = ($page - 1) * $per;

$total = (int)$pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT id, nome, telefone, mensagem, ip_address, read_at, created_at
     FROM contacts
     ORDER BY created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$per, $offset]);
$rows = $stmt->fetchAll();

ok([
    'items'      => $rows,
    'total'      => $total,
    'page'       => $page,
    'per_page'   => $per,
    'pages'      => (int)ceil($total / $per),
]);
