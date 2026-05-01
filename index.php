<?php
/**
 * index.php — Public home page.
 * Loads site settings from DB to render dynamic name, logo and footer.
 */
declare(strict_types=1);
require_once __DIR__ . '/app/db.php';
sendSecurityHeaders();

// ── Default values (shown when DB is not yet configured) ─────────────────��────
// These are example/fallback values; the DB values loaded below take priority.
$siteName         = 'Mario & Luigi';
$footerText       = '© ' . date('Y') . ' Mario & Luigi Encanadores. Todos os direitos reservados.';
$logoUrl          = '';
$whatsNum         = '5521984158857'; // fallback — override via site_settings in DB
$whatsMsg         = '';
$indexSubtitle    = 'Resolvendo Seus Problemas Com Estilo!';
$indexDescription = 'Você já se encontrou em uma situação de emergência com encanamento? Vazamentos inesperados, canos entupidos ou torneiras que não param de pingar? Não se preocupe, porque estamos aqui para salvar o dia! Apresentamos a vocês os encanadores mais famosos do Reino dos Cogumelos — Mario e Luigi!';

// Modal "Sobre" (editável via DB)
$aboutModalTitle = 'Sobre o produto';
$aboutModalText  = '📦 Funcionalidades do Site

Home — apresentação, serviços, planos, contato
Serviços & Planos — carrossel dinâmico, preços, links WhatsApp, Mercado Pago
Formulário de Contato — mensagens salvas no banco com IP registrado
PIX — chave PIX configurável pelo admin
Painel Admin — CRUD completo de serviços, planos, configurações, mensagens e upload de imagens
Configurações do Site — nome, logo, WhatsApp, cores do botão CTA, textos — tudo editável pelo admin, etc.

 © 2026 DEVS_POTENCIAL. Todos os direitos reservados. Autor e desenvolvedor: Daniel Raymundo RG-115781254';

try {
    $pdo      = getDb($config);
    $settings = getAllSiteSettings($pdo);

    $siteName         = $settings['site_name']           ?? $siteName;
    $footerText       = $settings['footer_text']         ?? $footerText;
    $logoUrl          = safeUrl($settings['logo_url']    ?? '');
    $whatsNum         = $settings['whatsapp_number']     ?? $whatsNum;
    $whatsMsg         = $settings['whatsapp_message']    ?? '';
    $indexSubtitle    = $settings['index_subtitle']      ?? $indexSubtitle;
    $indexDescription = $settings['index_description']   ?? $indexDescription;
    $aboutModalTitle  = $settings['about_modal_title']   ?? $aboutModalTitle;
    $aboutModalText   = $settings['about_modal_text']    ?? $aboutModalText;
} catch (\Throwable $e) {
    // Fall through with defaults — DB not configured yet
}

$whatsNum  = preg_replace('/[^0-9]/', '', (string)$whatsNum);
$whatsLink = 'https://wa.me/' . $whatsNum;

$whatsMsg = trim((string)$whatsMsg);
if ($whatsMsg !== '') {
    $whatsLink .= '?text=' . rawurlencode($whatsMsg);
}

$pageTitle      = e($siteName) . ' — Encanadores Profissionais';
$showPlanosLink = false;
define('PARTIAL_INCLUDED', true);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Mukta:wght@300;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <title><?= $pageTitle ?></title>
</head>
<body>
  <header class="navbar">
    <div class="navbar-container">
      <div class="navbar-logo">
        <?php if ($logoUrl !== ''): ?>
          <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="logo-img" />
        <?php else: ?>
          <img src="logo-header.png" alt="<?= e($siteName) ?>" class="logo-img" />
        <?php endif; ?>
        <span class="logo-text"><?= e($siteName) ?></span>
      </div>

      <div class="menu-toggle" id="menuToggle" aria-expanded="false" aria-controls="navMenu" role="button" tabindex="0">
        <span class="hamburger"></span>
        <span class="hamburger"></span>
        <span class="hamburger"></span>
      </div>

      <nav class="nav-menu" id="navMenu" aria-label="Navegação principal">
        <ul class="nav-list">
          <li class="nav-item"><a href="#inicio" class="nav-link">Início</a></li>
          <li class="nav-item"><a href="servicos.php" class="nav-link">Serviços</a></li>
          <li  class="nav-item"><a href="#sobre" id="btnSobre" class="nav-link">Sobre o produto</a></li>
        </ul>
        <button class="btn-contato-nav" onclick="cliqueiNoBotao()">Fale Conosco</button>
      </nav>
    </div>
  </header>

  <div class="caixa-mae">
    <!-- Vídeo de Fundo -->
    <div class="caixa-video-mario" id="inicio">
      <video src="video.mp4" autoplay muted loop playsinline></video>
      <div class="mascara-video" onclick="sumirFormulario()"></div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="caixa-principal">
      <h1><?= e($siteName) ?></h1>
     
      
        <div class="hero" id="sobre">
        <div class="hero-texto">
          <p class="description">
            <?= e($indexDescription) ?>
          </p>

        
          <p class="subtitle"><?= e($indexSubtitle) ?></p>
    

      <div class="botoes-principais">
  <button class="enviar1" onclick="cliqueiNoBotao()">Fale conosco</button>
  <a class="enviar1" href="servicos.php" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Ver Serviços</a>
     </div>
     </div>
        <?php if ($logoUrl !== ''): ?>
        <img src="<?= e($logoUrl) ?>" alt="logo <?= e($siteName) ?>" class="logo-mario" />
        <?php else: ?>
        <img src="logo.png" alt="logo <?= e($siteName) ?>" class="logo-mario" />
        <?php endif; ?>
        </div>
        </div>
        </div>
     <!-- Formulário de Contato -->
    <form class="fale-conosco" action="api/contact/save.php" method="POST" id="formContato">
      <div class="form-header">
        <div class="navbar-logo">
          <?php if ($logoUrl !== ''): ?>
            <img src="<?= e($logoUrl) ?>" alt="<?= e($siteName) ?>" class="logo-img" />
          <?php else: ?>
            <img src="logo-header.png" alt="<?= e($siteName) ?>" class="logo-img" />
          <?php endif; ?>
          <span class="tex-form"><?= e($siteName) ?></span>
        </div>
      </div>
      <input placeholder="Seu nome" type="text" name="nome" required />
      <input  id="celular" maxlength="15" oninput="maskCelular(this)" placeholder="(00) 00000-0000" type="tel" name="telefone" required />
      <textarea placeholder="Digite seu problema aqui" name="info" required></textarea>
      <button class="enviar" type="submit">Enviar formulário</button>
    </form>

    <div class="mascara-form" onclick="sumirFormulario()"></div>

    <!-- Botão flutuante WhatsApp -->
    <a
      href="<?= e($whatsLink) ?>"
      class="whatsapp-float"
      id="whatsappLink"
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Fale conosco no WhatsApp"
      title="Fale conosco no WhatsApp"
    >
      <i class="fab fa-whatsapp" aria-hidden="true"></i>
      <span class="whatsapp-tooltip">WhatsApp</span>
    </a>
  </div>

  <!-- Links discretos -->
  <div style="text-align:center; padding:12px 0; font-size:11px; opacity:.28;">
    <span><?= e($footerText) ?></span>
    <span style="margin:0 8px;">•</span>
    <a href="admin/login.php" style="color:inherit; text-decoration:none;">admin</a>
  </div>

  <script src="script.js" defer></script>
  <script>
    /* ── Contact form AJAX submission ─────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
      var form = document.getElementById('formContato');
      if (!form) return;

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type=submit]');
        if (btn) btn.disabled = true;

        var fd = new FormData(form);
        fetch('api/contact/save.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            nome:     fd.get('nome')     || '',
            telefone: fd.get('telefone') || '',
            mensagem: fd.get('info')     || fd.get('mensagem') || ''
          })
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (json.ok) {
            form.innerHTML = '<p style="color:#4caf50;text-align:center;padding:1.5rem;">✓ Mensagem enviada! Entraremos em contato em breve.</p>';
          } else {
            if (btn) btn.disabled = false;
            alert(json.error || 'Erro ao enviar. Tente novamente.');
          }
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          alert('Erro de rede. Verifique sua conexão e tente novamente.');
        });
      });

      // ── Modal "Sobre" ────────────────��────────────────────────
      var modal = document.getElementById('sobreModal');
      var btnOpen = document.getElementById('btnSobre');
      var btnClose = document.getElementById('btnFecharSobre');

      if (modal && btnOpen && btnClose) {
        function openModal() {
          modal.classList.add('open');
          modal.setAttribute('aria-hidden', 'false');
        }
        function closeModal() {
          modal.classList.remove('open');
          modal.setAttribute('aria-hidden', 'true');
        }

        btnOpen.addEventListener('click', openModal);
        btnClose.addEventListener('click', closeModal);

        modal.addEventListener('click', function (e) {
          if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeModal();
        });
      }
    });
  </script>
  
  <script>
function maskCelular(input) {
  let value = input.value;
  // Remove tudo que não é número
  value = value.replace(/\D/g, '');
  // Formata: (11) 98888-8888
  value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
  value = value.replace(/(\d)(\d{4})$/, '$1-$2');
  input.value = value;
}
</script>
  

  <!-- Modal Sobre -->
  <div class="pix-modal" id="sobreModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sobreModalTitle">
    <div class="pix-modal-content" role="document">
      <div class="pix-modal-top">
        <h3 id="sobreModalTitle"><?= e($aboutModalTitle) ?></h3>
        <button class="pix-close" type="button" id="btnFecharSobre" aria-label="Fechar">×</button>
      </div>

      <p class="pix-desc"><?= nl2br(e($aboutModalText)) ?></p>
    </div>
  </div>
</body>
</html>