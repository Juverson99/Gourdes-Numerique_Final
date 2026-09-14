# Gourde Numérique — Architecture MVC PHP

Conversion du prototype statique (Bootstrap) en application PHP MVC
fonctionnelle : comptes réels, sessions PHP, transactions atomiques en
base de données MySQL.

## Installation

1. **Créer la base de données**
   ```bash
   mysql -u root -p < database.sql
   ```
   Cela crée la base `gourde_numerique` avec les tables `users` et
   `transactions`, plus deux comptes de démonstration :

   | Email                      | Mot de passe |
   |-----------------------------|--------------|
   | jean.baptiste@example.ht   | 123456       |
   | marie.joseph@example.ht    | 123456       |

2. **Configurer la connexion à la base**
   Éditer `config/database.php` (ou définir les variables d'environnement
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

3. **Lancer un serveur local**
   ```bash
   php -S localhost:8000 -t public
   ```
   Puis ouvrir http://localhost:8000

   *(Avec Apache : pointer le DocumentRoot sur `public/`, ou utiliser le
   `.htaccess` à la racine si le DocumentRoot pointe sur le dossier du
   projet.)*

## Fonctionnalités réelles (pas de simulation)

- **Inscription / connexion** : mots de passe hachés (`password_hash`),
  session PHP serveur (`$_SESSION`), plus de `localStorage`.
- **Envoyer** : débite l'expéditeur et crédite le destinataire (trouvé par
  son NINU) dans une **transaction SQL atomique** (`BEGIN`/`COMMIT`/
  `ROLLBACK`, verrou `SELECT ... FOR UPDATE`) — impossible de créditer un
  compte sans débiter l'autre.
- **Recevoir** : QR code généré à partir du vrai NINU du compte connecté.
- **Paiement** : débite le compte et enregistre la facture/le marchand.
- **Scanner** : caméra réelle (librairie `jsQR`) ou saisie manuelle, avec
  recherche réelle du compte en base ; redirige vers *Envoyer* pré-rempli.
- **Historique** : liste les transactions réelles de l'utilisateur
  (envoyées, reçues, paiements), triées par date.

## Structure

Voir l'arborescence dans le message (`config/`, `app/controllers`,
`app/models`, `app/views`, `public/`, `public/api/`, `.htaccess`).
Le routeur (mise en correspondance URL → contrôleur) fait désormais
partie intégrante de `public/index.php`, point d'entrée unique de
l'application ; il n'y a plus de fichier `router.php` séparé.

## Format du NINU et du téléphone

Tous les formulaires (inscription, réglages du compte, dossiers admin
clients/employés/administrateurs, contact) appliquent la même règle,
définie une seule fois dans `config/config.php` :

- **NINU** : uniquement des chiffres, exactement 10 chiffres (ex. `1200384609`).
- **Téléphone** : indicatif Haïti `509` suivi de 8 chiffres (ex. `509 46213235`).
  L'indicatif est ajouté automatiquement si l'utilisateur ne saisit que les
  8 chiffres locaux.

Si votre base contient déjà des comptes enregistrés avec l'ancien format
libre (ex. `NINU-1123-8890`, `3712-3456`), exécutez une seule fois le
script de migration fourni pour les mettre à niveau :

```bash
# Aperçu des changements prévus (n'écrit rien) :
php migrate_normaliser_ninu_telephone.php

# Applique réellement les mises à jour :
php migrate_normaliser_ninu_telephone.php --appliquer
```

Un compte dont le NINU ou le téléphone ne peut pas être ramené au bon
format (ou qui entrerait en collision avec un autre compte une fois
normalisé) n'est jamais modifié automatiquement : il est signalé dans la
sortie du script, à corriger manuellement depuis `/admin`.

## Intégration MonCash (dossier client "Convertir")

Le dossier client **Convertir** (`/convertir`) permet à un client de
transformer l'argent de son compte MonCash en billet électronique Gourde
Numérique. L'intégration technique complète se trouve dans :

- `config/moncash.php` — identifiants et URLs de l'API (voir commentaires
  du fichier pour le détail de chaque constante) ;
- `app/lib/MonCash.php` — client HTTP natif (OAuth2, CreatePayment,
  RetrieveTransactionPayment, RetrieveOrderPayment) ;
- `app/models/Transaction.php` (méthodes `moncash*`) — logique métier :
  création du dépôt "en attente", crédit du solde uniquement après
  vérification réelle du paiement auprès de MonCash (jamais sur simple
  redirection), contrôle du montant reçu, idempotence ;
- `app/controllers/AccountController.php` (`convertir`,
  `convertirMoncashRetour`) et `app/controllers/AdminController.php`
  (`moncash`, `moncashVerifier`) — routes client et back-office ;
- `app/views/account/convertir.php` et `app/views/admin/moncash.php` —
  interfaces client et administrateur (stats, réconciliation manuelle).

### 1) Base de données

Sur une base déjà existante, appliquer une fois :

```bash
mysql -u root -p gourde_numerique < migration_add_moncash_bank.sql
mysql -u root -p gourde_numerique < migration_add_retrait_especes.sql
```

(Une base créée à partir de `database.sql` inclut déjà ces colonnes/types,
ces deux scripts ne sont utiles que pour une mise à niveau.)

### 2) Identifiants Digicel/MonCash

1. Créer un compte "business" sur
   https://moncashbutton.digicelgroup.com et générer une paire
   Client ID / Secret (un jeu **sandbox** pour les tests, un jeu **live**
   pour la production).
2. Déclarer l'URL de retour (Return URL) dans le tableau de bord marchand :
   `https://votre-domaine.tld/convertir/moncash/retour`
3. Définir les variables d'environnement (voir `.env.example`) :

   | Variable                | Exemple (sandbox)                  |
   |--------------------------|------------------------------------|
   | `MONCASH_MODE`            | `sandbox`                          |
   | `MONCASH_CLIENT_ID`       | *(fourni par Digicel)*             |
   | `MONCASH_CLIENT_SECRET`   | *(fourni par Digicel)*             |

   Ne jamais committer de vrais identifiants **live** dans le dépôt.

### 3) Donner l'accès à un administrateur

Dans `/admin/administrateurs` (ou `/admin/employes`), cocher la
permission **« Transactions MonCash »** pour l'administrateur concerné :
il pourra alors consulter `/admin/moncash` (statistiques, liste des
dépôts, réconciliation manuelle des dépôts restés "en attente").

### 4) Vérifier l'état de la configuration

L'écran `/admin/moncash` affiche en haut de page le mode actif
(sandbox/production) et si `MONCASH_CLIENT_ID`/`MONCASH_CLIENT_SECRET`
sont bien renseignés — pratique pour diagnostiquer un déploiement sans
avoir à lire les fichiers de configuration.

### Points de sécurité déjà en place

- Le solde n'est **jamais** crédité au simple retour du client sur le
  site : `convertirMoncashRetour()` interroge réellement l'API MonCash
  (`RetrieveTransactionPayment`) et exige `message === "successful"`.
- Le montant confirmé par MonCash est comparé au montant demandé à
  l'ouverture du paiement ; tout écart bloque le crédit ("Écart de
  montant détecté").
- La confirmation est protégée par verrou de ligne (`SELECT ... FOR
  UPDATE`) et est idempotente : rejouer la même confirmation ne crédite
  jamais deux fois.
- Un dépôt payé sur MonCash mais jamais "revenu" sur le site (fermeture
  du navigateur, coupure réseau) reste "en attente" et peut être
  réconcilié manuellement par un administrateur habilité, sans jamais
  faire confiance à une donnée fournie par le client.
