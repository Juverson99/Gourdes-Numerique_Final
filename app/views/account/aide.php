<?php
$pageTitle = 'Aide & FAQ — Gourde Numérique';
require VIEWS_PATH . '/layouts/header.php';

$faqs = [
    ['q' => "Qu'est-ce que la Gourde Numérique ?", 'a' => "La Gourde Numérique est la monnaie numérique de banque centrale (MNBC) émise par la Banque de la République d'Haïti (BRH), utilisable pour envoyer, recevoir et payer directement depuis votre compte."],
    ['q' => 'Comment envoyer des fonds à un autre utilisateur ?', 'a' => "Rendez-vous sur la page « Envoyer », saisissez le numéro de téléphone du destinataire et le montant, puis confirmez l'opération."],
    ['q' => "Comment fonctionne l'épargne ?", 'a' => "Le dossier « Épargne » vous permet de déplacer des fonds entre votre solde courant et un solde épargne distinct, en dépôt ou en retrait, à tout moment."],
    ['q' => 'À quoi servent les cartes virtuelles ?', 'a' => "Une carte virtuelle génère un numéro rattaché à votre compte pour vos achats en ligne. Vous pouvez la geler ou la supprimer à tout moment depuis « Cartes virtuelles »."],
    ['q' => 'Comment sécuriser mon compte ?', 'a' => "Depuis « Sécurité », vous pouvez changer votre mot de passe et définir un code PIN à 4-6 chiffres pour vos opérations sensibles."],
    ['q' => "J'ai oublié mon mot de passe, que faire ?", 'a' => "Contactez le support via la page « Contact » avec votre nom et votre numéro NINU ; notre équipe vous aidera à récupérer l'accès à votre compte."],
    ['q' => 'Le service est-il disponible partout en Haïti ?', 'a' => 'Oui, la plateforme est accessible dans tout le pays. Consultez la carte « Agences BNC » pour trouver un point de service physique près de chez vous.'],
];
?>
<div class="container" style="margin-top:1.5rem;">
  <a href="<?= url('/plus') ?>" class="breadcrumb-back"><i class="bi bi-arrow-left"></i> Retour à Plus de services</a>
</div>

<section class="hero page-hero">
  <div class="container">
    <span class="eyebrow"><span class="dot"></span> Aide & FAQ</span>
    <h1>Questions <span class="accent">fréquentes</span></h1>
    <p class="lead-text">Trouvez rapidement une réponse. Besoin d'aide supplémentaire ? <a href="<?= url('/contact') ?>">Contactez le support</a>.</p>
  </div>
</section>

<section class="container" style="max-width:760px;">
  <div class="accordion" id="faqAccordion">
    <?php foreach ($faqs as $i => $faq): ?>
      <div class="accordion-item" style="border-radius:var(--radius-md);overflow:hidden;margin-bottom:.6rem;border:1px solid var(--gris-clair);">
        <h2 class="accordion-header" id="faqHeading<?= $i ?>">
          <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= $i ?>">
            <?= e($faq['q']) ?>
          </button>
        </h2>
        <div id="faqCollapse<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
          <div class="accordion-body" style="color:var(--gris-texte);"><?= e($faq['a']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
