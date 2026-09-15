<?php
$pageTitle      = 'Institutions financières — Administration';
$activeAdminNav = 'institutions';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Institutions partenaires</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['total'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Actives</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--vert-succes, #1c8a4b);"><?= $counts['actifs'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Suspendues</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--rouge-haiti, #ce1126);"><?= $counts['suspendus'] ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Institutions financières <span style="color:var(--gris-texte);font-weight:400;">(<?= count($institutions) ?>)</span></h2>
    <div class="d-flex align-items-center gap-2">
      <form class="admin-search" method="get" action="<?= url('/admin/institutions') ?>">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par nom, licence, ville…">
        <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
      </form>
      <a href="<?= url('/admin/institutions/nouveau') ?>" class="mini-btn btn-admin-header"><i class="bi bi-plus-lg"></i> Nouvelle institution</a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success m-3" style="border-radius:var(--radius-md);"><i class="bi bi-check-circle-fill"></i> Institution enregistrée avec succès.</div>
  <?php endif; ?>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Répertoire des banques, coopératives et émetteurs de monnaie électronique partenaires
    de la plateforme. Seules les institutions <span class="badge-admin badge-in">actives</span>
    sont considérées comme partenaires opérationnels.
  </p>

  <?php if (empty($institutions)): ?>
    <div class="admin-empty"><?= $search !== '' ? 'Aucune institution ne correspond à cette recherche.' : 'Aucune institution enregistrée pour le moment.' ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Institution</th>
        <th>Type</th>
        <th>N° de licence (BRH)</th>
        <th>Contact</th>
        <th>Ville</th>
        <th>Montant reçu des clients</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($institutions as $inst): ?>
      <tr>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($inst['logo_path'])): ?>
              <img src="<?= e(url($inst['logo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($inst['name'])) ?>
            <?php endif; ?>
          </span>
          <?= e($inst['name']) ?>
        </td>
        <td><?= e($typeLabels[$inst['type']] ?? $inst['type']) ?></td>
        <td class="mono"><?= $inst['license_number'] ? e($inst['license_number']) : '—' ?></td>
        <td>
          <?php if ($inst['contact_name'] || $inst['phone'] || $inst['email']): ?>
            <?= $inst['contact_name'] ? e($inst['contact_name']) . '<br>' : '' ?>
            <span style="color:var(--gris-texte);font-size:.78rem;">
              <?= e(trim(($inst['phone'] ?? '') . ($inst['phone'] && $inst['email'] ? ' · ' : '') . ($inst['email'] ?? ''))) ?>
            </span>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?= $inst['ville'] ? e($inst['ville']) : '—' ?></td>
        <td class="mono">
          <?php if ($inst['type'] === 'banque'): ?>
            <a href="<?= url('/admin/institutions/mouvements?id=' . $inst['id']) ?>" style="color:var(--bleu-nuit);font-weight:600;" title="Voir le détail des virements et retraits en espèces">
              <?= money($montants[$inst['id']]['total'] ?? 0) ?> HTG
            </a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td>
          <span class="badge-admin <?= $inst['status'] === 'actif' ? 'badge-in' : 'badge-out' ?>">
            <?= $inst['status'] === 'actif' ? 'Actif' : 'Suspendu' ?>
          </span>
        </td>
        <td>
          <div class="admin-actions">
            <?php if ($inst['type'] === 'banque'): ?>
              <a href="<?= url('/admin/institutions/mouvements?id=' . $inst['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-eye-fill"></i> Mouvements</a>
            <?php endif; ?>
            <a href="<?= url('/admin/institutions/modifier?id=' . $inst['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-pencil-fill"></i> Modifier</a>
            <form method="post" action="<?= url('/admin/institutions/basculer') ?>">
              <input type="hidden" name="id" value="<?= (int) $inst['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm">
                <i class="bi <?= $inst['status'] === 'actif' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                <?= $inst['status'] === 'actif' ? 'Suspendre' : 'Réactiver' ?>
              </button>
            </form>
            <form method="post" action="<?= url('/admin/institutions/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement cette institution ?');">
              <input type="hidden" name="id" value="<?= (int) $inst['id'] ?>">
              <button type="submit" class="btn-outline-line btn-admin-sm danger"><i class="bi bi-trash3-fill"></i> Supprimer</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
