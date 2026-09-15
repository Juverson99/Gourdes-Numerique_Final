<?php
$pageTitle      = 'Dossier des employés — Administration';
$activeAdminNav = 'employes';
require VIEWS_PATH . '/layouts/admin_header.php';

$successMessages = [
    'cree'       => 'Employé créé avec succès.',
    'modifie'    => 'Employé modifié avec succès.',
    'promu'      => 'Le compte a été promu employé.',
    'retrograde' => "L'employé a été rétrogradé en client.",
    'supprime'   => 'Le compte employé a été supprimé.',
];
$errorMessages = [
    'self' => 'Impossible de modifier votre propre statut employé depuis cette page.',
];
?>

<?php if ($success && isset($successMessages[$success])): ?>
  <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($successMessages[$success]) ?></div>
<?php endif; ?>
<?php if ($error && isset($errorMessages[$error])): ?>
  <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($errorMessages[$error]) ?></div>
<?php endif; ?>

<div class="alert alert-info d-flex align-items-center gap-2 mb-3" style="border-radius:var(--radius-md);font-size:.82rem;">
  <i class="bi bi-info-circle-fill"></i>
  Un employé peut se connecter à l'espace administration (même page de connexion que les administrateurs), mais son
  accès reste toujours limité aux modules cochés dans son formulaire — jamais un accès complet, et jamais la gestion
  des administrateurs ou des employés eux-mêmes.
</div>

<div class="admin-card mb-3">
  <div class="admin-card-header">
    <h2><i class="bi bi-person-plus-fill"></i> Promouvoir un client existant</h2>
  </div>
  <div class="p-3">
    <p style="color:var(--gris-texte);font-size:.85rem;">
      Cherchez un compte client par email, téléphone ou numéro NINU pour lui accorder l'accès employé,
      sans avoir à recréer un compte.
    </p>
    <form class="d-flex gap-2 flex-wrap" method="get" action="<?= url('/admin/employes') ?>">
      <input type="text" class="form-control-auth" style="max-width:340px;" name="promote" value="<?= e($promoteQuery ?? '') ?>" placeholder="Email, téléphone ou NINU du client…">
      <button type="submit" class="mini-btn btn-admin-header"><i class="bi bi-search"></i> Rechercher</button>
    </form>

    <?php if (!empty($promoteError)): ?>
      <div class="alert alert-danger mt-3 mb-0" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($promoteError) ?></div>
    <?php elseif (!empty($promoteResult)): ?>
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 p-3" style="background:var(--gris-fond);border-radius:var(--radius-md);">
        <div>
          <strong><?= e($promoteResult['name']) ?></strong>
          <div style="font-size:.8rem;color:var(--gris-texte);"><?= e($promoteResult['email']) ?> · <?= e($promoteResult['phone']) ?> · NINU <?= e($promoteResult['ninu']) ?></div>
        </div>
        <form method="post" action="<?= url('/admin/employes/promouvoir') ?>">
          <input type="hidden" name="id" value="<?= (int) $promoteResult['id'] ?>">
          <button type="submit" class="mini-btn btn-admin-header"><i class="bi bi-person-badge-fill"></i> Promouvoir employé</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Dossier des employés <span style="color:var(--gris-texte);font-weight:400;">(<?= count($employees) ?>)</span></h2>
    <div class="d-flex gap-2 flex-wrap">
      <form class="admin-search" method="get" action="<?= url('/admin/employes') ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, email, téléphone, NINU…">
        <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
      </form>
      <a href="<?= url('/admin/employes/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouvel employé</a>
    </div>
  </div>

  <?php if (empty($employees)): ?>
    <div class="admin-empty">Aucun employé ne correspond à cette recherche.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>NINU</th>
        <th>Téléphone</th>
        <th>Email</th>
        <th>Accès</th>
        <th>Employé depuis</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($employees as $emp): $isSelf = (int) $emp['id'] === (int) current_user_id(); $ePerms = User::sanitizeEmployeePermissions(User::permissionsArray($emp)); ?>
      <tr>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($emp['photo_path'])): ?>
              <img src="<?= e(url($emp['photo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($emp['name'])) ?>
            <?php endif; ?>
          </span>
          <?= e($emp['name']) ?>
          <?php if ($isSelf): ?><span class="badge-admin badge-in ms-1">Vous</span><?php endif; ?>
        </td>
        <td class="mono"><?= e($emp['ninu']) ?></td>
        <td><?= e($emp['phone']) ?></td>
        <td><?= e($emp['email']) ?></td>
        <td>
          <?php if (empty($ePerms)): ?>
            <span class="badge-admin badge-out"><i class="bi bi-shield-slash"></i> Aucun module</span>
          <?php else: ?>
            <span class="badge-admin badge-out" title="<?= e(implode(', ', array_map(fn($k) => $permissionModules[$k]['label'] ?? $k, $ePerms))) ?>"><i class="bi bi-shield-lock"></i> <?= count($ePerms) ?> module<?= count($ePerms) > 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </td>
        <td><?= date('d/m/Y', strtotime($emp['created_at'])) ?></td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/employes/modifier?id=' . $emp['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <?php if (!$isSelf): ?>
              <form method="post" action="<?= url('/admin/employes/retrograder') ?>" onsubmit="return confirm('Rétrograder <?= e(addslashes($emp['name'])) ?> en simple client ?');">
                <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
                <button type="submit" class="btn-outline-line btn-admin-sm"><i class="bi bi-person-dash-fill"></i> Rétrograder</button>
              </form>
              <form method="post" action="<?= url('/admin/employes/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement le compte de <?= e(addslashes($emp['name'])) ?> ?');">
                <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
                <button type="submit" class="btn-outline-line btn-admin-sm danger"><i class="bi bi-trash3-fill"></i> Supprimer</button>
              </form>
            <?php endif; ?>
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
