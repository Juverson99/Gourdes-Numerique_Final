<?php
$pageTitle      = ($isEdit ? 'Modifier une catégorie' : 'Nouvelle catégorie') . ' — Administration';
$activeAdminNav = 'categories-billets';
require VIEWS_PATH . '/layouts/admin_header.php';

$vName        = $categorie['name']        ?? '';
$vDescription = $categorie['description'] ?? '';
$vSortOrder   = $categorie['sort_order']  ?? BankNoteCategory::nextSortOrder();
$vActive      = $categorie !== null ? !empty($categorie['active']) : true;
?>

<a href="<?= url('/admin/categories-billets') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour aux catégories</a>

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

        <form method="post" action="<?= $isEdit ? url('/admin/categories-billets/modifier?id=' . (int) $categorie['id']) : url('/admin/categories-billets/nouveau') ?>">
          <div class="mb-3">
            <label class="form-label-mini" for="name">Nom de la catégorie</label>
            <input type="text" class="form-control-auth" id="name" name="name" maxlength="80" value="<?= e((string) $vName) ?>" placeholder="Ex : Petites coupures" required>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="description">Description (facultatif)</label>
            <textarea class="form-control-auth" id="description" name="description" rows="2" maxlength="255" placeholder="Visible uniquement en admin"><?= e((string) $vDescription) ?></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label-mini" for="sort_order">Ordre d'affichage</label>
              <input type="number" class="form-control-auth" id="sort_order" name="sort_order" step="1" value="<?= e((string) $vSortOrder) ?>">
            </div>
            <div class="col-6 d-flex align-items-end">
              <div class="form-check" style="padding-left:1.6rem;">
                <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= $vActive ? 'checked' : '' ?>>
                <label class="form-check-label" for="active" style="font-size:.85rem;">Catégorie active (utilisable dans le formulaire des billets)</label>
              </div>
            </div>
          </div>

          <div class="admin-form-actions">
            <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : 'Créer la catégorie' ?></button>
            <a href="<?= url('/admin/categories-billets') ?>" class="mini-btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
