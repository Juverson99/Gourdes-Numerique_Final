-- migration_add_money_requests.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour activer la fonctionnalité "Demander de l'argent à un client" sur le
-- dossier client /recevoir (icône d'alerte + acceptation/refus).
--
-- Utilisation :
--   1) phpMyAdmin -> base "gourde_numerique" -> onglet SQL -> coller/exécuter
--   OU
--   2) mysql -u root gourde_numerique < migration_add_money_requests.sql

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS money_requests (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference      VARCHAR(40)  NOT NULL UNIQUE,
  requester_id   INT UNSIGNED NOT NULL COMMENT 'Client qui demande à recevoir l''argent',
  target_id      INT UNSIGNED NOT NULL COMMENT 'Client à qui l''argent est demandé',
  amount         DECIMAL(14,2) NOT NULL,
  message        VARCHAR(255) NULL COMMENT 'Motif optionnel de la demande',
  status         ENUM('en_attente','acceptee','refusee','annulee') NOT NULL DEFAULT 'en_attente',
  transaction_id INT UNSIGNED NULL COMMENT 'Transaction d''envoi créée si la demande est acceptée',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT chk_mr_amount     CHECK (amount > 0),
  CONSTRAINT fk_mr_requester   FOREIGN KEY (requester_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mr_target      FOREIGN KEY (target_id)      REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mr_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,

  INDEX idx_mr_requester (requester_id),
  INDEX idx_mr_target (target_id),
  INDEX idx_mr_status (status)
) ENGINE=InnoDB;
