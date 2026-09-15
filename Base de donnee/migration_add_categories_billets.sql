-- migration_add_categories_billets.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter les catégories de billets (regroupement des coupures).
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_categories_billets.sql

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS bank_note_categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL UNIQUE COMMENT 'Nom de la catégorie (ex : Petites coupures)',
  description VARCHAR(255) NULL COMMENT 'Description facultative affichée en admin',
  sort_order  INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage des catégories',
  active      TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Catégorie utilisable ou archivée',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bnc_active_sort (active, sort_order)
) ENGINE=InnoDB;

-- Quelques catégories de départ (ne fait rien si elles existent déjà)
INSERT IGNORE INTO bank_note_categories (name, description, sort_order) VALUES
('Petites coupures',  'Billets de faible valeur, utilisés pour l''appoint courant', 10),
('Coupures moyennes', 'Billets de valeur intermédiaire, usage quotidien', 20),
('Grosses coupures',  'Billets de forte valeur, gros montants', 30),
('Paquets de billets','Regroupements de plusieurs billets identiques', 40);

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE bank_notes
  ADD COLUMN category_id INT UNSIGNED NULL
  COMMENT 'Catégorie de regroupement (facultative)'
  AFTER bundle_qty;

-- Si la contrainte/l'index existe déjà, MySQL renverra une erreur "Duplicate
-- key name" ou similaire : normal également, ignorez et passez à la suite.
ALTER TABLE bank_notes
  ADD CONSTRAINT fk_bn_category FOREIGN KEY (category_id)
  REFERENCES bank_note_categories(id) ON DELETE SET NULL;

ALTER TABLE bank_notes ADD INDEX idx_bn_category (category_id);
