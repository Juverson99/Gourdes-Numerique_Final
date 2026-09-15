-- migration_add_fournisseurs.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter le dossier "Fournisseurs" (fournisseurs de billets/liquidités,
-- imprimeurs, prestataires de matériel ou de services), géré depuis
-- /admin/fournisseurs.
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_fournisseurs.sql

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS suppliers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150)  NOT NULL COMMENT 'Nom du fournisseur (ex : Imprimerie Nationale)',
  category       ENUM('billets','materiel','services','autre') NOT NULL DEFAULT 'autre' COMMENT 'Catégorie de fourniture',
  tax_id         VARCHAR(80)   NULL COMMENT 'Numéro d''identification fiscale (NIF) ou matricule',
  contact_name   VARCHAR(150)  NULL COMMENT 'Nom du responsable / point de contact chez le fournisseur',
  phone          VARCHAR(30)   NULL,
  email          VARCHAR(150)  NULL,
  ville          VARCHAR(100)  NULL,
  address        VARCHAR(255)  NULL,
  logo_path      VARCHAR(255)  NULL COMMENT 'Chemin relatif (depuis /public) du logo envoyé par l''admin',
  status         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif' COMMENT 'Statut du partenariat',
  notes          TEXT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_sup_tax_id (tax_id),
  INDEX idx_sup_status (status),
  INDEX idx_sup_category (category)
) ENGINE=InnoDB;

-- Quelques fournisseurs de départ (ne fait rien si la table n'est pas vide
-- n'est pas vérifié automatiquement : à retirer si vous ne voulez pas de
-- données de démonstration).
INSERT INTO suppliers (name, category, tax_id, contact_name, phone, email, ville, status) VALUES
('Imprimerie Nationale d''Haïti', 'billets', 'NIF-0001-IMP', 'Service Production', '2222-3344', 'production@imprimerie.ht', 'Port-au-Prince', 'actif'),
('CashLog Transport & Sécurité', 'billets', 'NIF-0002-CLS', 'Direction des Opérations', '3344-5566', 'operations@cashlog.ht', 'Port-au-Prince', 'actif');
