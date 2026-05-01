<?php
/**
 * /admin/forgot-password.php — Enter username or e-mail to request password reset.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Recuperar senha</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #0f172a; color: #e2e8f0;
      min-height: 100vh; display: flex;
      align-items: center; justify-content: center; padding: 1rem;
    }
    .card {
      background: #1e293b; border-radius: 14px;
      padding: 2.5rem 2rem; width: 100%; max-width: 380px;
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
    }
    h1 { font-size: 1.5rem; margin-bottom: .5rem; text-align: center; }
    .subtitle { font-size: .85rem; color: #94a3b8; text-align: center; margin-bottom: 1.5rem; }
    label { display: block; margin-bottom: .3rem; font-size: .85rem; color: #94a3b8; }
    input {
      width: 100%; padding: .65rem .9rem;
      border: 1px solid #334155; border-radius: 8px;
      background: #0f172a; color: #e2e8f0;
      font-size: 1rem; margin-bottom: 1.1rem;
      outline: none; transition: border-color .2s;
    }
    input:focus { border-color: #38bdf8; }
    button[type=submit] {
      width: 100%; padding: .75rem;
      background: #38bdf8; color: #0f172a;
      border: none; border-radius: 8px;
      font-size: 1rem; font-weight: 700;
      cursor: pointer; transition: background .2s;
    }
    button[type=submit]:hover { background: #7dd3fc; }
    button:disabled { opacity: .6; cursor: not-allowed; }
    .msg { font-size: .85rem; margin-top: .75rem; text-align: center; min-height: 1.2em; }
    .msg.ok  { color: #4ade80; }
    .msg.err { color: #f87171; }
    .back-link { display: block; text-align: center; margin-top: 1.2rem; font-size: .85rem; color: #38bdf8; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="card">
  <h1>🔒 Recuperar senha</h1>
  <p class="subtitle">Informe o e-mail cadastrado na sua conta.<br>Um link de redefinição será enviado.</p>
  <form id="forgotForm" novalidate>
    <label for="identifier">E-mail</label>
    <input type="email" id="identifier" name="identifier"
           autocomplete="email" required autofocus>
    <button type="submit" id="btnSubmit">Enviar link</button>
    <p class="msg" id="msg" aria-live="polite"></p>
  </form>
  <a href="login.php" class="back-link">← Voltar ao login</a>
</div>

<script>
  document.getElementById('forgotForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const msg = document.getElementById('msg');
    msg.textContent = '';
    msg.className = 'msg';
    btn.disabled = true;
    btn.textContent = 'Aguarde…';

    const body = { email: document.getElementById('identifier').value.trim() };

    try {
      const res  = await fetch('../api/auth/forgot-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
        credentials: 'same-origin',
      });
      const data = await res.json();
      msg.textContent = data.data?.message || 'Se o usuário existir, um e-mail foi enviado.';
      msg.classList.add('ok');
      btn.textContent = 'Enviado';
    } catch {
      msg.textContent = 'Erro de conexão. Verifique o servidor.';
      msg.classList.add('err');
      btn.disabled = false;
      btn.textContent = 'Enviar link';
    }
  });
</script>
</body>
</html>
