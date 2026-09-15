-- migration_add_image_path.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante
-- (créée avant l'ajout de la gestion d'image pour les billets).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_image_path.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE bank_notes
  ADD COLUMN image_path VARCHAR(255) NULL
  COMMENT 'Chemin relatif (depuis /public) de l''image du billet envoyée par l''admin'
  AFTER color_end;
