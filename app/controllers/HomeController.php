<?php
/**
 * app/controllers/HomeController.php
 */
class HomeController
{
    public function index(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }

        require VIEWS_PATH . '/home/index.php';
    }

    /** Carte interactive publique des agences BNC à travers le pays */
    public function agencesBnc(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }

        $agences = BncAgence::allActive();

        require VIEWS_PATH . '/home/agences_bnc.php';
    }

    /** Page publique "Fonctionnalités" : présentation détaillée de chaque service */
    public function fonctionnalites(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }

        require VIEWS_PATH . '/home/fonctionnalites.php';
    }

    /** Page publique "Objectifs" : mission et orientations de la plateforme */
    public function objectifs(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }

        require VIEWS_PATH . '/home/objectifs.php';
    }

    /** Page publique "Contact" : coordonnées + formulaire de contact */
    public function contact(): void
    {
        $currentUser = null;
        if (is_logged_in()) {
            $u = User::find(current_user_id());
            $currentUser = $u ? User::toPublic($u) : null;
        }

        require VIEWS_PATH . '/home/contact.php';
    }

    /**
     * Traite la soumission AJAX du formulaire de contact (appelée par
     * public/api/contact.php). Retourne un tableau ['ok' => bool, ...].
     */
    public function submitContact(array $input): array
    {
        $data = [
            'name'    => trim((string) ($input['name'] ?? '')),
            'email'   => trim((string) ($input['email'] ?? '')),
            'phone'   => trim((string) ($input['phone'] ?? '')),
            'subject' => trim((string) ($input['subject'] ?? '')),
            'message' => trim((string) ($input['message'] ?? '')),
        ];

        $errors = ContactMessage::validate($data);
        if (!empty($errors)) {
            return ['ok' => false, 'error' => $errors[0]];
        }

        if ($data['phone'] !== '') {
            $data['phone'] = normalize_phone($data['phone']);
        }

        ContactMessage::create($data);

        return ['ok' => true];
    }
}
