<?php
/**
 * config/moncash.php
 * Identifiants et paramètres de l'API MonCash (Digicel) — utilisés par
 * app/lib/MonCash.php pour le dossier client "Convertir" (/convertir).
 *
 * Où obtenir ces identifiants :
 *   1) Créer un compte "business" sur https://moncashbutton.digicelgroup.com
 *   2) Dans le tableau de bord marchand, générer une paire Client ID / Secret
 *      (un jeu "sandbox" pour les tests, un jeu "live" pour la production).
 *   3) Renseigner l'URL de retour (Return URL) dans ce même tableau de bord :
 *        https://votre-domaine.tld/convertir/moncash/retour
 *      C'est cette URL que MonCash appelle après le paiement, avec le
 *      paramètre ?transactionId=... utilisé par MonCashCallback ci-dessous.
 *
 * En local/développement, laissez les valeurs par défaut : le mode
 * "sandbox" fonctionne avec les identifiants de test fournis par Digicel.
 * Ne JAMAIS committer de vrais identifiants "live" dans ce fichier — passez
 * plutôt par des variables d'environnement (MONCASH_CLIENT_ID, etc.).
 */

define('MONCASH_MODE', trim(getenv('MONCASH_MODE') ?: 'sandbox')); // 'sandbox' ou 'live'
// trim() : un espace ou saut de ligne collé par erreur avec l'identifiant
// (très fréquent en copiant depuis le tableau de bord web Digicel) casse
// silencieusement l'en-tête Authorization (Base64) et renvoie "Unauthorized"
// côté API — sans ce trim(), rien dans le code ne permet de le repérer.
define('MONCASH_CLIENT_ID', trim(getenv('MONCASH_CLIENT_ID') ?: '32670c5417a420283c7772c81525f2fe'));
define('MONCASH_CLIENT_SECRET', trim(getenv('MONCASH_CLIENT_SECRET') ?: 'hNlpGUx3khzQ6HlWrh_O_e2id-mSeqaN5KhFNMjp6NmMa5Pb_XdbKzbPDXuQroak'));

define('MONCASH_API_BASE', MONCASH_MODE === 'live'
    ? 'https://moncashbutton.digicelgroup.com/Api'
    : 'https://sandbox.moncashbutton.digicelgroup.com/Api');

define('MONCASH_GATEWAY_BASE', MONCASH_MODE === 'live'
    ? 'https://moncashbutton.digicelgroup.com/Moncash-middleware/Payment/Redirect'
    : 'https://sandbox.moncashbutton.digicelgroup.com/Moncash-middleware/Payment/Redirect');

/**
 * Bundle de certificats CA (chemin vers un fichier .pem), utile
 * si le serveur (souvent en local : XAMPP/WAMP sous Windows,
 * antivirus qui inspecte le HTTPS...) renvoie une erreur du type
 * "SSL certificate problem: self-signed certificate in certificate chain"
 * lors des appels à l'API MonCash. Un bundle Mozilla à jour est fourni par
 * défaut (app/lib/cacert/cacert.pem, https://curl.se/docs/caextract.html) ;
 * définissez MONCASH_CA_BUNDLE (variable d'environnement) pour le remplacer
 * par un autre chemin, ou par une chaîne vide pour désactiver ce bundle et
 * utiliser uniquement celui du système (typiquement suffisant en production
 * sur un hébergement Linux normal).
 */
define('MONCASH_CA_BUNDLE', getenv('MONCASH_CA_BUNDLE') !== false
    ? getenv('MONCASH_CA_BUNDLE')
    : __DIR__ . '/../app/lib/cacert/cacert.pem');
