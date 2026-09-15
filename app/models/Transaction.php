<?php
/**
 * app/models/Transaction.php
 * Logique des opérations financières : envoi entre utilisateurs, paiement de
 * factures/marchands, et lecture de l'historique. Les transferts de solde
 * sont effectués dans une transaction SQL (COMMIT/ROLLBACK) pour garantir
 * qu'un envoi ne peut jamais créditer un compte sans débiter l'autre.
 */
class Transaction
{
    /**
     * Envoie un montant du compte $senderId vers le compte identifié par $recipientPhone
     * (numéro de téléphone déjà normalisé au format "509 XXXXXXXX", voir normalize_phone()).
     * Retourne un tableau ['ok' => bool, 'error' => ?string, 'reference' => ?string].
     */
    public static function envoyer(int $senderId, string $recipientPhone, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }

        $recipient = User::findByPhone($recipientPhone);
        if (!$recipient || User::isAdminRow($recipient)) {
            return ['ok' => false, 'error' => 'Aucun compte ne correspond à ce numéro de téléphone.'];
        }
        if ((int) $recipient['id'] === $senderId) {
            return ['ok' => false, 'error' => 'Vous ne pouvez pas vous envoyer de l\'argent à vous-même.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Verrouille la ligne de l'expéditeur pour vérifier son solde en toute sécurité
            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$senderId]);
            $senderBalance = (float) $stmt->fetchColumn();

            if ($senderBalance < $amount) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant pour cet envoi.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
                ->execute([$amount, $senderId]);
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
                ->execute([$amount, $recipient['id']]);

            $reference = generate_reference('ENV');
            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, status)
                 VALUES (?, ?, ?, "envoi", ?, "reussi")'
            )->execute([$reference, $senderId, $recipient['id'], $amount]);

            $pdo->commit();
            return ['ok' => true, 'reference' => $reference, 'recipient_name' => $recipient['name']];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant l\'envoi.'];
        }
    }

    /**
     * Paie une facture / un marchand (catégorie + référence) depuis le compte $senderId.
     * Il n'y a pas de destinataire "utilisateur" : le montant est simplement débité.
     */
    public static function payer(int $senderId, string $category, string $reference, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Entrez un montant à payer.'];
        }
        if (trim($reference) === '') {
            return ['ok' => false, 'error' => 'Entrez la référence ou le code marchand.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$senderId]);
            $balance = (float) $stmt->fetchColumn();

            if ($balance < $amount) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant pour ce paiement.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
                ->execute([$amount, $senderId]);

            $ref = generate_reference('PAY');
            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, ?, NULL, "paiement", ?, ?, ?, "reussi")'
            )->execute([$ref, $senderId, $amount, $category, $reference]);

            $pdo->commit();
            return ['ok' => true, 'reference' => $ref];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant le paiement.'];
        }
    }

    /**
     * Suggère automatiquement le prochain code de référence/marchand pour une
     * catégorie de paiement donnée : reprend le préfixe de la dernière
     * référence utilisée par ce client pour cette catégorie et incrémente le
     * numéro de 1 (ex : "EDH-99213" -> "EDH-99214"). Si le client n'a encore
     * jamais payé dans cette catégorie, part d'un préfixe déduit du nom de la
     * catégorie (sigle entre parenthèses s'il existe, sinon initiales) suivi
     * du numéro de départ 1001.
     */
    public static function nextPaymentReference(int $userId, string $category): string
    {
        $stmt = db()->prepare(
            "SELECT note FROM transactions
             WHERE sender_id = ? AND type = 'paiement' AND category = ? AND note IS NOT NULL AND note != ''
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId, $category]);
        $lastNote = $stmt->fetchColumn();

        if ($lastNote && preg_match('/^(.*?)(\d+)$/', trim((string) $lastNote), $m)) {
            $prefix    = $m[1];
            $number    = $m[2];
            $next      = (string) ((int) $number + 1);
            $next      = str_pad($next, strlen($number), '0', STR_PAD_LEFT);
            return $prefix . $next;
        }

        // Pas d'historique pour cette catégorie : on construit un préfixe de départ.
        if (preg_match('/\(([A-Za-zÀ-ÿ0-9]+)\)/', $category, $m)) {
            $prefix = strtoupper($m[1]);
        } else {
            $words  = preg_split('/[^A-Za-zÀ-ÿ0-9]+/u', $category, -1, PREG_SPLIT_NO_EMPTY);
            $letters = array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $words);
            $prefix  = implode('', array_slice($letters, 0, 4)) ?: 'REF';
        }

        return $prefix . '-1001';
    }

    /**
     * Historique des opérations d'un utilisateur (envoyées, reçues, paiements),
     * du plus récent au plus ancien.
     */
    public static function historique(int $userId, int $limit = 100): array
    {
        $stmt = db()->prepare(
            'SELECT t.*,
                    su.name AS sender_name, su.ninu AS sender_ninu, su.phone AS sender_phone,
                    ru.name AS receiver_name, ru.ninu AS receiver_ninu, ru.phone AS receiver_phone
             FROM transactions t
             LEFT JOIN users su ON su.id = t.sender_id
             LEFT JOIN users ru ON ru.id = t.receiver_id
             WHERE t.sender_id = :uid1 OR t.receiver_id = :uid2
             ORDER BY t.created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Ajoute le sens ("entrant"/"sortant") du point de vue de l'utilisateur connecté.
        // Chaque type de transaction a sa propre règle : un "envoi" est entrant/sortant
        // selon que l'utilisateur est destinataire ou expéditeur, mais un dépôt admin,
        // un dépôt MonCash ou un retrait d'épargne sont toujours entrants (argent qui
        // arrive sur le solde), tandis qu'un paiement, un retrait bancaire ou un dépôt
        // vers l'épargne sont toujours sortants (argent qui quitte le solde principal).
        foreach ($rows as &$row) {
            switch ($row['type']) {
                case 'envoi':
                    $row['sens'] = ((int) $row['receiver_id'] === $userId) ? 'entrant' : 'sortant';
                    break;
                case 'depot':
                case 'moncash_depot':
                case 'epargne_retrait':
                    $row['sens'] = 'entrant';
                    break;
                case 'paiement':
                case 'retrait_bancaire':
                case 'retrait_especes':
                case 'epargne_depot':
                default:
                    $row['sens'] = 'sortant';
                    break;
            }
        }
        return $rows;
    }

    /* =========================================================
     * Méthodes réservées à la partie administration (/admin)
     * ========================================================= */

    /**
     * "Dossier recevoir de tous les clients" : toutes les opérations où un client
     * a reçu de l'argent (envois reçus entre clients + billets/dépôts ajoutés par
     * un admin), du plus récent au plus ancien.
     */
    public static function allReceived(int $limit = 200, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       su.name AS sender_name, su.ninu AS sender_ninu,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE t.receiver_id IS NOT NULL AND t.type IN ("envoi", "depot", "reception")';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (ru.name LIKE :s OR ru.ninu LIKE :s OR t.reference LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Dossier envoyer de tous les clients" : tous les envois d'argent effectués
     * par un client vers un autre client, du plus récent au plus ancien.
     */
    public static function allSent(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       su.name AS sender_name, su.ninu AS sender_ninu,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE t.type = "envoi" AND t.sender_id IS NOT NULL';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (su.name LIKE :s OR su.ninu LIKE :s OR ru.name LIKE :s OR ru.ninu LIKE :s OR t.reference LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Dossier paiement de tous les clients" : tous les paiements de factures /
     * marchands effectués par les clients, du plus récent au plus ancien.
     */
    public static function allPayments(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       su.name AS sender_name, su.ninu AS sender_ninu,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE t.type = "paiement"';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (su.name LIKE :s OR su.ninu LIKE :s OR t.reference LIKE :s OR t.category LIKE :s OR t.note LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Dossier historique" : journal global de TOUTES les opérations, tous
     * clients et tous types confondus (envoi, réception, paiement, dépôt,
     * épargne), du plus récent au plus ancien — vue d'ensemble pour l'admin.
     */
    public static function allHistorique(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       su.name AS sender_name, su.ninu AS sender_ninu,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE 1=1';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (su.name LIKE :s OR su.ninu LIKE :s OR ru.name LIKE :s OR ru.ninu LIKE :s OR t.reference LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Dossier épargne" : tous les mouvements d'épargne (dépôts et retraits),
     * du plus récent au plus ancien.
     */
    public static function allEpargneMouvements(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE t.type IN ("epargne_depot", "epargne_retrait")';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (ru.name LIKE :s OR ru.ninu LIKE :s OR t.reference LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * "Dossier transactions MonCash" : tous les dépôts initiés par les clients
     * depuis MonCash (dossier client "Convertir"), tous statuts confondus
     * (réussi, en attente de confirmation, échec), du plus récent au plus
     * ancien. Voir Transaction::moncashInitier() / moncashConfirmer().
     */
    public static function allMoncash(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       ru.name AS receiver_name, ru.ninu AS receiver_ninu
                FROM transactions t
                LEFT JOIN users ru ON ru.id = t.receiver_id
                WHERE t.type = "moncash_depot"';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (ru.name LIKE :s OR ru.ninu LIKE :s OR t.reference LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Statistiques rapides du dossier MonCash (cartes en haut de page) :
     * montant total confirmé, nombre en attente de confirmation MonCash,
     * nombre en échec (identifiants MonCash manquants, API injoignable...).
     */
    public static function moncashStats(): array
    {
        $row = db()->query(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'reussi'    THEN amount ELSE 0 END), 0) AS total_reussi,
                COALESCE(SUM(CASE WHEN status = 'reussi'    THEN 1 ELSE 0 END), 0)      AS nb_reussi,
                COALESCE(SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END), 0)     AS nb_en_attente,
                COALESCE(SUM(CASE WHEN status = 'echec'     THEN 1 ELSE 0 END), 0)      AS nb_echec
             FROM transactions WHERE type = 'moncash_depot'"
        )->fetch();

        return [
            'total_reussi' => (float) ($row['total_reussi'] ?? 0),
            'nb_reussi'    => (int) ($row['nb_reussi'] ?? 0),
            'nb_en_attente' => (int) ($row['nb_en_attente'] ?? 0),
            'nb_echec'     => (int) ($row['nb_echec'] ?? 0),
        ];
    }

    /**
     * Volume des opérations réussies des N derniers jours (pour le graphique
     * "Activité des 7 derniers jours" du tableau de bord admin). Retourne un
     * tableau ordonné du plus ancien au plus récent, un jour manquant valant 0.
     */
    public static function volumeParJour(int $days = 7): array
    {
        $stmt = db()->prepare(
            'SELECT DATE(created_at) AS jour, SUM(amount) AS total
             FROM transactions
             WHERE status = "reussi" AND created_at >= DATE_SUB(CURDATE(), INTERVAL :d DAY)
             GROUP BY DATE(created_at)'
        );
        $stmt->bindValue(':d', $days - 1, PDO::PARAM_INT);
        $stmt->execute();
        $parJour = [];
        foreach ($stmt->fetchAll() as $row) {
            $parJour[$row['jour']] = (float) $row['total'];
        }

        $jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[] = [
                'label' => $jours[(int) date('w', strtotime($date))],
                'total' => $parJour[$date] ?? 0.0,
            ];
        }
        return $result;
    }

    /**
     * Répartition du nombre d'opérations réussies par type, regroupée en
     * catégories lisibles pour le graphique en anneau du tableau de bord.
     */
    public static function repartitionParType(): array
    {
        $stmt = db()->query(
            'SELECT type, COUNT(*) AS nb
             FROM transactions
             WHERE status = "reussi"
             GROUP BY type'
        );
        $counts = ['envoi' => 0, 'paiement' => 0, 'depot' => 0, 'epargne_depot' => 0, 'epargne_retrait' => 0, 'reception' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['type']] = (int) $row['nb'];
        }

        return [
            'Envois'    => $counts['envoi'] + $counts['reception'],
            'Paiements' => $counts['paiement'],
            'Billets'   => $counts['depot'],
            'Épargne'   => $counts['epargne_depot'] + $counts['epargne_retrait'],
        ];
    }

    /**
     * Volume total des opérations réussies par mois, sur les N derniers mois
     * (pour le graphique "Volume mensuel des opérations" du dossier Statistiques).
     * Retourne un tableau ordonné du plus ancien au plus récent, un mois sans
     * opération valant 0.
     */
    public static function volumeParMois(int $months = 12): array
    {
        $stmt = db()->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, SUM(amount) AS total
             FROM transactions
             WHERE status = 'reussi' AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL :m MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
        );
        $stmt->bindValue(':m', $months - 1, PDO::PARAM_INT);
        $stmt->execute();
        $parMois = [];
        foreach ($stmt->fetchAll() as $row) {
            $parMois[$row['mois']] = (float) $row['total'];
        }

        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $timestamp = strtotime("first day of -{$i} months");
            $key = date('Y-m', $timestamp);
            $result[] = [
                'label' => $moisLabels[(int) date('n', $timestamp) - 1] . ' ' . date('y', $timestamp),
                'total' => $parMois[$key] ?? 0.0,
            ];
        }
        return $result;
    }

    /**
     * Répartition des paiements réussis par catégorie (Électricité, Eau, DGI…),
     * triée par montant total décroissant — pour le graphique "Répartition des
     * paiements par catégorie" du dossier Statistiques. $limit regroupe les
     * catégories les moins importantes sous "Autres" au-delà de ce nombre.
     */
    public static function repartitionPaiementsParCategorie(int $limit = 6): array
    {
        $stmt = db()->query(
            "SELECT COALESCE(NULLIF(TRIM(category), ''), 'Autre') AS categorie, SUM(amount) AS total
             FROM transactions
             WHERE type = 'paiement' AND status = 'reussi'
             GROUP BY categorie
             ORDER BY total DESC"
        );
        $rows = $stmt->fetchAll();

        $result = [];
        $autres = 0.0;
        foreach ($rows as $i => $row) {
            if ($i < $limit) {
                $result[$row['categorie']] = (float) $row['total'];
            } else {
                $autres += (float) $row['total'];
            }
        }
        if ($autres > 0) {
            $result['Autres'] = $autres;
        }
        return $result;
    }

    /**
     * Répartition du nombre total d'opérations par statut (réussi, échec,
     * en attente) — pour le graphique "Statut des transactions" du dossier
     * Statistiques.
     */
    public static function repartitionParStatut(): array
    {
        $stmt = db()->query(
            "SELECT status, COUNT(*) AS nb
             FROM transactions
             GROUP BY status"
        );
        $counts = ['reussi' => 0, 'echec' => 0, 'en_attente' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['nb'];
        }

        return [
            'Réussies'    => $counts['reussi'],
            'Échouées'    => $counts['echec'],
            'En attente'  => $counts['en_attente'],
        ];
    }

    /**
     * Dossier client "Convertir" (/convertir), volet MonCash — étape 1 :
     * crée une transaction "en_attente" côté plateforme, puis ouvre un
     * paiement réel auprès de l'API MonCash (Digicel). Renvoie l'URL de la
     * passerelle MonCash vers laquelle rediriger le navigateur du client ;
     * le solde n'est crédité qu'après confirmation (voir moncashConfirmer).
     */
    public static function moncashInitier(int $userId, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }

        $reference = generate_reference('MCH');
        db()->prepare(
            'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
             VALUES (?, NULL, ?, "moncash_depot", ?, "MonCash", "En attente de confirmation MonCash", "en_attente")'
        )->execute([$reference, $userId, $amount]);

        $payment = MonCash::createPayment($reference, $amount);
        if (!$payment['ok']) {
            // Important : on met aussi à jour la note, pas seulement le statut.
            // Sinon la transaction affiche "Échec" avec un texte qui dit encore
            // "En attente de confirmation MonCash" — incohérence visible aussi
            // bien côté client (/convertir) que côté admin (/admin/moncash),
            // puisque les deux pages affichent ce même champ `note`.
            $note = 'Échec à l\'ouverture du paiement MonCash : ' . $payment['error'];
            db()->prepare('UPDATE transactions SET status = "echec", note = ? WHERE reference = ?')
                ->execute([$note, $reference]);
            return ['ok' => false, 'error' => $payment['error']];
        }

        return ['ok' => true, 'reference' => $reference, 'redirect_url' => $payment['redirect_url']];
    }

    /**
     * Dossier client "Convertir" (/convertir), volet MonCash — étape 2 :
     * appelée par le contrôleur au retour du client depuis la passerelle
     * MonCash (voir /convertir/moncash/retour et config/moncash.php).
     * Vérifie le paiement réel auprès de l'API MonCash avant de créditer le
     * solde : le montant n'est jamais crédité sur simple redirection.
     */
    public static function moncashConfirmer(int $userId, string $moncashTransactionId): array
    {
        $result = MonCash::retrieveTransaction($moncashTransactionId);
        if (!$result['ok']) {
            return $result;
        }
        $reference = $result['reference'];
        if (!$reference) {
            return ['ok' => false, 'error' => "Référence de commande introuvable dans la réponse MonCash."];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM transactions WHERE reference = ? AND receiver_id = ? AND type = "moncash_depot" LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$reference, $userId]);
            $tx = $stmt->fetch();

            if (!$tx) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Transaction introuvable pour ce compte.'];
            }

            return self::moncashAppliquerResultat($pdo, $tx, $result);
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant la confirmation MonCash.'];
        }
    }

    /**
     * Dossier admin "Transactions MonCash" : réconciliation manuelle d'un
     * dépôt resté "en attente" (ex. client qui a bien payé sur MonCash mais
     * a fermé son navigateur avant la redirection de retour, ou coupure
     * réseau pendant le retour). Interroge MonCash par NOTRE référence de
     * commande (MonCash::retrieveOrderPayment) plutôt que par l'identifiant
     * de transaction MonCash — inconnu ici puisque le client n'est jamais
     * revenu sur le site. Voir AdminController::moncashVerifier().
     *
     * $expectedUserId (optionnel) restreint la vérification au dépôt d'UN
     * compte précis : utilisé par le dossier client (self-service, voir
     * AccountController::convertirMoncashVerifier()) pour qu'un client ne
     * puisse jamais déclencher la vérification — ni a fortiori voir le
     * résultat — du dépôt MonCash d'un autre client en devinant/rejouant une
     * référence. Laissé à null pour l'usage admin ci-dessus, qui doit
     * pouvoir réconcilier n'importe quel dépôt.
     */
    public static function moncashVerifierManuel(string $reference, ?int $expectedUserId = null): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            return ['ok' => false, 'error' => 'Référence manquante.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM transactions WHERE reference = ? AND type = "moncash_depot" LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([$reference]);
            $tx = $stmt->fetch();

            if (!$tx || ($expectedUserId !== null && (int) $tx['receiver_id'] !== $expectedUserId)) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Transaction introuvable.'];
            }
            if ($tx['status'] === 'reussi') {
                $pdo->commit();
                return ['ok' => true, 'already' => true, 'amount' => (float) $tx['amount']];
            }

            $result = MonCash::retrieveOrderPayment($reference);
            if (!$result['ok']) {
                $pdo->rollBack();
                return $result;
            }

            return self::moncashAppliquerResultat($pdo, $tx, $result);
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant la vérification MonCash.'];
        }
    }

    /**
     * Logique commune de crédit d'un dépôt MonCash, partagée entre le retour
     * client normal (moncashConfirmer) et la réconciliation manuelle admin
     * (moncashVerifierManuel). $tx doit déjà être verrouillée (SELECT ... FOR
     * UPDATE) par l'appelant, qui gère aussi la transaction PDO englobante.
     *
     * Deux garde-fous avant de créditer un centime :
     *   1) $result['successful'] doit être vrai — un objet "payment" MonCash
     *      existant n'est PAS une preuve de paiement réussi (échec/annulation
     *      renvoient aussi un payment, juste avec un 'message' différent).
     *   2) le montant renvoyé par MonCash doit correspondre au montant
     *      demandé lors de l'ouverture du paiement (protection contre une
     *      transaction MonCash qui, par coïncidence ou falsification de la
     *      référence, ne correspondrait pas à la nôtre).
     */
    private static function moncashAppliquerResultat(PDO $pdo, array $tx, array $result): array
    {
        if ($tx['status'] === 'reussi') {
            $pdo->commit();
            return ['ok' => true, 'already' => true, 'amount' => (float) $tx['amount'], 'new_balance' => User::getBalance((int) $tx['receiver_id'])];
        }

        if (empty($result['successful'])) {
            $note = 'Paiement MonCash non abouti (statut : ' . ($result['message'] ?? 'inconnu') . ').';
            $pdo->prepare('UPDATE transactions SET status = "echec", note = ? WHERE id = ?')->execute([$note, $tx['id']]);
            $pdo->commit();
            return ['ok' => false, 'error' => $note];
        }

        $expected = (float) $tx['amount'];
        $received = $result['amount'];
        if ($received !== null && abs($received - $expected) > 0.01) {
            $note = sprintf(
                'Écart de montant détecté (attendu %s HTG, reçu %s HTG de MonCash) — dépôt bloqué par sécurité.',
                number_format($expected, 2),
                number_format($received, 2)
            );
            $pdo->prepare('UPDATE transactions SET status = "echec", note = ? WHERE id = ?')->execute([$note, $tx['id']]);
            $pdo->commit();
            return ['ok' => false, 'error' => $note];
        }

        $receiverId = (int) $tx['receiver_id'];
        $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([$expected, $receiverId]);
        $pdo->prepare('UPDATE transactions SET status = "reussi", note = ? WHERE id = ?')
            ->execute(['Confirmé par MonCash (transaction ' . ($result['transaction_id'] ?? '?') . ')', $tx['id']]);

        $pdo->commit();
        return ['ok' => true, 'amount' => $expected, 'new_balance' => User::getBalance($receiverId)];
    }

    /**
     * Dossier client "Convertir" (/convertir), volet bancaire : le client
     * retire un montant de son solde en billet électronique vers un compte
     * bancaire d'une institution partenaire. Opération simulée (le virement
     * réel vers la banque partenaire n'est pas exécuté par cette démo).
     */
    public static function retraitBancaire(int $userId, int $institutionId, string $accountNumber, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }
        $institution = FinancialInstitution::find($institutionId);
        if (!$institution || $institution['type'] !== 'banque' || $institution['status'] !== 'actif') {
            return ['ok' => false, 'error' => 'Institution bancaire invalide.'];
        }
        if (trim($accountNumber) === '') {
            return ['ok' => false, 'error' => 'Numéro de compte bancaire requis.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $balance = (float) $stmt->fetchColumn();
            if ($balance < $amount) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant pour ce retrait.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')->execute([$amount, $userId]);

            $reference = generate_reference('BNK');
            $note = 'Virement vers ' . $institution['name'] . ' — compte ' . $accountNumber;
            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, ?, NULL, "retrait_bancaire", ?, ?, ?, "reussi")'
            )->execute([$reference, $userId, $amount, $institution['name'], $note]);

            $pdo->commit();
            return ['ok' => true, 'reference' => $reference, 'new_balance' => User::getBalance($userId)];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant le retrait bancaire.'];
        }
    }

    /**
     * Dossier admin "Institutions financières" : montant total attribuable à
     * chaque banque, tous flux confondus — combine deux opérations client
     * distinctes qui envoient toutes deux de l'argent vers une banque
     * partenaire :
     *  - le virement bancaire électronique (retrait_bancaire), rattaché à
     *    l'institution par nom (transactions.category = institution.name) ;
     *  - le retrait en espèces (retrait_especes) — le client envoie de
     *    l'argent numérique pour recevoir de l'argent PHYSIQUE que la banque
     *    débourse au guichet d'une de ses agences — rattaché à l'institution
     *    via l'agence (bnc_agences.institution_id) puisque
     *    transactions.category y stocke le nom de l'agence, pas de la banque.
     * Seules les opérations réellement honorées (status = 'reussi') comptent
     * — un retrait en espèces encore "en_attente" au guichet n'est pas
     * encore de l'argent sorti de la banque.
     * Retourne, par id d'institution : ['virement' => float, 'especes' =>
     * float, 'total' => float].
     */
    public static function totauxMontantsInstitutions(): array
    {
        $totaux = [];

        $stmtVirements = db()->query(
            "SELECT fi.id, COALESCE(SUM(t.amount), 0) AS total
             FROM financial_institutions fi
             LEFT JOIN transactions t
               ON t.type = 'retrait_bancaire' AND t.status = 'reussi' AND t.category = fi.name
             GROUP BY fi.id"
        );
        foreach ($stmtVirements->fetchAll() as $row) {
            $id = (int) $row['id'];
            $totaux[$id] = ['virement' => (float) $row['total'], 'especes' => 0.0];
        }

        $stmtEspeces = db()->query(
            "SELECT a.institution_id AS id, COALESCE(SUM(t.amount), 0) AS total
             FROM bnc_agences a
             JOIN transactions t ON t.type = 'retrait_especes' AND t.status = 'reussi' AND t.category = a.nom
             WHERE a.institution_id IS NOT NULL
             GROUP BY a.institution_id"
        );
        foreach ($stmtEspeces->fetchAll() as $row) {
            $id = (int) $row['id'];
            if (!isset($totaux[$id])) {
                $totaux[$id] = ['virement' => 0.0, 'especes' => 0.0];
            }
            $totaux[$id]['especes'] = (float) $row['total'];
        }

        foreach ($totaux as $id => $t) {
            $totaux[$id]['total'] = $t['virement'] + $t['especes'];
        }

        return $totaux;
    }

    /**
     * Dossier admin "Institutions financières" — détail : chaque opération
     * (virement bancaire ou retrait en espèces, réussie uniquement) reçue
     * par UNE institution précise, avec les informations importantes du
     * client concerné. Utilisé par /admin/institutions/mouvements.
     */
    public static function mouvementsInstitution(int $institutionId, int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*, su.name AS sender_name, su.ninu AS sender_ninu,
                       su.phone AS sender_phone, su.email AS sender_email,
                       su.ville AS sender_ville, su.photo_path AS sender_photo_path
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                WHERE t.status = "reussi" AND (
                      (t.type = "retrait_bancaire" AND t.category = (SELECT name FROM financial_institutions WHERE id = :inst1))
                   OR (t.type = "retrait_especes" AND t.category IN (SELECT nom FROM bnc_agences WHERE institution_id = :inst2))
                )';
        $params = ['inst1' => $institutionId, 'inst2' => $institutionId];

        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (su.name LIKE :s OR su.ninu LIKE :s OR su.phone LIKE :s OR t.reference LIKE :s OR t.note LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Dossier client "Convertir", volet retrait en espèces : le client
     * convertit une partie de son solde en billet électronique en billet
     * PHYSIQUE, à retirer dans une agence BNC de son choix. Le montant est
     * débité immédiatement (comme pour un retrait bancaire) et la demande
     * reste "en_attente" jusqu'à ce qu'un administrateur confirme la remise
     * réelle des espèces au guichet (voir confirmerRetraitEspeces) — on ne
     * peut pas faire confiance à une simple redirection pour de l'argent
     * physique, contrairement à un virement électronique.
     */
    public static function demanderRetraitEspeces(int $userId, int $agenceId, float $amount): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }
        $agence = BncAgence::find($agenceId);
        if (!$agence || (int) $agence['active'] !== 1) {
            return ['ok' => false, 'error' => 'Agence de retrait invalide.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $balance = (float) $stmt->fetchColumn();
            if ($balance < $amount) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant pour ce retrait.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')->execute([$amount, $userId]);

            $reference = generate_reference('CASH');
            $note = 'À retirer à ' . $agence['nom'] . ' (' . $agence['ville'] . ') — présentez ce code au guichet';
            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, ?, NULL, "retrait_especes", ?, ?, ?, "en_attente")'
            )->execute([$reference, $userId, $amount, $agence['nom'], $note]);

            $pdo->commit();
            return [
                'ok'          => true,
                'reference'   => $reference,
                'agence_nom'  => $agence['nom'],
                'agence_ville'=> $agence['ville'],
                'new_balance' => User::getBalance($userId),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant la demande de retrait en espèces.'];
        }
    }

    /** Dossier admin "Retraits en espèces" : toutes les demandes, tous clients confondus */
    public static function allRetraitsEspeces(int $limit = 300, ?string $search = null): array
    {
        $sql = 'SELECT t.*,
                       su.name AS sender_name, su.ninu AS sender_ninu
                FROM transactions t
                LEFT JOIN users su ON su.id = t.sender_id
                WHERE t.type = "retrait_especes"';
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (su.name LIKE :s OR su.ninu LIKE :s OR t.reference LIKE :s OR t.category LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY t.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Confirme qu'un administrateur (à l'agence) a physiquement remis les
     * espèces au client. Le solde a déjà été débité à la demande : ici on ne
     * fait que valider la remise (passage "en_attente" -> "reussi").
     */
    public static function confirmerRetraitEspeces(string $reference): array
    {
        $stmt = db()->prepare('SELECT * FROM transactions WHERE reference = ? AND type = "retrait_especes" LIMIT 1');
        $stmt->execute([$reference]);
        $tx = $stmt->fetch();

        if (!$tx) {
            return ['ok' => false, 'error' => 'Demande de retrait introuvable.'];
        }
        if ($tx['status'] !== 'en_attente') {
            return ['ok' => false, 'error' => 'Cette demande a déjà été traitée.'];
        }

        db()->prepare('UPDATE transactions SET status = "reussi" WHERE id = ?')->execute([$tx['id']]);
        return ['ok' => true];
    }

    /**
     * Annule une demande de retrait en espèces qui n'a pas été honorée
     * (client absent, agence fermée...) et rembourse le montant débité sur
     * le solde du client.
     */
    public static function annulerRetraitEspeces(string $reference): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM transactions WHERE reference = ? AND type = "retrait_especes" LIMIT 1 FOR UPDATE');
            $stmt->execute([$reference]);
            $tx = $stmt->fetch();

            if (!$tx) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Demande de retrait introuvable.'];
            }
            if ($tx['status'] !== 'en_attente') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Cette demande a déjà été traitée.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([(float) $tx['amount'], $tx['sender_id']]);
            $pdo->prepare('UPDATE transactions SET status = "echec" WHERE id = ?')->execute([$tx['id']]);

            $pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant l\'annulation.'];
        }
    }

    /**
     * Dossier client "Épargne" (self-service, /epargne) : le client déplace
     * lui-même un montant entre son solde courant et son solde épargne.
     * $direction : "depot" (solde -> épargne) ou "retrait" (épargne -> solde).
     */
    public static function epargneClientAjuster(int $userId, float $amount, string $direction): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }
        if (!in_array($direction, ['depot', 'retrait'], true)) {
            return ['ok' => false, 'error' => 'Opération épargne invalide.'];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance, epargne_balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (!$row) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Compte introuvable.'];
            }
            $balance = (float) $row['balance'];
            $epargne = (float) $row['epargne_balance'];

            if ($direction === 'depot') {
                if ($balance < $amount) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'Solde courant insuffisant pour ce dépôt en épargne.'];
                }
                $pdo->prepare('UPDATE users SET balance = balance - ?, epargne_balance = epargne_balance + ? WHERE id = ?')
                    ->execute([$amount, $amount, $userId]);
                $type = 'epargne_depot';
            } else {
                if ($epargne < $amount) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'Solde épargne insuffisant pour ce retrait.'];
                }
                $pdo->prepare('UPDATE users SET balance = balance + ?, epargne_balance = epargne_balance - ? WHERE id = ?')
                    ->execute([$amount, $amount, $userId]);
                $type = 'epargne_retrait';
            }

            $reference = generate_reference('EPG');
            $note = $direction === 'depot' ? 'Dépôt vers l\'épargne' : 'Retrait depuis l\'épargne';

            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, NULL, ?, ?, ?, "Épargne", ?, "reussi")'
            )->execute([$reference, $userId, $type, $amount, $note]);

            $pdo->commit();

            return [
                'ok'          => true,
                'reference'   => $reference,
                'new_balance' => User::getBalance($userId),
                'new_epargne' => User::getEpargneBalance($userId),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => "Une erreur est survenue pendant l'opération d'épargne."];
        }
    }

    /**
     * "Dossier épargne" : un administrateur déplace un montant entre le solde
     * courant d'un client et son solde épargne (dépôt vers l'épargne, ou
     * retrait de l'épargne vers le solde courant). Opération atomique.
     * $direction : "depot" (solde -> épargne) ou "retrait" (épargne -> solde).
     */
    public static function epargneAjuster(int $adminId, int $clientId, float $amount, string $direction, string $note = ''): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }
        if (!in_array($direction, ['depot', 'retrait'], true)) {
            return ['ok' => false, 'error' => 'Opération épargne invalide.'];
        }

        $client = User::find($clientId);
        if (!$client || User::isAdminRow($client)) {
            return ['ok' => false, 'error' => 'Client introuvable.'];
        }

        $admin = User::find($adminId);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT balance, epargne_balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$clientId]);
            $row = $stmt->fetch();
            $balance  = (float) ($row['balance'] ?? 0);
            $epargne  = (float) ($row['epargne_balance'] ?? 0);

            if ($direction === 'depot') {
                if ($balance < $amount) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'Solde courant insuffisant pour ce dépôt en épargne.'];
                }
                $pdo->prepare('UPDATE users SET balance = balance - ?, epargne_balance = epargne_balance + ? WHERE id = ?')
                    ->execute([$amount, $amount, $clientId]);
                $type = 'epargne_depot';
            } else {
                if ($epargne < $amount) {
                    $pdo->rollBack();
                    return ['ok' => false, 'error' => 'Solde épargne insuffisant pour ce retrait.'];
                }
                $pdo->prepare('UPDATE users SET balance = balance + ?, epargne_balance = epargne_balance - ? WHERE id = ?')
                    ->execute([$amount, $amount, $clientId]);
                $type = 'epargne_retrait';
            }

            $reference = generate_reference('EPG');
            $noteFinale = trim(($direction === 'depot' ? 'Dépôt épargne' : 'Retrait épargne') . ' par ' . ($admin['name'] ?? 'Administrateur') . ($note !== '' ? ' — ' . $note : ''));

            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, NULL, ?, ?, ?, "Épargne", ?, "reussi")'
            )->execute([$reference, $clientId, $type, $amount, $noteFinale]);

            $pdo->commit();

            AdminLog::record(
                $adminId,
                $admin['name'] ?? 'Administrateur',
                $direction === 'depot' ? 'epargne_depot' : 'epargne_retrait',
                'user',
                $clientId,
                $client['name'],
                number_format($amount, 2, ',', ' ') . ' HTG (réf. ' . $reference . ')'
            );

            return [
                'ok'              => true,
                'reference'       => $reference,
                'client_name'     => $client['name'],
                'new_balance'     => User::getBalance($clientId),
                'new_epargne'     => User::getEpargneBalance($clientId),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant l\'opération d\'épargne.'];
        }
    }

    /**
     * "Ajouter un billet pour un client" : un administrateur crédite directement
     * le solde d'un client (dépôt manuel — ex. dépôt en agence, correction, bonus).
     * Opération atomique comme les autres mouvements de solde.
     */
    public static function ajouterBillet(int $adminId, int $clientId, float $amount, string $note = ''): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant du billet doit être supérieur à zéro.'];
        }

        $client = User::find($clientId);
        if (!$client || User::isAdminRow($client)) {
            return ['ok' => false, 'error' => 'Client introuvable.'];
        }

        $admin = User::find($adminId);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
                ->execute([$amount, $clientId]);

            $reference = generate_reference('BIL');
            $noteFinale = trim('Billet ajouté par ' . ($admin['name'] ?? 'Administrateur') . ($note !== '' ? ' — ' . $note : ''));

            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, category, note, status)
                 VALUES (?, NULL, ?, "depot", ?, "Dépôt administrateur", ?, "reussi")'
            )->execute([$reference, $clientId, $amount, $noteFinale]);

            $pdo->commit();

            AdminLog::record(
                $adminId,
                $admin['name'] ?? 'Administrateur',
                'ajout_billet',
                'user',
                $clientId,
                $client['name'],
                'Dépôt de ' . number_format($amount, 2, ',', ' ') . ' HTG (réf. ' . $reference . ')'
            );

            return [
                'ok'          => true,
                'reference'   => $reference,
                'client_name' => $client['name'],
                'new_balance' => User::getBalance($clientId),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant l\'ajout du billet.'];
        }
    }
}
