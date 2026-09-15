<?php
$pageTitle      = 'Recevoir — Tous les clients — Administration';
$activeAdminNav = 'recevoir';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Réceptions de tous les clients <span style="color:var(--gris-texte);font-weight:400;">(<?= count($receptions) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/recevoir') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, NINU, référence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (empty($receptions)): ?>
    <div class="admin-empty">Aucune réception enregistrée pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Référence</th>
        <th>Client (destinataire)</th>
        <th>Origine</th>
        <th>Type</th>
        <th>Montant</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($receptions as $r): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
        <td class="mono"><?= e($r['reference']) ?></td>
        <td>
          <?php if (!empty($r['receiver_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $r['receiver_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($r['receiver_name'] ?? '—') ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td>
          <?= $r['type'] === 'depot' ? 'Admin (billet)' : e($r['sender_name'] ?? 'Client') ?>
        </td>
        <td><span class="badge-admin badge-in"><?= $r['type'] === 'depot' ? 'Billet admin' : 'Envoi entre clients' ?></span></td>
        <td class="mono">+<?= money($r['amount']) ?> HTG</td>
        <td><?= e(ucfirst($r['status'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
