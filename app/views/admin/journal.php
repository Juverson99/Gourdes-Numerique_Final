<?php
$pageTitle      = "Journal d'activité — Administration";
$activeAdminNav = 'journal';
require VIEWS_PATH . '/layouts/admin_header.php';

// Fonction pour afficher le temps écoulé
if (!function_exists('getTimeAgo')) {
    function getTimeAgo($timestamp) {
        $seconds = time() - $timestamp;
        if ($seconds < 60) return 'À l\'instant';
        $minutes = intval($seconds / 60);
        if ($minutes < 60) return $minutes . 'm';
        $hours = intval($minutes / 60);
        if ($hours < 24) return $hours . 'h';
        $days = intval($hours / 24);
        return $days . 'j';
    }
}

$actionLabels = [
    'creation_admin'       => ['Création d\'administrateur', 'bi-person-plus-fill', 'badge-success'],
    'modification_admin'   => ['Modification d\'administrateur', 'bi-pencil-fill', 'badge-info'],
    'modification_client'  => ['Modification de client', 'bi-person-lines-fill', 'badge-info'],
    'promotion'            => ['Promotion administrateur', 'bi-shield-check', 'badge-success'],
    'retrogradation'       => ['Rétrogradation en client', 'bi-shield-minus', 'badge-warning'],
    'suppression_compte'   => ['Suppression de compte', 'bi-person-x-fill', 'badge-danger'],
    'creation_employe'       => ['Création d\'employé', 'bi-person-plus-fill', 'badge-success'],
    'modification_employe'   => ['Modification d\'employé', 'bi-pencil-fill', 'badge-info'],
    'promotion_employe'      => ['Promotion employé', 'bi-person-badge-fill', 'badge-success'],
    'retrogradation_employe' => ['Rétrogradation en client', 'bi-shield-minus', 'badge-warning'],
    'suppression_employe'    => ['Suppression de compte employé', 'bi-person-x-fill', 'badge-danger'],
    'ajout_billet'         => ['Dépôt manuel (billet)', 'bi-cash-coin', 'badge-success'],
    'creation_billet'      => ['Création de coupure', 'bi-wallet2', 'badge-success'],
    'modification_billet'  => ['Modification de coupure', 'bi-wallet2', 'badge-info'],
    'suppression_billet'   => ['Suppression de coupure', 'bi-wallet2', 'badge-danger'],
    'ajustement_stock'     => ['Ajustement de stock', 'bi-boxes', 'badge-info'],
    'creation_categorie_billet'      => ['Création de catégorie', 'bi-tags-fill', 'badge-success'],
    'modification_categorie_billet'  => ['Modification de catégorie', 'bi-tags-fill', 'badge-info'],
    'suppression_categorie_billet'   => ['Suppression de catégorie', 'bi-tags-fill', 'badge-danger'],
    'creation_institution'      => ['Ajout d\'institution financière', 'bi-bank', 'badge-success'],
    'modification_institution'  => ['Modification d\'institution financière', 'bi-bank', 'badge-info'],
    'suppression_institution'   => ['Suppression d\'institution financière', 'bi-bank', 'badge-danger'],
    'creation_fournisseur'      => ['Ajout de fournisseur', 'bi-truck', 'badge-success'],
    'modification_fournisseur'  => ['Modification de fournisseur', 'bi-truck', 'badge-info'],
    'suppression_fournisseur'   => ['Suppression de fournisseur', 'bi-truck', 'badge-danger'],
    'creation_agence_bnc'       => ['Ajout d\'agence BNC', 'bi-geo-alt-fill', 'badge-success'],
    'modification_agence_bnc'   => ['Modification d\'agence BNC', 'bi-geo-alt-fill', 'badge-info'],
    'suppression_agence_bnc'    => ['Suppression d\'agence BNC', 'bi-geo-alt-fill', 'badge-danger'],
    'suppression_message'       => ['Suppression de message', 'bi-envelope-fill', 'badge-danger'],
    'gel_carte_virtuelle'        => ['Gel de carte virtuelle', 'bi-credit-card-2-front-fill', 'badge-warning'],
    'activation_carte_virtuelle' => ['Réactivation de carte virtuelle', 'bi-credit-card-2-front-fill', 'badge-success'],
    'suppression_carte_virtuelle'=> ['Suppression de carte virtuelle', 'bi-credit-card-2-front-fill', 'badge-danger'],
];

// Calculer les statistiques
$statsActionCounts = array_count_values(array_column($logs, 'action'));
$statsAdminCounts = array_count_values(array_column($logs, 'admin_name'));
$statsTotalActions = count($logs);
$statsByType = [
    'create' => 0,
    'modify' => 0,
    'delete' => 0,
];
foreach ($logs as $log) {
    if (strpos($log['action'], 'creation') !== false || strpos($log['action'], 'promotion') !== false) $statsByType['create']++;
    elseif (strpos($log['action'], 'modification') !== false || strpos($log['action'], 'ajustement') !== false) $statsByType['modify']++;
    elseif (strpos($log['action'], 'suppression') !== false || strpos($log['action'], 'retrogradation') !== false) $statsByType['delete']++;
}

// Admin avec le plus d'actions
$topAdmin = $statsAdminCounts ? key(array_slice($statsAdminCounts, 0, 1, true)) : 'Aucun';
$topAdminCount = $statsAdminCounts ? max($statsAdminCounts) : 0;
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h2><i class="bi bi-clock-history"></i> Journal d'activité <span style="color:var(--gris-texte);font-weight:400;">(<?= count($logs) ?> actions)</span></h2>
      <p style="margin:0.5rem 0 0 0;font-size:.85rem;color:var(--gris-texte);">Historique complet de toutes les actions administratives</p>
    </div>
    <button type="button" class="mini-btn" id="exportJournalBtn" title="Exporter les données">
      <i class="bi bi-download"></i> Exporter
    </button>
  </div>

  <!-- Statistiques rapides -->
  <div class="journal-stats">
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(76, 175, 80, 0.1);color:#4caf50;">
        <i class="bi bi-plus-lg"></i>
      </div>
      <div class="stat-content">
        <div class="stat-value"><?= $statsByType['create'] ?></div>
        <div class="stat-label">Créations</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(33, 150, 243, 0.1);color:#2196f3;">
        <i class="bi bi-pencil-lg"></i>
      </div>
      <div class="stat-content">
        <div class="stat-value"><?= $statsByType['modify'] ?></div>
        <div class="stat-label">Modifications</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(244, 67, 54, 0.1);color:#f44336;">
        <i class="bi bi-trash-lg"></i>
      </div>
      <div class="stat-content">
        <div class="stat-value"><?= $statsByType['delete'] ?></div>
        <div class="stat-label">Suppressions</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:rgba(156, 39, 176, 0.1);color:#9c27b0;">
        <i class="bi bi-person-check-fill"></i>
      </div>
      <div class="stat-content">
        <div class="stat-value"><?= $topAdminCount ?></div>
        <div class="stat-label"><?= e(substr($topAdmin, 0, 20)) ?></div>
      </div>
    </div>
  </div>

  <!-- Filtres et recherche -->
  <div class="journal-filters">
    <div class="filter-group">
      <input type="text" id="journalSearch" class="filter-input" placeholder="🔍 Rechercher par label, détails...">
    </div>
    <div class="filter-group">
      <label style="font-size:.85rem;font-weight:600;color:var(--gris-texte);">ADMINISTRATEUR</label>
      <select id="journalAdminFilter" class="filter-select">
        <option value="">— Tous les administrateurs —</option>
        <?php 
        $admins = array_unique(array_column($logs, 'admin_name'));
        sort($admins);
        foreach ($admins as $admin): ?>
        <option value="<?= e($admin) ?>"><?= e($admin) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label style="font-size:.85rem;font-weight:600;color:var(--gris-texte);">TYPE D'ACTION</label>
      <select id="journalActionFilter" class="filter-select">
        <option value="">— Tous les types —</option>
        <option value="create">✚ Créations</option>
        <option value="modify">✎ Modifications</option>
        <option value="delete">✕ Suppressions</option>
      </select>
    </div>
    <button type="button" class="mini-btn-outline" id="resetFiltersBtn">
      <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
    </button>
  </div>

  <!-- Tableau du journal -->
  <?php if (empty($logs)): ?>
    <div class="admin-empty">Aucune action administrative enregistrée pour le moment.</div>
  <?php else: ?>
  <div class="journal-container">
    <div class="journal-list" id="journalList">
    <?php foreach ($logs as $log): 
      $meta = $actionLabels[$log['action']] ?? [$log['action'], 'bi-info-circle', 'badge-secondary'];
      $timestamp = strtotime($log['created_at']);
      $timeAgo = getTimeAgo($timestamp);
      $actionType = 'modify';
      if (strpos($log['action'], 'creation') !== false || strpos($log['action'], 'promotion') !== false) $actionType = 'create';
      elseif (strpos($log['action'], 'suppression') !== false || strpos($log['action'], 'retrogradation') !== false) $actionType = 'delete';
    ?>
      <div class="journal-entry" data-admin="<?= e($log['admin_name']) ?>" data-type="<?= $actionType ?>" data-action="<?= e($log['action']) ?>">
        <div class="journal-entry-icon">
          <span class="badge-icon <?= e($meta[2]) ?>"><i class="bi <?= e($meta[1]) ?>"></i></span>
        </div>
        <div class="journal-entry-content">
          <div class="journal-entry-header">
            <span class="journal-action"><?= e($meta[0]) ?></span>
            <span class="journal-time" title="<?= date('d/m/Y H:i:s', $timestamp) ?>"><?= e($timeAgo) ?></span>
          </div>
          <div class="journal-entry-details">
            <span class="detail-admin"><i class="bi bi-person"></i> <?= e($log['admin_name']) ?></span>
            <?php if ($log['target_label']): ?>
              <span class="detail-target"><i class="bi bi-target"></i> <?= e($log['target_label']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($log['details']): ?>
            <div class="journal-entry-notes">
              <i class="bi bi-sticky"></i> <?= e($log['details']) ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
// Fonction pour calculer le temps écoulé
function getTimeAgo(timestamp) {
  const seconds = Math.floor((Date.now() / 1000) - timestamp);
  if (seconds < 60) return 'À l\'instant';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return minutes + 'm';
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return hours + 'h';
  const days = Math.floor(hours / 24);
  return days + 'j';
}

// Filtrage et recherche du journal
const searchInput = document.getElementById('journalSearch');
const adminFilter = document.getElementById('journalAdminFilter');
const actionFilter = document.getElementById('journalActionFilter');
const resetBtn = document.getElementById('resetFiltersBtn');
const journalEntries = document.querySelectorAll('.journal-entry');

function filterJournal() {
  const searchTerm = searchInput.value.toLowerCase();
  const adminValue = adminFilter.value;
  const actionValue = actionFilter.value;

  journalEntries.forEach(entry => {
    let show = true;
    
    // Filtrer par admin
    if (adminValue && entry.dataset.admin !== adminValue) show = false;
    
    // Filtrer par type d'action
    if (actionValue && entry.dataset.type !== actionValue) show = false;
    
    // Filtrer par recherche
    if (searchTerm) {
      const text = entry.textContent.toLowerCase();
      if (!text.includes(searchTerm)) show = false;
    }
    
    entry.style.display = show ? 'flex' : 'none';
  });
}

searchInput.addEventListener('input', filterJournal);
adminFilter.addEventListener('change', filterJournal);
actionFilter.addEventListener('change', filterJournal);

resetBtn.addEventListener('click', () => {
  searchInput.value = '';
  adminFilter.value = '';
  actionFilter.value = '';
  filterJournal();
});

// Export du journal en CSV
document.getElementById('exportJournalBtn').addEventListener('click', () => {
  const visibleEntries = document.querySelectorAll('.journal-entry:not([style*="display: none"])');
  let csv = 'Date,Administrateur,Action,Concerne,Détails\n';
  
  visibleEntries.forEach(entry => {
    const admin = entry.dataset.admin;
    const action = entry.querySelector('.journal-action').textContent;
    const target = entry.querySelector('.detail-target')?.textContent.replace('🎯 ', '') || '—';
    const details = entry.querySelector('.journal-entry-notes')?.textContent.replace('📝 ', '') || '—';
    const time = entry.querySelector('.journal-time').getAttribute('title');
    
    csv += `"${time}","${admin}","${action}","${target}","${details}"\n`;
  });
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = `journal-activite-${new Date().toISOString().split('T')[0]}.csv`;
  link.click();
  showToast('Journal exporté en CSV ✓');
});
</script>

<?php require VIEWS_PATH . '/layouts/admin_footer.php'; ?>
