<?php
/**
 * public/api/account.php
 * Endpoint AJAX : inscription, connexion, déconnexion, état de session.
 * Réponses au format JSON. Actions : ?action=signup|login|logout|me
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// L'inscription peut envoyer une photo de profil : le formulaire est alors soumis en
// multipart/form-data (via FormData côté JS) plutôt qu'en JSON, seul format qui permette
// de transporter un fichier. On lit donc directement $_POST/$_FILES dans ce cas.
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_starts_with($contentType, 'multipart/form-data')) {
    $input = $_POST;
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
}

$controller = new AccountController();

try {
    switch ($action) {
        case 'signup':
            echo json_encode($controller->signup($input, $_FILES['photo'] ?? []));
            break;

        case 'login':
            echo json_encode($controller->login($input));
            break;

        case 'logout':
            echo json_encode($controller->logout());
            break;

        case 'me':
            echo json_encode($controller->me());
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Action inconnue.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
