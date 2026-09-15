<?php
/**
 * app/models/Supplier.php
 * Accès aux données du dossier "Fournisseurs" (répertoire des fournisseurs
 * de billets/liquidités, imprimeurs, prestataires de matériel ou de
 * services partenaires de la plateforme Gourde Numérique). Géré uniquement
 * depuis l'admin (/admin/fournisseurs).
 */
class Supplier
{
    /** Libellés affichables des catégories de fournisseur */
    public static function categoryLabels(): array
    {
        return [
            'billets'   => 'Fournisseur de billets / liquidités',
            'materiel'  => 'Matériel',
            'services'  => 'Services',
            'autre'     => 'Autre',
        ];
    }

    /** Tous les fournisseurs, avec filtre optionnel par recherche (nom / NIF / ville) */
    public static function all(?string $search = null): array
    {
        if ($search) {
            $stmt = db()->prepare(
                'SELECT * FROM suppliers
                 WHERE name LIKE :q OR tax_id LIKE :q OR ville LIKE :q
                 ORDER BY name ASC'
            );
            $stmt->execute(['q' => '%' . $search . '%']);
            return $stmt->fetchAll();
        }

        $stmt = db()->query('SELECT * FROM suppliers ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM suppliers WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Nombre total de fournisseurs et répartition par statut (pour la liste) */
    public static function counts(): array
    {
        $stmt = db()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'actif') AS actifs,
                SUM(status = 'suspendu') AS suspendus
             FROM suppliers"
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
            'INSERT INTO suppliers
                (name, category, tax_id, contact_name, phone, email, ville, address, logo_path, status, notes)
             VALUES
                (:name, :category, :tax_id, :contact_name, :phone, :email, :ville, :address, :logo_path, :status, :notes)'
        );
        $stmt->execute([
            'name'         => $data['name'],
            'category'     => $data['category'],
            'tax_id'       => $data['tax_id'] !== '' ? $data['tax_id'] : null,
            'contact_name' => $data['contact_name'] !== '' ? $data['contact_name'] : null,
            'phone'        => $data['phone'] !== '' ? $data['phone'] : null,
            'email'        => $data['email'] !== '' ? $data['email'] : null,
            'ville'        => $data['ville'] !== '' ? $data['ville'] : null,
            'address'      => $data['address'] !== '' ? $data['address'] : null,
            'logo_path'    => $data['logo_path'] ?? null,
            'status'       => $data['status'],
            'notes'        => $data['notes'] !== '' ? $data['notes'] : null,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE suppliers
             SET name = :name, category = :category, tax_id = :tax_id, contact_name = :contact_name,
                 phone = :phone, email = :email, ville = :ville, address = :address,
                 logo_path = :logo_path, status = :status, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'name'         => $data['name'],
            'category'     => $data['category'],
            'tax_id'       => $data['tax_id'] !== '' ? $data['tax_id'] : null,
            'contact_name' => $data['contact_name'] !== '' ? $data['contact_name'] : null,
            'phone'        => $data['phone'] !== '' ? $data['phone'] : null,
            'email'        => $data['email'] !== '' ? $data['email'] : null,
            'ville'        => $data['ville'] !== '' ? $data['ville'] : null,
            'address'      => $data['address'] !== '' ? $data['address'] : null,
            'logo_path'    => $data['logo_path'] ?? null,
            'status'       => $data['status'],
            'notes'        => $data['notes'] !== '' ? $data['notes'] : null,
            'id'           => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Supprime le fichier logo d'un fournisseur du disque, s'il existe.
     * Restreint volontairement au dossier d'upload prévu, pour ne jamais
     * risquer de supprimer un autre fichier du serveur.
     */
    public static function deleteLogoFile(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'assets/uploads/fournisseurs/')) {
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
            "UPDATE suppliers SET status = IF(status = 'actif', 'suspendu', 'actif') WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    /** Un NIF/matricule est-il déjà pris par un autre fournisseur ? */
    public static function existsWithTaxId(string $taxId, ?int $excludeId = null): bool
    {
        if ($taxId === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) FROM suppliers WHERE tax_id = :tax_id';
        $params = ['tax_id' => $taxId];

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
     * $excludeId : id du fournisseur en cours de modification (à exclure
     * de la vérification de doublon de NIF), ou null lors d'une création.
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'Le nom du fournisseur est obligatoire.';
        }
        if (!array_key_exists($data['category'] ?? '', self::categoryLabels())) {
            $errors[] = 'La catégorie de fournisseur sélectionnée n\'est pas valide.';
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
        if (!empty($data['tax_id']) && self::existsWithTaxId($data['tax_id'], $excludeId)) {
            $errors[] = 'Ce numéro d\'identification fiscale (NIF) est déjà utilisé par un autre fournisseur.';
        }

        return $errors;
    }
}
