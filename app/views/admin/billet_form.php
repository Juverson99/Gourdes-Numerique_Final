<?php
$pageTitle      = ($isEdit ? 'Modifier un billet' : 'Nouveau billet') . ' — Administration';
$activeAdminNav = 'billets';
require VIEWS_PATH . '/layouts/admin_header.php';

$vUnitValue    = $billet['unit_value']       ?? '';
$vColorStart   = $billet['color_start']      ?? '#1f6fb2';
$vColorEnd     = $billet['color_end']        ?? '#164b7a';
$vImagePath    = $billet['image_path']       ?? null;
$vImageVerso   = $billet['image_verso_path'] ?? null;
$vSortOrder    = $billet['sort_order']       ?? BankNote::nextSortOrder();
$vActive       = $billet !== null ? !empty($billet['active']) : true;
$vStockQty     = $billet['stock_quantity']   ?? 0;
$vCategoryId   = $billet['category_id']      ?? null;
?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><i class="bi bi-wallet2"></i> <?= $isEdit ? 'Modifier le billet' : 'Nouveau billet' ?></h2>
          </div>

          <div class="p-3">
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger" style="border-radius:var(--radius-md);">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul class="mb-0 ps-3">
                  <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" action="<?= $isEdit ? url('/admin/billets/modifier?id=' . (int) $billet['id']) : url('/admin/billets/nouveau') ?>" id="billetForm" enctype="multipart/form-data">
              <div class="mb-3">
                <label class="form-label-mini" for="unit_value">Valeur unitaire du billet (HTG)</label>
                <input type="number" class="form-control-auth" id="unit_value" name="unit_value" min="0.01" step="0.01" value="<?= e((string) $vUnitValue) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label-mini" for="category_id">Catégorie (facultatif)</label>
                <select class="form-control-auth" id="category_id" name="category_id">
                  <option value="">Sans catégorie</option>
                  <?php foreach ($categories as $cat): if (!$cat['active'] && (int) $cat['id'] !== (int) $vCategoryId) continue; ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (int) $vCategoryId === (int) $cat['id'] ? 'selected' : '' ?>>
                      <?= e($cat['name']) ?><?= !$cat['active'] ? ' (archivée)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p style="color:var(--gris-texte);font-size:.75rem;margin:.4rem 0 0;">
                  Sert à regrouper les billets dans le dossier de gestion. <a href="<?= url('/admin/categories-billets') ?>">Gérer les catégories →</a>
                </p>
              </div>

              <!-- Couleurs conservées en arrière-plan (repli visuel si aucune image n'est envoyée),
                   mais plus modifiables directement : le billet est désormais représenté par une image. -->
              <input type="hidden" id="color_start" name="color_start" value="<?= e($vColorStart) ?>">
              <input type="hidden" id="color_end" name="color_end" value="<?= e($vColorEnd) ?>">

              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label-mini" for="image">Image recto (avant)</label>
                  <input type="file" class="form-control-auth" id="image" name="image" accept="image/png,image/jpeg,image/gif,image/webp">
                  <?php if ($vImagePath): ?>
                    <div class="mb-0 mt-2 form-check" style="padding-left:1.6rem;">
                      <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image" value="1">
                      <label class="form-check-label" for="remove_image" style="font-size:.8rem;color:var(--rouge-haiti);">Retirer l'image recto actuelle</label>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-sm-6">
                  <label class="form-label-mini" for="image_verso">Image verso (arrière)</label>
                  <input type="file" class="form-control-auth" id="image_verso" name="image_verso" accept="image/png,image/jpeg,image/gif,image/webp">
                  <?php if ($vImageVerso): ?>
                    <div class="mb-0 mt-2 form-check" style="padding-left:1.6rem;">
                      <input type="checkbox" class="form-check-input" id="remove_image_verso" name="remove_image_verso" value="1">
                      <label class="form-check-label" for="remove_image_verso" style="font-size:.8rem;color:var(--rouge-haiti);">Retirer l'image verso actuelle</label>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              <p style="color:var(--gris-texte);font-size:.75rem;margin:-.6rem 0 1.2rem;">
                Formats acceptés : JPG, PNG, GIF ou WEBP — 4 Mo maximum chacune. Le recto s'affiche sur la page "Envoyer" ; le verso est visible en retournant le billet (aperçu ci-contre, et vue 3D 360°).
              </p>

              <?php if (!$isEdit): ?>
                <div class="mb-3">
                  <label class="form-label-mini" for="stock_quantity">Stock initial (quantité disponible en réserve)</label>
                  <input type="number" class="form-control-auth" id="stock_quantity" name="stock_quantity" min="0" step="1" value="<?= e((string) $vStockQty) ?>">
                  <p style="color:var(--gris-texte);font-size:.75rem;margin:.4rem 0 0;">
                    Une fois le billet créé, ajustez son stock depuis le <a href="<?= url('/admin/stock') ?>">dossier de gestion du stock</a>.
                  </p>
                </div>
              <?php else: ?>
                <div class="mb-3">
                  <label class="form-label-mini">Stock actuel</label>
                  <div class="mono" style="font-size:1.1rem;font-weight:700;"><?= number_format((int) $vStockQty, 0, ',', ' ') ?> en réserve</div>
                  <p style="color:var(--gris-texte);font-size:.75rem;margin:.4rem 0 0;">
                    Le stock se gère depuis le <a href="<?= url('/admin/stock') ?>">dossier de gestion du stock</a> (avec journalisation des mouvements), pas depuis ce formulaire.
                  </p>
                </div>
              <?php endif; ?>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label-mini" for="sort_order">Ordre d'affichage</label>
                  <input type="number" class="form-control-auth" id="sort_order" name="sort_order" step="1" value="<?= e((string) $vSortOrder) ?>">
                </div>
                <div class="col-6 d-flex align-items-end">
                  <div class="form-check" style="padding-left:1.6rem;">
                    <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= $vActive ? 'checked' : '' ?>>
                    <label class="form-check-label" for="active" style="font-size:.85rem;">Visible sur la page Envoyer</label>
                  </div>
                </div>
              </div>

              <div class="admin-form-actions">
                <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : 'Créer le billet' ?></button>
                <a href="<?= url('/admin/billets') ?>" class="mini-btn-outline">Annuler</a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="admin-card">
          <div class="admin-card-header"><h2><i class="bi bi-eye"></i> Aperçu</h2></div>
          <div class="p-4 d-flex flex-column align-items-center">
            <div class="bill-flip-wrap" id="previewFlipWrap" style="max-width:220px;">
              <div class="bill-flip-inner" id="previewFlipInner">
                <!-- Recto -->
                <span class="bill-face bill-flip-face <?= $vImagePath ? 'has-image' : '' ?>" id="previewFaceRecto" style="--bill-c1:<?= e($vColorStart) ?>; --bill-c2:<?= e($vColorEnd) ?>;">
                  <img class="bill-photo" id="previewImgRecto" src="<?= $vImagePath ? e(url($vImagePath)) : '' ?>" alt="" style="<?= $vImagePath ? '' : 'display:none;' ?>">
                  <span class="bill-photo-overlay" id="previewOverlayRecto" style="<?= $vImagePath ? '' : 'display:none;' ?>"></span>
                  <span class="bill-corner tl" id="previewCornerTl"><?= e((string) $vUnitValue) ?></span>
                  <span class="bill-corner br" id="previewCornerBr"><?= e((string) $vUnitValue) ?></span>
                  <span class="bill-face-tag">RECTO</span>
                  <span class="bill-center" id="previewCenterRecto" style="<?= $vImagePath ? 'display:none;' : '' ?>">
                    <span class="bill-emblem">G</span>
                    <span class="bill-caption">GOURDES NUMÉRIQUES</span>
                  </span>
                </span>
                <!-- Verso -->
                <span class="bill-face bill-flip-face bill-flip-back <?= $vImageVerso ? 'has-image' : '' ?>" id="previewFaceVerso" style="--bill-c1:<?= e($vColorEnd) ?>; --bill-c2:<?= e($vColorStart) ?>;">
                  <img class="bill-photo" id="previewImgVerso" src="<?= $vImageVerso ? e(url($vImageVerso)) : '' ?>" alt="" style="<?= $vImageVerso ? '' : 'display:none;' ?>">
                  <span class="bill-photo-overlay" id="previewOverlayVerso" style="<?= $vImageVerso ? '' : 'display:none;' ?>"></span>
                  <span class="bill-face-tag">VERSO</span>
                  <span class="bill-center" id="previewCenterVerso" style="<?= $vImageVerso ? 'display:none;' : '' ?>">
                    <span class="bill-emblem">G</span>
                    <span class="bill-caption">AUCUNE IMAGE VERSO</span>
                  </span>
                </span>
              </div>
            </div>
            <span class="bill-label mt-2" id="previewLabel">
              <?= $vUnitValue !== '' ? money((float) $vUnitValue) : '0,00' ?> HTG
            </span>
            <button type="button" class="mini-btn-outline mt-3" id="previewFlipBtn"><i class="bi bi-arrow-repeat"></i> Retourner le billet</button>
          </div>
          <p style="color:var(--gris-texte);font-size:.78rem;padding:0 1.2rem 1.2rem;text-align:center;">
            Aperçu du billet tel qu'il apparaîtra sur la page "Envoyer" (recto), avec son verso consultable en le retournant.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ---------- Aperçu en direct du formulaire "Gestion des billets" ----------
const unitValueEl = document.getElementById('unit_value');

const imageInputEl      = document.getElementById('image');
const removeImgEl       = document.getElementById('remove_image');
const imageVersoInputEl = document.getElementById('image_verso');
const removeImgVersoEl  = document.getElementById('remove_image_verso');

const previewFlipInner = document.getElementById('previewFlipInner');
const previewFlipBtn   = document.getElementById('previewFlipBtn');

const previewImgRecto     = document.getElementById('previewImgRecto');
const previewOverlayRecto = document.getElementById('previewOverlayRecto');
const previewCenterRecto  = document.getElementById('previewCenterRecto');
const previewFaceRecto    = document.getElementById('previewFaceRecto');

const previewImgVerso     = document.getElementById('previewImgVerso');
const previewOverlayVerso = document.getElementById('previewOverlayVerso');
const previewCenterVerso  = document.getElementById('previewCenterVerso');
const previewFaceVerso    = document.getElementById('previewFaceVerso');

const previewCornerTl = document.getElementById('previewCornerTl');
const previewCornerBr = document.getElementById('previewCornerBr');
const previewLabel    = document.getElementById('previewLabel');

function fmtHTG(n){
  return n.toLocaleString('fr-FR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function refreshPreview(){
  const unit = parseFloat(unitValueEl.value) || 0;
  previewCornerTl.textContent = unit || 0;
  previewCornerBr.textContent = unit || 0;
  previewLabel.textContent = fmtHTG(unit) + ' HTG';
}

function showImage(imgEl, overlayEl, centerEl, faceEl, url){
  imgEl.src = url;
  imgEl.style.display = '';
  overlayEl.style.display = '';
  centerEl.style.display = 'none';
  faceEl.classList.add('has-image');
}

function hideImage(imgEl, overlayEl, centerEl, faceEl){
  imgEl.style.display = 'none';
  overlayEl.style.display = 'none';
  centerEl.style.display = '';
  faceEl.classList.remove('has-image');
}

// Aperçu immédiat des images choisies, avant même l'enregistrement du formulaire
if (imageInputEl) {
  imageInputEl.addEventListener('change', () => {
    const file = imageInputEl.files && imageInputEl.files[0];
    if (!file) return;
    showImage(previewImgRecto, previewOverlayRecto, previewCenterRecto, previewFaceRecto, URL.createObjectURL(file));
    if (removeImgEl) removeImgEl.checked = false;
  });
}
if (removeImgEl) {
  removeImgEl.addEventListener('change', () => {
    if (removeImgEl.checked) {
      hideImage(previewImgRecto, previewOverlayRecto, previewCenterRecto, previewFaceRecto);
    } else if (previewImgRecto.src) {
      showImage(previewImgRecto, previewOverlayRecto, previewCenterRecto, previewFaceRecto, previewImgRecto.src);
    }
  });
}

if (imageVersoInputEl) {
  imageVersoInputEl.addEventListener('change', () => {
    const file = imageVersoInputEl.files && imageVersoInputEl.files[0];
    if (!file) return;
    showImage(previewImgVerso, previewOverlayVerso, previewCenterVerso, previewFaceVerso, URL.createObjectURL(file));
    if (removeImgVersoEl) removeImgVersoEl.checked = false;
  });
}
if (removeImgVersoEl) {
  removeImgVersoEl.addEventListener('change', () => {
    if (removeImgVersoEl.checked) {
      hideImage(previewImgVerso, previewOverlayVerso, previewCenterVerso, previewFaceVerso);
    } else if (previewImgVerso.src) {
      showImage(previewImgVerso, previewOverlayVerso, previewCenterVerso, previewFaceVerso, previewImgVerso.src);
    }
  });
}

if (previewFlipBtn) {
  previewFlipBtn.addEventListener('click', () => {
    previewFlipInner.classList.toggle('flipped');
  });
}

unitValueEl.addEventListener('input', refreshPreview);
refreshPreview();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
