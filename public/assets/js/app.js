/**
 * public/assets/js/app.js
 * Comportements partagés par toutes les pages : connexion, inscription,
 * déconnexion (via l'API PHP réelle), et petites notifications "toast".
 * L'état de connexion affiché au premier rendu vient du serveur (session
 * PHP) — ce script gère seulement les interactions après coup.
 */

const API_BASE = (window.APP_BASE_URL || '') + '/api';

function showToast(message) {
  const toast = document.getElementById('toastConfirm');
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2400);
}

// Affiche un toast mémorisé avant un rechargement de page (ex : après connexion réussie)
(function showFlashToast() {
  const flash = sessionStorage.getItem('gn_flash');
  if (flash) {
    sessionStorage.removeItem('gn_flash');
    window.addEventListener('DOMContentLoaded', () => showToast(flash));
  }
})();

function setFlashAndReload(message) {
  sessionStorage.setItem('gn_flash', message);
  window.location.reload();
}

// Comme setFlashAndReload, mais redirige vers une URL précise au lieu de
// recharger la page courante (ex. après l'inscription : toujours renvoyer
// vers l'accueil, quelle que soit la page où le formulaire a été ouvert).
function setFlashAndRedirect(message, path) {
  sessionStorage.setItem('gn_flash', message);
  window.location.href = (window.APP_BASE_URL || '') + path;
}

// ---------- Curseur personnalisé (point doré + halo avec inertie) ----------
// Désactivé : le site utilise maintenant le curseur flèche natif du système.
(function () {
  return; // curseur personnalisé désactivé
})();

// ---------- Effet "ripple" doré au clic sur les boutons ----------
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const RIPPLE_SELECTOR = '.btn-gold, .btn-outline-line, .mini-btn, .mini-btn-outline';

  document.addEventListener('click', (e) => {
    const btn = e.target.closest && e.target.closest(RIPPLE_SELECTOR);
    if (!btn) return;

    const rect = btn.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 1.4;
    const ripple = document.createElement('span');
    ripple.className = 'gn-ripple';
    ripple.style.width = ripple.style.height = `${size}px`;
    ripple.style.left = `${e.clientX - rect.left - size / 2}px`;
    ripple.style.top = `${e.clientY - rect.top - size / 2}px`;
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
    setTimeout(() => ripple.remove(), 700);
  });
})();

// ---------- Révélation progressive des cartes/sections au défilement ----------
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!('IntersectionObserver' in window)) return;

  const selector = [
    '.quick-card', '.bill-card', '.balance-card', '.info-card',
    '.feature-detail-card', '.contact-info-card', '.contact-form-card',
    '.section-title-wrap', '.value-prop-row'
  ].join(', ');
  const items = Array.from(document.querySelectorAll(selector));
  if (!items.length) return;

  // Petit décalage progressif pour les éléments d'un même groupe (ex : une grille de cartes).
  const seenPerParent = new Map();
  items.forEach((el) => {
    el.classList.add('reveal-init');
    const parent = el.parentElement;
    const idx = seenPerParent.get(parent) || 0;
    seenPerParent.set(parent, idx + 1);
    el.style.setProperty('--reveal-delay', `${Math.min(idx, 6) * 70}ms`);
  });

  // Une fois l'animation d'entrée terminée, on retire les classes/propriétés
  // pour laisser les effets de survol existants (transform au hover) reprendre
  // le contrôle sans qu'aucune règle ne rentre en conflit.
  function cleanup(el) {
    el.classList.remove('reveal-init', 'reveal-in');
    el.style.removeProperty('--reveal-delay');
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      el.classList.add('reveal-in');
      observer.unobserve(el);
      el.addEventListener('transitionend', function onEnd(ev) {
        if (ev.propertyName !== 'transform') return;
        el.removeEventListener('transitionend', onEnd);
        cleanup(el);
      });
      // Filet de sécurité si transitionend ne se déclenche pas.
      setTimeout(() => cleanup(el), 1200);
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  items.forEach((el) => observer.observe(el));
})();

// ---------- Bouton plein écran / réduire (barre de navigation) ----------
(function () {
  const btn  = document.getElementById('fullscreenToggleBtn');
  const icon = document.getElementById('fullscreenToggleIcon');
  if (!btn || !icon) return;

  function applyIcon() {
    const isFullscreen = !!document.fullscreenElement;
    icon.classList.toggle('bi-arrows-fullscreen', !isFullscreen);
    icon.classList.toggle('bi-fullscreen-exit', isFullscreen);
    btn.title = isFullscreen ? 'Quitter le plein écran' : 'Plein écran';
    btn.setAttribute('aria-label', btn.title);
  }

  btn.addEventListener('click', async () => {
    try {
      if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen();
      } else {
        await document.exitFullscreen();
      }
    } catch (e) {
      showToast("Le mode plein écran n'est pas disponible sur cet appareil.");
    }
  });

  document.addEventListener('fullscreenchange', applyIcon);
  applyIcon();
})();

// ---------- Bascule mode clair / nocturne (barre de navigation) ----------
(function () {
  const toggleBtn = document.getElementById('themeToggleBtn');
  const icon      = document.getElementById('themeToggleIcon');
  if (!toggleBtn || !icon) return;

  function applyIcon(isDark) {
    icon.classList.toggle('bi-moon-stars-fill', !isDark);
    icon.classList.toggle('bi-brightness-high-fill', isDark);
  }

  // L'attribut a déjà pu être posé par le script anti-flash dans <head>.
  applyIcon(document.documentElement.getAttribute('data-theme') === 'dark');

  toggleBtn.addEventListener('click', () => {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
      document.documentElement.removeAttribute('data-theme');
      try { localStorage.setItem('site-theme', 'light'); } catch (e) {}
      applyIcon(false);
    } else {
      document.documentElement.setAttribute('data-theme', 'dark');
      try { localStorage.setItem('site-theme', 'dark'); } catch (e) {}
      applyIcon(true);
    }
  });
})();

async function apiPost(url, payload) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload || {}),
  });
  return res.json();
}

// Variante pour les envois avec fichier (ex : photo de profil) : on laisse le
// navigateur poser lui-même l'en-tête Content-Type (multipart + boundary).
async function apiPostForm(url, formData) {
  const res = await fetch(url, { method: 'POST', body: formData });
  return res.json();
}

function showAuthError(el, message) {
  if (!el) return;
  el.textContent = message;
  el.classList.remove('d-none');
}
function hideAuthError(el) {
  if (!el) return;
  el.classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', () => {
  // ---------- Connexion ----------
  const loginForm = document.getElementById('loginForm');
  const loginError = document.getElementById('loginError');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideAuthError(loginError);

      const email = document.getElementById('loginIdentifier').value.trim();
      const password = document.getElementById('loginPassword').value;

      if (!email || !password) {
        showAuthError(loginError, 'Veuillez remplir tous les champs.');
        return;
      }

      const data = await apiPost(`${API_BASE}/account.php?action=login`, { email, password });
      if (!data.ok) {
        showAuthError(loginError, data.error || 'Identifiants incorrects.');
        return;
      }
      sessionStorage.setItem('gn_flash', `Content de vous revoir, ${data.user.name.split(' ')[0]} ✓`);
      window.location.href = window.LOGIN_REDIRECT_TO || window.location.pathname;
    });
  }

  // ---------- Inscription ----------
  const signupForm = document.getElementById('signupForm');
  const signupError = document.getElementById('signupError');
  if (signupForm) {
    signupForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideAuthError(signupError);

      const payload = {
        name: document.getElementById('signupName').value.trim(),
        ninu: document.getElementById('signupNinu').value.trim(),
        sexe: document.getElementById('signupSexe').value,
        age: document.getElementById('signupAge').value,
        ville: document.getElementById('signupVille').value.trim(),
        pays: document.getElementById('signupPays').value,
        phone: document.getElementById('signupPhone').value.trim(),
        email: document.getElementById('signupEmail').value.trim(),
        password: document.getElementById('signupPassword').value,
        password_confirm: document.getElementById('signupPasswordConfirm').value,
      };

      // Vérification immédiate du format NINU / téléphone, avant même d'appeler le
      // serveur (qui refera de toute façon la même validation, c'est la source de vérité).
      const ninuDigits = payload.ninu.replace(/\D/g, '');
      if (ninuDigits.length !== 10) {
        showAuthError(signupError, 'Le numéro NINU doit contenir exactement 10 chiffres.');
        return;
      }
      const phoneDigits = payload.phone.replace(/\D/g, '').replace(/^509/, '');
      if (phoneDigits.length !== 8) {
        showAuthError(signupError, 'Le numéro de téléphone doit contenir l\'indicatif 509 suivi de 8 chiffres, ex : 509 46213235.');
        return;
      }

      // Envoi en multipart/form-data pour pouvoir inclure la photo de profil (facultative)
      const formData = new FormData();
      Object.entries(payload).forEach(([key, value]) => formData.append(key, value));
      const signupPhotoInput = document.getElementById('signupPhoto');
      if (signupPhotoInput && signupPhotoInput.files && signupPhotoInput.files[0]) {
        formData.append('photo', signupPhotoInput.files[0]);
      }

      const data = await apiPostForm(`${API_BASE}/account.php?action=signup`, formData);
      if (!data.ok) {
        showAuthError(signupError, data.error || 'Impossible de créer le compte.');
        return;
      }
      setFlashAndRedirect(`Bienvenue ${data.user.name.split(' ')[0]} — votre compte a été créé ✓`, '/');
    });
  }

  // ---------- Déconnexion ----------
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await apiPost(`${API_BASE}/account.php?action=logout`, {});
      setFlashAndReload('Vous êtes déconnecté(e)');
    });
  }

  // Réinitialise les messages d'erreur à l'ouverture des modals
  const signupModalEl = document.getElementById('signupModal');
  const loginModalEl = document.getElementById('loginModal');
  if (signupModalEl) signupModalEl.addEventListener('show.bs.modal', () => hideAuthError(signupError));
  if (loginModalEl) loginModalEl.addEventListener('show.bs.modal', () => hideAuthError(loginError));
});

// ---------- Icône d'alerte : demandes d'argent reçues d'un autre client ----------
// Affichée sur TOUTES les pages (header partagé) dès qu'un client est connecté :
// interroge périodiquement le serveur pour savoir si un autre client lui a
// demandé un montant, affiche une pastille sur la cloche et la liste dans un
// menu déroulant, avec un bouton Accepter (déclenche un vrai envoi de solde)
// et un bouton Refuser pour chaque demande.
(function () {
  if (!window.APP_LOGGED_IN) return;

  const btn        = document.getElementById('requestsAlertBtn');
  const badge      = document.getElementById('requestsBadge');
  const menu       = document.getElementById('requestsDropdownMenu');
  const emptyMsg   = document.getElementById('requestsEmptyMsg');
  if (!btn || !badge || !menu) return;

  let busy = false;

  function renderRequests(demandes) {
    menu.querySelectorAll('.request-item').forEach(el => el.remove());
    const count = demandes.length;

    if (count === 0) {
      badge.classList.add('d-none');
      btn.classList.remove('has-requests');
      emptyMsg.classList.remove('d-none');
      return;
    }

    emptyMsg.classList.add('d-none');
    badge.textContent = count > 9 ? '9+' : String(count);
    badge.classList.remove('d-none');
    btn.classList.add('has-requests');

    demandes.forEach((d) => {
      const li = document.createElement('li');
      li.className = 'request-item';
      li.dataset.reference = d.reference;
      const amountText = Number(d.amount).toLocaleString('fr-FR');
      li.innerHTML = `
        <div class="ri-top">
          <span class="ri-name">${escapeHtml(d.requester_name)}</span>
          <span class="ri-amount">${amountText} HTG</span>
        </div>
        ${d.message ? `<div class="ri-message">${escapeHtml(d.message)}</div>` : ''}
        <div class="ri-actions">
          <button type="button" class="ri-accept" data-action="accepter">Accepter</button>
          <button type="button" class="ri-decline" data-action="refuser">Refuser</button>
        </div>
      `;
      menu.appendChild(li);
    });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = String(str ?? '');
    return div.innerHTML;
  }

  async function refreshRequests() {
    if (busy) return;
    try {
      const data = await apiPost(`${API_BASE}/transactions.php?action=mes_demandes`, {});
      if (data && data.ok) renderRequests(data.demandes || []);
    } catch (e) { /* silencieux : une alerte qui échoue ne doit pas gêner la navigation */ }
  }

  menu.addEventListener('click', async (e) => {
    const actionBtn = e.target.closest('button[data-action]');
    if (!actionBtn || busy) return;
    const item = actionBtn.closest('.request-item');
    const reference = item && item.dataset.reference;
    if (!reference) return;

    busy = true;
    actionBtn.disabled = true;

    if (actionBtn.dataset.action === 'accepter') {
      const data = await apiPost(`${API_BASE}/transactions.php?action=accepter_demande`, { reference });
      if (!data.ok) {
        showToast(data.error || "Impossible d'accepter cette demande.");
        busy = false;
        actionBtn.disabled = false;
        return;
      }
      // Le solde a changé : on recharge pour que l'affichage courant (solde,
      // historique...) reflète immédiatement le nouveau montant, comme pour
      // un envoi classique depuis la page "Envoyer".
      setFlashAndReload(`Demande acceptée — ${Number(data.amount).toLocaleString('fr-FR')} HTG envoyés à ${data.requester_name} ✓`);
      return;
    }

    if (actionBtn.dataset.action === 'refuser') {
      const data = await apiPost(`${API_BASE}/transactions.php?action=refuser_demande`, { reference });
      busy = false;
      if (!data.ok) {
        showToast(data.error || 'Impossible de refuser cette demande.');
        actionBtn.disabled = false;
        return;
      }
      item.remove();
      const remaining = menu.querySelectorAll('.request-item').length;
      if (remaining === 0) {
        badge.classList.add('d-none');
        btn.classList.remove('has-requests');
        emptyMsg.classList.remove('d-none');
      } else {
        badge.textContent = remaining > 9 ? '9+' : String(remaining);
      }
      showToast('Demande refusée');
    }
  });

  document.addEventListener('DOMContentLoaded', refreshRequests);
  if (document.readyState !== 'loading') refreshRequests();

  // Sondage périodique pour détecter une nouvelle demande sans recharger la page.
  setInterval(refreshRequests, 25000);
})();
