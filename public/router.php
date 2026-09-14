<?php
/**
 * public/router.php
 * À utiliser UNIQUEMENT avec le serveur de développement intégré de PHP,
 * qui ne lit pas le .htaccess (donc pas de règles de réécriture automatiques).
 * Apache/Nginx en production utilisent .htaccess / la config du serveur à la
 * place de ce fichier — il n'a aucune utilité en dehors du développement local.
 *
 * Lancer le site en local, depuis la racine du projet, avec :
 *   php -S localhost:8080 -t public public/router.php
 * puis ouvrir : http://localhost:8080/  (site)  et  http://localhost:8080/admin  (admin)
 *
 * Important : le -t doit pointer sur le dossier "public" (pas sur le dossier
 * du projet ni sur un dossier parent), sinon les URL contiennent des
 * sous-dossiers en trop et le routeur ne les reconnaît pas.
 */

$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Fichier réel existant (assets/, api/*.php, favicon...) : on laisse le
// serveur intégré le servir tel quel plutôt que de passer par le routeur MVC.
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Toute URL commençant par /admin va vers le routeur admin, le reste vers le site public —
// exactement la même logique que public/.htaccess en production.
if (preg_match('#^/admin(/|$)#', $uri)) {
    require __DIR__ . '/admin/index.php';
} else {
    require __DIR__ . '/index.php';
}
