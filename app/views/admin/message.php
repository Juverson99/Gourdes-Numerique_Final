<?php
$pageTitle      = 'Message de contact — Administration';
$activeAdminNav = 'messages';
require VIEWS_PATH . '/layouts/admin_header.php';

$statusMeta = [
    'nouveau' => ['label' => 'Nouveau', 'class' => 'badge-out'],
    'lu'      => ['label' => 'Lu',      'class' => 'badge-admin'],
    'traite'  => ['label' => 'Traité',  'class' => 'badge-in'],
];
$meta = $statusMeta[$message['status']] ?? $statusMeta['nouveau'];
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2><i class="bi bi-envelope-open-fill"></i> <?= e($message['subject']) ?></h2>
        <span class="badge-admin <?= e($meta['class']) ?>"><?= e($meta['label']) ?></span>
      </div>

      <div class="p-3">
        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <div class="form-label-mini">De</div>
            <div style="font-weight:600;color:var(--bleu-nuit);"><?= e($message['name']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="form-label-mini">Reçu le</div>
            <div style="font-weight:600;color:var(--bleu-nuit);"><?= date('d/m/Y à H:i', strtotime($message['created_at'])) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="form-label-mini">Courriel</div>
            <div><a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a></div>
          </div>
          <div class="col-sm-6">
            <div class="form-label-mini">Téléphone</div>
            <div><?= $message['phone'] ? '<a href="tel:' . e($message['phone']) . '">' . e($message['phone']) . '</a>' : '—' ?></div>
          </div>
        </div>

        <div class="form-label-mini">Message</div>
        <div style="background:var(--blanc-casse, #f6f4ef);border-radius:var(--radius-md);padding:1rem 1.2rem;white-space:pre-wrap;color:var(--bleu-nuit);line-height:1.6;">
<?= e($message['message']) ?>
        </div>

        <div class="admin-form-actions mt-4">
          <a href="mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re : ' . $message['subject']) ?>" class="mini-btn flex-fill"><i class="bi bi-reply-fill"></i> Répondre par courriel</a>
          <?php if ($message['status'] !== 'traite'): ?>
          <form method="post" action="<?= url('/admin/messages/traiter') ?>">
            <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
            <button type="submit" class="mini-btn-outline"><i class="bi bi-check-lg"></i> Marquer comme traité</button>
          </form>
          <?php endif; ?>
          <a href="<?= url('/admin/messages') ?>" class="mini-btn-outline">Retour au dossier</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
