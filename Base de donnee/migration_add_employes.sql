-- migration_add_employes.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter le rôle "employé" : un compte du personnel qui peut se
-- connecter à l'espace administration (/admin) mais dont l'accès est
-- TOUJOURS limité aux modules explicitement autorisés (jamais un accès
-- complet de super administrateur, contrairement à un compte role='admin'
-- avec permissions = NULL).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_employes.sql
--
-- Rejouable sans risque : MODIFY est idempotent.

USE gourde_numerique;

ALTER TABLE users
  MODIFY role ENUM('client','admin','employe') NOT NULL DEFAULT 'client'
  COMMENT 'Rôle du compte : "client" (usage courant), "admin" (accès complet possible à /admin) ou "employe" (accès à /admin limité aux modules autorisés, jamais un accès complet)';
