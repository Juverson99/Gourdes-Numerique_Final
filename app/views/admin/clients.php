<?php
$pageTitle      = 'Dossier des clients — Administration';
$activeAdminNav = 'clients';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Dossier des clients <span style="color:var(--gris-texte);font-weight:400;">(<?= count($clients) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/clients') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, email, téléphone, NINU…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (empty($clients)): ?>
    <div class="admin-empty">Aucun client ne correspond à cette recherche.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>NINU</th>
        <th>Ville / Pays</th>
        <th>Téléphone</th>
        <th>Email</th>
        <th>Solde</th>
        <th>Inscrit le</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($clients as $c): ?>
      <tr>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($c['photo_path'])): ?>
              <img src="<?= e(url($c['photo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($c['name'])) ?>
            <?php endif; ?>
          </span>
          <?= e($c['name']) ?>
        </td>
        <td class="mono"><?= e($c['ninu']) ?></td>
        <td><?= e($c['ville']) ?>, <?= e($c['pays']) ?></td>
        <td><?= e($c['phone']) ?></td>
        <td><?= e($c['email']) ?></td>
        <td class="mono"><?= money($c['balance']) ?> HTG</td>
        <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/client?id=' . $c['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-eye-fill"></i> Voir la fiche</a>
            <a href="<?= url('/admin/clients/modifier?id=' . $c['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
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
