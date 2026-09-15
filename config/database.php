<?php
/**
 * config/database.php
 * Connexion PDO à la base de données MySQL "gourde_numerique".
 *
 * Adapter les constantes ci-dessous à votre environnement local
 * (ou les définir via des variables d'environnement en production).
 */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'gourde_numerique');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO unique (pattern singleton simple).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Préparations "émulées" côté PHP (et non natives côté serveur
                // MySQL) : plusieurs requêtes de recherche de l'application
                // réutilisent volontairement le même paramètre nommé (:s, :q…)
                // à plusieurs endroits d'une même requête avec une seule valeur
                // fournie. C'est valide avec des préparations émulées, mais pas
                // avec des préparations natives, qui exigent une valeur par
                // occurrence et renvoient "SQLSTATE[HY093]: Invalid parameter
                // number" sinon. À laisser à true tant que ce style de requête
                // est utilisé dans le code.
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Erreur de connexion à la base de données. Vérifiez config/database.php. (' . $e->getMessage() . ')');
        }
        ensure_schema($pdo);
    }

    return $pdo;
}

/**
 * Auto-réparation légère du schéma : ajoute automatiquement une colonne
 * introduite par un fichier migration_*.sql si elle manque encore sur cette
 * base (cas d'une mise à jour du code déployée sans avoir rejoué la
 * migration correspondante). Vérifié une seule fois par connexion (donc une
 * seule fois par requête HTTP, via le singleton ci-dessus) grâce à
 * INFORMATION_SCHEMA, sans jamais toucher à une colonne déjà présente.
 * Silencieux en cas d'échec (table absente, droits insuffisants...) : la
 * fonctionnalité concernée signalera alors l'erreur normalement à l'usage.
 */
function ensure_schema(PDO $pdo): void
{
    $checks = [
        // [table, colonne, DDL d'ajout] — un par migration facultative connue
        ['bank_notes', 'stock_quantity', "ALTER TABLE bank_notes ADD COLUMN stock_quantity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantité de billets disponibles en réserve (stock)' AFTER image_path"],
    ];

    foreach ($checks as [$table, $column, $ddl]) {
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
            );
            $stmt->execute(['table' => $table, 'column' => $column]);
            if ((int) $stmt->fetchColumn() === 0) {
                $pdo->exec($ddl);
            }
        } catch (Throwable $e) {
            // Table absente ou droits ALTER manquants : on n'interrompt jamais
            // le chargement de la page pour ça, voir le commentaire ci-dessus.
        }
    }

    // Auto-ajout de bnc_agences.institution_id (lien agence -> institution
    // bancaire du répertoire "financial_institutions", voir
    // migration_add_agences_institution.sql) si la migration n'a pas encore
    // été rejouée sur cette base. Sans cette colonne, les dossiers admin
    // "Institutions financières" et "Agences BNC" plantent (colonne
    // inconnue) dès qu'ils tentent de lire ou de joindre cette relation.
    // Rattache aussi automatiquement les agences BNC existantes à
    // l'institution "BNC" du répertoire, si elle y est déjà déclarée —
    // comme le fait la migration elle-même.
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => 'bnc_agences', 'column' => 'institution_id']);
        if ((int) $stmt->fetchColumn() === 0) {
            $pdo->exec(
                'ALTER TABLE bnc_agences
                   ADD COLUMN institution_id INT UNSIGNED NULL AFTER id,
                   ADD INDEX idx_bnca_institution (institution_id),
                   ADD CONSTRAINT fk_bnca_institution FOREIGN KEY (institution_id)
                     REFERENCES financial_institutions(id) ON DELETE SET NULL'
            );
            $pdo->exec(
                "UPDATE bnc_agences a
                 JOIN financial_institutions fi
                   ON fi.type = 'banque' AND (fi.name = 'BNC' OR fi.name LIKE 'BNC %' OR fi.license_number LIKE 'BNC-%')
                 SET a.institution_id = fi.id
                 WHERE a.institution_id IS NULL"
            );
        }
    } catch (Throwable $e) {
        // Table bnc_agences ou financial_institutions absente, ou droits ALTER
        // manquants : on n'interrompt jamais le chargement de la page pour ça.
    }

    // Auto-création de la table login_attempts (protection anti-force-brute
    // sur la connexion) si la migration n'a pas encore été rejouée : cette
    // table de sécurité doit être disponible même sur un déploiement qui a
    // oublié migration_add_login_security.sql.
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS login_attempts (
                identifier        VARCHAR(190) NOT NULL PRIMARY KEY,
                attempts          INT UNSIGNED NOT NULL DEFAULT 1,
                first_attempt_at  DATETIME NOT NULL,
                locked_until      DATETIME NULL,
                INDEX idx_la_locked_until (locked_until)
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) {
        // Droits CREATE TABLE manquants : le formulaire de connexion continue
        // de fonctionner, simplement sans throttle tant que la table n'existe pas.
    }

    // Auto-création de la table money_requests ("Demander de l'argent à un
    // client", dossier /recevoir + icône d'alerte dans l'en-tête) si la
    // migration migration_add_money_requests.sql n'a pas encore été rejouée.
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS money_requests (
                id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reference      VARCHAR(40)  NOT NULL UNIQUE,
                requester_id   INT UNSIGNED NOT NULL,
                target_id      INT UNSIGNED NOT NULL,
                amount         DECIMAL(14,2) NOT NULL,
                message        VARCHAR(255) NULL,
                status         ENUM('en_attente','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
                transaction_id INT UNSIGNED NULL,
                created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                CONSTRAINT chk_mr_amount     CHECK (amount > 0),
                CONSTRAINT fk_mr_requester   FOREIGN KEY (requester_id)   REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_mr_target      FOREIGN KEY (target_id)      REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_mr_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,

                INDEX idx_mr_requester (requester_id),
                INDEX idx_mr_target (target_id),
                INDEX idx_mr_status (status)
            ) ENGINE=InnoDB"
        );
    } catch (Throwable $e) {
        // Table users/transactions absente ou droits CREATE TABLE manquants :
        // on n'interrompt jamais le chargement de la page pour ça — la
        // fonctionnalité "Demander de l'argent" signalera l'erreur normalement
        // à l'usage tant que la table n'existe pas.
    }
}
