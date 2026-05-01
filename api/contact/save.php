<?php
/**
 * POST /api/contact/save.php — Public endpoint.
 * Saves the contact form submission to the database and sends an e-mail.
 * Body: { "nome": "...", "telefone": "...", "mensagem": "..." }
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/mailer.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}

$b        = jsonInput();
$nome     = trim((string)($b['nome']     ?? ''));
$telefone = trim((string)($b['telefone'] ?? ''));
$mensagem = trim((string)($b['mensagem'] ?? ''));

// ── Server-side validation ────────────────────────────────────────────────────
if ($nome === '')     { fail('O campo nome é obrigatório.'); }
if ($telefone === '') { fail('O campo telefone é obrigatório.'); }
if ($mensagem === '') { fail('O campo mensagem é obrigatório.'); }

if (mb_strlen($nome) > 120)     { fail('Nome muito longo.'); }
if (mb_strlen($telefone) > 30)  { fail('Telefone muito longo.'); }
if (mb_strlen($mensagem) > 5000) { fail('Mensagem muito longa.'); }

// Basic phone validation (allow digits, spaces, +, -, (, ))
if (!preg_match('/^[\d\s\+\-\(\)]{6,30}$/', $telefone)) {
    fail('Telefone inválido.');
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = trim(explode(',', $ip)[0]);

// ── Save to database ──────────────────────────────────────────────────────────
$pdo  = getDb($config);
$stmt = $pdo->prepare(
    'INSERT INTO contacts (nome, telefone, mensagem, ip_address) VALUES (?, ?, ?, ?)'
);
$stmt->execute([$nome, $telefone, $mensagem, $ip]);

// ── Send e-mail ───────────────────────────────────────────────────────────────
$toEmail = '';

// Priority: config.php value → site_settings value
if (!empty($config['contact_email'])) {
    $toEmail = $config['contact_email'];
} else {
    $toEmail = getSiteSetting($pdo, 'contact_email', '');
}

if ($toEmail !== '' && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    $subject = 'Nova mensagem de contato — ' . $nome;
    $body    = "Você recebeu uma nova mensagem pelo formulário do site.\n\n"
             . "Nome: {$nome}\n"
             . "Telefone: {$telefone}\n\n"
             . "Mensagem:\n{$mensagem}\n\n"
             . "---\n"
             . "IP: {$ip}\n"
             . "Data: " . date('d/m/Y H:i:s') . "\n";

    sendMail($config, $toEmail, $subject, $body);
}

ok(['message' => 'Mensagem enviada com sucesso!']);
