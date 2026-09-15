<?php
$pageTitle = 'Historique — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Historique</span>
    <h1>Toutes vos transactions, <span class="accent">au même endroit</span></h1>
    <p class="lead-text">Consultez vos envois, réceptions et paiements passés, avec un filtrage rapide par type d'opération.</p>
  </div>
</section>

<section class="goals" style="padding-top:0;">
  <div class="container" style="max-width:760px;">
    <div class="history-filters">
      <button type="button" class="filter-chip is-active" data-filter="tout">Tout</button>
      <button type="button" class="filter-chip" data-filter="envoye">Envoyés</button>
      <button type="button" class="filter-chip" data-filter="recu">Reçus</button>
      <button type="button" class="filter-chip" data-filter="paiement">Paiements</button>
    </div>

    <div id="historyList">
      <?php if (empty($operations)): ?>
        <p style="color:var(--gris-texte);text-align:center;padding:2rem 0;">Aucune transaction pour le moment.</p>
      <?php endif; ?>
      <?php foreach ($operations as $i => $op): ?>
        <?php
          $isLast = $i === count($operations) - 1;
          $sign = $op['sens'] === 'entrant' ? 'in' : 'out';

          switch ($op['type']) {
              case 'paiement':
                  $rowType = 'paiement';
                  $icon = 'bi-credit-card-2-front-fill';
                  $label = 'Paiement — ' . $op['category'] . ($op['note'] ? ' (' . $op['note'] . ')' : '');
                  break;
              case 'depot':
                  $rowType = 'recu';
                  $icon = 'bi-arrow-down-left';
                  $label = 'Billet ajouté par l\'administration';
                  break;
              case 'moncash_depot':
                  $rowType = 'recu';
                  $icon = 'bi-arrow-down-left';
                  $label = 'Dépôt MonCash';
                  break;
              case 'retrait_bancaire':
                  $rowType = 'envoye';
                  $icon = 'bi-bank';
                  $label = 'Retrait bancaire';
                  break;
              case 'retrait_especes':
                  $rowType = 'envoye';
                  $icon = 'bi-cash-stack';
                  $label = 'Retrait en espèces — ' . ($op['category'] ?? 'Agence');
                  break;
              case 'epargne_depot':
                  $rowType = 'envoye';
                  $icon = 'bi-piggy-bank';
                  $label = 'Transfert vers l\'épargne';
                  break;
              case 'epargne_retrait':
                  $rowType = 'recu';
                  $icon = 'bi-piggy-bank';
                  $label = 'Retrait depuis l\'épargne';
                  break;
              case 'envoi':
              default:
                  if ($op['sens'] === 'entrant') {
                      $rowType = 'recu';
                      $icon = 'bi-arrow-down-left';
                      $label = 'Reçu de ' . ($op['sender_phone'] ?? '—');
                  } else {
                      $rowType = 'envoye';
                      $icon = 'bi-arrow-up-right';
                      $label = 'Envoyé à ' . ($op['receiver_phone'] ?? '—');
                  }
                  break;
          }
          $date = (new DateTime($op['created_at']))->format('j F Y · H:i');
        ?>
        <div class="history-row" data-type="<?= $rowType ?>"<?= $isLast ? ' style="border-bottom:none;"' : '' ?>>
          <div class="hi <?= $sign ?>"><i class="bi <?= $icon ?>"></i></div>
          <div class="ht"><strong><?= e($label) ?></strong><small><?= e($date) ?></small></div>
          <div class="ha <?= $sign ?>"><?= $sign === 'in' ? '+' : '−' ?> <?= money($op['amount']) ?> HTG</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'JS'
// ---------- Filtres de l'historique (les données viennent déjà du serveur) ----------
document.querySelectorAll('.filter-chip').forEach(chip => {
  chip.addEventListener('click', () => {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('is-active'));
    chip.classList.add('is-active');
    const filter = chip.dataset.filter;
    document.querySelectorAll('#historyList .history-row').forEach(row => {
      row.style.display = (filter === 'tout' || row.dataset.type === filter) ? 'grid' : 'none';
    });
  });
});
JS;

require VIEWS_PATH . '/layouts/footer.php';
