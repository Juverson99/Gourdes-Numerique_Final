<?php
$pageTitle = 'Paiement — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Paiement</span>
    <h1>Payez marchands et factures <span class="accent">en un tap</span></h1>
    <p class="lead-text">Réglez vos achats, vos factures et services publics directement en gourdes numériques, sans espèces ni file d'attente. Solde disponible : <strong><?= money($currentUser['balance']) ?> HTG</strong></p>
  </div>
</section>

<section class="send-section" style="padding-top:0;">
  <div class="container">
    <div class="row g-4 g-lg-5 justify-content-center">
      <div class="col-lg-7">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">Choisissez une catégorie</h3>
        <?php if (empty($categories)): ?>
          <div class="admin-empty" style="border-radius:var(--radius-md);">Aucune catégorie de paiement disponible pour le moment.</div>
        <?php else: ?>
        <div class="merchant-grid" id="merchantGrid">
          <?php foreach ($categories as $i => $cat): ?>
            <div class="merchant-chip<?= $i === 0 ? ' is-active' : '' ?>" data-cat="<?= e($cat['name']) ?>"><i class="bi <?= e($cat['icon']) ?>"></i><span><?= e($cat['name']) ?></span></div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p style="color:var(--gris-texte);font-size:.85rem;">Renseignez la référence indiquée sur votre facture ou le code du marchand, puis confirmez le montant à régler.</p>
      </div>

      <div class="col-lg-4">
        <div class="send-summary">
          <div class="label">Montant à payer</div>
          <div class="total-amount"><span id="payAmountDisplay">0</span><small>HTG</small></div>

          <label class="form-label-mini" for="payCategory">Catégorie</label>
          <input class="form-control-mini mb-2" type="text" id="payCategory" value="<?= e($categories[0]['name'] ?? '') ?>" readonly>

          <label class="form-label-mini" for="payReference">Référence / code marchand</label>
          <div class="d-flex gap-2 align-items-center mb-2">
            <input class="form-control-mini" style="flex:1;" type="text" id="payReference" placeholder="Ex : EDH-99213">
            <button type="button" class="mini-btn-outline" id="payRefRefresh" title="Générer le prochain code automatiquement" style="padding:.5rem .65rem;line-height:1;">
              <i class="bi bi-arrow-repeat"></i>
            </button>
          </div>
          <p style="color:var(--gris-texte);font-size:.72rem;margin-top:-.35rem;">Code suggéré automatiquement (incrémenté depuis votre dernier paiement dans cette catégorie) — modifiable si besoin.</p>

          <label class="form-label-mini" for="payAmount">Montant (HTG)</label>
          <input class="form-control-mini" type="number" id="payAmount" placeholder="Ex : 1200">

          <div id="payError" class="auth-error d-none mt-2"></div>

          <div class="d-flex gap-2 mt-3">
            <button type="button" class="mini-btn flex-fill" id="payBtn">Payer</button>
            <button type="button" class="mini-btn-outline" id="payResetBtn">Effacer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'JS'
// ---------- Paiement de factures / marchands (branché sur la vraie API) ----------
const payReferenceInput = document.getElementById('payReference');
const payRefRefreshBtn  = document.getElementById('payRefRefresh');

// Suggère automatiquement le prochain code de référence (incrémenté depuis le
// dernier paiement du client dans cette catégorie) et le pré-remplit — le
// client peut toujours le modifier manuellement s'il le souhaite.
async function suggestNextReference(category){
  if (!category) return;
  const data = await apiPost(`${API_BASE}/transactions.php?action=prochaine_reference`, { category });
  if (data.ok && data.reference){
    payReferenceInput.value = data.reference;
  }
}

document.querySelectorAll('.merchant-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.merchant-chip').forEach(c => c.classList.remove('is-active'));
    chip.classList.add('is-active');
    document.getElementById('payCategory').value = chip.dataset.cat;
    suggestNextReference(chip.dataset.cat);
  });
});

if (payRefRefreshBtn) {
  payRefRefreshBtn.addEventListener('click', () => {
    suggestNextReference(document.getElementById('payCategory').value);
  });
}

// Pré-remplissage initial, pour la catégorie sélectionnée par défaut au chargement.
suggestNextReference(document.getElementById('payCategory').value);

const payAmountInput = document.getElementById('payAmount');
const payAmountDisplay = document.getElementById('payAmountDisplay');
const payError = document.getElementById('payError');
payAmountInput.addEventListener('input', () => {
  const val = Number(payAmountInput.value) || 0;
  payAmountDisplay.textContent = val.toLocaleString('fr-FR');
});

document.getElementById('payResetBtn').addEventListener('click', () => {
  payAmountInput.value = '';
  payAmountDisplay.textContent = '0';
  payError.classList.add('d-none');
  suggestNextReference(document.getElementById('payCategory').value);
});

document.getElementById('payBtn').addEventListener('click', async () => {
  payError.classList.add('d-none');
  const amount = Number(payAmountInput.value) || 0;
  const reference = document.getElementById('payReference').value.trim();
  const category = document.getElementById('payCategory').value;

  if (!category){
    showToast('Aucune catégorie de paiement disponible');
    return;
  }
  if (amount === 0){
    showToast('Entrez un montant à payer');
    return;
  }
  if (!reference){
    showToast('Entrez la référence ou le code marchand');
    return;
  }

  const data = await apiPost(`${API_BASE}/transactions.php?action=paiement`, { category, reference, amount });

  if (!data.ok){
    payError.textContent = data.error || 'Impossible d\'effectuer le paiement.';
    payError.classList.remove('d-none');
    return;
  }

  setFlashAndReload(`Paiement de ${amount.toLocaleString('fr-FR')} HTG confirmé ✓`);
});
JS;

require VIEWS_PATH . '/layouts/footer.php';
