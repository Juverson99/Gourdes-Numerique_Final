-- migration_add_epargne.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante
-- (créée avant l'ajout du dossier "Épargne" dans l'administration).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_epargne.sql

USE gourde_numerique;

-- Si la colonne existe déjà, MySQL renverra une erreur "Duplicate column
-- name" : c'est normal, cela veut dire que la migration a déjà été faite.
ALTER TABLE users
  ADD COLUMN epargne_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00
  COMMENT 'Solde du compte épargne (HTG), distinct du solde courant'
  AFTER balance,
  ADD CONSTRAINT chk_users_epargne CHECK (epargne_balance >= 0);

-- Ajoute les deux nouveaux types de mouvement (dépôt / retrait épargne)
-- à la liste déjà existante des types de transaction.
ALTER TABLE transactions
  MODIFY type ENUM('envoi','reception','paiement','depot','epargne_depot','epargne_retrait') NOT NULL;
