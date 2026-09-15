-- migration_add_user_photo.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante
-- (créée avant l'ajout de la photo de profil pour les clients/administrateurs).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_user_photo.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE users
  ADD COLUMN photo_path VARCHAR(255) NULL
  COMMENT 'Chemin relatif (depuis /public) de la photo de profil envoyée à l''inscription ou par un admin'
  AFTER is_merchant;
