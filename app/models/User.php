<?php
/**
 * app/models/User.php
 * Accès aux données des utilisateurs (comptes citoyens de la plateforme).
 */
class User
{
    /** Trouve un utilisateur par son identifiant */
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Trouve un utilisateur par email (utilisé pour la connexion) */
    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Trouve un utilisateur par son numéro NINU (utilisé pour Envoyer / Scanner) */
    public static function findByNinu(string $ninu): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE ninu = ? LIMIT 1');
        $stmt->execute([trim($ninu)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Trouve un utilisateur par téléphone (utilisé pour promouvoir un client existant en admin) */
    public static function findByPhone(string $phone): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([trim($phone)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /** Vérifie si un email, un téléphone ou un NINU est déjà utilisé.
     *  $excludeId permet d'ignorer le compte lui-même (formulaire de modification). */
    public static function existsByEmailPhoneOrNinu(string $email, string $phone, string $ninu, ?int $excludeId = null): ?string
    {
        $sql = 'SELECT email, phone, ninu FROM users WHERE (email = ? OR phone = ? OR ninu = ?)';
        $params = [strtolower(trim($email)), trim($phone), trim($ninu)];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }
        if ($row['email'] === strtolower(trim($email))) {
            return 'Un compte existe déjà avec cet email.';
        }
        if ($row['phone'] === trim($phone)) {
            return 'Un compte existe déjà avec ce numéro de téléphone.';
        }
        return 'Un compte existe déjà avec ce numéro NINU.';
    }

    /** Crée un nouveau compte utilisateur et retourne son id */
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (ninu, name, sexe, age, ville, pays, phone, email, password_hash, balance, photo_path)
             VALUES (:ninu, :name, :sexe, :age, :ville, :pays, :phone, :email, :password_hash, :balance, :photo_path)'
        );
        $stmt->execute([
            'ninu'          => $data['ninu'],
            'name'          => $data['name'],
            'sexe'          => $data['sexe'],
            'age'           => $data['age'],
            'ville'         => $data['ville'],
            'pays'          => $data['pays'],
            'phone'         => $data['phone'],
            'email'         => strtolower($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'balance'       => SOLDE_INITIAL,
            'photo_path'    => $data['photo_path'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    /** Vérifie le mot de passe d'un utilisateur (retourne le compte si valide) */
    public static function verifyCredentials(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    /** Vérifie le mot de passe courant d'un utilisateur déjà connu par son id (dossier Sécurité) */
    public static function verifyPassword(int $userId, string $password): bool
    {
        $user = self::find($userId);
        return $user ? password_verify($password, $user['password_hash']) : false;
    }

    /** Change le mot de passe d'un utilisateur */
    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }

    /** Le compte a-t-il déjà défini un code PIN de sécurité ? */
    public static function hasPin(int $userId): bool
    {
        $stmt = db()->prepare('SELECT pin_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return !empty($stmt->fetchColumn());
    }

    /** Définit ou remplace le code PIN de sécurité d'un utilisateur */
    public static function setPin(int $userId, string $pin): bool
    {
        $stmt = db()->prepare('UPDATE users SET pin_hash = ? WHERE id = ?');
        return $stmt->execute([password_hash($pin, PASSWORD_DEFAULT), $userId]);
    }

    /** Vérifie le code PIN saisi par rapport à celui enregistré */
    public static function verifyPin(int $userId, string $pin): bool
    {
        $stmt = db()->prepare('SELECT pin_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        return $hash ? password_verify($pin, $hash) : false;
    }

    /** Retire le code PIN de sécurité (le client devra en redéfinir un) */
    public static function clearPin(int $userId): bool
    {
        $stmt = db()->prepare('UPDATE users SET pin_hash = NULL WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    /** Solde actuel d'un utilisateur */
    public static function getBalance(int $userId): float
    {
        $stmt = db()->prepare('SELECT balance FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return (float) $stmt->fetchColumn();
    }

    /** Solde épargne actuel d'un utilisateur (dossier Épargne) */
    public static function getEpargneBalance(int $userId): float
    {
        $stmt = db()->prepare('SELECT epargne_balance FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return (float) $stmt->fetchColumn();
    }

    /** Représentation publique (sans mot de passe) pour l'envoyer au front-end / à la session */
    public static function toPublic(array $user): array
    {
        return [
            'id'       => (int) $user['id'],
            'name'     => $user['name'],
            'ninu'     => $user['ninu'],
            'phone'    => $user['phone'],
            'email'    => $user['email'],
            'ville'    => $user['ville'],
            'pays'     => $user['pays'] ?? 'Haïti',
            'sexe'     => $user['sexe'] ?? null,
            'age'      => isset($user['age']) ? (int) $user['age'] : null,
            'balance'  => (float) $user['balance'],
            'epargne_balance' => (float) ($user['epargne_balance'] ?? 0),
            'pin_set'  => !empty($user['pin_hash']),
            'role'     => $user['role'] ?? 'client',
            'is_admin'    => self::isAdminRow($user),
            'is_employee' => self::isEmployeeRow($user),
            'permissions'    => self::permissionsArray($user),
            'is_super_admin' => self::permissionsArray($user) === null,
            'photo_path' => $user['photo_path'] ?? null,
            'photo_url'  => !empty($user['photo_path']) ? url($user['photo_path']) : null,
        ];
    }

    /** Le compte (ligne brute de la table users) a-t-il le rôle administrateur ? */
    public static function isAdminRow(array $user): bool
    {
        return ($user['role'] ?? 'client') === 'admin';
    }

    /** Le compte (ligne brute de la table users) a-t-il le rôle employé ? */
    public static function isEmployeeRow(array $user): bool
    {
        return ($user['role'] ?? 'client') === 'employe';
    }

    /**
     * Retire du tableau de permissions les modules qu'un employé ne peut
     * jamais recevoir (gestion des administrateurs et des employés
     * eux-mêmes — voir staff_restricted_modules() dans config.php).
     * Un compte employé n'a par ailleurs jamais un accès "complet" (null) :
     * appeler cette méthode avec $permissions = null renvoie donc un
     * tableau vide plutôt que null.
     */
    public static function sanitizeEmployeePermissions(?array $permissions): array
    {
        if ($permissions === null) {
            return [];
        }
        return array_values(array_diff($permissions, staff_restricted_modules()));
    }

    /* =========================================================
     * Permissions d'accès aux modules de l'administration
     * ========================================================= */

    /**
     * Convertit la colonne brute users.permissions en tableau exploitable.
     * - Colonne NULL (ou clé absente)  => null  (accès complet / super admin)
     * - Colonne ''                     => []    (aucun module, dashboard seul)
     * - Colonne "clients,billets"      => ['clients', 'billets']
     */
    public static function permissionsArray(array $user): ?array
    {
        if (!array_key_exists('permissions', $user) || $user['permissions'] === null) {
            return null;
        }
        $raw = trim((string) $user['permissions']);
        if ($raw === '') {
            return [];
        }
        $keys = array_map('trim', explode(',', $raw));
        return array_values(array_filter($keys, fn($k) => $k !== ''));
    }

    /** Convertit un tableau de permissions (ou null = accès complet) en valeur à stocker. */
    public static function permissionsToStorage(?array $permissions): ?string
    {
        if ($permissions === null) {
            return null;
        }
        $valid = array_keys(admin_permission_modules());
        $clean = array_values(array_unique(array_intersect(array_map('trim', $permissions), $valid)));
        return implode(',', $clean);
    }

    /** Met à jour uniquement les permissions d'un compte administrateur. */
    public static function setPermissions(int $id, ?array $permissions): bool
    {
        $stmt = db()->prepare('UPDATE users SET permissions = ? WHERE id = ?');
        return $stmt->execute([self::permissionsToStorage($permissions), $id]);
    }

    /* =========================================================
     * Méthodes réservées à la partie administration (/admin)
     * ========================================================= */

    /** Tous les comptes clients (hors administrateurs), du plus récent au plus ancien.
     *  $search filtre sur le nom, l'email, le téléphone ou le NINU. */
    public static function allClients(?string $search = null): array
    {
        $sql = "SELECT * FROM users WHERE role = 'client'";
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (name LIKE :s OR email LIKE :s OR phone LIKE :s OR ninu LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Nombre total de comptes clients (hors administrateurs) */
    public static function countClients(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    }

    /** Somme des soldes de tous les clients (vision globale de la masse monétaire en circulation) */
    public static function totalBalanceClients(): float
    {
        return (float) db()->query("SELECT COALESCE(SUM(balance), 0) FROM users WHERE role = 'client'")->fetchColumn();
    }

    /** Somme des soldes épargne de tous les clients (dossier Épargne) */
    public static function totalEpargneClients(): float
    {
        return (float) db()->query("SELECT COALESCE(SUM(epargne_balance), 0) FROM users WHERE role = 'client'")->fetchColumn();
    }

    /**
     * Top clients par solde courant, du plus élevé au plus faible — pour le
     * graphique "Top clients par solde" du dossier Statistiques.
     */
    public static function topClientsByBalance(int $limit = 5): array
    {
        $stmt = db()->prepare(
            "SELECT name, ville, balance FROM users WHERE role = 'client' ORDER BY balance DESC LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Répartition du nombre de clients par ville, triée par nombre décroissant —
     * pour le graphique "Provenance des clients" du dossier Statistiques.
     * $limit regroupe les villes les moins représentées sous "Autres" au-delà
     * de ce nombre.
     */
    public static function repartitionParVille(int $limit = 8): array
    {
        $stmt = db()->query(
            "SELECT COALESCE(NULLIF(TRIM(ville), ''), 'Non renseignée') AS ville, COUNT(*) AS nb
             FROM users
             WHERE role = 'client'
             GROUP BY ville
             ORDER BY nb DESC"
        );
        $rows = $stmt->fetchAll();

        $result = [];
        $autres = 0;
        foreach ($rows as $i => $row) {
            if ($i < $limit) {
                $result[$row['ville']] = (int) $row['nb'];
            } else {
                $autres += (int) $row['nb'];
            }
        }
        if ($autres > 0) {
            $result['Autres'] = $autres;
        }
        return $result;
    }

    /**
     * Nombre de nouveaux clients inscrits par mois, sur les N derniers mois
     * (pour le graphique "Nouveaux clients par mois" du dossier Statistiques).
     * Retourne un tableau ordonné du plus ancien au plus récent, un mois sans
     * inscription valant 0.
     */
    public static function inscriptionsParMois(int $months = 12): array
    {
        $stmt = db()->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb
             FROM users
             WHERE role = 'client' AND created_at >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL :m MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
        );
        $stmt->bindValue(':m', $months - 1, PDO::PARAM_INT);
        $stmt->execute();
        $parMois = [];
        foreach ($stmt->fetchAll() as $row) {
            $parMois[$row['mois']] = (int) $row['nb'];
        }

        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $timestamp = strtotime("first day of -{$i} months");
            $key = date('Y-m', $timestamp);
            $result[] = [
                'label' => $moisLabels[(int) date('n', $timestamp) - 1] . ' ' . date('y', $timestamp),
                'total' => $parMois[$key] ?? 0,
            ];
        }
        return $result;
    }

    /* =========================================================
     * Dossier de gestion des administrateurs : /admin/administrateurs
     * ========================================================= */

    /** Tous les comptes administrateurs, du plus récent au plus ancien.
     *  $search filtre sur le nom, l'email, le téléphone ou le NINU. */
    public static function allAdmins(?string $search = null): array
    {
        $sql = "SELECT * FROM users WHERE role = 'admin'";
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (name LIKE :s OR email LIKE :s OR phone LIKE :s OR ninu LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Nombre total de comptes administrateurs */
    public static function countAdmins(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    /** Accorde le statut administrateur à un compte existant (promotion d'un client) */
    public static function promote(int $id): bool
    {
        $stmt = db()->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Retire le statut administrateur (ou employé) d'un compte (rétrogradation en client) */
    public static function demote(int $id): bool
    {
        $stmt = db()->prepare("UPDATE users SET role = 'client' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /* =========================================================
     * Dossier de gestion des employés : /admin/employes
     * Un employé peut se connecter à l'espace /admin (voir is_staff()/
     * require_staff() dans config.php), mais son accès reste TOUJOURS
     * limité aux modules listés dans ses permissions (jamais NULL/complet).
     * ========================================================= */

    /** Tous les comptes employés, du plus récent au plus ancien.
     *  $search filtre sur le nom, l'email, le téléphone ou le NINU. */
    public static function allEmployees(?string $search = null): array
    {
        $sql = "SELECT * FROM users WHERE role = 'employe'";
        $params = [];
        if ($search !== null && trim($search) !== '') {
            $sql .= ' AND (name LIKE :s OR email LIKE :s OR phone LIKE :s OR ninu LIKE :s)';
            $params['s'] = '%' . trim($search) . '%';
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Nombre total de comptes employés */
    public static function countEmployees(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'employe'")->fetchColumn();
    }

    /** Accorde le statut employé à un compte existant (promotion d'un client) */
    public static function promoteEmployee(int $id): bool
    {
        $stmt = db()->prepare("UPDATE users SET role = 'employe' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Supprime définitivement un compte (les transactions liées sont conservées,
     *  sender_id/receiver_id passent à NULL grâce aux contraintes ON DELETE SET NULL) */
    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /** Met à jour les informations d'un compte administrateur, avec changement de
     *  mot de passe optionnel (laisser vide dans $data['password'] pour le conserver). */
    public static function updateProfile(int $id, array $data): bool
    {
        $sql = 'UPDATE users SET name = :name, ninu = :ninu, sexe = :sexe, age = :age,
                ville = :ville, pays = :pays, phone = :phone, email = :email';
        $params = [
            'id'    => $id,
            'name'  => $data['name'],
            'ninu'  => $data['ninu'],
            'sexe'  => $data['sexe'],
            'age'   => $data['age'],
            'ville' => $data['ville'],
            'pays'  => $data['pays'],
            'phone' => $data['phone'],
            'email' => strtolower($data['email']),
        ];
        if (!empty($data['password'])) {
            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        // photo_path n'est mis à jour que si la clé est explicitement présente dans
        // $data (upload d'une nouvelle photo, ou retrait volontaire = null) ; son
        // absence du tableau signifie "ne pas toucher à la photo actuelle".
        if (array_key_exists('photo_path', $data)) {
            $sql .= ', photo_path = :photo_path';
            $params['photo_path'] = $data['photo_path'];
        }
        $sql .= ' WHERE id = :id';
        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    }

    /* =========================================================
     * Photo de profil (inscription client ou fiche gérée par un admin)
     * ========================================================= */

    /**
     * Valide et déplace la photo de profil envoyée via un formulaire
     * ($_FILES['photo'] ou équivalent) vers /public/assets/uploads/users/.
     * Retourne ['path' => chemin relatif ou null, 'error' => message ou null].
     * Un fichier absent (champ facultatif) n'est pas une erreur.
     */
    public static function processPhotoUpload(array $file): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => "L'envoi de la photo a échoué. Veuillez réessayer."];
        }

        $maxSize = 4 * 1024 * 1024; // 4 Mo
        if ((int) $file['size'] > $maxSize) {
            return ['path' => null, 'error' => 'La photo ne doit pas dépasser 4 Mo.'];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['path' => null, 'error' => "Le fichier envoyé n'est pas une image valide."];
        }

        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
        ];
        if (!isset($allowedTypes[$info[2]])) {
            return ['path' => null, 'error' => "Formats d'image acceptés : JPG, PNG, GIF ou WEBP."];
        }

        $dir = ROOT_PATH . '/public/assets/uploads/users';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['path' => null, 'error' => "Impossible de créer le dossier de stockage des photos."];
        }

        $filename = 'user-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $allowedTypes[$info[2]];
        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['path' => null, 'error' => "Impossible d'enregistrer la photo envoyée."];
        }

        return ['path' => 'assets/uploads/users/' . $filename, 'error' => null];
    }

    /** Supprime le fichier d'une ancienne photo de profil, remplacée ou retirée */
    public static function deletePhotoFile(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'assets/uploads/users/')) {
            return;
        }
        $full = ROOT_PATH . '/public/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
