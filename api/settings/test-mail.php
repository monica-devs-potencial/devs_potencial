<?php
/**
 * POST /api/settings/test-mail.php — Admin-only endpoint.
 * Sends a test e-mail using the current SMTP/mail config and returns
 * success or the exact error from PHPMailer so you can diagnose issues.
 *
 * Body: { "to": "you@example.com" }   (optional — defaults to smtp_from)
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/mailer.php';
sendSecurityHeaders(true);

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

$b  = jsonInput();
$to = trim((string)($b['to'] ?? ''));

// If no recipient supplied, fall back to the configured from address
if ($to === '') {
    $to = trim((string)($config['smtp_from'] ?? $config['smtp_user'] ?? ''));
}

if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    fail('Forneça um endereço de e-mail válido no campo "to".');
}

// Summarise what config is being used (never expose password)
$smtpInfo = [
    'smtp_host'       => $config['smtp_host']       ?? '(não definido)',
    'smtp_port'       => $config['smtp_port']        ?? '(não definido)',
    'smtp_encryption' => $config['smtp_encryption']  ?? '(não definido)',
    'smtp_user'       => $config['smtp_user']        ?? '(não definido)',
    'smtp_from'       => $config['smtp_from']        ?? '(não definido)',
    'smtp_from_name'  => $config['smtp_from_name']   ?? '(não definido)',
];

$subject = 'Teste de e-mail — ' . date('d/m/Y H:i:s');
$body    = "Este é um e-mail de teste enviado pelo painel admin.\n\n"
         . "Se você recebeu esta mensagem, o envio de e-mail está funcionando!\n\n"
         . "Configuração usada:\n"
         . "  Host: "       . $smtpInfo['smtp_host']       . "\n"
         . "  Porta: "      . $smtpInfo['smtp_port']       . "\n"
         . "  Criptografia: ". $smtpInfo['smtp_encryption'] . "\n"
         . "  Usuário: "    . $smtpInfo['smtp_user']       . "\n"
         . "  De: "         . $smtpInfo['smtp_from']       . "\n\n"
         . "Data: " . date('d/m/Y H:i:s') . "\n";

$ok = sendMail($config, $to, $subject, $body);

global $lastMailError;

if ($ok) {
    ok([
        'message' => "E-mail de teste enviado para {$to} com sucesso!",
        'config'  => $smtpInfo,
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'ok'     => false,
        'error'  => 'Falha ao enviar e-mail.',
        'detail' => $lastMailError ?: 'Sem detalhes disponíveis. Verifique o error_log do servidor.',
        'config' => $smtpInfo,
    ]);
    exit;
}
