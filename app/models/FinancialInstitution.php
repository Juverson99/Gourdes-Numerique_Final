<?php
/**
 * app/models/FinancialInstitution.php
 * Accès aux données du dossier "Institutions financières" (répertoire des
 * banques, coopératives et émetteurs de monnaie électronique partenaires de
 * la plateforme Gourde Numérique). Géré uniquement depuis l'admin
 * (/admin/institutions).
 */
class FinancialInstitution
{
    /** Libellés affichables des types d'institution */
    public static function typeLabels(): array
    {
        return [
            'banque'      => 'Banque',
            'cooperative' => 'Coopérative',
            'emi'         => 'Émetteur de monnaie électronique',
            'autre'       => 'Autre',
        ];
    }

    /** Toutes les institutions, avec filtre optionnel par recherche (nom / n° licence / ville) */
    public static function all(?string $search = null): array
    {
        if ($search) {
            $stmt = db()->prepare(
                'SELECT * FROM financial_institutions
                 WHERE name LIKE :q OR license_number LIKE :q OR ville LIKE :q
                 ORDER BY name ASC'
            );
            $stmt->execute(['q' => '%' . $search . '%']);
            return $stmt->fetchAll();
        }

        $stmt = db()->query('SELECT * FROM financial_institutions ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    /** Institutions actives d'un type donné (ex : "banque"), pour les sélecteurs côté client */
    public static function allActiveByType(string $type): array
    {
        $stmt = db()->prepare("SELECT * FROM financial_institutions WHERE type = ? AND status = 'actif' ORDER BY name ASC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM financial_institutions WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Nombre total d'institutions et répartition par statut (pour le tableau de bord / la liste) */
    public static function counts(): array
    {
        $stmt = db()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'actif') AS actifs,
                SUM(status = 'suspendu') AS suspendus
             FROM financial_institutions"
        );
        $row = $stmt->fetch() ?: [];
        return [
            'total'     => (int) ($row['total'] ?? 0),
            'actifs'    => (int) ($row['actifs'] ?? 0),
            'suspendus' => (int) ($row['suspendus'] ?? 0),
        ];
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO financial_institutions
                (name, type, license_number, contact_name, phone, email, ville, address, logo_path, status, notes)
             VALUES
                (:name, :type, :license_number, :contact_name, :phone, :email, :ville, :address, :logo_path, :status, :notes)'
        );
        $stmt->execute([
            'name'           => $data['name'],
            'type'           => $data['type'],
            'license_number' => $data['license_number'] !== '' ? $data['license_number'] : null,
            'contact_name'   => $data['contact_name'] !== '' ? $data['contact_name'] : null,
            'phone'          => $data['phone'] !== '' ? $data['phone'] : null,
            'email'          => $data['email'] !== '' ? $data['email'] : null,
            'ville'          => $data['ville'] !== '' ? $data['ville'] : null,
            'address'        => $data['address'] !== '' ? $data['address'] : null,
            'logo_path'      => $data['logo_path'] ?? null,
            'status'         => $data['status'],
            'notes'          => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE financial_institutions
             SET name = :name, type = :type, license_number = :license_number, contact_name = :contact_name,
                 phone = :phone, email = :email, ville = :ville, address = :address,
                 logo_path = :logo_path, status = :status, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'name'           => $data['name'],
            'type'           => $data['type'],
            'license_number' => $data['license_number'] !== '' ? $data['license_number'] : null,
            'contact_name'   => $data['contact_name'] !== '' ? $data['contact_name'] : null,
            'phone'          => $data['phone'] !== '' ? $data['phone'] : null,
            'email'          => $data['email'] !== '' ? $data['email'] : null,
            'ville'          => $data['ville'] !== '' ? $data['ville'] : null,
            'address'        => $data['address'] !== '' ? $data['address'] : null,
            'logo_path'      => $data['logo_path'] ?? null,
            'status'         => $data['status'],
            'notes'          => $data['notes'] !== '' ? $data['notes'] : null,
            'id'             => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM financial_institutions WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Supprime le fichier logo d'une institution du disque, s'il existe.
     * Restreint volontairement au dossier d'upload prévu, pour ne jamais
     * risquer de supprimer un autre fichier du serveur.
     */
    public static function deleteLogoFile(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'assets/uploads/institutions/')) {
            return;
        }
        $full = ROOT_PATH . '/public/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** Bascule le statut actif / suspendu du partenariat */
    public static function toggleStatus(int $id): void
    {
        $stmt = db()->prepare(
            "UPDATE financial_institutions SET status = IF(status = 'actif', 'suspendu', 'actif') WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /** Un numéro de licence est-il déjà pris par une autre institution ? */
    public static function existsWithLicense(string $licenseNumber, ?int $excludeId = null): bool
    {
        if ($licenseNumber === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM financial_institutions WHERE license_number = :license_number';
        $params = ['license_number' => $licenseNumber];

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
     * $excludeId : id de l'institution en cours de modification (à exclure
     * de la vérification de doublon de licence), ou null lors d'une création.
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'Le nom de l\'institution est obligatoire.';
        }
        if (!array_key_exists($data['type'] ?? '', self::typeLabels())) {
            $errors[] = 'Le type d\'institution sélectionné n\'est pas valide.';
        }
        if (!in_array($data['status'] ?? '', ['actif', 'suspendu'], true)) {
            $errors[] = 'Le statut sélectionné n\'est pas valide.';
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse courriel du contact n\'est pas valide.';
        }
        // Le téléphone est facultatif ici, mais s'il est renseigné il doit respecter le
        // même format que partout ailleurs dans l'application (voir validate_phone()).
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '' && ($phoneError = validate_phone($phone))) {
            $errors[] = $phoneError;
        }
        if (!empty($data['license_number']) && self::existsWithLicense($data['license_number'], $excludeId)) {
            $errors[] = 'Ce numéro de licence est déjà utilisé par une autre institution.';
        }

        return $errors;
    }
}
