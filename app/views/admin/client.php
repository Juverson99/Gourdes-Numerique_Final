<?php
$pageTitle      = 'Fiche client — ' . $client['name'];
$activeAdminNav = 'clients';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<a href="<?= url('/admin/clients') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour au dossier des clients</a>

<?php if (!empty($_GET['success'])): ?>
  <div class="alert alert-success mb-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Informations du client mises à jour avec succès.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2>Informations du client</h2>
        <a href="<?= url('/admin/clients/modifier?id=' . $client['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
      </div>
      <div class="p-3">
        <div class="d-flex align-items-center gap-3 mb-3">
          <span class="signup-photo-preview" style="width:56px;height:56px;">
            <?php if (!empty($client['photo_path'])): ?>
              <img src="<?= e(url($client['photo_path'])) ?>" alt="">
            <?php else: ?>
              <i class="bi bi-person-fill"></i>
            <?php endif; ?>
          </span>
          <div>
            <strong style="font-size:1.05rem;"><?= e($client['name']) ?></strong>
            <div style="font-size:.8rem;color:var(--gris-texte);">Client NINU <?= e($client['ninu']) ?></div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Nom complet</small><br><strong><?= e($client['name']) ?></strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Numéro NINU</small><br><strong class="mono"><?= e($client['ninu']) ?></strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Sexe / Âge</small><br><strong><?= e($client['sexe']) ?>, <?= (int) $client['age'] ?> ans</strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Ville / Pays</small><br><strong><?= e($client['ville']) ?>, <?= e($client['pays']) ?></strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Téléphone</small><br><strong><?= e($client['phone']) ?></strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Email</small><br><strong><?= e($client['email']) ?></strong></div>
          <div class="col-sm-6"><small style="color:var(--gris-texte);">Compte créé le</small><br><strong><?= date('d/m/Y à H:i', strtotime($client['created_at'])) ?></strong></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-stat-card h-auto mb-3">
      <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
      <div class="stat-label">Solde actuel</div>
      <div class="stat-value"><?= money($client['balance']) ?> HTG</div>
      <a href="<?= url('/admin/billet?client_id=' . $client['id']) ?>" class="btn-gold btn-admin-header mt-3"><i class="bi bi-plus-lg"></i> Ajouter un billet</a>
    </div>
    <div class="admin-stat-card h-auto">
      <div class="stat-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <div class="stat-label">Solde épargne</div>
      <div class="stat-value"><?= money($client['epargne_balance'] ?? 0) ?> HTG</div>
      <a href="<?= url('/admin/epargne?client_id=' . $client['id']) ?>" class="btn-outline-line btn-admin-header mt-3"><i class="bi bi-arrow-left-right"></i> Gérer l'épargne</a>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><h2>Historique des opérations</h2></div>
  <?php if (empty($operations)): ?>
    <div class="admin-empty">Ce client n'a encore effectué aucune opération.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead><tr><th>Date</th><th>Type</th><th>Détail</th><th>Sens</th><th>Montant</th><th>Statut</th></tr></thead>
    <tbody>
    <?php foreach ($operations as $op): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($op['created_at'])) ?></td>
        <td><?= e(ucfirst($op['type'])) ?></td>
        <td>
          <?php if ($op['type'] === 'envoi'): ?>
            <?= $op['sens'] === 'entrant' ? 'De ' . e($op['sender_name'] ?? '—') : 'Vers ' . e($op['receiver_name'] ?? '—') ?>
          <?php elseif ($op['type'] === 'depot'): ?>
            <?= e($op['note'] ?? 'Dépôt administrateur') ?>
          <?php else: ?>
            <?= e($op['category'] ?? '—') ?>
          <?php endif; ?>
        </td>
        <td><span class="badge-admin <?= $op['sens'] === 'entrant' ? 'badge-in' : 'badge-out' ?>"><?= $op['sens'] === 'entrant' ? 'Reçu' : 'Envoyé' ?></span></td>
        <td class="mono"><?= $op['sens'] === 'entrant' ? '+' : '−' ?><?= money($op['amount']) ?> HTG</td>
        <td><?= e(ucfirst($op['status'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
