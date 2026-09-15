<?php
$pageTitle   = 'Contact — Gourde Numérique';
$currentUser = $currentUser ?? null;
require VIEWS_PATH . '/layouts/header.php';
?>

<section class="page-hero">
  <div class="container">
    <h1><i class="bi bi-envelope-fill"></i> Contactez-nous</h1>
    <p>Une question, un problème avec une transaction, ou besoin d'aide pour ouvrir un compte ? Notre équipe vous répond.</p>
  </div>
</section>

<section class="container" style="margin-bottom:3.5rem;">
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="contact-info-card mb-4">
        <div class="contact-info-item">
          <div class="ci-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <div>
            <h5>Adresse</h5>
            <p>Banque de la République d'Haïti<br>Port-au-Prince, Haïti</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="bi bi-telephone-fill"></i></div>
          <div>
            <h5>Téléphone</h5>
            <p><a href="tel:+50922994000">(+509) 2299-4000</a></p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <h5>Courriel</h5>
            <p><a href="mailto:contact@gourdenumerique.ht">contact@gourdenumerique.ht</a></p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="ci-icon"><i class="bi bi-clock-fill"></i></div>
          <div>
            <h5>Heures d'ouverture</h5>
            <p>Lundi – Vendredi : 8h00 – 16h00<br>Samedi : 8h00 – 12h00</p>
          </div>
        </div>
      </div>

      <div class="admin-empty" style="background:#fff;border:1px solid var(--ligne-claire);border-radius:var(--radius-lg);padding:1.4rem;text-align:center;">
        <i class="bi bi-geo-alt-fill" style="color:var(--or-fonce);"></i>
        <p style="margin:.5rem 0 .8rem;color:var(--gris-texte);font-size:.88rem;">Besoin d'un dépôt ou d'un retrait physique ?</p>
        <a href="<?= url('/agences-bnc') ?>" class="mini-btn-outline" style="display:inline-flex;">Trouver une agence BNC</a>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="contact-form-card">
        <h3 style="font-weight:700;margin-bottom:.3rem;">Envoyez-nous un message</h3>
        <p style="color:var(--gris-texte);font-size:.88rem;margin-bottom:1.4rem;">Nous répondons généralement sous 24 à 48 heures ouvrables.</p>

        <form id="contactForm" novalidate>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label-mini" for="contactName">Nom complet</label>
              <input type="text" class="form-control-auth" id="contactName" placeholder="Ex : Jean Baptiste" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="contactEmail">Adresse courriel</label>
              <input type="email" class="form-control-auth" id="contactEmail" placeholder="vous@email.com" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="contactPhone">Téléphone (facultatif)</label>
              <input type="tel" class="form-control-auth" id="contactPhone" placeholder="Ex : 509 46213235" inputmode="numeric" pattern="(509)?\s?[0-9]{8}" maxlength="12" title="Indicatif 509 suivi de 8 chiffres, ex : 509 46213235.">
            </div>
            <div class="col-sm-6">
              <label class="form-label-mini" for="contactSubject">Sujet</label>
              <input type="text" class="form-control-auth" id="contactSubject" placeholder="Ex : Problème de transaction" required>
            </div>
            <div class="col-12">
              <label class="form-label-mini" for="contactMessage">Message</label>
              <textarea class="form-control-auth" id="contactMessage" placeholder="Décrivez votre demande…" required></textarea>
            </div>
          </div>
          <div id="contactError" class="auth-error d-none"></div>
          <button type="submit" class="mini-btn w-100 mt-3"><i class="bi bi-send-fill"></i> Envoyer le message</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php
$extraScript = <<<'JS'
// ---------- Formulaire de contact (page publique /contact) ----------
(function () {
  const form = document.getElementById('contactForm');
  const errorBox = document.getElementById('contactError');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideAuthError(errorBox);

    const payload = {
      name: document.getElementById('contactName').value.trim(),
      email: document.getElementById('contactEmail').value.trim(),
      phone: document.getElementById('contactPhone').value.trim(),
      subject: document.getElementById('contactSubject').value.trim(),
      message: document.getElementById('contactMessage').value.trim(),
    };

    if (!payload.name || !payload.email || !payload.subject || !payload.message) {
      showAuthError(errorBox, 'Veuillez remplir tous les champs obligatoires.');
      return;
    }

    // Le téléphone est facultatif ici, mais s'il est renseigné il doit respecter le format attendu.
    if (payload.phone) {
      const phoneDigits = payload.phone.replace(/\D/g, '').replace(/^509/, '');
      if (phoneDigits.length !== 8) {
        showAuthError(errorBox, 'Le numéro de téléphone doit contenir l\'indicatif 509 suivi de 8 chiffres, ex : 509 46213235.');
        return;
      }
    }

    const data = await apiPost(`${API_BASE}/contact.php?action=send`, payload);
    if (!data.ok) {
      showAuthError(errorBox, data.error || "Impossible d'envoyer le message. Réessayez.");
      return;
    }

    form.reset();
    showToast('Votre message a été envoyé — nous vous répondrons bientôt ✓');
  });
})();
JS;

require VIEWS_PATH . '/layouts/footer.php';
