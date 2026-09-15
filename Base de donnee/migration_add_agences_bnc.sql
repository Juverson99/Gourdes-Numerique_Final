-- migration_add_agences_bnc.sql
-- À exécuter UNE SEULE FOIS sur une base "gourde_numerique" déjà existante,
-- pour ajouter le dossier des agences BNC (carte interactive publique).
--
-- Utilisation :
--   phpMyAdmin -> coller/exécuter (fonctionne peu importe la base sélectionnée)
--   OU : mysql -u root gourde_numerique < migration_add_agences_bnc.sql
--
-- Rejouable sans risque : CREATE TABLE IF NOT EXISTS ne recrée pas la table
-- si elle existe déjà, et INSERT IGNORE ignore les agences déjà présentes
-- (grâce à la contrainte unique sur le nom).

USE gourde_numerique;

CREATE TABLE IF NOT EXISTS bnc_agences (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom            VARCHAR(150) NOT NULL COMMENT 'Nom de l''agence (ex : BNC Delmas 33)',
  departement    VARCHAR(60)  NOT NULL COMMENT 'Département administratif d''Haïti',
  ville          VARCHAR(100) NOT NULL,
  adresse        VARCHAR(255) NULL,
  telephone      VARCHAR(30)  NULL,
  latitude       DECIMAL(10,7) NOT NULL,
  longitude      DECIMAL(10,7) NOT NULL,
  principale     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Siège / agence principale (mise en avant sur la carte)',
  sort_order     INT NOT NULL DEFAULT 0,
  active         TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Visible ou non sur la carte publique',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT chk_bnca_lat CHECK (latitude BETWEEN 17.5 AND 20.5),
  CONSTRAINT chk_bnca_lng CHECK (longitude BETWEEN -75.0 AND -71.0),

  INDEX idx_bnca_active_sort (active, sort_order),
  INDEX idx_bnca_departement (departement),
  UNIQUE KEY uq_bnca_nom (nom)
) ENGINE=InnoDB;

-- Répertoire de départ : une agence représentative par grande ville, répartie
-- sur les 10 départements d'Haïti (à ajuster/compléter depuis l'admin avec
-- les adresses et coordonnées exactes des vraies agences BNC).
INSERT IGNORE INTO bnc_agences (nom, departement, ville, adresse, telephone, latitude, longitude, principale, sort_order) VALUES
('BNC Siège Central — Champ de Mars',  'Ouest',        'Port-au-Prince', 'Angle rues du Champ de Mars et Monseigneur Guilloux', '2299-4000', 18.5453, -72.3402, 1, 10),
('BNC Delmas 33',                      'Ouest',        'Delmas',         'Route de Delmas, Delmas 33',                          '2299-4033', 18.5459, -72.3033, 0, 20),
('BNC Pétionville',                    'Ouest',        'Pétionville',    'Rue Grégoire, Pétionville',                            '2299-4050', 18.5136, -72.2852, 0, 30),
('BNC Cap-Haïtien',                    'Nord',         'Cap-Haïtien',    'Rue 17 A-B, Cap-Haïtien',                              '2262-0100', 19.7592, -72.2014, 0, 40),
('BNC Les Cayes',                      'Sud',          'Les Cayes',      'Boulevard des Cayes',                                  '2286-0200', 18.1959, -73.7499, 0, 50),
('BNC Gonaïves',                       'Artibonite',   'Gonaïves',       'Rue Egalité, Gonaïves',                                '2274-0300', 19.4515, -72.6891, 0, 60),
('BNC Fort-Liberté',                   'Nord-Est',     'Fort-Liberté',   'Rue du Commerce, Fort-Liberté',                        '2255-0400', 19.6633, -71.8397, 0, 70),
('BNC Port-de-Paix',                   'Nord-Ouest',   'Port-de-Paix',   'Rue Notre-Dame, Port-de-Paix',                         '2244-0500', 19.9403, -72.8352, 0, 80),
('BNC Hinche',                         'Centre',       'Hinche',         'Rue Trou, Hinche',                                     '2233-0600', 19.1503, -72.0086, 0, 90),
('BNC Jacmel',                         'Sud-Est',      'Jacmel',         'Rue du Commerce, Jacmel',                              '2288-0700', 18.2341, -72.5347, 0, 100),
('BNC Jérémie',                        'Grand''Anse',  'Jérémie',        'Rue Sténio Vincent, Jérémie',                          '2277-0800', 18.6493, -74.1178, 0, 110),
('BNC Miragoâne',                      'Nippes',       'Miragoâne',      'Rue Séïde Fils-Aimé, Miragoâne',                       '2255-0900', 18.4479, -73.0918, 0, 120);
