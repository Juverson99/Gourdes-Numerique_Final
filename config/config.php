<?php
/**
 * config/config.php
 * Paramètres globaux de la plateforme Gourde Numérique.
 */

// --------------------------------------------------------------
// Mode debug : contrôle l'affichage des erreurs PHP détaillées et des
// messages d'exception complets (fichier, ligne, trace). DÉSACTIVÉ par
// défaut (sécurisé par défaut) : une base de données injoignable ou une
// erreur SQL ne doit jamais révéler de chemins serveur ni de requêtes à un
// visiteur en production. Pour le développement local, définir la variable
// d'environnement APP_DEBUG=1 (ou APP_DEBUG=true).
// --------------------------------------------------------------
// --------------------------------------------------------------
// Config locale optionnelle (config/local.php), JAMAIS committée.
// Utile sous XAMPP/WAMP/MAMP (Apache + mod_php) : contrairement au serveur
// intégré `php -S` (où on peut faire `export MONCASH_CLIENT_ID=...` avant de
// lancer le serveur), Apache/mod_php ne définit par défaut AUCUNE variable
// d'environnement PHP pour getenv(). Sans ce fichier, MONCASH_CLIENT_ID et
// MONCASH_CLIENT_SECRET restent vides sous XAMPP même si tu as bien tes
// identifiants — c'est la cause la plus fréquente d'un bouton "Payer avec
// MonCash" qui ne redirige jamais. Voir config/local.example.php.
// --------------------------------------------------------------
if (is_readable(__DIR__ . '/local.php')) {
    require __DIR__ . '/local.php';
}

define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN));
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Fuseau horaire (Haïti)
date_default_timezone_set('America/Port-au-Prince');

/**
 * Détecte si la requête actuelle arrive en HTTPS (connexion chiffrée),
 * y compris derrière un proxy/répartiteur de charge qui termine le SSL
 * avant d'atteindre PHP (cas fréquent en hébergement mutualisé/cloud).
 */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? null) == 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

// --------------------------------------------------------------
// Redirection forcée vers HTTPS (cadenas du navigateur).
// Désactivée par défaut pour ne pas casser le site tant que le
// certificat SSL n'est pas configuré sur le serveur (WAMP, hébergement...).
// À activer une fois HTTPS réellement disponible : définir la variable
// d'environnement FORCE_HTTPS=1, ou mettre directement `true` ci-dessous.
// --------------------------------------------------------------
define('FORCE_HTTPS', filter_var(getenv('FORCE_HTTPS') ?: false, FILTER_VALIDATE_BOOLEAN));

if (FORCE_HTTPS && !is_https() && php_sapi_name() !== 'cli') {
    $redirectUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: ' . $redirectUrl, true, 301);
    exit;
}

/**
 * Détecte si la requête actuelle appartient à l'espace administrateur, pour
 * lui donner un cookie de session distinct de celui du site client (voir
 * juste en dessous). Sans ça, connexion admin et connexion cliente
 * partagent la même session PHP : se connecter comme admin déconnecte le
 * client sur ce même navigateur, et inversement.
 * Couvre deux cas :
 *  - toute URL qui commence par /admin (le back-office lui-même, servi par
 *    public/admin/index.php) ;
 *  - les appels au point d'entrée PARTAGÉ /api/account.php (connexion et
 *    déconnexion) quand ils viennent explicitement de l'écran de connexion
 *    admin, repérés par le paramètre ?scope=admin ajouté à ces appels-là
 *    (voir app/views/admin/login.php).
 */
function is_admin_area_request(): bool
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#/admin(/|$|\.php$)#', $path)) {
        return true;
    }
    $scope = $_GET['scope'] ?? $_POST['scope'] ?? '';
    return $scope === 'admin';
}

// Cookie de session sécurisé : envoyé uniquement en HTTPS quand c'est
// disponible (jamais en clair), inaccessible en JavaScript (protection XSS)
// et non transmis lors d'une navigation externe (protection CSRF de base).
// Démarrage de session (une seule fois, avant tout envoi de contenu)
// Nom de cookie distinct pour l'espace admin : un administrateur et un
// client peuvent ainsi rester connectés en même temps dans le même
// navigateur, chacun dans sa propre session, sans se marcher dessus.
if (session_status() === PHP_SESSION_NONE) {
    session_name(is_admin_area_request() ? 'GN_ADMIN_SESSID' : 'GN_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// En-têtes de sécurité de base, envoyés sur toutes les pages de l'application.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Désactive les API navigateur sensibles que l'application n'utilise pas.
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(self), payment=(self)');
    // CSP : autorise uniquement les CDN réellement utilisés par l'appli
    // (icônes Bootstrap, jsQR pour le scanner, Leaflet + OSRM pour la carte
    // des agences BNC) en plus de l'origine elle-même.
    // 'unsafe-inline' reste nécessaire pour les scripts/styles inline déjà
    // présents dans les vues ; à retirer progressivement si ces blocs sont
    // un jour migrés vers des fichiers .js/.css externes avec nonce.
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://unpkg.com; "
        . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com https://unpkg.com; "
        . "img-src 'self' data: blob: https://unpkg.com https://*.tile.openstreetmap.org; "
        . "connect-src 'self' https://nominatim.openstreetmap.org https://router.project-osrm.org; "
        . "frame-ancestors 'self'; "
        . "base-uri 'self'; "
        . "form-action 'self'");
    // HSTS (force le navigateur à toujours utiliser HTTPS pour ce domaine
    // sur la durée indiquée) : n'a de sens qu'une fois HTTPS actif, pour ne
    // jamais enfermer un visiteur hors ligne sur un site resté en HTTP.
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// URL de base de l'application (utilisée par le routeur, les vues et le JS).
// Détectée automatiquement, pour que les liens ne se cassent plus si l'appli
// change d'emplacement (racine du domaine, sous-dossier, hébergement mutualisé, etc.).
// Plus besoin de modifier cette valeur à la main lors d'un déploiement.
//
// Priorité à l'URL réellement demandée par le navigateur (REQUEST_URI) via le
// dossier "public" : c'est la seule donnée fiable à 100 % sur les
// déploiements où "public" n'est PAS la racine web servie (dossier du projet
// copié tel quel dans un htdocs, double dossier créé par un re-téléchargement
// du zip — ex. "gourde-numerique (4)/gourde-numerique/public/...", serveur de
// développement lancé avec une mauvaise racine de document...) :
// $_SERVER['SCRIPT_NAME'] n'y reflète pas toujours fidèlement ce préfixe, ce
// qui cassait aussi bien les liens générés (url()) que le routeur — voir
// request_path() plus bas pour le même repli côté routage.
$__requestUriPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (preg_match('#^(.*/public)(/.*)?$#', $__requestUriPath, $__m)) {
    $__scriptDir = $__m[1];
} else {
    // Repli : URL déjà "propre" (public/ EST la racine web, cas normal en
    // production) — on retombe sur le script réellement exécuté.
    $__scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Le front controller admin vit dans public/admin/ : on remonte d'un niveau
    // pour retrouver la racine publique du site (là où vivent /assets, /api, etc.).
    if (basename($__scriptDir) === 'admin') {
        $__scriptDir = dirname($__scriptDir);
    }
}
define('BASE_URL', $__scriptDir === '/' || $__scriptDir === '\\' ? '' : rtrim($__scriptDir, '/'));
unset($__scriptDir, $__requestUriPath, $__m);

// Solde de départ offert à la création d'un compte (démo pédagogique)
define('SOLDE_INITIAL', 0.00);

// Chemins racine
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');

// Chargement de la connexion à la base de données
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/moncash.php';

// Autoload simple des classes Controllers / Models
spl_autoload_register(function ($class) {
    $dirs = [APP_PATH . '/controllers/', APP_PATH . '/models/', APP_PATH . '/lib/'];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

/**
 * Petits utilitaires partagés par les vues
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return number_format($amount, 2, ',', ' ');
}

/**
 * Format attendu pour le NINU et le téléphone, valable pour TOUS les
 * formulaires de l'application (inscription publique, réglages du compte,
 * dossiers clients/employés/administrateurs) : une seule règle, définie ici,
 * appliquée partout de la même façon pour éviter qu'un formulaire accepte
 * ce qu'un autre refuse.
 */
define('NINU_LENGTH', 10);          // NINU : exactement ce nombre de chiffres, ni plus ni moins
define('PHONE_INDICATIF', '509');   // Indicatif Haïti
define('PHONE_LOCAL_LENGTH', 8);    // Nombre de chiffres après l'indicatif, ex. "509 46213235"

/** Ne garde que les chiffres d'une chaîne saisie (retire espaces, tirets, parenthèses, "+"...). */
function only_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

/** Valide un numéro NINU : uniquement des chiffres, exactement NINU_LENGTH chiffres.
 *  Retourne un message d'erreur en français, ou null si le numéro est valide. */
function validate_ninu(string $ninu): ?string
{
    $digits = only_digits($ninu);
    if ($digits === '') {
        return 'Le numéro NINU est obligatoire.';
    }
    if (strlen($digits) !== NINU_LENGTH) {
        return 'Le numéro NINU doit contenir exactement ' . NINU_LENGTH . ' chiffres (uniquement des chiffres, ni espace ni lettre).';
    }
    return null;
}

/** Normalise un NINU saisi (ne garde que les chiffres) pour un enregistrement cohérent en base. */
function normalize_ninu(string $ninu): string
{
    return only_digits($ninu);
}

/** Valide un numéro de téléphone : indicatif Haïti (509) suivi de PHONE_LOCAL_LENGTH chiffres,
 *  ex. "509 46213235". L'indicatif peut être omis à la saisie (ajouté automatiquement).
 *  Retourne un message d'erreur en français, ou null si le numéro est valide. */
function validate_phone(string $phone): ?string
{
    $digits = only_digits($phone);
    if ($digits === '') {
        return 'Le numéro de téléphone est obligatoire.';
    }
    if (strlen($digits) > PHONE_LOCAL_LENGTH && str_starts_with($digits, PHONE_INDICATIF)) {
        $digits = substr($digits, strlen(PHONE_INDICATIF));
    }
    if (strlen($digits) !== PHONE_LOCAL_LENGTH) {
        return 'Le numéro de téléphone doit contenir l\'indicatif ' . PHONE_INDICATIF . ' suivi de ' . PHONE_LOCAL_LENGTH . ' chiffres, par exemple « ' . PHONE_INDICATIF . ' 46213235 ».';
    }
    return null;
}

/** Normalise un téléphone saisi au format "509 XXXXXXXX" pour un enregistrement cohérent en base. */
function normalize_phone(string $phone): string
{
    $digits = only_digits($phone);
    if (strlen($digits) > PHONE_LOCAL_LENGTH && str_starts_with($digits, PHONE_INDICATIF)) {
        $digits = substr($digits, strlen(PHONE_INDICATIF));
    }
    return PHONE_INDICATIF . ' ' . $digits;
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Calcule le chemin de route "propre" à partir de l'URL demandée, quel que
 * soit le contexte : Apache/.htaccess (URL déjà propre, ex "/admin/clients"),
 * ou serveur de dev PHP intégré / mauvaise racine de document (URL contenant
 * encore le sous-dossier de déploiement et/ou "/index.php" en toutes lettres).
 *
 * Priorité au repli "/public" plutôt qu'à BASE_URL : sur un déploiement où le
 * dossier "public" n'est PAS la racine web servie (dossier du projet copié
 * tel quel dans un htdocs, double dossier créé par un re-téléchargement du
 * zip — ex. "gourde-numerique (4)/gourde-numerique/public/...", serveur de
 * développement lancé avec une mauvaise racine de document, hébergement
 * automatisé qui ne met pas SCRIPT_NAME à jour...), $_SERVER['SCRIPT_NAME']
 * ne reflète pas fidèlement l'URL réellement demandée et BASE_URL calculée
 * plus haut ne correspond alors plus à son vrai début : le lien cliqué garde
 * tout le sous-dossier de déploiement ET "/public" (ex.
 * "/mon-projet/public/paiement"), jamais réduit, d'où le "404 : page
 * introuvable" même pour une route qui existe bel et bien. Comme "public" est
 * toujours le nom du dossier des deux front controllers (public/index.php et
 * public/admin/index.php) et qu'aucune route de l'appli ne s'appelle jamais
 * "public", on peut sans risque couper tout ce qui précède — et y compris —
 * la DERNIÈRE occurrence de ce segment : c'est plus fiable, dans TOUS les
 * cas, que de dépendre de BASE_URL/SCRIPT_NAME. Sur un déploiement standard
 * où "public" EST déjà la racine web (donc absent de l'URL), ce repli ne
 * matche simplement pas et on retombe sur BASE_URL comme avant.
 */
function request_path(): string
{
    $path = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $path = urldecode($path);

    if (preg_match('#^(.*)/public(/.*)?$#', $path, $m)) {
        // ".*" est glouton : il consomme tout, puis recule au minimum
        // nécessaire pour que "/public" matche — il retrouve donc toujours
        // la DERNIÈRE occurrence de "/public" dans l'URL, jamais la première.
        $path = ($m[2] ?? '') === '' ? '/' : $m[2];
    } elseif (BASE_URL !== '' && str_starts_with($path, BASE_URL)) {
        $path = substr($path, strlen(BASE_URL));
    }

    $path = preg_replace('#/(index\.php)?$#', '', $path);

    return $path === '' ? '/' : $path;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Vérifie que l'utilisateur en session existe TOUJOURS en base de données,
 * et nettoie la session sinon (compte supprimé entre-temps, base réimportée
 * avec des IDs qui recommencent à zéro, etc.). Sans ça, une session
 * "orpheline" fait planter toute page protégée (User::find() renvoie null,
 * et le code appelant part du principe qu'un utilisateur connecté existe).
 * Renvoie l'utilisateur (tableau) s'il est valide, sinon null.
 */
function current_user_or_clear_session(): ?array
{
    if (!is_logged_in()) {
        return null;
    }
    $user = User::find(current_user_id());
    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['is_admin'], $_SESSION['is_employee']);
        return null;
    }
    return $user;
}

/**
 * URI courante (chemin + query string), sans le domaine, prête à être
 * réutilisée comme paramètre ?redirect=... après un (ré)login.
 *
 * IMPORTANT : ne JAMAIS utiliser parse_url(..., PHP_URL_PATH) seul ici —
 * ça tronque la query string. Or plusieurs retours externes (notamment
 * MonCash : /convertir/moncash/retour?transactionId=...) dépendent
 * entièrement de leur query string pour être traités après connexion. Si la
 * session serveur expire pendant que le client est sur la passerelle MonCash
 * (paiement qui prend plusieurs minutes) et qu'on le renvoie vers /login,
 * perdre transactionId ici veut dire perdre la confirmation du paiement :
 * le dépôt reste "en attente" indéfiniment côté plateforme, même si le
 * client a réellement payé, jusqu'à une réconciliation manuelle par un
 * administrateur (voir Transaction::moncashVerifierManuel()).
 */
function current_request_uri(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    // REQUEST_URI est déjà relatif (jamais de schéma/hôte) côté serveurs web
    // standards, mais on l'assainit quand même par prudence (jamais de
    // redirection externe via un `//host` ou `https://host` injecté).
    if ($uri === '' || $uri[0] !== '/' || str_starts_with($uri, '//') || preg_match('#^/https?:#i', $uri)) {
        return '/';
    }
    return $uri;
}

/**
 * Impose la connexion : redirige vers l'accueil (modal de connexion) si non connecté.
 * Utilisé par les pages/actions API qui exigent un utilisateur authentifié.
 */
function require_login(): void
{
    $user = current_user_or_clear_session();
    if (!$user) {
        if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Veuillez vous connecter.']);
            exit;
        }
        $current = current_request_uri();
        header('Location: ' . url('/login') . '?redirect=' . urlencode($current));
        exit;
    }
}

/**
 * Un utilisateur connecté est-il administrateur ? (flag posé en session à la connexion,
 * voir AccountController::login)
 */
function is_admin(): bool
{
    return is_logged_in() && !empty($_SESSION['is_admin']);
}

/**
 * Un utilisateur connecté est-il employé ? (flag posé en session à la connexion,
 * voir AccountController::login). Un employé a accès à l'espace /admin, mais
 * toujours limité aux modules que ses permissions autorisent explicitement —
 * jamais un accès complet, contrairement à un super administrateur.
 */
function is_employee(): bool
{
    return is_logged_in() && !empty($_SESSION['is_employee']);
}

/** Un utilisateur connecté fait-il partie du personnel de la plateforme (admin OU employé) ? */
function is_staff(): bool
{
    return is_admin() || is_employee();
}

/**
 * Impose le statut administrateur : renvoie vers /login si non connecté,
 * ou vers l'accueil (403) si connecté mais pas admin. Utilisé par AdminController
 * pour les dossiers réservés aux seuls comptes role='admin' (ex. gestion des
 * administrateurs et des employés eux-mêmes).
 */
function require_admin(): void
{
    $user = current_user_or_clear_session();
    if (!$user) {
        $current = current_request_uri();
        header('Location: ' . url('/admin/login') . '?redirect=' . urlencode($current));
        exit;
    }
    if (!is_admin()) {
        http_response_code(403);
        echo '<h1>403</h1><p>Accès réservé aux administrateurs de la plateforme.</p><p><a href="' . url('/') . '">Retour à l\'accueil</a></p>';
        exit;
    }
}

/**
 * Impose l'appartenance au personnel de la plateforme (administrateur OU
 * employé) : renvoie vers /admin/login si non connecté, ou vers l'accueil
 * (403) si connecté mais ni admin ni employé. Base commune utilisée par
 * require_permission() et par le tableau de bord.
 */
function require_staff(): void
{
    $user = current_user_or_clear_session();
    if (!$user) {
        $current = current_request_uri();
        header('Location: ' . url('/admin/login') . '?redirect=' . urlencode($current));
        exit;
    }
    if (!is_staff()) {
        http_response_code(403);
        echo '<h1>403</h1><p>Accès réservé au personnel de la plateforme.</p><p><a href="' . url('/') . '">Retour à l\'accueil</a></p>';
        exit;
    }
}

/**
 * Liste des modules de l'administration soumis à permission (hors Tableau de
 * bord, toujours accessible à tout administrateur). Clé = valeur stockée
 * dans users.permissions, utilisée aussi bien pour le contrôle d'accès que
 * pour construire les cases à cocher du formulaire administrateur.
 */
function admin_permission_modules(): array
{
    return [
        'clients'         => ['label' => 'Dossier des clients',         'icon' => 'bi-people-fill'],
        'recevoir'        => ['label' => 'Recevoir (tous clients)',     'icon' => 'bi-inboxes-fill'],
        'envoyer'         => ['label' => 'Envoyer (tous clients)',      'icon' => 'bi-send-fill'],
        'paiement'        => ['label' => 'Paiement',                   'icon' => 'bi-credit-card-fill'],
        'moncash'         => ['label' => 'Transactions MonCash',       'icon' => 'bi-phone-fill'],
        'epargne'         => ['label' => 'Épargne',                    'icon' => 'bi-piggy-bank-fill'],
        'retraits_especes'=> ['label' => 'Retraits en espèces',        'icon' => 'bi-cash-stack'],
        'cartes'          => ['label' => 'Cartes virtuelles',          'icon' => 'bi-credit-card-2-front-fill'],
        'billet'          => ['label' => 'Dépôt manuel (billet)',       'icon' => 'bi-cash-coin'],
        'historique'      => ['label' => 'Historique',                 'icon' => 'bi-clock-history'],
        'statistiques'    => ['label' => 'Statistiques',               'icon' => 'bi-bar-chart-fill'],
        'billets'         => ['label' => 'Gestion des billets',        'icon' => 'bi-wallet2'],
        'institutions'    => ['label' => 'Institutions financières',  'icon' => 'bi-bank'],
        'fournisseurs'    => ['label' => 'Fournisseurs',              'icon' => 'bi-truck'],
        'agences'         => ['label' => 'Agences BNC',               'icon' => 'bi-geo-alt-fill'],
        'messages'        => ['label' => 'Messages de contact',       'icon' => 'bi-envelope-fill'],
        'rapports'        => ['label' => 'Rapports (Excel / PDF)',     'icon' => 'bi-file-earmark-bar-graph-fill'],
        'administrateurs' => ['label' => 'Gestion des administrateurs','icon' => 'bi-shield-lock-fill'],
        'employes'        => ['label' => 'Gestion des employés',      'icon' => 'bi-person-badge-fill'],
        'journal'         => ['label' => "Journal d'activité",         'icon' => 'bi-clock-history'],
    ];
}

/**
 * Clés de modules qui restent toujours réservées à un compte role='admin' :
 * un employé ne peut jamais se voir attribuer la gestion des comptes
 * administrateurs, ni celle des comptes employés eux-mêmes (principe du
 * moindre privilège — seul un administrateur gère qui a accès à quoi).
 */
function staff_restricted_modules(): array
{
    return ['administrateurs', 'employes'];
}

/**
 * Permissions de l'administrateur actuellement connecté.
 * - null   => accès complet (super administrateur, valeur par défaut historique)
 * - array  => liste des clés de modules autorisés (peut être vide = aucun module,
 *             seul le tableau de bord reste accessible)
 * Mise en cache (variable statique) : une seule requête par exécution de page,
 * même si admin_can() est appelé plusieurs fois (menu latéral + contrôleur).
 */
function current_admin_permissions(): ?array
{
    static $resolved = false;
    static $permissions = [];

    if ($resolved) {
        return $permissions;
    }
    $resolved = true;

    $id = current_user_id();
    if (!$id) {
        $permissions = [];
        return $permissions;
    }
    $user = User::find($id);
    $permissions = $user ? User::permissionsArray($user) : [];
    return $permissions;
}

/** L'administrateur connecté a-t-il accès à ce module ? (null = accès complet) */
function admin_can(string $module): bool
{
    $permissions = current_admin_permissions();
    return $permissions === null || in_array($module, $permissions, true);
}

/**
 * Impose à la fois le statut administrateur ET la permission d'accéder à un
 * module précis de l'administration. À utiliser à la place de require_admin()
/**
 * Nombre de coupures de billets actives dont le stock est descendu au
 * seuil d'alerte ou en dessous. Mis en cache (une seule requête SQL par
 * page, même si affiché à la fois dans le menu latéral et le contenu).
 */
function low_stock_alert_count(int $threshold = 50): int
{
    static $cache = [];
    if (!array_key_exists($threshold, $cache)) {
        $cache[$threshold] = count(BankNote::lowStock($threshold));
    }
    return $cache[$threshold];
}

/** Nombre de demandes de retrait en espèces en attente de remise au guichet (badge admin) */
function pending_cash_withdrawals_count(): int
{
    static $count = null;
    if ($count === null) {
        $stmt = db()->query('SELECT COUNT(*) FROM transactions WHERE type = "retrait_especes" AND status = "en_attente"');
        $count = (int) $stmt->fetchColumn();
    }
    return $count;
}

/** Nombre de messages de contact non encore lus (badge admin) */
function new_contact_messages_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = ContactMessage::counts()['nouveaux'];
    }
    return $count;
}

/**
 * Impose à la fois l'appartenance au personnel (admin OU employé) ET la
 * permission d'accéder à un module précis de l'administration. À utiliser à
 * la place de require_admin()/require_staff() dans les contrôleurs
 * correspondant à un module de la liste ci-dessus.
 */
function require_permission(string $module): void
{
    require_staff();
    if (in_array($module, staff_restricted_modules(), true) && !is_admin()) {
        http_response_code(403);
        echo '<h1>403</h1><p>Ce module est réservé aux administrateurs de la plateforme.</p><p><a href="' . url('/admin') . '">Retour au tableau de bord</a></p>';
        exit;
    }
    if (!admin_can($module)) {
        http_response_code(403);
        echo '<h1>403</h1><p>Votre compte administrateur n\'a pas la permission d\'accéder à ce module.</p><p><a href="' . url('/admin') . '">Retour au tableau de bord</a></p>';
        exit;
    }
}

function generate_reference(string $prefix = 'GN'): string
{
    return strtoupper($prefix) . '-' . date('ymd') . '-' . bin2hex(random_bytes(4));
}

/**
 * Première lettre d'un nom, en majuscule, pour les avatars (header + admin).
 * Utilise mbstring si disponible (gère les accents), sinon repli en ASCII simple
 * pour ne jamais faire planter l'application sur un hébergement sans mbstring.
 */
function initial_letter(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '?';
    }
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($name, 0, 1));
}

/**
 * Valide un chemin de redirection fourni par l'utilisateur (paramètre ?redirect=)
 * pour éviter les redirections ouvertes vers un autre site.
 * N'accepte qu'un chemin interne commençant par un seul "/".
 */
function safe_redirect_path(?string $path): string
{
    if (!$path || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
        return '/';
    }
    // Un seul segment de chemin + query string, jamais un hôte externe
    if (preg_match('#^https?://#i', $path)) {
        return '/';
    }
    return $path;
}

/**
 * Filet de sécurité global : si une erreur imprévue survient n'importe où
 * dans l'appli (site ou admin) — base de données injoignable, table
 * manquante, etc. — on affiche TOUJOURS un message clair plutôt qu'une page
 * blanche (ce qui peut arriver selon la config "display_errors" du serveur).
 *
 * Le détail technique (message d'exception, chemin de fichier, ligne) n'est
 * affiché au visiteur QUE si APP_DEBUG est activé (développement local) :
 * en production, ces informations peuvent révéler la structure du serveur,
 * des requêtes SQL ou des chemins internes à un attaquant. L'erreur complète
 * est toujours écrite dans le journal serveur (error_log) pour l'équipe
 * technique, quel que soit le mode.
 */
set_exception_handler(function (Throwable $ex): void {
    error_log(sprintf(
        '[Gourde Numérique] Exception non interceptée : %s dans %s:%d',
        $ex->getMessage(),
        $ex->getFile(),
        $ex->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (APP_DEBUG) {
        $msg  = htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($ex->getFile(), ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
           . '<title>Erreur — Gourde Numérique</title></head>'
           . '<body style="font-family:sans-serif;max-width:680px;margin:60px auto;padding:0 20px;color:#1a1a2e;">'
           . '<h1 style="color:#c0392b;">Une erreur est survenue</h1>'
           . '<p>' . $msg . '</p>'
           . '<p style="color:#777;font-size:.85rem;">' . $file . ':' . $ex->getLine() . '</p>'
           . '<p style="color:#777;font-size:.85rem;">Vérifiez que MySQL est démarré dans XAMPP/WAMP et que '
           . '<code>database.sql</code> a bien été importé dans la base <code>gourde_numerique</code>.</p>'
           . '</body></html>';
        return;
    }

    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
       . '<title>Erreur — Gourde Numérique</title></head>'
       . '<body style="font-family:sans-serif;max-width:680px;margin:60px auto;padding:0 20px;color:#1a1a2e;text-align:center;">'
       . '<h1 style="color:#c0392b;">Une erreur est survenue</h1>'
       . '<p>Le service rencontre un problème temporaire. Merci de réessayer dans quelques instants.</p>'
       . '</body></html>';
});
