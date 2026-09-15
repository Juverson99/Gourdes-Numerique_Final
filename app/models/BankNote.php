<?php
/**
 * app/models/BankNote.php
 * Accès aux données du dossier de gestion des billets (coupures affichées
 * sur la page publique "Envoyer" : billets simples et paquets de billets).
 * Gérées uniquement depuis l'admin (/admin/billets).
 */
class BankNote
{
    /** Toutes les coupures actives, triées pour l'affichage public (page Envoyer) */
    public static function allActive(): array
    {
        $stmt = db()->query('SELECT * FROM bank_notes WHERE active = 1 ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    /**
     * Décompose le solde disponible d'un client en BILLETS INDIVIDUELS réels
     * (parmi les coupures actives), de sorte que la somme de TOUS les
     * billets retournés soit exactement égale à ce solde — comme si on
     * vidait son portefeuille sur la table. Chaque billet du catalogue peut
     * apparaître plusieurs fois dans le résultat : chaque occurrence
     * représente un billet physique distinct, avec un 'instance_id' propre
     * pour que la page "Envoyer" puisse les distinguer (chacun disparaît
     * individuellement quand on le touche, et peut revenir à sa place s'il
     * n'est finalement pas envoyé).
     *
     * Les "paquets" (is_bundle = 1, ex. un lot de 10 × 100 affiché comme UNE
     * seule carte avec une étiquette "×10") sont volontairement exclus de
     * cette décomposition : le client doit voir plusieurs billets distincts
     * qui s'additionnent pour former son solde, jamais un lot compressé en
     * une seule carte — les paquets restent une dénomination valide ailleurs
     * dans le catalogue (admin), simplement pas utilisée ici.
     *
     * L'algorithme alterne entre les différentes coupures actives (au lieu
     * d'épuiser la plus grande en premier) afin de toujours proposer un
     * assortiment varié de billets — jamais une seule et même dénomination
     * répétée à l'infini — comme un vrai portefeuille contiendrait un
     * mélange de coupures.
     *
     * $maxNotes protège l'affichage contre un solde énorme qui produirait
     * un nombre de billets ingérable ; le reliquat au-delà de cette limite
     * n'est simplement pas représenté (cas extrême, sans impact en usage
     * normal).
     */
    public static function breakdownForBalance(float $balance, int $maxNotes = 60): array
    {
        $reste = (int) round($balance * 100); // travail en centimes pour éviter les erreurs d'arrondi flottant
        if ($reste <= 0) {
            return [];
        }

        // Uniquement des billets individuels — jamais un paquet groupé (voir
        // le commentaire de la méthode ci-dessus).
        $denoms = array_values(array_filter(self::allActive(), static fn (array $n): bool => empty($n['is_bundle'])));
        usort($denoms, fn (array $a, array $b): int => self::totalValue($b) <=> self::totalValue($a));
        if (empty($denoms)) {
            return [];
        }

        // Nombre maximal d'occurrences d'UNE MÊME coupure avant de forcer le
        // passage aux autres dénominations disponibles. S'il n'y a qu'une
        // seule coupure active, la diversité n'est simplement pas possible.
        $maxParDenomination = max(3, (int) ceil($maxNotes / count($denoms)));

        $result           = [];
        $compteurParDenom = array_fill(0, count($denoms), 0);

        // Plusieurs passes en va-et-vient parmi les coupures actives : à
        // chaque passe, on prend au plus une poignée de chaque dénomination
        // (dans la limite de $maxParDenomination) avant de reboucler, ce qui
        // mélange naturellement les billets plutôt que de vider la plus
        // grande coupure avant de passer à la suivante.
        $progress = true;
        while ($reste > 0 && count($result) < $maxNotes && $progress) {
            $progress = false;
            foreach ($denoms as $i => $note) {
                if ($reste <= 0 || count($result) >= $maxNotes) {
                    break;
                }
                $valeur = (int) round(self::totalValue($note) * 100);
                if ($valeur <= 0 || $reste < $valeur) {
                    continue;
                }
                if ($compteurParDenom[$i] >= $maxParDenomination) {
                    continue;
                }

                $note['instance_id'] = $note['id'] . '-' . count($result);
                $result[]            = $note;
                $reste               -= $valeur;
                $compteurParDenom[$i]++;
                $progress = true;
            }
        }

        // S'il reste un montant que la limite de diversité empêche de
        // couvrir (solde très élevé par rapport au nombre de coupures
        // actives), on termine avec la méthode "gourmande" classique afin
        // de ne jamais laisser une partie du solde non représentée.
        if ($reste > 0 && count($result) < $maxNotes) {
            foreach ($denoms as $note) {
                $valeur = (int) round(self::totalValue($note) * 100);
                if ($valeur <= 0) {
                    continue;
                }
                while ($reste >= $valeur && count($result) < $maxNotes) {
                    $note['instance_id'] = $note['id'] . '-' . count($result);
                    $result[]            = $note;
                    $reste               -= $valeur;
                }
                if ($reste <= 0 || count($result) >= $maxNotes) {
                    break;
                }
            }
        }

        return $result;
    }

    /** Toutes les coupures (actives ou non), pour le dossier de gestion en admin */
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM bank_notes ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM bank_notes WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $note = $stmt->fetch();
        return $note ?: null;
    }

    /**
     * Toutes les coupures, regroupées par catégorie pour l'affichage en admin.
     * Retourne un tableau [ 'Nom de catégorie' => [coupures...], ..., 'Sans catégorie' => [...] ].
     * L'ordre des groupes suit le sort_order des catégories ; les coupures
     * sans catégorie apparaissent toujours en dernier.
     */
    public static function allGroupedByCategory(): array
    {
        $stmt = db()->query(
            'SELECT bn.*, bnc.name AS category_name, bnc.sort_order AS category_sort_order
             FROM bank_notes bn
             LEFT JOIN bank_note_categories bnc ON bnc.id = bn.category_id
             ORDER BY (bnc.id IS NULL) ASC, bnc.sort_order ASC, bn.sort_order ASC, bn.id ASC'
        );
        $groups = [];
        foreach ($stmt->fetchAll() as $row) {
            $key = $row['category_name'] ?? 'Sans catégorie';
            $groups[$key][] = $row;
        }
        return $groups;
    }

    /** Prochain ordre d'affichage suggéré (à la suite de la dernière coupure) */
    public static function nextSortOrder(): int
    {
        $max = db()->query('SELECT MAX(sort_order) FROM bank_notes')->fetchColumn();
        return $max !== null ? ((int) $max) + 10 : 10;
    }

    /** Valeur totale ajoutée au montant quand on touche cette carte */
    public static function totalValue(array $note): float
    {
        $unit = (float) $note['unit_value'];
        if (!empty($note['is_bundle']) && !empty($note['bundle_qty'])) {
            return $unit * (int) $note['bundle_qty'];
        }
        return $unit;
    }

    /** Crée une nouvelle coupure (billet simple, avec image recto + verso facultatives) */
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO bank_notes (unit_value, is_bundle, bundle_qty, category_id, color_start, color_end, image_path, image_verso_path, stock_quantity, sort_order, active)
             VALUES (:unit_value, :is_bundle, :bundle_qty, :category_id, :color_start, :color_end, :image_path, :image_verso_path, :stock_quantity, :sort_order, :active)'
        );
        $stmt->execute([
            'unit_value'       => $data['unit_value'],
            'is_bundle'        => $data['is_bundle'] ? 1 : 0,
            'bundle_qty'       => $data['is_bundle'] ? $data['bundle_qty'] : null,
            'category_id'      => $data['category_id'] ?: null,
            'color_start'      => $data['color_start'],
            'color_end'        => $data['color_end'],
            'image_path'       => $data['image_path'] ?? null,
            'image_verso_path' => $data['image_verso_path'] ?? null,
            'stock_quantity'   => $data['stock_quantity'] ?? 0,
            'sort_order'       => $data['sort_order'],
            'active'           => $data['active'] ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    /** Met à jour une coupure existante */
    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE bank_notes
             SET unit_value = :unit_value, is_bundle = :is_bundle, bundle_qty = :bundle_qty,
                 category_id = :category_id, color_start = :color_start, color_end = :color_end,
                 image_path = :image_path, image_verso_path = :image_verso_path,
                 sort_order = :sort_order, active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'unit_value'       => $data['unit_value'],
            'is_bundle'        => $data['is_bundle'] ? 1 : 0,
            'bundle_qty'       => $data['is_bundle'] ? $data['bundle_qty'] : null,
            'category_id'      => $data['category_id'] ?: null,
            'color_start'      => $data['color_start'],
            'color_end'        => $data['color_end'],
            'image_path'       => $data['image_path'] ?? null,
            'image_verso_path' => $data['image_verso_path'] ?? null,
            'sort_order'       => $data['sort_order'],
            'active'           => $data['active'] ? 1 : 0,
            'id'               => $id,
        ]);
        // stock_quantity est géré séparément (voir setStock/adjustStock) : la
        // modification des infos d'une coupure ne touche jamais son stock,
        // pour éviter d'écraser accidentellement une quantité par un oubli
        // de champ dans le formulaire d'édition générale.
    }

    /**
     * Fixe directement la quantité en stock d'une coupure à une valeur donnée
     * (utilisé à la création, et par le formulaire de gestion du stock).
     */
    public static function setStock(int $id, int $quantity): void
    {
        $stmt = db()->prepare('UPDATE bank_notes SET stock_quantity = :qty WHERE id = :id');
        $stmt->execute(['qty' => max(0, $quantity), 'id' => $id]);
    }

    /**
     * Ajoute (ou retire, avec un delta négatif) une quantité au stock actuel
     * d'une coupure, sans jamais descendre sous 0. Retourne la nouvelle
     * quantité en stock après ajustement.
     */
    public static function adjustStock(int $id, int $delta): int
    {
        $note = self::find($id);
        if (!$note) {
            return 0;
        }
        $newQty = max(0, (int) $note['stock_quantity'] + $delta);
        self::setStock($id, $newQty);
        return $newQty;
    }

    /** Quantité totale de billets unitaires en stock, toutes coupures confondues */
    public static function totalStockCount(): int
    {
        return (int) db()->query('SELECT COALESCE(SUM(stock_quantity), 0) FROM bank_notes')->fetchColumn();
    }

    /**
     * Valeur totale du stock en HTG (quantité en stock × valeur d'un billet ;
     * pour un paquet, la quantité en stock compte des paquets, donc la valeur
     * d'un paquet = unit_value × bundle_qty, comme pour totalValue()).
     */
    public static function totalStockValue(): float
    {
        $stmt = db()->query('SELECT unit_value, is_bundle, bundle_qty, stock_quantity FROM bank_notes');
        $total = 0.0;
        foreach ($stmt->fetchAll() as $row) {
            $unitValue = (float) $row['unit_value'];
            if (!empty($row['is_bundle']) && !empty($row['bundle_qty'])) {
                $unitValue *= (int) $row['bundle_qty'];
            }
            $total += $unitValue * (int) $row['stock_quantity'];
        }
        return $total;
    }

    /**
     * Coupures dont le stock est descendu à ou sous le seuil d'alerte donné
     * (uniquement parmi les coupures actives, visibles sur la page Envoyer).
     */
    public static function lowStock(int $threshold = 50): array
    {
        $stmt = db()->prepare(
            'SELECT * FROM bank_notes WHERE active = 1 AND stock_quantity <= :threshold ORDER BY stock_quantity ASC, sort_order ASC'
        );
        $stmt->execute(['threshold' => $threshold]);
        return $stmt->fetchAll();
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM bank_notes WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Supprime le fichier image d'un billet du disque, s'il existe.
     * Restreint volontairement au dossier d'upload prévu pour les billets,
     * pour ne jamais risquer de supprimer un autre fichier du serveur.
     */
    public static function deleteImageFile(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'assets/uploads/billets/')) {
            return;
        }
        $full = ROOT_PATH . '/public/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** Active/désactive l'affichage d'une coupure sur la page Envoyer */
    public static function toggleActive(int $id): void
    {
        $stmt = db()->prepare('UPDATE bank_notes SET active = NOT active WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Indique si une coupure avec cette valeur unitaire ET ce type (billet
     * simple / paquet) existe déjà — c'est la combinaison protégée par la
     * contrainte unique "uq_bn_value_type" de la table.
     * $excludeId permet d'ignorer la coupure elle-même lors d'une modification.
     */
    public static function existsWithValueType(float $unitValue, bool $isBundle, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM bank_notes WHERE unit_value = :unit_value AND is_bundle = :is_bundle';
        $params = ['unit_value' => $unitValue, 'is_bundle' => $isBundle ? 1 : 0];

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
     * $excludeId : id de la coupure en cours de modification (à exclure de
     * la vérification de doublon), ou null lors d'une création.
     * Retourne un tableau d'erreurs (vide si tout est valide).
     */
    public static function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (!isset($data['unit_value']) || (float) $data['unit_value'] <= 0) {
            $errors[] = 'La valeur unitaire du billet doit être un nombre supérieur à 0.';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($data['color_start'] ?? ''))) {
            $errors[] = 'La couleur de départ doit être un code hexadécimal valide (ex : #1f6fb2).';
        }
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($data['color_end'] ?? ''))) {
            $errors[] = 'La couleur de fin doit être un code hexadécimal valide (ex : #164b7a).';
        }
        if (!empty($data['is_bundle']) && (int) ($data['bundle_qty'] ?? 0) <= 1) {
            $errors[] = 'Un paquet de billets doit contenir au moins 2 billets.';
        }

        // Vérifie le doublon uniquement si la valeur est valide, pour éviter
        // un message redondant avec l'erreur "valeur > 0" ci-dessus.
        if (isset($data['unit_value']) && (float) $data['unit_value'] > 0) {
            $isBundle = !empty($data['is_bundle']);
            if (self::existsWithValueType((float) $data['unit_value'], $isBundle, $excludeId)) {
                $type = $isBundle ? 'un paquet de billets' : 'un billet simple';
                $errors[] = 'Ce billet existe déjà : ' . $type . ' de ' . rtrim(rtrim(number_format((float) $data['unit_value'], 2, ',', ' '), '0'), ',')
                    . ' HTG est déjà enregistré. Modifiez la coupure existante au lieu d\'en créer une nouvelle.';
            }
        }

        return $errors;
    }
}
