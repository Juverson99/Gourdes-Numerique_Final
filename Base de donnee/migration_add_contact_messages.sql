-- migration_add_contact_messages.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter la page publique /contact (formulaire de contact) et son
-- dossier de gestion en admin (/admin/messages).
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_contact_messages.sql

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS contact_messages (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  phone          VARCHAR(30)  NULL,
  subject        VARCHAR(150) NOT NULL,
  message        TEXT NOT NULL,
  status         ENUM('nouveau','lu','traite') NOT NULL DEFAULT 'nouveau' COMMENT 'Statut de traitement du message',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_cm_status (status),
  INDEX idx_cm_created (created_at)
) ENGINE=InnoDB;
