<?php
$pageTitle      = ($isEdit ? 'Modifier un employé' : 'Nouvel employé') . ' — Administration';
$activeAdminNav = 'employes';
require VIEWS_PATH . '/layouts/admin_header.php';

$vName    = $employee['name']  ?? '';
$vNinu    = $employee['ninu']  ?? '';
$vSexe    = $employee['sexe']  ?? '';
$vAge     = $employee['age']   ?? '';
$vVille   = $employee['ville'] ?? '';
$vPays    = $employee['pays']  ?? 'Haïti';
$vPhone   = $employee['phone'] ?? '';
$vEmail   = $employee['email'] ?? '';
$vPhotoPath = $employee['photo_path'] ?? null;
?>

<a href="<?= url('/admin/employes') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour au dossier des employés</a>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-person-badge-fill"></i> <?= $isEdit ? "Modifier l'employé" : 'Nouvel employé' ?></h2>
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

        <form method="post" action="<?= $isEdit ? url('/admin/employes/modifier?id=' . (int) $employee['id']) : url('/admin/employes/nouveau') ?>" enctype="multipart/form-data">
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="signup-photo-preview" id="employePhotoPreview">
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

            <div class="alert alert-info d-flex align-items-center gap-2" style="border-radius:var(--radius-md);font-size:.8rem;">
              <i class="bi bi-info-circle-fill"></i> Un employé n'a jamais d'accès complet : cochez uniquement les modules dont il a besoin pour son poste.
            </div>

            <div class="row g-2" style="padding-left:.2rem;">
              <?php foreach ($permissionModules as $modKey => $mod): ?>
                <div class="col-sm-6">
                  <div class="form-check" style="padding-left:1.6rem;">
                    <input type="checkbox" class="form-check-input" id="perm_<?= e($modKey) ?>" name="permissions[]" value="<?= e($modKey) ?>"
                      <?= in_array($modKey, $currentPermissions, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="perm_<?= e($modKey) ?>"><i class="bi <?= e($mod['icon']) ?>"></i> <?= e($mod['label']) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <p style="color:var(--gris-texte);font-size:.72rem;margin:.5rem 0 0;">Le tableau de bord reste toujours accessible à tout employé connecté, quelles que soient ses permissions. La gestion des administrateurs et celle des employés restent réservées aux comptes administrateurs.</p>
          </div>

          <div class="admin-form-actions">
            <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : "Créer l'employé" ?></button>
            <a href="<?= url('/admin/employes') ?>" class="mini-btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const input = document.getElementById('photo');
  const preview = document.getElementById('employePhotoPreview');
  const removeCheckbox = document.getElementById('remove_photo');
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (!file) return;
    preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="">`;
    if (removeCheckbox) removeCheckbox.checked = false;
  });
})();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
