<?php
/**
 * public/api/transactions.php
 * Endpoint AJAX : envoi d'argent entre utilisateurs, paiement de factures/marchands.
 * Réponses au format JSON. Actions : ?action=envoyer|paiement
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$controller = new TransactionController();

try {
    switch ($action) {
        case 'envoyer':
            echo json_encode($controller->apiEnvoyer($input));
            break;

        case 'paiement':
            echo json_encode($controller->apiPaiement($input));
            break;

        case 'prochaine_reference':
            echo json_encode($controller->apiProchaineReference($input));
            break;

        case 'demander':
            echo json_encode($controller->apiDemander($input));
            break;

        case 'mes_demandes':
            echo json_encode($controller->apiMesDemandes());
            break;

        case 'accepter_demande':
            echo json_encode($controller->apiAccepterDemande($input));
            break;

        case 'refuser_demande':
            echo json_encode($controller->apiRefuserDemande($input));
            break;

        case 'annuler_demande':
            echo json_encode($controller->apiAnnulerDemande($input));
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Action inconnue.']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erreur serveur.']);
}
