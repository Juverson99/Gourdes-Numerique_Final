<?php
$pageTitle = 'Envoyer de l\'argent — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';

// Regroupe les billets individuels décomposés (breakdownForBalance) par
// coupure (lot) : un billet de 100 HTG apparaît par exemple comme UN seul
// "lot" affichant "×12" plutôt que 12 cartes séparées à l'écran. L'utilisateur
// détache ensuite les billets un par un de ce lot (voir JS plus bas) —
// chaque instance individuelle reste distincte (via instance_id), seul
// l'affichage est groupé.
$lots = [];
foreach ($billets as $b) {
    $noteId = (int) $b['id'];
    if (!isset($lots[$noteId])) {
        $lots[$noteId] = ['note' => $b, 'instances' => []];
    }
    $lots[$noteId]['instances'][] = $b['instance_id'];
}
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>
<!-- ===================== ENVOYER — sélection par lots de billets ===================== -->
<section class="send-section" id="envoyer">
  <div class="container">
    <div class="section-title-wrap">
      <span class="eyebrow"><span class="dot"></span> Envoyer de l'argent</span>
      <h2>Composez le montant avec des billets</h2>
      <p>Vos billets sont rangés par lot, comme une vraie liasse. Solde disponible : <strong><?= money($currentUser['balance']) ?> HTG</strong></p>
      <p style="font-size:.85rem;color:var(--gris-texte);">Touchez un lot pour en détacher un billet à la fois : il rejoint la pile « Billets détachés » et s'ajoute au montant à envoyer. Touchez un billet détaché (ou cliquez sur « Effacer ») pour le remettre dans son lot.</p>
    </div>

    <div class="row g-4 g-lg-5 align-items-start justify-content-center">
      <div class="col-lg-7">
        <?php if (!empty($lots)): ?>
        <div class="bills-toolbar">
          <div class="bills-selected-count" id="billsSelectedCount">0 billet sélectionné</div>
          <div class="bills-toolbar-actions">
            <button type="button" class="mini-btn-outline bills-toolbar-btn" id="selectAllBtn"><i class="bi bi-check2-square"></i> Tout détacher</button>
            <button type="button" class="mini-btn-outline bills-toolbar-btn" id="deselectAllBtn"><i class="bi bi-square"></i> Tout remettre</button>
          </div>
        </div>
        <?php endif; ?>

        <div class="bills-grid" id="billsGrid">
          <?php if (empty($lots)): ?>
            <?php if ($aucuneCoupureActive): ?>
              <p style="color:var(--gris-texte);">Aucun billet n'est disponible pour le moment. Réessayez plus tard.</p>
            <?php elseif ($soldeNul): ?>
              <p style="color:var(--gris-texte);">Vous n'avez pas encore de solde disponible pour composer un envoi.</p>
            <?php else: ?>
              <p style="color:var(--gris-texte);">Votre solde de <strong><?= money($currentUser['balance']) ?> HTG</strong> ne permet de composer aucun billet pour le moment.</p>
            <?php endif; ?>
          <?php endif; ?>

          <?php foreach ($lots as $noteId => $lot):
            $b     = $lot['note'];
            $unit  = (float) $b['unit_value'];
            $count = count($lot['instances']);
            $unitDisplay = $unit == (int) $unit ? (string) (int) $unit : rtrim(rtrim((string) $unit, '0'), '.');
            $hasImage = !empty($b['image_path']);
            $hasVerso = !empty($b['image_verso_path']);
          ?>
          <div class="bill-wrapper">
            <button type="button" class="bill-card lot-card" id="lot-<?= $noteId ?>"
                    data-note-id="<?= $noteId ?>"
                    data-value="<?= $unit ?>"
                    data-instances='<?= e(json_encode($lot['instances'])) ?>'
                    title="Détacher un billet de ce lot">
              <span class="bill-face lot-face<?= $hasImage ? ' has-image' : '' ?>" style="--bill-c1:<?= e($b['color_start']) ?>; --bill-c2:<?= e($b['color_end']) ?>;">
                <?php if ($hasImage): ?>
                  <img class="bill-photo" src="<?= e(url($b['image_path'])) ?>" alt="" loading="lazy">
                  <span class="bill-photo-overlay"></span>
                <?php endif; ?>
                <span class="lot-badge" id="lotBadge-<?= $noteId ?>">×<?= $count ?></span>
                <span class="bill-corner tl"><?= $unitDisplay ?></span>
                <span class="bill-corner br"><?= $unitDisplay ?></span>
                <?php if (!$hasImage): ?>
                <span class="bill-center">
                  <span class="bill-emblem">G</span>
                  <span class="bill-caption">GOURDES NUMÉRIQUES</span>
                </span>
                <?php endif; ?>
              </span>
              <span class="bill-label">Lot de <?= $unitDisplay ?> HTG</span>
            </button>
            <?php if ($hasImage): ?>
            <button type="button" class="bill-3d-btn" data-image="<?= e(url($b['image_path'])) ?>" data-verso="<?= $hasVerso ? e(url($b['image_verso_path'])) : '' ?>" data-value="<?= $unitDisplay ?> HTG" title="Voir en réalité virtuelle 360°">
              <i class="bi bi-cube"></i>
              <span>360°</span>
            </button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($lots)): ?>
        <div class="detached-tray-wrap" id="detachedTrayWrap" style="display:none;">
          <div class="detached-tray-title"><i class="bi bi-collection-fill"></i> Billets détachés</div>
          <div class="detached-tray" id="detachedTray"></div>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="send-summary">
          <div class="label">Montant à envoyer</div>
          <div class="total-amount"><span id="totalAmount">0</span><small>HTG</small></div>

          <label class="form-label-mini" for="recipientInput">Numéro de téléphone du destinataire</label>
          <?php
            // L'indicatif "509" est affiché à part, en dehors du champ éditable :
            // l'utilisateur ne saisit/n'efface que les 8 chiffres locaux.
            $prefillLocal = '';
            if (isset($_GET['phone'])) {
                $prefillLocal = preg_replace('/\D/', '', $_GET['phone']);
                if (strlen($prefillLocal) > 8 && str_starts_with($prefillLocal, '509')) {
                    $prefillLocal = substr($prefillLocal, 3);
                }
                $prefillLocal = substr($prefillLocal, 0, 8);
            }
          ?>
          <div class="phone-input-wrap">
            <span class="phone-input-prefix">509</span>
            <input class="form-control-mini" type="text" id="recipientInput" placeholder="46213235" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" title="8 chiffres locaux, ex : 46213235." value="<?= e($prefillLocal) ?>">
          </div>

          <div id="sendError" class="auth-error d-none mt-2"></div>

          <div class="d-flex gap-2 mt-3">
            <button type="button" class="mini-btn flex-fill" id="sendBtn">Envoyer</button>
            <button type="button" class="mini-btn-outline" id="resetBtn">Effacer</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'JS'
// ---------- Sélecteur de billets pour "Envoyer" — par lots, détachables un par un ----------
const totalEl = document.getElementById('totalAmount');
const recipientInput = document.getElementById('recipientInput');
const sendError = document.getElementById('sendError');
const billsSelectedCount = document.getElementById('billsSelectedCount');
const selectAllBtn = document.getElementById('selectAllBtn');
const deselectAllBtn = document.getElementById('deselectAllBtn');
const detachedTray = document.getElementById('detachedTray');
const detachedTrayWrap = document.getElementById('detachedTrayWrap');
let total = 0;

// ---------- Formatage live du téléphone destinataire ----------
// L'indicatif "509" est affiché à part (voir .phone-input-prefix dans le HTML) :
// ce champ ne contient donc QUE les chiffres locaux, jamais l'indicatif lui-même.
// Cela évite l'ancien bug où l'indicatif, ré-analysé à chaque frappe comme du
// texte, finissait par être confondu avec des chiffres saisis par l'utilisateur
// et se réinjectait sans arrêt — rendant le champ impossible à vider complètement.
// Ici, effacer les chiffres locaux vide simplement le champ, sans reformatage
// piégeux : max 8 chiffres, non-chiffres retirés, position du curseur préservée.
function formatRecipientPhone(input){
  const oldPos = input.selectionStart ?? input.value.length;
  const digitsBeforeCursor = input.value.slice(0, oldPos).replace(/\D/g, '').length;

  const digits = input.value.replace(/\D/g, '').slice(0, 8);
  input.value = digits;

  if (document.activeElement === input) {
    const newPos = Math.min(digitsBeforeCursor, digits.length);
    input.setSelectionRange(newPos, newPos);
  }
}

if (recipientInput) {
  formatRecipientPhone(recipientInput); // nettoie une valeur déjà pré-remplie (ex : ?phone= dans l'URL)
  recipientInput.addEventListener('input', () => formatRecipientPhone(recipientInput));
}

function updateTotal(){
  totalEl.textContent = total.toLocaleString('fr-FR');
}

function updateSelectedCount(){
  const nb = detachedTray ? detachedTray.children.length : 0;
  if (billsSelectedCount) {
    billsSelectedCount.textContent = nb === 0
      ? '0 billet sélectionné'
      : (nb === 1 ? '1 billet sélectionné' : `${nb} billets sélectionnés`);
  }
  if (detachedTrayWrap) {
    detachedTrayWrap.style.display = nb === 0 ? 'none' : '';
  }
}

function fmtValue(v){
  return v === Math.trunc(v) ? String(Math.trunc(v)) : String(v);
}

// Chaque "lot" représente une pile de billets identiques (même coupure) du
// solde du client. lotsData[noteId] garde la file des instance_id restants
// dans le lot (une pop() = un billet détaché) ainsi que son cumul courant.
const lotsData = {};
document.querySelectorAll('.lot-card').forEach(card => {
  const noteId = card.dataset.noteId;
  let instances = [];
  try { instances = JSON.parse(card.dataset.instances) || []; } catch (e) { instances = []; }
  lotsData[noteId] = {
    value: Number(card.dataset.value),
    instances: instances,
    remaining: instances.length,
    cardEl: card,
    badgeEl: document.getElementById('lotBadge-' + noteId),
  };
});

function updateBadge(noteId){
  const lot = lotsData[noteId];
  if (!lot) return;
  lot.badgeEl.textContent = '×' + lot.remaining;
  lot.cardEl.classList.toggle('lot-empty', lot.remaining === 0);
  lot.cardEl.title = lot.remaining === 0 ? 'Lot épuisé — plus aucun billet à détacher' : 'Détacher un billet de ce lot';
}

function addTrayChip(noteId, instanceId, value){
  const chip = document.createElement('button');
  chip.type = 'button';
  chip.className = 'detached-chip';
  chip.dataset.noteId = noteId;
  chip.dataset.instanceId = instanceId;
  chip.title = 'Remettre ce billet dans son lot';
  chip.innerHTML = `<i class="bi bi-cash-coin"></i> ${fmtValue(value)} HTG <span class="detached-chip-remove"><i class="bi bi-x-lg"></i></span>`;
  chip.addEventListener('click', () => reattachOne(noteId, instanceId, chip));
  detachedTray.appendChild(chip);
}

function detachOne(noteId){
  const lot = lotsData[noteId];
  if (!lot || lot.remaining <= 0) return;
  const instanceId = lot.instances.pop();
  lot.remaining--;
  total += lot.value;
  total = Math.max(0, Math.round(total * 100) / 100);

  updateBadge(noteId);
  addTrayChip(noteId, instanceId, lot.value);
  updateTotal();
  updateSelectedCount();

  lot.cardEl.classList.add('pop');
  setTimeout(() => lot.cardEl.classList.remove('pop'), 160);
}

function reattachOne(noteId, instanceId, chipEl){
  const lot = lotsData[noteId];
  if (!lot) return;
  lot.instances.push(instanceId);
  lot.remaining++;
  total -= lot.value;
  total = Math.max(0, Math.round(total * 100) / 100);

  updateBadge(noteId);
  chipEl.remove();
  updateTotal();
  updateSelectedCount();
}

function reattachAll(){
  Array.from(detachedTray.children).forEach(chip => {
    reattachOne(chip.dataset.noteId, chip.dataset.instanceId, chip);
  });
}

document.querySelectorAll('.lot-card').forEach(card => {
  card.addEventListener('click', () => detachOne(card.dataset.noteId));
});

// ---------- Tout détacher / Tout remettre ----------
if (selectAllBtn) selectAllBtn.addEventListener('click', () => {
  Object.keys(lotsData).forEach(noteId => {
    while (lotsData[noteId].remaining > 0) detachOne(noteId);
  });
});

if (deselectAllBtn) deselectAllBtn.addEventListener('click', reattachAll);

document.getElementById('resetBtn').addEventListener('click', () => {
  reattachAll();
  recipientInput.value = '';
  sendError.classList.add('d-none');
});

// ---------- Gestion des billets 3D 360° (recto + verso si disponible) ----------
document.querySelectorAll('.bill-3d-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const imageUrl = btn.dataset.image;
    const versoUrl = btn.dataset.verso || null;
    const billValue = btn.dataset.value;
    openBill3DViewer(imageUrl, billValue, versoUrl);
  });
});

document.getElementById('sendBtn').addEventListener('click', async () => {
  sendError.classList.add('d-none');

  if (total === 0){
    showToast('Détachez au moins un billet');
    return;
  }
  if (!recipientInput.value.trim()){
    showToast('Entrez le numéro de téléphone du destinataire');
    return;
  }

  const data = await apiPost(`${API_BASE}/transactions.php?action=envoyer`, {
    recipient_phone: recipientInput.value.trim(),
    amount: total,
  });

  if (!data.ok){
    sendError.textContent = data.error || "Impossible d'effectuer l'envoi.";
    sendError.classList.remove('d-none');
    return;
  }

  setFlashAndReload(`Envoi de ${total.toLocaleString('fr-FR')} HTG à ${data.recipient_name} confirmé ✓`);
});
JS;

$extraHeadScript = '<script src="' . url('/assets/js/bill-viewer-3d.js') . '"></script>';
require VIEWS_PATH . '/layouts/footer.php';
