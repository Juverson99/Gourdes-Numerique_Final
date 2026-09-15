<?php
/**
 * app/views/layouts/admin_header.php
 * Attend $pageTitle, $currentUser (déjà vérifié membre du personnel par
 * require_staff()) et éventuellement $activeAdminNav ('dashboard'|'clients'|'recevoir'|'envoyer'
 * |'paiement'|'moncash'|'epargne'|'retraits-especes'|'billet'|'historique'|'statistiques'|'billets'
 * |'institutions'|'fournisseurs'|'agences-bnc'|'messages'|'rapports'|'administrateurs'|'employes'|'journal').
 */
$pageTitle     = $pageTitle ?? 'Administration — Gourde Numérique';
// Titre affiché dans l'en-tête de la page (h1) : on retire le suffixe
// « — Administration », redondant avec le menu latéral déjà intitulé
// « Administration », pour garder un titre court sur une seule ligne.
// Le <title> de l'onglet du navigateur, lui, garde le suffixe complet.
$pageHeading    = preg_replace('/\s*—\s*Administration$/u', '', $pageTitle);
$activeAdminNav = $activeAdminNav ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<script>
  // Applique immédiatement le thème mémorisé (avant le chargement des CSS)
  // pour éviter un flash en mode clair au chargement d'une page en mode sombre.
  (function () {
    try {
      if (localStorage.getItem('admin-theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) {}
  })();
</script>
<link rel="icon" type="image/png" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">
<link rel="apple-touch-icon" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/css/admin-brh-theme.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/css/admin-dark-mode.css') ?>">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-shell">

  <!-- Rideau mobile : ferme le menu latéral au clic en dehors -->
  <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

  <!-- ===================== SIDEBAR ADMIN ===================== -->
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-brand">
      <div class="d-flex align-items-center gap-2">
        <span class="logo-wrap">
          <img src="<?= url('/assets/images/logo-gourde-numerique.png') ?>" alt="Logo Gourde Numérique">
        </span>
        <div class="txt">GOURDE NUMÉRIQUE<small>Administration</small></div>
      </div>
      <button type="button" class="admin-sidebar-close" id="adminSidebarClose" title="Masquer le menu" aria-label="Masquer le menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <ul class="admin-nav">
      <li><a href="<?= url('/admin') ?>" class="<?= $activeAdminNav === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Tableau de bord</a></li>
    </ul>

    <?php if (admin_can('clients')): ?>
    <div class="admin-nav-divider">Clients</div>
    <ul class="admin-nav">
      <li><a href="<?= url('/admin/clients') ?>" class="<?= $activeAdminNav === 'clients' ? 'active' : '' ?>"><i class="bi bi-people-fill"></i> Dossier des clients</a></li>
    </ul>
    <?php endif; ?>

    <?php if (admin_can('recevoir') || admin_can('envoyer') || admin_can('paiement') || admin_can('moncash') || admin_can('epargne') || admin_can('billet') || admin_can('historique') || admin_can('retraits_especes') || admin_can('cartes')): ?>
    <div class="admin-nav-divider">Opérations</div>
    <ul class="admin-nav">
      <?php if (admin_can('recevoir')): ?><li><a href="<?= url('/admin/recevoir') ?>" class="<?= $activeAdminNav === 'recevoir' ? 'active' : '' ?>"><i class="bi bi-inboxes-fill"></i> Recevoir (tous clients)</a></li><?php endif; ?>
      <?php if (admin_can('envoyer')): ?><li><a href="<?= url('/admin/envoyer') ?>" class="<?= $activeAdminNav === 'envoyer' ? 'active' : '' ?>"><i class="bi bi-send-fill"></i> Envoyer (tous clients)</a></li><?php endif; ?>
      <?php if (admin_can('paiement')): ?><li><a href="<?= url('/admin/paiement') ?>" class="<?= $activeAdminNav === 'paiement' ? 'active' : '' ?>"><i class="bi bi-credit-card-fill"></i> Paiement</a></li><?php endif; ?>
      <?php if (admin_can('paiement')): ?><li><a href="<?= url('/admin/categories-paiement') ?>" class="<?= $activeAdminNav === 'categories-paiement' ? 'active' : '' ?>"><i class="bi bi-tags-fill"></i> Catégories de paiement</a></li><?php endif; ?>
      <?php if (admin_can('moncash')): ?><li><a href="<?= url('/admin/moncash') ?>" class="<?= $activeAdminNav === 'moncash' ? 'active' : '' ?>"><i class="bi bi-phone-fill"></i> Transactions MonCash</a></li><?php endif; ?>
      <?php if (admin_can('epargne')): ?><li><a href="<?= url('/admin/epargne') ?>" class="<?= $activeAdminNav === 'epargne' ? 'active' : '' ?>"><i class="bi bi-piggy-bank-fill"></i> Épargne</a></li><?php endif; ?>
      <?php if (admin_can('cartes')): ?><li><a href="<?= url('/admin/cartes-virtuelles') ?>" class="<?= $activeAdminNav === 'cartes-virtuelles' ? 'active' : '' ?>"><i class="bi bi-credit-card-2-front-fill"></i> Cartes virtuelles</a></li><?php endif; ?>
      <?php if (admin_can('retraits_especes')): $especesCount = pending_cash_withdrawals_count(); ?>
      <li>
        <a href="<?= url('/admin/retraits-especes') ?>" class="<?= $activeAdminNav === 'retraits-especes' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between;">
          <span><i class="bi bi-cash-stack"></i> Retraits en espèces</span>
          <?php if ($especesCount > 0): ?><span style="background:var(--rouge-haiti,#ce1126);color:#fff;border-radius:999px;font-size:.68rem;font-weight:700;padding:.05rem .5rem;line-height:1.4;"><?= $especesCount ?></span><?php endif; ?>
        </a>
      </li>
      <?php endif; ?>
      <?php if (admin_can('billet')): ?><li><a href="<?= url('/admin/billet') ?>" class="<?= $activeAdminNav === 'billet' ? 'active' : '' ?>"><i class="bi bi-cash-coin"></i> Dépôt manuel (billet)</a></li><?php endif; ?>
      <?php if (admin_can('historique')): ?><li><a href="<?= url('/admin/historique') ?>" class="<?= $activeAdminNav === 'historique' ? 'active' : '' ?>"><i class="bi bi-clock-history"></i> Historique</a></li><?php endif; ?>
    </ul>
    <?php endif; ?>

    <?php if (admin_can('statistiques')): ?>
    <div class="admin-nav-divider">Analyse</div>
    <ul class="admin-nav">
      <li><a href="<?= url('/admin/statistiques') ?>" class="<?= $activeAdminNav === 'statistiques' ? 'active' : '' ?>"><i class="bi bi-bar-chart-fill"></i> Statistiques</a></li>
    </ul>
    <?php endif; ?>

    <?php if (admin_can('rapports')): ?>
    <div class="admin-nav-divider">Rapports</div>
    <ul class="admin-nav">
      <li><a href="<?= url('/admin/rapports') ?>" class="<?= $activeAdminNav === 'rapports' ? 'active' : '' ?>"><i class="bi bi-file-earmark-bar-graph-fill"></i> Excel &amp; PDF</a></li>
    </ul>
    <?php endif; ?>

    <?php if (admin_can('billets') || admin_can('institutions') || admin_can('fournisseurs') || admin_can('agences') || admin_can('messages')): ?>
    <div class="admin-nav-divider">Plateforme</div>
    <ul class="admin-nav">
      <?php if (admin_can('billets')): ?><li><a href="<?= url('/admin/billets') ?>" class="<?= $activeAdminNav === 'billets' ? 'active' : '' ?>"><i class="bi bi-wallet2"></i> Gestion des billets</a></li><?php endif; ?>
      <?php if (admin_can('billets')): ?><li><a href="<?= url('/admin/categories-billets') ?>" class="<?= $activeAdminNav === 'categories-billets' ? 'active' : '' ?>"><i class="bi bi-tags-fill"></i> Catégories de billets</a></li><?php endif; ?>
      <?php if (admin_can('billets')): ?><li><a href="<?= url('/admin/stock') ?>" class="<?= $activeAdminNav === 'stock' ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Stock des billets</a></li><?php endif; ?>
      <?php if (admin_can('billets')): $alertCount = low_stock_alert_count(50); ?>
      <li>
        <a href="<?= url('/admin/alertes-stock') ?>" class="<?= $activeAdminNav === 'alertes-stock' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between;">
          <span><i class="bi bi-bell-fill"></i> Alertes de stock</span>
          <?php if ($alertCount > 0): ?><span style="background:var(--rouge-haiti,#ce1126);color:#fff;border-radius:999px;font-size:.68rem;font-weight:700;padding:.05rem .5rem;line-height:1.4;"><?= $alertCount ?></span><?php endif; ?>
        </a>
      </li>
      <?php endif; ?>
      <?php if (admin_can('institutions')): ?><li><a href="<?= url('/admin/institutions') ?>" class="<?= $activeAdminNav === 'institutions' ? 'active' : '' ?>"><i class="bi bi-bank"></i> Institutions financières</a></li><?php endif; ?>
      <?php if (admin_can('fournisseurs')): ?><li><a href="<?= url('/admin/fournisseurs') ?>" class="<?= $activeAdminNav === 'fournisseurs' ? 'active' : '' ?>"><i class="bi bi-truck"></i> Fournisseurs</a></li><?php endif; ?>
      <?php if (admin_can('agences')): ?><li><a href="<?= url('/admin/agences-bnc') ?>" class="<?= $activeAdminNav === 'agences-bnc' ? 'active' : '' ?>"><i class="bi bi-geo-alt-fill"></i> Agences BNC</a></li><?php endif; ?>
      <?php if (admin_can('messages')): $newMsgCount = new_contact_messages_count(); ?>
      <li>
        <a href="<?= url('/admin/messages') ?>" class="<?= $activeAdminNav === 'messages' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between;">
          <span><i class="bi bi-envelope-fill"></i> Messages de contact</span>
          <?php if ($newMsgCount > 0): ?><span style="background:var(--rouge-haiti,#ce1126);color:#fff;border-radius:999px;font-size:.68rem;font-weight:700;padding:.05rem .5rem;line-height:1.4;"><?= $newMsgCount ?></span><?php endif; ?>
        </a>
      </li>
      <?php endif; ?>
    </ul>
    <?php endif; ?>

    <?php if (admin_can('administrateurs') || admin_can('employes') || admin_can('journal')): ?>
    <div class="admin-nav-divider">Administration</div>
    <ul class="admin-nav">
      <?php if (admin_can('administrateurs')): ?><li><a href="<?= url('/admin/administrateurs') ?>" class="<?= $activeAdminNav === 'administrateurs' ? 'active' : '' ?>"><i class="bi bi-shield-lock-fill"></i> Dossier des administrateurs</a></li><?php endif; ?>
      <?php if (admin_can('employes')): ?><li><a href="<?= url('/admin/employes') ?>" class="<?= $activeAdminNav === 'employes' ? 'active' : '' ?>"><i class="bi bi-person-badge-fill"></i> Dossier des employés</a></li><?php endif; ?>
      <?php if (admin_can('journal')): ?><li><a href="<?= url('/admin/journal') ?>" class="<?= $activeAdminNav === 'journal' ? 'active' : '' ?>"><i class="bi bi-clock-history"></i> Journal d'activité</a></li><?php endif; ?>
    </ul>
    <?php endif; ?>

    <div class="admin-back-site">
      <ul class="admin-nav">
        <li><a href="<?= url('/') ?>"><i class="bi bi-box-arrow-left"></i> Retour au site</a></li>
        <li><a href="#" id="adminLogoutBtn"><i class="bi bi-power"></i> Se déconnecter</a></li>
      </ul>
    </div>
  </aside>

  <!-- ===================== CONTENU ===================== -->
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-2" style="min-width:0;">
        <button class="btn btn-sm border-0" id="adminSidebarToggle" title="Afficher/masquer le menu" aria-label="Afficher/masquer le menu"><i class="bi bi-list" style="font-size:1.4rem;"></i></button>
        <h1 title="<?= e($pageHeading) ?>"><?= e($pageHeading) ?></h1>
      </div>
      <div class="admin-clock" id="adminClock">
        <i class="bi bi-clock-history"></i>
        <span id="adminClockDate"></span>
        <span id="adminClockTime" class="admin-clock-time"></span>
      </div>
      <div class="admin-user">
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Basculer mode clair / nocturne" aria-label="Basculer mode clair / nocturne">
          <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
        </button>
        <button type="button" class="theme-toggle-btn" id="fullscreenToggleBtn" title="Plein écran" aria-label="Basculer le mode plein écran">
          <i class="bi bi-arrows-fullscreen" id="fullscreenToggleIcon"></i>
        </button>
        <span class="avatar">
          <?php if (!empty($currentUser['photo_url'])): ?>
            <img src="<?= e($currentUser['photo_url']) ?>" alt="">
          <?php else: ?>
            <?= e(initial_letter($currentUser['name'])) ?>
          <?php endif; ?>
        </span>
        <span>
          <?= e($currentUser['name']) ?>
          <?php if (!empty($currentUser['is_employee'])): ?>
            <span class="badge-admin badge-out" style="margin-left:.4rem;font-size:.65rem;vertical-align:middle;"><i class="bi bi-person-badge-fill"></i> Employé</span>
          <?php elseif (!empty($currentUser['is_super_admin'])): ?>
            <span class="badge-admin badge-in" style="margin-left:.4rem;font-size:.65rem;vertical-align:middle;"><i class="bi bi-shield-check"></i> Super admin</span>
          <?php endif; ?>
        </span>
      </div>
    </div>
    <div class="admin-content">
