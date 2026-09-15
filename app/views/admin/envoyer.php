<?php
$pageTitle      = 'Envoyer — Tous les clients — Administration';
$activeAdminNav = 'envoyer';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Envois entre clients <span style="color:var(--gris-texte);font-weight:400;">(<?= count($envois) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/envoyer') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, référence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (empty($envois)): ?>
    <div class="admin-empty">Aucun envoi enregistré pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Référence</th>
        <th>Expéditeur</th>
        <th>Destinataire</th>
        <th>Montant</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($envois as $t): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
        <td class="mono"><?= e($t['reference']) ?></td>
        <td>
          <a href="<?= url('/admin/client?id=' . $t['sender_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($t['sender_name'] ?? '—') ?></a>
        </td>
        <td>
          <?php if (!empty($t['receiver_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $t['receiver_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($t['receiver_name'] ?? '—') ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="mono">-<?= money($t['amount']) ?> HTG</td>
        <td><?= e(ucfirst($t['status'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
