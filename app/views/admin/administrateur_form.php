<?php
$pageTitle      = ($isEdit ? 'Modifier un administrateur' : 'Nouvel administrateur') . ' — Administration';
$activeAdminNav = 'administrateurs';
require VIEWS_PATH . '/layouts/admin_header.php';

$vName    = $admin['name']  ?? '';
$vNinu    = $admin['ninu']  ?? '';
$vSexe    = $admin['sexe']  ?? '';
$vAge     = $admin['age']   ?? '';
$vVille   = $admin['ville'] ?? '';
$vPays    = $admin['pays']  ?? 'Haïti';
$vPhone   = $admin['phone'] ?? '';
$vEmail   = $admin['email'] ?? '';
$vPhotoPath = $admin['photo_path'] ?? null;
?>

<a href="<?= url('/admin/administrateurs') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour au dossier des administrateurs</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-shield-lock-fill"></i> <?= $isEdit ? 'Modifier l\'administrateur' : 'Nouvel administrateur' ?></h2>
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

        <form method="post" action="<?= $isEdit ? url('/admin/administrateurs/modifier?id=' . (int) $admin['id']) : url('/admin/administrateurs/nouveau') ?>" enctype="multipart/form-data">
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="signup-photo-preview" id="adminPhotoPreview">
              <?php if ($vPhotoPath): ?>
                <img src="<?= e(url($vPhotoPath)) ?>" alt="">
              <?php else: ?>
                <i class="bi bi-person-fill"></i>
              <?php endif; ?>
            </span>
            <div class="flex-fill">
              <label class="form-label-mini" for="photo">Photo de profil</label>
              <input type="file" class="form-control-auth" id="photo" name="photo" accept="image/png,image/jpeg,image/gif,image/webp">
              <p style="color:var(--gris-texte);font-size:.72rem;margin:.3rem 0 0;">JPG, PNG, GIF ou WEBP — 4 Mo maximum.</p>
              <?php if ($vPhotoPath): ?>
                <div class="form-check mt-1" style="padding-left:1.6rem;">
                  <input type="checkbox" class="form-check-input" id="remove_photo" name="remove_photo" value="1">
                  <label class="form-check-label" for="remove_photo" style="font-size:.8rem;color:var(--rouge-haiti);">Retirer la photo actuelle</label>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-8">
              <label class="form-label-mini" for="name">Nom complet</label>
              <input type="text" class="form-control-auth" id="name" name="name" value="<?= e((string) $vName) ?>" required>
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="ninu">Numéro NINU</label>
              <input type="text" class="form-control-auth" id="ninu" name="ninu" value="<?= e((string) $vNinu) ?>" required inputmode="numeric" pattern="[0-9]{10}" maxlength="10" title="Le numéro NINU doit contenir exactement 10 chiffres.">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-4">
              <label class="form-label-mini" for="sexe">Sexe</label>
              <select class="form-control-auth" id="sexe" name="sexe" required>
                <option value="">Choisir…</option>
                <?php foreach (['Homme', 'Femme', 'Autre'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= $vSexe === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="age">Âge</label>
              <input type="number" class="form-control-auth" id="age" name="age" min="18" value="<?= e((string) $vAge) ?>" required>
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="phone">Téléphone</label>
              <input type="text" class="form-control-auth" id="phone" name="phone" value="<?= e((string) $vPhone) ?>" required inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-mini" for="ville">Ville</label>
              <input type="text" class="form-control-auth" id="ville" name="ville" value="<?= e((string) $vVille) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="pays">Pays</label>
              <input type="text" class="form-control-auth" id="pays" name="pays" value="<?= e((string) $vPays) ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="email">Email</label>
            <input type="email" class="form-control-auth" id="email" name="email" value="<?= e((string) $vEmail) ?>" required>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label-mini" for="password"><?= $isEdit ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe' ?></label>
              <input type="password" class="form-control-auth" id="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="6">
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="password_confirm">Confirmation</label>
              <input type="password" class="form-control-auth" id="password_confirm" name="password_confirm" <?= $isEdit ? '' : 'required' ?> minlength="6">
            </div>
          </div>
          <?php if ($isEdit): ?>
            <p style="color:var(--gris-texte);font-size:.78rem;">Laissez les deux champs vides pour conserver le mot de passe actuel.</p>
          <?php endif; ?>

          <hr style="border-color:var(--ligne-claire);margin:1.5rem 0 1.2rem;">

          <div class="mb-3">
            <label class="form-label-mini" style="margin-bottom:.6rem;"><i class="bi bi-key-fill"></i> Permissions d'accès à l'administration</label>

            <?php if ($isSelf): ?>
              <div class="alert alert-info d-flex align-items-center gap-2" style="border-radius:var(--radius-md);font-size:.8rem;">
                <i class="bi bi-info-circle-fill"></i> Vous ne pouvez pas modifier vos propres permissions depuis cette page (pour éviter de vous bloquer vous-même hors d'un module).
              </div>
            <?php elseif (!$canEditPermissions): ?>
              <div class="alert alert-info d-flex align-items-center gap-2" style="border-radius:var(--radius-md);font-size:.8rem;">
                <i class="bi bi-info-circle-fill"></i> Seul un administrateur à accès complet peut modifier les permissions d'un autre compte.
              </div>
            <?php endif; ?>

            <div class="form-check mb-2" style="padding-left:1.6rem;">
              <input type="checkbox" class="form-check-input" id="full_access" name="full_access" value="1"
                <?= $currentPermissions === null ? 'checked' : '' ?>
                <?= !$canEditPermissions ? 'disabled' : '' ?>>
              <label class="form-check-label" for="full_access" style="font-weight:600;">
                <i class="bi bi-shield-check"></i> Accès complet (super administrateur — tous les modules, actuels et futurs)
              </label>
            </div>

            <div id="permissionModulesBox" class="row g-2" style="padding-left:.2rem;<?= $currentPermissions === null ? 'opacity:.5;' : '' ?>">
              <?php foreach ($permissionModules as $modKey => $mod): ?>
                <div class="col-sm-6">
                  <div class="form-check" style="padding-left:1.6rem;">
                    <input type="checkbox" class="form-check-input module-permission-checkbox" id="perm_<?= e($modKey) ?>" name="permissions[]" value="<?= e($modKey) ?>"
                      <?= ($currentPermissions !== null && in_array($modKey, $currentPermissions, true)) ? 'checked' : '' ?>
                      <?= (!$canEditPermissions || $currentPermissions === null) ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="perm_<?= e($modKey) ?>"><i class="bi <?= e($mod['icon']) ?>"></i> <?= e($mod['label']) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <p style="color:var(--gris-texte);font-size:.72rem;margin:.5rem 0 0;">Le tableau de bord reste toujours accessible à tout administrateur, quelles que soient ses permissions.</p>
          </div>

          <div class="admin-form-actions">
            <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : "Créer l'administrateur" ?></button>
            <a href="<?= url('/admin/administrateurs') ?>" class="mini-btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const input = document.getElementById('photo');
  const preview = document.getElementById('adminPhotoPreview');
  const removeCheckbox = document.getElementById('remove_photo');
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (!file) return;
    preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="">`;
    if (removeCheckbox) removeCheckbox.checked = false;
  });
})();

(function () {
  // Bascule visuelle + activation des cases à cocher de modules selon l'état
  // de "Accès complet" — uniquement pertinent quand la permission est modifiable
  // (les cases sont rendues "disabled" côté serveur sinon, ce script n'a alors rien à faire).
  const fullAccess = document.getElementById('full_access');
  const box = document.getElementById('permissionModulesBox');
  if (!fullAccess || !box || fullAccess.disabled) return;

  const moduleCheckboxes = box.querySelectorAll('.module-permission-checkbox');
  function syncModules() {
    const disable = fullAccess.checked;
    box.style.opacity = disable ? '.5' : '1';
    moduleCheckboxes.forEach((cb) => { cb.disabled = disable; });
  }
  fullAccess.addEventListener('change', syncModules);
  syncModules();
})();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
