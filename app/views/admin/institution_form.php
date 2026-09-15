<?php
$pageTitle      = ($isEdit ? 'Modifier une institution' : 'Nouvelle institution') . ' — Administration';
$activeAdminNav = 'institutions';
require VIEWS_PATH . '/layouts/admin_header.php';

$vName          = $institution['name']           ?? '';
$vType          = $institution['type']           ?? 'banque';
$vLicense       = $institution['license_number'] ?? '';
$vContactName   = $institution['contact_name']   ?? '';
$vPhone         = $institution['phone']          ?? '';
$vEmail         = $institution['email']          ?? '';
$vVille         = $institution['ville']          ?? '';
$vAddress       = $institution['address']        ?? '';
$vLogoPath      = $institution['logo_path']       ?? null;
$vStatus        = $institution['status']         ?? 'actif';
$vNotes         = $institution['notes']          ?? '';
?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><i class="bi bi-bank"></i> <?= $isEdit ? 'Modifier l\'institution' : 'Nouvelle institution' ?></h2>
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

            <form method="post" action="<?= $isEdit ? url('/admin/institutions/modifier?id=' . (int) $institution['id']) : url('/admin/institutions/nouveau') ?>" enctype="multipart/form-data">
              <div class="mb-3">
                <label class="form-label-mini" for="name">Nom de l'institution</label>
                <input type="text" class="form-control-auth" id="name" name="name" value="<?= e($vName) ?>" placeholder="Ex : Sogebank" required>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label-mini" for="type">Type d'institution</label>
                  <select class="form-control-auth" id="type" name="type">
                    <?php foreach ($typeLabels as $key => $label): ?>
                      <option value="<?= e($key) ?>" <?= $vType === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label-mini" for="license_number">N° de licence (BRH)</label>
                  <input type="text" class="form-control-auth" id="license_number" name="license_number" value="<?= e($vLicense) ?>" placeholder="Ex : BRH-BQ-014">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-mini" for="contact_name">Nom du contact / responsable</label>
                <input type="text" class="form-control-auth" id="contact_name" name="contact_name" value="<?= e($vContactName) ?>" placeholder="Ex : Service Partenariats">
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label-mini" for="phone">Téléphone</label>
                  <input type="tel" class="form-control-auth" id="phone" name="phone" value="<?= e($vPhone) ?>" placeholder="Ex : 509 46213235" inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
                </div>
                <div class="col-6">
                  <label class="form-label-mini" for="email">Courriel</label>
                  <input type="email" class="form-control-auth" id="email" name="email" value="<?= e($vEmail) ?>">
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label-mini" for="ville">Ville</label>
                  <input type="text" class="form-control-auth" id="ville" name="ville" value="<?= e($vVille) ?>" placeholder="Ex : Port-au-Prince">
                </div>
                <div class="col-6">
                  <label class="form-label-mini" for="status">Statut du partenariat</label>
                  <select class="form-control-auth" id="status" name="status">
                    <option value="actif" <?= $vStatus === 'actif' ? 'selected' : '' ?>>Actif</option>
                    <option value="suspendu" <?= $vStatus === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                  </select>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label-mini" for="address">Adresse</label>
                <input type="text" class="form-control-auth" id="address" name="address" value="<?= e($vAddress) ?>">
              </div>

              <div class="mb-3">
                <label class="form-label-mini" for="logo">Logo de l'institution</label>
                <input type="file" class="form-control-auth" id="logo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
                <p style="color:var(--gris-texte);font-size:.75rem;margin:.4rem 0 0;">
                  Formats acceptés : JPG, PNG, GIF ou WEBP — 4 Mo maximum.
                </p>
              </div>

              <?php if ($vLogoPath): ?>
                <div class="mb-3 d-flex align-items-center gap-3">
                  <span class="table-avatar"><img src="<?= e(url($vLogoPath)) ?>" alt=""></span>
                  <div class="form-check" style="padding-left:1.6rem;">
                    <input type="checkbox" class="form-check-input" id="remove_logo" name="remove_logo" value="1">
                    <label class="form-check-label" for="remove_logo" style="font-size:.85rem;color:var(--rouge-haiti);">Retirer le logo actuel</label>
                  </div>
                </div>
              <?php endif; ?>

              <div class="mb-3">
                <label class="form-label-mini" for="notes">Notes internes</label>
                <textarea class="form-control-auth" id="notes" name="notes" rows="3" placeholder="Informations complémentaires sur le partenariat…"><?= e($vNotes) ?></textarea>
              </div>

              <div class="admin-form-actions">
                <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : 'Créer l\'institution' ?></button>
                <a href="<?= url('/admin/institutions') ?>" class="mini-btn-outline">Annuler</a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="admin-card">
          <div class="admin-card-header"><h2><i class="bi bi-eye"></i> Aperçu</h2></div>
          <div class="p-4 d-flex flex-column align-items-center gap-3" id="previewBox">
            <span class="table-avatar" style="width:64px;height:64px;font-size:1.4rem;" id="previewAvatar">
              <?php if ($vLogoPath): ?>
                <img src="<?= e(url($vLogoPath)) ?>" alt="" id="previewLogoImg">
              <?php else: ?>
                <span id="previewInitial"><?= $vName !== '' ? e(initial_letter($vName)) : '?' ?></span>
              <?php endif; ?>
            </span>
            <div style="text-align:center;">
              <div style="font-weight:700;color:var(--bleu-nuit);" id="previewName"><?= $vName !== '' ? e($vName) : 'Nom de l\'institution' ?></div>
              <div style="font-size:.8rem;color:var(--gris-texte);" id="previewType"><?= e($typeLabels[$vType] ?? $vType) ?></div>
            </div>
            <span class="badge-admin <?= $vStatus === 'actif' ? 'badge-in' : 'badge-out' ?>" id="previewStatus">
              <?= $vStatus === 'actif' ? 'Actif' : 'Suspendu' ?>
            </span>
          </div>
          <p style="color:var(--gris-texte);font-size:.78rem;padding:0 1.2rem 1.2rem;text-align:center;">
            Aperçu de la fiche telle qu'elle apparaîtra dans le dossier des institutions financières.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ---------- Aperçu en direct du formulaire "Institutions financières" ----------
const nameEl    = document.getElementById('name');
const typeEl    = document.getElementById('type');
const statusEl  = document.getElementById('status');
const logoEl    = document.getElementById('logo');
const removeLogoEl = document.getElementById('remove_logo');

const previewName   = document.getElementById('previewName');
const previewType   = document.getElementById('previewType');
const previewStatus = document.getElementById('previewStatus');
const previewAvatar = document.getElementById('previewAvatar');

function refreshPreview(){
  previewName.textContent = nameEl.value.trim() || "Nom de l'institution";
  previewType.textContent = typeEl.options[typeEl.selectedIndex].text;

  const isActive = statusEl.value === 'actif';
  previewStatus.textContent = isActive ? 'Actif' : 'Suspendu';
  previewStatus.classList.toggle('badge-in', isActive);
  previewStatus.classList.toggle('badge-out', !isActive);

  const initialEl = document.getElementById('previewInitial');
  if (initialEl) {
    initialEl.textContent = (nameEl.value.trim().charAt(0) || '?').toUpperCase();
  }
}

if (logoEl) {
  logoEl.addEventListener('change', () => {
    const file = logoEl.files && logoEl.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    previewAvatar.innerHTML = `<img src="${url}" alt="">`;
    if (removeLogoEl) removeLogoEl.checked = false;
  });
}

[nameEl, typeEl, statusEl].forEach(el => {
  el.addEventListener('input', refreshPreview);
  el.addEventListener('change', refreshPreview);
});

refreshPreview();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
