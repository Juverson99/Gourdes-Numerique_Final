<?php
$pageTitle      = 'Tableau de bord — Administration';
$activeAdminNav = 'dashboard';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
      <div class="stat-label">Clients inscrits</div>
      <div class="stat-value"><?= number_format($stats['total_clients'], 0, ',', ' ') ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-coin"></i></div>
      <div class="stat-label">Masse monétaire (clients)</div>
      <div class="stat-value" style="font-size:1.15rem;"><?= money($stats['masse_monetaire']) ?> HTG</div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-piggy-bank-fill"></i></div>
      <div class="stat-label">Total épargné</div>
      <div class="stat-value" style="font-size:1.15rem;"><?= money($stats['total_epargne']) ?> HTG</div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-inboxes-fill"></i></div>
      <div class="stat-label">Réceptions enregistrées</div>
      <div class="stat-value"><?= number_format(count($stats['total_recu']), 0, ',', ' ') ?></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="admin-stat-card">
      <div class="stat-icon"><i class="bi bi-boxes"></i></div>
      <div class="stat-label">Billets en stock</div>
      <div class="stat-value"><?= number_format($stats['stock_billets'], 0, ',', ' ') ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <div>
          <h2>Activité des 7 derniers jours</h2>
          <div style="font-size:.78rem;color:var(--gris-texte);">Volume des opérations réussies, en HTG</div>
        </div>
      </div>
      <div class="p-3">
        <canvas id="chart-volume" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Répartition des opérations</h2></div>
      <div class="p-3 d-flex flex-column align-items-center gap-3">
        <canvas id="chart-repartition" height="190"></canvas>
        <div id="repartition-legend" style="display:flex;flex-direction:column;gap:8px;width:100%;font-size:.8rem;"></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Accès rapide</h2></div>
      <div class="admin-quick-links p-3">
        <a href="<?= url('/admin/recevoir') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-inboxes-fill"></i> Voir les réceptions</a>
        <a href="<?= url('/admin/envoyer') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-send-fill"></i> Voir les envois</a>
        <a href="<?= url('/admin/paiement') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-credit-card-fill"></i> Voir les paiements</a>
        <a href="<?= url('/admin/epargne') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-piggy-bank-fill"></i> Gérer l'épargne</a>
        <a href="<?= url('/admin/cartes-virtuelles') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-credit-card-2-front-fill"></i> Cartes virtuelles</a>
        <a href="<?= url('/admin/billet') ?>" class="btn-gold btn-admin-header"><i class="bi bi-plus-lg"></i> Ajouter un billet</a>
        <a href="<?= url('/admin/historique') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-clock-history"></i> Historique global</a>
        <a href="<?= url('/admin/statistiques') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-bar-chart-fill"></i> Voir les statistiques</a>
        <a href="<?= url('/admin/billets') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-wallet2"></i> Gestion des billets</a>
        <a href="<?= url('/admin/categories-billets') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-tags-fill"></i> Catégories de billets</a>
        <a href="<?= url('/admin/stock') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-boxes"></i> Gestion du stock</a>
        <a href="<?= url('/admin/alertes-stock') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-bell-fill"></i> Alertes de stock</a>
        <a href="<?= url('/admin/institutions') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-bank"></i> Institutions financières</a>
        <a href="<?= url('/admin/fournisseurs') ?>" class="btn-outline-line btn-admin-header"><i class="bi bi-truck"></i> Fournisseurs</a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2>Derniers clients inscrits</h2>
        <a href="<?= url('/admin/clients') ?>" style="font-size:.8rem;color:var(--bleu-royal);">Voir le dossier des clients →</a>
      </div>
      <?php if (empty($derniersClients)): ?>
        <div class="admin-empty">Aucun client inscrit pour le moment.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Nom</th><th>Ville</th><th>Solde</th></tr></thead>
        <tbody>
        <?php foreach ($derniersClients as $c): ?>
          <tr>
            <td><a href="<?= url('/admin/client?id=' . $c['id']) ?>" style="color:var(--bleu-nuit);font-weight:600;"><?= e($c['name']) ?></a></td>
            <td><?= e($c['ville']) ?></td>
            <td class="mono"><?= money($c['balance']) ?> HTG</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2>Dernières réceptions</h2>
        <a href="<?= url('/admin/recevoir') ?>" style="font-size:.8rem;color:var(--bleu-royal);">Voir tout →</a>
      </div>
      <?php if (empty($dernieresReceptions)): ?>
        <div class="admin-empty">Aucune réception enregistrée pour le moment.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
      <table class="admin-table">
        <thead><tr><th>Client</th><th>Type</th><th>Montant</th></tr></thead>
        <tbody>
        <?php foreach ($dernieresReceptions as $r): ?>
          <tr>
            <td><?= e($r['receiver_name'] ?? '—') ?></td>
            <td><span class="badge-admin badge-in"><?= $r['type'] === 'depot' ? 'Billet admin' : 'Envoi reçu' ?></span></td>
            <td class="mono">+<?= money($r['amount']) ?> HTG</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  const volumeLabels = <?= json_encode(array_column($chartVolume7j, 'label')) ?>;
  const volumeData    = <?= json_encode(array_map(fn($j) => round($j['total'], 2), $chartVolume7j)) ?>;

  const repartition      = <?= json_encode($chartRepartition) ?>;
  const repartitionLabels = Object.keys(repartition);
  const repartitionValues = Object.values(repartition);
  const repartitionColors = ['#123a7a', '#d4af37', '#3aa0ff', '#ce1126'];

  const c1 = document.getElementById('chart-volume');
  if (c1) {
    new Chart(c1, {
      type: 'line',
      data: {
        labels: volumeLabels,
        datasets: [{
          label: 'Volume HTG',
          data: volumeData,
          borderColor: '#123a7a',
          backgroundColor: 'rgba(18,58,122,.08)',
          fill: true, tension: .4,
          pointBackgroundColor: '#123a7a',
          pointBorderColor: '#fff', pointBorderWidth: 2,
          pointRadius: 5, pointHoverRadius: 7,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(11,27,58,.05)' }, ticks: { font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } },
          y: { grid: { color: 'rgba(11,27,58,.05)' }, ticks: { callback: v => v.toLocaleString('fr-HT'), font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } }
        }
      }
    });
  }

  const c2 = document.getElementById('chart-repartition');
  if (c2) {
    new Chart(c2, {
      type: 'doughnut',
      data: {
        labels: repartitionLabels,
        datasets: [{ data: repartitionValues, backgroundColor: repartitionColors, borderWidth: 3, borderColor: '#fff' }]
      },
      options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
  }

  const total = repartitionValues.reduce((a, b) => a + b, 0) || 1;
  const legend = document.getElementById('repartition-legend');
  if (legend) {
    legend.innerHTML = repartitionLabels.map((label, i) => {
      const pct = Math.round((repartitionValues[i] / total) * 100);
      return `<div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:${repartitionColors[i]};display:inline-block;"></span>${label}
        </span>
        <strong>${pct}%</strong>
      </div>`;
    }).join('');
  }
})();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
