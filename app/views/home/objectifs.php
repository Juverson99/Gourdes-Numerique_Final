<?php
$pageTitle   = 'Objectifs — Gourde Numérique';
$currentUser = $currentUser ?? null;
require VIEWS_PATH . '/layouts/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1><i class="bi bi-bullseye"></i> Nos objectifs</h1>
    <p>Une infrastructure numérique nationale, développée pour et par Haïti, en partenariat avec la Banque de la République d'Haïti.</p>
  </div>
</section>

<section class="container" style="margin-bottom:3rem;">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-shield-check"></i></div>
        <h3>Réduire la dépendance à la monnaie fiduciaire</h3>
        <p>Moins de billets en circulation, plus de traçabilité et de sécurité pour chaque gourde. En numérisant les échanges, la plateforme limite les risques liés au transport et au stockage physique de l'argent.</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-graph-up"></i></div>
        <h3>Moderniser le système de paiement</h3>
        <p>Des paiements instantanés, disponibles 24h/24, partout sur le territoire. La plateforme rapproche les usages financiers haïtiens des standards internationaux de rapidité et de fiabilité.</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-people"></i></div>
        <h3>Favoriser l'inclusion financière</h3>
        <p>Un accès simplifié aux services financiers, même sans compte bancaire traditionnel. L'ouverture d'un compte se fait en quelques minutes, avec un simple numéro NINU.</p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="feature-detail-card">
        <div class="fdc-icon"><i class="bi bi-globe"></i></div>
        <h3>Promouvoir la souveraineté économique</h3>
        <p>Une monnaie numérique nationale, garantie et régulée par la BRH, qui renforce la maîtrise haïtienne sur ses propres infrastructures de paiement.</p>
      </div>
    </div>
  </div>
</section>

<section class="container" style="margin-bottom:3.5rem;">
  <div class="section-title-wrap">
    <span class="eyebrow"><span class="dot"></span> Notre vision</span>
    <h2>Une plateforme au service de tous les Haïtiens</h2>
    <p>Chaque décision de conception vise un même but : rendre la monnaie numérique utile, simple et digne de confiance pour l'ensemble de la population.</p>
  </div>
  <div class="row g-4">
    <div class="col-md-6">
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-building"></i></div>
        <div>
          <h4>Un partenariat institutionnel solide</h4>
          <p>Conçue en lien avec la Banque de la République d'Haïti, garante de la stabilité monétaire du pays.</p>
        </div>
      </div>
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-house-heart-fill"></i></div>
        <div>
          <h4>Pensée pour le contexte haïtien</h4>
          <p>Adaptée aux réalités locales : usage mobile, réseau d'agences physiques et simplicité d'accès.</p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-arrow-repeat"></i></div>
        <div>
          <h4>Une circulation plus fluide de l'argent</h4>
          <p>Des transferts et paiements sans friction entre particuliers, marchands et institutions.</p>
        </div>
      </div>
      <div class="value-prop-row">
        <div class="vp-icon"><i class="bi bi-lightbulb-fill"></i></div>
        <div>
          <h4>Une plateforme évolutive</h4>
          <p>De nouvelles fonctionnalités s'ajoutent progressivement, à l'écoute des besoins des utilisateurs.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="container" style="margin-bottom:3.5rem;">
  <div class="admin-empty" style="background:#fff;border:1px solid var(--ligne-claire);border-radius:var(--radius-lg);padding:2rem;text-align:center;">
    <i class="bi bi-chat-quote" style="font-size:1.6rem;color:var(--or-fonce);"></i>
    <p style="max-width:600px;margin:.8rem auto 1.2rem;color:var(--gris-texte);">Une question sur notre mission ou nos projets à venir ? Notre équipe se fera un plaisir d'y répondre.</p>
    <a href="<?= url('/contact') ?>" class="mini-btn" style="display:inline-flex;">Nous contacter</a>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
