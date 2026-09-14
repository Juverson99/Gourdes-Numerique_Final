<?php
/**
 * public/api/scanner.php
 * Endpoint AJAX : valide un code scanné/saisi manuellement (numéro de
 * téléphone) et retourne les informations du destinataire. Actions : ?action=lookup
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$controller = new TransactionController();

try {
    switch ($action) {
        case 'lookup':
            echo json_encode($controller->apiScannerLookup($input));
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Action inconnue.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
