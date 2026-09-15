<?php
$pageTitle = 'Scanner un code — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Scanner</span>
    <h1>Scannez un code pour <span class="accent">payer ou vous connecter</span></h1>
    <p class="lead-text">Utilisez l'appareil photo pour scanner le QR code d'un contact, ou entrez son numéro de téléphone manuellement.</p>
  </div>
</section>

<section class="send-section" style="padding-top:0;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 text-center">
        <div class="scanner-box" id="scannerBox">
          <video id="scannerVideo" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover;display:none;border-radius:inherit;"></video>
          <div class="corner tl"></div>
          <div class="corner tr"></div>
          <div class="corner bl"></div>
          <div class="corner br"></div>
          <div class="scan-line" id="scanLine"></div>
          <div class="scan-icon" id="scanIcon"><i class="bi bi-qr-code-scan"></i></div>
        </div>
        <button type="button" class="mini-btn mb-4" id="scanBtn"><i class="bi bi-camera-fill"></i> Activer la caméra</button>

        <div class="info-card text-start mt-2">
          <div class="label" style="font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--gris-texte);margin-bottom:.6rem;">Ou entrez le code manuellement</div>
          <label class="form-label-mini" for="manualCode">Code / numéro de téléphone</label>
          <input class="form-control-mini mb-3" type="text" id="manualCode" placeholder="Ex : 509 46213235" inputmode="tel" maxlength="15">
          <div id="manualCodeError" class="auth-error d-none mb-2"></div>
          <button type="button" class="mini-btn-outline w-100" id="manualCodeBtn">Valider le code</button>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$sendUrl = json_encode(url('/envoyer'));
$extraScript = <<<JS
// ---------- Scanner (caméra réelle via jsQR + saisie manuelle) — recherche un vrai compte ----------
const manualCodeError = document.getElementById('manualCodeError');
let scanning = false;
let stream = null;

async function lookupAndRedirect(code){
  const data = await apiPost(`\${API_BASE}/scanner.php?action=lookup`, { code });
  if (!data.ok){
    manualCodeError.textContent = data.error || 'Code non reconnu.';
    manualCodeError.classList.remove('d-none');
    showToast(data.error || 'Code non reconnu.');
    return false;
  }
  showToast(`Compte de \${data.recipient.name} reconnu ✓ — redirection…`);
  setTimeout(() => {
    window.location.href = {$sendUrl} + '?phone=' + encodeURIComponent(data.recipient.phone);
  }, 900);
  return true;
}

document.getElementById('manualCodeBtn').addEventListener('click', async () => {
  manualCodeError.classList.add('d-none');
  const code = document.getElementById('manualCode').value.trim();
  if (!code){
    showToast('Entrez un code à valider');
    return;
  }
  await lookupAndRedirect(code);
});

const scanBtn = document.getElementById('scanBtn');
const video = document.getElementById('scannerVideo');
const scanIcon = document.getElementById('scanIcon');
const scanLine = document.getElementById('scanLine');
const canvas = document.createElement('canvas');
const ctx = canvas.getContext('2d');

async function startScan(){
  try{
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
  }catch(e){
    showToast("Impossible d'accéder à la caméra sur cet appareil.");
    return;
  }
  video.srcObject = stream;
  video.style.display = 'block';
  scanIcon.style.display = 'none';
  scanLine.style.display = 'none';
  scanBtn.innerHTML = '<i class="bi bi-x-circle"></i> Arrêter la caméra';
  scanning = true;
  requestAnimationFrame(tick);
}

function stopScan(){
  scanning = false;
  if (stream){ stream.getTracks().forEach(t => t.stop()); stream = null; }
  video.style.display = 'none';
  scanIcon.style.display = 'flex';
  scanLine.style.display = 'block';
  scanBtn.innerHTML = '<i class="bi bi-camera-fill"></i> Activer la caméra';
}

function tick(){
  if (!scanning) return;
  if (video.readyState === video.HAVE_ENOUGH_DATA && window.jsQR){
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const result = window.jsQR(imageData.data, imageData.width, imageData.height);
    if (result && result.data){
      stopScan();
      lookupAndRedirect(result.data.trim());
      return;
    }
  }
  requestAnimationFrame(tick);
}

scanBtn.addEventListener('click', () => {
  if (scanning) stopScan(); else startScan();
});
JS;

$extraHeadScript = '<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>';
require VIEWS_PATH . '/layouts/footer.php';
