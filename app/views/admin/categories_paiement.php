<?php
$pageTitle      = 'Catégories de paiement — Administration';
$activeAdminNav = 'categories-paiement';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Catégories de paiement <span style="color:var(--gris-texte);font-weight:400;">(<?= count($categories) ?>)</span></h2>
    <a href="<?= url('/admin/categories-paiement/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouvelle catégorie</a>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Catégorie enregistrée avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Ces catégories sont affichées en « chips » sur la page publique <a href="<?= url('/paiement') ?>">Paiement</a>
    (« Payez marchands et factures en un tap »). Seules les catégories <strong>actives</strong> apparaissent côté client.
    Supprimer une catégorie n'efface pas l'historique des paiements déjà effectués sous ce libellé.
  </p>

  <?php if (empty($categories)): ?>
    <div class="admin-empty">Aucune catégorie enregistrée pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th></th>
        <th>Nom</th>
        <th>Paiements enregistrés</th>
        <th>Ordre</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($categories as $c): $n = $paymentCounts[$c['id']] ?? 0; ?>
      <tr>
        <td><span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:var(--bleu-clair,#eaf2fb);color:var(--bleu-nuit);font-size:1.05rem;"><i class="bi <?= e($c['icon']) ?>"></i></span></td>
        <td style="font-weight:600;"><?= e($c['name']) ?></td>
        <td class="mono"><?= $n ?> paiement<?= $n > 1 ? 's' : '' ?></td>
        <td class="mono"><?= (int) $c['sort_order'] ?></td>
        <td>
          <span class="badge-admin <?= (int) $c['active'] === 1 ? 'badge-in' : 'badge-out' ?>">
            <?= (int) $c['active'] === 1 ? 'Active' : 'Archivée' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/categories-paiement/modifier?id=' . $c['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/categories-paiement/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= (int) $c['active'] === 1 ? 'bi-archive-fill' : 'bi-arrow-counterclockwise' ?>"></i>
                <?= (int) $c['active'] === 1 ? 'Archiver' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/categories-paiement/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement cette catégorie ?');">
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
