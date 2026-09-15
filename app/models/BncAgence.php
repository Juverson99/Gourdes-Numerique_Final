<?php
/**
 * app/models/BncAgence.php
 * Répertoire des agences de la Banque Nationale de Crédit (BNC) à travers
 * le pays. Affiché au public sur une carte interactive (/agences-bnc) et
 * géré depuis l'admin (/admin/agences-bnc).
 */
class BncAgence
{
    /** Départements administratifs d'Haïti (liste fermée pour le formulaire) */
    public static function departements(): array
    {
        return [
            'Ouest', 'Artibonite', 'Centre', 'Grand\'Anse', 'Nippes',
            'Nord', 'Nord-Est', 'Nord-Ouest', 'Sud', 'Sud-Est',
        ];
    }

    /** Toutes les agences actives, triées pour l'affichage public (principale d'abord) */
    public static function allActive(): array
    {
        $stmt = db()->query(
            'SELECT * FROM bnc_agences WHERE active = 1 ORDER BY principale DESC, sort_order ASC, nom ASC'
        );
        return $stmt->fetchAll();
    }

    /** Toutes les agences (actives ou non), avec filtre optionnel de recherche, pour l'admin — inclut le nom de l'institution rattachée */
    public static function all(?string $search = null): array
    {
        if ($search) {
            $stmt = db()->prepare(
                'SELECT a.*, fi.name AS institution_name
                 FROM bnc_agences a
                 LEFT JOIN financial_institutions fi ON fi.id = a.institution_id
                 WHERE a.nom LIKE :q OR a.ville LIKE :q OR a.departement LIKE :q
                 ORDER BY a.principale DESC, a.sort_order ASC, a.nom ASC'
            );
            $stmt->execute(['q' => '%' . $search . '%']);
            return $stmt->fetchAll();
        }
        $stmt = db()->query(
            'SELECT a.*, fi.name AS institution_name
             FROM bnc_agences a
             LEFT JOIN financial_institutions fi ON fi.id = a.institution_id
             ORDER BY a.principale DESC, a.sort_order ASC, a.nom ASC'
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM bnc_agences WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Nombre total d'agences, actives et par département (pour la liste / le dashboard) */
    public static function counts(): array
    {
        $stmt = db()->query('SELECT COUNT(*) AS total, SUM(active = 1) AS actives, COUNT(DISTINCT departement) AS departements FROM bnc_agences');
        $row = $stmt->fetch() ?: [];
        return [
            'total'       => (int) ($row['total'] ?? 0),
            'actives'     => (int) ($row['actives'] ?? 0),
            'departements'=> (int) ($row['departements'] ?? 0),
        ];
    }

    public static function nextSortOrder(): int
    {
        $max = db()->query('SELECT MAX(sort_order) FROM bnc_agences')->fetchColumn();
        return $max !== null ? ((int) $max) + 10 : 10;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO bnc_agences (institution_id, nom, departement, ville, adresse, telephone, latitude, longitude, principale, sort_order, active)
             VALUES (:institution_id, :nom, :departement, :ville, :adresse, :telephone, :latitude, :longitude, :principale, :sort_order, :active)'
        );
        $stmt->execute([
            'institution_id' => $data['institution_id'] !== '' && $data['institution_id'] !== null ? (int) $data['institution_id'] : null,
            'nom'         => $data['nom'],
            'departement' => $data['departement'],
            'ville'       => $data['ville'],
            'adresse'     => $data['adresse'] !== '' ? $data['adresse'] : null,
            'telephone'   => $data['telephone'] !== '' ? $data['telephone'] : null,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'principale'  => $data['principale'] ? 1 : 0,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'] ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE bnc_agences
             SET institution_id = :institution_id, nom = :nom, departement = :departement, ville = :ville, adresse = :adresse,
                 telephone = :telephone, latitude = :latitude, longitude = :longitude,
                 principale = :principale, sort_order = :sort_order, active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'institution_id' => $data['institution_id'] !== '' && $data['institution_id'] !== null ? (int) $data['institution_id'] : null,
            'nom'         => $data['nom'],
            'departement' => $data['departement'],
            'ville'       => $data['ville'],
            'adresse'     => $data['adresse'] !== '' ? $data['adresse'] : null,
            'telephone'   => $data['telephone'] !== '' ? $data['telephone'] : null,
            'latitude'    => $data['latitude'],
            'longitude'   => $data['longitude'],
            'principale'  => $data['principale'] ? 1 : 0,
            'sort_order'  => $data['sort_order'],
            'active'      => $data['active'] ? 1 : 0,
            'id'          => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM bnc_agences WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function toggleActive(int $id): void
    {
        $stmt = db()->prepare('UPDATE bnc_agences SET active = NOT active WHERE id = ?');
        $stmt->execute([$id]);
    }

    /** Une autre agence porte-t-elle déjà ce nom ? (insensible à la casse) */
    public static function existsByName(string $nom, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM bnc_agences WHERE LOWER(nom) = LOWER(:nom)';
        $params = ['nom' => $nom];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * Valide les champs soumis par le formulaire admin.
     * $excludeId : id de l'agence en cours de modification (à exclure de la
     * vérification de doublon de nom), ou null lors d'une création.
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        $nom = trim((string) ($data['nom'] ?? ''));
        if ($nom === '') {
            $errors[] = 'Le nom de l\'agence est obligatoire.';
        } elseif (self::existsByName($nom, $excludeId)) {
            $errors[] = 'Une agence nommée « ' . $nom . ' » existe déjà.';
        }
        if (!in_array($data['departement'] ?? '', self::departements(), true)) {
            $errors[] = 'Le département sélectionné n\'est pas valide.';
        }
        if (trim((string) ($data['ville'] ?? '')) === '') {
            $errors[] = 'La ville est obligatoire.';
        }
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        if ($lat === null || $lat === '' || !is_numeric($lat) || (float) $lat < 17.5 || (float) $lat > 20.5) {
            $errors[] = 'La latitude doit être un nombre valide situé dans le territoire d\'Haïti (entre 17,5 et 20,5).';
        }
        if ($lng === null || $lng === '' || !is_numeric($lng) || (float) $lng < -75.0 || (float) $lng > -71.0) {
            $errors[] = 'La longitude doit être un nombre valide situé dans le territoire d\'Haïti (entre -75 et -71).';
        }
        // Le téléphone est facultatif ici, mais s'il est renseigné il doit respecter le
        // même format que partout ailleurs dans l'application (voir validate_phone()).
        $telephone = trim((string) ($data['telephone'] ?? ''));
        if ($telephone !== '' && ($phoneError = validate_phone($telephone))) {
            $errors[] = $phoneError;
        }

        return $errors;
    }
}
