<?php
$pageTitle      = 'Messages de contact — Administration';
$activeAdminNav = 'messages';
require VIEWS_PATH . '/layouts/admin_header.php';

$statusMeta = [
    'nouveau' => ['label' => 'Nouveau', 'class' => 'badge-out'],
    'lu'      => ['label' => 'Lu',      'class' => 'badge-admin'],
    'traite'  => ['label' => 'Traité',  'class' => 'badge-in'],
];
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Messages reçus</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['total'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Nouveaux</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--rouge-haiti, #ce1126);"><?= $counts['nouveaux'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Lus</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--bleu-nuit);"><?= $counts['lus'] ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-md-3">
    <div class="admin-card p-3">
      <div style="font-size:.78rem;color:var(--gris-texte);">Traités</div>
      <div style="font-size:1.6rem;font-weight:700;color:var(--vert-succes, #1c8a4b);"><?= $counts['traites'] ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <h2>Messages de contact <span style="color:var(--gris-texte);font-weight:400;">(<?= count($messages) ?>)</span></h2>
  </div>

  <p style="color:var(--gris-texte);font-size:.85rem;padding:0 1.2rem 1rem;">
    Messages envoyés par les visiteurs depuis le formulaire public de la page <strong>Contact</strong>. Un message est
    marqué <span class="badge-admin badge-out">Nouveau</span> à sa réception, puis passe automatiquement à
    <span class="badge-admin">Lu</span> dès son ouverture ; marquez-le <span class="badge-admin badge-in">Traité</span>
    une fois la demande résolue.
  </p>

  <?php if (empty($messages)): ?>
    <div class="admin-empty">Aucun message de contact reçu pour le moment.</div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>De</th>
        <th>Sujet</th>
        <th>Reçu le</th>
        <th>Statut</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($messages as $msg): $meta = $statusMeta[$msg['status']] ?? $statusMeta['nouveau']; ?>
      <tr style="<?= $msg['status'] === 'nouveau' ? 'font-weight:600;' : '' ?>">
        <td>
          <?= e($msg['name']) ?><br>
          <span style="color:var(--gris-texte);font-size:.78rem;font-weight:400;"><?= e($msg['email']) ?></span>
        </td>
        <td><?= e($msg['subject']) ?></td>
        <td style="font-weight:400;"><?= date('d/m/Y à H:i', strtotime($msg['created_at'])) ?></td>
        <td><span class="badge-admin <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span></td>
        <td>
          <div class="admin-actions">
            <a href="<?= url('/admin/messages/voir?id=' . $msg['id']) ?>" class="btn-outline-line btn-admin-sm"><i class="bi bi-eye-fill"></i> Voir</a>
            <form method="post" action="<?= url('/admin/messages/supprimer') ?>" onsubmit="return confirm('Supprimer définitivement ce message ?');">
              <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
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
