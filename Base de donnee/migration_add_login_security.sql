-- migration_add_login_security.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter la protection contre les attaques par force brute sur la
-- connexion (verrouillage temporaire après plusieurs échecs).
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_login_security.sql

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS login_attempts (
  identifier        VARCHAR(190) NOT NULL PRIMARY KEY COMMENT 'email + adresse IP (voir LoginThrottle::key)',
  attempts          INT UNSIGNED NOT NULL DEFAULT 1,
  first_attempt_at  DATETIME NOT NULL,
  locked_until      DATETIME NULL COMMENT 'NULL = pas verrouillé ; sinon date/heure de fin de verrouillage',
  INDEX idx_la_locked_until (locked_until)
) ENGINE=InnoDB;
