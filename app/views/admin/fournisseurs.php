<?php
$pageTitle      = 'Fournisseurs — Administration';
$activeAdminNav = 'fournisseurs';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Fournisseurs enregistrés</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['total'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Actifs</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--vert-succes, #1c8a4b);"><?= $counts['actifs'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Suspendus</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--rouge-haiti, #ce1126);"><?= $counts['suspendus'] ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Fournisseurs <span style="color:var(--gris-texte);font-weight:400;">(<?= count($fournisseurs) ?>)</span></h2>
    <div class="d-flex align-items-center gap-2">
      <form class="admin-search" method="get" action="<?= url('/admin/fournisseurs') ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, NIF, ville…">
        <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
      </form>
      <a href="<?= url('/admin/fournisseurs/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouveau fournisseur</a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Fournisseur enregistré avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Répertoire des fournisseurs de la plateforme : fournisseurs de billets/liquidités pour le
    réapprovisionnement du stock, imprimeurs, prestataires de matériel ou de services. Seuls les
    fournisseurs <span class="badge-admin badge-in">actifs</span> sont considérés comme partenaires opérationnels.
  </p>

  <?php if (empty($fournisseurs)): ?>
    <div class="admin-empty"><?= $search !== '' ? 'Aucun fournisseur ne correspond à cette recherche.' : 'Aucun fournisseur enregistré pour le moment.' ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Fournisseur</th>
        <th>Catégorie</th>
        <th>NIF / Matricule</th>
        <th>Contact</th>
        <th>Ville</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($fournisseurs as $sup): ?>
      <tr>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($sup['logo_path'])): ?>
              <img src="<?= e(url($sup['logo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($sup['name'])) ?>
            <?php endif; ?>
          </span>
          <?= e($sup['name']) ?>
        </td>
        <td><?= e($categoryLabels[$sup['category']] ?? $sup['category']) ?></td>
        <td class="mono"><?= $sup['tax_id'] ? e($sup['tax_id']) : '—' ?></td>
        <td>
          <?php if ($sup['contact_name'] || $sup['phone'] || $sup['email']): ?>
            <?= $sup['contact_name'] ? e($sup['contact_name']) . '<br>' : '' ?>
            <span style="color:var(--gris-texte);font-size:.78rem;">
              <?= e(trim(($sup['phone'] ?? '') . ($sup['phone'] && $sup['email'] ? ' · ' : '') . ($sup['email'] ?? ''))) ?>
            </span>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?= $sup['ville'] ? e($sup['ville']) : '—' ?></td>
        <td>
          <span class="badge-admin <?= $sup['status'] === 'actif' ? 'badge-in' : 'badge-out' ?>">
            <?= $sup['status'] === 'actif' ? 'Actif' : 'Suspendu' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/fournisseurs/modifier?id=' . $sup['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/fournisseurs/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $sup['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= $sup['status'] === 'actif' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                <?= $sup['status'] === 'actif' ? 'Suspendre' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/fournisseurs/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement ce fournisseur ?');">
              <input type="hidden" name="id" value="<?= (int) $sup['id'] ?>">
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
