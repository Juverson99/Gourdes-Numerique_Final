<?php
/**
 * app/controllers/TransactionController.php
 * Pages et actions liées aux opérations : Envoyer, Recevoir, Payer, Scanner, Historique.
 */
class TransactionController
{
    public function envoyer(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        // Le portefeuille du client est décomposé en billets réels dont la
        // somme correspond exactement à son solde — pas juste "tous les
        // billets qui tiennent dedans", mais la vraie composition du solde.
        $billets              = BankNote::breakdownForBalance((float) $currentUser['balance']);
        // "Aucune coupure disponible" doit s'appuyer sur les mêmes coupures
        // que breakdownForBalance() (coupures actives, is_bundle=0 — l'ancienne
        // fonctionnalité "paquet de billets" n'est plus utilisée).
        $aucuneCoupureActive  = empty(array_filter(BankNote::allActive(), static fn (array $n): bool => empty($n['is_bundle'])));
        $soldeNul             = ((float) $currentUser['balance']) <= 0.0001;
        require VIEWS_PATH . '/transactions/envoyer.php';
    }

    public function recevoir(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        require VIEWS_PATH . '/transactions/recevoir.php';
    }

    /* ---------------- Demandes d'argent entre clients (dossier Recevoir) ---------------- */

    /** Crée une demande d'argent vers un autre client (par numéro de téléphone) */
    public function apiDemander(array $input): array
    {
        require_login();
        $amount  = (float) ($input['amount'] ?? 0);
        $phone   = trim((string) ($input['target_phone'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        if ($phone === '') {
            return ['ok' => false, 'error' => 'Entrez le numéro de téléphone du client à qui demander de l\'argent.'];
        }
        $phoneError = validate_phone($phone);
        if ($phoneError) {
            return ['ok' => false, 'error' => $phoneError];
        }
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Entrez le montant à demander.'];
        }

        return MoneyRequest::creer(current_user_id(), normalize_phone($phone), $amount, $message);
    }

    /** Liste (pour l'icône d'alerte, sur toutes les pages) les demandes en attente reçues par le client connecté */
    public function apiMesDemandes(): array
    {
        require_login();
        $demandes = MoneyRequest::enAttentePourCible(current_user_id());
        $items = array_map(static function (array $d): array {
            return [
                'reference'       => $d['reference'],
                'amount'          => (float) $d['amount'],
                'message'         => $d['message'],
                'requester_name'  => $d['requester_name'],
                'requester_phone' => $d['requester_phone'],
                'created_at'      => $d['created_at'],
            ];
        }, $demandes);

        return ['ok' => true, 'count' => count($items), 'demandes' => $items];
    }

    /** Le client sollicité accepte une demande : déclenche un vrai envoi de son solde vers le demandeur */
    public function apiAccepterDemande(array $input): array
    {
        require_login();
        $reference = trim((string) ($input['reference'] ?? ''));
        if ($reference === '') {
            return ['ok' => false, 'error' => 'Référence de demande manquante.'];
        }
        return MoneyRequest::accepter($reference, current_user_id());
    }

    /** Le client sollicité refuse une demande, sans aucun mouvement d'argent */
    public function apiRefuserDemande(array $input): array
    {
        require_login();
        $reference = trim((string) ($input['reference'] ?? ''));
        if ($reference === '') {
            return ['ok' => false, 'error' => 'Référence de demande manquante.'];
        }
        return MoneyRequest::refuser($reference, current_user_id());
    }

    /** Le demandeur annule sa propre demande encore en attente */
    public function apiAnnulerDemande(array $input): array
    {
        require_login();
        $reference = trim((string) ($input['reference'] ?? ''));
        if ($reference === '') {
            return ['ok' => false, 'error' => 'Référence de demande manquante.'];
        }
        return MoneyRequest::annuler($reference, current_user_id());
    }

    public function paiement(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        $categories  = PaymentCategory::allActive();
        require VIEWS_PATH . '/transactions/paiement.php';
    }

    public function scanner(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        require VIEWS_PATH . '/transactions/scanner.php';
    }

    public function historique(): void
    {
        require_login();
        $currentUser = User::toPublic(User::find(current_user_id()));
        $operations  = Transaction::historique(current_user_id());
        require VIEWS_PATH . '/transactions/historique.php';
    }

    /* ---------------------- Actions appelées en AJAX ---------------------- */

    public function apiEnvoyer(array $input): array
    {
        require_login();
        $amount = (float) ($input['amount'] ?? 0);
        $phone  = trim((string) ($input['recipient_phone'] ?? ''));

        if ($phone === '') {
            return ['ok' => false, 'error' => 'Entrez le numéro de téléphone du destinataire.'];
        }

        $phoneError = validate_phone($phone);
        if ($phoneError) {
            return ['ok' => false, 'error' => $phoneError];
        }

        $result = Transaction::envoyer(current_user_id(), normalize_phone($phone), $amount);
        if ($result['ok']) {
            $result['balance'] = User::getBalance(current_user_id());
        }
        return $result;
    }

    public function apiPaiement(array $input): array
    {
        require_login();
        $amount   = (float) ($input['amount'] ?? 0);
        $category = trim((string) ($input['category'] ?? 'Marchand'));
        $reference = trim((string) ($input['reference'] ?? ''));

        $result = Transaction::payer(current_user_id(), $category, $reference, $amount);
        if ($result['ok']) {
            $result['balance'] = User::getBalance(current_user_id());
        }
        return $result;
    }

    /** Suggère automatiquement le prochain code de référence/marchand pour une catégorie donnée */
    public function apiProchaineReference(array $input): array
    {
        require_login();
        $category = trim((string) ($input['category'] ?? ''));
        if ($category === '') {
            return ['ok' => false, 'error' => 'Catégorie manquante.'];
        }
        $reference = Transaction::nextPaymentReference(current_user_id(), $category);
        return ['ok' => true, 'reference' => $reference];
    }

    /** Recherche un compte par numéro de téléphone (utilisé par le scanner / la saisie manuelle) */
    public function apiScannerLookup(array $input): array
    {
        require_login();
        $code = trim((string) ($input['code'] ?? ''));
        if ($code === '') {
            return ['ok' => false, 'error' => 'Entrez un code.'];
        }

        $phoneError = validate_phone($code);
        if ($phoneError) {
            return ['ok' => false, 'error' => 'Aucun compte ne correspond à ce code.'];
        }

        $user = User::findByPhone(normalize_phone($code));
        if (!$user || User::isAdminRow($user)) {
            return ['ok' => false, 'error' => 'Aucun compte ne correspond à ce code.'];
        }
        if ((int) $user['id'] === current_user_id()) {
            return ['ok' => false, 'error' => 'Vous ne pouvez pas scanner votre propre code.'];
        }

        return ['ok' => true, 'recipient' => ['name' => $user['name'], 'phone' => $user['phone']]];
    }
}
