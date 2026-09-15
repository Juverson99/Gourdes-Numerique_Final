<?php
$pageTitle      = 'Agences BNC — Administration';
$activeAdminNav = 'agences-bnc';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Agences enregistrées</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['total'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Visibles sur la carte publique</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--vert-succes, #1c8a4b);"><?= $counts['actives'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Départements couverts</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--or-fonce,#a8791f);"><?= $counts['departements'] ?> / 10</div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Agences BNC <span style="color:var(--gris-texte);font-weight:400;">(<?= count($agences) ?>)</span></h2>
    <div class="d-flex align-items-center gap-2">
      <form class="admin-search" method="get" action="<?= url('/admin/agences-bnc') ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, ville, département…">
        <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
      </form>
      <a href="<?= url('/admin/agences-bnc/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouvelle agence</a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Agence enregistrée avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Répertoire des agences de la Banque Nationale de Crédit (BNC), affichées sur la carte interactive publique
    (<a href="<?= url('/agences-bnc') ?>" target="_blank">voir la page →</a>). Seules les agences
    <span class="badge-admin badge-in">visibles</span> apparaissent sur la carte.
  </p>

  <?php if (empty($agences)): ?>
    <div class="admin-empty"><?= $search !== '' ? 'Aucune agence ne correspond à cette recherche.' : 'Aucune agence enregistrée pour le moment.' ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Agence</th>
        <th>Département</th>
        <th>Ville</th>
        <th>Coordonnées</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($agences as $a): ?>
      <tr>
        <td style="font-weight:600;">
          <?= e($a['nom']) ?>
          <?php if ((int) $a['principale'] === 1): ?><span class="badge-admin" style="background:rgba(212,175,55,.15);color:var(--or-fonce,#a8791f);margin-left:.3rem;">Siège</span><?php endif; ?>
        </td>
        <td><?= e($a['departement']) ?></td>
        <td><?= e($a['ville']) ?></td>
        <td class="mono" style="font-size:.78rem;color:var(--gris-texte);"><?= number_format((float) $a['latitude'], 4) ?>, <?= number_format((float) $a['longitude'], 4) ?></td>
        <td>
          <span class="badge-admin <?= (int) $a['active'] === 1 ? 'badge-in' : 'badge-out' ?>">
            <?= (int) $a['active'] === 1 ? 'Visible' : 'Masquée' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/agences-bnc/modifier?id=' . $a['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/agences-bnc/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= (int) $a['active'] === 1 ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i>
                <?= (int) $a['active'] === 1 ? 'Masquer' : 'Afficher' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/agences-bnc/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement cette agence ?');">
              <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
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
