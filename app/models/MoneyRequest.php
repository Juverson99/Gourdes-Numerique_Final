<?php
/**
 * app/models/MoneyRequest.php
 * "Demander de l'argent" (dossier client /recevoir) : un client (requester)
 * demande un montant précis à un autre client (target) en indiquant son
 * numéro de téléphone. Le client sollicité voit la demande (icône d'alerte
 * dans l'en-tête, sur toutes les pages) et peut :
 *  - l'accepter : un vrai envoi est effectué de son solde vers le demandeur,
 *    exactement comme un envoi classique (même vérification de solde,
 *    même transaction SQL atomique) ;
 *  - la refuser : la demande est classée sans mouvement d'argent.
 * Tant qu'elle n'est pas traitée, une demande est "en_attente" et ne touche
 * jamais au solde de personne.
 */
class MoneyRequest
{
    /**
     * Crée une demande d'argent de $requesterId vers le client dont le
     * numéro de téléphone est $targetPhone (déjà normalisé, voir normalize_phone()).
     */
    public static function creer(int $requesterId, string $targetPhone, float $amount, string $message = ''): array
    {
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Le montant doit être supérieur à zéro.'];
        }

        $target = User::findByPhone($targetPhone);
        if (!$target || User::isAdminRow($target)) {
            return ['ok' => false, 'error' => 'Aucun compte client ne correspond à ce numéro de téléphone.'];
        }
        if ((int) $target['id'] === $requesterId) {
            return ['ok' => false, 'error' => 'Vous ne pouvez pas vous demander de l\'argent à vous-même.'];
        }

        // Pas plus d'une demande en attente déjà envoyée par ce même client à ce même client.
        $stmt = db()->prepare(
            "SELECT id FROM money_requests
             WHERE requester_id = ? AND target_id = ? AND status = 'en_attente' LIMIT 1"
        );
        $stmt->execute([$requesterId, $target['id']]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'Vous avez déjà une demande en attente auprès de ce client.'];
        }

        $requester = User::find($requesterId);
        $reference = generate_reference('DEM');
        $message   = trim($message);

        db()->prepare(
            'INSERT INTO money_requests (reference, requester_id, target_id, amount, message, status)
             VALUES (?, ?, ?, ?, ?, "en_attente")'
        )->execute([$reference, $requesterId, $target['id'], $amount, $message !== '' ? $message : null]);

        return [
            'ok'          => true,
            'reference'   => $reference,
            'target_name' => $target['name'],
        ];
    }

    /** Trouve une demande par sa référence */
    public static function findByReference(string $reference): ?array
    {
        $stmt = db()->prepare('SELECT * FROM money_requests WHERE reference = ? LIMIT 1');
        $stmt->execute([$reference]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Demandes reçues par $userId (on lui demande de l'argent) qui sont
     * encore en attente — utilisées pour l'icône d'alerte et son menu,
     * affichés sur toutes les pages une fois connecté.
     */
    public static function enAttentePourCible(int $userId): array
    {
        $stmt = db()->prepare(
            "SELECT mr.*, ru.name AS requester_name, ru.phone AS requester_phone, ru.photo_path AS requester_photo_path
             FROM money_requests mr
             JOIN users ru ON ru.id = mr.requester_id
             WHERE mr.target_id = ? AND mr.status = 'en_attente'
             ORDER BY mr.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Nombre de demandes en attente pour $userId (badge de l'icône d'alerte) */
    public static function compterEnAttentePourCible(int $userId): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*) FROM money_requests WHERE target_id = ? AND status = 'en_attente'"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Le client sollicité ($userId) accepte la demande $reference : un envoi
     * réel est effectué de son solde vers le demandeur (même logique que
     * Transaction::envoyer, dans une seule transaction SQL atomique).
     */
    public static function accepter(string $reference, int $userId): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM money_requests WHERE reference = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$reference]);
            $demande = $stmt->fetch();

            if (!$demande) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Demande introuvable.'];
            }
            if ((int) $demande['target_id'] !== $userId) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Cette demande ne vous concerne pas.'];
            }
            if ($demande['status'] !== 'en_attente') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Cette demande a déjà été traitée.'];
            }

            $amount = (float) $demande['amount'];

            $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $balance = (float) $stmt->fetchColumn();
            if ($balance < $amount) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant pour accepter cette demande.'];
            }

            $requester = User::find((int) $demande['requester_id']);
            if (!$requester) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Le demandeur n\'existe plus.'];
            }

            $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?')
                ->execute([$amount, $userId]);
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')
                ->execute([$amount, $requester['id']]);

            $txReference = generate_reference('ENV');
            $note = 'Demande d\'argent acceptée (réf. ' . $demande['reference'] . ')' . ($demande['message'] ? ' — ' . $demande['message'] : '');
            $pdo->prepare(
                'INSERT INTO transactions (reference, sender_id, receiver_id, type, amount, note, status)
                 VALUES (?, ?, ?, "envoi", ?, ?, "reussi")'
            )->execute([$txReference, $userId, $requester['id'], $amount, $note]);
            $transactionId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'UPDATE money_requests SET status = "acceptee", transaction_id = ? WHERE id = ?'
            )->execute([$transactionId, $demande['id']]);

            $pdo->commit();
            return [
                'ok'              => true,
                'amount'          => $amount,
                'requester_name'  => $requester['name'],
                'new_balance'     => User::getBalance($userId),
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'Une erreur est survenue pendant l\'acceptation de la demande.'];
        }
    }

    /** Le client sollicité ($userId) refuse la demande $reference, sans aucun mouvement d'argent. */
    public static function refuser(string $reference, int $userId): array
    {
        $demande = self::findByReference($reference);
        if (!$demande) {
            return ['ok' => false, 'error' => 'Demande introuvable.'];
        }
        if ((int) $demande['target_id'] !== $userId) {
            return ['ok' => false, 'error' => 'Cette demande ne vous concerne pas.'];
        }
        if ($demande['status'] !== 'en_attente') {
            return ['ok' => false, 'error' => 'Cette demande a déjà été traitée.'];
        }

        db()->prepare('UPDATE money_requests SET status = "refusee" WHERE id = ?')->execute([$demande['id']]);
        return ['ok' => true];
    }

    /** Le demandeur ($userId) annule sa propre demande encore en attente. */
    public static function annuler(string $reference, int $userId): array
    {
        $demande = self::findByReference($reference);
        if (!$demande) {
            return ['ok' => false, 'error' => 'Demande introuvable.'];
        }
        if ((int) $demande['requester_id'] !== $userId) {
            return ['ok' => false, 'error' => 'Cette demande ne vous appartient pas.'];
        }
        if ($demande['status'] !== 'en_attente') {
            return ['ok' => false, 'error' => 'Cette demande a déjà été traitée.'];
        }

        db()->prepare('UPDATE money_requests SET status = "annulee" WHERE id = ?')->execute([$demande['id']]);
        return ['ok' => true];
    }
}
