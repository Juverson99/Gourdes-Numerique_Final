-- ============================================================
-- Migration : catégories de paiement (marchands / factures)
-- À exécuter sur une base "gourde_numerique" déjà existante.
-- Permet de gérer depuis l'admin les catégories affichées sur la
-- page publique "Paiement" (Payez marchands et factures en un tap),
-- au lieu de les avoir codées en dur dans la vue.
-- ============================================================

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS payment_categories (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(80) NOT NULL UNIQUE COMMENT 'Nom affiché du marchand/service (ex : Électricité (EDH))',
  icon           VARCHAR(60) NOT NULL DEFAULT 'bi-shop' COMMENT 'Classe icône Bootstrap Icons (ex : bi-lightning-charge-fill)',
  sort_order     INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage des catégories (chips)',
  active         TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Catégorie utilisable ou archivée',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_pc_active_sort (active, sort_order)
) ENGINE=InnoDB;

-- Reprend les catégories qui étaient codées en dur dans la vue publique
-- de paiement, pour que rien ne change visuellement après la migration.
INSERT IGNORE INTO payment_categories (name, icon, sort_order) VALUES
('Électricité (EDH)',   'bi-lightning-charge-fill', 10),
('Eau (DINEPA)',        'bi-droplet-fill',          20),
('Internet & télécom',  'bi-wifi',                  30),
('Marchand',            'bi-shop',                  40),
('Transport',           'bi-bus-front-fill',        50),
('Éducation',           'bi-mortarboard-fill',      60);
