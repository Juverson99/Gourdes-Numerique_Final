<?php
$pageTitle      = ($isEdit ? 'Modifier une catégorie' : 'Nouvelle catégorie') . ' — Administration';
$activeAdminNav = 'categories-paiement';
require VIEWS_PATH . '/layouts/admin_header.php';

$vName      = $categorie['name']       ?? '';
$vIcon      = $categorie['icon']       ?? PaymentCategory::defaultIcon();
$vSortOrder = $categorie['sort_order'] ?? PaymentCategory::nextSortOrder();
$vActive    = $categorie !== null ? !empty($categorie['active']) : true;

// Quelques icônes courantes proposées en suggestion (marchands, factures, services publics)
$iconSuggestions = [
    'bi-shop', 'bi-lightning-charge-fill', 'bi-droplet-fill', 'bi-wifi',
    'bi-bus-front-fill', 'bi-mortarboard-fill', 'bi-cart-fill', 'bi-receipt',
    'bi-phone-fill', 'bi-house-door-fill', 'bi-tv-fill', 'bi-cup-hot-fill',
];
?>

<a href="<?= url('/admin/categories-paiement') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour aux catégories</a>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-tags-fill"></i> <?= $isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h2>
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

        <form method="post" action="<?= $isEdit ? url('/admin/categories-paiement/modifier?id=' . (int) $categorie['id']) : url('/admin/categories-paiement/nouveau') ?>">
          <div class="mb-3">
            <label class="form-label-mini" for="name">Nom de la catégorie</label>
            <input type="text" class="form-control-auth" id="name" name="name" maxlength="80" value="<?= e((string) $vName) ?>" placeholder="Ex : Électricité (EDH)" required>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="icon">Icône (Bootstrap Icons)</label>
            <div class="d-flex align-items-center gap-2">
              <span id="iconPreview" style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;border-radius:10px;background:var(--bleu-clair,#eaf2fb);color:var(--bleu-nuit);font-size:1.2rem;"><i class="bi <?= e((string) $vIcon) ?>" id="iconPreviewIcon"></i></span>
              <input type="text" class="form-control-auth flex-fill" id="icon" name="icon" list="iconSuggestions" maxlength="60" value="<?= e((string) $vIcon) ?>" placeholder="Ex : bi-lightning-charge-fill">
            </div>
            <datalist id="iconSuggestions">
              <?php foreach ($iconSuggestions as $ic): ?><option value="<?= e($ic) ?>"><?php endforeach; ?>
            </datalist>
            <small style="color:var(--gris-texte);">Nom d'une classe <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">Bootstrap Icons</a>, ex : <code>bi-shop</code>, <code>bi-wifi</code>.</small>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label-mini" for="sort_order">Ordre d'affichage</label>
              <input type="number" class="form-control-auth" id="sort_order" name="sort_order" step="1" value="<?= e((string) $vSortOrder) ?>">
            </div>
            <div class="col-6 d-flex align-items-end">
              <div class="form-check" style="padding-left:1.6rem;">
                <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= $vActive ? 'checked' : '' ?>>
                <label class="form-check-label" for="active" style="font-size:.85rem;">Catégorie active (visible sur la page Paiement)</label>
              </div>
            </div>
          </div>

          <div class="admin-form-actions">
            <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : 'Créer la catégorie' ?></button>
            <a href="<?= url('/admin/categories-paiement') ?>" class="mini-btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  const iconInput = document.getElementById('icon');
  const iconPreviewIcon = document.getElementById('iconPreviewIcon');
  if (iconInput && iconPreviewIcon) {
    iconInput.addEventListener('input', () => {
      const val = iconInput.value.trim() || 'bi-shop';
      iconPreviewIcon.className = 'bi ' + val;
    });
  }
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
