<?php
/**
 * app/models/VirtualCard.php
 * Cartes virtuelles générées par un client pour ses achats en ligne
 * (dossier client "Cartes virtuelles", /cartes-virtuelles).
 * Numéros générés localement (non rattachés à un vrai réseau de paiement) :
 * ils illustrent le service au sein de la plateforme Gourde Numérique.
 */
class VirtualCard
{
    /** Toutes les cartes d'un client, les plus récentes en premier */
    public static function allForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT * FROM virtual_cards WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Nombre de cartes déjà générées par ce client (limite anti-abus) */
    public static function countForUser(int $userId): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM virtual_cards WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** Une carte précise, à condition qu'elle appartienne bien au client */
    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM virtual_cards WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $card = $stmt->fetch();
        return $card ?: null;
    }

    /** Génère et enregistre une nouvelle carte virtuelle pour un client */
    public static function create(int $userId, string $holderName, string $label = 'Carte virtuelle'): array
    {
        $number = self::generateNumber();
        $expiry = (int) date('Y') + 3;

        $stmt = db()->prepare(
            'INSERT INTO virtual_cards (user_id, label, card_number, holder_name, expiry_month, expiry_year, cvv, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "active")'
        );
        $stmt->execute([
            $userId,
            $label !== '' ? $label : 'Carte virtuelle',
            $number,
            $holderName,
            (int) date('n'),
            $expiry,
            str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT),
        ]);

        return self::findForUser((int) db()->lastInsertId(), $userId);
    }

    /** Active/gèle une carte (bascule) */
    public static function toggleStatus(int $id, int $userId): bool
    {
        $card = self::findForUser($id, $userId);
        if (!$card) {
            return false;
        }
        $newStatus = $card['status'] === 'active' ? 'frozen' : 'active';
        $stmt = db()->prepare('UPDATE virtual_cards SET status = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$newStatus, $id, $userId]);
    }

    /** Supprime définitivement une carte */
    public static function delete(int $id, int $userId): bool
    {
        $stmt = db()->prepare('DELETE FROM virtual_cards WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    /** Génère un numéro à 16 chiffres (préfixe fictif "4926"), formaté par groupes de 4 */
    private static function generateNumber(): string
    {
        $number = '4926';
        for ($i = 0; $i < 3; $i++) {
            $number .= str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }
        return implode(' ', str_split($number, 4));
    }

    /** Numéro masqué pour affichage rapide (liste), ex. •••• •••• •••• 4821 */
    public static function masked(string $cardNumber): string
    {
        $digits = str_replace(' ', '', $cardNumber);
        return '•••• •••• •••• ' . substr($digits, -4);
    }

    /* =========================================================
     * Dossier admin "Cartes virtuelles" : vue d'ensemble, tous
     * clients confondus (/admin/cartes-virtuelles).
     * ========================================================= */

    /** Toutes les cartes virtuelles, tous clients confondus, avec filtre optionnel de recherche */
    public static function allAdmin(?string $search = null, int $limit = 300): array
    {
        $sql = 'SELECT vc.*, u.name AS client_name, u.ninu AS client_ninu, u.photo_path AS client_photo_path
                FROM virtual_cards vc
                JOIN users u ON u.id = vc.user_id';
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE (u.name LIKE :s OR u.ninu LIKE :s OR vc.label LIKE :s OR vc.card_number LIKE :s OR vc.holder_name LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY vc.created_at DESC LIMIT :lim';

        $stmt = db()->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(':' . $key, $val);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Nombre total de cartes, actives et gelées — pour les compteurs du dossier admin */
    public static function counts(): array
    {
        $stmt = db()->query(
            "SELECT COUNT(*) AS total, SUM(status = 'active') AS actives, SUM(status = 'frozen') AS gelees
             FROM virtual_cards"
        );
        $row = $stmt->fetch() ?: [];
        return [
            'total'   => (int) ($row['total'] ?? 0),
            'actives' => (int) ($row['actives'] ?? 0),
            'gelees'  => (int) ($row['gelees'] ?? 0),
        ];
    }

    /** Une carte précise, avec les infos du client — sans restriction de propriétaire (usage admin) */
    public static function findAny(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT vc.*, u.name AS client_name, u.ninu AS client_ninu
             FROM virtual_cards vc
             JOIN users u ON u.id = vc.user_id
             WHERE vc.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $card = $stmt->fetch();
        return $card ?: null;
    }

    /** Active/gèle une carte, quel qu'en soit le propriétaire (usage admin) */
    public static function toggleStatusAdmin(int $id): bool
    {
        $card = self::findAny($id);
        if (!$card) {
            return false;
        }
        $newStatus = $card['status'] === 'active' ? 'frozen' : 'active';
        $stmt = db()->prepare('UPDATE virtual_cards SET status = ? WHERE id = ?');
        return $stmt->execute([$newStatus, $id]);
    }

    /** Supprime définitivement une carte, quel qu'en soit le propriétaire (usage admin) */
    public static function deleteAdmin(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM virtual_cards WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
