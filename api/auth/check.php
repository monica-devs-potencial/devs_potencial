<?php
/**
 * GET /api/auth/check.php — Returns 200 with username if session is active, 401 otherwise.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

requireAuth();
ok(['username' => $_SESSION['admin_username'] ?? '']);
