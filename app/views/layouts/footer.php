<!-- ===================== FOOTER ===================== -->
<footer id="pied">
  <div class="container">
    <div class="row gy-4">
      <div class="col-lg-3 col-md-6">
        <div class="footer-brand mb-2">
          <span class="logo-wrap logo-wrap-footer">
            <img src="<?= url('/assets/images/logo-gourde-numerique.png') ?>" alt="Logo">
          </span>
          GOURDE NUMÉRIQUE
        </div>
        <p>BANQUE DE LA RÉPUBLIQUE D’HAÏTI (BRH) 
           Institution centrale œuvrant à la stabilité
           monétaire, à la modernisation du système 
           financier et l’accompagnement de la transformation
           numérique de l’économie haïtienne.</p>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6 class="footer-heading mb-2">Liens rapides</h6>
        <div class="d-flex flex-column gap-1">
          <a href="<?= url('/') ?>">Accueil</a>
          <a href="<?= url('/fonctionnalites') ?>">Fonctionnalités</a>
          <a href="<?= url('/objectifs') ?>">Objectifs</a>
          <a href="<?= url('/contact') ?>">Contact</a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6 class="footer-heading mb-2">Contact</h6>
        <div class="footer-contact-item">
          <span class="fc-icon"><i class="bi bi-geo-alt-fill"></i></span>
          <span>Banque de la République d'Haïti<br>Port-au-Prince, Haïti</span>
        </div>
        <div class="footer-contact-item">
          <span class="fc-icon"><i class="bi bi-telephone-fill"></i></span>
          <span><a href="tel:+50922994000">(+509) 2299-4000</a></span>
        </div>
        <div class="footer-contact-item">
          <span class="fc-icon"><i class="bi bi-envelope-fill"></i></span>
          <span><a href="mailto:contact@gourdenumerique.ht">contact@gourdenumerique.ht</a></span>
        </div>
      </div>
      <div class="col-lg-3 col-md-6">
        <h6 class="footer-heading mb-2">Sécurité</h6>
        <p>Toutes les transactions sont chiffrées et supervisées par la Banque de la République d'Haïti.</p>
      </div>
    </div>
    <hr style="border-color:rgba(212,175,55,.15); margin:2rem 0 1.2rem;">
    <div class="text-center" style="font-size:.78rem;">© 2026 République d'Haïti — Plateforme Nationale de Gourde Numérique. Tous droits réservés.</div>
  </div>
</footer>
<div class="toast-confirm" id="toastConfirm"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($extraHeadScript)): ?>
<?= $extraHeadScript ?>
<?php endif; ?>
<script>
  window.APP_BASE_URL = <?= json_encode(rtrim(url(''), '/')) ?>;
  window.APP_LOGGED_IN = <?= $currentUser ? 'true' : 'false' ?>;
</script>
<script src="<?= url('/assets/js/app.js') ?>"></script>
<?php if (!empty($extraScript)): ?>
<script><?= $extraScript ?></script>
<?php endif; ?>
</body>
</html>
