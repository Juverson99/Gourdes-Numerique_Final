<?php
$pageTitle = 'Convertir — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Convertir</span>
    <h1>MonCash, banque <span class="accent">et billet physique</span></h1>
    <p class="lead-text">Convertissez votre argent MonCash en billet électronique, transférez votre solde vers un compte bancaire partenaire, ou retirez des billets physiques dans une agence.</p>
  </div>
</section>

<section class="container" style="max-width:820px;">
  <div class="info-card mb-4">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);">Solde en billet électronique</div>
    <div style="font-family:var(--font-mono);font-size:1.4rem;font-weight:700;color:var(--bleu-nuit);"><?= money($currentUser['balance']) ?> HTG</div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> <?= e($success) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger" style="border-radius:var(--radius-md);"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="info-card h-100">
        <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">
          <i class="bi bi-phone-fill"></i> MonCash → Billet électronique
        </div>
        <p style="color:var(--gris-texte);font-size:.85rem;">Convertissez l'argent de votre compte MonCash en billet électronique Gourde Numérique. Vous serez redirigé vers la passerelle sécurisée MonCash pour saisir votre numéro et votre code MonCash.</p>
        <form method="post" action="<?= url('/convertir') ?>">
          <input type="hidden" name="action" value="moncash">
          <div class="mb-3">
            <label class="form-label-mini" for="moncash_amount">Montant (HTG)</label>
            <input type="number" class="form-control-auth" id="moncash_amount" name="amount" min="1" step="0.01" placeholder="Ex : 1000.00" required>
          </div>
          <button type="submit" class="mini-btn w-100"><i class="bi bi-arrow-down-circle"></i> Payer avec MonCash</button>
        </form>
      </div>
    </div>

    <div class="col-md-6">
      <div class="info-card h-100">
        <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">
          <i class="bi bi-bank"></i> Billet électronique → Compte bancaire
        </div>
        <p style="color:var(--gris-texte);font-size:.85rem;">Transférez une partie de votre solde vers un compte bancaire d'une institution partenaire.</p>
        <?php if (empty($banques)): ?>
          <p style="color:var(--gris-texte);font-size:.85rem;">Aucune banque partenaire active pour le moment. Réessayez plus tard.</p>
        <?php else: ?>
        <form method="post" action="<?= url('/convertir') ?>">
          <input type="hidden" name="action" value="banque">
          <div class="mb-3">
            <label class="form-label-mini" for="institution_id">Banque</label>
            <select class="form-control-auth" id="institution_id" name="institution_id" required>
              <option value="" disabled selected>Choisir une banque…</option>
              <?php foreach ($banques as $b): ?>
                <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?><?= $b['ville'] ? ' — ' . e($b['ville']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label-mini" for="account_number">Numéro de compte</label>
            <input type="text" class="form-control-auth" id="account_number" name="account_number" placeholder="Ex : 001-234567-01" required>
          </div>
          <div class="mb-3">
            <label class="form-label-mini" for="bank_amount">Montant (HTG)</label>
            <input type="number" class="form-control-auth" id="bank_amount" name="amount" min="1" step="0.01" placeholder="Ex : 1000.00" required>
          </div>
          <button type="submit" class="mini-btn w-100"><i class="bi bi-arrow-up-circle"></i> Envoyer vers ma banque</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="info-card h-100">
        <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">
          <i class="bi bi-cash-stack"></i> Billet électronique → Billet physique
        </div>
        <p style="color:var(--gris-texte);font-size:.85rem;">Convertissez une partie de votre solde en billets physiques, à retirer dans l'agence BNC de votre choix. Un code de retrait vous sera fourni : présentez-le au guichet pour récupérer vos espèces.</p>
        <?php if (empty($agences)): ?>
          <p style="color:var(--gris-texte);font-size:.85rem;">Aucune agence de retrait active pour le moment. Réessayez plus tard.</p>
        <?php else: ?>
        <form method="post" action="<?= url('/convertir') ?>">
          <input type="hidden" name="action" value="especes">
          <div class="mb-3">
            <label class="form-label-mini" for="agence_id">Agence de retrait</label>
            <select class="form-control-auth" id="agence_id" name="agence_id" required>
              <option value="" disabled selected>Choisir une agence…</option>
              <?php foreach ($agences as $a): ?>
                <option value="<?= (int) $a['id'] ?>"><?= e($a['nom']) ?> — <?= e($a['ville']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label-mini" for="especes_amount">Montant (HTG)</label>
            <input type="number" class="form-control-auth" id="especes_amount" name="amount" min="1" step="0.01" placeholder="Ex : 1000.00" required>
          </div>
          <button type="submit" class="mini-btn w-100"><i class="bi bi-cash-coin"></i> Demander le retrait en espèces</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="info-card">
    <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.8rem;">Derniers mouvements</div>
    <?php if (empty($mouvements)): ?>
      <p style="color:var(--gris-texte);font-size:.9rem;">Aucune conversion pour le moment.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Type</th><th>Détail</th><th>Statut</th><th>Montant</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($mouvements as $m): ?>
          <tr>
            <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
            <td>
              <?php
                $typeLabels = ['moncash_depot' => 'MonCash', 'retrait_bancaire' => 'Retrait bancaire', 'retrait_especes' => 'Retrait espèces'];
                echo e($typeLabels[$m['type']] ?? ucfirst($m['type']));
              ?>
            </td>
            <td><?= e($m['category'] ?? '') ?></td>
            <td>
              <?php if ($m['status'] === 'reussi'): ?>
                <span class="badge-admin badge-in">Réussi</span>
              <?php elseif ($m['status'] === 'en_attente'): ?>
                <span class="badge-admin" style="background:#fff3cd;color:#8a6d3b;">En attente</span>
              <?php else: ?>
                <span class="badge-admin badge-out">Échec</span>
              <?php endif; ?>
            </td>
            <td class="mono"><?= $m['type'] === 'moncash_depot' ? '+' : '-' ?><?= money($m['amount']) ?> HTG</td>
            <td>
              <?php if ($m['type'] === 'moncash_depot' && $m['status'] === 'en_attente'): ?>
              <form method="post" action="<?= url('/convertir/moncash/verifier') ?>" onsubmit="return confirm('Avez-vous déjà terminé le paiement sur MonCash ? On va vérifier auprès de MonCash.');">
                <input type="hidden" name="reference" value="<?= e($m['reference']) ?>">
                <button type="submit" class="btn-outline-line btn-admin-sm" title="Si vous avez payé sur MonCash mais que la page ne s'est pas mise à jour toute seule">
                  <i class="bi bi-arrow-repeat"></i> J'ai payé, vérifier
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
