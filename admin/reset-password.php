<?php
/**
 * /admin/reset-password.php — Set a new password using a reset token.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Nova senha</title>
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
    .hint { font-size: .78rem; color: #64748b; margin-top: -.8rem; margin-bottom: .8rem; }
    #noToken { text-align: center; color: #f87171; padding: 1rem 0; }
  </style>
</head>
<body>
<div class="card">
  <h1>🔑 Nova senha</h1>
<?php if ($token === ''): ?>
  <p id="noToken">Token inválido ou ausente.<br><a href="forgot-password.php" style="color:#38bdf8">Solicitar novo link</a></p>
<?php else: ?>
  <p class="subtitle">Defina sua nova senha abaixo.</p>
  <form id="resetForm" novalidate>
    <input type="hidden" id="resetToken" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
    <label for="newPassword">Nova senha</label>
    <input type="password" id="newPassword" name="newPassword"
           autocomplete="new-password" minlength="8" required autofocus>
    <p class="hint">Mínimo de 8 caracteres.</p>
    <label for="confirmPassword">Confirmar senha</label>
    <input type="password" id="confirmPassword" name="confirmPassword"
           autocomplete="new-password" minlength="8" required>
    <button type="submit" id="btnSubmit">Salvar nova senha</button>
    <p class="msg" id="msg" aria-live="polite"></p>
  </form>
<?php endif; ?>
  <a href="login.php" class="back-link">← Voltar ao login</a>
</div>

<script>
  const form = document.getElementById('resetForm');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('btnSubmit');
      const msg = document.getElementById('msg');
      msg.textContent = '';
      msg.className = 'msg';

      const password = document.getElementById('newPassword').value;
      const confirm  = document.getElementById('confirmPassword').value;
      const token    = document.getElementById('resetToken').value;

      if (password.length < 8) {
        msg.textContent = 'A senha deve ter pelo menos 8 caracteres.';
        msg.classList.add('err');
        return;
      }
      if (password !== confirm) {
        msg.textContent = 'As senhas não coincidem.';
        msg.classList.add('err');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Aguarde…';

      try {
        const res  = await fetch('../api/auth/reset-password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ token, password }),
          credentials: 'same-origin',
        });
        const data = await res.json();

        if (data.ok) {
          msg.textContent = data.data?.message || 'Senha redefinida! Redirecionando…';
          msg.classList.add('ok');
          btn.textContent = 'Salvo!';
          setTimeout(() => { window.location.href = 'login.php'; }, 2500);
        } else {
          msg.textContent = data.error || 'Token inválido ou expirado.';
          msg.classList.add('err');
          btn.disabled = false;
          btn.textContent = 'Salvar nova senha';
        }
      } catch {
        msg.textContent = 'Erro de conexão. Verifique o servidor.';
        msg.classList.add('err');
        btn.disabled = false;
        btn.textContent = 'Salvar nova senha';
      }
    });
  }
</script>
</body>
</html>
