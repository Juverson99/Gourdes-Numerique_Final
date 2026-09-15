    </div><!-- /.admin-content -->
  </div><!-- /.admin-main -->
</div><!-- /.admin-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.APP_BASE_URL = <?= json_encode(rtrim(url(''), '/')) ?>;

  (function () {
    const sidebar   = document.getElementById('adminSidebar');
    const backdrop  = document.getElementById('adminSidebarBackdrop');
    const toggleBtn = document.getElementById('adminSidebarToggle');
    const closeBtn  = document.getElementById('adminSidebarClose');
    const BREAKPOINT = 900;

    function isMobile() {
      return window.innerWidth <= BREAKPOINT;
    }

    // Sur mobile, le menu est une superposition (masquée par défaut, "open" l'affiche).
    // Sur desktop, le menu fait partie de la mise en page (affiché par défaut, "collapsed" le masque).
    function isHidden() {
      return isMobile() ? !sidebar.classList.contains('open') : sidebar.classList.contains('collapsed');
    }

    function showSidebar() {
      if (isMobile()) {
        sidebar?.classList.add('open');
        backdrop?.classList.add('open');
      } else {
        sidebar?.classList.remove('collapsed');
      }
    }

    function hideSidebar() {
      if (isMobile()) {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('open');
      } else {
        sidebar?.classList.add('collapsed');
      }
    }

    function toggleSidebar() {
      if (isHidden()) showSidebar(); else hideSidebar();
    }

    toggleBtn?.addEventListener('click', toggleSidebar);
    closeBtn?.addEventListener('click', hideSidebar);
    backdrop?.addEventListener('click', hideSidebar);

    // En passant en largeur mobile/desktop, on efface l'état de superposition mobile
    // ("open") pour ne pas laisser le rideau ouvert par erreur après redimensionnement.
    window.addEventListener('resize', function () {
      if (!isMobile()) {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('open');
      }
    });

    // Ferme le menu après avoir choisi un lien (mobile uniquement)
    sidebar?.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (isMobile()) hideSidebar();
      });
    });
  })();

  document.getElementById('adminLogoutBtn')?.addEventListener('click', async function (e) {
    e.preventDefault();
    try {
      await fetch(window.APP_BASE_URL + '/api/account.php?action=logout', { method: 'POST' });
    } finally {
      window.location.href = window.APP_BASE_URL + '/';
    }
  });

  // ---------- Bascule mode clair / nocturne ----------
  (function () {
    const toggleBtn = document.getElementById('themeToggleBtn');
    const icon       = document.getElementById('themeToggleIcon');

    function applyIcon(isDark) {
      if (!icon) return;
      icon.classList.toggle('bi-moon-stars-fill', !isDark);
      icon.classList.toggle('bi-brightness-high-fill', isDark);
    }

    // L'attribut a déjà pu être posé par le script anti-flash dans <head>.
    applyIcon(document.documentElement.getAttribute('data-theme') === 'dark');

    toggleBtn?.addEventListener('click', function () {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        try { localStorage.setItem('admin-theme', 'light'); } catch (e) {}
        applyIcon(false);
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('admin-theme', 'dark'); } catch (e) {}
        applyIcon(true);
      }
    });
  })();

  // ---------- Bouton plein écran / réduire ----------
  (function () {
    const btn  = document.getElementById('fullscreenToggleBtn');
    const icon = document.getElementById('fullscreenToggleIcon');
    if (!btn || !icon) return;

    function applyIcon() {
      const isFullscreen = !!document.fullscreenElement;
      icon.classList.toggle('bi-arrows-fullscreen', !isFullscreen);
      icon.classList.toggle('bi-fullscreen-exit', isFullscreen);
      btn.title = isFullscreen ? 'Quitter le plein écran' : 'Plein écran';
      btn.setAttribute('aria-label', btn.title);
    }

    btn.addEventListener('click', async function () {
      try {
        if (!document.fullscreenElement) {
          await document.documentElement.requestFullscreen();
        } else {
          await document.exitFullscreen();
        }
      } catch (e) {
        // Plein écran indisponible sur cet appareil/navigateur : on ignore silencieusement.
      }
    });

    document.addEventListener('fullscreenchange', applyIcon);
    applyIcon();
  })();

  // ---------- Horloge en direct (date + heure:minute:seconde) ----------
  (function () {
    const dateEl = document.getElementById('adminClockDate');
    const timeEl = document.getElementById('adminClockTime');
    if (!dateEl || !timeEl) return;

    const dateFmt = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });

    function pad(n) { return String(n).padStart(2, '0'); }

    function tick() {
      const now = new Date();
      dateEl.textContent = dateFmt.format(now);
      timeEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }

    tick();
    setInterval(tick, 1000);
  })();
</script>
</body>
</html>
