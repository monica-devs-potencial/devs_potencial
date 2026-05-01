<?php
/**
 * servicos.php — Public services page.
 * Loads site settings from DB for dynamic name, logo and footer.
 */
declare(strict_types=1);
require_once __DIR__ . '/app/db.php';
sendSecurityHeaders();

// ── Default values (shown when DB is not yet configured) ──────────────────────
// These are example/fallback values; the DB values loaded below take priority.
$siteName              = 'Mario & Luigi';
$footerText            = '© ' . date('Y') . ' Mario & Luigi Encanadores. Todos os direitos reservados.';
$logoUrl               = '';
$whatsNum              = '5521984158857'; // fallback — override via site_settings in DB
$whatsMsg              = '';
$servicosHeroTitle       = 'Serviços & Planos';
$servicosHeroSubtitle    = 'Soluções completas com qualidade, rapidez e garantia.';
$servicosHeroDescription = 'Confira abaixo nossos serviços mais solicitados e opções de planos. Para orçamento final, fale com a gente no WhatsApp.';
$mercadoPagoLink         = ''; // configurar depois (ex: https://www.mercadopago.com.br/…)
$servicosSectionTitle    = 'Serviços';
$servicosSectionSubtitle = 'Atendimentos avulsos para resolver rápido.';
$planosSectionTitle      = 'Planos & Preços';
$planosSectionSubtitle   = 'Para manutenção recorrente e prioridade no atendimento.';
$aboutModalTitle         = 'Sobre';
$aboutModalText          = '';
$services                = [];
$plans                   = [];
try {
    $pdo      = getDb($config);
    $settings = getAllSiteSettings($pdo);

    $siteName              = $settings['site_name']                ?? $siteName;
    $footerText            = $settings['footer_text']              ?? $footerText;
    $logoUrl               = safeUrl($settings['logo_url']         ?? '');
    $whatsNum              = $settings['whatsapp_number']          ?? $whatsNum;
    $whatsMsg              = $settings['whatsapp_message']         ?? '';
    $servicosHeroTitle       = $settings['servicos_hero_title']       ?? $servicosHeroTitle;
    $servicosHeroSubtitle    = $settings['servicos_hero_subtitle']    ?? $servicosHeroSubtitle;
    $servicosHeroDescription = $settings['servicos_hero_description'] ?? $servicosHeroDescription;
    $mercadoPagoLink         = safeUrl($settings['mercadopago_checkout_url'] ?? '');
    $servicosSectionTitle    = $settings['servicos_section_title']    ?? $servicosSectionTitle;
    $servicosSectionSubtitle = $settings['servicos_section_subtitle'] ?? $servicosSectionSubtitle;
    $planosSectionTitle      = $settings['planos_section_title']      ?? $planosSectionTitle;
    $planosSectionSubtitle   = $settings['planos_section_subtitle']   ?? $planosSectionSubtitle;
    $aboutModalTitle         = $settings['about_modal_title']         ?? $aboutModalTitle;
    $aboutModalText          = $settings['about_modal_text']          ?? ($settings['about_text'] ?? $aboutModalText);

    $services = $pdo->query(
        'SELECT id, title, description, price_text, badge_text, badge_color,
                whatsapp_link, image_url
         FROM services
         WHERE active = 1
         ORDER BY sort_order ASC, id ASC'
    )->fetchAll();

    $plans = $pdo->query(
        'SELECT id, title, description, price_text, features_json, featured,
                badge_text, whatsapp_link, image_url
         FROM plans
         WHERE active = 1
         ORDER BY sort_order ASC, id ASC'
    )->fetchAll();

    foreach ($plans as &$plan) {
        $plan['features'] = json_decode($plan['features_json'] ?? '[]', true) ?: [];
    }
    unset($plan);
} catch (\Throwable $e) {
    // Fall through with defaults — DB not configured yet
}

$whatsNum  = preg_replace('/[^0-9]/', '', (string)$whatsNum);
$whatsLink = 'https://wa.me/' . $whatsNum;

$whatsMsg = trim((string)$whatsMsg);
if ($whatsMsg !== '') {
    $whatsLink .= '?text=' . rawurlencode($whatsMsg);
}
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
  <title>Serviços &amp; Planos — <?= e($siteName) ?></title>
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
          <li class="nav-item"><a href="index.php" class="nav-link">Início</a></li>
          <li class="nav-item"><a href="servicos.php" class="nav-link">Serviços</a></li>
          <li class="nav-item"><a href="#planos" class="nav-link">Planos</a></li>
          <li class="nav-item"><a href="#pix" class="nav-link">PIX</a></li>
          <li class="nav-item"><a href="#contato" class="nav-link">Contato</a></li>
        </ul>
        <button class="btn-contato-nav" onclick="cliqueiNoBotao()">Fale Conosco</button>
      </nav>
    </div>
  </header>

  <div class="caixa-mae">
    <div class="caixa-video-mario" id="inicio">
      <video src="video.mp4" autoplay muted loop playsinline></video>
      <div class="mascara-video"></div>
    </div>

    <main class="pagina-servicos" aria-label="Página de serviços">
      
      
      <section class="servicos-hero">
        <h1><?= e($servicosHeroTitle) ?></h1>
        <p class="subtitle"><?= e($servicosHeroSubtitle) ?></p>
        <p class="description">
          <?= e($servicosHeroDescription) ?>
        </p>
      </section>

      <!-- Grid: Serviços -->
      <section class="cards-section" id="servicos">
        <div class="cards-section-header">
          <h2><?= e($servicosSectionTitle) ?></h2>
          <p class="cards-subtitle"><?= e($servicosSectionSubtitle) ?></p>
          <div class="cards-section-actions">
            <a class="card-cta" target="_blank" rel="noopener noreferrer" href="<?= e($whatsLink) ?>">Contratar no WhatsApp</a>
            <?php if ($mercadoPagoLink !== ''): ?>
              <a class="card-cta card-cta--mp" target="_blank" rel="noopener noreferrer" href="<?= e($mercadoPagoLink) ?>">Pagar com Mercado Pago</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="cards-grid">
          <?php
          $allowedBadgeColors = ['blue' => true, 'yellow' => true];
          foreach ($services as $s):
            $badgeClass = isset($allowedBadgeColors[$s['badge_color']]) ? ' ' . $s['badge_color'] : '';
            $sWhats     = ($s['whatsapp_link'] !== '') ? e($s['whatsapp_link']) : e($whatsLink);
          ?>
          <article class="service-card">
            <?php if ($s['badge_text'] !== ''): ?>
              <div class="card-badge<?= $badgeClass ?>"><?= e($s['badge_text']) ?></div>
            <?php endif; ?>
            <?php if ($s['image_url'] !== ''): ?>
              <div class="card-img-wrapper">
                <img src="<?= e($s['image_url']) ?>" alt="<?= e($s['title']) ?>" class="card-img" loading="lazy" />
              </div>
            <?php endif; ?>
            <div class="card-body">
              <h3><?= e($s['title']) ?></h3>
              <p><?= e($s['description']) ?></p>
            </div>
            <div class="card-footer">
              <?php if ($s['price_text'] !== ''): ?>
                <div class="card-price">A partir de <strong><?= e($s['price_text']) ?></strong></div>
              <?php endif; ?>
              <a class="card-cta" target="_blank" rel="noopener noreferrer" href="<?= $sWhats ?>">Contratar no WhatsApp</a>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($services)): ?>
            <p class="cards-empty">Nenhum serviço cadastrado ainda.</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- Grid: Planos -->
      <section class="cards-section" id="planos">
        <div class="cards-section-header">
          <h2><?= e($planosSectionTitle) ?></h2>
          <p class="cards-subtitle"><?= e($planosSectionSubtitle) ?></p>
        </div>

        <div class="cards-grid">
          <?php foreach ($plans as $p):
            $featuredClass = $p['featured'] ? ' featured' : '';
            $pWhats        = ($p['whatsapp_link'] !== '') ? e($p['whatsapp_link']) : e($whatsLink);
            $features      = $p['features'] ?? [];
          ?>
          <article class="plan-card<?= $featuredClass ?>">
            <?php if ($p['badge_text'] !== ''): ?>
              <div class="card-badge"><?= e($p['badge_text']) ?></div>
            <?php endif; ?>
            <?php if ($p['image_url'] !== ''): ?>
              <div class="card-img-wrapper">
                <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['title']) ?>" class="card-img" loading="lazy" />
              </div>
            <?php endif; ?>
            <div class="card-body">
              <h3><?= e($p['title']) ?></h3>
              <p><?= e($p['description']) ?></p>
              <?php if (!empty($features)): ?>
                <ul>
                  <?php foreach ($features as $f): ?>
                    <li><?= e($f) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
            <div class="card-footer">
              <?php if ($p['price_text'] !== ''): ?>
                <div class="card-price"><strong><?= e($p['price_text']) ?></strong></div>
              <?php endif; ?>
              <?php if ($mercadoPagoLink !== ''): ?>
                <a class="card-cta card-cta--mp" target="_blank" rel="noopener noreferrer" href="<?= e($mercadoPagoLink) ?>">Pagar com Mercado Pago</a>
              <?php endif; ?>
              <a class="card-cta" target="_blank" rel="noopener noreferrer" href="<?= $pWhats ?>">Contratar no WhatsApp</a>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (empty($plans)): ?>
            <p class="cards-empty">Nenhum plano cadastrado ainda.</p>
          <?php endif; ?>
        </div>
      </section>

      <!-- PIX -->
      <section class="pix-section" id="pix">
        <div class="pix-card">
          <div class="pix-header">
            <h2>Pagamento via PIX</h2>
            <p class="carousel-subtitle">Gere a chave e copie em 1 clique.</p>
          </div>

          <button class="pix-btn" type="button" id="btnGerarPix" aria-haspopup="dialog" aria-controls="pixModal">
            <span class="pix-btn-icon"><i class="fa-solid fa-bolt"></i></span>
            <span class="pix-btn-text">Gerar chave PIX</span>
            <span class="pix-btn-glow" aria-hidden="true"></span>
          </button>

          <p class="pix-hint" id="pixHintText">Chave PIX (telefone): <strong>+55<?= e($whatsNum) ?></strong></p>
        </div>
      </section>
    </main>

    <!-- Formulário de Contato -->
    <form class="fale-conosco" action="api/contact/save.php" method="POST" id="contato">
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
      <input placeholder="Seu telefone" type="tel" name="telefone" required />
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

  <!-- Modal PIX -->
  <div class="pix-modal" id="pixModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pixModalTitle">
    <div class="pix-modal-content" role="document">
      <div class="pix-modal-top">
        <h3 id="pixModalTitle">Chave PIX gerada</h3>
        <button class="pix-close" type="button" id="btnFecharPix" aria-label="Fechar">×</button>
      </div>

      <p class="pix-desc">Copie a chave abaixo e cole no app do seu banco:</p>

      <div class="pix-key-box">
        <code id="pixKey">+55<?= e($whatsNum) ?></code>
        <button class="pix-copy" type="button" id="btnCopiarPix">
          <i class="fa-regular fa-copy"></i> Copiar
        </button>
      </div>

      <div class="pix-actions">
        <a class="pix-whats" id="pixWhatsLink" target="_blank" rel="noopener noreferrer" href="<?= e($whatsLink) ?>">
          <i class="fa-brands fa-whatsapp"></i> Confirmar no WhatsApp
        </a>
      </div>

      <div class="pix-toast" id="pixToast" aria-live="polite" aria-atomic="true"></div>
    </div>
  </div>

  <!-- Links discretos -->
  <div style="text-align:center; padding:12px 0; font-size:11px; opacity:.28;">
    <span><?= e($footerText) ?></span>
    <span style="margin:0 8px;">•</span>
    <a href="admin/login.php" style="color:inherit; text-decoration:none;">admin</a>
  </div>

  <script src="script.js" defer></script>
  <script>
    (function () {
      var API_BASE = 'api';

      function esc(str) {
        return String(str == null ? '' : str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      function loadPix() {
        return fetch(API_BASE + '/pix/get.php')
          .then(function(r) { return r.json(); })
          .then(function(json) {
            if (!json.ok) return;
            var d = json.data;
            if (d.pix_key) {
              var keyEl = document.getElementById('pixKey');
              if (keyEl) keyEl.textContent = d.pix_key;
            }
            if (d.pix_hint_text) {
              var hintEl = document.getElementById('pixHintText');
              if (hintEl) hintEl.textContent = d.pix_hint_text;
            }
            if (d.whatsapp_link) {
              var safeLink = esc(d.whatsapp_link);
              var whatsEl = document.getElementById('pixWhatsLink');
              if (whatsEl) whatsEl.href = safeLink;
              var floatEl = document.getElementById('whatsappLink');
              if (floatEl) floatEl.href = safeLink;
            }
          })
          .catch(function() { /* keep fallback */ });
      }

      document.addEventListener('DOMContentLoaded', function() {
        loadPix();

        /* ── Contact form AJAX submission ────────────────────────────────────── */
        var form = document.getElementById('contato');
        if (form) {
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
        }
      });
    })();
  </script>
    <!-- Modal Sobre -->
<div class="pix-modal" id="sobreModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sobreModalTitle">
  <div class="pix-modal-content" role="document">
    <div class="pix-modal-top">
      <h3 id="sobreModalTitle"><?= e($aboutModalTitle) ?></h3>
      <button class="pix-close" type="button" id="btnFecharSobre" aria-label="Fechar">×</button>
    </div>

    <p class="pix-desc"><?= e($aboutModalText) ?></p>
  </div>
</div>
</body>
</html>
