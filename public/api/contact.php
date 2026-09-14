<?php
/**
 * public/api/contact.php
 * Endpoint AJAX : soumission du formulaire public de contact (/contact).
 * Réponse au format JSON. Action unique : ?action=send (POST)
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? 'send';
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$controller = new HomeController();

try {
    switch ($action) {
        case 'send':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'Méthode non autorisée.']);
                break;
            }
            echo json_encode($controller->submitContact($input));
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Action inconnue.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
