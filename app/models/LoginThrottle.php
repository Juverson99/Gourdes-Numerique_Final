<?php
/**
 * app/models/LoginThrottle.php
 * Protection élémentaire contre les attaques par force brute sur la
 * connexion (client ET admin, même formulaire) : verrouille temporairement
 * une clé (email + adresse IP) après plusieurs échecs consécutifs.
 *
 * Volontairement simple (une seule table, pas de dépendance externe) :
 * suffisant pour ralentir un script d'essais automatisés sans pénaliser
 * un utilisateur légitime qui se trompe une ou deux fois de mot de passe.
 */
class LoginThrottle
{
    /** Nombre d'échecs consécutifs autorisés avant verrouillage temporaire */
    private const MAX_ATTEMPTS = 5;

    /** Durée du verrouillage une fois le seuil atteint (secondes) */
    private const LOCK_SECONDS = 900; // 15 minutes

    /** Fenêtre glissante au-delà de laquelle on repart d'un compteur à zéro */
    private const WINDOW_SECONDS = 900; // 15 minutes

    /** Construit la clé de throttle : par couple email + IP (évite qu'un
     *  attaquant bloque le compte d'un tiers juste en connaissant son email,
     *  tout en limitant un poste attaquant qui teste plusieurs comptes). */
    private static function key(string $email): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        return mb_strtolower(trim($email)) . '|' . $ip;
    }

    /**
     * Le compte est-il actuellement verrouillé pour cette clé ? Retourne le
     * nombre de secondes restantes (0 si non verrouillé).
     */
    public static function secondsRemaining(string $email): int
    {
        $stmt = db()->prepare('SELECT locked_until FROM login_attempts WHERE identifier = ? LIMIT 1');
        $stmt->execute([self::key($email)]);
        $row = $stmt->fetch();
        if (!$row || $row['locked_until'] === null) {
            return 0;
        }
        $remaining = strtotime($row['locked_until']) - time();
        return $remaining > 0 ? $remaining : 0;
    }

    /** Enregistre un échec de connexion ; verrouille si le seuil est atteint. */
    public static function recordFailure(string $email): void
    {
        $key = self::key($email);
        $pdo = db();
        $stmt = $pdo->prepare('SELECT attempts, first_attempt_at FROM login_attempts WHERE identifier = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        $now = time();
        if (!$row || ($now - strtotime($row['first_attempt_at'])) > self::WINDOW_SECONDS) {
            // Pas d'historique récent : on repart d'un compteur à 1.
            $pdo->prepare(
                'INSERT INTO login_attempts (identifier, attempts, first_attempt_at, locked_until)
                 VALUES (?, 1, NOW(), NULL)
                 ON DUPLICATE KEY UPDATE attempts = 1, first_attempt_at = NOW(), locked_until = NULL'
            )->execute([$key]);
            return;
        }

        $attempts = (int) $row['attempts'] + 1;
        $lockedUntil = $attempts >= self::MAX_ATTEMPTS
            ? date('Y-m-d H:i:s', $now + self::LOCK_SECONDS)
            : null;

        $pdo->prepare(
            'UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE identifier = ?'
        )->execute([$attempts, $lockedUntil, $key]);
    }

    /** Réinitialise le compteur après une connexion réussie. */
    public static function clear(string $email): void
    {
        $stmt = db()->prepare('DELETE FROM login_attempts WHERE identifier = ?');
        $stmt->execute([self::key($email)]);
    }
}
