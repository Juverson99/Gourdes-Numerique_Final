<?php
$pageTitle      = 'Ajouter un billet — Administration';
$activeAdminNav = 'billet';
$preselectId    = (int) ($_GET['client_id'] ?? 0);
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="admin-card">
      <div class="admin-card-header"><h2><i class="bi bi-cash-coin"></i> Ajouter un billet pour un client</h2></div>

      <div class="p-3">
        <p style="color:var(--gris-texte);font-size:.88rem;">
          Cette opération crédite directement le solde d'un client (dépôt manuel — ex. dépôt en agence,
          correction, bonus). L'opération est enregistrée dans son historique comme un dépôt administrateur.
        </p>

        <?php if ($success): ?>
          <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/admin/billet') ?>">
          <div class="mb-3">
            <label class="form-label-mini" for="client_id">Client</label>
            <select class="form-control-auth" id="client_id" name="client_id" required>
              <option value="" disabled <?= $preselectId ? '' : 'selected' ?>>Choisir un client…</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $preselectId === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?> — <?= e($c['ninu']) ?> (<?= money($c['balance']) ?> HTG)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="amount">Montant du billet (HTG)</label>
            <input type="number" class="form-control-auth" id="amount" name="amount" min="1" step="0.01" placeholder="Ex : 2000.00" required>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="note">Note (optionnel)</label>
            <input type="text" class="form-control-auth" id="note" name="note" placeholder="Ex : Dépôt en agence Delmas 31">
          </div>

          <button type="submit" class="mini-btn w-100 mt-2"><i class="bi bi-plus-lg"></i> Ajouter le billet</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
