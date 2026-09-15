-- migration_add_retrait_especes.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour activer la conversion "Billet électronique -> Billet physique"
-- (retrait en espèces dans une agence BNC) sur le dossier client
-- "Convertir" et le nouveau dossier admin "Retraits en espèces".
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_retrait_especes.sql
--
-- La liste ENUM ci-dessous inclut déjà toutes les valeurs des migrations
-- précédentes : ce script peut être exécuté indépendamment de leur ordre.

USE gourde_numerique;

ALTER TABLE transactions
  MODIFY type ENUM(
    'envoi','reception','paiement','depot',
    'epargne_depot','epargne_retrait',
    'moncash_depot','retrait_bancaire','retrait_especes'
  ) NOT NULL;
