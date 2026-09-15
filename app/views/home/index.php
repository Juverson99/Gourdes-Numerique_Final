<?php
$pageTitle = 'Gourde Numérique — Plateforme Nationale | République d\'Haïti';
require VIEWS_PATH . '/layouts/header.php';
?>
<!-- ===================== CARROUSEL VISUEL (remplace le hero) ===================== -->
<section class="showcase showcase-hero" id="accueil">
  <div class="showcase-viewport">
    <button type="button" class="showcase-arrow prev" id="showcasePrev" aria-label="Précédent">‹</button>
    <div class="showcase-track" id="showcaseTrack">
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Marcher.jfif') ?>" alt="Les parents gèrent les dépenses de leurs enfants avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Market.jfif') ?>" alt="Paiement des frais universitaires avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Dinepa.jfif') ?>" alt="Paiement des frais d'hôpital avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/billet.jfif') ?>" alt="Paiement à la pharmacie avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Ecole.jfif') ?>" alt="Paiement dans les auto-bus avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Camionette.jfif') ?>" alt="Paiement dans les camionnettes avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Marcher.jfif') ?>" alt="Paiement au marché public avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/moto.jfif') ?>" alt="Paiement en supermarché avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Dinepa.jfif') ?>" alt="Paiement du carburant et de l'entretien automobile avec Gourde Numérique">
      </div>
      <div class="showcase-slide" data-slide>
        <img src="<?= url('/assets/images/carousel/Camionette.jfif') ?>" alt="Transactions bancaires avec Gourde Numérique">
      </div>
    </div>
    <button type="button" class="showcase-arrow next" id="showcaseNext" aria-label="Suivant">›</button>
  </div>
  <div class="showcase-dots" id="showcaseDots"></div>
</section>


<?php if ($currentUser): ?>
<!-- ===================== SOLDE DU COMPTE CONNECTÉ ===================== -->
<section class="container" style="margin-top:2rem;">
  <div class="balance-card" style="background:linear-gradient(135deg,var(--bleu-nuit),var(--bleu-royal));border-radius:var(--radius-lg);padding:1.6rem 1.8rem;color:#fff;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
    <div>
      <div style="opacity:.75;font-size:.85rem;">Bonjour <?= e(explode(' ', $currentUser['name'])[0]) ?>, votre solde</div>
      <div style="font-family:var(--font-mono);font-size:2rem;font-weight:700;"><?= money($currentUser['balance']) ?> <small style="font-size:1rem;opacity:.8;">HTG</small></div>
    </div>
    <div style="font-family:var(--font-mono);font-size:.85rem;opacity:.85;">Tél : <?= e($currentUser['phone']) ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ===================== ACTIONS RAPIDES (grille pleine largeur) ===================== -->
<section class="quick-actions" id="actions">
  <div class="container">
    <div class="section-title-wrap">
      <span class="eyebrow"><span class="dot"></span> Page d'accueil</span>
      <h2>Toutes vos opérations, en un tap</h2>
      <p>Pensée pour rester simple, rapide et sécurisée — sur mobile comme sur ordinateur.</p>
    </div>

    <div class="row g-3 g-md-4">
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/envoyer') ?>">
          <div class="qc-icon"><i class="bi bi-send-fill"></i></div>
          <h3>Envoyer</h3>
          <p>Transférez des gourdes numériques instantanément</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/recevoir') ?>">
          <div class="qc-icon"><i class="bi bi-download"></i></div>
          <h3>Recevoir</h3>
          <p>Partagez votre QR code ou votre numéro de compte</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/paiement') ?>">
          <div class="qc-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
          <h3>Paiement</h3>
          <p>Payez marchands et factures en toute simplicité</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/historique') ?>">
          <div class="qc-icon"><i class="bi bi-clock-history"></i></div>
          <h3>Historique</h3>
          <p>Consultez toutes vos transactions passées</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/scanner') ?>">
          <div class="qc-icon"><i class="bi bi-qr-code-scan"></i></div>
          <h3>Scanner</h3>
          <p>Scannez un code pour payer ou vous connecter</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <a class="quick-card" href="<?= url('/plus') ?>">
          <div class="qc-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
          <h3>Plus</h3>
          <p>Épargne, cartes, support et réglages du compte</p>
        </a>
      </div>
    </div>
  </div>
</section>
<section class="goals" id="objectifs">
  <div class="container">
    <div class="row gy-5 align-items-center">
      <div class="col-lg-5">
        <span class="eyebrow"><span class="dot"></span> Notre mission</span>
        <h2 class="mt-3 mb-3" style="font-weight:800;font-size:clamp(1.6rem,3vw,2.2rem);">Conçue pour l'économie haïtienne</h2>
        <p style="color:var(--gris-texte);">Une infrastructure numérique nationale, développée pour et par Haïti, en partenariat avec la Banque de la République d'Haïti.</p>
      </div>
      <div class="col-lg-7">
        <div class="goal-row">
          <div class="gi"><i class="bi bi-shield-check"></i></div>
          <div>
            <h4>Réduire la dépendance à la monnaie fiduciaire</h4>
            <p>Moins de billets en circulation, plus de traçabilité et de sécurité pour chaque gourde.</p>
          </div>
        </div>
        <div class="goal-row">
          <div class="gi"><i class="bi bi-graph-up"></i></div>
          <div>
            <h4>Moderniser le système de paiement</h4>
            <p>Des paiements instantanés, disponibles 24h/24, partout sur le territoire.</p>
          </div>
        </div>
        <div class="goal-row">
          <div class="gi"><i class="bi bi-people"></i></div>
          <div>
            <h4>Favoriser l'inclusion financière</h4>
            <p>Un accès simplifié aux services financiers, même sans compte bancaire traditionnel.</p>
          </div>
        </div>
        <div class="goal-row">
          <div class="gi"><i class="bi bi-globe"></i></div>
          <div>
            <h4>Promouvoir la souveraineté économique</h4>
            <p>Une monnaie numérique nationale, garantie et régulée par la BRH.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'JS'
// ---------- Carrousel visuel : boucle infinie, auto-avance, flèches, points ----------
(function(){
  const viewport = document.querySelector('.showcase-viewport');
  const track = document.getElementById('showcaseTrack');
  const dotsWrap = document.getElementById('showcaseDots');
  const prevBtn = document.getElementById('showcasePrev');
  const nextBtn = document.getElementById('showcaseNext');
  if (!track) return;

  const realSlides = Array.from(track.children);
  const count = realSlides.length;

  const firstClone = realSlides[0].cloneNode(true);
  const lastClone = realSlides[count - 1].cloneNode(true);
  track.appendChild(firstClone);
  track.insertBefore(lastClone, realSlides[0]);

  const allSlides = Array.from(track.children);
  let index = 1;
  let autoplayTimer = null;
  const AUTOPLAY_MS = 4200;

  realSlides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.type = 'button';
    dot.className = 'showcase-dot' + (i === 0 ? ' is-active' : '');
    dot.setAttribute('aria-label', `Aller à la diapositive ${i + 1}`);
    dot.addEventListener('click', () => goTo(i + 1));
    dotsWrap.appendChild(dot);
  });
  const dots = Array.from(dotsWrap.children);

  function updateDots(){
    let realIndex = index - 1;
    if (realIndex < 0) realIndex = count - 1;
    if (realIndex >= count) realIndex = 0;
    dots.forEach((d, i) => d.classList.toggle('is-active', i === realIndex));
  }

  function render(withTransition = true){
    track.style.transition = withTransition ? '' : 'none';
    const slideWidth = allSlides[0].getBoundingClientRect().width;
    const gap = parseFloat(getComputedStyle(track).gap || 0);
    const offset = (slideWidth + gap) * index;
    const viewportCenter = viewport.getBoundingClientRect().width / 2;
    track.style.transform = `translateX(${viewportCenter - offset - slideWidth / 2}px)`;

    allSlides.forEach((s, i) => s.classList.toggle('is-active', i === index));
    updateDots();
  }

  function goTo(newIndex){
    index = newIndex;
    render(true);
    resetAutoplay();
  }

  function next(){ goTo(index + 1); }
  function prev(){ goTo(index - 1); }

  track.addEventListener('transitionend', () => {
    if (index >= allSlides.length - 1){
      index = 1;
      render(false);
    } else if (index <= 0){
      index = allSlides.length - 2;
      render(false);
    }
  });

  nextBtn.addEventListener('click', next);
  prevBtn.addEventListener('click', prev);

  function startAutoplay(){
    autoplayTimer = setInterval(next, AUTOPLAY_MS);
  }
  function resetAutoplay(){
    clearInterval(autoplayTimer);
    startAutoplay();
  }

  viewport.addEventListener('mouseenter', () => clearInterval(autoplayTimer));
  viewport.addEventListener('mouseleave', startAutoplay);

  window.addEventListener('resize', () => render(false));

  render(false);
  startAutoplay();
})();
JS;

require VIEWS_PATH . '/layouts/footer.php';
