<?php
/**
 * /admin/setup.php — First-admin bootstrap.
 *
 * This page is ONLY accessible when no admin user exists in the database.
 * Once the first admin is created it redirects to login and returns 403 on
 * every subsequent request — preventing a second setup without a DB wipe.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders();

// ── Check if setup is allowed ─────────────────────────────────────────────────
try {
    $pdo   = getDb($config);
    $count = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
} catch (\Throwable $e) {
    $count = 0; // DB might not be set up yet; allow setup to show the form
}

if ($count > 0) {
    // Admin already exists — block access
    http_response_code(403);
    ?><!DOCTYPE html>
<html lang="pt-br">
<head><meta charset="UTF-8"><title>Setup bloqueado</title>
<style>body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.box{text-align:center;padding:2rem}a{color:#38bdf8}</style>
</head>
<body><div class="box">
  <h1>⛔ Setup bloqueado</h1>
  <p>O painel já foi configurado. <a href="login.php">Ir para o login</a>.</p>
</div></body></html><?php
    exit;
}

// ── Handle POST ───────────────────────────────────────────────────────────────
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $confirm  = $_POST['confirm']       ?? '';

    if ($username === '' || $password === '') {
        $error = 'Usuário e senha são obrigatórios.';
    } elseif (strlen($username) < 3) {
        $error = 'Usuário deve ter ao menos 3 caracteres.';
    } elseif (strlen($password) < 8) {
        $error = 'Senha deve ter ao menos 8 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'As senhas não coincidem.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->execute([$username, $email, $hash]);
            $success = true;
        } catch (\Throwable $e) {
            $error = 'Erro ao criar usuário. Verifique as credenciais do banco.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuração inicial — Admin</title>
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
      padding: 2.5rem 2rem; width: 100%; max-width: 420px;
      box-shadow: 0 8px 32px rgba(0,0,0,.5);
    }
    h1 { font-size: 1.4rem; margin-bottom: .4rem; text-align: center; }
    .subtitle { font-size: .85rem; color: #94a3b8; text-align: center; margin-bottom: 1.8rem; }
    label { display: block; margin-bottom: .3rem; font-size: .83rem; color: #94a3b8; }
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
    .notice {
      background: #1d4ed8; border-radius: 8px;
      padding: .75rem 1rem; font-size: .82rem;
      margin-bottom: 1.5rem; line-height: 1.5;
    }
    .err  { color: #f87171; font-size: .85rem; margin-top: .5rem; text-align: center; }
    .ok   {
      background: #14532d; border-radius: 8px;
      padding: 1rem; text-align: center; font-size: .95rem;
    }
    .ok a { color: #4ade80; font-weight: 600; }
  </style>
</head>
<body>
<div class="card">
  <h1>🔧 Configuração Inicial</h1>
  <p class="subtitle">Crie o primeiro usuário administrador do painel.</p>

  <?php if ($success): ?>
    <div class="ok">
      ✅ Admin criado com sucesso!<br>
      <a href="login.php">Ir para o login →</a>
    </div>
  <?php else: ?>
    <div class="notice">
      ⚠️ Esta página só está disponível enquanto não houver nenhum admin cadastrado.
      Após criar o primeiro usuário ela ficará inacessível.
    </div>

    <form method="POST" action="setup.php">
      <label for="username">Usuário *</label>
      <input type="text" id="username" name="username"
             autocomplete="username" minlength="3" required
             value="<?= e($_POST['username'] ?? '') ?>">

      <label for="email">E-mail (opcional)</label>
      <input type="email" id="email" name="email"
             autocomplete="email"
             value="<?= e($_POST['email'] ?? '') ?>">

      <label for="password">Senha * (mín. 8 caracteres)</label>
      <input type="password" id="password" name="password"
             autocomplete="new-password" minlength="8" required>

      <label for="confirm">Confirmar senha *</label>
      <input type="password" id="confirm" name="confirm"
             autocomplete="new-password" minlength="8" required>

      <?php if ($error !== ''): ?>
        <p class="err"><?= e($error) ?></p>
      <?php endif; ?>

      <button type="submit">Criar administrador</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
