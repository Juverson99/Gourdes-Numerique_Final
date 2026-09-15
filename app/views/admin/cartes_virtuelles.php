<?php
$pageTitle      = 'Cartes virtuelles — Administration';
$activeAdminNav = 'cartes-virtuelles';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Cartes générées</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['total'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Actives</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--vert-succes, #1c8a4b);"><?= $counts['actives'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Gelées</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--rouge-haiti, #ce1126);"><?= $counts['gelees'] ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Cartes virtuelles <span style="color:var(--gris-texte);font-weight:400;">(<?= count($cartes) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/cartes-virtuelles') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, NINU, numéro, libellé…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Mise à jour effectuée avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Toutes les cartes virtuelles générées par les clients depuis leur dossier « Cartes virtuelles »,
    tous comptes confondus. Une carte <span class="badge-admin badge-out">gelée</span> est bloquée
    côté client mais reste visible ici — un administrateur peut la réactiver ou la supprimer
    définitivement en cas d'abus ou de fraude suspectée.
  </p>

  <?php if (empty($cartes)): ?>
    <div class="admin-empty"><?= $search !== '' ? 'Aucune carte ne correspond à cette recherche.' : 'Aucune carte virtuelle générée pour le moment.' ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Client</th>
        <th>Carte</th>
        <th>Titulaire</th>
        <th>Expire</th>
        <th>Générée le</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($cartes as $c): ?>
      <tr>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($c['client_photo_path'])): ?>
              <img src="<?= e(url($c['client_photo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($c['client_name'] ?? '?')) ?>
            <?php endif; ?>
          </span>
          <a href="<?= url('/admin/client?id=' . $c['user_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($c['client_name']) ?></a>
          <?php if ($c['client_ninu']): ?>
            <div style="font-size:.75rem;color:var(--gris-texte);font-weight:400;" class="mono"><?= e($c['client_ninu']) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <span class="mono" style="font-weight:600;"><?= e(VirtualCard::masked($c['card_number'])) ?></span>
          <div style="font-size:.75rem;color:var(--gris-texte);"><?= e($c['label']) ?></div>
        </td>
        <td><?= e($c['holder_name']) ?></td>
        <td class="mono"><?= str_pad((string) $c['expiry_month'], 2, '0', STR_PAD_LEFT) ?>/<?= substr((string) $c['expiry_year'], -2) ?></td>
        <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
        <td>
          <span class="badge-admin <?= $c['status'] === 'active' ? 'badge-in' : 'badge-out' ?>">
            <?= $c['status'] === 'active' ? 'Active' : 'Gelée' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <form method="post" action="<?= url('/admin/cartes-virtuelles/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= $c['status'] === 'active' ? 'bi-snow' : 'bi-play-circle-fill' ?>"></i>
                <?= $c['status'] === 'active' ? 'Geler' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/cartes-virtuelles/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement cette carte virtuelle ?');">
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
