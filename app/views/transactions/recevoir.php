<?php
$pageTitle = 'Recevoir de l\'argent — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Recevoir de l'argent</span>
    <h1>Partagez votre code pour <span class="accent">recevoir</span> instantanément</h1>
    <p class="lead-text">Montrez votre QR code ou communiquez votre numéro de téléphone à l'expéditeur : les gourdes numériques arrivent sur votre compte en quelques secondes.</p>
  </div>
</section>

<section class="send-section" style="padding-top:0;">
  <div class="container">
    <div class="row g-4 g-lg-5 justify-content-center">
      <div class="col-lg-5">
        <div class="info-card text-center">
          <div class="qr-box" id="qrBox"></div>
          <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.5rem;">Votre numéro de téléphone</div>
          <div class="account-id">
            <span id="phoneValue"><?= e($currentUser['phone']) ?></span>
            <button type="button" class="copy-btn" id="copyPhoneBtn" title="Copier"><i class="bi bi-clipboard"></i></button>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button type="button" class="mini-btn flex-fill" id="shareBtn"><i class="bi bi-share-fill"></i> Partager mon code</button>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="info-card">
          <label class="form-label-mini" for="requestAmount">Demander un montant précis (optionnel)</label>
          <input class="form-control-mini mb-3" type="number" id="requestAmount" placeholder="Ex : 2500">
          <div class="goal-row">
            <div class="gi"><i class="bi bi-1-circle"></i></div>
            <div><h4>Partagez votre code</h4><p>Montrez le QR code ci-contre ou envoyez votre numéro de téléphone à l'expéditeur.</p></div>
          </div>
          <div class="goal-row">
            <div class="gi"><i class="bi bi-2-circle"></i></div>
            <div><h4>L'expéditeur compose le montant</h4><p>Il choisit les billets numériques correspondants et confirme l'envoi.</p></div>
          </div>
          <div class="goal-row" style="border-bottom:none;">
            <div class="gi"><i class="bi bi-3-circle"></i></div>
            <div><h4>Recevez instantanément</h4><p>Le montant est crédité sur votre compte et visible dans votre historique.</p></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 g-lg-5 justify-content-center mt-1">
      <div class="col-lg-11">
        <div class="info-card">
          <div class="d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-send-check-fill" style="color:var(--or);font-size:1.3rem;"></i>
            <h4 class="mb-0">Demander de l'argent à un client</h4>
          </div>
          <p style="color:var(--gris-texte);font-size:.88rem;">Indiquez le numéro de téléphone d'un client pour lui demander un montant précis : il reçoit une alerte et peut l'accepter (l'argent arrive alors directement sur votre compte) ou la refuser.</p>
          <div class="row g-3">
            <div class="col-sm-5">
              <label class="form-label-mini" for="requestTargetInput">Numéro de téléphone du client</label>
              <div class="phone-input-wrap">
                <span class="phone-input-prefix">509</span>
                <input class="form-control-mini" type="text" id="requestTargetInput" placeholder="46213235" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" title="8 chiffres locaux, ex : 46213235.">
              </div>
            </div>
            <div class="col-sm-3">
              <label class="form-label-mini" for="requestToAmount">Montant (HTG)</label>
              <input class="form-control-mini" type="number" id="requestToAmount" placeholder="Ex : 2500" min="1">
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="requestToMessage">Motif (optionnel)</label>
              <input class="form-control-mini" type="text" id="requestToMessage" placeholder="Ex : Part du loyer" maxlength="120">
            </div>
          </div>
          <div id="requestToError" class="auth-error d-none mt-2"></div>
          <button type="button" class="mini-btn mt-3" id="sendRequestBtn"><i class="bi bi-send-fill"></i> Envoyer la demande</button>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$phoneJs = json_encode($currentUser['phone']);
$extraScript = <<<JS
// ---------- Code QR personnel (généré à partir du vrai numéro de téléphone du compte connecté) ----------
new QRCode(document.getElementById('qrBox'), {
  text: {$phoneJs},
  width: 168,
  height: 168,
  colorDark: '#0b1b3a',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.M
});

document.getElementById('copyPhoneBtn').addEventListener('click', () => {
  navigator.clipboard.writeText({$phoneJs}).then(() => showToast('Numéro de téléphone copié ✓'));
});

document.getElementById('shareBtn').addEventListener('click', () => {
  const amount = document.getElementById('requestAmount').value;
  const text = amount
    ? `Envoyez-moi \${Number(amount).toLocaleString('fr-FR')} HTG via Gourde Numérique — mon numéro : {$phoneJs}`
    : `Envoyez-moi des Gourdes Numériques — mon numéro : {$phoneJs}`;
  if (navigator.share) {
    navigator.share({ title: 'Gourde Numérique', text }).catch(() => {});
  } else {
    navigator.clipboard.writeText(text).then(() => showToast('Message copié — collez-le où vous voulez ✓'));
  }
});

// ---------- Demander de l'argent à un client précis (numéro de téléphone) ----------
const requestTargetInput = document.getElementById('requestTargetInput');
const requestToError = document.getElementById('requestToError');
const sendRequestBtn = document.getElementById('sendRequestBtn');

if (requestTargetInput) {
  requestTargetInput.addEventListener('input', () => {
    requestTargetInput.value = requestTargetInput.value.replace(/\\D/g, '').slice(0, 8);
  });
}

sendRequestBtn.addEventListener('click', async () => {
  requestToError.classList.add('d-none');

  const phone = requestTargetInput.value.trim();
  const amount = Number(document.getElementById('requestToAmount').value);
  const message = document.getElementById('requestToMessage').value.trim();

  if (!phone) {
    showToast('Entrez le numéro de téléphone du client');
    return;
  }
  if (!amount || amount <= 0) {
    showToast('Entrez le montant à demander');
    return;
  }

  sendRequestBtn.disabled = true;
  const data = await apiPost(`\${API_BASE}/transactions.php?action=demander`, {
    target_phone: phone,
    amount: amount,
    message: message,
  });
  sendRequestBtn.disabled = false;

  if (!data.ok) {
    requestToError.textContent = data.error || "Impossible d'envoyer cette demande.";
    requestToError.classList.remove('d-none');
    return;
  }

  requestTargetInput.value = '';
  document.getElementById('requestToAmount').value = '';
  document.getElementById('requestToMessage').value = '';
  showToast(`Demande de \${amount.toLocaleString('fr-FR')} HTG envoyée à \${data.target_name} ✓`);
});
JS;

$extraHeadScript = '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>';
require VIEWS_PATH . '/layouts/footer.php';
