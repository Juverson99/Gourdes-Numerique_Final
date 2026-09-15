-- migration_add_client_features.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour activer les dossiers client "Sécurité" (code PIN) et "Cartes virtuelles".
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_client_features.sql

USE gourde_numerique;

-- Code PIN de sécurité (4 à 6 chiffres, haché comme le mot de passe).
-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE users
  ADD COLUMN pin_hash VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Hash du code PIN de sécurité (dossier "Sécurité"), NULL = pas encore défini'
  AFTER password_hash;

-- ------------------------------------------------------------
-- Table : virtual_cards
-- Cartes virtuelles générées par un client pour ses achats en ligne
-- (dossier client "Cartes virtuelles").
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS virtual_cards (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  label        VARCHAR(60)  NOT NULL DEFAULT 'Carte virtuelle',
  card_number  VARCHAR(19)  NOT NULL COMMENT 'Numéro à 16 chiffres, formaté par groupes de 4',
  holder_name  VARCHAR(150) NOT NULL,
  expiry_month TINYINT UNSIGNED NOT NULL,
  expiry_year  SMALLINT UNSIGNED NOT NULL,
  cvv          CHAR(3)      NOT NULL,
  status       ENUM('active','frozen') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_vc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_vc_user (user_id)
) ENGINE=InnoDB;
