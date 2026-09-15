<?php
/**
 * config/local.example.php
 * Modèle à copier en config/local.php (JAMAIS committer local.php lui-même —
 * il contient tes vrais identifiants MonCash).
 *
 * À quoi ça sert : sous XAMPP/WAMP/MAMP (Apache + mod_php), il n'existe pas
 * de moyen simple de définir des variables d'environnement PHP comme on le
 * ferait avec `export MONCASH_CLIENT_ID=...` avant `php -S`. Ce fichier
 * comble ce manque avec putenv(), chargé automatiquement par
 * config/config.php avant config/moncash.php.
 *
 * Étapes :
 *   1) Copie ce fichier : config/local.example.php -> config/local.php
 *   2) Remplace les valeurs ci-dessous par ton VRAI Client ID / Secret
 *      sandbox (tableau de bord marchand MonCash Digicel).
 *   3) Redémarre Apache (XAMPP Control Panel -> Stop puis Start sur Apache)
 *      pour être sûr que rien n'est resté en cache.
 */

putenv('MONCASH_MODE=sandbox');
putenv('MONCASH_CLIENT_ID=32670c5417a420283c7772c81525f2fe');
putenv('MONCASH_CLIENT_SECRET=hNlpGUx3khzQ6HlWrh_O_e2id-mSeqaN5KhFNMjp6NmMa5Pb_XdbKzbPDXuQroak');

// Optionnel : active les messages d'erreur PHP détaillés en local pour
// diagnostiquer plus vite (ne JAMAIS activer ça en production).
putenv('APP_DEBUG=1');
