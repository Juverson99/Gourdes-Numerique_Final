<?php
/**
 * app/views/layouts/header.php
 * Attend éventuellement une variable $pageTitle et $currentUser (tableau public
 * de l'utilisateur connecté, ou null) déjà définies par le contrôleur appelant.
 */
$pageTitle   = $pageTitle ?? 'Gourde Numérique — Plateforme Nationale | République d\'Haïti';
$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="Gourde Numérique — la monnaie numérique souveraine de la République d'Haïti. Envoyez, recevez et payez en toute sécurité.">
<script>
  // Applique immédiatement le thème mémorisé (avant le chargement des CSS)
  // pour éviter un flash en mode clair au chargement d'une page en mode sombre.
  (function () {
    try {
      if (localStorage.getItem('site-theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) {}
  })();
</script>
<link rel="icon" type="image/png" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">
<link rel="apple-touch-icon" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Google Fonts : Sora (display) / Inter (texte) / JetBrains Mono (chiffres) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<!-- Styles de la plateforme (extraits du prototype, partagés par toutes les pages) -->
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
<!-- Mode nocturne du site client -->
<link rel="stylesheet" href="<?= url('/assets/css/site-dark-mode.css') ?>">
</head>
<body>

<div class="circuit-bg"></div>
<div class="glow-orb" style="width:520px;height:520px; background:var(--cyan-circuit); top:-160px; right:-120px;"></div>
<div class="glow-orb" style="width:420px;height:420px; background:var(--or); bottom:10%; left:-140px;"></div>

<!-- ===================== HEADER ===================== -->
<header class="site-header">
  <nav class="navbar navbar-expand-lg py-2">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
        <span class="logo-wrap logo-wrap-nav">
          <img src="<?= url('/assets/images/logo-gourde-numerique.png') ?>" alt="Logo Gourde Numérique" class="brand-logo">
        </span>
        <span class="brand-text">GOURDE NUMÉRIQUE<small>République d'Haïti · BRH</small></span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <i class="bi bi-list" style="font-size:1.8rem;color:#0b1b3a;"></i>
      </button>
      <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
          <li class="nav-item"><a class="nav-link nav-link-custom" href="<?= url('/') ?>">Accueil</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="<?= url('/fonctionnalites') ?>">Fonctionnalités</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="<?= url('/objectifs') ?>">Objectifs</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="<?= url('/contact') ?>">Contact</a></li>
          <li class="nav-item"><a class="nav-link nav-link-custom" href="<?= url('/agences-bnc') ?>"><i class="bi bi-geo-alt-fill"></i> Agences BNC</a></li>
          <li class="nav-item nav-item-toggles ms-lg-2 mt-2 mt-lg-0 d-flex align-items-center gap-2">
            <button type="button" class="btn-fullscreen-toggle" id="themeToggleBtn" title="Basculer mode clair / nocturne" aria-label="Basculer mode clair / nocturne">
              <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
            </button>
            <button type="button" class="btn-fullscreen-toggle" id="fullscreenToggleBtn" title="Plein écran" aria-label="Basculer le mode plein écran">
              <i class="bi bi-arrows-fullscreen" id="fullscreenToggleIcon"></i>
            </button>
          </li>
          <!-- ===================== Icône d'alerte : demandes d'argent reçues ===================== -->
          <li class="nav-item nav-item-toggles mt-2 mt-lg-0 auth-slot<?= $currentUser ? '' : ' d-none' ?>" id="requestsAlertSlot">
            <div class="dropdown">
              <button type="button" class="btn-fullscreen-toggle position-relative" id="requestsAlertBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Demandes d'argent reçues" aria-label="Demandes d'argent reçues">
                <i class="bi bi-bell-fill" id="requestsAlertIcon"></i>
                <span class="request-badge d-none" id="requestsBadge">0</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end requests-dropdown-menu" id="requestsDropdownMenu" aria-labelledby="requestsAlertBtn">
                <li class="requests-empty" id="requestsEmptyMsg">Aucune demande en attente</li>
              </ul>
            </div>
          </li>
          <li class="nav-item ms-lg-3 mt-3 mt-lg-0 auth-slot" id="authLoggedOut"<?= $currentUser ? ' style="display:none"' : '' ?>>
            <a href="<?= url('/login') ?>" class="btn-outline-line" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
          </li>
          <li class="nav-item mt-2 mt-lg-0 auth-slot" id="authLoggedOutSignup"<?= $currentUser ? ' style="display:none"' : '' ?>>
            <a href="#" class="btn-gold" data-bs-toggle="modal" data-bs-target="#signupModal"><i class="bi bi-person-plus-fill"></i> Ouvrir un compte</a>
          </li>
          <li class="nav-item ms-lg-3 mt-2 mt-lg-0 auth-slot<?= $currentUser ? '' : ' d-none' ?>" id="authLoggedIn">
            <div class="dropdown">
              <button class="btn-account dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="account-avatar" id="accountAvatar">
                  <?php if ($currentUser && !empty($currentUser['photo_url'])): ?>
                    <img src="<?= e($currentUser['photo_url']) ?>" alt="">
                  <?php else: ?>
                    <?= $currentUser ? e(initial_letter($currentUser['name'])) : 'G' ?>
                  <?php endif; ?>
                </span>
                <span id="accountName"><?= $currentUser ? e(explode(' ', $currentUser['name'])[0]) : 'Mon compte' ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= url('/plus') ?>">Mon tableau de bord</a></li>
                <li><a class="dropdown-item" href="<?= url('/historique') ?>">Historique</a></li>
                <?php if (!empty($currentUser['is_admin']) || !empty($currentUser['is_employee'])): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= url('/admin') ?>"><i class="bi bi-speedometer2"></i> Administration</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="#" id="logoutBtn">Se déconnecter</a></li>
              </ul>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
<!-- ===================== MODAL CONNEXION ===================== -->
<div class="modal fade auth-modal" id="loginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Se connecter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <form id="loginForm" novalidate>
          <div class="mb-3">
            <label class="form-label-mini" for="loginIdentifier">Email</label>
            <input type="email" class="form-control-auth" id="loginIdentifier" placeholder="Ex : vous@email.com" required>
          </div>
          <div class="mb-2">
            <label class="form-label-mini" for="loginPassword">Mot de passe</label>
            <input type="password" class="form-control-auth" id="loginPassword" placeholder="••••••••" required minlength="4">
          </div>
          <div id="loginError" class="auth-error d-none"></div>
          <button type="submit" class="mini-btn w-100 mt-3">Se connecter</button>
          <p class="auth-switch">Pas encore de compte ? <a href="#" data-bs-toggle="modal" data-bs-target="#signupModal" data-bs-dismiss="modal">Ouvrir un compte</a></p>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ===================== MODAL CRÉATION DE COMPTE ===================== -->
<div class="modal fade auth-modal" id="signupModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ouvrir un compte</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        <form id="signupForm" novalidate enctype="multipart/form-data">
          <div class="d-flex align-items-center gap-3 mb-3">
            <span class="signup-photo-preview" id="signupPhotoPreview"><i class="bi bi-person-fill"></i></span>
            <div class="flex-fill">
              <label class="form-label-mini" for="signupPhoto">Photo de profil (facultatif)</label>
              <input type="file" class="form-control-auth" id="signupPhoto" name="photo" accept="image/png,image/jpeg,image/gif,image/webp">
              <p style="color:var(--gris-texte);font-size:.72rem;margin:.3rem 0 0;">JPG, PNG, GIF ou WEBP — 4 Mo maximum.</p>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-mini" for="signupName">Nom complet</label>
              <input type="text" class="form-control-auth" id="signupName" placeholder="Ex : Jean Baptiste" required>
            </div>
            <div class="col-12">
              <label class="form-label-mini" for="signupNinu">Numéro NINU</label>
              <input type="text" class="form-control-auth" id="signupNinu" placeholder="10 chiffres, ex : 1200384609" required inputmode="numeric" pattern="[0-9]{10}" maxlength="10" title="Le numéro NINU doit contenir exactement 10 chiffres.">
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="signupSexe">Sexe</label>
              <select class="form-control-auth" id="signupSexe" required>
                <option value="" disabled selected>Choisir</option>
                <option value="Femme">Femme</option>
                <option value="Homme">Homme</option>
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="signupAge">Âge</label>
              <input type="number" class="form-control-auth" id="signupAge" placeholder="Ex : 28" min="18" max="120" required>
            </div>
            <div class="col-sm-4">
              <label class="form-label-mini" for="signupVille">Ville</label>
              <input type="text" class="form-control-auth" id="signupVille" placeholder="Ex : Port-au-Prince" required>
            </div>
            <div class="col-12">
              <label class="form-label-mini" for="signupPays">Pays</label>
              <select class="form-control-auth" id="signupPays" required>
                <option value="Haïti" selected>Haïti</option>
                <option value="République Dominicaine">République Dominicaine</option>
                <option value="États-Unis">États-Unis</option>
                <option value="Canada">Canada</option>
                <option value="France">France</option>
                <option value="Autre">Autre</option>
              </select>
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="signupPhone">Téléphone</label>
              <input type="tel" class="form-control-auth" id="signupPhone" placeholder="Ex : 509 46213235" required inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="signupEmail">Email</label>
              <input type="email" class="form-control-auth" id="signupEmail" placeholder="vous@email.com" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="signupPassword">Mot de passe</label>
              <input type="password" class="form-control-auth" id="signupPassword" placeholder="6 caractères minimum" required minlength="6">
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="signupPasswordConfirm">Confirmer le mot de passe</label>
              <input type="password" class="form-control-auth" id="signupPasswordConfirm" placeholder="••••••••" required minlength="6">
            </div>
          </div>
          <div id="signupError" class="auth-error d-none"></div>
          <button type="submit" class="mini-btn w-100 mt-3">Créer mon compte</button>
          <p class="auth-switch">Déjà inscrit ? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Se connecter</a></p>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
// Aperçu immédiat de la photo de profil choisie à l'inscription
(function () {
  const input = document.getElementById('signupPhoto');
  const preview = document.getElementById('signupPhotoPreview');
  if (!input || !preview) return;
  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    if (!file) { preview.innerHTML = '<i class="bi bi-person-fill"></i>'; return; }
    const url = URL.createObjectURL(file);
    preview.innerHTML = `<img src="${url}" alt="">`;
  });
})();
</script>
