<?php
/**
 * config.example.php — Copy this file to config.php and fill in your real values.
 *
 * NEVER commit config.php to version control (it is already in .gitignore).
 * Place config.php at the repo root, one level above the public files.
 */
return [
    // ── MySQL (Hostinger: find credentials in hPanel > Databases) ─────────────
    'db_host' => 'localhost',           // Usually 'localhost' on Hostinger
    'db_name' => 'your_database_name',
    'db_user' => 'your_database_user',
    'db_pass' => 'your_database_password',

    // ── Site base URL (no trailing slash) ─────────────────────────────────────
    // Used to build public URLs for uploaded images
    'base_url' => 'https://yoursite.com',

    // ── Upload directory (absolute path, writable by web server) ──────────────
    'upload_dir'  => __DIR__ . '/uploads/',

    // ── Upload public path (relative to base_url) ─────────────────────────────
    'upload_path' => '/uploads/',

    // ── Contact form e-mail recipient ─────────────────────────────────────────
    // Overrides the site_settings value; leave empty to use site_settings
    'contact_email' => '',

    // ── SMTP (leave all empty to fall back to PHP mail()) ─────────────────────
    // Hostinger example: host=smtp.hostinger.com, port=465, encryption=ssl
    // Gmail example:     host=smtp.gmail.com, port=587, encryption=tls
    'smtp_host'       => '',          // e.g. 'smtp.hostinger.com'
    'smtp_port'       => 465,         // 465 (SSL) or 587 (TLS/STARTTLS)
    'smtp_encryption' => 'ssl',       // 'ssl' or 'tls'
    'smtp_user'       => '',          // full e-mail: contato@seudominio.com.br
    'smtp_pass'       => '',          // password for that mailbox
    'smtp_from'       => '',          // sender address (usually same as smtp_user)
    'smtp_from_name'  => 'Site',      // name shown in the From field

    // ── App secret (used for extra token signing if needed in the future) ─────
    // Generate with: php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'change_me_to_a_random_64_char_hex_string',
];
