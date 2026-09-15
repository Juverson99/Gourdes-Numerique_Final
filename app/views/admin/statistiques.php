<?php
$pageTitle      = 'Statistiques — Administration';
$activeAdminNav = 'statistiques';
require VIEWS_PATH . '/layouts/admin_header.php';
?>

<div class="row g-3 mb-3">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header">
        <div>
          <h2>Top clients par solde</h2>
          <div style="font-size:.78rem;color:var(--gris-texte);">Les 5 comptes clients au solde courant le plus élevé</div>
        </div>
      </div>
      <div class="p-3">
        <?php if (empty($topClients)): ?>
          <div class="admin-empty">Aucun client pour le moment.</div>
        <?php else: ?>
          <canvas id="chart-top-clients" height="260"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header">
        <div>
          <h2>Volume mensuel des opérations</h2>
          <div style="font-size:.78rem;color:var(--gris-texte);">Total des opérations réussies (12 derniers mois), en HTG</div>
        </div>
      </div>
      <div class="p-3">
        <canvas id="chart-volume-mensuel" height="260"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Répartition des paiements par catégorie</h2></div>
      <div class="p-3 d-flex flex-column align-items-center gap-3">
        <?php if (empty($paiementsParCategorie)): ?>
          <div class="admin-empty">Aucun paiement enregistré pour le moment.</div>
        <?php else: ?>
          <canvas id="chart-paiements-categorie" height="220" style="max-width:320px;"></canvas>
          <div id="paiements-categorie-legend" style="display:flex;flex-direction:column;gap:8px;width:100%;font-size:.8rem;"></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Provenance des clients</h2></div>
      <div class="p-3">
        <?php if (empty($villesClients)): ?>
          <div class="admin-empty">Aucun client pour le moment.</div>
        <?php else: ?>
          <?php $totalVilles = array_sum($villesClients) ?: 1; ?>
          <div style="display:flex;flex-direction:column;gap:.85rem;">
            <?php foreach ($villesClients as $ville => $nb): $pct = round(($nb / $totalVilles) * 100); ?>
              <div>
                <div class="d-flex justify-content-between" style="font-size:.82rem;margin-bottom:.25rem;">
                  <span style="font-weight:600;"><i class="bi bi-geo-alt-fill" style="color:var(--or);"></i> <?= e($ville) ?></span>
                  <span class="mono" style="color:var(--gris-texte);"><?= $nb ?> client<?= $nb > 1 ? 's' : '' ?> · <?= $pct ?>%</span>
                </div>
                <div style="height:8px;border-radius:20px;background:var(--gris-fond, #eef1f7);overflow:hidden;">
                  <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg, var(--bleu-nuit), var(--bleu-royal));border-radius:20px;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="admin-card">
      <div class="admin-card-header">
        <div>
          <h2>Nouveaux clients par mois</h2>
          <div style="font-size:.78rem;color:var(--gris-texte);">Inscriptions (12 derniers mois)</div>
        </div>
      </div>
      <div class="p-3">
        <canvas id="chart-nouveaux-clients" height="220"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Statut des transactions</h2></div>
      <div class="p-3 d-flex flex-column align-items-center gap-3">
        <canvas id="chart-statut" height="190"></canvas>
        <div id="statut-legend" style="display:flex;flex-direction:column;gap:8px;width:100%;font-size:.8rem;"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="admin-card">
      <div class="admin-card-header"><h2>Solde vs Épargne</h2></div>
      <div class="p-3 d-flex flex-column align-items-center gap-3">
        <canvas id="chart-solde-epargne" height="190"></canvas>
        <div id="solde-epargne-legend" style="display:flex;flex-direction:column;gap:8px;width:100%;font-size:.8rem;"></div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const palette = ['#123a7a', '#d4af37', '#3aa0ff', '#ce1126', '#1c8a4b', '#8a5cf6', '#f97316'];

  <?php if (!empty($topClients)): ?>
  const topLabels = <?= json_encode(array_map(fn($c) => $c['name'], $topClients)) ?>;
  const topData   = <?= json_encode(array_map(fn($c) => round((float) $c['balance'], 2), $topClients)) ?>;

  const cTop = document.getElementById('chart-top-clients');
  if (cTop) {
    new Chart(cTop, {
      type: 'bar',
      data: {
        labels: topLabels,
        datasets: [{
          label: 'Solde (HTG)',
          data: topData,
          backgroundColor: '#d4af37',
          borderRadius: 6,
          maxBarThickness: 42,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(11,27,58,.05)' }, ticks: { callback: v => v.toLocaleString('fr-HT'), font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } },
          y: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } }
        }
      }
    });
  }
  <?php endif; ?>

  const moisLabels = <?= json_encode(array_column($volumeMensuel, 'label')) ?>;
  const moisData    = <?= json_encode(array_map(fn($m) => round($m['total'], 2), $volumeMensuel)) ?>;

  const cMois = document.getElementById('chart-volume-mensuel');
  if (cMois) {
    new Chart(cMois, {
      type: 'bar',
      data: {
        labels: moisLabels,
        datasets: [{
          label: 'Volume HTG',
          data: moisData,
          backgroundColor: '#123a7a',
          borderRadius: 6,
          maxBarThickness: 32,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } },
          y: { grid: { color: 'rgba(11,27,58,.05)' }, ticks: { callback: v => v.toLocaleString('fr-HT'), font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } }
        }
      }
    });
  }

  <?php if (!empty($paiementsParCategorie)): ?>
  const catLabels = <?= json_encode(array_keys($paiementsParCategorie)) ?>;
  const catValues = <?= json_encode(array_map(fn($v) => round($v, 2), array_values($paiementsParCategorie))) ?>;
  const catColors = catLabels.map((_, i) => palette[i % palette.length]);

  const cCat = document.getElementById('chart-paiements-categorie');
  if (cCat) {
    new Chart(cCat, {
      type: 'doughnut',
      data: {
        labels: catLabels,
        datasets: [{ data: catValues, backgroundColor: catColors, borderWidth: 3, borderColor: '#fff' }]
      },
      options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
  }

  const totalCat = catValues.reduce((a, b) => a + b, 0) || 1;
  const catLegend = document.getElementById('paiements-categorie-legend');
  if (catLegend) {
    catLegend.innerHTML = catLabels.map((label, i) => {
      const pct = Math.round((catValues[i] / totalCat) * 100);
      return `<div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:${catColors[i]};display:inline-block;"></span>${label}
        </span>
        <strong>${pct}%</strong>
      </div>`;
    }).join('');
  }
  <?php endif; ?>

  // Nouveaux clients par mois
  const nvLabels = <?= json_encode(array_column($inscriptionsParMois, 'label')) ?>;
  const nvData    = <?= json_encode(array_column($inscriptionsParMois, 'total')) ?>;

  const cNv = document.getElementById('chart-nouveaux-clients');
  if (cNv) {
    new Chart(cNv, {
      type: 'line',
      data: {
        labels: nvLabels,
        datasets: [{
          label: 'Nouveaux clients',
          data: nvData,
          borderColor: '#d4af37',
          backgroundColor: 'rgba(212,175,55,.10)',
          fill: true, tension: .4,
          pointBackgroundColor: '#d4af37',
          pointBorderColor: '#fff', pointBorderWidth: 2,
          pointRadius: 5, pointHoverRadius: 7,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } },
          y: { grid: { color: 'rgba(11,27,58,.05)' }, ticks: { precision: 0, font: { size: 11, family: 'Inter' }, color: '#5c6b8a' } }
        }
      }
    });
  }

  // Statut des transactions
  const statut       = <?= json_encode($statutTransactions) ?>;
  const statutLabels = Object.keys(statut);
  const statutValues = Object.values(statut);
  const statutColors = ['#1c8a4b', '#ce1126', '#d4af37'];

  const cStatut = document.getElementById('chart-statut');
  if (cStatut) {
    new Chart(cStatut, {
      type: 'doughnut',
      data: {
        labels: statutLabels,
        datasets: [{ data: statutValues, backgroundColor: statutColors, borderWidth: 3, borderColor: '#fff' }]
      },
      options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
  }

  const totalStatut = statutValues.reduce((a, b) => a + b, 0) || 1;
  const statutLegend = document.getElementById('statut-legend');
  if (statutLegend) {
    statutLegend.innerHTML = statutLabels.map((label, i) => {
      const pct = Math.round((statutValues[i] / totalStatut) * 100);
      return `<div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:${statutColors[i]};display:inline-block;"></span>${label}
        </span>
        <strong>${pct}%</strong>
      </div>`;
    }).join('');
  }

  // Solde courant vs Épargne (masse monétaire globale)
  const soldeEpargneLabels = ['Solde courant', 'Épargne'];
  const soldeEpargneValues = [<?= (float) $masseMonetaire ?>, <?= (float) $totalEpargne ?>];
  const soldeEpargneColors = ['#123a7a', '#3aa0ff'];

  const cSoldeEp = document.getElementById('chart-solde-epargne');
  if (cSoldeEp) {
    new Chart(cSoldeEp, {
      type: 'doughnut',
      data: {
        labels: soldeEpargneLabels,
        datasets: [{ data: soldeEpargneValues, backgroundColor: soldeEpargneColors, borderWidth: 3, borderColor: '#fff' }]
      },
      options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
  }

  const totalSoldeEp = soldeEpargneValues.reduce((a, b) => a + b, 0) || 1;
  const soldeEpLegend = document.getElementById('solde-epargne-legend');
  if (soldeEpLegend) {
    soldeEpLegend.innerHTML = soldeEpargneLabels.map((label, i) => {
      const pct = Math.round((soldeEpargneValues[i] / totalSoldeEp) * 100);
      return `<div style="display:flex;justify-content:space-between;align-items:center;">
        <span style="display:flex;align-items:center;gap:6px;">
          <span style="width:10px;height:10px;border-radius:50%;background:${soldeEpargneColors[i]};display:inline-block;"></span>${label}
        </span>
        <strong>${pct}%</strong>
      </div>`;
    }).join('');
  }
})();
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
