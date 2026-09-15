<?php
$pageTitle      = 'Retraits en espèces — Administration';
$activeAdminNav = 'retraits-especes';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Retraits en espèces <span style="color:var(--gris-texte);font-weight:400;">(<?= count($retraits) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/retraits-especes') ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, code, agence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Un client a demandé de convertir du billet électronique en billet <strong>physique</strong> à retirer en agence :
    le montant est déjà débité de son solde. Une fois les espèces remises au guichet, confirmez la demande.
    Si le client ne se présente pas (ou en cas d'erreur), annulez pour le rembourser automatiquement.
  </p>

  <?php if (empty($retraits)): ?>
    <div class="admin-empty">Aucune demande de retrait en espèces pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Code</th>
        <th>Client</th>
        <th>Agence</th>
        <th>Montant</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($retraits as $r): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
        <td class="mono" style="font-weight:700;"><?= e($r['reference']) ?></td>
        <td>
          <?php if (!empty($r['sender_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $r['sender_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($r['sender_name'] ?? '—') ?></a>
          <?php else: ?>—<?php endif; ?>
        </td>
        <td><?= e($r['category'] ?? '—') ?></td>
        <td class="mono">-<?= money($r['amount']) ?> HTG</td>
        <td>
          <?php if ($r['status'] === 'reussi'): ?>
            <span class="badge-admin badge-in">Remis</span>
          <?php elseif ($r['status'] === 'en_attente'): ?>
            <span class="badge-admin" style="background:#fff3cd;color:#8a6d3b;">En attente</span>
          <?php else: ?>
            <span class="badge-admin badge-out">Annulé</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($r['status'] === 'en_attente'): ?>
          <div class="admin-actions">
            <form method="post" action="<?= url('/admin/retraits-especes/confirmer') ?>">
              <input type="hidden" name="reference" value="<?= e($r['reference']) ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm"><i class="bi bi-check-lg"></i> Confirmer la remise</button>
            </form>
            <form method="post" action="<?= url('/admin/retraits-especes/annuler') ?>" onsubmit="return confirm('Annuler cette demande et rembourser le client ?');">
              <input type="hidden" name="reference" value="<?= e($r['reference']) ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm danger"><i class="bi bi-x-lg"></i> Annuler (rembourser)</button>
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
