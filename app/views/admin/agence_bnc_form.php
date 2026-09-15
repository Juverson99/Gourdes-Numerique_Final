<?php
$pageTitle      = ($isEdit ? 'Modifier une agence' : 'Nouvelle agence') . ' — Administration';
$activeAdminNav = 'agences-bnc';
require VIEWS_PATH . '/layouts/admin_header.php';

$vNom         = $agence['nom']         ?? '';
$vInstitutionId = $agence['institution_id'] ?? '';
$vDepartement = $agence['departement'] ?? 'Ouest';
$vVille       = $agence['ville']       ?? '';
$vAdresse     = $agence['adresse']     ?? '';
$vTelephone   = $agence['telephone']   ?? '';
$vLatitude    = $agence['latitude']    ?? '18.5453';
$vLongitude   = $agence['longitude']   ?? '-72.3402';
$vPrincipale  = $agence !== null ? !empty($agence['principale']) : false;
$vSortOrder   = $agence['sort_order']  ?? BncAgence::nextSortOrder();
$vActive      = $agence !== null ? !empty($agence['active']) : true;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<a href="<?= url('/admin/agences-bnc') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour aux agences</a>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-bank2"></i> <?= $isEdit ? 'Modifier l\'agence' : 'Nouvelle agence' ?></h2>
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

        <form method="post" action="<?= $isEdit ? url('/admin/agences-bnc/modifier?id=' . (int) $agence['id']) : url('/admin/agences-bnc/nouveau') ?>">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label-mini" for="nom">Nom de l'agence</label>
              <input type="text" class="form-control-auth" id="nom" name="nom" value="<?= e((string) $vNom) ?>" placeholder="Ex : BNC Delmas 33" required>
            </div>
            <div class="col-md-4">
              <label class="form-label-mini" for="departement">Département</label>
              <select class="form-control-auth" id="departement" name="departement" required>
                <?php foreach (BncAgence::departements() as $dep): ?>
                  <option value="<?= e($dep) ?>" <?= $vDepartement === $dep ? 'selected' : '' ?>><?= e($dep) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="institution_id">Institution bancaire</label>
            <select class="form-control-auth" id="institution_id" name="institution_id">
              <option value="">— Non rattachée —</option>
              <?php foreach ($banques as $b): ?>
                <option value="<?= (int) $b['id'] ?>" <?= (string) $vInstitutionId === (string) $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <p style="color:var(--gris-texte);font-size:.78rem;margin:.4rem 0 0;">
              Détermine à quelle banque, dans le dossier « Institutions financières », sont attribués
              les retraits en espèces effectués dans cette agence.
            </p>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label-mini" for="ville">Ville</label>
              <input type="text" class="form-control-auth" id="ville" name="ville" value="<?= e((string) $vVille) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-mini" for="telephone">Téléphone (facultatif)</label>
              <input type="tel" class="form-control-auth" id="telephone" name="telephone" value="<?= e((string) $vTelephone) ?>" placeholder="Ex : 509 46213235" inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label-mini" for="adresse">Adresse (facultatif)</label>
            <input type="text" class="form-control-auth" id="adresse" name="adresse" value="<?= e((string) $vAdresse) ?>" placeholder="Rue, quartier…">
          </div>

          <div class="mb-3">
            <label class="form-label-mini">Emplacement sur la carte</label>
            <p style="color:var(--gris-texte);font-size:.78rem;margin:0 0 .5rem;">Cliquez sur la carte pour positionner l'agence, ou saisissez les coordonnées manuellement.</p>
            <div id="pickerMap" style="height:340px;border-radius:var(--radius-md);border:1px solid rgba(0,61,122,.15);"></div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label-mini" for="latitude">Latitude</label>
              <input type="text" class="form-control-auth" id="latitude" name="latitude" value="<?= e((string) $vLatitude) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-mini" for="longitude">Longitude</label>
              <input type="text" class="form-control-auth" id="longitude" name="longitude" value="<?= e((string) $vLongitude) ?>" required>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-mini" for="sort_order">Ordre d'affichage</label>
              <input type="number" class="form-control-auth" id="sort_order" name="sort_order" step="1" value="<?= e((string) $vSortOrder) ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check" style="padding-left:1.6rem;">
                <input type="checkbox" class="form-check-input" id="principale" name="principale" value="1" <?= $vPrincipale ? 'checked' : '' ?>>
                <label class="form-check-label" for="principale" style="font-size:.85rem;">Agence principale (siège)</label>
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check" style="padding-left:1.6rem;">
                <input type="checkbox" class="form-check-input" id="active" name="active" value="1" <?= $vActive ? 'checked' : '' ?>>
                <label class="form-check-label" for="active" style="font-size:.85rem;">Visible sur la carte publique</label>
              </div>
            </div>
          </div>

          <div class="admin-form-actions">
            <button type="submit" class="mini-btn flex-fill"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Enregistrer' : 'Créer l\'agence' ?></button>
            <a href="<?= url('/admin/agences-bnc') ?>" class="mini-btn-outline">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const latInput = document.getElementById('latitude');
  const lngInput = document.getElementById('longitude');
  const startLat = parseFloat(latInput.value) || 18.9712;
  const startLng = parseFloat(lngInput.value) || -72.2852;

  const map = L.map('pickerMap').setView([startLat, startLng], 8);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', subdomains: 'abc', maxZoom: 18, minZoom: 7
  }).addTo(map);

  let marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

  function updateInputs(lat, lng) {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
  }

  marker.on('dragend', function () {
    const pos = marker.getLatLng();
    updateInputs(pos.lat, pos.lng);
  });

  map.on('click', function (e) {
    marker.setLatLng(e.latlng);
    updateInputs(e.latlng.lat, e.latlng.lng);
  });
})();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
