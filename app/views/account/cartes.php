<?php
$pageTitle = 'Cartes virtuelles — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Cartes virtuelles</span>
    <h1>Payez en ligne avec une <span class="accent">carte virtuelle</span></h1>
    <p class="lead-text">Générez une carte virtuelle rattachée à votre solde Gourde Numérique, gelez-la ou supprimez-la à tout moment.</p>
  </div>
</section>

<section class="container" style="max-width:820px;padding-bottom:2.5rem;">
  <?php if ($success): ?>
    <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="info-card mb-4">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">Générer une nouvelle carte</div>
    <form method="post" action="<?= url('/cartes-virtuelles') ?>" class="d-flex gap-2 flex-wrap align-items-end">
      <input type="hidden" name="action" value="create">
      <div class="flex-grow-1" style="min-width:220px;">
        <label class="form-label-mini" for="label">Nom de la carte (optionnel)</label>
        <input type="text" class="form-control-auth" id="label" name="label" placeholder="Ex : Achats en ligne">
      </div>
      <button type="submit" class="mini-btn"><i class="bi bi-plus-circle"></i> Générer une carte</button>
    </form>
    <p style="color:var(--gris-texte);font-size:.8rem;margin-top:.6rem;margin-bottom:0;">Jusqu'à 5 cartes virtuelles actives par compte.</p>
  </div>

  <?php if (empty($cartes)): ?>
    <div class="info-card text-center" style="color:var(--gris-texte);">Vous n'avez pas encore de carte virtuelle.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($cartes as $c): ?>
        <div class="col-md-6">
          <div class="info-card carte-virtuelle">
            <div class="carte-virtuelle-watermark" aria-hidden="true"><span>BRH</span></div>
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="carte-brh-emblem">
                <span class="ring">BRH</span>
                <span style="font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;opacity:.8;"><?= e($c['label']) ?></span>
              </span>
              <span class="badge-admin <?= $c['status'] === 'active' ? 'badge-in' : 'badge-out' ?>"><?= $c['status'] === 'active' ? 'Active' : 'Gelée' ?></span>
            </div>
            <div style="font-family:var(--font-mono);font-size:1.15rem;letter-spacing:.05em;margin-bottom:1rem;"><?= e($c['card_number']) ?></div>
            <div class="d-flex justify-content-between" style="font-size:.8rem;opacity:.9;">
              <div>
                <div style="opacity:.7;font-size:.7rem;">TITULAIRE</div>
                <?= e($c['holder_name']) ?>
              </div>
              <div>
                <div style="opacity:.7;font-size:.7rem;">EXPIRE</div>
                <?= str_pad((string) $c['expiry_month'], 2, '0', STR_PAD_LEFT) ?>/<?= substr((string) $c['expiry_year'], -2) ?>
              </div>
              <div>
                <div style="opacity:.7;font-size:.7rem;">CVV</div>
                <?= e($c['cvv']) ?>
              </div>
            </div>
            <button type="button" class="card-vr-btn"
              data-label="<?= e($c['label']) ?>"
              data-number="<?= e($c['card_number']) ?>"
              data-holder="<?= e($c['holder_name']) ?>"
              data-expiry="<?= str_pad((string) $c['expiry_month'], 2, '0', STR_PAD_LEFT) ?>/<?= substr((string) $c['expiry_year'], -2) ?>"
              data-cvv="<?= e($c['cvv']) ?>"
              data-status="<?= $c['status'] === 'active' ? 'Active' : 'Gelée' ?>"
              title="Voir en réalité virtuelle 360°">
              <i class="bi bi-cube"></i>
              <span>360°</span>
            </button>
          </div>
          <div class="d-flex gap-2 mt-2">
            <form method="post" action="<?= url('/cartes-virtuelles') ?>" class="flex-grow-1">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="card_id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="mini-btn w-100" style="background:var(--gris-clair);color:var(--bleu-nuit);">
                <i class="bi bi-<?= $c['status'] === 'active' ? 'lock-fill' : 'unlock-fill' ?>"></i>
                <?= $c['status'] === 'active' ? 'Geler' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/cartes-virtuelles') ?>" onsubmit="return confirm('Supprimer définitivement cette carte ?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="card_id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="mini-btn" style="background:#ffe5e5;color:#b3261e;"><i class="bi bi-trash-fill"></i></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
<script src="<?= url('/assets/js/card-viewer-360.js') ?>"></script>
