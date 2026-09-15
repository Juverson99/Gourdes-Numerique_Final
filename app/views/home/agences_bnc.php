<?php
$pageTitle   = 'Agences BNC — Gourde Numérique';
$currentUser = $currentUser ?? null;
require VIEWS_PATH . '/layouts/header.php';
?>

<!-- Leaflet (carte OpenStreetMap) + Leaflet Routing Machine (itinéraires) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
<link rel="stylesheet" href="<?= url('/assets/css/agences-bnc.css') ?>">

<section class="agences-hero">
  <div class="container">
    <h1><i class="bi bi-geo-alt-fill"></i> Agences BNC à travers le pays</h1>
    <p>Localisez l'agence de la Banque Nationale de Crédit la plus proche de chez vous pour un dépôt ou un retrait physique, et calculez votre itinéraire.</p>
  </div>
</section>

<section class="container agences-section">
  <?php if (empty($agences)): ?>
    <div class="agences-empty"><i class="bi bi-info-circle"></i> Aucune agence n'est disponible pour le moment.</div>
  <?php else: ?>
  <div class="row g-4 align-items-start">
    <div class="col-lg-7">
      <div class="map-container">
        <div class="directions-search">
          <input type="text" id="startAddress" placeholder="Votre adresse de départ (ex : Delmas 33)">
          <button class="btn-go" onclick="AgencesBncMap.calculateRoute()" title="Calculer l'itinéraire"><i class="bi bi-signpost-2"></i></button>
          <button class="icon-btn-round" onclick="AgencesBncMap.locateUser()" title="Utiliser ma position"><i class="bi bi-crosshair"></i></button>
          <button class="icon-btn-round clear-btn" onclick="AgencesBncMap.clearRoute()" title="Effacer l'itinéraire"><i class="bi bi-trash"></i></button>
        </div>
        <div id="addressSuggestions" class="suggestions-box" style="display:none;"></div>
        <div id="map"></div>
      </div>
      <div id="directionsPanel" class="directions-panel" style="display:none;">
        <div class="directions-header">
          <span class="directions-title"><i class="bi bi-signpost-2 me-1"></i>Itinéraire</span>
          <span class="directions-close" onclick="AgencesBncMap.closeDirections()">✕</span>
        </div>
        <div id="directionsContent"></div>
      </div>
      <p class="instruction-text"><i class="bi bi-info-circle"></i> 1. Sélectionnez une agence · 2. Entrez votre adresse · 3. Obtenez l'itinéraire</p>
    </div>
    <div class="col-lg-5">
      <h3 class="agence-list-title"><i class="bi bi-bank me-1"></i> Nos agences (<?= count($agences) ?>)</h3>
      <p class="agence-list-subtitle">Cliquez sur une agence pour la localiser ou calculer l'itinéraire.</p>
      <div id="agence-list"></div>
    </div>
  </div>
  <?php endif; ?>
</section>

<div class="toast-stack" id="toast-stack"></div>

<?php if (!empty($agences)): ?>
<script>
// ==========================================================
// Carte interactive des agences BNC — Gourde Numérique
// (Leaflet + OpenStreetMap + OSRM — itinéraire & géolocalisation)
// Adaptée du composant de carte du projet MonRegime.
// ==========================================================
const AGENCES_BNC = <?= json_encode(array_map(function ($a) {
    return [
        'id'         => (int) $a['id'],
        'nom'        => $a['nom'],
        'ville'      => $a['ville'],
        'departement'=> $a['departement'],
        'adresse'    => $a['adresse'] ?: ($a['ville'] . ', ' . $a['departement']),
        'telephone'  => $a['telephone'],
        'principale' => (bool) $a['principale'],
        'lat'        => (float) $a['latitude'],
        'lng'        => (float) $a['longitude'],
    ];
}, $agences), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const AgencesBncMap = {
    map: null,
    markers: [],
    activeId: null,
    selected: null,
    routingControl: null,
    userPosition: null,
    userMarker: null,
    startMarker: null,
    debounceTimer: null,

    HAITI_CENTER: [18.9712, -72.2852],
    HAITI_BOUNDS: { north: 20.3, south: 17.5, west: -75.0, east: -71.0 },

    init: function () {
        this.initMap();
        this.addMarkers();
        this.renderList();
        this.setupSearch();
    },

    initMap: function () {
        this.map = L.map('map').setView(this.HAITI_CENTER, 8);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            subdomains: 'abc', maxZoom: 19, minZoom: 7
        }).addTo(this.map);
        this.map.setMaxBounds([
            [this.HAITI_BOUNDS.south, this.HAITI_BOUNDS.west],
            [this.HAITI_BOUNDS.north, this.HAITI_BOUNDS.east]
        ]);
    },

    addMarkers: function () {
        AGENCES_BNC.forEach(a => {
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div style="background-color:${a.principale ? '#d4af37' : '#003d7a'};width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 2px 5px rgba(0,0,0,.3);color:#fff;font-size:15px;"><i class="bi bi-bank2"></i></div>`,
                iconSize: [32, 32],
                popupAnchor: [0, -16]
            });

            const marker = L.marker([a.lat, a.lng], { icon })
                .addTo(this.map)
                .bindPopup(`
                    <div style="min-width:210px;">
                        <h6 style="margin-bottom:5px;color:#003d7a;font-weight:bold;">
                            ${this.escapeHtml(a.nom)}
                            ${a.principale ? '<span style="background:#d4af37;color:#0b1b3a;padding:2px 6px;border-radius:10px;font-size:10px;margin-left:5px;">SIÈGE</span>' : ''}
                        </h6>
                        <p style="margin-bottom:2px;font-size:12px;"><i class="bi bi-geo-alt"></i> ${this.escapeHtml(a.adresse)}</p>
                        ${a.telephone ? `<p style="margin-bottom:6px;font-size:12px;"><i class="bi bi-telephone"></i> ${this.escapeHtml(a.telephone)}</p>` : ''}
                        <button onclick="AgencesBncMap.selectForRoute(${a.id})" style="background:#003d7a;color:#fff;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;width:100%;">
                            Itinéraire
                        </button>
                    </div>
                `);
            marker.on('click', () => this.select(a.id));
            this.markers.push(marker);
        });

        if (this.markers.length > 0) {
            const group = L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.25));
        }
    },

    renderList: function () {
        const container = document.getElementById('agence-list');
        container.innerHTML = AGENCES_BNC.map(a => `
            <div class="agence-item ${a.principale ? 'main-branch' : ''} ${this.activeId === a.id ? 'active' : ''}" id="agi-${a.id}" onclick="AgencesBncMap.select(${a.id})">
                <div class="d-flex align-items-start gap-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:${a.principale ? 'rgba(212,175,55,.15)' : 'rgba(0,61,122,.08)'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-bank2" style="color:${a.principale ? 'var(--or-fonce,#a8791f)' : 'var(--bleu-nuit,#003d7a)'};"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div style="font-weight:700;font-size:.9rem;">
                            ${this.escapeHtml(a.nom)} ${a.principale ? '<span class="agence-badge">SIÈGE</span>' : ''}
                        </div>
                        <div style="font-size:.78rem;color:#666;margin-top:2px;"><i class="bi bi-signpost me-1"></i>${this.escapeHtml(a.departement)}</div>
                        <div style="font-size:.8rem;color:#666;margin-top:2px;"><i class="bi bi-geo-alt me-1"></i>${this.escapeHtml(a.adresse)}</div>
                        <button class="btn-directions" onclick="event.stopPropagation(); AgencesBncMap.selectForRoute(${a.id})"><i class="bi bi-signpost-2 me-1"></i>Obtenir l'itinéraire</button>
                    </div>
                </div>
            </div>`).join('');
    },

    select: function (id) {
        this.activeId = id;
        this.selected = AGENCES_BNC.find(a => a.id === id);
        this.renderList();
        if (this.selected) {
            this.map.setView([this.selected.lat, this.selected.lng], 13);
            const marker = this.markers[AGENCES_BNC.indexOf(this.selected)];
            if (marker) marker.openPopup();
        }
        this.tryAutoRoute();
    },

    selectForRoute: function (id) { this.select(id); },

    tryAutoRoute: function () {
        if (this.userPosition && this.selected) {
            this.buildRoute(this.userPosition, this.selected);
        }
    },

    calculateRoute: function () {
        const addr = document.getElementById('startAddress').value.trim();
        if (!this.selected) { this.showToast('Sélectionnez d\'abord une agence', 'error'); return; }
        if (this.userPosition) { this.buildRoute(this.userPosition, this.selected); return; }
        if (addr.length < 3) { this.showToast('Entrez une adresse de départ', 'error'); return; }

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addr + ', Haïti')}&limit=1`)
            .then(res => res.json())
            .then(data => {
                if (!data.length) { this.showToast('Adresse introuvable', 'error'); return; }
                this.userPosition = { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon), name: addr };
                this.buildRoute(this.userPosition, this.selected);
            });
    },

    clearRoute: function () {
        if (this.routingControl) { this.map.removeControl(this.routingControl); this.routingControl = null; }
        if (this.startMarker) { this.map.removeLayer(this.startMarker); this.startMarker = null; }
        document.getElementById('directionsPanel').style.display = 'none';
    },

    buildRoute: function (startPoint, agence) {
        this.clearRoute();

        this.routingControl = L.Routing.control({
            waypoints: [L.latLng(startPoint.lat, startPoint.lng), L.latLng(agence.lat, agence.lng)],
            lineOptions: { styles: [{ color: '#d4af37', weight: 5 }] },
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: false,
            show: false,
            createMarker: () => null,
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' })
        }).addTo(this.map);

        const startIcon = L.divIcon({
            className: 'start-marker',
            html: `<div style="background-color:#4285F4;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,.3);color:#fff;font-size:12px;"><i class="bi bi-flag-fill"></i></div>`,
            iconSize: [26, 26]
        });
        this.startMarker = L.marker([startPoint.lat, startPoint.lng], { icon: startIcon })
            .addTo(this.map).bindPopup(`<strong>Départ :</strong> ${this.escapeHtml(startPoint.name)}`).openPopup();

        this.routingControl.on('routesfound', (e) => {
            const routes = e.routes;
            if (routes.length > 0) this.displayDirections(routes[0].summary, routes[0].instructions, agence.nom);
        });
        this.routingControl.on('routingerror', () => this.showToast("Impossible de calculer l'itinéraire", 'error'));

        setTimeout(() => {
            const bounds = L.latLngBounds([startPoint, agence]);
            this.map.fitBounds(bounds.pad(0.2));
        }, 400);
    },

    displayDirections: function (summary, instructions, destName) {
        const panel = document.getElementById('directionsPanel');
        const content = document.getElementById('directionsContent');
        const totalDistance = (summary.totalDistance / 1000).toFixed(1);
        const totalTime = Math.round(summary.totalTime / 60);

        let html = `
            <div class="directions-summary">
                <div>
                    <strong>Destination :</strong> ${this.escapeHtml(destName)}<br>
                    <small style="color:#666">${totalDistance} km · ${totalTime} min</small>
                </div>
                <div><i class="bi bi-car-front-fill"></i></div>
            </div>
            <div class="directions-steps">`;

        instructions.forEach((instruction) => {
            let icon = 'bi-arrow-up-right';
            if (instruction.text.includes('tourner à gauche')) icon = 'bi-arrow-90deg-left';
            else if (instruction.text.includes('tourner à droite')) icon = 'bi-arrow-90deg-right';
            else if (instruction.text.includes('continuer tout droit')) icon = 'bi-arrow-up';
            else if (instruction.text.includes('arrivée')) icon = 'bi-flag-fill';

            const distance = instruction.distance ? (instruction.distance / 1000).toFixed(1) + ' km' : '';
            html += `
                <div class="directions-step">
                    <div><i class="bi ${icon}"></i></div>
                    <div class="directions-step-text">${instruction.text}</div>
                    <div class="directions-step-distance">${distance}</div>
                </div>`;
        });

        html += `</div>`;
        content.innerHTML = html;
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    },

    closeDirections: function () { document.getElementById('directionsPanel').style.display = 'none'; },

    locateUser: function () {
        if (!navigator.geolocation) { this.showToast('Géolocalisation non supportée', 'error'); return; }
        this.showToast('Recherche de votre position...', 'info');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.userPosition = { lat: position.coords.latitude, lng: position.coords.longitude, name: 'Ma position' };
                if (this.userMarker) this.map.removeLayer(this.userMarker);

                const userIcon = L.divIcon({
                    className: 'user-marker',
                    html: `<div style="background-color:#4285F4;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,.3);color:#fff;font-size:12px;"><i class="bi bi-person-fill"></i></div>`,
                    iconSize: [26, 26]
                });
                this.userMarker = L.marker([this.userPosition.lat, this.userPosition.lng], { icon: userIcon })
                    .addTo(this.map).bindPopup('Votre position').openPopup();

                this.map.setView([this.userPosition.lat, this.userPosition.lng], 14);
                document.getElementById('startAddress').placeholder = 'Position détectée';
                this.showToast('Position trouvée !');
                this.tryAutoRoute();
            },
            (error) => {
                let message = 'Erreur de géolocalisation';
                if (error.code === error.PERMISSION_DENIED) message = 'Permission de géolocalisation refusée';
                else if (error.code === error.POSITION_UNAVAILABLE) message = 'Position non disponible';
                else if (error.code === error.TIMEOUT) message = "Délai d'attente dépassé";
                this.showToast(message, 'error');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    },

    setupSearch: function () {
        const input = document.getElementById('startAddress');
        const box = document.getElementById('addressSuggestions');

        input.addEventListener('input', () => {
            const query = input.value;
            clearTimeout(this.debounceTimer);
            if (query.length < 3) { box.style.display = 'none'; box.innerHTML = ''; return; }

            this.debounceTimer = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query + ', Haïti')}&limit=5`)
                    .then(res => res.json())
                    .then(data => this.showSuggestions(data));
            }, 400);
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.directions-search')) box.style.display = 'none';
        });
    },

    showSuggestions: function (results) {
        const box = document.getElementById('addressSuggestions');
        if (results.length === 0) { box.style.display = 'none'; return; }
        box.style.display = 'block';
        box.innerHTML = results.map(place => `
            <div class="suggestion-item" onclick="AgencesBncMap.selectSuggestion('${this.escapeHtml(place.display_name).replace(/'/g, "\\'")}', ${place.lat}, ${place.lon})">
                ${this.escapeHtml(place.display_name)}
            </div>`).join('');
    },

    selectSuggestion: function (name, lat, lon) {
        document.getElementById('startAddress').value = name;
        document.getElementById('addressSuggestions').style.display = 'none';
        this.userPosition = { lat: parseFloat(lat), lng: parseFloat(lon), name };
        this.tryAutoRoute();
    },

    escapeHtml: function (text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    showToast: function (message, type = 'success') {
        const stack = document.getElementById('toast-stack');
        const toast = document.createElement('div');
        toast.className = `toast-g ${type === 'error' ? 'err' : ''}`;
        toast.innerHTML = `<i class="bi ${type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-geo-alt-fill'}"></i> ${this.escapeHtml(message)}`;
        stack.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', function () { AgencesBncMap.init(); });
</script>
<?php endif; ?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
