<?php
$pageTitle = 'Sécurité — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Sécurité</span>
    <h1>Protégez <span class="accent">votre compte</span></h1>
    <p class="lead-text">Gérez votre mot de passe et votre code PIN de sécurité.</p>
  </div>
</section>

<section class="container" style="max-width:640px;">
  <?php if ($success): ?>
    <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="info-card mb-4">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;"><i class="bi bi-key-fill"></i> Mot de passe</div>
    <form method="post" action="<?= url('/securite') ?>">
      <input type="hidden" name="action" value="password">
      <div class="mb-3">
        <label class="form-label-mini" for="current_password">Mot de passe actuel</label>
        <input type="password" class="form-control-auth" id="current_password" name="current_password" required>
      </div>
      <div class="mb-3">
        <label class="form-label-mini" for="new_password">Nouveau mot de passe</label>
        <input type="password" class="form-control-auth" id="new_password" name="new_password" minlength="6" required>
      </div>
      <div class="mb-3">
        <label class="form-label-mini" for="new_password_confirm">Confirmer le nouveau mot de passe</label>
        <input type="password" class="form-control-auth" id="new_password_confirm" name="new_password_confirm" minlength="6" required>
      </div>
      <button type="submit" class="mini-btn w-100"><i class="bi bi-check-circle"></i> Mettre à jour le mot de passe</button>
    </form>
  </div>

  <div class="info-card">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;"><i class="bi bi-grid-3x3-gap-fill"></i> Code PIN</div>
    <p style="color:var(--gris-texte);font-size:.85rem;">
      <?= $currentUser['pin_set'] ? 'Un code PIN est déjà défini sur votre compte.' : "Aucun code PIN n'est encore défini." ?>
    </p>
    <form method="post" action="<?= url('/securite') ?>">
      <input type="hidden" name="action" value="pin">
      <div class="mb-3">
        <label class="form-label-mini" for="pin">Nouveau code PIN (4 à 6 chiffres)</label>
        <input type="password" inputmode="numeric" pattern="\d{4,6}" class="form-control-auth" id="pin" name="pin" minlength="4" maxlength="6" required>
      </div>
      <div class="mb-3">
        <label class="form-label-mini" for="pin_confirm">Confirmer le code PIN</label>
        <input type="password" inputmode="numeric" pattern="\d{4,6}" class="form-control-auth" id="pin_confirm" name="pin_confirm" minlength="4" maxlength="6" required>
      </div>
      <button type="submit" class="mini-btn w-100"><i class="bi bi-shield-lock"></i> <?= $currentUser['pin_set'] ? 'Remplacer le code PIN' : 'Définir le code PIN' ?></button>
    </form>
    <?php if ($currentUser['pin_set']): ?>
      <form method="post" action="<?= url('/securite') ?>" class="mt-2" onsubmit="return confirm('Retirer le code PIN de votre compte ?');">
        <input type="hidden" name="action" value="pin_remove">
        <button type="submit" class="mini-btn w-100" style="background:#ffe5e5;color:#b3261e;"><i class="bi bi-x-circle"></i> Retirer le code PIN</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
