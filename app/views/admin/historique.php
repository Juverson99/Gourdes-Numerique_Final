<?php
$pageTitle      = 'Historique — Toutes les opérations — Administration';
$activeAdminNav = 'historique';

$typeLabels = [
    'envoi'            => 'Envoi entre clients',
    'reception'        => 'Réception',
    'paiement'         => 'Paiement',
    'depot'            => 'Billet admin',
    'epargne_depot'    => 'Dépôt épargne',
    'epargne_retrait'  => 'Retrait épargne',
    'moncash_depot'    => 'Dépôt MonCash',
    'retrait_bancaire' => 'Retrait bancaire',
    'retrait_especes'  => 'Retrait en espèces',
];

require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Historique global <span style="color:var(--gris-texte);font-weight:400;">(<?= count($operations) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/historique') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, référence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (empty($operations)): ?>
    <div class="admin-empty">Aucune opération enregistrée pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Référence</th>
        <th>Expéditeur</th>
        <th>Destinataire</th>
        <th>Type</th>
        <th>Montant</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($operations as $op):
        $sortant = in_array($op['type'], ['paiement', 'epargne_depot', 'retrait_bancaire', 'retrait_especes'], true) || ($op['type'] === 'envoi' && !empty($op['sender_id']));
    ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($op['created_at'])) ?></td>
        <td class="mono"><?= e($op['reference']) ?></td>
        <td>
          <?php if (!empty($op['sender_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $op['sender_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($op['sender_name'] ?? '—') ?></a>
          <?php else: ?><span style="color:var(--gris-texte);">Administration</span><?php endif; ?>
        </td>
        <td>
          <?php if (!empty($op['receiver_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $op['receiver_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($op['receiver_name'] ?? '—') ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><span class="badge-admin <?= $sortant ? 'badge-out' : 'badge-in' ?>"><?= e($typeLabels[$op['type']] ?? ucfirst($op['type'])) ?></span></td>
        <td class="mono"><?= $sortant ? '-' : '+' ?><?= money($op['amount']) ?> HTG</td>
        <td><?= e(ucfirst($op['status'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
