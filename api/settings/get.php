<?php
/**
 * GET /api/settings/get.php — Public endpoint. Returns site-wide settings.
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
sendSecurityHeaders(true);

$pdo      = getDb($config);
$settings = getAllSiteSettings($pdo);

// Only expose public-safe keys
$public = [
    'site_name'              => $settings['site_name']              ?? 'Mario & Luigi',
    'footer_text'            => $settings['footer_text']            ?? '',
    'logo_url'               => $settings['logo_url']               ?? '',
    'whatsapp_number'        => $settings['whatsapp_number']        ?? '',
    'whatsapp_message'       => $settings['whatsapp_message']       ?? '',
    'cta_label'              => $settings['cta_label']              ?? 'Contratar no WhatsApp',
    'cta_bg_color'           => $settings['cta_bg_color']           ?? '#25d366',
    'cta_text_color'         => $settings['cta_text_color']         ?? '#ffffff',
    'cta_border_color'       => $settings['cta_border_color']       ?? '',
    'cta_hover_bg_color'     => $settings['cta_hover_bg_color']     ?? '#1aae52',
    'mercadopago_checkout_url' => $settings['mercadopago_checkout_url'] ?? '',
    'bank_links'             => $settings['bank_links']             ?? '[]',
    'about_text'             => $settings['about_text']             ?? '',
    'servicos_hero_title'       => $settings['servicos_hero_title']       ?? 'Serviços & Planos',
    'servicos_hero_subtitle'    => $settings['servicos_hero_subtitle']    ?? 'Soluções completas com qualidade, rapidez e garantia.',
    'servicos_hero_description' => $settings['servicos_hero_description'] ?? 'Confira abaixo nossos serviços mais solicitados e opções de planos. Para orçamento final, fale com a gente no WhatsApp.',
    'servicos_section_title'    => $settings['servicos_section_title']    ?? 'Serviços',
    'servicos_section_subtitle' => $settings['servicos_section_subtitle'] ?? 'Atendimentos avulsos para resolver rápido.',
    'planos_section_title'      => $settings['planos_section_title']      ?? 'Planos & Preços',
    'planos_section_subtitle'   => $settings['planos_section_subtitle']   ?? 'Para manutenção recorrente e prioridade no atendimento.',
];

ok($public);
