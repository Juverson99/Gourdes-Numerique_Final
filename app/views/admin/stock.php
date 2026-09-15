<?php
$pageTitle      = 'Stock des billets — Administration';
$activeAdminNav = 'stock';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-boxes"></i></div>
      <div class="stat-label">Billets en stock (toutes coupures)</div>
      <div class="stat-value"><?= number_format($totalStockCount, 0, ',', ' ') ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-label">Valeur totale du stock</div>
      <div class="stat-value" style="font-size:1.15rem;"><?= money($totalStockValue) ?> HTG</div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div class="stat-label">Coupures en stock faible (≤ <?= $seuilAlerte ?>)</div>
      <div class="stat-value"><?= count($stockFaible) ?></div>
    </div>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success mb-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Stock mis à jour avec succès.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger mb-3" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> Ajustement invalide : coupure introuvable ou quantité nulle.</div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Stock par coupure <span style="color:var(--gris-texte);font-weight:400;">(<?= count($billets) ?>)</span></h2>
    <a href="<?= url('/admin/billets') ?>" class="btn-outline-line" style="padding:.35rem .8rem;font-size:.75rem;"><i class="bi bi-wallet2"></i> Gestion des billets</a>
  </div>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Le stock représente la quantité de billets (ou de paquets) disponibles en réserve pour chaque coupure.
    Chaque ajustement est enregistré dans le <a href="<?= url('/admin/journal') ?>">journal d'activité</a>.
  </p>

  <?php if (empty($billets)): ?>
    <div class="admin-empty">Aucune coupure enregistrée pour le moment. <a href="<?= url('/admin/billets/nouveau') ?>">Créer un billet</a> pour commencer à suivre son stock.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Aperçu</th>
        <th>Coupure</th>
        <th>Stock actuel</th>
        <th>Valeur en stock</th>
        <th>Ajuster</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($billets as $b): $stockValue = BankNote::totalValue($b) * (int) $b['stock_quantity']; $faible = (int) $b['stock_quantity'] <= $seuilAlerte; ?>
      <tr>
        <td>
          <?php if (!empty($b['image_path'])): ?>
            <span class="billet-swatch billet-swatch-img">
              <img src="<?= e(url($b['image_path'])) ?>" alt="Billet <?= e((string) $b['unit_value']) ?> HTG">
            </span>
          <?php else: ?>
            <span class="billet-swatch" style="--bill-c1:<?= e($b['color_start']) ?>; --bill-c2:<?= e($b['color_end']) ?>;">
              <?= (int) $b['is_bundle'] === 1 ? '×' . (int) $b['bundle_qty'] : (int) $b['unit_value'] ?>
            </span>
          <?php endif; ?>
        </td>
        <td>
          <div class="mono" style="font-weight:600;"><?= money((float) $b['unit_value']) ?> HTG</div>
          <div style="font-size:.75rem;color:var(--gris-texte);"><?= (int) $b['is_bundle'] === 1 ? 'Paquet de ' . (int) $b['bundle_qty'] : 'Billet simple' ?></div>
        </td>
        <td>
          <span class="mono" style="font-weight:700;<?= $faible ? 'color:var(--rouge-haiti,#ce1126);' : '' ?>"><?= number_format((int) $b['stock_quantity'], 0, ',', ' ') ?></span>
          <?php if ($faible): ?><div><span class="badge-admin badge-out" style="font-size:.65rem;">Stock faible</span></div><?php endif; ?>
        </td>
        <td class="mono"><?= money($stockValue) ?> HTG</td>
        <td>
          <form method="post" action="<?= url('/admin/stock/ajuster') ?>" class="d-flex flex-wrap gap-2 align-items-center" style="min-width:280px;">
            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <input type="number" name="delta" placeholder="Ex : 100 ou -50" required
                   style="width:130px;" class="form-control-auth" step="1">
            <input type="text" name="motif" placeholder="Motif (facultatif)" maxlength="120"
                   style="width:150px;" class="form-control-auth">
            <button type="submit" class="btn-outline-line btn-admin-sm"><i class="bi bi-arrow-repeat"></i> Ajuster</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
