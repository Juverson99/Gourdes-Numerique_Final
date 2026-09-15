-- migration_add_stock_billets.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter la gestion de stock des billets (quantité en réserve).
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_stock_billets.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE bank_notes
  ADD COLUMN stock_quantity INT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Quantité de billets disponibles en réserve (stock)'
  AFTER image_path;
