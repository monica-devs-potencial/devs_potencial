<?php
/**
 * POST /api/settings/update.php — Requires authentication + CSRF.
 * Accepts a JSON body with one or more setting keys to update.
 * Allowed keys: site_name, footer_text, logo_url, whatsapp_number, whatsapp_message, contact_email
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

$b = jsonInput();

$allowed = [
    'site_name', 'footer_text', 'logo_url', 'whatsapp_number', 'whatsapp_message', 'contact_email',
    'cta_label', 'cta_bg_color', 'cta_text_color', 'cta_border_color', 'cta_hover_bg_color',
    'mercadopago_checkout_url', 'bank_links', 'about_text',
    'servicos_hero_title', 'servicos_hero_subtitle', 'servicos_hero_description',
    'servicos_section_title', 'servicos_section_subtitle',
    'planos_section_title', 'planos_section_subtitle',
];
$updated = [];

$pdo  = getDb($config);
$stmt = $pdo->prepare(
    "INSERT INTO site_settings (`key`, `value`) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
);

foreach ($allowed as $key) {
    if (!array_key_exists($key, $b)) {
        continue;
    }

    $value = trim((string)$b[$key]);

    // Extra validation for specific keys
    if ($key === 'contact_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        fail('E-mail de contato inválido.');
    }
    if ($key === 'logo_url' && $value !== '') {
        $value = safeUrl($value);
    }
    if ($key === 'mercadopago_checkout_url' && $value !== '') {
        $value = safeUrl($value);
        if ($value === '') {
            fail('URL do Mercado Pago inválida (use http:// ou https://).');
        }
    }
    if (in_array($key, ['cta_bg_color', 'cta_text_color', 'cta_border_color', 'cta_hover_bg_color'], true) && $value !== '') {
        if (!preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
            fail("Cor inválida para $key. Use formato hex, ex: #25d366.");
        }
    }
    if ($key === 'bank_links' && $value !== '') {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            fail('bank_links deve ser um JSON array válido.');
        }
        // Sanitize URLs inside bank_links
        foreach ($decoded as &$lnk) {
            if (isset($lnk['url'])) {
                $lnk['url'] = safeUrl((string)$lnk['url']);
            }
        }
        unset($lnk);
        $value = json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
    if ($key === 'site_name' && $value === '') {
        fail('O nome do site não pode ficar em branco.');
    }
    if ($key === 'whatsapp_message' && mb_strlen($value) > 500) {
        fail('Mensagem do WhatsApp muito longa (máx. 500 caracteres).');
    }

    $stmt->execute([$key, $value]);
    $updated[] = $key;
}

ok(['updated' => $updated]);
