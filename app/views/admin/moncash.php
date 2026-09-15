<?php
$pageTitle      = 'Transactions MonCash — Administration';
$activeAdminNav = 'moncash';
require VIEWS_PATH . '/layouts/admin_header.php';

$statusBadge = [
    'reussi'     => ['class' => 'badge-in',  'label' => 'Réussi'],
    'en_attente' => ['class' => 'badge-out', 'label' => 'En attente'],
    'echec'      => ['class' => 'badge-out', 'label' => 'Échec'],
];
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-phone-fill"></i></div>
      <div class="stat-label">Total confirmé (réussi)</div>
      <div class="stat-value" style="font-size:1.1rem;"><?= money($stats['total_reussi']) ?> HTG</div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
      <div class="stat-label">Dépôts réussis</div>
      <div class="stat-value"><?= $stats['nb_reussi'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div class="stat-label">En attente de confirmation</div>
      <div class="stat-value"><?= $stats['nb_en_attente'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
      <div class="stat-label">Échecs</div>
      <div class="stat-value"><?= $stats['nb_echec'] ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Transactions MonCash <span style="color:var(--gris-texte);font-weight:400;">(<?= count($moncash) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/moncash') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, NINU, référence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <?php if (!empty($verifySuccess)): ?>
    <div class="alert alert-success" style="border-radius:var(--radius-md);margin:0 1.2rem 1rem;"><i class="bi bi-check-circle-fill"></i> Vérification réussie — <?= e($verifySuccess) ?>.</div>
  <?php endif; ?>
  <?php if (!empty($verifyError)): ?>
    <div class="alert alert-danger" style="border-radius:var(--radius-md);margin:0 1.2rem 1rem;"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($verifyError) ?></div>
  <?php endif; ?>

  <?php if (empty($moncash)): ?>
    <div class="admin-empty">Aucune transaction MonCash enregistrée pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Référence</th>
        <th>Client</th>
        <th>Montant</th>
        <th>Statut</th>
        <th>Note</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($moncash as $m): $badge = $statusBadge[$m['status']] ?? ['class' => 'badge-out', 'label' => ucfirst($m['status'])]; ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
        <td class="mono"><?= e($m['reference']) ?></td>
        <td>
          <?php if (!empty($m['receiver_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $m['receiver_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($m['receiver_name'] ?? '—') ?></a>
            <div style="color:var(--gris-texte);font-size:.75rem;"><?= e($m['receiver_ninu'] ?? '') ?></div>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td class="mono">+<?= money($m['amount']) ?> HTG</td>
        <td><span class="badge-admin <?= e($badge['class']) ?>"><?= e($badge['label']) ?></span></td>
        <td style="color:var(--gris-texte);font-size:.8rem;"><?= $m['note'] ? e($m['note']) : '—' ?></td>
        <td>
          <?php if ($m['status'] === 'en_attente'): ?>
          <div class="admin-actions">
            <form method="post" action="<?= url('/admin/moncash/verifier') ?>" onsubmit="return confirm('Vérifier ce dépôt auprès de MonCash ?');">
              <input type="hidden" name="reference" value="<?= e($m['reference']) ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm"><i class="bi bi-arrow-repeat"></i> Vérifier maintenant</button>
            </form>
          </div>
          <?php else: ?>
          —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
