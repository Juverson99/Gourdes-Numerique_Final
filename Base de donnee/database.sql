-- ============================================================
-- Gourde Numérique — Schéma de base de données MySQL
-- Plateforme Nationale de Monnaie Numérique de la République d'Haïti
-- ============================================================

CREATE DATABASE IF NOT EXISTS gourde_numerique
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE gourde_numerique;

-- Réimport propre : supprime les tables existantes avant de les recréer,
-- pour que ce script puisse être rejoué sans erreur de doublon (ordre
-- inverse des clés étrangères : admin_activity_log et transactions
-- référencent users, donc elles sont supprimées avant).
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS admin_activity_log;
DROP TABLE IF EXISTS money_requests;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS payment_categories;
DROP TABLE IF EXISTS bank_notes;
DROP TABLE IF EXISTS bank_note_categories;
DROP TABLE IF EXISTS bnc_agences;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS financial_institutions;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Table : users
-- Comptes des citoyens/utilisateurs de la plateforme, y compris
-- les administrateurs (distingués par la colonne "role").
-- Un seul et même compte peut donc être client puis administrateur
-- (promotion) sans migration de données ni doublon.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ninu           VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Numéro d''Identification National Unique',
  name           VARCHAR(150) NOT NULL,
  sexe           ENUM('Homme','Femme','Autre') NOT NULL,
  age            TINYINT UNSIGNED NOT NULL,
  ville          VARCHAR(100) NOT NULL,
  pays           VARCHAR(100) NOT NULL DEFAULT 'Haïti',
  phone          VARCHAR(30)  NOT NULL UNIQUE,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  pin_hash       VARCHAR(255) NULL DEFAULT NULL COMMENT 'Hash du code PIN de sécurité, NULL = pas encore défini',
  balance        DECIMAL(14,2) NOT NULL DEFAULT 5000.00 COMMENT 'Solde en gourdes numériques (HTG)',
  epargne_balance DECIMAL(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Solde du compte épargne (HTG), distinct du solde courant',
  is_merchant    TINYINT(1) NOT NULL DEFAULT 0,
  photo_path     VARCHAR(255) NULL
                 COMMENT 'Chemin relatif (depuis /public) de la photo de profil envoyée à l''inscription ou par un admin',
  role           ENUM('client','admin','employe') NOT NULL DEFAULT 'client'
                 COMMENT 'Rôle du compte : "client" (usage courant), "admin" (accès complet possible à /admin) ou "employe" (accès à /admin limité aux modules autorisés, jamais un accès complet)',
  permissions    TEXT NULL DEFAULT NULL
                 COMMENT 'Permissions d''un compte admin/employé : NULL = accès complet (réservé aux administrateurs, jamais un employé), liste de clés séparées par des virgules sinon (ex. "clients,billets"). Sans effet pour un compte client.',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT chk_users_age     CHECK (age >= 18 AND age <= 120),
  CONSTRAINT chk_users_balance CHECK (balance >= 0),
  CONSTRAINT chk_users_epargne CHECK (epargne_balance >= 0),

  INDEX idx_users_role (role),
  INDEX idx_users_name (name)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : transactions
-- Historique de toutes les opérations (envoi, réception, paiement, dépôt)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference      VARCHAR(40)  NOT NULL UNIQUE,
  sender_id      INT UNSIGNED NULL,
  receiver_id    INT UNSIGNED NULL,
  type           ENUM('envoi','reception','paiement','depot','epargne_depot','epargne_retrait','moncash_depot','retrait_bancaire','retrait_especes') NOT NULL,
  amount         DECIMAL(14,2) NOT NULL,
  category       VARCHAR(100) NULL COMMENT 'Catégorie pour les paiements (ex : Électricité EDH)',
  note           VARCHAR(255) NULL,
  status         ENUM('reussi','echec','en_attente') NOT NULL DEFAULT 'reussi',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT chk_tx_amount   CHECK (amount > 0),
  CONSTRAINT fk_tx_sender    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_tx_receiver  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,

  INDEX idx_sender (sender_id),
  INDEX idx_receiver (receiver_id),
  INDEX idx_created (created_at),
  INDEX idx_tx_type (type),
  INDEX idx_tx_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : money_requests
-- Demandes d'argent d'un client vers un autre : le "demandeur" (requester)
-- veut recevoir un montant précis d'un autre client (target). Le client
-- sollicité voit la demande (icône d'alerte dans l'en-tête) et peut
-- l'accepter (ce qui déclenche un vrai envoi de son solde vers le
-- demandeur, comme un envoi classique) ou la refuser. Table volontairement
-- séparée de "transactions" : une demande n'est pas un mouvement d'argent
-- tant qu'elle n'est pas acceptée.
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Table : login_attempts
-- Protection contre les attaques par force brute sur la connexion (site
-- client ET admin, même formulaire) : verrouille temporairement une clé
-- (email + adresse IP) après plusieurs échecs consécutifs. Voir la classe
-- LoginThrottle (app/models/LoginThrottle.php).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS login_attempts (
  identifier        VARCHAR(190) NOT NULL PRIMARY KEY COMMENT 'email + adresse IP (voir LoginThrottle::key)',
  attempts          INT UNSIGNED NOT NULL DEFAULT 1,
  first_attempt_at  DATETIME NOT NULL,
  locked_until      DATETIME NULL COMMENT 'NULL = pas verrouillé ; sinon date/heure de fin de verrouillage',

  INDEX idx_la_locked_until (locked_until)
) ENGINE=InnoDB;

-- (Les catégories de paiement ont déjà leur propre table "payment_categories"
-- plus bas dans ce fichier, avec son propre jeu de données de départ —
-- rien à ajouter ici.)

-- ------------------------------------------------------------
-- Table : bank_notes
-- Dossier de gestion des billets (coupures) affichés sur la page
-- "Envoyer" (sélecteur visuel de billets, avec image recto + verso).
-- Gérée depuis l'admin : /admin/billets
-- ------------------------------------------------------------
-- ------------------------------------------------------------
-- Table : bank_note_categories
-- Catégories utilisées pour regrouper/organiser les coupures de billets
-- (ex. "Petites coupures", "Grosses coupures", "Paquets promotionnels").
-- Facultatif : une coupure peut ne pas avoir de catégorie.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS virtual_cards (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  label        VARCHAR(60)  NOT NULL DEFAULT 'Carte virtuelle',
  card_number  VARCHAR(19)  NOT NULL COMMENT 'Numéro à 16 chiffres, formaté par groupes de 4',
  holder_name  VARCHAR(150) NOT NULL,
  expiry_month TINYINT UNSIGNED NOT NULL,
  expiry_year  SMALLINT UNSIGNED NOT NULL,
  cvv          CHAR(3)      NOT NULL,
  status       ENUM('active','frozen') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_vc_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_vc_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bank_note_categories (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(80) NOT NULL UNIQUE COMMENT 'Nom de la catégorie (ex : Petites coupures)',
  description    VARCHAR(255) NULL COMMENT 'Description facultative affichée en admin',
  sort_order     INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage des catégories',
  active         TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Catégorie utilisable ou archivée',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_bnc_active_sort (active, sort_order)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : payment_categories
-- Catégories affichées (chips) sur la page publique "Paiement"
-- (Payez marchands et factures en un tap) : Électricité, Eau,
-- Internet, Marchand, Transport, Éducation... Gérée depuis l'admin
-- : /admin/categories-paiement
-- ------------------------------------------------------------
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

CREATE TABLE IF NOT EXISTS bank_notes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  unit_value     DECIMAL(12,2) NOT NULL COMMENT 'Valeur d''un billet unitaire (ex : 1, 5, 10, 100 HTG)',
  is_bundle      TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ancienne fonctionnalité "paquet de billets", conservée en base pour compatibilité mais plus utilisée (remplacée par le recto/verso)',
  bundle_qty     INT UNSIGNED NULL COMMENT 'Ancienne fonctionnalité "paquet de billets" (voir is_bundle), plus utilisée',
  category_id    INT UNSIGNED NULL COMMENT 'Catégorie de regroupement (facultative)',
  color_start    VARCHAR(7) NOT NULL DEFAULT '#1f6fb2' COMMENT 'Couleur de départ du dégradé (hex, utilisée si aucune image)',
  color_end      VARCHAR(7) NOT NULL DEFAULT '#164b7a' COMMENT 'Couleur de fin du dégradé (hex, utilisée si aucune image)',
  image_path     VARCHAR(255) NULL COMMENT 'Chemin relatif (depuis /public) de l''image du RECTO (avant) du billet, envoyée par l''admin',
  image_verso_path VARCHAR(255) NULL COMMENT 'Chemin relatif (depuis /public) de l''image du VERSO (arrière) du billet, envoyée par l''admin',
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantité de billets disponibles en réserve (stock)',
  sort_order     INT NOT NULL DEFAULT 0 COMMENT 'Ordre d''affichage sur la page Envoyer',
  active         TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Visible ou non sur la page Envoyer',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT chk_bn_unit_value CHECK (unit_value > 0),
  CONSTRAINT chk_bn_bundle_qty CHECK (is_bundle = 0 OR bundle_qty >= 2),
  CONSTRAINT uq_bn_value_type  UNIQUE (unit_value, is_bundle),
  CONSTRAINT fk_bn_category FOREIGN KEY (category_id) REFERENCES bank_note_categories(id) ON DELETE SET NULL,

  INDEX idx_bn_active_sort (active, sort_order),
  INDEX idx_bn_category (category_id)
) ENGINE=InnoDB;

-- Catégories de paiement affichées en chips sur la page publique "Paiement"
INSERT IGNORE INTO payment_categories (name, icon, sort_order) VALUES
('Électricité (EDH)',   'bi-lightning-charge-fill', 10),
('Eau (DINEPA)',        'bi-droplet-fill',          20),
('Internet & télécom',  'bi-wifi',                  30),
('Marchand',            'bi-shop',                  40),
('Transport',           'bi-bus-front-fill',        50),
('Éducation',           'bi-mortarboard-fill',      60);

INSERT INTO bank_note_categories (name, description, sort_order) VALUES
('Petites coupures',  'Billets de faible valeur, utilisés pour l''appoint courant', 10),
('Coupures moyennes', 'Billets de valeur intermédiaire, usage quotidien', 20),
('Grosses coupures',  'Billets de forte valeur, gros montants', 30),
('Paquets de billets','Regroupements de plusieurs billets identiques', 40);

INSERT INTO bank_notes (unit_value, is_bundle, bundle_qty, color_start, color_end, sort_order) VALUES
(1,    0, NULL,  '#8a8f98', '#565b63', 10),
(1,    1, 5000,  '#8a8f98', '#565b63', 20),
(5,    0, NULL,  '#3c7a9e', '#204f6b', 30),
(5,    1, 1000,  '#3c7a9e', '#204f6b', 40),
(10,   0, NULL,  '#c65a7a', '#8f3a55', 50),
(10,   1, 500,   '#c65a7a', '#8f3a55', 60),
(25,   0, NULL,  '#3f8f5f', '#215c3c', 70),
(25,   1, 200,   '#3f8f5f', '#215c3c', 80),
(50,   0, NULL,  '#6a4c93', '#432f63', 90),
(50,   1, 100,   '#6a4c93', '#432f63', 100),
(100,  0, NULL,  '#1f6fb2', '#164b7a', 110),
(100,  1, 50,    '#1f6fb2', '#164b7a', 120),
(250,  0, NULL,  '#d98a3d', '#a3611f', 130),
(250,  1, 20,    '#d98a3d', '#a3611f', 140),
(500,  0, NULL,  '#1f9e8f', '#166f64', 150),
(500,  1, 10,    '#1f9e8f', '#166f64', 160),
(1000, 0, NULL,  '#a8324a', '#701f31', 170),
(1000, 1, 5,     '#a8324a', '#701f31', 180);

-- ------------------------------------------------------------
-- Table : admin_activity_log
-- Historique des actions effectuées depuis l'espace administration
-- (promotion/rétrogradation/suppression de compte, création/modification
-- d'administrateur, gestion des billets, dépôts manuels...). Permet de
-- savoir QUI a fait QUOI et QUAND dans le dossier de gestion des admins.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_activity_log (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id       INT UNSIGNED NULL COMMENT 'Administrateur ayant réalisé l''action (NULL si son compte a depuis été supprimé)',
  admin_name     VARCHAR(150) NOT NULL COMMENT 'Nom figé au moment de l''action (lisible même si le compte est supprimé ensuite)',
  action         VARCHAR(50)  NOT NULL COMMENT 'Ex : creation_admin, modification_admin, promotion, retrogradation, suppression_compte, ajout_billet, creation_billet, modification_billet, suppression_billet',
  target_type    VARCHAR(30)  NOT NULL COMMENT 'Type de la ressource concernée : user, bank_note',
  target_id      INT UNSIGNED NULL COMMENT 'Identifiant de la ressource concernée (NULL si supprimée)',
  target_label   VARCHAR(150) NULL COMMENT 'Nom/libellé figé de la ressource concernée, pour affichage même après suppression',
  details        VARCHAR(255) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_log_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,

  INDEX idx_log_admin (admin_id),
  INDEX idx_log_created (created_at),
  INDEX idx_log_target (target_type, target_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : financial_institutions
-- Répertoire des banques/coopératives/EMI partenaires de la plateforme
-- Gourde Numérique, géré depuis /admin/institutions.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS financial_institutions (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150)  NOT NULL COMMENT 'Nom de l''institution (ex : Banque Nationale de Crédit)',
  type           ENUM('banque','cooperative','emi','autre') NOT NULL DEFAULT 'banque' COMMENT 'Type d''institution financière',
  license_number VARCHAR(80)   NULL COMMENT 'Numéro de licence / agrément délivré par la BRH',
  contact_name   VARCHAR(150)  NULL COMMENT 'Nom du responsable / point de contact chez le partenaire',
  phone          VARCHAR(30)   NULL,
  email          VARCHAR(150)  NULL,
  ville          VARCHAR(100)  NULL,
  address        VARCHAR(255)  NULL,
  logo_path      VARCHAR(255)  NULL COMMENT 'Chemin relatif (depuis /public) du logo envoyé par l''admin',
  status         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif' COMMENT 'Statut du partenariat',
  notes          TEXT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_fi_license (license_number),
  INDEX idx_fi_status (status),
  INDEX idx_fi_type (type)
) ENGINE=InnoDB;

INSERT INTO financial_institutions (name, type, license_number, contact_name, phone, email, ville, status) VALUES
('Banque de la République d''Haïti', 'banque', 'BRH-0001', 'Direction des Systèmes de Paiement', '2299-1000', 'contact@brh.ht', 'Port-au-Prince', 'actif'),
('Sogebank', 'banque', 'BRH-BQ-014', 'Service Partenariats', '2999-1000', 'partenariats@sogebank.com', 'Port-au-Prince', 'actif'),
('Unibank', 'banque', 'BRH-BQ-021', 'Service Partenariats', '2812-3000', 'partenariats@unibank.com', 'Port-au-Prince', 'actif');

-- ------------------------------------------------------------
-- Table : suppliers
-- Dossier des fournisseurs de la plateforme Gourde Numérique (ex :
-- fournisseurs de billets/liquidités pour réapprovisionner le stock,
-- imprimeurs, prestataires de matériel ou de services). Géré depuis
-- /admin/fournisseurs.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS suppliers (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150)  NOT NULL COMMENT 'Nom du fournisseur (ex : Imprimerie Nationale)',
  category       ENUM('billets','materiel','services','autre') NOT NULL DEFAULT 'autre' COMMENT 'Catégorie de fourniture',
  tax_id         VARCHAR(80)   NULL COMMENT 'Numéro d''identification fiscale (NIF) ou matricule',
  contact_name   VARCHAR(150)  NULL COMMENT 'Nom du responsable / point de contact chez le fournisseur',
  phone          VARCHAR(30)   NULL,
  email          VARCHAR(150)  NULL,
  ville          VARCHAR(100)  NULL,
  address        VARCHAR(255)  NULL,
  logo_path      VARCHAR(255)  NULL COMMENT 'Chemin relatif (depuis /public) du logo envoyé par l''admin',
  status         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif' COMMENT 'Statut du partenariat',
  notes          TEXT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uq_sup_tax_id (tax_id),
  INDEX idx_sup_status (status),
  INDEX idx_sup_category (category)
) ENGINE=InnoDB;

INSERT INTO suppliers (name, category, tax_id, contact_name, phone, email, ville, status) VALUES
('Imprimerie Nationale d''Haïti', 'billets', 'NIF-0001-IMP', 'Service Production', '2222-3344', 'production@imprimerie.ht', 'Port-au-Prince', 'actif'),
('CashLog Transport & Sécurité', 'billets', 'NIF-0002-CLS', 'Direction des Opérations', '3344-5566', 'operations@cashlog.ht', 'Port-au-Prince', 'actif');

-- ------------------------------------------------------------
-- Table : bnc_agences
-- Répertoire des agences de la Banque Nationale de Crédit (BNC) à travers
-- le pays, affiché au public sur une carte interactive (/agences-bnc) pour
-- permettre aux clients de localiser un point de dépôt/retrait physique.
-- Géré depuis l'admin (/admin/agences-bnc).
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Table : contact_messages
-- Messages envoyés par les visiteurs depuis la page publique /contact.
-- Consultés et gérés depuis l'admin (/admin/messages).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL,
  phone          VARCHAR(30)  NULL,
  subject        VARCHAR(150) NOT NULL,
  message        TEXT NOT NULL,
  status         ENUM('nouveau','lu','traite') NOT NULL DEFAULT 'nouveau' COMMENT 'Statut de traitement du message',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_cm_status (status),
  INDEX idx_cm_created (created_at)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Données de démonstration (mot de passe pour les deux : "123456")
-- NINU : exactement 10 chiffres. Téléphone : indicatif 509 + 8 chiffres.
-- ------------------------------------------------------------
-- Hash bcrypt valide du mot de passe "123456" (généré une seule fois, réutilisé pour les 2 comptes démo)
INSERT INTO users (ninu, name, sexe, age, ville, pays, phone, email, password_hash, balance)
VALUES
('1123008890', 'Jean Baptiste', 'Homme', 32, 'Port-au-Prince', 'Haïti', '509 37123456',
 'jean.baptiste@example.ht', '$2b$12$uXX1bK03wJJ2vxTmqd4dVeT9/r1Afcxlz0T1NzdyUP7n9P264VpgW', 12500.00),
('2245001102', 'Marie Joseph', 'Femme', 27, 'Cap-Haïtien', 'Haïti', '509 36992201',
 'marie.joseph@example.ht', '$2b$12$uXX1bK03wJJ2vxTmqd4dVeT9/r1Afcxlz0T1NzdyUP7n9P264VpgW', 8300.00);

-- ------------------------------------------------------------
-- Compte administrateur de démonstration (mot de passe : "123456")
-- Se connecte via la page /login normale ; role='admin' donne accès à /admin
-- ------------------------------------------------------------
INSERT INTO users (ninu, name, sexe, age, ville, pays, phone, email, password_hash, balance, role)
VALUES
('9000000001', 'Administrateur Plateforme', 'Homme', 35, 'Port-au-Prince', 'Haïti', '509 37000000',
 'admin@gourdenumerique.ht', '$2b$12$uXX1bK03wJJ2vxTmqd4dVeT9/r1Afcxlz0T1NzdyUP7n9P264VpgW', 0.00, 'admin');

-- ------------------------------------------------------------
-- Migration pour une base déjà existante (créée avant cette restructuration,
-- avec l'ancienne colonne is_admin TINYINT(1)) :
--
-- ALTER TABLE users ADD COLUMN role ENUM('client','admin') NOT NULL DEFAULT 'client'
--   COMMENT 'Rôle du compte : "client" (usage courant) ou "admin" (accès à /admin)' AFTER is_merchant;
-- UPDATE users SET role = 'admin' WHERE is_admin = 1;
-- ALTER TABLE users DROP COLUMN is_admin;
-- ALTER TABLE users ADD CONSTRAINT chk_users_age CHECK (age >= 18 AND age <= 120);
-- ALTER TABLE users ADD CONSTRAINT chk_users_balance CHECK (balance >= 0);
-- ALTER TABLE users ADD INDEX idx_users_role (role), ADD INDEX idx_users_name (name);
--
-- ALTER TABLE transactions ADD CONSTRAINT chk_tx_amount CHECK (amount > 0);
-- ALTER TABLE transactions ADD INDEX idx_tx_type (type), ADD INDEX idx_tx_status (status);
--
-- ALTER TABLE bank_notes ADD CONSTRAINT chk_bn_unit_value CHECK (unit_value > 0);
-- ALTER TABLE bank_notes ADD CONSTRAINT chk_bn_bundle_qty CHECK (is_bundle = 0 OR bundle_qty >= 2);
-- ALTER TABLE bank_notes ADD CONSTRAINT uq_bn_value_type UNIQUE (unit_value, is_bundle);
-- ALTER TABLE bank_notes ADD INDEX idx_bn_active_sort (active, sort_order);
--
-- Puis recréer la table admin_activity_log ci-dessus (bloc CREATE TABLE complet).
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout de l'upload d'image pour les billets (remplace les
-- cartes en dégradé de couleur par une vraie image de billet).
-- À exécuter une seule fois sur une base déjà existante :
--
-- ALTER TABLE bank_notes ADD COLUMN image_path VARCHAR(255) NULL
--   COMMENT 'Chemin relatif (depuis /public) de l''image du billet envoyée par l''admin'
--   AFTER color_end;
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout de la gestion de stock des billets (quantité
-- disponible en réserve pour chaque coupure), avec journal des mouvements.
-- À exécuter une seule fois sur une base déjà existante :
--
-- ALTER TABLE bank_notes ADD COLUMN stock_quantity INT UNSIGNED NOT NULL DEFAULT 0
--   COMMENT 'Quantité de billets disponibles en réserve (stock)'
--   AFTER image_path;
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout des catégories de billets (regroupement des coupures).
-- À exécuter une seule fois sur une base déjà existante :
--
-- CREATE TABLE IF NOT EXISTS bank_note_categories (
--   id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   name        VARCHAR(80) NOT NULL UNIQUE,
--   description VARCHAR(255) NULL,
--   sort_order  INT NOT NULL DEFAULT 0,
--   active      TINYINT(1) NOT NULL DEFAULT 1,
--   created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   INDEX idx_bnc_active_sort (active, sort_order)
-- ) ENGINE=InnoDB;
--
-- ALTER TABLE bank_notes ADD COLUMN category_id INT UNSIGNED NULL
--   COMMENT 'Catégorie de regroupement (facultative)' AFTER bundle_qty;
-- ALTER TABLE bank_notes ADD CONSTRAINT fk_bn_category
--   FOREIGN KEY (category_id) REFERENCES bank_note_categories(id) ON DELETE SET NULL;
-- ALTER TABLE bank_notes ADD INDEX idx_bn_category (category_id);
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout du dossier "Fournisseurs" (fournisseurs de billets/
-- liquidités, imprimeurs, prestataires de matériel ou de services),
-- géré depuis /admin/fournisseurs.
-- À exécuter une seule fois sur une base déjà existante (voir aussi le
-- fichier autonome migration_add_fournisseurs.sql, identique) :
--
-- CREATE TABLE IF NOT EXISTS suppliers (
--   id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   name           VARCHAR(150)  NOT NULL,
--   category       ENUM('billets','materiel','services','autre') NOT NULL DEFAULT 'autre',
--   tax_id         VARCHAR(80)   NULL,
--   contact_name   VARCHAR(150)  NULL,
--   phone          VARCHAR(30)   NULL,
--   email          VARCHAR(150)  NULL,
--   ville          VARCHAR(100)  NULL,
--   address        VARCHAR(255)  NULL,
--   logo_path      VARCHAR(255)  NULL,
--   status         ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
--   notes          TEXT NULL,
--   created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   UNIQUE KEY uq_sup_tax_id (tax_id),
--   INDEX idx_sup_status (status),
--   INDEX idx_sup_category (category)
-- ) ENGINE=InnoDB;
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout du dossier des agences BNC (Banque Nationale de
-- Crédit) affichées sur une carte interactive côté site client.
-- À exécuter une seule fois sur une base déjà existante : voir le script
-- migration_add_agences_bnc.sql fourni séparément (structure complète de
-- la table + jeu de données de départ).
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout des pages publiques "Fonctionnalités", "Objectifs"
-- et "Contact" (/fonctionnalites, /objectifs, /contact). Seule la page
-- Contact nécessite une table (les deux autres sont éditoriales).
-- À exécuter une seule fois sur une base déjà existante (voir aussi le
-- fichier autonome migration_add_contact_messages.sql, identique) :
--
-- CREATE TABLE IF NOT EXISTS contact_messages (
--   id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   name           VARCHAR(150) NOT NULL,
--   email          VARCHAR(150) NOT NULL,
--   phone          VARCHAR(30)  NULL,
--   subject        VARCHAR(150) NOT NULL,
--   message        TEXT NOT NULL,
--   status         ENUM('nouveau','lu','traite') NOT NULL DEFAULT 'nouveau',
--   created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   INDEX idx_cm_status (status),
--   INDEX idx_cm_created (created_at)
-- ) ENGINE=InnoDB;
-- ------------------------------------------------------------

-- ------------------------------------------------------------
-- Migration : ajout du rôle "employé" (accès limité à l'administration,
-- toujours restreint par permissions — jamais un accès complet), avec le
-- dossier de gestion associé "/admin/employes". À exécuter une seule fois
-- sur une base déjà existante : voir le script autonome
-- migration_add_employes.sql fourni séparément.
-- ------------------------------------------------------------
