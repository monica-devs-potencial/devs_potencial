let formulario;
let mascara;

/* ── Sound beep (Web Audio API) ───────────────────────────────── */
(function () {
  const STORAGE_KEY = 'som_habilitado';

  function isSoundEnabled() {
    return localStorage.getItem(STORAGE_KEY) === '1';
  }

  function playBeep() {
    if (!isSoundEnabled()) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      gain.gain.setValueAtTime(0.08, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.12);
      osc.onended = () => ctx.close();
    } catch (e) { /* silently ignore if AudioContext unavailable */ }
  }

  // Expose globally so inline handlers can call it
  window.playBeep = playBeep;
  window.isSoundEnabled = isSoundEnabled;

  function attachBeepListeners() {
    const selectors = [
      '.btn-contato-nav',
      '.btn-fale-conosco',
      '.carousel-btn',
      '.card-cta',
      '.modal-close-btn',
      '#btnGerarPix',
      '#btnFecharPix',
      '#btnCopiarPix',
      '.sobre-modal-close',
      '[data-prev]',
      '[data-next]',
      '.pix-close',
      '.pix-copy',
      '.pix-whats',
    ];
    document.querySelectorAll(selectors.join(',')).forEach((el) => {
      if (!el.__beepAttached) {
        el.__beepAttached = true;
        el.addEventListener('click', playBeep);
      }
    });
  }

  // Build the toggle checkbox and insert into the footer
  function buildSoundToggle() {
    const footer = document.querySelector('.site-footer');
    if (!footer) return;
    if (footer.querySelector('.sound-toggle')) return; // already added

    const label = document.createElement('label');
    label.className = 'sound-toggle';
    label.setAttribute('title', 'Ativar/desativar som nos botões');

    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = isSoundEnabled();
    cb.addEventListener('change', () => {
      localStorage.setItem(STORAGE_KEY, cb.checked ? '1' : '0');
    });

    label.appendChild(cb);
    label.appendChild(document.createTextNode(' Som'));

    const sep = document.createElement('span');
    sep.className = 'footer-sep';
    sep.textContent = '·';

    footer.appendChild(sep);
    footer.appendChild(label);
  }

  document.addEventListener('DOMContentLoaded', () => {
    buildSoundToggle();
    attachBeepListeners();
  });

  // Re-attach after dynamic content is loaded (e.g., carousels)
  document.addEventListener('cardsRendered', attachBeepListeners);
})();


function cliqueiNoBotao() {
  if (!formulario || !mascara) return;
  formulario.classList.add('visible');
  mascara.classList.add('visible');
  // Announce to screen readers
  const firstInput = formulario.querySelector('input');
  if (firstInput) setTimeout(() => firstInput.focus(), 80);
}

function sumirFormulario() {
  if (!formulario || !mascara) return;
  formulario.classList.remove('visible');
  mascara.classList.remove('visible');
}

document.addEventListener("DOMContentLoaded", () => {
  formulario = document.querySelector(".fale-conosco");
  mascara = document.querySelector(".mascara-form");

  // Menu hamburger
  const menuToggle = document.getElementById("menuToggle");
  const navMenu = document.getElementById("navMenu");

  if (menuToggle && navMenu) {
    menuToggle.addEventListener("click", () => {
      const isActive = menuToggle.classList.toggle("active");
      navMenu.classList.toggle("active");
      menuToggle.setAttribute("aria-expanded", isActive ? "true" : "false");
    });

    document.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", () => {
        menuToggle.classList.remove("active");
        navMenu.classList.remove("active");
      });
    });

    // Fechar menu ao clicar fora
    document.addEventListener("click", (e) => {
      if (!menuToggle.contains(e.target) && !navMenu.contains(e.target)) {
        menuToggle.classList.remove("active");
        navMenu.classList.remove("active");
      }
    });
  }

  // Fechar modal ao clicar no fundo
  const modal = document.getElementById("modalProdutos");
  if (modal) {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) fecharModalProdutos();
    });
  }
});






function initCarousels() {
  const carousels = document.querySelectorAll("[data-carousel]");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  carousels.forEach((carousel) => {
    const track = carousel.querySelector(".carousel-track");
    const prev = carousel.querySelector("[data-prev]");
    const next = carousel.querySelector("[data-next]");
    const dotsEl = carousel.parentElement.querySelector("[data-dots]");

    if (!track || !prev || !next || !dotsEl) return;

    const cards = Array.from(track.children);
    let index = 0;
    let autoplayId = null;

    function cardsPerView() {
      return window.innerWidth <= 1024 ? 1 : 2;
    }

    function maxIndex() {
      return Math.max(0, cards.length - cardsPerView());
    }

    function update() {
      const gap = 14; // precisa bater com o CSS
      const cardWidth = cards[0].getBoundingClientRect().width;
      const offset = index * (cardWidth + gap);
      track.style.transform = `translateX(${-offset}px)`;

      // dots
      const pages = Math.max(1, maxIndex() + 1);
      dotsEl.querySelectorAll(".carousel-dot").forEach((d, i) => {
        d.classList.toggle("active", i === index);
        d.setAttribute("aria-current", i === index ? "true" : "false");
      });

      prev.disabled = index === 0;
      next.disabled = index === maxIndex();
    }

    function buildDots() {
      dotsEl.innerHTML = "";
      const pages = Math.max(1, maxIndex() + 1);
      for (let i = 0; i < pages; i++) {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "carousel-dot";
        dot.addEventListener("click", () => {
          index = i;
          update();
        });
        dotsEl.appendChild(dot);
      }
    }

    function goNext() {
      index = Math.min(maxIndex(), index + 1);
      update();
    }

    function goPrev() {
      index = Math.max(0, index - 1);
      update();
    }

    prev.addEventListener("click", goPrev);
    next.addEventListener("click", goNext);

    // Swipe (mobile)
    let startX = 0;
    let dragging = false;

    carousel.addEventListener("touchstart", (e) => {
      dragging = true;
      startX = e.touches[0].clientX;
    }, { passive: true });

    carousel.addEventListener("touchend", (e) => {
      if (!dragging) return;
      dragging = false;
      const endX = e.changedTouches[0].clientX;
      const diff = endX - startX;

      if (Math.abs(diff) > 40) {
        if (diff < 0) goNext();
        else goPrev();
      }
    });

    function startAutoplay() {
      if (reduceMotion) return;
      stopAutoplay();
      autoplayId = setInterval(() => {
        if (index >= maxIndex()) index = 0;
        else index += 1;
        update();
      }, 4500);
    }

    function stopAutoplay() {
      if (autoplayId) clearInterval(autoplayId);
      autoplayId = null;
    }

    // pausa no hover
    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);

    window.addEventListener("resize", () => {
      index = Math.min(index, maxIndex());
      buildDots();
      update();
    });

    buildDots();
    update();
    startAutoplay();
  });
}

document.addEventListener("DOMContentLoaded", () => {
  // If servicos.html sets this flag, it will call initCarousels itself
  // after loading dynamic content from the API, to avoid double-init.
  if (!window.__deferCarousels) {
    initCarousels();
  }
});



document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("pixModal");
  const btnAbrir = document.getElementById("btnGerarPix");
  const btnFechar = document.getElementById("btnFecharPix");
  const btnCopiar = document.getElementById("btnCopiarPix");
  const pixKeyEl = document.getElementById("pixKey");
  const toast = document.getElementById("pixToast");

  if (!modal || !btnAbrir || !btnFechar || !btnCopiar || !pixKeyEl) return;

  function abrirPix() {
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
  }

  function fecharPix() {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    if (toast) toast.textContent = "";
  }

  async function copiarPix() {
    const chave = pixKeyEl.textContent.trim();
    try {
      await navigator.clipboard.writeText(chave);
      if (toast) toast.textContent = "Chave PIX copiada!";
    } catch (e) {
      const textarea = document.createElement("textarea");
      textarea.value = chave;
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      textarea.remove();
      if (toast) toast.textContent = "Chave PIX copiada!";
    }
  }

  btnAbrir.addEventListener("click", abrirPix);
  btnFechar.addEventListener("click", fecharPix);
  btnCopiar.addEventListener("click", copiarPix);

  modal.addEventListener("click", (e) => {
    if (e.target === modal) fecharPix();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("open")) fecharPix();
  });
});



// ================================
// PIX Modal + Copiar chave
// ================================
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("pixModal");
  const btnAbrir = document.getElementById("btnGerarPix");
  const btnFechar = document.getElementById("btnFecharPix");
  const btnCopiar = document.getElementById("btnCopiarPix");
  const pixKeyEl = document.getElementById("pixKey");
  const toast = document.getElementById("pixToast");

  // Se estiver no index.html, esses elementos não existem — então sai sem erro
  if (!modal || !btnAbrir || !btnFechar || !btnCopiar || !pixKeyEl) return;

  function abrirPix() {
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
  }

  function fecharPix() {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    if (toast) toast.textContent = "";
  }

  async function copiarPix() {
    const chave = pixKeyEl.textContent.trim();

    try {
      await navigator.clipboard.writeText(chave);
      if (toast) toast.textContent = "Chave PIX copiada!";
    } catch (e) {
      // Fallback para navegadores que bloqueiam clipboard fora de HTTPS
      const textarea = document.createElement("textarea");
      textarea.value = chave;
      textarea.style.position = "fixed";
      textarea.style.left = "-9999px";
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();
      document.execCommand("copy");
      textarea.remove();

      if (toast) toast.textContent = "Chave PIX copiada!";
    }
  }

  btnAbrir.addEventListener("click", abrirPix);
  btnFechar.addEventListener("click", fecharPix);
  btnCopiar.addEventListener("click", copiarPix);

  // Fecha clicando fora do conteúdo do modal
  modal.addEventListener("click", (e) => {
    if (e.target === modal) fecharPix();
  });

  // Fecha com ESC
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("open")) fecharPix();
  });
});

(function () {
  function ensureToast() {
    let toast = document.getElementById("copyToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "copyToast";
      toast.className = "copy-toast";
      document.body.appendChild(toast);
    }
    return toast;
  }

  let toastTimer = null;

  function showToast(message) {
    const toast = ensureToast();
    toast.textContent = message;

    toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove("show"), 2200);
  }

  // Bloqueia menu de contexto só em imagens
  document.addEventListener("contextmenu", function (e) {
    const img = e.target && e.target.closest ? e.target.closest("img") : null;
    if (!img) return;

    e.preventDefault();
    showToast("Conteúdo protegido. Veja: termos.html");
  });

  // Evita arrastar imagens (caso esqueça draggable=\"false\")
  document.addEventListener("dragstart", function (e) {
    const img = e.target && e.target.closest ? e.target.closest("img") : null;
    if (!img) return;

    e.preventDefault();
    showToast("Conteúdo protegido. Veja: termos.html");
  });
})();

// ── Modal "Sobre" ─────────────────────────────────────────────
(function () {
  var modal = document.getElementById('sobreModal');
  var btnOpen = document.getElementById('btnSobre');
  var btnOpenNav = document.getElementById('btnSobreNav');
  var btnClose = document.getElementById('btnFecharSobre');

  if (!modal || !btnClose) return;

  function openModal(e) {
    if (e) e.preventDefault();
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }
  function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }

  if (btnOpen)    btnOpen.addEventListener('click', openModal);
  if (btnOpenNav) btnOpenNav.addEventListener('click', openModal);
  btnClose.addEventListener('click', closeModal);

  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });
})();
