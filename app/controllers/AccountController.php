<?php
/**
 * app/controllers/AccountController.php
 * Gère l'inscription, la connexion, la déconnexion (appelées en AJAX depuis
 * public/api/account.php) ainsi que la page "Plus" (réglages du compte).
 */
class AccountController
{
    /** Page dédiée "/login" — ouvre automatiquement la modale de connexion.
     *  Sert de vraie page (lien partageable, marche sans JS pour la redirection
     *  post-connexion) plutôt que d'exiger le clic sur la modale du header. */
    public function loginPage(): void
    {
        $redirect = safe_redirect_path($_GET['redirect'] ?? '/');
        if (is_logged_in()) {
            header('Location: ' . url($redirect));
            exit;
        }
        require VIEWS_PATH . '/account/login.php';
    }

    /** Page "Plus" : épargne, cartes, sécurité, réglages, support (aperçu du compte) */
    public function plus(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        require VIEWS_PATH . '/account/plus.php';
    }

    /** Dossier client "Épargne" (/epargne) : transfert self-service solde <-> épargne */
    public function epargne(): void
    {
        require_login();
        $userId = current_user_id();
        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $amount    = (float) ($_POST['amount'] ?? 0);
            $direction = (string) ($_POST['direction'] ?? 'depot');
            $result = Transaction::epargneClientAjuster($userId, $amount, $direction);
            if ($result['ok']) {
                $success = ($direction === 'depot' ? 'Dépôt' : 'Retrait') . ' de ' . money($amount)
                    . ' HTG effectué (référence ' . $result['reference'] . ').';
            } else {
                $error = $result['error'];
            }
        }

        $currentUser  = User::toPublic(User::find($userId));
        $mouvements   = array_values(array_filter(
            Transaction::historique($userId, 200),
            fn($t) => str_starts_with($t['type'], 'epargne_')
        ));

        require VIEWS_PATH . '/account/epargne.php';
    }

    /** Dossier client "Cartes virtuelles" (/cartes-virtuelles) : générer / geler / supprimer */
    public function cartes(): void
    {
        require_login();
        $userId = current_user_id();
        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? '');
            if ($action === 'create') {
                if (VirtualCard::countForUser($userId) >= 5) {
                    $error = 'Vous avez atteint la limite de 5 cartes virtuelles actives.';
                } else {
                    $user  = User::find($userId);
                    $label = trim((string) ($_POST['label'] ?? '')) ?: 'Carte virtuelle';
                    VirtualCard::create($userId, $user['name'], $label);
                    $success = 'Votre nouvelle carte virtuelle a été générée.';
                }
            } elseif ($action === 'toggle') {
                VirtualCard::toggleStatus((int) ($_POST['card_id'] ?? 0), $userId);
                $success = 'Statut de la carte mis à jour.';
            } elseif ($action === 'delete') {
                VirtualCard::delete((int) ($_POST['card_id'] ?? 0), $userId);
                $success = 'Carte virtuelle supprimée.';
            }
        }

        $currentUser = User::toPublic(User::find($userId));
        $cartes      = VirtualCard::allForUser($userId);

        require VIEWS_PATH . '/account/cartes.php';
    }

    /** Dossier client "Sécurité" (/securite) : mot de passe + code PIN */
    public function securite(): void
    {
        require_login();
        $userId = current_user_id();
        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'password') {
                $current  = (string) ($_POST['current_password'] ?? '');
                $new      = (string) ($_POST['new_password'] ?? '');
                $confirm  = (string) ($_POST['new_password_confirm'] ?? '');
                if (!User::verifyPassword($userId, $current)) {
                    $error = 'Mot de passe actuel incorrect.';
                } elseif (strlen($new) < 6) {
                    $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                } elseif ($new !== $confirm) {
                    $error = 'Les deux mots de passe ne correspondent pas.';
                } else {
                    User::updatePassword($userId, $new);
                    $success = 'Mot de passe mis à jour avec succès.';
                }
            } elseif ($action === 'pin') {
                $pin     = (string) ($_POST['pin'] ?? '');
                $confirm = (string) ($_POST['pin_confirm'] ?? '');
                if (!preg_match('/^\d{4,6}$/', $pin)) {
                    $error = 'Le code PIN doit contenir entre 4 et 6 chiffres.';
                } elseif ($pin !== $confirm) {
                    $error = 'Les deux codes PIN ne correspondent pas.';
                } else {
                    User::setPin($userId, $pin);
                    $success = 'Code PIN défini avec succès.';
                }
            } elseif ($action === 'pin_remove') {
                User::clearPin($userId);
                $success = 'Code PIN retiré.';
            }
        }

        $currentUser = User::toPublic(User::find($userId));
        require VIEWS_PATH . '/account/securite.php';
    }

    /** Dossier client "Réglages du compte" (/reglages) : informations personnelles */
    public function reglages(): void
    {
        require_login();
        $userId = current_user_id();
        $success = null;
        $error   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'  => trim((string) ($_POST['name'] ?? '')),
                'ninu'  => trim((string) ($_POST['ninu'] ?? '')),
                'sexe'  => (string) ($_POST['sexe'] ?? ''),
                'age'   => (int) ($_POST['age'] ?? 0),
                'ville' => trim((string) ($_POST['ville'] ?? '')),
                'pays'  => trim((string) ($_POST['pays'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
            ];

            $ninuError  = validate_ninu($data['ninu']);
            $phoneError = validate_phone($data['phone']);

            if (in_array('', [$data['name'], $data['ninu'], $data['sexe'], $data['ville'], $data['pays'], $data['phone'], $data['email']], true) || $data['age'] < 18) {
                $error = 'Veuillez remplir correctement tous les champs (18 ans minimum).';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } elseif ($ninuError) {
                $error = $ninuError;
            } elseif ($phoneError) {
                $error = $phoneError;
            } else {
                // Normalise avant l'enregistrement pour un format cohérent en base.
                $data['ninu']  = normalize_ninu($data['ninu']);
                $data['phone'] = normalize_phone($data['phone']);
                $conflict = User::existsByEmailPhoneOrNinu($data['email'], $data['phone'], $data['ninu'], $userId);
                if ($conflict) {
                    $error = $conflict;
                } else {
                    if (!empty($_FILES['photo']['name'])) {
                        $upload = User::processPhotoUpload($_FILES['photo']);
                        if ($upload['error']) {
                            $error = $upload['error'];
                        } else {
                            $old = User::find($userId)['photo_path'] ?? null;
                            $data['photo_path'] = $upload['path'];
                            User::deletePhotoFile($old);
                        }
                    }
                    if (!$error) {
                        User::updateProfile($userId, $data);
                        $success = 'Vos informations ont été mises à jour.';
                    }
                }
            }
        }

        $currentUser = User::toPublic(User::find($userId));
        require VIEWS_PATH . '/account/reglages.php';
    }

    /** Dossier client "Convertir" (/convertir) : MonCash -> billet électronique, billet électronique -> compte bancaire */
    public function convertir(): void
    {
        require_login();
        $userId = current_user_id();
        $success = $_GET['success'] ?? null;
        $error   = $_GET['error'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = (string) ($_POST['action'] ?? '');

            if ($action === 'moncash') {
                $amount = (float) ($_POST['amount'] ?? 0);
                $result = Transaction::moncashInitier($userId, $amount);
                if ($result['ok']) {
                    header('Location: ' . $result['redirect_url']);
                    exit;
                }
                $error = $result['error'];
            } elseif ($action === 'banque') {
                $institutionId = (int) ($_POST['institution_id'] ?? 0);
                $accountNumber = (string) ($_POST['account_number'] ?? '');
                $amount        = (float) ($_POST['amount'] ?? 0);
                $result = Transaction::retraitBancaire($userId, $institutionId, $accountNumber, $amount);
                if ($result['ok']) {
                    $success = money($amount) . ' HTG envoyés vers votre compte bancaire (référence ' . $result['reference'] . ').';
                } else {
                    $error = $result['error'];
                }
            } elseif ($action === 'especes') {
                $agenceId = (int) ($_POST['agence_id'] ?? 0);
                $amount   = (float) ($_POST['amount'] ?? 0);
                $result = Transaction::demanderRetraitEspeces($userId, $agenceId, $amount);
                if ($result['ok']) {
                    $success = 'Retrait de ' . money($amount) . ' HTG en billet physique demandé. Présentez le code '
                        . $result['reference'] . ' à l\'agence ' . $result['agence_nom'] . ' (' . $result['agence_ville'] . ') pour récupérer vos espèces.';
                } else {
                    $error = $result['error'];
                }
            }
        }

        $currentUser  = User::toPublic(User::find($userId));
        $banques      = FinancialInstitution::allActiveByType('banque');
        $agences      = BncAgence::allActive();
        $mouvements   = array_values(array_filter(
            Transaction::historique($userId, 200),
            fn($t) => in_array($t['type'], ['moncash_depot', 'retrait_bancaire', 'retrait_especes'], true)
        ));

        require VIEWS_PATH . '/account/convertir.php';
    }

    /**
     * Point de retour appelé par MonCash après le paiement (voir l'URL de
     * retour à déclarer dans le tableau de bord marchand Digicel, et
     * config/moncash.php). Vérifie réellement le paiement auprès de l'API
     * MonCash avant de créditer quoi que ce soit, puis renvoie le client
     * vers /convertir avec un message de résultat.
     */
    public function convertirMoncashRetour(): void
    {
        require_login();
        $userId        = current_user_id();
        $transactionId = (string) ($_GET['transactionId'] ?? '');

        if ($transactionId === '') {
            header('Location: ' . url('/convertir') . '?error=' . urlencode('Retour MonCash invalide (identifiant de transaction manquant).'));
            exit;
        }

        $result = Transaction::moncashConfirmer($userId, $transactionId);
        if ($result['ok']) {
            $msg = ($result['already'] ?? false)
                ? 'Ce paiement MonCash avait déjà été confirmé.'
                : money($result['amount']) . ' HTG convertis depuis MonCash en billet électronique.';
            header('Location: ' . url('/convertir') . '?success=' . urlencode($msg));
        } else {
            header('Location: ' . url('/convertir') . '?error=' . urlencode($result['error']));
        }
        exit;
    }

    /**
     * Dossier client "Convertir" (/convertir) : réconciliation manuelle,
     * PAR LE CLIENT LUI-MÊME, d'un dépôt MonCash resté "en attente" (ex. le
     * client a bien payé sur MonCash mais a fermé son navigateur/son
     * application avant la redirection de retour, coupure réseau pendant le
     * retour, ou session expirée pendant qu'il finalisait le paiement côté
     * MonCash — voir convertirMoncashRetour() et require_login()). Sans
     * cela, un dépôt bloqué dans ce cas ne pouvait être débloqué que par un
     * administrateur (/admin/moncash), parfois bien après le paiement réel.
     * Interroge réellement l'API MonCash (voir Transaction::
     * moncashVerifierManuel()) — jamais de crédit sur simple demande.
     */
    public function convertirMoncashVerifier(): void
    {
        require_login();
        $userId = current_user_id();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reference = (string) ($_POST['reference'] ?? '');
            $result = Transaction::moncashVerifierManuel($reference, $userId);
            if ($result['ok']) {
                $msg = ($result['already'] ?? false)
                    ? 'Ce paiement MonCash avait déjà été confirmé.'
                    : money($result['amount']) . ' HTG convertis depuis MonCash en billet électronique.';
                header('Location: ' . url('/convertir') . '?success=' . urlencode($msg));
            } else {
                header('Location: ' . url('/convertir') . '?error=' . urlencode($result['error']));
            }
            exit;
        }

        header('Location: ' . url('/convertir'));
        exit;
    }

    /** Dossier public/client "Aide & FAQ" (/aide) */
    public function aide(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }
        require VIEWS_PATH . '/account/aide.php';
    }

    /** Inscription — appelé par public/api/account.php.
     *  $photoFile est le tableau $_FILES['photo'] envoyé par le formulaire d'inscription
     *  (facultatif : un client peut créer son compte sans photo de profil). */
    public function signup(array $input, array $photoFile = []): array
    {
        $required = ['name', 'ninu', 'sexe', 'age', 'ville', 'pays', 'phone', 'email', 'password', 'password_confirm'];
        foreach ($required as $field) {
            if (empty($input[$field]) && $input[$field] !== '0') {
                return ['ok' => false, 'error' => 'Veuillez remplir tous les champs.'];
            }
        }
        if ((int) $input['age'] < 18) {
            return ['ok' => false, 'error' => 'Vous devez avoir au moins 18 ans pour ouvrir un compte.'];
        }
        if (strlen($input['password']) < 6) {
            return ['ok' => false, 'error' => 'Le mot de passe doit contenir au moins 6 caractères.'];
        }
        if ($input['password'] !== $input['password_confirm']) {
            return ['ok' => false, 'error' => 'Les mots de passe ne correspondent pas.'];
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Adresse email invalide.'];
        }
        $ninuError = validate_ninu((string) $input['ninu']);
        if ($ninuError) {
            return ['ok' => false, 'error' => $ninuError];
        }
        $phoneError = validate_phone((string) $input['phone']);
        if ($phoneError) {
            return ['ok' => false, 'error' => $phoneError];
        }
        // Normalise avant l'enregistrement pour que le NINU et le téléphone soient
        // stockés dans un format identique quel que soit la façon dont ils ont été saisis.
        $input['ninu']  = normalize_ninu((string) $input['ninu']);
        $input['phone'] = normalize_phone((string) $input['phone']);

        $conflict = User::existsByEmailPhoneOrNinu($input['email'], $input['phone'], $input['ninu']);
        if ($conflict) {
            return ['ok' => false, 'error' => $conflict];
        }

        // Photo de profil facultative envoyée avec le formulaire d'inscription
        $upload = User::processPhotoUpload($photoFile);
        if ($upload['error']) {
            return ['ok' => false, 'error' => $upload['error']];
        }
        $input['photo_path'] = $upload['path'];

        $userId = User::create($input);
        $_SESSION['user_id'] = $userId;
        $_SESSION['is_admin'] = false; // un compte créé via l'inscription publique n'est jamais admin
        $_SESSION['is_employee'] = false; // ni employé

        return ['ok' => true, 'user' => User::toPublic(User::find($userId))];
    }

    /** Connexion — appelé par public/api/account.php */
    public function login(array $input): array
    {
        if (empty($input['email']) || empty($input['password'])) {
            return ['ok' => false, 'error' => 'Veuillez remplir tous les champs.'];
        }

        $email = (string) $input['email'];

        // Protection anti-force-brute : trop d'échecs récents pour cet email
        // depuis cette adresse IP -> on refuse même un mot de passe correct
        // tant que le verrouillage temporaire n'est pas expiré.
        $lockedSeconds = LoginThrottle::secondsRemaining($email);
        if ($lockedSeconds > 0) {
            $minutes = (int) ceil($lockedSeconds / 60);
            return ['ok' => false, 'error' => "Trop de tentatives échouées. Réessayez dans {$minutes} minute" . ($minutes > 1 ? 's' : '') . '.'];
        }

        $user = User::verifyCredentials($email, $input['password']);
        if (!$user) {
            LoginThrottle::recordFailure($email);
            return ['ok' => false, 'error' => 'Identifiants incorrects. Vérifiez et réessayez.'];
        }

        LoginThrottle::clear($email);
        session_regenerate_id(true); // évite la fixation de session après une authentification réussie
        $_SESSION['user_id']     = (int) $user['id'];
        $_SESSION['is_admin']    = User::isAdminRow($user);
        $_SESSION['is_employee'] = User::isEmployeeRow($user);
        return ['ok' => true, 'user' => User::toPublic($user)];
    }

    /** Déconnexion */
    public function logout(): array
    {
        unset($_SESSION['user_id'], $_SESSION['is_admin'], $_SESSION['is_employee']);
        session_regenerate_id(true);
        return ['ok' => true];
    }

    /** Session courante (pour rafraîchir l'UI à chaque chargement de page) */
    public function me(): array
    {
        if (!is_logged_in()) {
            return ['ok' => true, 'user' => null];
        }
        $user = User::find(current_user_id());
        if (!$user) {
            unset($_SESSION['user_id']);
            return ['ok' => true, 'user' => null];
        }
        return ['ok' => true, 'user' => User::toPublic($user)];
    }
}
