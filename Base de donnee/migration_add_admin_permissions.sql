-- migration_add_admin_permissions.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante
-- (créée avant l'ajout de la gestion des permissions par administrateur).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_admin_permissions.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
--
-- NULL (valeur par défaut) = accès complet (super administrateur), donc tous
-- les comptes administrateurs déjà existants conservent leur accès total
-- après cette migration, sans aucune action supplémentaire à faire.
ALTER TABLE users
  ADD COLUMN permissions TEXT NULL DEFAULT NULL
  COMMENT 'Permissions d''un compte admin : NULL = accès complet (super administrateur), liste de clés séparées par des virgules sinon (ex. "clients,billets"). Sans effet pour un compte client.'
  AFTER role;
