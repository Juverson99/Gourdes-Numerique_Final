<?php
/**
 * config/local.php
 * Config locale (JAMAIS commitée — voir .gitignore) chargée automatiquement
 * par config/config.php avant config/moncash.php.
 *
 * À quoi ça sert : sous XAMPP/WAMP/MAMP (Apache + mod_php), il n'existe pas
 * de moyen simple de définir des variables d'environnement PHP comme on le
 * ferait avec `export MONCASH_CLIENT_ID=...` avant `php -S`. Ce fichier
 * comble ce manque avec putenv().
 *
 * IL NE RESTE QU'UNE CHOSE À FAIRE :
 *   1) Remplacer les deux valeurs "colle-ici-..." ci-dessous par ton VRAI
 *      Client ID / Secret sandbox (tableau de bord marchand MonCash Digicel :
 *      https://moncashbutton.digicelgroup.com/Moncash-business/API).
 *   2) Redémarrer Apache (XAMPP Control Panel -> Stop puis Start) pour être
 *      sûr que rien n'est resté en cache.
 * Tant que ces deux valeurs ne sont pas remplacées, /convertir affiche
 * normalement "Identifiants MonCash manquants" au clic sur "Payer avec
 * MonCash" — c'est attendu, pas un bug.
 */

putenv('MONCASH_MODE=sandbox');
putenv('MONCASH_CLIENT_ID=32670c5417a420283c7772c81525f2fe');
putenv('MONCASH_CLIENT_SECRET=hNlpGUx3khzQ6HlWrh_O_e2id-mSeqaN5KhFNMjp6NmMa5Pb_XdbKzbPDXuQroak');

// Optionnel : active les messages d'erreur PHP détaillés en local pour
// diagnostiquer plus vite (ne JAMAIS activer ça en production).
putenv('APP_DEBUG=1');
