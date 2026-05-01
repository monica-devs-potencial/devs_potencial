<?php
/**
 * /admin/login.php — Admin login page.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders();

// Already logged in → redirect to panel
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
  <title>Admin — Login</title>
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
    h1 { font-size: 1.5rem; margin-bottom: 1.5rem; text-align: center; }
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
    .err { color: #f87171; font-size: .85rem; margin-top: .5rem; text-align: center; min-height: 1.2em; }
    .forgot-link { display: block; text-align: center; margin-top: .9rem; font-size: .82rem; color: #94a3b8; text-decoration: none; }
    .forgot-link:hover { color: #38bdf8; }
  </style>
</head>
<body>
<div class="card">
  <h1>🔑 Painel Admin</h1>
  <form id="loginForm" novalidate>
    <label for="username">Usuário</label>
    <input type="text" id="username" name="username"
           autocomplete="username" required autofocus>

    <label for="password">Senha</label>
    <input type="password" id="password" name="password"
           autocomplete="current-password" required>

    <button type="submit" id="btnSubmit">Entrar</button>
    <p class="err" id="msg" aria-live="polite"></p>
  </form>
  <a href="forgot-password.php" class="forgot-link">Recuperar senha</a>
</div>

<script>
  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    const msg = document.getElementById('msg');
    msg.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Aguarde…';

    const body = {
      username: document.getElementById('username').value.trim(),
      password: document.getElementById('password').value,
    };

    try {
      const res  = await fetch('../api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
        credentials: 'same-origin',
      });
      const data = await res.json();

      if (data.ok) {
        window.location.href = 'index.php';
      } else {
        msg.textContent = data.error || 'Usuário ou senha inválidos.';
        btn.disabled = false;
        btn.textContent = 'Entrar';
      }
    } catch {
      msg.textContent = 'Erro de conexão. Verifique o servidor.';
      btn.disabled = false;
      btn.textContent = 'Entrar';
    }
  });
</script>
</body>
</html>
