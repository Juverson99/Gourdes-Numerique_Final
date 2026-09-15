<?php
$pageTitle      = 'Gestion des billets — Administration';
$activeAdminNav = 'billets';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Gestion des billets <span style="color:var(--gris-texte);font-weight:400;">(<?= count($billets) ?>)</span></h2>
    <a href="<?= url('/admin/billets/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouveau billet</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Billet enregistré avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Ces coupures sont celles que les clients touchent pour composer un montant sur la page
    <strong>Envoyer</strong>, avec leur image recto (avant) et verso (arrière). Seuls les billets
    <span class="badge-admin badge-in">actifs</span> y sont affichés.
  </p>

  <?php if (empty($billets)): ?>
    <div class="admin-empty">Aucun billet enregistré pour le moment.</div>
  <?php else: ?>
  <?php $categoryNames = array_column($categories, 'name', 'id'); ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Aperçu</th>
        <th>Recto / Verso</th>
        <th>Catégorie</th>
        <th>Valeur unitaire</th>
        <th>Stock</th>
        <th>Ordre</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($billets as $b): ?>
      <tr>
        <td>
          <?php if (!empty($b['image_path'])): ?>
            <span class="billet-swatch billet-swatch-img">
              <img src="<?= e(url($b['image_path'])) ?>" alt="Billet <?= e((string) $b['unit_value']) ?> HTG">
            </span>
          <?php else: ?>
            <span class="billet-swatch" style="--bill-c1:<?= e($b['color_start']) ?>; --bill-c2:<?= e($b['color_end']) ?>;">
              <?= (int) $b['unit_value'] ?>
            </span>
          <?php endif; ?>
        </td>
        <td>
          <div class="d-flex gap-1">
            <span class="badge-admin <?= !empty($b['image_path']) ? 'badge-in' : 'badge-out' ?>" title="Image recto">
              <i class="bi bi-image"></i> Recto <?= !empty($b['image_path']) ? '✓' : '—' ?>
            </span>
            <span class="badge-admin <?= !empty($b['image_verso_path']) ? 'badge-in' : 'badge-out' ?>" title="Image verso">
              <i class="bi bi-image"></i> Verso <?= !empty($b['image_verso_path']) ? '✓' : '—' ?>
            </span>
          </div>
        </td>
        <td><?= !empty($b['category_id']) && isset($categoryNames[$b['category_id']]) ? e($categoryNames[$b['category_id']]) : '<span style="color:var(--gris-texte);">—</span>' ?></td>
        <td class="mono"><?= money((float) $b['unit_value']) ?> HTG</td>
        <td class="mono" style="<?= (int) $b['stock_quantity'] <= 50 ? 'color:var(--rouge-haiti,#ce1126);font-weight:700;' : '' ?>"><?= number_format((int) $b['stock_quantity'], 0, ',', ' ') ?></td>
        <td class="mono"><?= (int) $b['sort_order'] ?></td>
        <td>
          <span class="badge-admin <?= (int) $b['active'] === 1 ? 'badge-in' : 'badge-out' ?>">
            <?= (int) $b['active'] === 1 ? 'Actif' : 'Masqué' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/billets/modifier?id=' . $b['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/billets/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= (int) $b['active'] === 1 ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i>
                <?= (int) $b['active'] === 1 ? 'Masquer' : 'Activer' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/billets/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement ce billet ?');">
              <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm danger"><i class="bi bi-trash3-fill"></i> Supprimer</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
