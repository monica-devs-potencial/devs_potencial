<?php
/**
 * GET /api/csrf.php — Returns a fresh CSRF token for the current session.
 * Called by admin JS on page load to obtain the token to include as X-CSRF-Token header.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders(true);

requireAuth();
ok(['token' => csrfToken()]);
