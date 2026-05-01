<?php
/**
 * /admin/index.php — Protected admin panel.
 */
declare(strict_types=1);
require_once __DIR__ . '/../app/db.php';
sendSecurityHeaders();

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$whatsMsg   = '';
$csrf = csrfToken();
$username = e($_SESSION['admin_username'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Painel</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      background: #0f172a;
      color: #e2e8f0;
      min-height: 100vh;
    }

    /* ── Header ──────────────────────────────────────────────── */
    .topbar {
      background: #1e293b;
      display: flex; align-items: center; justify-content: space-between;
      padding: .85rem 1.5rem;
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,.4);
    }
    .topbar h1 { font-size: 1.15rem; }
    .topbar-actions { display: flex; gap: .75rem; align-items: center; }
    #usernameDisplay { font-size: .85rem; color: #94a3b8; }
    .btn-sm {
      padding: .4rem .85rem; border: none; border-radius: 6px;
      cursor: pointer; font-size: .85rem; font-weight: 600;
      transition: background .2s;
    }
    .btn-danger   { background: #ef4444; color: #fff; }
    .btn-danger:hover { background: #dc2626; }
    .btn-primary  { background: #38bdf8; color: #0f172a; }
    .btn-primary:hover { background: #7dd3fc; }
    .btn-secondary { background: #334155; color: #e2e8f0; }
    .btn-secondary:hover { background: #475569; }
    .btn-edit     { background: #0ea5e9; color: #fff; }
    .btn-edit:hover { background: #0284c7; }
    .btn-del      { background: #ef4444; color: #fff; }
    .btn-del:hover { background: #dc2626; }
    .btn-success  { background: #22c55e; color: #fff; }
    .btn-success:hover { background: #16a34a; }

    /* ── Tabs ────────────────────────────────────────────────── */
    .tabs {
      display: flex; gap: 0;
      background: #1e293b; border-bottom: 2px solid #334155;
      padding: 0 1.5rem; overflow-x: auto;
    }
    .tab-btn {
      padding: .75rem 1.25rem; background: none; border: none;
      color: #94a3b8; font-size: .95rem; cursor: pointer;
      border-bottom: 2px solid transparent; margin-bottom: -2px;
      white-space: nowrap;
      transition: color .2s, border-color .2s;
    }
    .tab-btn.active { color: #38bdf8; border-bottom-color: #38bdf8; }

    /* ── Content ─────────────────────────────────────────────── */
    .content { padding: 1.5rem; max-width: 960px; margin: 0 auto; }
    .panel { display: none; }
    .panel.active { display: block; }

    /* ── List table ──────────────────────────────────────────── */
    .list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .list-header h2 { font-size: 1.15rem; }
    .item-list { display: flex; flex-direction: column; gap: .6rem; }
    .item-row {
      background: #1e293b; border-radius: 8px; padding: .75rem 1rem;
      display: flex; align-items: center; gap: .75rem;
    }
    .item-row img {
      width: 48px; height: 48px; object-fit: cover;
      border-radius: 6px; background: #334155; flex-shrink: 0;
    }
    .item-info { flex: 1; min-width: 0; }
    .item-title { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-sub { font-size: .8rem; color: #94a3b8; }
    .item-actions { display: flex; gap: .4rem; flex-shrink: 0; }

    /* ── Contact row ─────────────────────────────────────────── */
    .contact-row {
      background: #1e293b; border-radius: 8px; padding: .85rem 1rem;
      display: flex; gap: .75rem; flex-direction: column;
      border-left: 3px solid #334155;
    }
    .contact-row.unread { border-left-color: #38bdf8; }
    .contact-meta { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
    .contact-name { font-weight: 600; }
    .contact-phone { color: #94a3b8; font-size: .85rem; }
    .contact-date  { color: #64748b; font-size: .78rem; margin-left: auto; }
    .contact-msg   { font-size: .9rem; line-height: 1.5; white-space: pre-wrap; }
    .contact-badge {
      font-size: .7rem; font-weight: 700; padding: .2rem .5rem;
      border-radius: 999px; background: #1d4ed8; color: #bfdbfe;
    }

    /* ── Modal ───────────────────────────────────────────────── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.7); z-index: 200;
      align-items: flex-start; justify-content: center;
      padding: 2rem 1rem; overflow-y: auto;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: #1e293b; border-radius: 12px; padding: 2rem;
      width: 100%; max-width: 520px; position: relative;
    }
    .modal h3 { margin-bottom: 1.2rem; font-size: 1.1rem; }
    .modal-close {
      position: absolute; top: .75rem; right: .75rem;
      background: none; border: none; color: #94a3b8; font-size: 1.4rem; cursor: pointer;
    }
    .modal-close:hover { color: #e2e8f0; }

    /* ── Form fields ─────────────────────────────────────────── */
    label { display: block; margin-bottom: .3rem; font-size: .83rem; color: #94a3b8; }
    input[type=text], input[type=url], input[type=email], input[type=number],
    select, textarea {
      width: 100%; padding: .6rem .85rem;
      border: 1px solid #334155; border-radius: 7px;
      background: #0f172a; color: #e2e8f0;
      font-size: .95rem; margin-bottom: .9rem;
      outline: none; transition: border-color .2s; font-family: inherit;
    }
    input:focus, select:focus, textarea:focus { border-color: #38bdf8; }
    textarea { resize: vertical; min-height: 80px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .form-check { display: flex; align-items: center; gap: .4rem; margin-bottom: .9rem; }
    .form-check input[type=checkbox] { width: auto; margin: 0; }

    /* image preview */
    .img-preview {
      width: 80px; height: 80px; object-fit: cover; border-radius: 8px;
      background: #334155; display: none; margin-bottom: .6rem;
    }
    .img-preview.show { display: block; }
    .upload-area { margin-bottom: .9rem; }
    .upload-area input[type=file] { display: none; }
    .upload-label {
      display: inline-block; padding: .45rem .9rem;
      background: #334155; border-radius: 7px; cursor: pointer; font-size: .85rem;
      transition: background .2s;
    }
    .upload-label:hover { background: #475569; }
    .upload-status { font-size: .8rem; color: #94a3b8; margin-top: .3rem; }

    /* features list (plans) */
    .features-list { display: flex; flex-direction: column; gap: .4rem; margin-bottom: .5rem; }
    .feature-row { display: flex; gap: .4rem; }
    .feature-row input { margin-bottom: 0; }
    .feature-row .btn-sm { padding: .3rem .6rem; }

    /* form actions */
    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: .5rem; }

    /* settings */
    #settingsForm { max-width: 500px; }
    .settings-logo-preview {
      width: 80px; height: 80px; object-fit: contain;
      border-radius: 8px; background: #334155;
      display: none; margin-bottom: .6rem;
    }
    .settings-logo-preview.show { display: block; }

    /* toast */
    #toast {
      position: fixed; bottom: 1.5rem; right: 1.5rem;
      background: #22c55e; color: #fff;
      padding: .7rem 1.2rem; border-radius: 8px;
      font-weight: 600; font-size: .9rem;
      opacity: 0; transform: translateY(8px);
      transition: opacity .3s, transform .3s;
      pointer-events: none; z-index: 999;
    }
    #toast.show { opacity: 1; transform: translateY(0); }
    #toast.err  { background: #ef4444; }

    .loading { color: #94a3b8; padding: 1rem 0; }
    .empty   { color: #64748b; padding: 1rem 0; }

    #pixForm { max-width: 480px; }

    @media (max-width: 560px) {
      .form-row { grid-template-columns: 1fr; }
      .tabs { gap: 0; }
      .tab-btn { padding: .65rem .9rem; font-size: .85rem; }
    }
  </style>
</head>
<body>

<!-- Header -->
<header class="topbar">
  <h1>⚙️ Painel Admin</h1>
  <div class="topbar-actions">
    <span id="usernameDisplay"><?= $username ?></span>
    <a href="logout.php" class="btn-sm btn-danger" id="btnLogout">Sair</a>
  </div>
</header>

<!-- Tabs -->
<nav class="tabs" role="tablist">
  <button class="tab-btn active" data-tab="services" role="tab">Serviços</button>
  <button class="tab-btn" data-tab="plans" role="tab">Planos</button>
  <button class="tab-btn" data-tab="pix" role="tab">PIX</button>
  <button class="tab-btn" data-tab="contacts" role="tab">Mensagens</button>
  <button class="tab-btn" data-tab="settings" role="tab">Configurações</button>
  <button class="tab-btn" data-tab="profile" role="tab">Minha Conta</button>
</nav>

<!-- ══════════════════ SERVICES PANEL ══════════════════ -->
<div class="content">
  <div class="panel active" id="panel-services" role="tabpanel">
    <div class="list-header">
      <h2>Serviços</h2>
      <button class="btn-sm btn-primary" id="btnNewService">+ Novo</button>
    </div>
    <div class="item-list" id="servicesList">
      <p class="loading">Carregando…</p>
    </div>
  </div>

  <!-- ══════════════════ PLANS PANEL ══════════════════ -->
  <div class="panel" id="panel-plans" role="tabpanel">
    <div class="list-header">
      <h2>Planos</h2>
      <button class="btn-sm btn-primary" id="btnNewPlan">+ Novo</button>
    </div>
    <div class="item-list" id="plansList">
      <p class="loading">Carregando…</p>
    </div>
  </div>

  <!-- ══════════════════ PIX PANEL ══════════════════ -->
  <div class="panel" id="panel-pix" role="tabpanel">
    <h2 style="margin-bottom:1rem">Configuração PIX</h2>
    <form id="pixForm" novalidate>
      <label for="pixKey">Chave PIX *</label>
      <input type="text" id="pixKey" required />
      <label for="pixHint">Texto de dica (exibido abaixo do botão)</label>
      <input type="text" id="pixHint" />
      <label for="pixWhatsapp">Link WhatsApp (ex: https://wa.me/5521…)</label>
      <input type="url" id="pixWhatsapp" />
      <div class="form-actions">
        <button type="submit" class="btn-sm btn-primary">Salvar PIX</button>
      </div>
    </form>
  </div>

  <!-- ══════════════════ CONTACTS PANEL ══════════════════ -->
  <div class="panel" id="panel-contacts" role="tabpanel">
    <div class="list-header">
      <h2>Mensagens de Contato</h2>
      <button class="btn-sm btn-secondary" id="btnRefreshContacts">↺ Atualizar</button>
    </div>
    <div id="contactsList">
      <p class="loading">Carregando…</p>
    </div>
  </div>

  <!-- ══════════════════ SETTINGS PANEL ══════════════════ -->
  <div class="panel" id="panel-settings" role="tabpanel">
    <h2 style="margin-bottom:1rem">Configurações do Site</h2>
    <form id="settingsForm" novalidate>

      <label for="cfgSiteName">Nome do site *</label>
      <input type="text" id="cfgSiteName" required />

      <label for="cfgFooter">Texto do rodapé</label>
      <input type="text" id="cfgFooter" />

      <label for="cfgWhatsapp">Número do WhatsApp (somente dígitos, ex: 5521984158857)</label>
      <input type="text" id="cfgWhatsapp" placeholder="5521984158857" />

      <!-- ✅ MOVIDO PRA CÁ (CONFIGURAÇÕES) -->
      <label for="cfgWhatsappMessage">Mensagem padrão do WhatsApp (editável)</label>
      <textarea id="cfgWhatsappMessage"
                placeholder="Ex: Olá! Vim pelo site... Meu bairro/Zona é: ____."
                maxlength="500"></textarea>

      <label for="cfgEmail">E-mail para receber mensagens de contato</label>
      <input type="email" id="cfgEmail" />

      <label>Logo do site</label>
      <img id="cfgLogoPreview" class="settings-logo-preview" alt="Logo preview" />
      <div class="upload-area">
        <label class="upload-label" for="cfgLogoFile">📷 Trocar logo</label>
        <input type="file" id="cfgLogoFile" accept="image/jpeg,image/png,image/webp" />
        <div class="upload-status" id="cfgLogoStatus"></div>
      </div>
      <input type="hidden" id="cfgLogoUrl" />

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Botão "Contratar no WhatsApp" dos cards</h3>

      <label for="cfgCtaLabel">Texto do botão</label>
      <input type="text" id="cfgCtaLabel" placeholder="Contratar no WhatsApp" maxlength="80" />

      <div class="form-row">
        <div>
          <label for="cfgCtaBg">Cor de fundo (hex)</label>
          <input type="color" id="cfgCtaBg" value="#25d366" style="height:2.4rem;padding:.2rem .4rem;cursor:pointer" />
        </div>
        <div>
          <label for="cfgCtaColor">Cor do texto (hex)</label>
          <input type="color" id="cfgCtaColor" value="#ffffff" style="height:2.4rem;padding:.2rem .4rem;cursor:pointer" />
        </div>
      </div>
      <div class="form-row">
        <div>
          <label for="cfgCtaBorder">Cor da borda (hex, opcional)</label>
          <input type="text" id="cfgCtaBorder" placeholder="#000000 ou vazio" maxlength="7" />
        </div>
        <div>
          <label for="cfgCtaHoverBg">Cor hover fundo (hex)</label>
          <input type="color" id="cfgCtaHoverBg" value="#1aae52" style="height:2.4rem;padding:.2rem .4rem;cursor:pointer" />
        </div>
      </div>

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Texto de Apresentação (modal "Sobre")</h3>
      <label for="cfgAboutText">Texto exibido no modal "Sobre"</label>
      <textarea id="cfgAboutText" rows="4" maxlength="1000"
                placeholder="Descreva o site e seus serviços…"></textarea>

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Página de Serviços — Hero</h3>

      <label for="cfgServicosHeroTitle">Título principal</label>
      <input type="text" id="cfgServicosHeroTitle" placeholder="Serviços &amp; Planos" maxlength="120" />

      <label for="cfgServicosHeroSubtitle">Subtítulo</label>
      <input type="text" id="cfgServicosHeroSubtitle" placeholder="Soluções completas com qualidade…" maxlength="200" />

      <label for="cfgServicosHeroDesc">Descrição</label>
      <textarea id="cfgServicosHeroDesc" rows="3" maxlength="500"
                placeholder="Confira abaixo nossos serviços…"></textarea>

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Página de Serviços — Carrossel "Serviços"</h3>

      <label for="cfgServicosSectionTitle">Título da seção</label>
      <input type="text" id="cfgServicosSectionTitle" placeholder="Serviços" maxlength="120" />

      <label for="cfgServicosSectionSubtitle">Subtítulo da seção</label>
      <input type="text" id="cfgServicosSectionSubtitle" placeholder="Atendimentos avulsos para resolver rápido." maxlength="200" />

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Página de Serviços — Carrossel "Planos"</h3>

      <label for="cfgPlanosSectionTitle">Título da seção</label>
      <input type="text" id="cfgPlanosSectionTitle" placeholder="Planos &amp; Preços" maxlength="120" />

      <label for="cfgPlanosSectionSubtitle">Subtítulo da seção</label>
      <input type="text" id="cfgPlanosSectionSubtitle" placeholder="Para manutenção recorrente…" maxlength="200" />

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.9rem;font-size:.95rem;color:#94a3b8">Pagamentos</h3>

      <label for="cfgMercadoPago">URL checkout Mercado Pago (ex: https://www.mercadopago.com.br/…)</label>
      <input type="url" id="cfgMercadoPago" placeholder="https://www.mercadopago.com.br/…" />

      <label>Links de bancos (até 5)</label>
      <div id="bankLinksContainer"></div>
      <button type="button" class="btn-sm btn-secondary" id="btnAddBankLink"
              style="margin-bottom:.9rem">+ Adicionar link de banco</button>

      <div class="form-actions">
        <button type="submit" class="btn-sm btn-primary">Salvar configurações</button>
      </div>
    </form>
  </div>

<!-- ══════════════════ PROFILE PANEL ══════════════════ -->
  <div class="panel" id="panel-profile" role="tabpanel">
    <h2 style="margin-bottom:1rem">Minha Conta</h2>

    <p style="font-size:.85rem;color:#94a3b8;margin-bottom:1.5rem">
      Atualize seu e-mail ou senha de acesso ao painel.
      O e-mail cadastrado aqui é usado para recuperação de senha.
    </p>

    <form id="profileForm" novalidate>
      <h3 style="margin-bottom:.8rem;font-size:.95rem;color:#94a3b8">E-mail da conta</h3>

      <label for="profEmail">Novo e-mail</label>
      <input type="email" id="profEmail" autocomplete="email"
             placeholder="seu@email.com" />

      <hr style="border-color:#334155;margin:1.2rem 0">
      <h3 style="margin-bottom:.8rem;font-size:.95rem;color:#94a3b8">Alterar senha (opcional)</h3>

      <label for="profNewPass">Nova senha</label>
      <input type="password" id="profNewPass" autocomplete="new-password"
             minlength="8" placeholder="Mínimo 8 caracteres" />

      <label for="profConfirmPass">Confirmar nova senha</label>
      <input type="password" id="profConfirmPass" autocomplete="new-password"
             minlength="8" />

      <hr style="border-color:#334155;margin:1.2rem 0">

      <label for="profCurrentPass">Senha atual <span style="color:#f87171">*</span></label>
      <input type="password" id="profCurrentPass" autocomplete="current-password"
             placeholder="Obrigatório para salvar qualquer alteração" required />

      <div class="form-actions">
        <button type="submit" class="btn-sm btn-primary" id="btnSaveProfile">Salvar alterações</button>
      </div>
      <p class="msg" id="profileMsg" aria-live="polite"
         style="font-size:.85rem;margin-top:.75rem;text-align:center;min-height:1.2em"></p>
    </form>
  </div>
</div>
<div class="modal-overlay" id="serviceModal" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle">
  <div class="modal">
    <button class="modal-close" data-close="serviceModal" aria-label="Fechar">×</button>
    <h3 id="serviceModalTitle">Novo Serviço</h3>
    <input type="hidden" id="svcId" />
    <label for="svcTitle">Título *</label>
    <input type="text" id="svcTitle" required />
    <label for="svcDesc">Descrição *</label>
    <textarea id="svcDesc" rows="3"></textarea>
    <div class="form-row">
      <div>
        <label for="svcPrice">Texto do Preço</label>
        <input type="text" id="svcPrice" placeholder="A partir de R$ 150,00" />
      </div>
      <div>
        <label for="svcOrder">Ordenação</label>
        <input type="number" id="svcOrder" value="0" />
      </div>
    </div>
    <div class="form-row">
      <div>
        <label for="svcBadge">Badge texto</label>
        <input type="text" id="svcBadge" placeholder="Mais pedido" />
      </div>
      <div>
        <label for="svcBadgeColor">Badge cor</label>
        <select id="svcBadgeColor">
          <option value="default">Padrão (vermelho)</option>
          <option value="blue">Azul</option>
          <option value="yellow">Amarelo</option>
        </select>
      </div>
    </div>
    <label for="svcWhatsapp">Link WhatsApp</label>
    <input type="url" id="svcWhatsapp" placeholder="https://wa.me/5521..." />

    <!-- ✅ REMOVIDO DAQUI: cfgWhatsappMessage -->

    <label>Imagem</label>
    <div class="upload-area">
      <img class="img-preview" id="svcImgPreview" alt="preview" />
      <label class="upload-label" for="svcImgFile">📷 Escolher imagem</label>
      <input type="file" id="svcImgFile" accept="image/jpeg,image/png,image/webp" />
      <div class="upload-status" id="svcUploadStatus"></div>
    </div>
    <input type="hidden" id="svcImageUrl" />
    <div class="form-check">
      <input type="checkbox" id="svcActive" checked />
      <label for="svcActive" style="margin:0;color:#e2e8f0">Ativo</label>
    </div>
    <div class="form-actions">
      <button type="button" class="btn-sm btn-secondary" data-close="serviceModal">Cancelar</button>
      <button type="button" class="btn-sm btn-primary" id="btnSaveService">Salvar</button>
    </div>
  </div>
</div>

<!-- ══════════════════ PLAN MODAL ══════════════════ -->
<div class="modal-overlay" id="planModal" role="dialog" aria-modal="true" aria-labelledby="planModalTitle">
  <div class="modal">
    <button class="modal-close" data-close="planModal" aria-label="Fechar">×</button>
    <h3 id="planModalTitle">Novo Plano</h3>
    <input type="hidden" id="planId" />
    <label for="planTitle">Título *</label>
    <input type="text" id="planTitle" required />
    <label for="planDesc">Descrição *</label>
    <textarea id="planDesc" rows="3"></textarea>
    <div class="form-row">
      <div>
        <label for="planPrice">Texto do Preço</label>
        <input type="text" id="planPrice" placeholder="R$ 99,90 / mês" />
      </div>
      <div>
        <label for="planOrder">Ordenação</label>
        <input type="number" id="planOrder" value="0" />
      </div>
    </div>
    <div class="form-row">
      <div>
        <label for="planBadge">Badge texto</label>
        <input type="text" id="planBadge" placeholder="Recomendado" />
      </div>
      <div>
        <label for="planWhatsapp">Link WhatsApp</label>
        <input type="url" id="planWhatsapp" placeholder="https://wa.me/5521..." />
      </div>
    </div>
    <label>Itens do plano (um por linha)</label>
    <div class="features-list" id="featuresContainer"></div>
    <button type="button" class="btn-sm btn-secondary" id="btnAddFeature" style="margin-bottom:.9rem">+ Item</button>
    <label>Imagem</label>
    <div class="upload-area">
      <img class="img-preview" id="planImgPreview" alt="preview" />
      <label class="upload-label" for="planImgFile">📷 Escolher imagem</label>
      <input type="file" id="planImgFile" accept="image/jpeg,image/png,image/webp" />
      <div class="upload-status" id="planUploadStatus"></div>
    </div>
    <input type="hidden" id="planImageUrl" />
    <div class="form-check">
      <input type="checkbox" id="planFeatured" />
      <label for="planFeatured" style="margin:0;color:#e2e8f0">Destaque</label>
    </div>
    <div class="form-check">
      <input type="checkbox" id="planActive" checked />
      <label for="planActive" style="margin:0;color:#e2e8f0">Ativo</label>
    </div>
    <div class="form-actions">
      <button type="button" class="btn-sm btn-secondary" data-close="planModal">Cancelar</button>
      <button type="button" class="btn-sm btn-primary" id="btnSavePlan">Salvar</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast" role="status" aria-live="polite"></div>

<script>
/* ══════════════════════════════════════════════════════════════
   Globals & helpers
══════════════════════════════════════════════════════════════ */
const API = '../api';
let CSRF_TOKEN = '<?= e($csrf) ?>';

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

async function api(path, opts = {}) {
  const headers = {
    'Content-Type': 'application/json',
    'X-CSRF-Token': CSRF_TOKEN,
    ...(opts.headers || {}),
  };
  const res  = await fetch(API + path, {
    credentials: 'same-origin',
    headers,
    ...opts,
  });
  const json = await res.json();
  if (!json.ok) throw new Error(json.error || 'Erro desconhecido');
  return json.data;
}

let toastTimer;
function showToast(msg, isErr = false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'show' + (isErr ? ' err' : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { t.className = ''; }, 3200);
}

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-close]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.dataset.close));
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); });
});

/* ══════════════════════════════════════════════════════════════
   Tabs
══════════════════════════════════════════════════════════════ */
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const panel = document.getElementById('panel-' + btn.dataset.tab);
    if (panel) panel.classList.add('active');
    // Lazy-load contacts tab
    if (btn.dataset.tab === 'contacts') loadContacts();
    if (btn.dataset.tab === 'settings') loadSettings();
    if (btn.dataset.tab === 'profile')  loadProfile();
  });
});

/* ══════════════════════════════════════════════════════════════
   Image upload helper
══════════════════════════════════════════════════════════════ */
function setupImageUpload(inputId, previewId, statusId, hiddenId) {
  document.getElementById(inputId).addEventListener('change', async function () {
    const file = this.files[0];
    if (!file) return;
    const statusEl = document.getElementById(statusId);
    statusEl.textContent = 'Enviando…';
    const fd = new FormData();
    fd.append('image', file);
    fd.append('csrf_token', CSRF_TOKEN);
    try {
      const res  = await fetch(API + '/upload.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error);
      document.getElementById(hiddenId).value = json.data.url;
      const preview = document.getElementById(previewId);
      preview.src = json.data.url;
      preview.classList.add('show');
      statusEl.textContent = 'Upload concluído ✓';
    } catch (e) {
      statusEl.textContent = 'Erro: ' + e.message;
    }
  });
}
setupImageUpload('svcImgFile',  'svcImgPreview',  'svcUploadStatus',  'svcImageUrl');
setupImageUpload('planImgFile', 'planImgPreview', 'planUploadStatus', 'planImageUrl');
setupImageUpload('cfgLogoFile', 'cfgLogoPreview', 'cfgLogoStatus',    'cfgLogoUrl');

/* ══════════════════════════════════════════════════════════════
   SERVICES
══════════════════════════════════════════════════════════════ */
let services = [];

async function loadServices() {
  try {
    const res  = await fetch(API + '/services/list.php', { credentials: 'same-origin' });
    const json = await res.json();
    services   = json.data || [];
    renderServices();
  } catch { showToast('Erro ao carregar serviços', true); }
}

function renderServices() {
  const el = document.getElementById('servicesList');
  if (!services.length) { el.innerHTML = '<p class="empty">Nenhum serviço cadastrado.</p>'; return; }
  el.innerHTML = '';
  services.forEach(s => {
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
      ${s.image_url ? `<img src="${esc(s.image_url)}" alt="">` : `<div style="width:48px;height:48px;background:#334155;border-radius:6px;flex-shrink:0"></div>`}
      <div class="item-info">
        <div class="item-title">${esc(s.title)}</div>
        <div class="item-sub">${esc(s.price_text)} · ${Number(s.active) === 1 ? 'Ativo' : 'Inativo'}</div>
      </div>
      <div class="item-actions">
        <button class="btn-sm btn-edit" data-id="${s.id}" data-action="edit-svc">Editar</button>
        <button class="btn-sm btn-del"  data-id="${s.id}" data-action="del-svc">Excluir</button>
      </div>`;
    el.appendChild(row);
  });
  el.querySelectorAll('[data-action=edit-svc]').forEach(btn =>
    btn.addEventListener('click', () => openServiceModal(parseInt(btn.dataset.id)))
  );
  el.querySelectorAll('[data-action=del-svc]').forEach(btn =>
    btn.addEventListener('click', () => deleteService(parseInt(btn.dataset.id)))
  );
}

function openServiceModal(id = 0) {
  const svc = id ? services.find(s => s.id == id) : null;
  document.getElementById('serviceModalTitle').textContent = svc ? 'Editar Serviço' : 'Novo Serviço';
  document.getElementById('svcId').value          = svc ? svc.id : '';
  document.getElementById('svcTitle').value       = svc ? svc.title : '';
  document.getElementById('svcDesc').value        = svc ? svc.description : '';
  document.getElementById('svcPrice').value       = svc ? svc.price_text : '';
  document.getElementById('svcBadge').value       = svc ? svc.badge_text : '';
  document.getElementById('svcBadgeColor').value  = svc ? svc.badge_color : 'default';
  document.getElementById('svcWhatsapp').value    = svc ? svc.whatsapp_link : '';
  document.getElementById('svcOrder').value       = svc ? svc.sort_order : 0;
  document.getElementById('svcActive').checked    = svc ? Number(svc.active) === 1 : true;
  document.getElementById('svcImageUrl').value    = svc ? svc.image_url : '';
  document.getElementById('svcUploadStatus').textContent = '';
  const prev = document.getElementById('svcImgPreview');
  if (svc && svc.image_url) { prev.src = svc.image_url; prev.classList.add('show'); }
  else { prev.src = ''; prev.classList.remove('show'); }
  document.getElementById('svcImgFile').value = '';
  openModal('serviceModal');
}

document.getElementById('btnNewService').addEventListener('click', () => openServiceModal());

document.getElementById('btnSaveService').addEventListener('click', async () => {
  const id   = parseInt(document.getElementById('svcId').value) || 0;
  const title = document.getElementById('svcTitle').value.trim();
  const desc  = document.getElementById('svcDesc').value.trim();
  if (!title || !desc) { showToast('Título e descrição são obrigatórios.', true); return; }
  const body = {
    id, title, description: desc,
    price_text:    document.getElementById('svcPrice').value.trim(),
    badge_text:    document.getElementById('svcBadge').value.trim(),
    badge_color:   document.getElementById('svcBadgeColor').value,
    whatsapp_link: document.getElementById('svcWhatsapp').value.trim(),
    image_url:     document.getElementById('svcImageUrl').value.trim(),
    sort_order:    parseInt(document.getElementById('svcOrder').value) || 0,
    active:        document.getElementById('svcActive').checked,
  };
  try {
    const endpoint = id ? '/services/update.php' : '/services/create.php';
    await api(endpoint, { method: 'POST', body: JSON.stringify(body) });
    showToast(id ? 'Serviço atualizado!' : 'Serviço criado!');
    closeModal('serviceModal');
    loadServices();
  } catch (e) { showToast(e.message, true); }
});

async function deleteService(id) {
  if (!confirm('Excluir este serviço?')) return;
  try {
    await api('/services/delete.php', { method: 'POST', body: JSON.stringify({ id }) });
    showToast('Serviço excluído.');
    loadServices();
  } catch (e) { showToast(e.message, true); }
}

/* ══════════════════════════════════════════════════════════════
   PLANS
══════════════════════════════════════════════════════════════ */
let plans = [];

async function loadPlans() {
  try {
    const res  = await fetch(API + '/plans/list.php', { credentials: 'same-origin' });
    const json = await res.json();
    plans      = json.data || [];
    renderPlans();
  } catch { showToast('Erro ao carregar planos', true); }
}

function renderPlans() {
  const el = document.getElementById('plansList');
  if (!plans.length) { el.innerHTML = '<p class="empty">Nenhum plano cadastrado.</p>'; return; }
  el.innerHTML = '';
  plans.forEach(p => {
    const row = document.createElement('div');
    row.className = 'item-row';
    row.innerHTML = `
      ${p.image_url ? `<img src="${esc(p.image_url)}" alt="">` : `<div style="width:48px;height:48px;background:#334155;border-radius:6px;flex-shrink:0"></div>`}
      <div class="item-info">
        <div class="item-title">${esc(p.title)}${p.featured ? ' ⭐' : ''}</div>
        <div class="item-sub">${esc(p.price_text)} · ${p.active ? 'Ativo' : 'Inativo'}</div>
      </div>
      <div class="item-actions">
        <button class="btn-sm btn-edit" data-id="${p.id}" data-action="edit-plan">Editar</button>
        <button class="btn-sm btn-del"  data-id="${p.id}" data-action="del-plan">Excluir</button>
      </div>`;
    el.appendChild(row);
  });
  el.querySelectorAll('[data-action=edit-plan]').forEach(btn =>
    btn.addEventListener('click', () => openPlanModal(parseInt(btn.dataset.id)))
  );
  el.querySelectorAll('[data-action=del-plan]').forEach(btn =>
    btn.addEventListener('click', () => deletePlan(parseInt(btn.dataset.id)))
  );
}

function openPlanModal(id = 0) {
  const plan = id ? plans.find(p => p.id == id) : null;
  document.getElementById('planModalTitle').textContent = plan ? 'Editar Plano' : 'Novo Plano';
  document.getElementById('planId').value        = plan ? plan.id : '';
  document.getElementById('planTitle').value     = plan ? plan.title : '';
  document.getElementById('planDesc').value      = plan ? plan.description : '';
  document.getElementById('planPrice').value     = plan ? plan.price_text : '';
  document.getElementById('planBadge').value     = plan ? plan.badge_text : '';
  document.getElementById('planWhatsapp').value  = plan ? plan.whatsapp_link : '';
  document.getElementById('planOrder').value     = plan ? plan.sort_order : 0;
  document.getElementById('planFeatured').checked = plan ? !!plan.featured : false;
  document.getElementById('planActive').checked   = plan ? !!plan.active : true;
  document.getElementById('planImageUrl').value   = plan ? plan.image_url : '';
  document.getElementById('planUploadStatus').textContent = '';
  const prev = document.getElementById('planImgPreview');
  if (plan && plan.image_url) { prev.src = plan.image_url; prev.classList.add('show'); }
  else { prev.src = ''; prev.classList.remove('show'); }
  document.getElementById('planImgFile').value = '';
  const container = document.getElementById('featuresContainer');
  container.innerHTML = '';
  (plan ? (plan.features || []) : []).forEach(f => addFeatureRow(f));
  openModal('planModal');
}

function addFeatureRow(value = '') {
  const container = document.getElementById('featuresContainer');
  const row = document.createElement('div');
  row.className = 'feature-row';
  row.innerHTML = `
    <input type="text" value="${esc(value)}" placeholder="Ex: 2 visitas/mês" style="flex:1;margin-bottom:0" />
    <button type="button" class="btn-sm btn-del" title="Remover">×</button>`;
  row.querySelector('.btn-del').addEventListener('click', () => row.remove());
  container.appendChild(row);
}

document.getElementById('btnAddFeature').addEventListener('click', () => addFeatureRow());
document.getElementById('btnNewPlan').addEventListener('click', () => openPlanModal());

document.getElementById('btnSavePlan').addEventListener('click', async () => {
  const id    = parseInt(document.getElementById('planId').value) || 0;
  const title = document.getElementById('planTitle').value.trim();
  const desc  = document.getElementById('planDesc').value.trim();
  if (!title || !desc) { showToast('Título e descrição são obrigatórios.', true); return; }
  const features = Array.from(document.getElementById('featuresContainer').querySelectorAll('input'))
    .map(i => i.value.trim()).filter(Boolean);
  const body = {
    id, title, description: desc,
    price_text:    document.getElementById('planPrice').value.trim(),
    badge_text:    document.getElementById('planBadge').value.trim(),
    whatsapp_link: document.getElementById('planWhatsapp').value.trim(),
    image_url:     document.getElementById('planImageUrl').value.trim(),
    sort_order:    parseInt(document.getElementById('planOrder').value) || 0,
    featured:      document.getElementById('planFeatured').checked,
    active:        document.getElementById('planActive').checked,
    features,
  };
  try {
    const endpoint = id ? '/plans/update.php' : '/plans/create.php';
    await api(endpoint, { method: 'POST', body: JSON.stringify(body) });
    showToast(id ? 'Plano atualizado!' : 'Plano criado!');
    closeModal('planModal');
    loadPlans();
  } catch (e) { showToast(e.message, true); }
});

async function deletePlan(id) {
  if (!confirm('Excluir este plano?')) return;
  try {
    await api('/plans/delete.php', { method: 'POST', body: JSON.stringify({ id }) });
    showToast('Plano excluído.');
    loadPlans();
  } catch (e) { showToast(e.message, true); }
}

/* ══════════════════════════════════════════════════════════════
   PIX
══════════════════════════════════════════════════════════════ */
async function loadPix() {
  try {
    const res  = await fetch(API + '/pix/get.php', { credentials: 'same-origin' });
    const json = await res.json();
    if (json.ok) {
      document.getElementById('pixKey').value      = json.data.pix_key      || '';
      document.getElementById('pixHint').value     = json.data.pix_hint_text || '';
      document.getElementById('pixWhatsapp').value = json.data.whatsapp_link || '';
    }
  } catch { showToast('Erro ao carregar dados PIX', true); }
}

document.getElementById('pixForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const body = {
    pix_key:       document.getElementById('pixKey').value.trim(),
    pix_hint_text: document.getElementById('pixHint').value.trim(),
    whatsapp_link: document.getElementById('pixWhatsapp').value.trim(),
  };
  try {
    await api('/pix/update.php', { method: 'POST', body: JSON.stringify(body) });
    showToast('PIX atualizado!');
  } catch (e) { showToast(e.message, true); }
});

/* ══════════════════════════════════════════════════════════════
   CONTACTS — sound alert + polling
══════════════════════════════════════════════════════════════ */
let contactsLoaded   = false;
let knownUnreadIds   = null; // null = first load (no alert)
const POLL_INTERVAL  = 30000; // 30 s

function playAlertSound() {
  try {
    const ctx  = new (window.AudioContext || window.webkitAudioContext)();
    const gain = ctx.createGain();
    gain.gain.value = 1.5; // loud
    gain.connect(ctx.destination);

    // Three rising beeps
    [0, 0.25, 0.5].forEach((delay, i) => {
      const osc = ctx.createOscillator();
      osc.type      = 'square';
      osc.frequency.value = 880 + i * 220; // 880 Hz → 1100 Hz → 1320 Hz
      osc.connect(gain);
      osc.start(ctx.currentTime + delay);
      osc.stop(ctx.currentTime  + delay + 0.18);
    });

    setTimeout(() => ctx.close(), 1500);
  } catch (e) { /* AudioContext not supported */ }
}

async function fetchContactItems() {
  const res  = await fetch(API + '/contact/list.php', { credentials: 'same-origin' });
  const json = await res.json();
  return json.data?.items || [];
}

function renderContactItems(items) {
  const el = document.getElementById('contactsList');
  if (!items.length) {
    el.innerHTML = '<p class="empty">Nenhuma mensagem recebida ainda.</p>';
    return;
  }
  el.innerHTML = '';
  items.forEach(c => {
    const div = document.createElement('div');
    div.className = 'contact-row' + (c.read_at ? '' : ' unread');
    const dateStr = new Date(c.created_at).toLocaleString('pt-BR');
    div.innerHTML = `
      <div class="contact-meta">
        <span class="contact-name">${esc(c.nome)}</span>
        <span class="contact-phone">📞 ${esc(c.telefone)}</span>
        ${!c.read_at ? '<span class="contact-badge">Novo</span>' : ''}
        <span class="contact-date">${esc(dateStr)}</span>
      </div>
      <div class="contact-msg">${esc(c.mensagem)}</div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        ${!c.read_at ? `<button class="btn-sm btn-success" data-cid="${c.id}" data-action="mark-read">✓ Marcar como lido</button>` : ''}
        <button class="btn-sm btn-del" data-cid="${c.id}" data-action="del-contact">🗑 Excluir</button>
      </div>`;
    el.appendChild(div);
  });
  el.querySelectorAll('[data-action=mark-read]').forEach(btn => {
    btn.addEventListener('click', async () => {
      try {
        await api('/contact/mark-read.php', { method: 'POST', body: JSON.stringify({ id: parseInt(btn.dataset.cid) }) });
        contactsLoaded = false;
        loadContacts();
      } catch (e) { showToast(e.message, true); }
    });
  });
  el.querySelectorAll('[data-action=del-contact]').forEach(btn => {
    btn.addEventListener('click', () => deleteContact(parseInt(btn.dataset.cid)));
  });
}

async function loadContacts() {
  if (contactsLoaded) return;
  contactsLoaded = false; // always refresh
  const el = document.getElementById('contactsList');
  el.innerHTML = '<p class="loading">Carregando…</p>';
  try {
    const items = await fetchContactItems();
    renderContactItems(items);
    knownUnreadIds = new Set(items.filter(c => !c.read_at).map(c => c.id));
    contactsLoaded = true;
  } catch { showToast('Erro ao carregar mensagens', true); }
}

// Background polling — detects new unread messages even when on other tabs
async function pollNewContacts() {
  try {
    const items    = await fetchContactItems();
    const unreadNow = items.filter(c => !c.read_at).map(c => c.id);

    if (knownUnreadIds === null) {
      // First-ever poll: just initialise, no alert
      knownUnreadIds = new Set(unreadNow);
      return;
    }

    const newOnes = unreadNow.filter(id => !knownUnreadIds.has(id));
    if (newOnes.length > 0) {
      newOnes.forEach(id => knownUnreadIds.add(id));
      playAlertSound();
      showToast(`📩 ${newOnes.length} nova(s) mensagem(ns)!`);
      document.title = `(${knownUnreadIds.size}) Admin — Painel`;
      // Refresh visible list if contacts tab is open
      contactsLoaded = false;
      const panel = document.getElementById('panel-contacts');
      if (panel && panel.classList.contains('active')) loadContacts();
    }
  } catch { /* silent — polling errors should not disturb the admin */ }
}

setInterval(pollNewContacts, POLL_INTERVAL);

async function deleteContact(id) {
  if (!confirm('Excluir esta mensagem permanentemente?')) return;
  try {
    await api('/contact/delete.php', { method: 'POST', body: JSON.stringify({ id }) });
    showToast('Mensagem excluída.');
    contactsLoaded = false;
    loadContacts();
  } catch (e) { showToast(e.message, true); }
}

document.getElementById('btnRefreshContacts').addEventListener('click', () => {
  contactsLoaded = false;
  loadContacts();
});

/* ══════════════════════════════════════════════════════════════
   SETTINGS
══════════════════════════════════════════════════════════════ */
async function loadSettings() {
  try {
    const res  = await fetch(API + '/settings/get.php', { credentials: 'same-origin' });
    const json = await res.json();
    if (!json.ok) return;
    const d = json.data;
    document.getElementById('cfgSiteName').value  = d.site_name       || '';
    document.getElementById('cfgFooter').value    = d.footer_text     || '';
    document.getElementById('cfgWhatsapp').value  = d.whatsapp_number || '';
    document.getElementById('cfgWhatsappMessage').value = d.whatsapp_message || '';
    document.getElementById('cfgLogoUrl').value   = d.logo_url        || '';
    const prev = document.getElementById('cfgLogoPreview');
    if (d.logo_url) { prev.src = d.logo_url; prev.classList.add('show'); }
    else { prev.src = ''; prev.classList.remove('show'); }

    // CTA button settings
    document.getElementById('cfgCtaLabel').value    = d.cta_label        || 'Contratar no WhatsApp';
    document.getElementById('cfgCtaBg').value       = d.cta_bg_color     || '#25d366';
    document.getElementById('cfgCtaColor').value    = d.cta_text_color   || '#ffffff';
    document.getElementById('cfgCtaBorder').value   = d.cta_border_color || '';
    document.getElementById('cfgCtaHoverBg').value  = d.cta_hover_bg_color || '#1aae52';

    // About text
    document.getElementById('cfgAboutText').value   = d.about_text       || '';

    // Servicos page texts
    document.getElementById('cfgServicosHeroTitle').value       = d.servicos_hero_title       || '';
    document.getElementById('cfgServicosHeroSubtitle').value    = d.servicos_hero_subtitle    || '';
    document.getElementById('cfgServicosHeroDesc').value        = d.servicos_hero_description || '';
    document.getElementById('cfgServicosSectionTitle').value    = d.servicos_section_title    || '';
    document.getElementById('cfgServicosSectionSubtitle').value = d.servicos_section_subtitle || '';
    document.getElementById('cfgPlanosSectionTitle').value      = d.planos_section_title      || '';
    document.getElementById('cfgPlanosSectionSubtitle').value   = d.planos_section_subtitle   || '';

    // Payment
    document.getElementById('cfgMercadoPago').value   = d.mercadopago_checkout_url || '';
    loadBankLinks(d.bank_links || '[]');

    // contact_email is not in public get — leave empty unless saved
  } catch { showToast('Erro ao carregar configurações', true); }
}

/* ── Bank links editor ──────────────────────────────── */
function loadBankLinks(raw) {
  let arr = [];
  try { arr = JSON.parse(raw); } catch {}
  if (!Array.isArray(arr)) arr = [];
  const container = document.getElementById('bankLinksContainer');
  container.innerHTML = '';
  arr.forEach((lnk) => addBankLinkRow(lnk.label || '', lnk.url || ''));
}

function addBankLinkRow(label = '', url = '') {
  const container = document.getElementById('bankLinksContainer');
  const btn = document.getElementById('btnAddBankLink');
  if (container.children.length >= 5) {
    showToast('Limite de 5 links atingido.', true);
    return;
  }
  const row = document.createElement('div');
  row.className = 'form-row';
  row.style.cssText = 'align-items:start;margin-bottom:.4rem';
  row.innerHTML = `
    <div>
      <label>Nome do banco</label>
      <input type="text" class="bl-label" value="${escAdminHtml(label)}" placeholder="Ex: Itaú" maxlength="60" />
    </div>
    <div>
      <label>URL do formulário</label>
      <input type="url" class="bl-url" value="${escAdminHtml(url)}" placeholder="https://…" />
    </div>
    <div style="display:flex;align-items:flex-end;padding-bottom:.9rem">
      <button type="button" class="btn-sm btn-del" onclick="this.closest('.form-row').remove()" aria-label="Remover">✕</button>
    </div>
  `;
  container.appendChild(row);
}

function getBankLinks() {
  const rows = document.querySelectorAll('#bankLinksContainer .form-row');
  const arr = [];
  rows.forEach(row => {
    const label = row.querySelector('.bl-label').value.trim();
    const url   = row.querySelector('.bl-url').value.trim();
    if (label || url) arr.push({ label, url });
  });
  return arr;
}

function escAdminHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.getElementById('btnAddBankLink').addEventListener('click', () => addBankLinkRow());

document.getElementById('settingsForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const body = {
    site_name:               document.getElementById('cfgSiteName').value.trim(),
    footer_text:             document.getElementById('cfgFooter').value.trim(),
    whatsapp_number:         document.getElementById('cfgWhatsapp').value.trim(),
    whatsapp_message:        document.getElementById('cfgWhatsappMessage').value,
    logo_url:                document.getElementById('cfgLogoUrl').value.trim(),
    contact_email:           document.getElementById('cfgEmail').value.trim(),
    cta_label:               document.getElementById('cfgCtaLabel').value.trim(),
    cta_bg_color:            document.getElementById('cfgCtaBg').value.trim(),
    cta_text_color:          document.getElementById('cfgCtaColor').value.trim(),
    cta_border_color:        document.getElementById('cfgCtaBorder').value.trim(),
    cta_hover_bg_color:      document.getElementById('cfgCtaHoverBg').value.trim(),
    about_text:              document.getElementById('cfgAboutText').value,
    servicos_hero_title:       document.getElementById('cfgServicosHeroTitle').value.trim(),
    servicos_hero_subtitle:    document.getElementById('cfgServicosHeroSubtitle').value.trim(),
    servicos_hero_description: document.getElementById('cfgServicosHeroDesc').value.trim(),
    servicos_section_title:    document.getElementById('cfgServicosSectionTitle').value.trim(),
    servicos_section_subtitle: document.getElementById('cfgServicosSectionSubtitle').value.trim(),
    planos_section_title:      document.getElementById('cfgPlanosSectionTitle').value.trim(),
    planos_section_subtitle:   document.getElementById('cfgPlanosSectionSubtitle').value.trim(),
    mercadopago_checkout_url:  document.getElementById('cfgMercadoPago').value.trim(),
    bank_links:              JSON.stringify(getBankLinks()),
  };
  if (!body.site_name) { showToast('Nome do site é obrigatório.', true); return; }
  try {
    await api('/settings/update.php', { method: 'POST', body: JSON.stringify(body) });
    showToast('Configurações salvas!');
  } catch (e) { showToast(e.message, true); }
});

/* ══════════════════════════════════════════════════════════════
   Profile (Minha Conta)
══════════════════════════════════════════════════════════════ */
async function loadProfile() {
  try {
    const data = await api('/auth/get-profile.php');
    document.getElementById('profEmail').value = data.email || '';
  } catch (_) { /* silently ignore — email field stays empty */ }
}

document.getElementById('profileForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg        = document.getElementById('profileMsg');
  const btn        = document.getElementById('btnSaveProfile');
  const newEmail   = document.getElementById('profEmail').value.trim();
  const newPass    = document.getElementById('profNewPass').value;
  const confirmPass= document.getElementById('profConfirmPass').value;
  const curPass    = document.getElementById('profCurrentPass').value;

  msg.textContent = '';
  msg.style.color = '';

  if (!curPass) {
    msg.textContent = 'Informe sua senha atual.';
    msg.style.color = '#f87171';
    return;
  }
  if (!newEmail && !newPass) {
    msg.textContent = 'Informe um novo e-mail ou uma nova senha.';
    msg.style.color = '#f87171';
    return;
  }
  if (newPass && newPass !== confirmPass) {
    msg.textContent = 'As senhas não coincidem.';
    msg.style.color = '#f87171';
    return;
  }
  if (newPass && newPass.length < 8) {
    msg.textContent = 'A nova senha deve ter pelo menos 8 caracteres.';
    msg.style.color = '#f87171';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Salvando…';

  const body = { current_password: curPass };
  if (newEmail)  body.email        = newEmail;
  if (newPass)   body.new_password = newPass;

  try {
    await api('/auth/update-profile.php', { method: 'POST', body: JSON.stringify(body) });
    msg.textContent = 'Perfil atualizado com sucesso!';
    msg.style.color = '#4ade80';
    document.getElementById('profCurrentPass').value = '';
    document.getElementById('profNewPass').value     = '';
    document.getElementById('profConfirmPass').value = '';
    btn.textContent = 'Salvar alterações';
    btn.disabled = false;
  } catch (err) {
    msg.textContent = err.message || 'Erro ao salvar.';
    msg.style.color = '#f87171';
    btn.textContent = 'Salvar alterações';
    btn.disabled = false;
  }
});

/* ══════════════════════════════════════════════════════════════
   Init
══════════════════════════════════════════════════════════════ */
loadServices();
loadPlans();
loadPix();
// Kick off the first poll immediately so knownUnreadIds is set as early as possible
pollNewContacts();
</script>
</body>
</html>
