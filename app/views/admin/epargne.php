<?php
$pageTitle      = 'Épargne — Administration';
$activeAdminNav = 'epargne';
$preselectId    = (int) ($_GET['client_id'] ?? 0);
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <div class="stat-label">Total épargné (clients)</div>
      <div class="stat-value" style="font-size:1.15rem;"><?= money($stats['total_epargne']) ?> HTG</div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-4">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div class="stat-label">Clients</div>
      <div class="stat-value"><?= count($clients) ?></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="admin-card">
      <div class="admin-card-header"><h2><i class="bi bi-piggy-bank-fill"></i> Déposer / retirer</h2></div>

      <div class="p-3">
        <p style="color:var(--gris-texte);font-size:.85rem;">
          Déplace un montant entre le solde courant d'un client et son solde épargne.
          Un dépôt débite le solde courant ; un retrait crédite le solde courant.
        </p>

        <?php if ($success): ?>
          <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/admin/epargne') ?>">
          <div class="mb-3">
            <label class="form-label-mini" for="client_id">Client</label>
            <select class="form-control-auth" id="client_id" name="client_id" required>
              <option value="" disabled <?= $preselectId ? '' : 'selected' ?>>Choisir un client…</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $preselectId === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?> — <?= e($c['ninu']) ?> (épargne : <?= money($c['epargne_balance']) ?> HTG)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label-mini">Opération</label>
            <div class="d-flex gap-3 flex-wrap">
              <label class="d-flex align-items-center gap-2" style="font-size:.85rem;">
                <input type="radio" name="direction" value="depot" checked> Dépôt (solde → épargne)
              </label>
              <label class="d-flex align-items-center gap-2" style="font-size:.85rem;">
                <input type="radio" name="direction" value="retrait"> Retrait (épargne → solde)
              </label>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="amount">Montant (HTG)</label>
            <input type="number" class="form-control-auth" id="amount" name="amount" min="1" step="0.01" placeholder="Ex : 1000.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="note">Note (optionnel)</label>
            <input type="text" class="form-control-auth" id="note" name="note" placeholder="Ex : Objectif rentrée scolaire">
          </div>

          <button type="submit" class="mini-btn w-100 mt-2"><i class="bi bi-arrow-left-right"></i> Valider l'opération</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <h2>Soldes épargne des clients</h2>
        <form class="admin-search" method="get" action="<?= url('/admin/epargne') ?>">
          <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher un client…">
          <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
        </form>
      </div>
      <?php if (empty($clients)): ?>
        <div class="admin-empty">Aucun client inscrit pour le moment.</div>
      <?php else: ?>
      <div style="overflow-x:auto;max-height:420px;overflow-y:auto;">
      <table class="admin-table">
        <thead><tr><th>Client</th><th>NINU</th><th>Solde épargne</th></tr></thead>
        <tbody>
        <?php foreach ($clients as $c): ?>
          <tr>
            <td><a href="<?= url('/admin/client?id=' . $c['id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($c['name']) ?></a></td>
            <td class="mono"><?= e($c['ninu']) ?></td>
            <td class="mono"><?= money($c['epargne_balance']) ?> HTG</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <div class="admin-card-header"><h2>Derniers mouvements d'épargne</h2></div>
      <?php if (empty($mouvements)): ?>
        <div class="admin-empty">Aucun mouvement d'épargne enregistré pour le moment.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Client</th><th>Type</th><th>Montant</th></tr></thead>
        <tbody>
        <?php foreach ($mouvements as $m): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
            <td><?= e($m['receiver_name'] ?? '—') ?></td>
            <td><span class="badge-admin <?= $m['type'] === 'epargne_depot' ? 'badge-in' : 'badge-out' ?>"><?= $m['type'] === 'epargne_depot' ? 'Dépôt' : 'Retrait' ?></span></td>
            <td class="mono"><?= $m['type'] === 'epargne_depot' ? '+' : '-' ?><?= money($m['amount']) ?> HTG</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
