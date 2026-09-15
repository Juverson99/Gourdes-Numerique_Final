<?php
$pageTitle      = 'Mouvements reçus — ' . $institution['name'] . ' — Administration';
$activeAdminNav = 'institutions';
require VIEWS_PATH . '/layouts/admin_header.php';

$typeInfo = [
    'retrait_bancaire' => ['label' => 'Virement bancaire',  'icon' => 'bi-bank',       'bg' => 'rgba(0,61,122,.1)',  'fg' => 'var(--bleu-nuit)'],
    'retrait_especes'  => ['label' => 'Retrait en espèces', 'icon' => 'bi-cash-stack', 'bg' => 'rgba(212,160,23,.15)', 'fg' => '#8a6c0c'],
];
?>

<a href="<?= url('/admin/institutions') ?>" class="breadcrumb-back d-inline-block mb-3"><i class="bi bi-arrow-left"></i> Retour au dossier des institutions</a>

<div class="row g-3 mb-3">
  <div class="col-md-8">
    <div class="admin-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <span class="table-avatar" style="width:48px;height:48px;font-size:1.1rem;">
          <?php if (!empty($institution['logo_path'])): ?>
            <img src="<?= e(url($institution['logo_path'])) ?>" alt="">
          <?php else: ?>
            <?= e(initial_letter($institution['name'])) ?>
          <?php endif; ?>
        </span>
        <div>
          <strong style="font-size:1.1rem;"><?= e($institution['name']) ?></strong>
          <div style="font-size:.8rem;color:var(--gris-texte);">
            <?= $institution['license_number'] ? 'Licence BRH n° ' . e($institution['license_number']) : 'Aucun numéro de licence renseigné' ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Total reçu de la part des clients</div>
      <div style="font-size:1.5rem;font-weight:700;color:var(--bleu-nuit);"><?= money($total) ?> HTG</div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Mouvements reçus <span style="color:var(--gris-texte);font-weight:400;">(<?= count($mouvements) ?>)</span></h2>
    <form class="admin-search" method="get" action="<?= url('/admin/institutions/mouvements') ?>">
      <input type="hidden" name="id" value="<?= (int) $institution['id'] ?>">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Rechercher par client, NINU, référence…">
      <button type="submit" class="btn-search-admin"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Toutes les opérations réussies par lesquelles un client envoie de l'argent numérique vers cette
    banque — un virement bancaire électronique (dossier client « Convertir », volet « Retrait
    bancaire ») ou un retrait en espèces honoré au guichet d'une de ses agences (le client envoie de
    l'argent numérique et reçoit de l'argent physique que la banque débourse) — avec les informations
    importantes du client concerné.
  </p>

  <?php if (empty($mouvements)): ?>
    <div class="admin-empty"><?= $search !== '' ? 'Aucun mouvement ne correspond à cette recherche.' : 'Aucun mouvement reçu pour le moment.' ?></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Type</th>
        <th>Client</th>
        <th>NINU</th>
        <th>Contact</th>
        <th>Référence</th>
        <th>Détail</th>
        <th>Montant</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($mouvements as $m): ?>
      <?php $info = $typeInfo[$m['type']] ?? ['label' => $m['type'], 'icon' => 'bi-arrow-left-right', 'bg' => 'rgba(0,0,0,.06)', 'fg' => 'var(--bleu-nuit)']; ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
        <td><span style="background:<?= $info['bg'] ?>;color:<?= $info['fg'] ?>;padding:.25rem .6rem;border-radius:1rem;font-size:.75rem;font-weight:600;white-space:nowrap;"><i class="bi <?= $info['icon'] ?>"></i> <?= e($info['label']) ?></span></td>
        <td style="font-weight:600;">
          <span class="table-avatar">
            <?php if (!empty($m['sender_photo_path'])): ?>
              <img src="<?= e(url($m['sender_photo_path'])) ?>" alt="">
            <?php else: ?>
              <?= e(initial_letter($m['sender_name'] ?? '?')) ?>
            <?php endif; ?>
          </span>
          <?php if (!empty($m['sender_id'])): ?>
            <a href="<?= url('/admin/client?id=' . $m['sender_id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($m['sender_name'] ?? '—') ?></a>
          <?php else: ?>
            <?= e($m['sender_name'] ?? '—') ?>
          <?php endif; ?>
          <?php if (!empty($m['sender_ville'])): ?>
            <div style="font-size:.75rem;color:var(--gris-texte);font-weight:400;"><?= e($m['sender_ville']) ?></div>
          <?php endif; ?>
        </td>
        <td class="mono"><?= $m['sender_ninu'] ? e($m['sender_ninu']) : '—' ?></td>
        <td>
          <?php if ($m['sender_phone'] || $m['sender_email']): ?>
            <?= $m['sender_phone'] ? e($m['sender_phone']) . '<br>' : '' ?>
            <span style="color:var(--gris-texte);font-size:.78rem;"><?= $m['sender_email'] ? e($m['sender_email']) : '' ?></span>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td class="mono"><?= e($m['reference']) ?></td>
        <td><?= $m['note'] ? e($m['note']) : '—' ?></td>
        <td class="mono" style="font-weight:600;">-<?= money($m['amount']) ?> HTG</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
