<?php
$pageTitle      = 'Rapports — Administration';
$activeAdminNav = 'rapports';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="admin-card mb-3">
  <div class="admin-card-header">
    <h2><i class="bi bi-file-earmark-bar-graph-fill"></i> Dossier des rapports</h2>
  </div>
  <div class="p-3" style="color:var(--gris-texte);font-size:.85rem;">
    Générez et téléchargez les principaux dossiers de la plateforme en Excel (<code>.xlsx</code>) ou en PDF.
    Chaque rapport reflète les données actuelles au moment du téléchargement.
  </div>
</div>

<div class="row g-3">
  <?php foreach ($rapportTypes as $key => $r): ?>
  <div class="col-md-6 col-lg-4">
    <div class="admin-card h-100 d-flex flex-column">
      <div class="p-3 d-flex flex-column flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="width:38px;height:38px;border-radius:10px;background:rgba(11,27,58,.06);display:flex;align-items:center;justify-content:center;color:var(--bleu-nuit);font-size:1.1rem;flex-shrink:0;">
            <i class="bi <?= e($r['icon']) ?>"></i>
          </span>
          <h3 style="font-size:.95rem;font-family:var(--font-display);font-weight:700;color:var(--bleu-nuit);margin:0;"><?= e($r['label']) ?></h3>
        </div>
        <p style="font-size:.78rem;color:var(--gris-texte);flex-grow:1;"><?= e($r['desc']) ?></p>
        <div class="d-flex gap-2 mt-2">
          <a href="<?= url('/admin/rapports/excel?type=' . urlencode($key)) ?>" class="btn-outline-line btn-admin-sm" style="flex:1;justify-content:center;">
            <i class="bi bi-file-earmark-excel-fill" style="color:#1c8a4b;"></i> Excel
          </a>
          <a href="<?= url('/admin/rapports/pdf?type=' . urlencode($key)) ?>" class="btn-outline-line btn-admin-sm" style="flex:1;justify-content:center;">
            <i class="bi bi-file-earmark-pdf-fill" style="color:var(--rouge-haiti,#ce1126);"></i> PDF
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
