<?php
/**
 * public/index.php
 * Point d'entrée (front controller) du SITE PUBLIC de Gourde Numérique.
 *
 * La partie administration a son propre point d'entrée, séparé et indépendant :
 * voir public/admin/index.php (routes /admin, /admin/clients, etc.).
 * Le .htaccess de public/ redirige automatiquement toute URL commençant par
 * /admin vers ce second routeur — ce fichier-ci ne traite donc plus que les
 * pages du site public.
 */

require_once dirname(__DIR__) . '/config/config.php';

function route(): void
{
    $path = request_path();

    $routes = [
        '/'            => ['HomeController', 'index'],
        '/login'       => ['AccountController', 'loginPage'],
        '/envoyer'     => ['TransactionController', 'envoyer'],
        '/recevoir'    => ['TransactionController', 'recevoir'],
        '/paiement'    => ['TransactionController', 'paiement'],
        '/scanner'     => ['TransactionController', 'scanner'],
        '/agences-bnc' => ['HomeController', 'agencesBnc'],
        '/fonctionnalites' => ['HomeController', 'fonctionnalites'],
        '/objectifs'   => ['HomeController', 'objectifs'],
        '/contact'     => ['HomeController', 'contact'],
        '/historique'  => ['TransactionController', 'historique'],
        '/plus'        => ['AccountController', 'plus'],
        '/epargne'     => ['AccountController', 'epargne'],
        '/cartes-virtuelles' => ['AccountController', 'cartes'],
        '/securite'    => ['AccountController', 'securite'],
        '/reglages'    => ['AccountController', 'reglages'],
        '/convertir'   => ['AccountController', 'convertir'],
        '/convertir/moncash/retour' => ['AccountController', 'convertirMoncashRetour'],
        '/convertir/moncash/verifier' => ['AccountController', 'convertirMoncashVerifier'],
        '/aide'        => ['AccountController', 'aide'],
    ];

    if (!isset($routes[$path])) {
        http_response_code(404);
        echo '<h1>404</h1><p>Page introuvable : ' . e($path) . '</p><p><a href="' . url('/') . '">Retour à l\'accueil</a></p>';
        return;
    }

    [$controllerName, $method] = $routes[$path];
    $controller = new $controllerName();
    $controller->$method();
}

route();
