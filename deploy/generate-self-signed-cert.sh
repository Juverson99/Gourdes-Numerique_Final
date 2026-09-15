#!/usr/bin/env bash
# deploy/generate-self-signed-cert.sh
#
# Génère un certificat TLS auto-signé, valable 825 jours, pour TESTER le
# fonctionnement HTTPS en local ou sur un environnement de recette.
#
# ⚠️  Un certificat auto-signé n'est PAS approuvé par les navigateurs
#     (avertissement "connexion non sécurisée") : il ne doit JAMAIS être
#     utilisé pour un site accessible publiquement. Pour la production,
#     utiliser Let's Encrypt/Certbot (gratuit, reconnu) ou un certificat
#     acheté auprès d'une autorité (voir deploy/README.md).
#
# Utilisation :
#   chmod +x deploy/generate-self-signed-cert.sh
#   ./deploy/generate-self-signed-cert.sh [nom-de-domaine] [dossier-de-sortie]
#
# Exemple :
#   sudo ./deploy/generate-self-signed-cert.sh localhost /etc/ssl/gourde-numerique

set -euo pipefail

DOMAIN="${1:-localhost}"
OUT_DIR="${2:-./ssl-dev}"

mkdir -p "$OUT_DIR"

echo "Génération d'un certificat auto-signé pour : $DOMAIN"
echo "Dossier de sortie : $OUT_DIR"

openssl req -x509 -nodes -newkey rsa:2048 \
  -days 825 \
  -keyout "$OUT_DIR/server.key" \
  -out "$OUT_DIR/server.crt" \
  -subj "/C=HT/ST=Ouest/L=Port-au-Prince/O=Gourde Numerique/CN=${DOMAIN}" \
  -addext "subjectAltName=DNS:${DOMAIN},DNS:www.${DOMAIN},IP:127.0.0.1"

chmod 600 "$OUT_DIR/server.key"
chmod 644 "$OUT_DIR/server.crt"

echo
echo "OK. Fichiers générés :"
echo "  - Certificat : $OUT_DIR/server.crt"
echo "  - Clé privée : $OUT_DIR/server.key  (à garder strictement confidentielle)"
echo
echo "Référencez ces deux chemins dans deploy/apache-ssl.conf ou"
echo "deploy/nginx-ssl.conf, puis redémarrez le serveur web."
