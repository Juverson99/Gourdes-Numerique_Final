<?php
/**
 * app/models/BankNoteCategory.php
 * Catégories utilisées pour regrouper/organiser les coupures de billets
 * (dossier de gestion : /admin/categories-billets).
 */
class BankNoteCategory
{
    /** Toutes les catégories actives, triées pour l'affichage (sélecteurs, regroupement) */
    public static function allActive(): array
    {
        $stmt = db()->query('SELECT * FROM bank_note_categories WHERE active = 1 ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    /** Toutes les catégories (actives ou non), pour le dossier de gestion en admin */
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM bank_note_categories ORDER BY sort_order ASC, name ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM bank_note_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        return $cat ?: null;
    }

    /** Nombre de coupures rattachées à chaque catégorie (id => nombre) */
    public static function noteCounts(): array
    {
        $stmt = db()->query('SELECT category_id, COUNT(*) AS n FROM bank_notes WHERE category_id IS NOT NULL GROUP BY category_id');
        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['category_id']] = (int) $row['n'];
        }
        return $counts;
    }

    /** Prochain ordre d'affichage suggéré (à la suite de la dernière catégorie) */
    public static function nextSortOrder(): int
    {
        $max = db()->query('SELECT MAX(sort_order) FROM bank_note_categories')->fetchColumn();
        return $max !== null ? ((int) $max) + 10 : 10;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO bank_note_categories (name, description, sort_order, active)
             VALUES (:name, :description, :sort_order, :active)'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'] ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE bank_note_categories
             SET name = :name, description = :description, sort_order = :sort_order, active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'] ? 1 : 0,
            'id'          => $id,
        ]);
    }

    /**
     * Supprime une catégorie. Les coupures qui y étaient rattachées ne sont
     * PAS supprimées : leur category_id repasse à NULL (contrainte
     * ON DELETE SET NULL de la table bank_notes).
     */
    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM bank_note_categories WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = db()->prepare('UPDATE bank_note_categories SET active = NOT active WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Une autre catégorie porte-t-elle déjà ce nom ? (insensible à la casse) */
    public static function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM bank_note_categories WHERE LOWER(name) = LOWER(:name)';
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

        return $errors;
    }
}
