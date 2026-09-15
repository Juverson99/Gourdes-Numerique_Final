-- ------------------------------------------------------------
-- Migration : ajout du dossier "Institutions financières"
-- Répertoire des banques/coopératives/EMI partenaires de la plateforme
-- Gourde Numérique, géré depuis /admin/institutions.
-- À exécuter sur une base existante (ajout sans perte de données).
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS financial_institutions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150)  NOT NULL COMMENT 'Nom de l''institution (ex : Banque Nationale de Crédit)',
  type           ENUM('banque','cooperative','emi','autre') NOT NULL DEFAULT 'banque' COMMENT 'Type d''institution financière',
  license_number VARCHAR(80)   NULL COMMENT 'Numéro de licence / agrément délivré par la BRH',
  contact_name   VARCHAR(150)  NULL COMMENT 'Nom du responsable / point de contact chez le partenaire',
  phone          VARCHAR(30)   NULL,
  email          VARCHAR(150)  NULL,
  ville          VARCHAR(100)  NULL,
  address        VARCHAR(255)  NULL,
  logo_path      VARCHAR(255)  NULL COMMENT 'Chemin relatif (depuis /public) du logo envoyé par l''admin',
  status         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif' COMMENT 'Statut du partenariat',
  notes          TEXT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_fi_license (license_number),
  INDEX idx_fi_status (status),
  INDEX idx_fi_type (type)
) ENGINE=InnoDB;
