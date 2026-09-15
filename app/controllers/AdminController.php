<?php
/**
 * app/controllers/AdminController.php
 * Partie administration de la plateforme, accessible au personnel (comptes
 * role='admin' à accès potentiellement complet, ou role='employe' à accès
 * toujours limité par permissions — voir is_staff()/require_staff() dans
 * config.php) :
 *  - Tableau de bord
 *  - Dossier des clients (liste + fiche client)
 *  - Dossier recevoir de tous les clients (toutes les réceptions, tous clients confondus)
 *  - Dossier envoyer de tous les clients (tous les envois entre clients, tous clients confondus)
 *  - Dossier paiement de tous les clients (tous les paiements de factures/marchands)
 *  - Dossier historique (journal global de toutes les opérations, tous clients/types)
 *  - Dossier épargne (soldes épargne des clients + dépôt/retrait manuel)
 *  - Dossier des retraits en espèces (billet électronique -> billet physique)
 *  - Dossier d'ajouter un billet pour un client (dépôt manuel de solde)
 *  - Dossier de gestion des billets (coupures de la page publique "Envoyer")
 *  - Dossier des institutions financières (banques, coopératives, EMI partenaires)
 *  - Dossier des fournisseurs (billets/liquidités, imprimeurs, matériel, services)
 *  - Dossier des messages de contact reçus depuis la page publique /contact
 *  - Dossier de gestion des administrateurs (liste, création, promotion d'un
 *    client existant, modification, rétrogradation, suppression)
 *  - Dossier de gestion des employés (même principe que les administrateurs,
 *    mais un employé n'a jamais un accès complet : ses permissions sont
 *    toujours une liste explicite de modules, réservée par un administrateur)
 *  - Journal d'activité (historique des actions administratives)
 */
class AdminController
{
    /** Page dédiée "/admin/login" : point d'entrée réel de l'espace admin
     *  quand on n'est pas encore connecté (voir require_staff() dans
     *  config/config.php). Design distinct du site client. Accessible aux
     *  administrateurs ET aux employés (tout le personnel de la plateforme). */
    public function loginPage(): void
    {
        $redirect = safe_redirect_path($_GET['redirect'] ?? '/admin');
        // Doit rester dans l'espace admin : on ignore toute redirection
        // qui ne pointe pas vers /admin.
        if (!str_starts_with($redirect, '/admin')) {
            $redirect = '/admin';
        }
        if (is_logged_in() && is_staff()) {
            header('Location: ' . url($redirect));
            exit;
        }
        require VIEWS_PATH . '/admin/login.php';
    }

    /** Tableau de bord : vue d'ensemble (accessible à tout le personnel connecté) */
    public function dashboard(): void
    {
        require_staff();
        $currentUser = User::toPublic(User::find(current_user_id()));

        $stats = [
            'total_clients'  => User::countClients(),
            'masse_monetaire'=> User::totalBalanceClients(),
            'total_recu'     => Transaction::allReceived(1000),
            'total_epargne'  => User::totalEpargneClients(),
            'stock_billets'  => BankNote::totalStockCount(),
            'valeur_stock'   => BankNote::totalStockValue(),
        ];
        $dernieresReceptions = array_slice($stats['total_recu'], 0, 6);
        $derniersClients     = array_slice(User::allClients(), 0, 6);
        $stockFaible         = BankNote::lowStock(50);

        $chartVolume7j     = Transaction::volumeParJour(7);
        $chartRepartition  = Transaction::repartitionParType();

        require VIEWS_PATH . '/admin/dashboard.php';
    }

    /** Dossier des clients : liste de tous les clients ayant créé un compte */
    public function clients(): void
    {
        require_permission('clients');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search  = trim((string) ($_GET['q'] ?? ''));
        $clients = User::allClients($search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/clients.php';
    }

    /** Dossier d'un client : fiche détaillée + son historique d'opérations */
    public function client(): void
    {
        require_permission('clients');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $clientId = (int) ($_GET['id'] ?? 0);
        $client   = $clientId ? User::find($clientId) : null;

        if (!$client || User::isAdminRow($client)) {
            http_response_code(404);
            echo '<h1>404</h1><p>Client introuvable.</p><p><a href="' . url('/admin/clients') . '">Retour au dossier des clients</a></p>';
            return;
        }

        $operations = Transaction::historique($clientId, 100);
        require VIEWS_PATH . '/admin/client.php';
    }

    /** Dossier paiement : tous les paiements de factures/marchands, tous clients confondus */
    public function paiement(): void
    {
        require_permission('paiement');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search   = trim((string) ($_GET['q'] ?? ''));
        $paiements = Transaction::allPayments(300, $search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/paiement.php';
    }

    /* =========================================================
     * Dossier retraits en espèces : demandes des clients de convertir
     * du billet électronique en billet PHYSIQUE, à retirer dans une
     * agence BNC. Un administrateur doit confirmer la remise réelle
     * des espèces au guichet (ou annuler/rembourser si non honorée).
     * /admin/retraits-especes
     * ========================================================= */

    public function retraitsEspeces(): void
    {
        require_permission('retraits_especes');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search   = trim((string) ($_GET['q'] ?? ''));
        $retraits = Transaction::allRetraitsEspeces(300, $search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/retraits_especes.php';
    }

    /** Confirme qu'un client a bien reçu ses espèces au guichet */
    public function retraitEspeceConfirmer(): void
    {
        require_permission('retraits_especes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reference = trim((string) ($_POST['reference'] ?? ''));
            if ($reference !== '') {
                $result = Transaction::confirmerRetraitEspeces($reference);
                if ($result['ok']) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'confirmation_retrait_especes', 'transaction', null, $reference);
                }
            }
        }
        header('Location: ' . url('/admin/retraits-especes'));
        exit;
    }

    /** Annule une demande non honorée et rembourse le client */
    public function retraitEspeceAnnuler(): void
    {
        require_permission('retraits_especes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reference = trim((string) ($_POST['reference'] ?? ''));
            if ($reference !== '') {
                $result = Transaction::annulerRetraitEspeces($reference);
                if ($result['ok']) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'annulation_retrait_especes', 'transaction', null, $reference);
                }
            }
        }
        header('Location: ' . url('/admin/retraits-especes'));
        exit;
    }

    /** Dossier historique : journal global de toutes les opérations, tous clients et tous types */
    public function historique(): void
    {
        require_permission('historique');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search     = trim((string) ($_GET['q'] ?? ''));
        $operations = Transaction::allHistorique(300, $search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/historique.php';
    }

    /** Dossier envoyer : tous les envois d'argent entre clients, tous clients confondus */
    public function envoyer(): void
    {
        require_permission('envoyer');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search = trim((string) ($_GET['q'] ?? ''));
        $envois = Transaction::allSent(300, $search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/envoyer.php';
    }

    /**
     * Dossier transactions MonCash : tous les dépôts effectués par les clients
     * via MonCash (dossier client "Convertir"), tous statuts confondus.
     */
    public function moncash(): void
    {
        require_permission('moncash');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search   = trim((string) ($_GET['q'] ?? ''));
        $moncash  = Transaction::allMoncash(300, $search !== '' ? $search : null);
        $stats    = Transaction::moncashStats();
        $verifyError   = $_GET['verify_error'] ?? null;
        $verifySuccess = $_GET['verify_success'] ?? null;

        // État de configuration de l'intégration MonCash (config/moncash.php),
        // affiché en haut de la page pour repérer d'un coup d'œil un identifiant
        // manquant ou un environnement sandbox oublié en production.
        $moncashConfig = [
            'mode'      => MONCASH_MODE,
            'configure' => MONCASH_CLIENT_ID !== '' && MONCASH_CLIENT_SECRET !== '',
        ];

        require VIEWS_PATH . '/admin/moncash.php';
    }

    /**
     * Réconciliation manuelle d'un dépôt MonCash resté "en attente" : le
     * client a pu payer réellement sur MonCash sans jamais revenir sur le
     * site (navigateur fermé, coupure réseau...), ce qui laisse la
     * transaction bloquée côté plateforme. Interroge l'API MonCash par
     * référence de commande (voir Transaction::moncashVerifierManuel() /
     * MonCash::retrieveOrderPayment()) plutôt que d'attendre un retour client
     * qui ne viendra jamais.
     */
    public function moncashVerifier(): void
    {
        require_permission('moncash');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reference = trim((string) ($_POST['reference'] ?? ''));
            if ($reference !== '') {
                $result = Transaction::moncashVerifierManuel($reference);
                $currentUser = User::toPublic(User::find(current_user_id()));
                if ($result['ok']) {
                    $detail = ($result['already'] ?? false) ? 'déjà confirmé' : money($result['amount']) . ' HTG crédités';
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'verification_moncash', 'transaction', null, $reference, $detail);
                    header('Location: ' . url('/admin/moncash') . '?verify_success=' . urlencode($detail));
                    exit;
                } else {
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'verification_moncash_echec', 'transaction', null, $reference, $result['error']);
                    header('Location: ' . url('/admin/moncash') . '?verify_error=' . urlencode($result['error']));
                    exit;
                }
            }
        }
        header('Location: ' . url('/admin/moncash'));
        exit;
    }

    /** Dossier épargne : soldes épargne de tous les clients + dépôt/retrait manuel */
    public function epargne(): void
    {
        require_permission('epargne');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId  = (int) ($_POST['client_id'] ?? 0);
            $amount    = (float) ($_POST['amount'] ?? 0);
            $direction = (string) ($_POST['direction'] ?? 'depot');
            $note      = trim((string) ($_POST['note'] ?? ''));

            $result = Transaction::epargneAjuster(current_user_id(), $clientId, $amount, $direction, $note);
            if ($result['ok']) {
                $label = $direction === 'depot' ? 'déposé sur' : 'retiré de';
                $success = money($amount) . ' HTG ' . $label . ' l\'épargne de ' . $result['client_name']
                    . ' (référence ' . $result['reference'] . '). Nouveau solde épargne : ' . money($result['new_epargne']) . ' HTG.';
            } else {
                $error = $result['error'];
            }
        }

        $search  = trim((string) ($_GET['q'] ?? ''));
        $clients = User::allClients($search !== '' ? $search : null);
        $stats   = ['total_epargne' => User::totalEpargneClients()];
        $mouvements = Transaction::allEpargneMouvements(100);

        require VIEWS_PATH . '/admin/epargne.php';
    }

    /**
     * Dossier Statistiques : graphiques d'analyse complémentaires au tableau
     * de bord (volume mensuel, top clients, paiements par catégorie, villes).
     */
    public function statistiques(): void
    {
        require_permission('statistiques');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $volumeMensuel        = Transaction::volumeParMois(12);
        $topClients           = User::topClientsByBalance(5);
        $paiementsParCategorie = Transaction::repartitionPaiementsParCategorie(6);
        $villesClients        = User::repartitionParVille(8);

        $inscriptionsParMois  = User::inscriptionsParMois(12);
        $statutTransactions   = Transaction::repartitionParStatut();
        $masseMonetaire       = User::totalBalanceClients();
        $totalEpargne         = User::totalEpargneClients();

        require VIEWS_PATH . '/admin/statistiques.php';
    }

    /** Formulaire de modification des informations d'un client (GET affiche, POST enregistre) */
    public function clientForm(): void
    {
        require_permission('clients');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id     = (int) ($_GET['id'] ?? 0);
        $client = $id ? User::find($id) : null;

        if (!$client || User::isAdminRow($client)) {
            http_response_code(404);
            echo '<h1>404</h1><p>Client introuvable.</p><p><a href="' . url('/admin/clients') . '">Retour au dossier des clients</a></p>';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'             => trim((string) ($_POST['name'] ?? '')),
                'ninu'             => trim((string) ($_POST['ninu'] ?? '')),
                'sexe'             => (string) ($_POST['sexe'] ?? ''),
                'age'              => (int) ($_POST['age'] ?? 0),
                'ville'            => trim((string) ($_POST['ville'] ?? '')),
                'pays'             => trim((string) ($_POST['pays'] ?? 'Haïti')),
                'phone'            => trim((string) ($_POST['phone'] ?? '')),
                'email'            => trim((string) ($_POST['email'] ?? '')),
                'password'         => (string) ($_POST['password'] ?? ''),
                'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
            ];

            $errors = $this->validateClientData($data);

            if (empty($errors)) {
                // Normalise avant vérification de doublon / enregistrement pour un format cohérent en base.
                $data['ninu']  = normalize_ninu($data['ninu']);
                $data['phone'] = normalize_phone($data['phone']);
                $conflict = User::existsByEmailPhoneOrNinu($data['email'], $data['phone'], $data['ninu'], $id);
                if ($conflict) {
                    $errors[] = $conflict;
                }
            }

            // Traitement de la photo de profil envoyée (upload facultatif)
            $upload = User::processPhotoUpload($_FILES['photo'] ?? []);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            }
            $removePhoto = !empty($_POST['remove_photo']) && !$upload['path'];

            if (empty($errors)) {
                $oldPhotoPath = $client['photo_path'] ?? null;

                if ($upload['path']) {
                    $data['photo_path'] = $upload['path'];
                } elseif ($removePhoto) {
                    $data['photo_path'] = null;
                }

                User::updateProfile($id, $data);
                AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_client', 'user', $id, $data['name']);

                // Nettoyage de l'ancienne photo si elle a été remplacée ou retirée
                if (array_key_exists('photo_path', $data) && $oldPhotoPath && $oldPhotoPath !== $data['photo_path']) {
                    User::deletePhotoFile($oldPhotoPath);
                }

                header('Location: ' . url('/admin/client?id=' . $id . '&success=1'));
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $client = array_merge($client, $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/client_form.php';
    }

    /** Règles de validation des informations d'un client, modifiées depuis l'admin */
    private function validateClientData(array $data): array
    {
        $errors = [];

        foreach (['name', 'ninu', 'sexe', 'age', 'ville', 'pays', 'phone', 'email'] as $field) {
            if ($data[$field] === '' || $data[$field] === null) {
                $errors[] = 'Veuillez remplir tous les champs obligatoires.';
                break;
            }
        }
        if ((int) $data['age'] < 18) {
            $errors[] = 'Le client doit avoir au moins 18 ans.';
        }
        if (!in_array($data['sexe'], ['Homme', 'Femme', 'Autre'], true)) {
            $errors[] = 'Sexe invalide.';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if ($data['ninu'] !== '' && ($ninuError = validate_ninu($data['ninu']))) {
            $errors[] = $ninuError;
        }
        if ($data['phone'] !== '' && ($phoneError = validate_phone($data['phone']))) {
            $errors[] = $phoneError;
        }
        // Mot de passe facultatif ici : vide = mot de passe du client inchangé
        if ($data['password'] !== '') {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            if ($data['password'] !== $data['password_confirm']) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
        }

        return $errors;
    }

    /** Dossier recevoir de tous les clients : toutes les réceptions, tous clients confondus */
    public function recevoir(): void
    {
        require_permission('recevoir');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search  = trim((string) ($_GET['q'] ?? ''));
        $receptions = Transaction::allReceived(300, $search !== '' ? $search : null);

        require VIEWS_PATH . '/admin/recevoir.php';
    }

    /** Dossier d'ajouter un billet pour un client (affiche le formulaire + traite l'envoi) */
    public function billet(): void
    {
        require_permission('billet');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId = (int) ($_POST['client_id'] ?? 0);
            $amount   = (float) ($_POST['amount'] ?? 0);
            $note     = trim((string) ($_POST['note'] ?? ''));

            $result = Transaction::ajouterBillet(current_user_id(), $clientId, $amount, $note);
            if ($result['ok']) {
                $success = 'Billet de ' . money($amount) . ' HTG ajouté au compte de ' . $result['client_name']
                    . ' (référence ' . $result['reference'] . '). Nouveau solde : ' . money($result['new_balance']) . ' HTG.';
            } else {
                $error = $result['error'];
            }
        }

        $clients = User::allClients();
        require VIEWS_PATH . '/admin/billet.php';
    }

    /* =========================================================
     * Dossier de gestion des billets (coupures affichées sur la
     * page publique "Envoyer") : /admin/billets
     * ========================================================= */

    /** Liste de toutes les coupures (billets, avec image recto/verso) gérées */
    public function billets(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $billets    = BankNote::all();
        $categories = BankNoteCategory::all();
        $success    = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/billets.php';
    }

    /* =========================================================
     * Gestion du stock des billets (quantité en réserve pour
     * chaque coupure) : /admin/stock
     * ========================================================= */

    /** Vue d'ensemble du stock : quantités actuelles, valeur totale, alertes */
    public function stock(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $billets           = BankNote::all();
        $totalStockCount   = BankNote::totalStockCount();
        $totalStockValue   = BankNote::totalStockValue();
        $seuilAlerte       = 50;
        $stockFaible       = BankNote::lowStock($seuilAlerte);
        $success           = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/stock.php';
    }

    /** Traite un ajustement de stock (+ ou -) pour une coupure, avec journalisation */
    public function stockAjuster(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        // Détermine vers quelle page revenir après l'action (le formulaire
        // de /admin/stock et celui de /admin/alertes-stock partagent la même
        // action, mais chacun doit ramener l'admin là où il travaillait).
        $redirectTo = ($_POST['_redirect'] ?? '') === 'alertes' ? '/admin/alertes-stock' : '/admin/stock';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url($redirectTo));
            exit;
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $delta  = (int) ($_POST['delta'] ?? 0);
        $motif  = trim((string) ($_POST['motif'] ?? ''));
        $billet = $id ? BankNote::find($id) : null;

        if (!$billet || $delta === 0) {
            header('Location: ' . url($redirectTo . '?error=1'));
            exit;
        }

        try {
            $newQty = BankNote::adjustStock($id, $delta);
        } catch (Throwable $e) {
            // Ne devrait plus arriver depuis l'auto-réparation du schéma dans
            // config/database.php, mais on protège quand même contre un souci
            // imprévu (droits SQL insuffisants...) avec un message explicite
            // plutôt qu'un échec silencieux du bouton "Ajuster".
            header('Location: ' . url($redirectTo . '?error=1'));
            exit;
        }

        $label  = money($billet['unit_value']) . ' HTG'
            . (!empty($billet['is_bundle']) ? ' (paquet de ' . $billet['bundle_qty'] . ')' : '')
            . ' : ' . ($delta > 0 ? '+' : '') . $delta . ' → nouveau stock ' . $newQty
            . ($motif !== '' ? ' (' . $motif . ')' : '');

        AdminLog::record($currentUser['id'], $currentUser['name'], 'ajustement_stock', 'bank_note', $id, $label);

        header('Location: ' . url($redirectTo . '?success=1'));
        exit;
    }

    /**
     * Dossier dédié aux alertes de stock : liste toutes les coupures dont le
     * stock est descendu au seuil d'alerte ou en dessous (seuil ajustable
     * via ?seuil=), avec possibilité d'ajuster le stock directement depuis
     * cette page. Distinct de /admin/stock (vue d'ensemble complète) : ici,
     * on ne voit QUE ce qui nécessite une action.
     */
    public function alertesStock(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $seuil = (int) ($_GET['seuil'] ?? 50);
        if ($seuil < 1) {
            $seuil = 50;
        }

        $alertes         = BankNote::lowStock($seuil);
        $ruptures        = array_filter($alertes, fn($b) => (int) $b['stock_quantity'] === 0);
        $valeurEnRisque  = array_sum(array_map(fn($b) => BankNote::totalValue($b) * (int) $b['stock_quantity'], $alertes));
        $success         = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/alertes_stock.php';
    }

    /** Formulaire de création OU de modification d'une coupure (GET affiche, POST enregistre) */
    public function billetForm(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id     = (int) ($_GET['id'] ?? 0);
        $isEdit = $id > 0;
        $billet = $isEdit ? BankNote::find($id) : null;

        if ($isEdit && !$billet) {
            http_response_code(404);
            echo '<h1>404</h1><p>Billet introuvable.</p><p><a href="' . url('/admin/billets') . '">Retour au dossier des billets</a></p>';
            return;
        }

        $errors = [];
        $categories = BankNoteCategory::all();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'unit_value'  => (float) ($_POST['unit_value'] ?? 0),
                // Fonctionnalité "paquet de billets" retirée du formulaire : chaque
                // billet est désormais géré comme une coupure simple, avec recto/verso.
                'is_bundle'   => false,
                'bundle_qty'  => null,
                'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
                'color_start' => trim((string) ($_POST['color_start'] ?? '')) ?: '#1f6fb2',
                'color_end'   => trim((string) ($_POST['color_end'] ?? '')) ?: '#164b7a',
                'image_path'       => $billet['image_path']       ?? null,
                'image_verso_path' => $billet['image_verso_path'] ?? null,
                'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                'active'      => !empty($_POST['active']),
            ];
            if (!$isEdit) {
                // Le stock initial n'est proposé qu'à la création : sa modification
                // ultérieure passe par le dossier de stock dédié (/admin/stock),
                // pour toujours garder une trace des mouvements dans le journal.
                $data['stock_quantity'] = max(0, (int) ($_POST['stock_quantity'] ?? 0));
            }

            $errors = BankNote::validate($data, $isEdit ? $id : null);

            // Traitement des images envoyées (upload) — recto (avant) et verso (arrière)
            $uploadRecto = $this->processBilletImageUpload($_FILES['image'] ?? []);
            if ($uploadRecto['error']) {
                $errors[] = $uploadRecto['error'];
            }
            $uploadVerso = $this->processBilletImageUpload($_FILES['image_verso'] ?? []);
            if ($uploadVerso['error']) {
                $errors[] = $uploadVerso['error'];
            }
            $removeImage      = !empty($_POST['remove_image']) && !$uploadRecto['path'];
            $removeImageVerso = !empty($_POST['remove_image_verso']) && !$uploadVerso['path'];

            if (empty($errors)) {
                $oldImagePath      = $billet['image_path']       ?? null;
                $oldImageVersoPath = $billet['image_verso_path'] ?? null;

                if ($uploadRecto['path']) {
                    $data['image_path'] = $uploadRecto['path'];
                } elseif ($removeImage) {
                    $data['image_path'] = null;
                }

                if ($uploadVerso['path']) {
                    $data['image_verso_path'] = $uploadVerso['path'];
                } elseif ($removeImageVerso) {
                    $data['image_verso_path'] = null;
                }

                $label = money($data['unit_value']) . ' HTG';
                if ($billet) {
                    BankNote::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_billet', 'bank_note', $id, $label);
                } else {
                    $newId = BankNote::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_billet', 'bank_note', $newId, $label);
                }

                // Nettoyage des anciennes images si elles ont été remplacées ou retirées
                if ($oldImagePath && $oldImagePath !== $data['image_path']) {
                    BankNote::deleteImageFile($oldImagePath);
                }
                if ($oldImageVersoPath && $oldImageVersoPath !== $data['image_verso_path']) {
                    BankNote::deleteImageFile($oldImageVersoPath);
                }

                header('Location: ' . url('/admin/billets?success=1'));
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $billet = array_merge($billet ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/billet_form.php';
    }

    /**
     * Valide et déplace l'image d'un billet envoyée via le formulaire admin
     * ($_FILES['image']) vers /public/assets/uploads/billets/.
     * Retourne ['path' => chemin relatif ou null, 'error' => message ou null].
     */
    private function processBilletImageUpload(array $file): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE  => "L'image dépasse la taille maximale autorisée par le serveur pour un envoi de fichier (souvent 2 Mo par défaut). Réduisez le poids de l'image (compression, redimensionnement) ou demandez à augmenter les réglages upload_max_filesize / post_max_size du serveur, puis réessayez.",
                UPLOAD_ERR_FORM_SIZE => "L'image dépasse la taille maximale autorisée par le formulaire. Réduisez le poids de l'image et réessayez.",
                UPLOAD_ERR_PARTIAL   => "L'image n'a été que partiellement envoyée (connexion interrompue). Réessayez l'envoi.",
                UPLOAD_ERR_NO_TMP_DIR => "Le serveur n'a pas de dossier temporaire disponible pour recevoir l'image. Contactez l'hébergeur.",
                UPLOAD_ERR_CANT_WRITE => "Le serveur n'a pas pu écrire l'image sur le disque. Contactez l'hébergeur.",
                UPLOAD_ERR_EXTENSION  => "L'envoi de l'image a été bloqué par une extension du serveur PHP.",
            ];
            return ['path' => null, 'error' => $messages[$file['error']] ?? "L'envoi de l'image a échoué (code {$file['error']}). Veuillez réessayer."];
        }

        $maxSize = 4 * 1024 * 1024; // 4 Mo
        if ((int) $file['size'] > $maxSize) {
            return ['path' => null, 'error' => "L'image du billet ne doit pas dépasser 4 Mo."];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['path' => null, 'error' => "Le fichier envoyé n'est pas une image valide."];
        }

        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        if (!isset($allowedTypes[$info[2]])) {
            return ['path' => null, 'error' => "Formats d'image acceptés : JPG, PNG, GIF ou WEBP."];
        }

        $dir = ROOT_PATH . '/public/assets/uploads/billets';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => "Impossible de créer le dossier de stockage des images."];
        }

        $filename = 'billet-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$info[2]];
        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => "Impossible d'enregistrer l'image envoyée."];
        }

        return ['path' => 'assets/uploads/billets/' . $filename, 'error' => null];
    }

    /** Supprime une coupure du dossier de gestion des billets */
    public function billetDelete(): void
    {
        require_permission('billets');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $billet = BankNote::find($id);
                BankNote::delete($id);
                if ($billet) {
                    BankNote::deleteImageFile($billet['image_path'] ?? null);
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    $label = money($billet['unit_value']) . ' HTG' . ($billet['is_bundle'] ? ' (paquet de ' . $billet['bundle_qty'] . ')' : '');
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_billet', 'bank_note', $id, $label);
                }
            }
        }
        header('Location: ' . url('/admin/billets'));
        exit;
    }

    /** Active/désactive l'affichage d'une coupure sur la page publique Envoyer */
    public function billetToggle(): void
    {
        require_permission('billets');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                BankNote::toggleActive($id);
            }
        }
        header('Location: ' . url('/admin/billets'));
        exit;
    }

    /* =========================================================
     * Gestion des catégories de billets (regroupement des coupures) :
     * /admin/categories-billets
     * ========================================================= */

    /** Liste de toutes les catégories, avec le nombre de coupures rattachées */
    public function categoriesBillets(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $categories = BankNoteCategory::all();
        $noteCounts = BankNoteCategory::noteCounts();
        $success    = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/categories_billets.php';
    }

    /** Formulaire de création OU de modification d'une catégorie (GET affiche, POST enregistre) */
    public function categorieBilletForm(): void
    {
        require_permission('billets');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id       = (int) ($_GET['id'] ?? 0);
        $isEdit   = $id > 0;
        $categorie = $isEdit ? BankNoteCategory::find($id) : null;

        if ($isEdit && !$categorie) {
            http_response_code(404);
            echo '<h1>404</h1><p>Catégorie introuvable.</p><p><a href="' . url('/admin/categories-billets') . '">Retour au dossier des catégories</a></p>';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'        => trim((string) ($_POST['name'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                'active'      => !empty($_POST['active']),
            ];

            $errors = BankNoteCategory::validate($data, $isEdit ? $id : null);

            if (empty($errors)) {
                if ($categorie) {
                    BankNoteCategory::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_categorie_billet', 'bank_note_category', $id, $data['name']);
                } else {
                    $newId = BankNoteCategory::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_categorie_billet', 'bank_note_category', $newId, $data['name']);
                }
                header('Location: ' . url('/admin/categories-billets?success=1'));
                exit;
            }

            $categorie = array_merge($categorie ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/categorie_billet_form.php';
    }

    /** Supprime une catégorie (les coupures rattachées repassent "sans catégorie") */
    public function categorieBilletDelete(): void
    {
        require_permission('billets');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $categorie = BankNoteCategory::find($id);
                BankNoteCategory::delete($id);
                if ($categorie) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_categorie_billet', 'bank_note_category', $id, $categorie['name']);
                }
            }
        }
        header('Location: ' . url('/admin/categories-billets'));
        exit;
    }

    /** Active/archive une catégorie */
    public function categorieBilletToggle(): void
    {
        require_permission('billets');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                BankNoteCategory::toggleActive($id);
            }
        }
        header('Location: ' . url('/admin/categories-billets'));
        exit;
    }

    /* =========================================================
     * Gestion des catégories de paiement (chips affichées sur la page
     * publique "Paiement" — Payez marchands et factures en un tap) :
     * /admin/categories-paiement
     * ========================================================= */

    /** Liste de toutes les catégories, avec le nombre de paiements déjà enregistrés */
    public function categoriesPaiement(): void
    {
        require_permission('paiement');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $categories     = PaymentCategory::all();
        $paymentCounts  = PaymentCategory::paymentCounts();
        $success        = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/categories_paiement.php';
    }

    /** Formulaire de création OU de modification d'une catégorie (GET affiche, POST enregistre) */
    public function categoriePaiementForm(): void
    {
        require_permission('paiement');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id        = (int) ($_GET['id'] ?? 0);
        $isEdit    = $id > 0;
        $categorie = $isEdit ? PaymentCategory::find($id) : null;

        if ($isEdit && !$categorie) {
            http_response_code(404);
            echo '<h1>404</h1><p>Catégorie introuvable.</p><p><a href="' . url('/admin/categories-paiement') . '">Retour au dossier des catégories</a></p>';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'       => trim((string) ($_POST['name'] ?? '')),
                'icon'       => trim((string) ($_POST['icon'] ?? '')),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'active'     => !empty($_POST['active']),
            ];

            $errors = PaymentCategory::validate($data, $isEdit ? $id : null);

            if (empty($errors)) {
                if ($categorie) {
                    PaymentCategory::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_categorie_paiement', 'payment_category', $id, $data['name']);
                } else {
                    $newId = PaymentCategory::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_categorie_paiement', 'payment_category', $newId, $data['name']);
                }
                header('Location: ' . url('/admin/categories-paiement?success=1'));
                exit;
            }

            $categorie = array_merge($categorie ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/categorie_paiement_form.php';
    }

    /** Supprime une catégorie (les paiements déjà enregistrés dans l'historique ne sont pas affectés) */
    public function categoriePaiementDelete(): void
    {
        require_permission('paiement');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $categorie = PaymentCategory::find($id);
                PaymentCategory::delete($id);
                if ($categorie) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_categorie_paiement', 'payment_category', $id, $categorie['name']);
                }
            }
        }
        header('Location: ' . url('/admin/categories-paiement'));
        exit;
    }

    /** Active/archive une catégorie (les catégories archivées disparaissent des chips côté client) */
    public function categoriePaiementToggle(): void
    {
        require_permission('paiement');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                PaymentCategory::toggleActive($id);
            }
        }
        header('Location: ' . url('/admin/categories-paiement'));
        exit;
    }

    /* =========================================================
     * Dossier des institutions financières (banques, coopératives et
     * émetteurs de monnaie électronique partenaires) : /admin/institutions
     * ========================================================= */

    /** Liste des institutions financières partenaires, avec recherche */
    public function institutions(): void
    {
        require_permission('institutions');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search       = trim((string) ($_GET['q'] ?? ''));
        $institutions = FinancialInstitution::all($search !== '' ? $search : null);
        $counts       = FinancialInstitution::counts();
        $typeLabels   = FinancialInstitution::typeLabels();
        $montants     = Transaction::totauxMontantsInstitutions();
        $success      = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/institutions.php';
    }

    /**
     * Détail des montants reçus par UNE institution bancaire — virements
     * bancaires électroniques ET retraits en espèces honorés dans ses
     * agences — avec les informations importantes du client concerné pour
     * chaque opération : /admin/institutions/mouvements.
     */
    public function institutionMouvements(): void
    {
        require_permission('institutions');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id          = (int) ($_GET['id'] ?? 0);
        $institution = $id ? FinancialInstitution::find($id) : null;

        if (!$institution) {
            http_response_code(404);
            echo '<h1>404</h1><p>Institution introuvable.</p><p><a href="' . url('/admin/institutions') . '">Retour au dossier des institutions</a></p>';
            return;
        }

        $search     = trim((string) ($_GET['q'] ?? ''));
        $mouvements = Transaction::mouvementsInstitution($id, 300, $search !== '' ? $search : null);
        $total      = array_sum(array_column($mouvements, 'amount'));

        require VIEWS_PATH . '/admin/institution_mouvements.php';
    }

    /* =========================================================
     * Dossier "Cartes virtuelles" (vue d'ensemble admin, tous
     * clients confondus) : /admin/cartes-virtuelles
     * ========================================================= */

    /** Liste de toutes les cartes virtuelles générées par les clients, avec recherche */
    public function cartesVirtuelles(): void
    {
        require_permission('cartes');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search  = trim((string) ($_GET['q'] ?? ''));
        $cartes  = VirtualCard::allAdmin($search !== '' ? $search : null);
        $counts  = VirtualCard::counts();
        $success = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/cartes_virtuelles.php';
    }

    /** Active/gèle une carte virtuelle (n'importe quel client) */
    public function carteVirtuelleToggle(): void
    {
        require_permission('cartes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id    = (int) ($_POST['id'] ?? 0);
            $carte = $id ? VirtualCard::findAny($id) : null;
            if ($carte && VirtualCard::toggleStatusAdmin($id)) {
                $currentUser = User::toPublic(User::find(current_user_id()));
                $action      = $carte['status'] === 'active' ? 'gel_carte_virtuelle' : 'activation_carte_virtuelle';
                AdminLog::record($currentUser['id'], $currentUser['name'], $action, 'virtual_card', $id, $carte['client_name'] . ' — ' . VirtualCard::masked($carte['card_number']));
            }
        }
        header('Location: ' . url('/admin/cartes-virtuelles'));
        exit;
    }

    /** Supprime définitivement une carte virtuelle (n'importe quel client) */
    public function carteVirtuelleDelete(): void
    {
        require_permission('cartes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id    = (int) ($_POST['id'] ?? 0);
            $carte = $id ? VirtualCard::findAny($id) : null;
            if ($carte) {
                VirtualCard::deleteAdmin($id);
                $currentUser = User::toPublic(User::find(current_user_id()));
                AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_carte_virtuelle', 'virtual_card', $id, $carte['client_name'] . ' — ' . VirtualCard::masked($carte['card_number']));
            }
        }
        header('Location: ' . url('/admin/cartes-virtuelles'));
        exit;
    }

    /** Formulaire de création OU de modification d'une institution (GET affiche, POST enregistre) */
    public function institutionForm(): void
    {
        require_permission('institutions');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id          = (int) ($_GET['id'] ?? 0);
        $isEdit      = $id > 0;
        $institution = $isEdit ? FinancialInstitution::find($id) : null;
        $typeLabels  = FinancialInstitution::typeLabels();

        if ($isEdit && !$institution) {
            http_response_code(404);
            echo '<h1>404</h1><p>Institution introuvable.</p><p><a href="' . url('/admin/institutions') . '">Retour au dossier des institutions</a></p>';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'           => trim((string) ($_POST['name'] ?? '')),
                'type'           => (string) ($_POST['type'] ?? 'banque'),
                'license_number' => trim((string) ($_POST['license_number'] ?? '')),
                'contact_name'   => trim((string) ($_POST['contact_name'] ?? '')),
                'phone'          => trim((string) ($_POST['phone'] ?? '')),
                'email'          => trim((string) ($_POST['email'] ?? '')),
                'ville'          => trim((string) ($_POST['ville'] ?? '')),
                'address'        => trim((string) ($_POST['address'] ?? '')),
                'logo_path'      => $institution['logo_path'] ?? null,
                'status'         => (string) ($_POST['status'] ?? 'actif'),
                'notes'          => trim((string) ($_POST['notes'] ?? '')),
            ];

            $errors = FinancialInstitution::validate($data, $isEdit ? $id : null);

            // Normalise le téléphone au format "509 XXXXXXXX" avant l'enregistrement,
            // une fois la validation passée (comme pour les clients/employés/administrateurs).
            if (empty($errors) && $data['phone'] !== '') {
                $data['phone'] = normalize_phone($data['phone']);
            }

            // Traitement du logo envoyé (upload facultatif)
            $upload = $this->processInstitutionLogoUpload($_FILES['logo'] ?? []);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            }
            $removeLogo = !empty($_POST['remove_logo']) && !$upload['path'];

            if (empty($errors)) {
                $oldLogoPath = $institution['logo_path'] ?? null;

                if ($upload['path']) {
                    $data['logo_path'] = $upload['path'];
                } elseif ($removeLogo) {
                    $data['logo_path'] = null;
                }

                if ($institution) {
                    FinancialInstitution::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_institution', 'financial_institution', $id, $data['name']);
                } else {
                    $newId = FinancialInstitution::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_institution', 'financial_institution', $newId, $data['name']);
                }

                // Nettoyage de l'ancien logo si remplacé ou retiré
                if ($oldLogoPath && $oldLogoPath !== $data['logo_path']) {
                    FinancialInstitution::deleteLogoFile($oldLogoPath);
                }

                header('Location: ' . url('/admin/institutions?success=1'));
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $institution = array_merge($institution ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/institution_form.php';
    }

    /**
     * Valide et déplace le logo d'une institution envoyé via le formulaire
     * admin ($_FILES['logo']) vers /public/assets/uploads/institutions/.
     * Retourne ['path' => chemin relatif ou null, 'error' => message ou null].
     */
    private function processInstitutionLogoUpload(array $file): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => "L'envoi du logo a échoué. Veuillez réessayer."];
        }

        $maxSize = 4 * 1024 * 1024; // 4 Mo
        if ((int) $file['size'] > $maxSize) {
            return ['path' => null, 'error' => "Le logo ne doit pas dépasser 4 Mo."];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['path' => null, 'error' => "Le fichier envoyé n'est pas une image valide."];
        }

        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        if (!isset($allowedTypes[$info[2]])) {
            return ['path' => null, 'error' => "Formats d'image acceptés : JPG, PNG, GIF ou WEBP."];
        }

        $dir = ROOT_PATH . '/public/assets/uploads/institutions';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => "Impossible de créer le dossier de stockage des logos."];
        }

        $filename = 'institution-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$info[2]];
        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => "Impossible d'enregistrer le logo envoyé."];
        }

        return ['path' => 'assets/uploads/institutions/' . $filename, 'error' => null];
    }

    /** Supprime une institution du dossier des institutions financières */
    public function institutionDelete(): void
    {
        require_permission('institutions');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $institution = FinancialInstitution::find($id);
                FinancialInstitution::delete($id);
                if ($institution) {
                    FinancialInstitution::deleteLogoFile($institution['logo_path'] ?? null);
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_institution', 'financial_institution', $id, $institution['name']);
                }
            }
        }
        header('Location: ' . url('/admin/institutions'));
        exit;
    }

    /** Active/suspend le partenariat d'une institution financière */
    public function institutionToggle(): void
    {
        require_permission('institutions');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                FinancialInstitution::toggleStatus($id);
            }
        }
        header('Location: ' . url('/admin/institutions'));
        exit;
    }

    /* =========================================================
     * Dossier des fournisseurs (billets/liquidités, imprimeurs,
     * matériel, services) : /admin/fournisseurs
     * ========================================================= */

    /** Liste des fournisseurs, avec recherche */
    public function fournisseurs(): void
    {
        require_permission('fournisseurs');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search         = trim((string) ($_GET['q'] ?? ''));
        $fournisseurs   = Supplier::all($search !== '' ? $search : null);
        $counts         = Supplier::counts();
        $categoryLabels = Supplier::categoryLabels();
        $success        = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/fournisseurs.php';
    }

    /** Formulaire de création OU de modification d'un fournisseur (GET affiche, POST enregistre) */
    public function fournisseurForm(): void
    {
        require_permission('fournisseurs');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id             = (int) ($_GET['id'] ?? 0);
        $isEdit         = $id > 0;
        $fournisseur    = $isEdit ? Supplier::find($id) : null;
        $categoryLabels = Supplier::categoryLabels();

        if ($isEdit && !$fournisseur) {
            http_response_code(404);
            echo '<h1>404</h1><p>Fournisseur introuvable.</p><p><a href="' . url('/admin/fournisseurs') . '">Retour au dossier des fournisseurs</a></p>';
            return;
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'         => trim((string) ($_POST['name'] ?? '')),
                'category'     => (string) ($_POST['category'] ?? 'autre'),
                'tax_id'       => trim((string) ($_POST['tax_id'] ?? '')),
                'contact_name' => trim((string) ($_POST['contact_name'] ?? '')),
                'phone'        => trim((string) ($_POST['phone'] ?? '')),
                'email'        => trim((string) ($_POST['email'] ?? '')),
                'ville'        => trim((string) ($_POST['ville'] ?? '')),
                'address'      => trim((string) ($_POST['address'] ?? '')),
                'logo_path'    => $fournisseur['logo_path'] ?? null,
                'status'       => (string) ($_POST['status'] ?? 'actif'),
                'notes'        => trim((string) ($_POST['notes'] ?? '')),
            ];

            $errors = Supplier::validate($data, $isEdit ? $id : null);

            // Normalise le téléphone au format "509 XXXXXXXX" avant l'enregistrement,
            // une fois la validation passée (comme pour les clients/employés/administrateurs).
            if (empty($errors) && $data['phone'] !== '') {
                $data['phone'] = normalize_phone($data['phone']);
            }

            // Traitement du logo envoyé (upload facultatif)
            $upload = $this->processFournisseurLogoUpload($_FILES['logo'] ?? []);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            }
            $removeLogo = !empty($_POST['remove_logo']) && !$upload['path'];

            if (empty($errors)) {
                $oldLogoPath = $fournisseur['logo_path'] ?? null;

                if ($upload['path']) {
                    $data['logo_path'] = $upload['path'];
                } elseif ($removeLogo) {
                    $data['logo_path'] = null;
                }

                if ($fournisseur) {
                    Supplier::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_fournisseur', 'supplier', $id, $data['name']);
                } else {
                    $newId = Supplier::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_fournisseur', 'supplier', $newId, $data['name']);
                }

                // Nettoyage de l'ancien logo si remplacé ou retiré
                if ($oldLogoPath && $oldLogoPath !== $data['logo_path']) {
                    Supplier::deleteLogoFile($oldLogoPath);
                }

                header('Location: ' . url('/admin/fournisseurs?success=1'));
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $fournisseur = array_merge($fournisseur ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/fournisseur_form.php';
    }

    /**
     * Valide et déplace le logo d'un fournisseur envoyé via le formulaire
     * admin ($_FILES['logo']) vers /public/assets/uploads/fournisseurs/.
     * Retourne ['path' => chemin relatif ou null, 'error' => message ou null].
     */
    private function processFournisseurLogoUpload(array $file): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => "L'envoi du logo a échoué. Veuillez réessayer."];
        }

        $maxSize = 4 * 1024 * 1024; // 4 Mo
        if ((int) $file['size'] > $maxSize) {
            return ['path' => null, 'error' => "Le logo ne doit pas dépasser 4 Mo."];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['path' => null, 'error' => "Le fichier envoyé n'est pas une image valide."];
        }

        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        if (!isset($allowedTypes[$info[2]])) {
            return ['path' => null, 'error' => "Formats d'image acceptés : JPG, PNG, GIF ou WEBP."];
        }

        $dir = ROOT_PATH . '/public/assets/uploads/fournisseurs';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => "Impossible de créer le dossier de stockage des logos."];
        }

        $filename = 'fournisseur-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$info[2]];
        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => "Impossible d'enregistrer le logo envoyé."];
        }

        return ['path' => 'assets/uploads/fournisseurs/' . $filename, 'error' => null];
    }

    /** Supprime un fournisseur du dossier des fournisseurs */
    public function fournisseurDelete(): void
    {
        require_permission('fournisseurs');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $fournisseur = Supplier::find($id);
                Supplier::delete($id);
                if ($fournisseur) {
                    Supplier::deleteLogoFile($fournisseur['logo_path'] ?? null);
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_fournisseur', 'supplier', $id, $fournisseur['name']);
                }
            }
        }
        header('Location: ' . url('/admin/fournisseurs'));
        exit;
    }

    /** Active/suspend le partenariat d'un fournisseur */
    public function fournisseurToggle(): void
    {
        require_permission('fournisseurs');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                Supplier::toggleStatus($id);
            }
        }
        header('Location: ' . url('/admin/fournisseurs'));
        exit;
    }

    /* =========================================================
     * Dossier des agences BNC (Banque Nationale de Crédit), affichées au
     * public sur une carte interactive : /admin/agences-bnc
     * ========================================================= */

    /** Liste de toutes les agences, avec recherche (nom / ville / département) */
    public function agencesBnc(): void
    {
        require_permission('agences');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search  = trim((string) ($_GET['q'] ?? ''));
        $agences = BncAgence::all($search !== '' ? $search : null);
        $counts  = BncAgence::counts();
        $success = $_GET['success'] ?? null;

        require VIEWS_PATH . '/admin/agences_bnc.php';
    }

    /** Formulaire de création OU de modification d'une agence (GET affiche, POST enregistre) */
    public function agenceBncForm(): void
    {
        require_permission('agences');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id      = (int) ($_GET['id'] ?? 0);
        $isEdit  = $id > 0;
        $agence  = $isEdit ? BncAgence::find($id) : null;

        if ($isEdit && !$agence) {
            http_response_code(404);
            echo '<h1>404</h1><p>Agence introuvable.</p><p><a href="' . url('/admin/agences-bnc') . '">Retour au dossier des agences</a></p>';
            return;
        }

        $errors = [];
        $banques = FinancialInstitution::allActiveByType('banque');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'institution_id' => trim((string) ($_POST['institution_id'] ?? '')),
                'nom'         => trim((string) ($_POST['nom'] ?? '')),
                'departement' => (string) ($_POST['departement'] ?? ''),
                'ville'       => trim((string) ($_POST['ville'] ?? '')),
                'adresse'     => trim((string) ($_POST['adresse'] ?? '')),
                'telephone'   => trim((string) ($_POST['telephone'] ?? '')),
                'latitude'    => trim((string) ($_POST['latitude'] ?? '')),
                'longitude'   => trim((string) ($_POST['longitude'] ?? '')),
                'principale'  => !empty($_POST['principale']),
                'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                'active'      => !empty($_POST['active']),
            ];

            $errors = BncAgence::validate($data, $isEdit ? $id : null);

            // Normalise le téléphone au format "509 XXXXXXXX" avant l'enregistrement,
            // une fois la validation passée (comme pour les clients/employés/administrateurs).
            if (empty($errors) && $data['telephone'] !== '') {
                $data['telephone'] = normalize_phone($data['telephone']);
            }

            if (empty($errors)) {
                if ($agence) {
                    BncAgence::update($id, $data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_agence_bnc', 'bnc_agence', $id, $data['nom']);
                } else {
                    $newId = BncAgence::create($data);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_agence_bnc', 'bnc_agence', $newId, $data['nom']);
                }
                header('Location: ' . url('/admin/agences-bnc?success=1'));
                exit;
            }

            $agence = array_merge($agence ?? [], $data, ['id' => $id]);
        }

        require VIEWS_PATH . '/admin/agence_bnc_form.php';
    }

    /** Supprime une agence */
    public function agenceBncDelete(): void
    {
        require_permission('agences');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $agence = BncAgence::find($id);
                BncAgence::delete($id);
                if ($agence) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_agence_bnc', 'bnc_agence', $id, $agence['nom']);
                }
            }
        }
        header('Location: ' . url('/admin/agences-bnc'));
        exit;
    }

    /** Active/désactive l'affichage public d'une agence sur la carte */
    public function agenceBncToggle(): void
    {
        require_permission('agences');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                BncAgence::toggleActive($id);
            }
        }
        header('Location: ' . url('/admin/agences-bnc'));
        exit;
    }

    /* =========================================================
     * Dossier des messages de contact reçus depuis la page publique
     * /contact : /admin/messages
     * ========================================================= */

    /** Liste de tous les messages de contact reçus */
    public function messages(): void
    {
        require_permission('messages');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $messages = ContactMessage::all();
        $counts   = ContactMessage::counts();

        require VIEWS_PATH . '/admin/messages.php';
    }

    /** Affiche le détail d'un message et le marque automatiquement comme "lu" */
    public function message(): void
    {
        require_permission('messages');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id      = (int) ($_GET['id'] ?? 0);
        $message = $id > 0 ? ContactMessage::find($id) : null;

        if (!$message) {
            http_response_code(404);
            echo '<h1>404</h1><p>Message introuvable.</p><p><a href="' . url('/admin/messages') . '">Retour au dossier des messages</a></p>';
            return;
        }

        if ($message['status'] === 'nouveau') {
            ContactMessage::updateStatus($id, 'lu');
            $message['status'] = 'lu';
        }

        require VIEWS_PATH . '/admin/message.php';
    }

    /** Marque un message comme "traité" */
    public function messageTraiter(): void
    {
        require_permission('messages');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                ContactMessage::updateStatus($id, 'traite');
            }
        }
        header('Location: ' . url('/admin/messages'));
        exit;
    }

    /** Supprime un message de contact */
    public function messageDelete(): void
    {
        require_permission('messages');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $message = ContactMessage::find($id);
                ContactMessage::delete($id);
                if ($message) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_message', 'contact_message', $id, $message['subject']);
                }
            }
        }
        header('Location: ' . url('/admin/messages'));
        exit;
    }

    /* =========================================================
     * Dossier de gestion des administrateurs : /admin/administrateurs
     * ========================================================= */

    /** Liste de tous les comptes administrateurs + recherche d'un client
     *  existant à promouvoir (via ?promote=email|téléphone|NINU) */
    public function administrateurs(): void
    {
        require_permission('administrateurs');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search = trim((string) ($_GET['q'] ?? ''));
        $admins = User::allAdmins($search !== '' ? $search : null);
        $permissionModules = admin_permission_modules();

        $promoteQuery  = trim((string) ($_GET['promote'] ?? ''));
        $promoteResult = null;
        $promoteError  = null;

        if ($promoteQuery !== '') {
            $found = User::findByEmail($promoteQuery)
                ?? User::findByNinu($promoteQuery)
                ?? User::findByPhone($promoteQuery);

            if (!$found) {
                $promoteError = 'Aucun compte ne correspond à « ' . $promoteQuery . ' ».';
            } elseif (User::isAdminRow($found)) {
                $promoteError = $found['name'] . ' est déjà administrateur.';
            } else {
                $promoteResult = $found;
            }
        }

        $success = $_GET['success'] ?? null;
        $error   = $_GET['error'] ?? null;

        require VIEWS_PATH . '/admin/administrateurs.php';
    }

    /** Promeut un client existant (trouvé via la recherche ci-dessus) au rang d'administrateur */
    public function administrateurPromouvoir(): void
    {
        require_permission('administrateurs');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $target = User::find($id);
                User::promote($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'promotion', 'user', $id, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/administrateurs?success=promu'));
        exit;
    }

    /** Rétrograde un administrateur au rang de client (jamais son propre compte) */
    public function administrateurRetrograder(): void
    {
        require_permission('administrateurs');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === current_user_id()) {
                header('Location: ' . url('/admin/administrateurs?error=self'));
                exit;
            }
            if ($id) {
                $target = User::find($id);
                User::demote($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'retrogradation', 'user', $id, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/administrateurs?success=retrograde'));
        exit;
    }

    /** Supprime définitivement un compte administrateur (jamais son propre compte) */
    public function administrateurSupprimer(): void
    {
        require_permission('administrateurs');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === current_user_id()) {
                header('Location: ' . url('/admin/administrateurs?error=self'));
                exit;
            }
            if ($id) {
                $target = User::find($id);
                User::delete($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_compte', 'user', null, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/administrateurs?success=supprime'));
        exit;
    }

    /** Formulaire de création OU de modification d'un administrateur (GET affiche, POST enregistre) */
    public function administrateurForm(): void
    {
        require_permission('administrateurs');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id     = (int) ($_GET['id'] ?? 0);
        $isEdit = $id > 0;
        $admin  = null;
        $isSelf = $isEdit && $id === (int) current_user_id();

        if ($isEdit) {
            $admin = User::find($id);
            if (!$admin || !User::isAdminRow($admin)) {
                http_response_code(404);
                echo '<h1>404</h1><p>Administrateur introuvable.</p><p><a href="' . url('/admin/administrateurs') . '">Retour au dossier des administrateurs</a></p>';
                return;
            }
        }

        // Seul un administrateur à accès complet (super administrateur) peut définir
        // les permissions d'un autre compte, et jamais les siennes propres (pour éviter
        // de se retrouver bloqué hors de l'espace administrateurs par erreur).
        $canEditPermissions = current_admin_permissions() === null && !$isSelf;
        $permissionModules  = admin_permission_modules();
        $currentPermissions = $isEdit ? User::permissionsArray($admin ?? []) : null;

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'             => trim((string) ($_POST['name'] ?? '')),
                'ninu'             => trim((string) ($_POST['ninu'] ?? '')),
                'sexe'             => (string) ($_POST['sexe'] ?? ''),
                'age'              => (int) ($_POST['age'] ?? 0),
                'ville'            => trim((string) ($_POST['ville'] ?? '')),
                'pays'             => trim((string) ($_POST['pays'] ?? 'Haïti')),
                'phone'            => trim((string) ($_POST['phone'] ?? '')),
                'email'            => trim((string) ($_POST['email'] ?? '')),
                'password'         => (string) ($_POST['password'] ?? ''),
                'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
            ];

            // Permissions soumises : seul un compte à accès complet peut les modifier
            // (jamais les siennes propres). Sinon, on ne touche pas à ce qui existe déjà
            // (édition) ou on applique un accès restreint par défaut (création par un
            // administrateur restreint disposant exceptionnellement du droit de créer
            // des comptes administrateurs), pour ne jamais accorder plus que ce que le
            // formulaire autorise explicitement.
            if ($canEditPermissions) {
                if (!empty($_POST['full_access'])) {
                    $submittedPermissions = null;
                } else {
                    $submittedPermissions = array_values(array_intersect(
                        array_map('strval', $_POST['permissions'] ?? []),
                        array_keys($permissionModules)
                    ));
                }
            } else {
                $submittedPermissions = $isEdit ? $currentPermissions : [];
            }

            $errors = $this->validateStaffData($data, $isEdit);

            if (empty($errors)) {
                // Normalise avant vérification de doublon / enregistrement pour un format cohérent en base.
                $data['ninu']  = normalize_ninu($data['ninu']);
                $data['phone'] = normalize_phone($data['phone']);
                $conflict = User::existsByEmailPhoneOrNinu($data['email'], $data['phone'], $data['ninu'], $isEdit ? $id : null);
                if ($conflict) {
                    $errors[] = $conflict;
                }
            }

            // Traitement de la photo de profil envoyée (upload facultatif)
            $upload = User::processPhotoUpload($_FILES['photo'] ?? []);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            }
            $removePhoto = $isEdit && !empty($_POST['remove_photo']) && !$upload['path'];

            if (empty($errors)) {
                $oldPhotoPath = $admin['photo_path'] ?? null;

                if ($upload['path']) {
                    $data['photo_path'] = $upload['path'];
                } elseif ($removePhoto) {
                    $data['photo_path'] = null;
                }

                if ($isEdit) {
                    User::updateProfile($id, $data);
                    if ($canEditPermissions) {
                        User::setPermissions($id, $submittedPermissions);
                    }
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_admin', 'user', $id, $data['name']);

                    if (array_key_exists('photo_path', $data) && $oldPhotoPath && $oldPhotoPath !== $data['photo_path']) {
                        User::deletePhotoFile($oldPhotoPath);
                    }

                    header('Location: ' . url('/admin/administrateurs?success=modifie'));
                } else {
                    $newId = User::create($data);
                    User::promote($newId);
                    User::setPermissions($newId, $submittedPermissions);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_admin', 'user', $newId, $data['name']);
                    header('Location: ' . url('/admin/administrateurs?success=cree'));
                }
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $admin = array_merge($admin ?? [], $data, ['id' => $id]);
            $currentPermissions = $submittedPermissions;
        }

        require VIEWS_PATH . '/admin/administrateur_form.php';
    }

    /** Journal d'activité : historique des actions administratives (table admin_activity_log) */
    public function journal(): void
    {
        require_permission('journal');
        $currentUser = User::toPublic(User::find(current_user_id()));
        $logs = AdminLog::recent(200);
        require VIEWS_PATH . '/admin/journal.php';
    }

    /** Règles de validation communes à la création et à la modification d'un compte du personnel (admin ou employé) */
    private function validateStaffData(array $data, bool $isEdit): array
    {
        $errors = [];

        foreach (['name', 'ninu', 'sexe', 'age', 'ville', 'pays', 'phone', 'email'] as $field) {
            if ($data[$field] === '' || $data[$field] === null) {
                $errors[] = 'Veuillez remplir tous les champs obligatoires.';
                break;
            }
        }
        if ((int) $data['age'] < 18) {
            $errors[] = "Le membre du personnel doit avoir au moins 18 ans.";
        }
        if (!in_array($data['sexe'], ['Homme', 'Femme', 'Autre'], true)) {
            $errors[] = 'Sexe invalide.';
        }
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if ($data['ninu'] !== '' && ($ninuError = validate_ninu($data['ninu']))) {
            $errors[] = $ninuError;
        }
        if ($data['phone'] !== '' && ($phoneError = validate_phone($data['phone']))) {
            $errors[] = $phoneError;
        }
        // Mot de passe obligatoire à la création ; optionnel en modification (vide = inchangé)
        if (!$isEdit || $data['password'] !== '') {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            if ($data['password'] !== $data['password_confirm']) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
        }

        return $errors;
    }

    /* =========================================================
     * Dossier de gestion des employés : /admin/employes
     * Même principe que le dossier des administrateurs ci-dessus, à ceci
     * près qu'un employé n'a JAMAIS un accès complet : ses permissions
     * sont toujours une liste explicite de modules (jamais NULL), et ne
     * peuvent jamais inclure la gestion des administrateurs ni celle des
     * employés eux-mêmes (voir staff_restricted_modules() dans config.php).
     * Réservé aux comptes role='admin' disposant de la permission 'employes'
     * (require_permission autorise un employé à passer ce gate uniquement
     * s'il est lui-même admin — voir staff_restricted_modules()).
     * ========================================================= */

    /** Liste de tous les comptes employés + recherche d'un client existant à
     *  promouvoir (via ?promote=email|téléphone|NINU) */
    public function employes(): void
    {
        require_permission('employes');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $search    = trim((string) ($_GET['q'] ?? ''));
        $employees = User::allEmployees($search !== '' ? $search : null);
        $permissionModules = array_diff_key(admin_permission_modules(), array_flip(staff_restricted_modules()));

        $promoteQuery  = trim((string) ($_GET['promote'] ?? ''));
        $promoteResult = null;
        $promoteError  = null;

        if ($promoteQuery !== '') {
            $found = User::findByEmail($promoteQuery)
                ?? User::findByNinu($promoteQuery)
                ?? User::findByPhone($promoteQuery);

            if (!$found) {
                $promoteError = 'Aucun compte ne correspond à « ' . $promoteQuery . ' ».';
            } elseif (User::isAdminRow($found)) {
                $promoteError = $found['name'] . ' est déjà administrateur.';
            } elseif (User::isEmployeeRow($found)) {
                $promoteError = $found['name'] . ' est déjà employé.';
            } else {
                $promoteResult = $found;
            }
        }

        $success = $_GET['success'] ?? null;
        $error   = $_GET['error'] ?? null;

        require VIEWS_PATH . '/admin/employes.php';
    }

    /** Promeut un client existant (trouvé via la recherche ci-dessus) au rang d'employé */
    public function employePromouvoir(): void
    {
        require_permission('employes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                $target = User::find($id);
                User::promoteEmployee($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'promotion_employe', 'user', $id, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/employes?success=promu'));
        exit;
    }

    /** Rétrograde un employé au rang de client (jamais son propre compte) */
    public function employeRetrograder(): void
    {
        require_permission('employes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === current_user_id()) {
                header('Location: ' . url('/admin/employes?error=self'));
                exit;
            }
            if ($id) {
                $target = User::find($id);
                User::demote($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'retrogradation_employe', 'user', $id, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/employes?success=retrograde'));
        exit;
    }

    /** Supprime définitivement un compte employé (jamais son propre compte) */
    public function employeSupprimer(): void
    {
        require_permission('employes');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === current_user_id()) {
                header('Location: ' . url('/admin/employes?error=self'));
                exit;
            }
            if ($id) {
                $target = User::find($id);
                User::delete($id);
                if ($target) {
                    $currentUser = User::toPublic(User::find(current_user_id()));
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'suppression_employe', 'user', null, $target['name']);
                }
            }
        }
        header('Location: ' . url('/admin/employes?success=supprime'));
        exit;
    }

    /** Formulaire de création OU de modification d'un employé (GET affiche, POST enregistre) */
    public function employeForm(): void
    {
        require_permission('employes');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $id     = (int) ($_GET['id'] ?? 0);
        $isEdit = $id > 0;
        $employee = null;

        if ($isEdit) {
            $employee = User::find($id);
            if (!$employee || !User::isEmployeeRow($employee)) {
                http_response_code(404);
                echo '<h1>404</h1><p>Employé introuvable.</p><p><a href="' . url('/admin/employes') . '">Retour au dossier des employés</a></p>';
                return;
            }
        }

        // Modules disponibles pour un employé : jamais la gestion des
        // administrateurs ni celle des employés eux-mêmes.
        $permissionModules  = array_diff_key(admin_permission_modules(), array_flip(staff_restricted_modules()));
        $currentPermissions = $isEdit ? User::sanitizeEmployeePermissions(User::permissionsArray($employee ?? [])) : [];

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'             => trim((string) ($_POST['name'] ?? '')),
                'ninu'             => trim((string) ($_POST['ninu'] ?? '')),
                'sexe'             => (string) ($_POST['sexe'] ?? ''),
                'age'              => (int) ($_POST['age'] ?? 0),
                'ville'            => trim((string) ($_POST['ville'] ?? '')),
                'pays'             => trim((string) ($_POST['pays'] ?? 'Haïti')),
                'phone'            => trim((string) ($_POST['phone'] ?? '')),
                'email'            => trim((string) ($_POST['email'] ?? '')),
                'password'         => (string) ($_POST['password'] ?? ''),
                'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
            ];

            // Toujours une liste explicite, filtrée aux seuls modules autorisés
            // à un employé — jamais d'accès complet (aucune case "full_access" ici).
            $submittedPermissions = User::sanitizeEmployeePermissions(array_values(array_intersect(
                array_map('strval', $_POST['permissions'] ?? []),
                array_keys($permissionModules)
            )));

            $errors = $this->validateStaffData($data, $isEdit);

            if (empty($errors)) {
                // Normalise avant vérification de doublon / enregistrement pour un format cohérent en base.
                $data['ninu']  = normalize_ninu($data['ninu']);
                $data['phone'] = normalize_phone($data['phone']);
                $conflict = User::existsByEmailPhoneOrNinu($data['email'], $data['phone'], $data['ninu'], $isEdit ? $id : null);
                if ($conflict) {
                    $errors[] = $conflict;
                }
            }

            // Traitement de la photo de profil envoyée (upload facultatif)
            $upload = User::processPhotoUpload($_FILES['photo'] ?? []);
            if ($upload['error']) {
                $errors[] = $upload['error'];
            }
            $removePhoto = $isEdit && !empty($_POST['remove_photo']) && !$upload['path'];

            if (empty($errors)) {
                $oldPhotoPath = $employee['photo_path'] ?? null;

                if ($upload['path']) {
                    $data['photo_path'] = $upload['path'];
                } elseif ($removePhoto) {
                    $data['photo_path'] = null;
                }

                if ($isEdit) {
                    User::updateProfile($id, $data);
                    User::setPermissions($id, $submittedPermissions);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'modification_employe', 'user', $id, $data['name']);

                    if (array_key_exists('photo_path', $data) && $oldPhotoPath && $oldPhotoPath !== $data['photo_path']) {
                        User::deletePhotoFile($oldPhotoPath);
                    }

                    header('Location: ' . url('/admin/employes?success=modifie'));
                } else {
                    $newId = User::create($data);
                    User::promoteEmployee($newId);
                    User::setPermissions($newId, $submittedPermissions);
                    AdminLog::record($currentUser['id'], $currentUser['name'], 'creation_employe', 'user', $newId, $data['name']);
                    header('Location: ' . url('/admin/employes?success=cree'));
                }
                exit;
            }

            // Réaffiche le formulaire avec les valeurs soumises (pas encore enregistrées)
            $employee = array_merge($employee ?? [], $data, ['id' => $id]);
            $currentPermissions = $submittedPermissions;
        }

        require VIEWS_PATH . '/admin/employe_form.php';
    }

    /* =========================================================
     * Dossier des rapports (export Excel / PDF) : /admin/rapports
     * ========================================================= */

    /** Page du dossier des rapports : une carte par type de rapport disponible */
    public function rapports(): void
    {
        require_permission('rapports');
        $currentUser = User::toPublic(User::find(current_user_id()));

        $rapportTypes = $this->rapportTypes();

        require VIEWS_PATH . '/admin/rapports.php';
    }

    /** Télécharge le rapport demandé (?type=...) au format Excel (.xlsx) */
    public function rapportExcel(): void
    {
        require_permission('rapports');
        $data = $this->rapportDonnees((string) ($_GET['type'] ?? ''));
        if ($data === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>Type de rapport inconnu.</p><p><a href="' . url('/admin/rapports') . '">Retour au dossier des rapports</a></p>';
            return;
        }

        $filename = $data['slug'] . '-' . date('Y-m-d') . '.xlsx';
        $subtitle = 'Généré le ' . date('d/m/Y à H:i') . ' — ' . count($data['excelRows']) . ' ligne(s)';
        XlsxWriter::download(
            $filename,
            $data['title'],
            $data['headers'],
            $data['excelRows'],
            $subtitle,
            ROOT_PATH . '/public/assets/images/logo-gourde-numerique.png'
        );
    }

    /** Télécharge le rapport demandé (?type=...) au format PDF */
    public function rapportPdf(): void
    {
        require_permission('rapports');
        $data = $this->rapportDonnees((string) ($_GET['type'] ?? ''));
        if ($data === null) {
            http_response_code(404);
            echo '<h1>404</h1><p>Type de rapport inconnu.</p><p><a href="' . url('/admin/rapports') . '">Retour au dossier des rapports</a></p>';
            return;
        }

        $subtitle = 'Généré le ' . date('d/m/Y à H:i') . ' — ' . count($data['pdfRows']) . ' ligne(s)';
        $pdf = new SimplePdf($data['title'], $subtitle, $data['headers'], $data['colWidths'], 'landscape');
        $pdf->setLogo(ROOT_PATH . '/public/assets/images/logo-gourde-numerique.png');
        $pdf->addRows($data['pdfRows']);
        $pdf->output($data['slug'] . '-' . date('Y-m-d') . '.pdf');
    }

    /** Liste des types de rapports disponibles, pour la page /admin/rapports */
    private function rapportTypes(): array
    {
        return [
            'clients'         => ['label' => 'Dossier des clients',            'icon' => 'bi-people-fill',              'desc' => 'Comptes clients : identité, ville, contact et solde courant.'],
            'historique'      => ['label' => 'Historique des opérations',      'icon' => 'bi-clock-history',            'desc' => 'Toutes les opérations enregistrées (envois, paiements, dépôts, épargne).'],
            'billets'         => ['label' => 'Gestion des billets',            'icon' => 'bi-wallet2',                  'desc' => 'Coupures actives et inactives, avec stock et valeur totale.'],
            'institutions'    => ['label' => 'Institutions financières',       'icon' => 'bi-bank',                     'desc' => 'Banques, coopératives et EMI partenaires.'],
            'fournisseurs'    => ['label' => 'Fournisseurs',                   'icon' => 'bi-truck',                    'desc' => 'Fournisseurs de billets, matériel et services.'],
            'administrateurs' => ['label' => 'Dossier des administrateurs',    'icon' => 'bi-shield-lock-fill',         'desc' => 'Comptes administrateurs et leurs accès.'],
        ];
    }

    /**
     * Prépare les données d'un rapport pour l'export : titre, en-têtes de
     * colonnes, largeurs de colonnes (pour la mise en page PDF), lignes pour
     * Excel (valeurs numériques natives quand pertinent) et lignes pour PDF
     * (toutes les valeurs déjà formatées en texte). Retourne null si $type
     * ne correspond à aucun rapport connu.
     */
    private function rapportDonnees(string $type): ?array
    {
        switch ($type) {
            case 'clients':
                $clients = User::allClients();
                $headers = ['Nom', 'NINU', 'Ville', 'Pays', 'Téléphone', 'Email', 'Solde (HTG)', 'Inscrit le'];
                $colWidths = [110, 85, 70, 60, 80, 150, 75, 75];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($clients as $c) {
                    $inscrit = date('d/m/Y', strtotime($c['created_at']));
                    $excelRows[] = [$c['name'], $c['ninu'], $c['ville'], $c['pays'], $c['phone'], $c['email'], (float) $c['balance'], $inscrit];
                    $pdfRows[]   = [$c['name'], $c['ninu'], $c['ville'], $c['pays'], $c['phone'], $c['email'], money($c['balance']), $inscrit];
                }
                return [
                    'slug' => 'clients', 'title' => 'Dossier des clients',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            case 'historique':
                $typeLabels = [
                    'envoi'            => 'Envoi entre clients',
                    'reception'        => 'Réception',
                    'paiement'         => 'Paiement',
                    'depot'            => 'Billet admin',
                    'epargne_depot'    => 'Dépôt épargne',
                    'epargne_retrait'  => 'Retrait épargne',
                    'moncash_depot'    => 'Dépôt MonCash',
                    'retrait_bancaire' => 'Retrait bancaire',
                    'retrait_especes'  => 'Retrait en espèces',
                ];
                $operations = Transaction::allHistorique(5000);
                $headers = ['Date', 'Référence', 'Expéditeur', 'Destinataire', 'Type', 'Sens', 'Montant (HTG)', 'Statut'];
                $colWidths = [78, 82, 95, 95, 95, 55, 75, 55];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($operations as $op) {
                    $sortant = in_array($op['type'], ['paiement', 'epargne_depot', 'retrait_bancaire', 'retrait_especes'], true) || ($op['type'] === 'envoi' && !empty($op['sender_id']));
                    $date       = date('d/m/Y H:i', strtotime($op['created_at']));
                    $expediteur = !empty($op['sender_id']) ? ($op['sender_name'] ?? '—') : 'Administration';
                    $destinataire = !empty($op['receiver_id']) ? ($op['receiver_name'] ?? '—') : '—';
                    $typeLabel  = $typeLabels[$op['type']] ?? ucfirst($op['type']);
                    $sens       = $sortant ? 'Sortant' : 'Entrant';
                    $statut     = ucfirst($op['status']);
                    $excelRows[] = [$date, $op['reference'], $expediteur, $destinataire, $typeLabel, $sens, (float) $op['amount'], $statut];
                    $pdfRows[]   = [$date, $op['reference'], $expediteur, $destinataire, $typeLabel, $sens, ($sortant ? '-' : '+') . money($op['amount']), $statut];
                }
                return [
                    'slug' => 'historique', 'title' => 'Historique des opérations',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            case 'billets':
                $categories = [];
                foreach (BankNoteCategory::all() as $cat) {
                    $categories[(int) $cat['id']] = $cat['name'];
                }
                $billets = BankNote::all();
                $headers = ['Coupure (HTG)', 'Type', 'Catégorie', 'Quantité (paquet)', 'Stock', 'Valeur totale (HTG)', 'Ordre', 'Statut'];
                $colWidths = [80, 80, 110, 90, 60, 100, 55, 70];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($billets as $b) {
                    $typeLabel = !empty($b['is_bundle']) ? 'Paquet de billets' : 'Billet simple';
                    $catLabel  = $categories[(int) ($b['category_id'] ?? 0)] ?? 'Sans catégorie';
                    $qty       = !empty($b['is_bundle']) ? (int) $b['bundle_qty'] : 0;
                    $stock     = (int) $b['stock_quantity'];
                    $total     = BankNote::totalValue($b);
                    $statut    = !empty($b['active']) ? 'Actif' : 'Inactif';
                    $excelRows[] = [(float) $b['unit_value'], $typeLabel, $catLabel, $qty, $stock, (float) $total, (int) $b['sort_order'], $statut];
                    $pdfRows[]   = [money($b['unit_value']), $typeLabel, $catLabel, $qty > 0 ? ('×' . $qty) : '—', (string) $stock, money($total), (string) $b['sort_order'], $statut];
                }
                return [
                    'slug' => 'billets', 'title' => 'Gestion des billets',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            case 'institutions':
                $typeLabels = FinancialInstitution::typeLabels();
                $institutions = FinancialInstitution::all();
                $headers = ['Institution', 'Type', 'N° de licence (BRH)', 'Contact', 'Téléphone', 'Email', 'Ville', 'Statut'];
                $colWidths = [110, 80, 90, 90, 80, 130, 65, 65];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($institutions as $i) {
                    $row = [
                        $i['name'],
                        $typeLabels[$i['type']] ?? ucfirst($i['type']),
                        $i['license_number'] ?? '—',
                        $i['contact_name'] ?? '—',
                        $i['phone'] ?? '—',
                        $i['email'] ?? '—',
                        $i['ville'] ?? '—',
                        $i['status'] === 'actif' ? 'Actif' : 'Suspendu',
                    ];
                    $excelRows[] = $row;
                    $pdfRows[]   = $row;
                }
                return [
                    'slug' => 'institutions', 'title' => 'Institutions financières',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            case 'fournisseurs':
                $categoryLabels = Supplier::categoryLabels();
                $fournisseurs = Supplier::all();
                $headers = ['Fournisseur', 'Catégorie', 'NIF / Matricule', 'Contact', 'Téléphone', 'Email', 'Ville', 'Statut'];
                $colWidths = [110, 100, 90, 90, 80, 120, 65, 65];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($fournisseurs as $f) {
                    $row = [
                        $f['name'],
                        $categoryLabels[$f['category']] ?? ucfirst($f['category']),
                        $f['tax_id'] ?? '—',
                        $f['contact_name'] ?? '—',
                        $f['phone'] ?? '—',
                        $f['email'] ?? '—',
                        $f['ville'] ?? '—',
                        $f['status'] === 'actif' ? 'Actif' : 'Suspendu',
                    ];
                    $excelRows[] = $row;
                    $pdfRows[]   = $row;
                }
                return [
                    'slug' => 'fournisseurs', 'title' => 'Fournisseurs',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            case 'administrateurs':
                $modules = admin_permission_modules();
                $admins  = User::allAdmins();
                $headers = ['Nom', 'NINU', 'Téléphone', 'Email', 'Accès', 'Administrateur depuis'];
                $colWidths = [110, 85, 85, 150, 150, 90];
                $excelRows = [];
                $pdfRows   = [];
                foreach ($admins as $a) {
                    $perms = User::permissionsArray($a);
                    $acces = $perms === null ? 'Accès complet' : (empty($perms) ? 'Aucun module' : implode(', ', array_map(fn ($k) => $modules[$k]['label'] ?? $k, $perms)));
                    $depuis = date('d/m/Y', strtotime($a['created_at']));
                    $row = [$a['name'], $a['ninu'], $a['phone'], $a['email'], $acces, $depuis];
                    $excelRows[] = $row;
                    $pdfRows[]   = $row;
                }
                return [
                    'slug' => 'administrateurs', 'title' => 'Dossier des administrateurs',
                    'headers' => $headers, 'colWidths' => $colWidths,
                    'excelRows' => $excelRows, 'pdfRows' => $pdfRows,
                ];

            default:
                return null;
        }
    }
}
