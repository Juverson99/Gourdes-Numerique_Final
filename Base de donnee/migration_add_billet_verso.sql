-- migration_add_billet_verso.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante
-- (créée avant le passage de la fonctionnalité "paquet de billets" à la
-- fonctionnalité "recto / verso" pour chaque billet).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_billet_verso.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE bank_notes
  ADD COLUMN image_verso_path VARCHAR(255) NULL
  COMMENT 'Chemin relatif (depuis /public) de l''image du VERSO (dos) du billet, envoyée par l''admin'
  AFTER image_path;

-- Remarque : les colonnes is_bundle / bundle_qty (ancienne fonctionnalité
-- "paquet de billets") restent en base pour ne pas casser les données
-- existantes, mais ne sont plus modifiables depuis le formulaire d'admin :
-- chaque billet est désormais géré comme une coupure simple, avec une
-- image recto (avant) et une image verso (arrière) facultatives.
