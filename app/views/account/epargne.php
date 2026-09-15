<?php
$pageTitle = 'Épargne — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Épargne</span>
    <h1>Mettez de côté vos <span class="accent">gourdes numériques</span></h1>
    <p class="lead-text">Déplacez des fonds entre votre solde courant et votre solde épargne, en toute simplicité.</p>
  </div>
</section>

<section class="container" style="max-width:760px;">
  <div class="row g-3 mb-4">
    <div class="col-sm-6">
      <div class="info-card">
        <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);">Solde courant</div>
        <div style="font-family:var(--font-mono);font-size:1.4rem;font-weight:700;color:var(--bleu-nuit);"><?= money($currentUser['balance']) ?> HTG</div>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="info-card">
        <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);">Solde épargne</div>
        <div style="font-family:var(--font-mono);font-size:1.4rem;font-weight:700;color:var(--bleu-nuit);"><?= money($currentUser['epargne_balance']) ?> HTG</div>
      </div>
    </div>
  </div>

  <div class="info-card mb-4">
    <?php if ($success): ?>
      <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= url('/epargne') ?>">
      <div class="mb-3">
        <label class="form-label-mini">Opération</label>
        <div class="d-flex gap-3 flex-wrap">
          <label class="d-flex align-items-center gap-2" style="font-size:.85rem;">
            <input type="radio" name="direction" value="depot" checked> Déposer (solde → épargne)
          </label>
          <label class="d-flex align-items-center gap-2" style="font-size:.85rem;">
            <input type="radio" name="direction" value="retrait"> Retirer (épargne → solde)
          </label>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label-mini" for="amount">Montant (HTG)</label>
        <input type="number" class="form-control-auth" id="amount" name="amount" min="1" step="0.01" placeholder="Ex : 500.00" required>
      </div>
      <button type="submit" class="mini-btn w-100 mt-2"><i class="bi bi-arrow-left-right"></i> Valider l'opération</button>
    </form>
  </div>

  <div class="info-card">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">Derniers mouvements</div>
    <?php if (empty($mouvements)): ?>
      <p style="color:var(--gris-texte);font-size:.9rem;">Aucun mouvement d'épargne pour le moment.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Type</th><th>Montant</th></tr></thead>
        <tbody>
        <?php foreach ($mouvements as $m): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
            <td><?= $m['type'] === 'epargne_depot' ? 'Dépôt' : 'Retrait' ?></td>
            <td class="mono"><?= $m['type'] === 'epargne_depot' ? '+' : '-' ?><?= money($m['amount']) ?> HTG</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
