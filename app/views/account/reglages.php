<?php
$pageTitle = 'Réglages du compte — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Réglages du compte</span>
    <h1>Vos <span class="accent">informations personnelles</span></h1>
    <p class="lead-text">Mettez à jour votre profil Gourde Numérique.</p>
  </div>
</section>

<section class="container" style="max-width:640px;">
  <div class="info-card">
    <?php if ($success): ?>
      <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/reglages') ?>" enctype="multipart/form-data">
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="signup-photo-preview" style="width:56px;height:56px;">
          <?php if (!empty($currentUser['photo_url'])): ?>
            <img src="<?= e($currentUser['photo_url']) ?>" alt="">
          <?php else: ?>
            <i class="bi bi-person-fill"></i>
          <?php endif; ?>
        </span>
        <div class="flex-grow-1">
          <label class="form-label-mini" for="photo">Photo de profil</label>
          <input type="file" class="form-control-auth" id="photo" name="photo" accept="image/png,image/jpeg,image/gif,image/webp">
        </div>
      </div>

      <div class="row g-3">
        <div class="col-sm-6">
          <label class="form-label-mini" for="name">Nom complet</label>
          <input type="text" class="form-control-auth" id="name" name="name" value="<?= e($currentUser['name']) ?>" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="ninu">Numéro NINU</label>
          <input type="text" class="form-control-auth" id="ninu" name="ninu" value="<?= e($currentUser['ninu']) ?>" required inputmode="numeric" pattern="[0-9]{10}" maxlength="10" title="Le numéro NINU doit contenir exactement 10 chiffres.">
          <p style="color:var(--gris-texte);font-size:.72rem;margin:.3rem 0 0;">Exactement 10 chiffres, sans espace ni lettre.</p>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="sexe">Sexe</label>
          <select class="form-control-auth" id="sexe" name="sexe" required>
            <?php foreach (['Homme', 'Femme', 'Autre'] as $opt): ?>
              <option value="<?= $opt ?>" <?= ($currentUser['sexe'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="age">Âge</label>
          <input type="number" class="form-control-auth" id="age" name="age" min="18" max="120" value="<?= e((string) ($currentUser['age'] ?? '')) ?>" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="ville">Ville</label>
          <input type="text" class="form-control-auth" id="ville" name="ville" value="<?= e($currentUser['ville']) ?>" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="pays">Pays</label>
          <input type="text" class="form-control-auth" id="pays" name="pays" value="<?= e($currentUser['pays'] ?? 'Haïti') ?>" required>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="phone">Téléphone</label>
          <input type="tel" class="form-control-auth" id="phone" name="phone" value="<?= e($currentUser['phone']) ?>" required inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
          <p style="color:var(--gris-texte);font-size:.72rem;margin:.3rem 0 0;">Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.</p>
        </div>
        <div class="col-sm-6">
          <label class="form-label-mini" for="email">Email</label>
          <input type="email" class="form-control-auth" id="email" name="email" value="<?= e($currentUser['email']) ?>" required>
        </div>
      </div>

      <button type="submit" class="mini-btn w-100 mt-3"><i class="bi bi-check-circle"></i> Enregistrer les modifications</button>
    </form>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
