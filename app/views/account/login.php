<?php
$pageTitle = 'Se connecter — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';
?>
<!-- ===================== PAGE LOGIN ===================== -->
<!-- Le vrai formulaire vit dans la modale #loginModal (incluse par le header).
     Cette page sert de point d'entrée réel (/login) : lien partageable,
     fonctionne même si on y arrive directement (ex : redirection depuis une
     page protégée), et ouvre automatiquement la modale au chargement. -->
<section class="hero page-hero" style="min-height:50vh;display:flex;align-items:center;">
  <div class="container text-center">
    <span class="eyebrow"><span class="dot"></span> Connexion</span>
    <h1>Connectez-vous à votre compte <span class="accent">Gourde Numérique</span></h1>
    <p class="lead-text">
      La fenêtre de connexion s'ouvre automatiquement.
      Si rien ne se passe, <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">cliquez ici</a>.
    </p>
  </div>
</section>

<?php
$redirectTo = safe_redirect_path($_GET['redirect'] ?? '/');
$extraScript = 'window.LOGIN_REDIRECT_TO = ' . json_encode($redirectTo) . ';'
    . "\ndocument.addEventListener('DOMContentLoaded', () => {"
    . "\n  const modalEl = document.getElementById('loginModal');"
    . "\n  if (modalEl) new bootstrap.Modal(modalEl).show();"
    . "\n});";
require VIEWS_PATH . '/layouts/footer.php';
