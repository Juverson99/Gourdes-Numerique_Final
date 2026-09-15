-- migration_add_agences_institution.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante.
--
-- Objectif : lier chaque agence (bnc_agences) à l'institution bancaire
-- (financial_institutions) qui l'exploite, afin de pouvoir attribuer les
-- retraits en espèces effectués dans cette agence au montant total de la
-- bonne banque, dans le dossier admin "Institutions financières"
-- (/admin/institutions). Jusqu'ici les agences n'étaient pas rattachées à
-- une institution précise : le retrait en espèces (argent physique) n'était
-- donc jamais compté dans le montant reçu par la banque.
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_agences_institution.sql
--
-- Rejouable sans risque : la colonne n'est ajoutée que si elle n'existe pas
-- déjà, et le rattachement automatique ne touche que les agences non
-- encore liées (institution_id IS NULL).

USE gourde_numerique;

SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bnc_agences' AND COLUMN_NAME = 'institution_id'
);

SET @sql := IF(@col_exists = 0,
  'ALTER TABLE bnc_agences
     ADD COLUMN institution_id INT UNSIGNED NULL AFTER id,
     ADD INDEX idx_bnca_institution (institution_id),
     ADD CONSTRAINT fk_bnca_institution FOREIGN KEY (institution_id)
       REFERENCES financial_institutions(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rattachement automatique : toutes les agences existantes sont des agences
-- BNC (répertoire "bnc_agences"), donc on les relie à l'institution "BNC"
-- si elle existe déjà dans le répertoire des institutions financières.
UPDATE bnc_agences a
JOIN financial_institutions fi
  ON fi.type = 'banque' AND (fi.name = 'BNC' OR fi.name LIKE 'BNC %' OR fi.license_number LIKE 'BNC-%')
SET a.institution_id = fi.id
WHERE a.institution_id IS NULL;

-- Pour toute agence qui ne se serait pas rattachée automatiquement (nom de
-- l'institution différent de "BNC" dans le répertoire), un administrateur
-- peut la relier manuellement depuis /admin/agences-bnc (formulaire de
-- modification de l'agence, champ "Institution bancaire").
