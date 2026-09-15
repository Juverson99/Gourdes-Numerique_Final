<?php
/**
 * app/models/PaymentCategory.php
 * Catégories utilisées sur la page publique "Paiement" (Payez marchands et
 * factures en un tap) : Électricité, Eau, Internet, Marchand, Transport, etc.
 * Gérées depuis l'admin (/admin/categories-paiement) et affichées côté
 * client sous forme de "chips" à sélectionner en un tap.
 */
class PaymentCategory
{
    /** Toutes les catégories actives, triées pour l'affichage côté client (chips) */
    public static function allActive(): array
    {
        $stmt = db()->query('SELECT * FROM payment_categories WHERE active = 1 ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    /** Toutes les catégories (actives ou non), pour le dossier de gestion en admin */
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM payment_categories ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM payment_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        return $cat ?: null;
    }

    /** Nombre de paiements déjà enregistrés sous le libellé de chaque catégorie (id => nombre) */
    public static function paymentCounts(): array
    {
        $stmt = db()->query(
            "SELECT pc.id, COUNT(t.id) AS n
             FROM payment_categories pc
             LEFT JOIN transactions t ON t.type = 'paiement' AND t.category = pc.name
             GROUP BY pc.id"
        );
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['id']] = (int) $row['n'];
        }
        return $counts;
    }

    /** Prochain ordre d'affichage suggéré (à la suite de la dernière catégorie) */
    public static function nextSortOrder(): int
    {
        $max = db()->query('SELECT MAX(sort_order) FROM payment_categories')->fetchColumn();
        return $max !== null ? ((int) $max) + 10 : 10;
    }

    /** Icône Bootstrap Icons par défaut proposée à la création */
    public static function defaultIcon(): string
    {
        return 'bi-shop';
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO payment_categories (name, icon, sort_order, active)
             VALUES (:name, :icon, :sort_order, :active)'
        );
        $stmt->execute([
            'name'       => $data['name'],
            'icon'       => $data['icon'] !== '' ? $data['icon'] : self::defaultIcon(),
            'sort_order' => $data['sort_order'],
            'active'     => $data['active'] ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE payment_categories
             SET name = :name, icon = :icon, sort_order = :sort_order, active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'name'       => $data['name'],
            'icon'       => $data['icon'] !== '' ? $data['icon'] : self::defaultIcon(),
            'sort_order' => $data['sort_order'],
            'active'     => $data['active'] ? 1 : 0,
            'id'         => $id,
        ]);
    }

    /**
     * Supprime une catégorie. Les paiements déjà effectués sous ce libellé
     * restent inchangés dans l'historique (le champ transactions.category
     * est un simple texte, indépendant de cette table de gestion).
     */
    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM payment_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = db()->prepare('UPDATE payment_categories SET active = NOT active WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Une autre catégorie porte-t-elle déjà ce nom ? (insensible à la casse) */
    public static function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM payment_categories WHERE LOWER(name) = LOWER(:name)';
        $params = ['name' => $name];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /** Valide les champs soumis par le formulaire admin. */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'Le nom de la catégorie est obligatoire.';
        } elseif (mb_strlen($name) > 80) {
            $errors[] = 'Le nom de la catégorie ne doit pas dépasser 80 caractères.';
        } elseif (self::existsByName($name, $excludeId)) {
            $errors[] = 'Une catégorie nommée « ' . $name . ' » existe déjà.';
        }

        $icon = trim((string) ($data['icon'] ?? ''));
        if ($icon !== '' && !preg_match('/^bi-[a-z0-9-]+$/', $icon)) {
            $errors[] = 'L\'icône doit être une classe Bootstrap Icons valide (ex : bi-shop).';
        }

        return $errors;
    }
}
