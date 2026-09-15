<?php
$pageTitle      = 'Alertes de stock — Administration';
$activeAdminNav = 'alertes-stock';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div class="stat-label">Coupures en alerte (≤ <?= $seuil ?>)</div>
      <div class="stat-value"><?= count($alertes) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-x-octagon-fill"></i></div>
      <div class="stat-label">En rupture totale (stock = 0)</div>
      <div class="stat-value"><?= count($ruptures) ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-label">Valeur restante sur ces coupures</div>
      <div class="stat-value" style="font-size:1.15rem;"><?= money($valeurEnRisque) ?> HTG</div>
    </div>
  </div>
</div>

<?php if ($success): ?>
  <div class="alert alert-success mb-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Stock mis à jour avec succès.</div>
<?php endif; ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2><i class="bi bi-bell-fill"></i> Alertes de stock</h2>
    <a href="<?= url('/admin/stock') ?>" class="btn-outline-line" style="padding:.35rem .8rem;font-size:.75rem;"><i class="bi bi-boxes"></i> Vue d'ensemble du stock</a>
  </div>

  <div style="padding:0 1.2rem 1rem;">
    <p style="color:var(--gris-texte);font-size:.85rem;margin-bottom:.8rem;">
      Cette page ne montre que les coupures actives dont le stock est descendu au seuil d'alerte ou en dessous.
      Ajustez le seuil selon vos besoins (par défaut : 50).
    </p>
    <form method="get" action="<?= url('/admin/alertes-stock') ?>" class="d-flex align-items-end gap-2 flex-wrap">
      <div>
        <label class="form-label-mini" for="seuil">Seuil d'alerte</label>
        <input type="number" class="form-control-auth" id="seuil" name="seuil" min="1" step="1" value="<?= (int) $seuil ?>" style="width:140px;">
      </div>
      <button type="submit" class="btn-outline-line" style="padding:.5rem 1rem;font-size:.8rem;"><i class="bi bi-funnel-fill"></i> Appliquer</button>
    </form>
  </div>

  <?php if (empty($alertes)): ?>
    <div class="admin-empty"><i class="bi bi-check-circle-fill text-success"></i> Aucune alerte : toutes les coupures actives ont un stock supérieur à <?= $seuil ?> unités.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Aperçu</th>
        <th>Coupure</th>
        <th>Stock actuel</th>
        <th>Sévérité</th>
        <th>Ajuster</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($alertes as $b): $qty = (int) $b['stock_quantity']; $rupture = $qty === 0; ?>
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
        <td class="mono" style="font-weight:700;color:var(--rouge-haiti,#ce1126);"><?= number_format($qty, 0, ',', ' ') ?></td>
        <td>
          <span class="badge-admin <?= $rupture ? 'badge-out' : 'badge-in' ?>" style="<?= $rupture ? 'background:rgba(206,17,38,.12);color:var(--rouge-haiti,#ce1126);' : 'background:rgba(212,175,55,.15);color:#8a6d1a;' ?>">
            <?= $rupture ? 'Rupture totale' : 'Stock faible' ?>
          </span>
        </td>
        <td>
          <form method="post" action="<?= url('/admin/stock/ajuster') ?>" class="d-flex flex-wrap gap-2 align-items-center" style="min-width:260px;">
            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
            <input type="hidden" name="_redirect" value="alertes">
            <input type="number" name="delta" placeholder="Ex : 200" required style="width:110px;" class="form-control-auth" step="1" min="1">
            <input type="text" name="motif" placeholder="Motif (facultatif)" maxlength="120" style="width:150px;" class="form-control-auth">
            <button type="submit" class="btn-outline-line btn-admin-sm"><i class="bi bi-arrow-repeat"></i> Réapprovisionner</button>
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
