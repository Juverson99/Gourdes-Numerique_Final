<?php
$pageTitle = 'Plus de services — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Plus de services</span>
    <h1>Épargne, cartes, support <span class="accent">et réglages</span></h1>
    <p class="lead-text">Retrouvez ici l'ensemble des services complémentaires de votre compte Gourde Numérique.</p>
  </div>
</section>

<section class="container" style="max-width:760px;">
  <div class="info-card">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.6rem;">Mon compte</div>
    <div class="d-flex align-items-center gap-3 mb-3">
      <span class="signup-photo-preview" style="width:56px;height:56px;">
        <?php if (!empty($currentUser['photo_url'])): ?>
          <img src="<?= e($currentUser['photo_url']) ?>" alt="">
        <?php else: ?>
          <i class="bi bi-person-fill"></i>
        <?php endif; ?>
      </span>
      <strong style="font-size:1.05rem;"><?= e($currentUser['name']) ?></strong>
    </div>
    <div class="row g-3">
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Nom complet</small><br><strong><?= e($currentUser['name']) ?></strong></div>
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Numéro NINU</small><br><strong><?= e($currentUser['ninu']) ?></strong></div>
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Téléphone</small><br><strong><?= e($currentUser['phone']) ?></strong></div>
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Email</small><br><strong><?= e($currentUser['email']) ?></strong></div>
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Ville</small><br><strong><?= e($currentUser['ville']) ?></strong></div>
      <div class="col-sm-6"><small style="color:var(--gris-texte);">Solde actuel</small><br><strong style="font-family:var(--font-mono);"><?= money($currentUser['balance']) ?> HTG</strong></div>
    </div>
  </div>
</section>

<section class="quick-actions" style="padding-top:1.5rem;">
  <div class="container">
    <div class="row g-3 g-md-4">
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/epargne') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-piggy-bank-fill"></i></div>
          <h3>Épargne</h3>
          <p>Mettez de côté des gourdes numériques et suivez vos objectifs</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/cartes-virtuelles') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-credit-card-fill"></i></div>
          <h3>Cartes virtuelles</h3>
          <p>Générez une carte virtuelle pour vos achats en ligne</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/securite') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-shield-lock-fill"></i></div>
          <h3>Sécurité</h3>
          <p>Gérez votre code PIN, votre authentification et vos appareils</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/convertir') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-arrow-left-right"></i></div>
          <h3>Convertir</h3>
          <p>Convertissez votre argent MonCash, votre banque ou du billet physique en un clic</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/reglages') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-gear-fill"></i></div>
          <h3>Réglages du compte</h3>
          <p>Mettez à jour vos informations personnelles et préférences</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/contact') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-headset"></i></div>
          <h3>Support</h3>
          <p>Contactez le service client de la Gourde Numérique</p>
        </a>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= url('/aide') ?>" class="quick-card">
          <div class="qc-icon"><i class="bi bi-question-circle-fill"></i></div>
          <h3>Aide & FAQ</h3>
          <p>Trouvez des réponses aux questions les plus fréquentes</p>
        </a>
      </div>
    </div>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
