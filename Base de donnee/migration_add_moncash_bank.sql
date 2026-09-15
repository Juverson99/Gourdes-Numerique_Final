-- migration_add_moncash_bank.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour activer le dossier client "Convertir" (MonCash -> billet électronique,
-- billet électronique -> compte bancaire).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_moncash_bank.sql
--
-- Si les migrations migration_add_epargne.sql et/ou
-- migration_add_client_features.sql n'ont pas encore été appliquées, la
-- liste ENUM ci-dessous les inclut déjà : ce script peut être exécuté
-- indépendamment de l'ordre des autres migrations.

USE gourde_numerique;

ALTER TABLE transactions
  MODIFY type ENUM(
    'envoi','reception','paiement','depot',
    'epargne_depot','epargne_retrait',
    'moncash_depot','retrait_bancaire'
  ) NOT NULL;
