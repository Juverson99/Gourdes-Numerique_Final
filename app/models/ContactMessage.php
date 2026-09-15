<?php
/**
 * app/models/ContactMessage.php
 * Messages envoyés par les visiteurs depuis le formulaire public /contact.
 * Consultés et gérés uniquement depuis l'admin (/admin/messages).
 */
class ContactMessage
{
    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Nombre total de messages et répartition par statut (pour la liste) */
    public static function counts(): array
    {
        $stmt = db()->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'nouveau') AS nouveaux,
                SUM(status = 'lu') AS lus,
                SUM(status = 'traite') AS traites
             FROM contact_messages"
        );
        $row = $stmt->fetch() ?: [];
        return [
            'total'    => (int) ($row['total'] ?? 0),
            'nouveaux' => (int) ($row['nouveaux'] ?? 0),
            'lus'      => (int) ($row['lus'] ?? 0),
            'traites'  => (int) ($row['traites'] ?? 0),
        ];
    }

    /**
     * Enregistre un nouveau message reçu depuis le formulaire public.
     * Retourne l'id du message créé.
     */
    public static function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message)
             VALUES (:name, :email, :phone, :subject, :message)'
        );
        $stmt->execute([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] !== '' ? $data['phone'] : null,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);
        return (int) db()->lastInsertId();
    }

    /** Change le statut de traitement d'un message (nouveau / lu / traité) */
    public static function updateStatus(int $id, string $status): void
    {
        if (!in_array($status, ['nouveau', 'lu', 'traite'], true)) {
            return;
        }
        $stmt = db()->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Valide les champs soumis par le formulaire public de contact.
     * Retourne un tableau d'erreurs (vide si tout est valide).
     */
    public static function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors[] = 'Votre nom est obligatoire.';
        }
        if (trim((string) ($data['email'] ?? '')) === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Une adresse courriel valide est obligatoire.';
        }
        if (trim((string) ($data['subject'] ?? '')) === '') {
            $errors[] = 'Le sujet de votre message est obligatoire.';
        }
        // Le téléphone est facultatif ici, mais s'il est renseigné il doit être valide
        // (même format que le reste de l'application : indicatif 509 + 8 chiffres).
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone !== '' && ($phoneError = validate_phone($phone))) {
            $errors[] = $phoneError;
        }
        if (trim((string) ($data['message'] ?? '')) === '') {
            $errors[] = 'Le contenu du message ne peut pas être vide.';
        } elseif (mb_strlen((string) $data['message']) > 5000) {
            $errors[] = 'Le message est trop long (5000 caractères maximum).';
        }

        return $errors;
    }
}
