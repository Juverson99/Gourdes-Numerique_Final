<?php
$pageTitle   = 'Fonctionnalités — Gourde Numérique';
$currentUser = $currentUser ?? null;
require VIEWS_PATH . '/layouts/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1><i class="bi bi-grid-3x3-gap-fill"></i> Fonctionnalités</h1>
    <p>Tout ce qu'il faut pour gérer votre argent au quotidien — envoyer, recevoir, payer et suivre vos gourdes numériques, en toute simplicité et en toute sécurité.</p>
  </div>
</section>

<section class="container" style="margin-bottom:3rem;">
  <div class="row g-4">
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-send-fill"></i></div>
        <h3>Envoyer</h3>
        <p>Transférez des gourdes numériques instantanément vers un autre compte, partout en Haïti.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Transfert instantané, 24h/24</li>
          <li><i class="bi bi-check-circle-fill"></i> Recherche par NINU, téléphone ou email</li>
          <li><i class="bi bi-check-circle-fill"></i> Composition du montant par coupures</li>
        </ul>
        <a href="<?= url('/envoyer') ?>" class="fdc-link">Envoyer maintenant <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-download"></i></div>
        <h3>Recevoir</h3>
        <p>Partagez votre QR code ou votre numéro de compte pour recevoir de l'argent en toute simplicité.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> QR code personnel généré automatiquement</li>
          <li><i class="bi bi-check-circle-fill"></i> Aucune limite de nombre de réceptions</li>
          <li><i class="bi bi-check-circle-fill"></i> Notification immédiate à la réception</li>
        </ul>
        <a href="<?= url('/recevoir') ?>" class="fdc-link">Recevoir un paiement <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
        <h3>Paiement</h3>
        <p>Payez marchands, factures et services directement depuis votre solde, sans espèces.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Paiement chez les marchands partenaires</li>
          <li><i class="bi bi-check-circle-fill"></i> Règlement de factures courantes</li>
          <li><i class="bi bi-check-circle-fill"></i> Confirmation immédiate de la transaction</li>
        </ul>
        <a href="<?= url('/paiement') ?>" class="fdc-link">Effectuer un paiement <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-qr-code-scan"></i></div>
        <h3>Scanner</h3>
        <p>Scannez le code QR d'un marchand ou d'un proche pour payer ou vous connecter en un instant.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Scan direct depuis l'appareil photo</li>
          <li><i class="bi bi-check-circle-fill"></i> Connexion rapide par QR code</li>
          <li><i class="bi bi-check-circle-fill"></i> Aucune saisie manuelle nécessaire</li>
        </ul>
        <a href="<?= url('/scanner') ?>" class="fdc-link">Ouvrir le scanner <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-clock-history"></i></div>
        <h3>Historique</h3>
        <p>Consultez l'ensemble de vos transactions passées, avec tous les détails de chaque opération.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Liste complète des envois et réceptions</li>
          <li><i class="bi bi-check-circle-fill"></i> Filtrage par type et par date</li>
          <li><i class="bi bi-check-circle-fill"></i> Reçu détaillé pour chaque transaction</li>
        </ul>
        <a href="<?= url('/historique') ?>" class="fdc-link">Voir l'historique <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-piggy-bank-fill"></i></div>
        <h3>Épargne &amp; plus</h3>
        <p>Épargnez une partie de votre solde, gérez votre profil et accédez au support depuis un seul espace.</p>
        <ul>
          <li><i class="bi bi-check-circle-fill"></i> Compte épargne intégré</li>
          <li><i class="bi bi-check-circle-fill"></i> Gestion du profil et des préférences</li>
          <li><i class="bi bi-check-circle-fill"></i> Accès rapide au support</li>
        </ul>
        <a href="<?= url('/plus') ?>" class="fdc-link">Voir mon tableau de bord <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
  </div>
</section>

<section class="container" style="margin-bottom:3.5rem;">
  <div class="section-title-wrap">
    <span class="eyebrow"><span class="dot"></span> Et bien plus</span>
    <h2>Une plateforme pensée pour la confiance</h2>
    <p>Au-delà des transactions, chaque fonctionnalité est conçue pour rassurer et simplifier la vie financière des utilisateurs.</p>
  </div>
  <div class="row g-4">
    <div class="col-md-6">
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <div>
          <h4>Sécurité renforcée</h4>
          <p>Toutes les transactions sont chiffrées et supervisées par la Banque de la République d'Haïti.</p>
        </div>
      </div>
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-geo-alt-fill"></i></div>
        <div>
          <h4>Réseau d'agences BNC</h4>
          <p>Localisez l'agence la plus proche pour un dépôt ou un retrait physique, avec calcul d'itinéraire.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-phone-fill"></i></div>
        <div>
          <h4>Disponible partout</h4>
          <p>Accessible depuis un ordinateur ou un téléphone, sans installation, 24 heures sur 24.</p>
        </div>
      </div>
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-headset"></i></div>
        <div>
          <h4>Support accessible</h4>
          <p>Une équipe dédiée reste joignable pour répondre à toute question via la page Contact.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
