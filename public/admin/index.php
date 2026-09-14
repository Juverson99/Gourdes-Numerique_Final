<?php
/**
 * public/admin/index.php
 * Point d'entrée (front controller) DÉDIÉ À L'ADMINISTRATION de Gourde Numérique.
 *
 * Séparé du routeur du site public (public/index.php) afin que les deux
 * parties soient totalement indépendantes : chacune a son propre fichier
 * "index", son propre tableau de routes, et ne peut pas interférer avec
 * l'autre. C'est le .htaccess de public/ qui aiguille automatiquement toute
 * URL commençant par /admin vers CE fichier-ci (voir public/.htaccess).
 *
 * BASE_URL (config/config.php) est calculée dynamiquement à partir du script
 * réellement exécuté : que ce soit ce fichier ou public/index.php qui répond,
 * les liens générés par url() pointent toujours correctement vers la racine
 * du site, même si l'application change d'emplacement (sous-dossier, domaine,
 * hébergement...). C'est ce qui empêche le lien de redirection de se "casser"
 * après un déploiement.
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

function route_admin(): void
{
    // request_path() gère déjà BASE_URL et un éventuel "/index.php" final ;
    // il ne reste plus qu'à retirer le préfixe /admin propre à ce routeur.
    $path = request_path();
    $path = preg_replace('#^/admin#', '', $path);
    if ($path === '') {
        $path = '/';
    }

    $routes = [
        '/'         => ['AdminController', 'dashboard'],
        '/login'    => ['AdminController', 'loginPage'],
        '/clients'  => ['AdminController', 'clients'],
        '/clients/modifier' => ['AdminController', 'clientForm'],
        '/client'   => ['AdminController', 'client'],
        '/recevoir' => ['AdminController', 'recevoir'],
        '/envoyer'  => ['AdminController', 'envoyer'],
        '/paiement' => ['AdminController', 'paiement'],
        '/moncash'  => ['AdminController', 'moncash'],
        '/moncash/verifier' => ['AdminController', 'moncashVerifier'],
        '/historique' => ['AdminController', 'historique'],
        '/epargne'  => ['AdminController', 'epargne'],
        '/billet'   => ['AdminController', 'billet'],
        '/statistiques' => ['AdminController', 'statistiques'],

        // Dossier des retraits en espèces (billet électronique -> billet physique)
        '/retraits-especes'            => ['AdminController', 'retraitsEspeces'],
        '/retraits-especes/confirmer'  => ['AdminController', 'retraitEspeceConfirmer'],
        '/retraits-especes/annuler'    => ['AdminController', 'retraitEspeceAnnuler'],

        // Dossier de gestion des billets (coupures de la page publique "Envoyer")
        '/billets'            => ['AdminController', 'billets'],
        '/billets/nouveau'    => ['AdminController', 'billetForm'],
        '/billets/modifier'   => ['AdminController', 'billetForm'],
        '/billets/supprimer'  => ['AdminController', 'billetDelete'],
        '/billets/basculer'   => ['AdminController', 'billetToggle'],

        // Catégories de billets (regroupement des coupures)
        '/categories-billets'            => ['AdminController', 'categoriesBillets'],
        '/categories-billets/nouveau'    => ['AdminController', 'categorieBilletForm'],
        '/categories-billets/modifier'   => ['AdminController', 'categorieBilletForm'],
        '/categories-billets/supprimer'  => ['AdminController', 'categorieBilletDelete'],
        '/categories-billets/basculer'   => ['AdminController', 'categorieBilletToggle'],

        // Catégories de paiement (chips "Payez marchands et factures en un tap")
        '/categories-paiement'           => ['AdminController', 'categoriesPaiement'],
        '/categories-paiement/nouveau'   => ['AdminController', 'categoriePaiementForm'],
        '/categories-paiement/modifier'  => ['AdminController', 'categoriePaiementForm'],
        '/categories-paiement/supprimer' => ['AdminController', 'categoriePaiementDelete'],
        '/categories-paiement/basculer'  => ['AdminController', 'categoriePaiementToggle'],

        // Gestion du stock des billets (quantité en réserve par coupure)
        '/stock'          => ['AdminController', 'stock'],
        '/stock/ajuster'  => ['AdminController', 'stockAjuster'],
        '/alertes-stock'  => ['AdminController', 'alertesStock'],

        // Dossier des institutions financières (banques, coopératives, EMI partenaires)
        '/institutions'            => ['AdminController', 'institutions'],
        '/institutions/mouvements' => ['AdminController', 'institutionMouvements'],
        '/institutions/nouveau'    => ['AdminController', 'institutionForm'],
        '/institutions/modifier'   => ['AdminController', 'institutionForm'],
        '/institutions/supprimer'  => ['AdminController', 'institutionDelete'],
        '/institutions/basculer'   => ['AdminController', 'institutionToggle'],

        // Dossier des fournisseurs (billets/liquidités, imprimeurs, matériel, services)
        '/fournisseurs'            => ['AdminController', 'fournisseurs'],
        '/fournisseurs/nouveau'    => ['AdminController', 'fournisseurForm'],
        '/fournisseurs/modifier'   => ['AdminController', 'fournisseurForm'],
        '/fournisseurs/supprimer'  => ['AdminController', 'fournisseurDelete'],
        '/fournisseurs/basculer'   => ['AdminController', 'fournisseurToggle'],

        // Dossier des agences BNC (carte interactive publique)
        '/agences-bnc'            => ['AdminController', 'agencesBnc'],
        '/agences-bnc/nouveau'    => ['AdminController', 'agenceBncForm'],
        '/agences-bnc/modifier'   => ['AdminController', 'agenceBncForm'],
        '/agences-bnc/supprimer'  => ['AdminController', 'agenceBncDelete'],
        '/agences-bnc/basculer'   => ['AdminController', 'agenceBncToggle'],

        // Dossier des cartes virtuelles générées par les clients
        '/cartes-virtuelles'            => ['AdminController', 'cartesVirtuelles'],
        '/cartes-virtuelles/basculer'   => ['AdminController', 'carteVirtuelleToggle'],
        '/cartes-virtuelles/supprimer'  => ['AdminController', 'carteVirtuelleDelete'],

        // Dossier des messages de contact (envoyés depuis la page publique /contact)
        '/messages'               => ['AdminController', 'messages'],
        '/messages/voir'          => ['AdminController', 'message'],
        '/messages/traiter'       => ['AdminController', 'messageTraiter'],
        '/messages/supprimer'     => ['AdminController', 'messageDelete'],

        // Dossier des rapports (export Excel / PDF des différents dossiers)
        '/rapports'       => ['AdminController', 'rapports'],
        '/rapports/excel' => ['AdminController', 'rapportExcel'],
        '/rapports/pdf'   => ['AdminController', 'rapportPdf'],

        // Dossier de gestion des administrateurs
        '/administrateurs'              => ['AdminController', 'administrateurs'],
        '/administrateurs/nouveau'      => ['AdminController', 'administrateurForm'],
        '/administrateurs/modifier'     => ['AdminController', 'administrateurForm'],
        '/administrateurs/promouvoir'   => ['AdminController', 'administrateurPromouvoir'],
        '/administrateurs/retrograder'  => ['AdminController', 'administrateurRetrograder'],
        '/administrateurs/supprimer'    => ['AdminController', 'administrateurSupprimer'],

        // Dossier de gestion des employés (accès à /admin limité par permissions)
        '/employes'              => ['AdminController', 'employes'],
        '/employes/nouveau'      => ['AdminController', 'employeForm'],
        '/employes/modifier'     => ['AdminController', 'employeForm'],
        '/employes/promouvoir'   => ['AdminController', 'employePromouvoir'],
        '/employes/retrograder'  => ['AdminController', 'employeRetrograder'],
        '/employes/supprimer'    => ['AdminController', 'employeSupprimer'],

        // Journal d'activité (historique des actions administratives)
        '/journal' => ['AdminController', 'journal'],
    ];

    if (!isset($routes[$path])) {
        http_response_code(404);
        echo '<h1>404</h1><p>Page administration introuvable : ' . e('/admin' . $path) . '</p><p><a href="' . url('/admin') . '">Retour au tableau de bord</a></p>';
        return;
    }

    [$controllerName, $method] = $routes[$path];
    $controller = new $controllerName();
    $controller->$method();
}

route_admin();
