<?php
$pageTitle      = 'Catégories de billets — Administration';
$activeAdminNav = 'categories-billets';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Catégories de billets <span style="color:var(--gris-texte);font-weight:400;">(<?= count($categories) ?>)</span></h2>
    <a href="<?= url('/admin/categories-billets/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouvelle catégorie</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Catégorie enregistrée avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Les catégories permettent de regrouper les coupures dans le
    <a href="<?= url('/admin/billets') ?>">dossier de gestion des billets</a> (ex. « Petites coupures », « Grosses coupures »).
    Une coupure peut n'appartenir à aucune catégorie. Supprimer une catégorie ne supprime pas les billets qui y sont rattachés —
    ils repassent simplement « Sans catégorie ».
  </p>

  <?php if (empty($categories)): ?>
    <div class="admin-empty">Aucune catégorie enregistrée pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Description</th>
        <th>Billets rattachés</th>
        <th>Ordre</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($categories as $c): $n = $noteCounts[$c['id']] ?? 0; ?>
      <tr>
        <td style="font-weight:600;"><?= e($c['name']) ?></td>
        <td style="color:var(--gris-texte);font-size:.85rem;"><?= $c['description'] ? e($c['description']) : '—' ?></td>
        <td class="mono"><?= $n ?> billet<?= $n > 1 ? 's' : '' ?></td>
        <td class="mono"><?= (int) $c['sort_order'] ?></td>
        <td>
          <span class="badge-admin <?= (int) $c['active'] === 1 ? 'badge-in' : 'badge-out' ?>">
            <?= (int) $c['active'] === 1 ? 'Active' : 'Archivée' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/categories-billets/modifier?id=' . $c['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/categories-billets/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= (int) $c['active'] === 1 ? 'bi-archive-fill' : 'bi-arrow-counterclockwise' ?>"></i>
                <?= (int) $c['active'] === 1 ? 'Archiver' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/categories-billets/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement cette catégorie ? Les billets rattachés repasseront « Sans catégorie ».');">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
