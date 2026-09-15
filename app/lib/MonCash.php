<?php
/**
 * app/lib/MonCash.php
 * Client pour l'API MonCash (Digicel Business API), sans dépendance externe
 * (cURL natif de PHP). Couvre le dossier client "Convertir" (/convertir) :
 *   1) getAccessToken()        — authentification OAuth2 (client_credentials),
 *                                 avec mise en cache du jeton entre requêtes.
 *   2) createPayment()         — ouvre un paiement, renvoie l'URL de la passerelle.
 *   3) retrieveTransaction()   — vérifie le résultat via l'identifiant de
 *                                 transaction MonCash (retour passerelle).
 *   4) retrieveOrderPayment()  — vérifie le résultat via NOTRE référence de
 *                                 commande (reconciliation / vérification
 *                                 manuelle admin quand le client n'est jamais
 *                                 revenu sur le site après avoir payé).
 *
 * Documentation officielle Digicel MonCash Business API :
 *   https://moncashbutton.digicelgroup.com/Moncash-business/API
 *
 * Toutes les méthodes renvoient ['ok' => bool, ...] et ne lancent jamais
 * d'exception : les erreurs réseau/API sont transformées en ['ok' => false,
 * 'error' => '...'] pour rester simples à afficher dans les vues.
 *
 * IMPORTANT — un paiement MonCash "trouvé" n'est pas forcément un paiement
 * RÉUSSI : l'API peut renvoyer un objet "payment" pour une transaction en
 * échec ou annulée. C'est pourquoi retrieveTransaction()/retrieveOrderPayment()
 * exposent toujours 'successful' (bool) et 'message' (texte brut MonCash) —
 * le code appelant DOIT tester 'successful' avant de créditer quoi que ce
 * soit (voir Transaction::moncashConfirmer()).
 */
class MonCash
{
    /** Jeton d'accès en cache pour la durée de la requête HTTP courante (repli si le cache fichier échoue) */
    private static ?string $tokenCache = null;

    /** Étape 1 : authentification OAuth2 (client_credentials) auprès de MonCash */
    public static function getAccessToken(): array
    {
        if (self::$tokenCache !== null) {
            return ['ok' => true, 'access_token' => self::$tokenCache];
        }
        if (MONCASH_CLIENT_ID === '' || MONCASH_CLIENT_SECRET === '') {
            return ['ok' => false, 'error' => "Identifiants MonCash manquants. Configurez MONCASH_CLIENT_ID / MONCASH_CLIENT_SECRET (voir config/moncash.php)."];
        }

        // Le jeton OAuth MonCash reste valable environ 1h (voir 'expires_in'
        // renvoyé par l'API) : on le met en cache sur disque entre les
        // requêtes HTTP pour éviter de ré-authentifier à chaque paiement.
        // Best-effort : si le cache est illisible/inaccessible, on retombe
        // simplement sur une authentification normale, sans jamais faire
        // échouer le paiement pour une histoire de cache.
        $cached = self::readCachedToken();
        if ($cached !== null) {
            self::$tokenCache = $cached;
            return ['ok' => true, 'access_token' => $cached];
        }

        $response = self::request(
            'POST',
            MONCASH_API_BASE . '/oauth/token',
            'grant_type=client_credentials&scope=read,write',
            [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode(MONCASH_CLIENT_ID . ':' . MONCASH_CLIENT_SECRET),
            ]
        );

        if (!$response['ok']) {
            // Un 401 précisément ICI (authentification OAuth2, avant même la
            // création du paiement) veut presque toujours dire Client ID /
            // Secret incorrects pour le mode actuel — pas un souci réseau ni
            // un bug applicatif. On remplace le message brut MonCash
            // ("Unauthorized", peu parlant) par une piste concrète, tout en
            // gardant le message d'origine pour qui voudrait le détail.
            if (($response['status'] ?? null) === 401) {
                return ['ok' => false, 'error' => "Authentification MonCash refusée (Unauthorized). Vérifiez, dans config/local.php (ou les variables d'environnement), que MONCASH_CLIENT_ID/MONCASH_CLIENT_SECRET sont bien ceux du mode actuel (" . MONCASH_MODE . "), copiés sans espace ni saut de ligne, puis redémarrez Apache/le serveur."];
            }
            return $response;
        }

        $token = $response['data']['access_token'] ?? null;
        if (!$token) {
            return ['ok' => false, 'error' => "Réponse MonCash invalide lors de l'authentification."];
        }

        self::$tokenCache = $token;
        $expiresIn = (int) ($response['data']['expires_in'] ?? 3600);
        self::writeCachedToken($token, $expiresIn);

        return ['ok' => true, 'access_token' => $token];
    }

    /**
     * Étape 2 : ouvre un paiement MonCash pour la référence de commande
     * $orderId (notre référence de transaction interne) et le montant $amount
     * (en HTG). Renvoie l'URL de la passerelle vers laquelle rediriger le client.
     */
    public static function createPayment(string $orderId, float $amount): array
    {
        $auth = self::getAccessToken();
        if (!$auth['ok']) {
            return $auth;
        }

        $response = self::request(
            'POST',
            MONCASH_API_BASE . '/v1/CreatePayment',
            json_encode(['amount' => round($amount, 2), 'orderId' => $orderId]),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $auth['access_token'],
            ]
        );

        // Un jeton mis en cache peut avoir expiré côté MonCash sans qu'on le
        // sache encore côté application (horloge serveur légèrement décalée,
        // révocation manuelle...). Sur un 401, on invalide le cache et on
        // retente une seule fois avec un jeton neuf avant d'abandonner.
        if (!$response['ok'] && ($response['status'] ?? null) === 401) {
            self::clearCachedToken();
            $auth = self::getAccessToken();
            if (!$auth['ok']) {
                return $auth;
            }
            $response = self::request(
                'POST',
                MONCASH_API_BASE . '/v1/CreatePayment',
                json_encode(['amount' => round($amount, 2), 'orderId' => $orderId]),
                [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $auth['access_token'],
                ]
            );
        }

        if (!$response['ok']) {
            return $response;
        }

        $token = $response['data']['payment_token']['token'] ?? null;
        if (!$token) {
            return ['ok' => false, 'error' => "MonCash n'a pas renvoyé de jeton de paiement."];
        }

        return [
            'ok'            => true,
            'payment_token' => $token,
            'redirect_url'  => MONCASH_GATEWAY_BASE . '?token=' . urlencode($token),
        ];
    }

    /**
     * Étape 3 : après le retour du client depuis la passerelle MonCash (voir
     * config/moncash.php pour l'URL de retour à déclarer chez Digicel), vérifie
     * le résultat réel du paiement à partir de l'identifiant de transaction
     * MonCash reçu en paramètre ?transactionId=... sur l'URL de retour.
     */
    public static function retrieveTransaction(string $transactionId): array
    {
        $auth = self::getAccessToken();
        if (!$auth['ok']) {
            return $auth;
        }

        $response = self::request(
            'POST',
            MONCASH_API_BASE . '/v1/RetrieveTransactionPayment',
            json_encode(['transactionId' => $transactionId]),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $auth['access_token'],
            ]
        );

        if (!$response['ok']) {
            return $response;
        }

        return self::formatPayment($response['data']['payment'] ?? null, $transactionId);
    }

    /**
     * Vérification par NOTRE référence de commande (orderId), plutôt que par
     * l'identifiant MonCash. Utile pour :
     *   - la réconciliation manuelle admin (bouton "Vérifier maintenant" sur
     *     un dépôt resté "en attente" — client qui a payé mais a fermé son
     *     navigateur avant la redirection de retour) ;
     *   - une tâche planifiée qui nettoierait périodiquement les dépôts en
     *     attente depuis trop longtemps.
     * Endpoint /v1/RetrieveOrderPayment (voir doc Digicel Business API,
     * section "Retrieve Order Payment").
     */
    public static function retrieveOrderPayment(string $orderId): array
    {
        $auth = self::getAccessToken();
        if (!$auth['ok']) {
            return $auth;
        }

        $response = self::request(
            'POST',
            MONCASH_API_BASE . '/v1/RetrieveOrderPayment',
            json_encode(['orderId' => $orderId]),
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $auth['access_token'],
            ]
        );

        if (!$response['ok']) {
            return $response;
        }

        return self::formatPayment($response['data']['payment'] ?? null, null, $orderId);
    }

    /**
     * Met en forme la réponse "payment" de MonCash en un tableau stable pour
     * le reste de l'application. Expose explicitement 'successful' (bool) :
     * un objet "payment" existant ne veut PAS dire que l'argent a été payé
     * (MonCash renvoie aussi un payment pour un statut échoué/annulé) — voir
     * le champ texte 'message' de l'API ("successful" en cas de succès réel).
     */
    private static function formatPayment(?array $payment, ?string $fallbackTransactionId, ?string $fallbackReference = null): array
    {
        if (!$payment) {
            return ['ok' => false, 'error' => "Transaction MonCash introuvable."];
        }

        $rawMessage = (string) ($payment['message'] ?? '');

        return [
            'ok'             => true,
            'successful'     => strtolower($rawMessage) === 'successful',
            'reference'      => $payment['reference'] ?? $fallbackReference, // notre orderId d'origine
            'transaction_id' => $payment['transaction_id'] ?? $fallbackTransactionId,
            'amount'         => isset($payment['cost']) ? (float) $payment['cost'] : null,
            'payer'          => $payment['payer'] ?? null,
            'message'        => $rawMessage !== '' ? $rawMessage : null,
        ];
    }

    /**
     * Appel HTTP bas niveau (cURL natif, aucune dépendance externe).
     *
     * La sandbox Digicel a des micro-coupures/lenteurs par moments (le
     * symptôme typique côté client : le bouton "Payer avec MonCash" ne
     * redirige pas, une fois sur deux, sans que rien de notre côté n'ait
     * changé). Pour absorber ça sans faire attendre le client
     * indéfiniment :
     *   - une seule reprise automatique, après une courte pause, mais
     *     uniquement pour les échecs de transport (timeout, connexion
     *     refusée, DNS...) — jamais pour une réponse HTTP d'erreur propre
     *     (401, 400...) qui, elle, ne se réglera pas en réessayant ;
     *   - chaque échec (transport ou HTTP) est journalisé via error_log()
     *     avec assez de détail pour diagnostiquer (méthode, endpoint,
     *     erreur cURL, code HTTP, début de la réponse), sans jamais logguer
     *     les en-têtes d'autorisation ni les identifiants MonCash.
     */
    private static function request(string $method, string $url, ?string $body, array $headers, bool $isRetry = false): array
    {
        // Filet de sécurité : quel que soit le `max_execution_time` du
        // serveur (souvent 30s par défaut chez un hébergeur mutualisé), on
        // redonne ici 60s au script PHP avant chaque appel réseau vers
        // MonCash. Sans ça, un simple ralentissement de la sandbox peut
        // faire tuer le script en plein milieu — sans AUCUN message affiché
        // au client (page qui reste bloquée puis rien) — bien avant que nos
        // propres délais d'attente cURL ci-dessous n'aient eu la chance de
        // se déclencher proprement. `@` au cas où la fonction serait
        // désactivée côté hébergeur (disable_functions) : dans ce cas on ne
        // fait rien de plus qu'avant, sans casser l'appel.
        @set_time_limit(60);

        $headers[] = 'Accept: application/json';

        $ch = curl_init($url);
        $options = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];

        // Bundle de certificats CA optionnel (voir config/moncash.php) :
        // règle l'erreur fréquente en local (XAMPP/WAMP Windows, antivirus qui
        // inspecte le HTTPS...) "SSL certificate problem: self-signed
        // certificate in certificate chain", sans désactiver la vérification.
        if (defined('MONCASH_CA_BUNDLE') && MONCASH_CA_BUNDLE !== '' && is_readable(MONCASH_CA_BUNDLE)) {
            $options[CURLOPT_CAINFO] = MONCASH_CA_BUNDLE;
        }

        curl_setopt_array($ch, $options);
        $raw      = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            self::logFailure($method, $url, 'échec de transport (cURL #' . $curlErrno . ') : ' . $curlErr, null, null);

            // Une seule reprise, après une courte pause, uniquement pour un
            // vrai souci de transport (jamais si on a déjà réessayé).
            if (!$isRetry) {
                usleep(300000); // 0,3 s
                return self::request($method, $url, $body, $headers, true);
            }

            return ['ok' => false, 'error' => "Impossible de contacter MonCash pour le moment (problème réseau). Merci de réessayer dans un instant."];
        }

        $data = json_decode($raw, true);
        if ($status < 200 || $status >= 300) {
            $msg = $data['message'] ?? $data['error_description'] ?? $data['error'] ?? ('Erreur MonCash (HTTP ' . $status . ')');
            self::logFailure($method, $url, $msg, $status, $raw);
            return ['ok' => false, 'error' => $msg, 'status' => $status];
        }

        return ['ok' => true, 'data' => $data ?? [], 'status' => $status];
    }

    /**
     * Journalise un échec d'appel MonCash dans le journal serveur PHP
     * (error_log — même convention que le reste de l'application, voir
     * config/config.php). Ne jamais inclure $headers ni le corps de la
     * requête sortante (contient potentiellement le jeton Bearer) ; seul le
     * corps de la RÉPONSE MonCash est tronqué et inclus, pour diagnostic.
     */
    private static function logFailure(string $method, string $url, string $message, ?int $status, ?string $responseBody): void
    {
        $endpoint = parse_url($url, PHP_URL_PATH) ?: $url;
        error_log(sprintf(
            '[MonCash] %s %s -> %s%s%s',
            $method,
            $endpoint,
            $message,
            $status !== null ? ' (HTTP ' . $status . ')' : '',
            $responseBody ? ' | réponse: ' . substr($responseBody, 0, 300) : ''
        ));
    }

    /**
     * --- Cache disque du jeton OAuth (best-effort) -----------------------
     * Un fichier par mode+identifiants (sandbox/live), dans le dossier
     * temporaire du système : jamais d'écriture requise dans l'arborescence
     * du projet, donc aucun souci de permissions/déploiement. Toute erreur
     * de lecture/écriture est silencieusement ignorée — le pire cas est une
     * ré-authentification à chaque appel, exactement comme avant.
     */
    private static function tokenCachePath(): string
    {
        $key = md5(MONCASH_MODE . '|' . MONCASH_CLIENT_ID);
        return rtrim(sys_get_temp_dir(), '/\\') . '/gourde_numerique_moncash_token_' . $key . '.json';
    }

    private static function readCachedToken(): ?string
    {
        $path = self::tokenCachePath();
        if (!is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['token']) || empty($data['expires_at'])) {
            return null;
        }
        // Marge de sécurité de 60s pour ne jamais utiliser un jeton sur le
        // point d'expirer au moment précis de l'appel.
        if ((int) $data['expires_at'] <= (time() + 60)) {
            return null;
        }
        return (string) $data['token'];
    }

    private static function writeCachedToken(string $token, int $expiresIn): void
    {
        $path = self::tokenCachePath();
        $payload = json_encode(['token' => $token, 'expires_at' => time() + max(60, $expiresIn)]);
        @file_put_contents($path, $payload, LOCK_EX);
    }

    private static function clearCachedToken(): void
    {
        self::$tokenCache = null;
        @unlink(self::tokenCachePath());
    }
}
