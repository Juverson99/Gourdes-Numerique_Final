/**
 * Card 360° Viewer — Réalité Virtuelle pour la carte virtuelle
 * Carte 3D en CSS pure (recto/verso), rotation libre au glisser (souris/tactile),
 * rotation automatique légère à l'arrêt, et bouton de retournement rapide.
 */
(function () {
  let rotationY = 0;       // rotation actuelle affichée (degrés)
  let targetRotationY = 0; // rotation cible (interpolée)
  let isDragging = false;
  let dragStartX = 0;
  let dragStartRotation = 0;
  let autoRotate = true;
  let rafId = null;
  let cardEl = null;
  let resumeTimeout = null;

  function buildModal() {
    let modal = document.getElementById('card360Modal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'card360Modal';
    modal.innerHTML = `
      <div class="card360-overlay"></div>
      <div class="card360-modal-content">
        <div class="card360-modal-header">
          <h3><i class="bi bi-cube"></i> Carte virtuelle — Vue 360°</h3>
          <button type="button" class="card360-close" id="card360CloseBtn" aria-label="Fermer">&times;</button>
        </div>
        <div class="card360-scene" id="card360Scene">
          <div class="card360-card" id="card360Card">
            <div class="card360-face card360-front" id="card360Front"></div>
            <div class="card360-face card360-back" id="card360Back">
              <div class="mag-stripe"></div>
              <div class="back-emblem"><span class="ring">BRH</span> Gourde Numérique</div>
              <div class="sig-row">
                <div class="sig-panel"></div>
                <div class="cvv-box" id="card360CvvBack"></div>
              </div>
              <p class="back-info">Cette carte virtuelle est réservée aux paiements en ligne et n'est pas physiquement émise. Toute utilisation frauduleuse doit être signalée immédiatement à la BRH.</p>
            </div>
          </div>
        </div>
        <p class="card360-instructions">
          <i class="bi bi-hand-index"></i> Glissez pour faire pivoter la carte
        </p>
        <div class="text-center">
          <button type="button" class="card360-flip-btn" id="card360FlipBtn">
            <i class="bi bi-arrow-repeat"></i> Retourner la carte
          </button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    document.getElementById('card360CloseBtn').addEventListener('click', closeCard360Viewer);
    modal.querySelector('.card360-overlay').addEventListener('click', closeCard360Viewer);
    document.getElementById('card360FlipBtn').addEventListener('click', () => {
      targetRotationY += 180;
      autoRotate = false;
    });

    cardEl = document.getElementById('card360Card');
    setupDragEvents(cardEl);

    return modal;
  }

  function setupDragEvents(el) {
    const onDown = (clientX) => {
      isDragging = true;
      autoRotate = false;
      dragStartX = clientX;
      dragStartRotation = targetRotationY;
      clearTimeout(resumeTimeout);
    };
    const onMove = (clientX) => {
      if (!isDragging) return;
      const deltaX = clientX - dragStartX;
      targetRotationY = dragStartRotation + deltaX * 0.4;
    };
    const onUp = () => {
      if (!isDragging) return;
      isDragging = false;
      resumeTimeout = setTimeout(() => { autoRotate = true; }, 1200);
    };

    el.addEventListener('mousedown', (e) => { onDown(e.clientX); e.preventDefault(); });
    window.addEventListener('mousemove', (e) => onMove(e.clientX));
    window.addEventListener('mouseup', onUp);

    el.addEventListener('touchstart', (e) => {
      if (e.touches.length === 1) onDown(e.touches[0].clientX);
    }, { passive: true });
    el.addEventListener('touchmove', (e) => {
      if (e.touches.length === 1) { onMove(e.touches[0].clientX); }
    }, { passive: true });
    el.addEventListener('touchend', onUp);
  }

  function animate() {
    rafId = requestAnimationFrame(animate);
    if (autoRotate && !isDragging) {
      targetRotationY += 0.15;
    }
    rotationY += (targetRotationY - rotationY) * 0.15;
    if (cardEl) {
      cardEl.style.transform = `rotateY(${rotationY}deg)`;
    }
  }

  function fillCardData(btn) {
    const front = document.getElementById('card360Front');
    const cvvBack = document.getElementById('card360CvvBack');

    const label = btn.dataset.label || 'Carte virtuelle';
    const number = btn.dataset.number || '';
    const holder = btn.dataset.holder || '';
    const expiry = btn.dataset.expiry || '';
    const cvv = btn.dataset.cvv || '';
    const status = btn.dataset.status || 'Active';
    const statusClass = status === 'Active' ? 'badge-in' : 'badge-out';

    front.innerHTML = `
      <div class="d-flex justify-content-between align-items-start">
        <span class="carte-brh-emblem">
          <span class="ring">BRH</span>
          <span style="font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">${label}</span>
        </span>
        <span class="badge-admin ${statusClass}">${status}</span>
      </div>
      <div>
        <div style="font-family:var(--font-mono);font-size:1.15rem;letter-spacing:.05em;margin-bottom:.9rem;">${number}</div>
        <div class="d-flex justify-content-between" style="font-size:.8rem;opacity:.9;">
          <div>
            <div style="opacity:.7;font-size:.7rem;">TITULAIRE</div>
            ${holder}
          </div>
          <div>
            <div style="opacity:.7;font-size:.7rem;">EXPIRE</div>
            ${expiry}
          </div>
        </div>
      </div>
    `;
    cvvBack.textContent = cvv;
  }

  window.openCard360Viewer = function (btn) {
    const modal = buildModal();
    fillCardData(btn);

    rotationY = 0;
    targetRotationY = 0;
    autoRotate = true;
    if (cardEl) cardEl.style.transform = 'rotateY(0deg)';

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    if (!rafId) animate();
  };

  function closeCard360Viewer() {
    const modal = document.getElementById('card360Modal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    isDragging = false;
    autoRotate = true;
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.card-vr-btn').forEach((btn) => {
      btn.addEventListener('click', () => openCard360Viewer(btn));
    });
  });
})();
