<?php
$pageTitle      = 'Paiement — Tous les clients — Administration';
$activeAdminNav = 'paiement';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Paiements de tous les clients <span style="color:var(--gris-texte);font-weight:400;">(<?= count($paiements) ?>)</span></h2>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <a href="<?= url('/admin/categories-paiement') ?>" class="mini-btn-outline btn-admin-header"><i class="bi bi-tags-fill"></i> Catégories de paiement</a>
      <form class="admin-search" method="get" action="<?= url('/admin/paiement') ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, référence, catégorie…">
        <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
      </form>
    </div>
  </div>

  <?php if (empty($paiements)): ?>
    <div class="admin-empty">Aucun paiement enregistré pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Référence</th>
        <th>Client</th>
        <th>Catégorie</th>
        <th>Réf. marchand</th>
        <th>Montant</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($paiements as $p): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
        <td class="mono"><?= e($p['reference']) ?></td>
        <td>
          <?php if (!empty($p['sender_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $p['sender_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($p['sender_name'] ?? '—') ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><span class="badge-admin badge-out"><?= e($p['category'] ?? 'Marchand') ?></span></td>
        <td class="mono"><?= e($p['note'] ?? '—') ?></td>
        <td class="mono">-<?= money($p['amount']) ?> HTG</td>
        <td><?= e(ucfirst($p['status'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
