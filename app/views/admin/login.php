<?php
/**
 * app/views/admin/login.php
 * Page de connexion DÉDIÉE à la partie administration (/admin/login).
 * Design distinct du site client (fond bleu nuit, même identité que le
 * dashboard admin) pour qu'il n'y ait aucune confusion sur le fait qu'on
 * s'apprête à entrer dans l'espace administrateur.
 * Attend (optionnel) : $error, $redirectTo
 */
$pageTitle  = 'Connexion administrateur — Gourde Numérique';
$redirectTo = $redirectTo ?? '/admin';
$error      = $error ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="icon" type="image/png" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">
<link rel="apple-touch-icon" href="<?= url('/assets/images/logo-gourde-numerique.png') ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= url('/assets/css/admin.css') ?>">
<style>
  body{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(180deg, var(--bleu-nuit) 0%, var(--bleu-nuit-2) 100%);
    font-family:var(--font-body);
  }
  .admin-login-card{
    width:100%;
    max-width:400px;
    background:#fff;
    border-radius:var(--radius-lg);
    padding:2.2rem 2rem;
    box-shadow:0 20px 50px rgba(0,0,0,.35);
    margin:1.5rem;
  }
  .admin-login-brand{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:.6rem;
    margin-bottom:1.6rem;
    text-align:center;
  }
  .admin-login-brand img{height:52px; width:52px; object-fit:contain;}
  .admin-login-brand h1{
    font-family:var(--font-display);
    font-size:1.15rem;
    font-weight:700;
    color:var(--bleu-nuit);
    margin:0;
  }
  .admin-login-brand small{
    font-size:.72rem;
    font-weight:600;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:var(--or-fonce);
  }
  .admin-login-card label{font-weight:600; font-size:.85rem; color:var(--bleu-nuit);}
  .admin-login-card .btn-admin-login{
    background:var(--bleu-nuit);
    color:#fff;
    font-weight:700;
    border:none;
    width:100%;
    padding:.65rem;
    border-radius:var(--radius-md);
    margin-top:.4rem;
  }
  .admin-login-card .btn-admin-login:hover{background:var(--bleu-nuit-2); color:#fff;}
  .admin-login-back{
    display:block;
    text-align:center;
    margin-top:1.2rem;
    font-size:.8rem;
    color:var(--gris-texte);
  }

  /* ---------- Photo en arrière-plan (bâtiment de la BRH) ----------
     Purement décoratif : ancre visuellement l'écran de connexion dans
     l'identité institutionnelle de la plateforme. */
  .admin-login-bg{
    position:fixed;
    inset:0;
    z-index:0;
    background-image:url('<?= url('/assets/images/brh-batiment.jpg') ?>');
    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    filter:saturate(1.05);
    transform:scale(1.03);
    pointer-events:none;
    user-select:none;
  }
  .admin-login-overlay{
    position:fixed;
    inset:0;
    z-index:1;
    background:linear-gradient(180deg, rgba(0,20,45,.72) 0%, rgba(0,20,45,.85) 100%);
    pointer-events:none;
  }
  .admin-login-card{position:relative; z-index:2;}
</style>
</head>
<body>

<!-- ===== Arrière-plan : photo du bâtiment de la BRH ===== -->
<div class="admin-login-bg" aria-hidden="true"></div>
<div class="admin-login-overlay" aria-hidden="true"></div>

<div class="admin-login-card">
  <div class="admin-login-brand">
    <img src="<?= url('/assets/images/logo-gourde-numerique.png') ?>" alt="Logo Gourde Numérique">
    <h1>Gourde Numérique</h1>
    <small>Espace administration</small>
  </div>

  <div id="adminLoginError" class="alert alert-danger py-2 px-3 small <?= $error ? '' : 'd-none' ?>">
    <?= e($error ?? '') ?>
  </div>

  <form id="adminLoginForm" novalidate>
    <div class="mb-3">
      <label for="adminEmail" class="form-label">Adresse email</label>
      <input type="email" class="form-control" id="adminEmail" required autofocus>
    </div>
    <div class="mb-3">
      <label for="adminPassword" class="form-label">Mot de passe</label>
      <input type="password" class="form-control" id="adminPassword" required>
    </div>
    <button type="submit" class="btn btn-admin-login">Se connecter</button>
  </form>

  <a href="<?= url('/') ?>" class="admin-login-back"><i class="bi bi-arrow-left"></i> Retour au site</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.APP_BASE_URL = <?= json_encode(rtrim(url(''), '/')) ?>;
  const ADMIN_REDIRECT_TO = <?= json_encode($redirectTo) ?>;

  const form = document.getElementById('adminLoginForm');
  const errorBox = document.getElementById('adminLoginError');

  function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.remove('d-none');
  }
  function hideError() {
    errorBox.classList.add('d-none');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideError();

    const email = document.getElementById('adminEmail').value.trim();
    const password = document.getElementById('adminPassword').value;

    if (!email || !password) {
      showError('Veuillez remplir tous les champs.');
      return;
    }

    try {
      const res = await fetch(window.APP_BASE_URL + '/api/account.php?action=login&scope=admin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password }),
      });
      const data = await res.json();

      if (!data.ok) {
        showError(data.error || 'Identifiants incorrects.');
        return;
      }
      if (!data.user || (!data.user.is_admin && !data.user.is_employee)) {
        // Connecté, mais ni administrateur ni employé : on annule la session
        // ouverte par ce formulaire et on refuse l'accès à l'espace admin.
        await fetch(window.APP_BASE_URL + '/api/account.php?action=logout&scope=admin', { method: 'POST' });
        showError("Ce compte n'a pas les droits d'accès à l'administration.");
        return;
      }

      window.location.href = window.APP_BASE_URL + ADMIN_REDIRECT_TO;
    } catch (err) {
      showError('Erreur de connexion au serveur. Réessayez.');
    }
  });
</script>
</body>
</html>
