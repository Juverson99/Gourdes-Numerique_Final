# HTTPS / TLS & sécurité — Gourde Numérique

Ce dossier contient tout ce qu'il faut pour activer HTTPS et durcir
l'application avant une mise en production. Le code applicatif (session,
en-têtes, redirections) était déjà prêt à recevoir HTTPS ; ce qui manquait,
et qui est ajouté ici, c'est la configuration serveur (certificat + vhost)
et quelques protections applicatives supplémentaires.

## 1. Choisir un certificat

| Contexte | Solution | Coût | Confiance navigateur |
|---|---|---|---|
| Développement local / recette interne | Certificat auto-signé (`generate-self-signed-cert.sh`) | Gratuit | ❌ Avertissement navigateur (normal, à ignorer en dev) |
| Production, domaine public | **Let's Encrypt** (Certbot) | Gratuit | ✅ |
| Production, exigence contractuelle/entreprise | Certificat acheté (DigiCert, Sectigo, GlobalSign...) | Payant | ✅ |

### Développement / recette (certificat auto-signé)
```bash
chmod +x deploy/generate-self-signed-cert.sh
sudo ./deploy/generate-self-signed-cert.sh localhost /etc/ssl/gourde-numerique
```

### Production (Let's Encrypt, recommandé)
```bash
sudo apt install certbot python3-certbot-apache   # ou python3-certbot-nginx
sudo certbot --apache -d votre-domaine.tld -d www.votre-domaine.tld
# Renouvellement automatique déjà planifié par le paquet certbot (systemd timer / cron) ;
# vérifier avec : sudo certbot renew --dry-run
```
Puis, dans `deploy/apache-ssl.conf` ou `deploy/nginx-ssl.conf`, pointer
`SSLCertificateFile`/`ssl_certificate` vers
`/etc/letsencrypt/live/votre-domaine.tld/fullchain.pem` (et `privkey.pem`
pour la clé) au lieu du certificat auto-signé.

### Certificat acheté auprès d'une autorité
L'autorité fournit un fichier certificat, une clé privée (générée par vous
via `openssl req` et jamais transmise), et souvent un fichier de chaîne
intermédiaire. Placez les trois fichiers et référencez-les dans
`SSLCertificateFile` / `SSLCertificateKeyFile` / `SSLCertificateChainFile`.

## 2. Installer le vhost

- Apache : `deploy/apache-ssl.conf` (nécessite `mod_ssl`, `mod_rewrite`, `mod_headers`)
- Nginx + PHP-FPM : `deploy/nginx-ssl.conf`

Adaptez le domaine, les chemins de certificat et le chemin du projet, puis
activez le site et rechargez le serveur (commandes détaillées en en-tête de
chaque fichier).

Les deux modèles :
- redirigent tout le trafic HTTP (port 80) vers HTTPS (port 443), sauf le
  challenge ACME nécessaire au renouvellement Let's Encrypt ;
- pointent le `DocumentRoot`/`root` sur `public/` — jamais la racine du
  projet, pour que `config/`, les fichiers `.sql` et les migrations restent
  inaccessibles depuis un navigateur ;
- désactivent TLS 1.0/1.1 et SSLv3 (protocoles obsolètes), ne gardent que
  des suites de chiffrement modernes ;
- définissent `FORCE_HTTPS=1` dans l'environnement du serveur web, ce que
  `config/config.php` lit déjà (`getenv('FORCE_HTTPS')`) pour activer la
  redirection HTTP→HTTPS côté PHP et l'en-tête HSTS.

## 3. Ce qui est déjà en place côté application

Ces protections existaient déjà ou ont été ajoutées dans ce même chantier,
et s'activent automatiquement dès que HTTPS est en place :

- **Cookies de session sécurisés** : `secure` (HTTPS uniquement dès que
  détecté), `HttpOnly` (invisible en JavaScript), `SameSite=Lax`.
- **HSTS** (`Strict-Transport-Security`) envoyé automatiquement dès que la
  requête est en HTTPS — force le navigateur à ne plus jamais revenir en HTTP.
- **En-têtes de sécurité** : `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`, et une `Content-Security-Policy`
  restreinte aux CDN réellement utilisés par l'appli (`config/config.php`).
- **Verrouillage anti-force-brute** sur la connexion (client ET admin) :
  5 échecs consécutifs depuis la même IP/email verrouillent 15 minutes
  (`app/models/LoginThrottle.php`, table `login_attempts`).
- **Régénération d'identifiant de session** après connexion réussie
  (protection contre la fixation de session).
- **Messages d'erreur génériques en production** : le détail technique
  (message SQL, chemin de fichier) n'est plus affiché à un visiteur, sauf en
  développement local (`APP_DEBUG=1`) — il est toujours journalisé côté
  serveur (`error_log`).
- **Sessions séparées client/admin**, mots de passe hachés (`password_hash`),
  transactions SQL atomiques avec verrou (déjà présents avant ce chantier).

## 4. Variables d'environnement à définir en production

| Variable | Valeur recommandée en prod | Effet |
|---|---|---|
| `FORCE_HTTPS` | `1` | Redirige tout HTTP vers HTTPS, active HSTS |
| `APP_DEBUG` | *(ne pas définir, ou `0`)* | Cache les détails techniques des erreurs |
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | vos identifiants réels | Ne jamais laisser les valeurs par défaut de `config/database.php` |

En développement local uniquement : `APP_DEBUG=1` pour retrouver le détail
des erreurs PHP à l'écran.

## 5. Migrations à exécuter

Sur une base existante (mise à jour du code sans réinstallation complète) :
```bash
mysql -u root -p gourde_numerique < migration_add_login_security.sql
```
(Sur une base entièrement neuve, `database.sql` contient déjà tout.)

## 6. Checklist de gestion des vulnérabilités (au-delà de ce qui est automatisé)

Ce qui précède couvre le transport (HTTPS/TLS) et plusieurs vulnérabilités
applicatives concrètes. Avant une mise en production réelle, complétez avec
des mesures qui dépendent de votre infrastructure et de votre organisation :

- [ ] **Mettre à jour PHP, MySQL et le serveur web** vers une version
      maintenue (correctifs de sécurité) ; désactiver `expose_php` dans
      `php.ini` (`expose_php = Off`) pour ne pas annoncer la version PHP.
- [ ] **Pare-feu applicatif (WAF)** devant le site (ex. ModSecurity,
      Cloudflare) pour filtrer les tentatives d'injection connues.
- [ ] **Sauvegardes régulières** de la base de données, testées
      (une sauvegarde qu'on n'a jamais restaurée n'est pas fiable).
- [ ] **Surveillance des journaux** (`error_log`, journal d'activité admin
      déjà présent dans l'appli via `AdminLog`) pour repérer une activité
      anormale.
- [ ] **Double authentification (2FA)** sur les comptes administrateurs,
      en plus du verrouillage anti-force-brute déjà en place.
- [ ] **Jetons anti-CSRF** sur les formulaires POST de l'espace admin (non
      couvert par ce chantier, car cela touche l'ensemble des formulaires
      existants) — voir OWASP CSRF Prevention Cheat Sheet.
- [ ] **Scan de vulnérabilités** périodique (ex. `openssl s_client`,
      SSL Labs (`ssllabs.com/ssltest`) une fois le certificat en production).
- [ ] **Limiter les droits MySQL** de l'utilisateur applicatif (pas de
      compte `root` en production) et changer les mots de passe des
      comptes de démonstration créés par `database.sql`.
