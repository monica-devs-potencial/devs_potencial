<?php
/**
 * POST /api/upload.php — Requires authentication + CSRF.
 * Accepts a multipart/form-data file field named "image".
 * Validates MIME type via finfo, enforces 2 MB limit (per requirements), randomises filename.
 * Returns: { "ok": true, "data": { "url": "https://...", "filename": "abc.jpg" } }
 */
declare(strict_types=1);
define('API_ENDPOINT', true);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Método não permitido.', 405);
}
requireAuth();

// CSRF for multipart: comes as POST field
$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals((string)csrfToken(), (string)$token)) {
    fail('Token CSRF inválido.', 403);
}

if (!isset($_FILES['image'])) {
    fail('Nenhum arquivo enviado.');
}

$file  = $_FILES['image'];
$error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

if ($error !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo excede o limite do servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo excede o limite do formulário.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto.',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário ausente.',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar no disco.',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão.',
    ];
    fail($messages[$error] ?? 'Erro desconhecido no upload.');
}

// ── Size limit: 2 MB ──────────────────────────────────────────────────────────
$maxBytes = 2 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    fail('Imagem deve ter no máximo 2 MB.');
}

// ── MIME validation via finfo (never trust browser-supplied type) ─────────────
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!isset($allowed[$mimeType])) {
    fail('Tipo de arquivo não permitido. Use JPEG, PNG ou WebP.');
}

// ── Save to uploads/ ──────────────────────────────────────────────────────────
$uploadDir = $config['upload_dir'];
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        fail('Não foi possível criar o diretório de uploads.', 500);
    }
}

// Copy the .htaccess protection to uploads/ if it doesn't exist yet
$htaccessDest = rtrim($uploadDir, '/') . '/.htaccess';
if (!file_exists($htaccessDest)) {
    $htaccessSrc = __DIR__ . '/../uploads/.htaccess';
    if (file_exists($htaccessSrc)) {
        copy($htaccessSrc, $htaccessDest);
    }
}

$ext      = $allowed[$mimeType];
$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$dest     = rtrim($uploadDir, '/') . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    fail('Falha ao salvar o arquivo.', 500);
}

$url = rtrim($config['base_url'], '/') . $config['upload_path'] . $filename;

ok(['url' => $url, 'filename' => $filename]);
