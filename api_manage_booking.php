<?php
header('Content-Type: application/json');

require_once 'includes/TrawexAPI.php';

// Allow POST input
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true) ?: $_POST;

$action = $data['action'] ?? $_GET['action'] ?? '';
$sessionId = $data['session_id'] ?? $_GET['session_id'] ?? '';
$uniqueId = $data['pnr'] ?? $_GET['pnr'] ?? ''; // PNR or UniqueID

if (empty($action) || empty($sessionId) || empty($uniqueId)) {
    echo json_encode(['success' => false, 'message' => 'Missing action, session_id, or pnr']);
    exit;
}

$trawex = new TrawexAPI();

switch ($action) {
    case 'trip_details':
        $details = $trawex->getTripDetails($sessionId, $uniqueId);
        if ($details && isset($details['Success']) && $details['Success'] === 'true') {
            echo json_encode(['success' => true, 'data' => $details['TripDetails'] ?? []]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch trip details. Verify PNR.']);
        }
        break;

    case 'refund_quote':
        // For Quote, we usually need PaxDetails, but for MVP we might try calling it empty or just standard ADT
        $pax = [['type' => 'ADT']]; // Minimum payload
        $quote = $trawex->getRefundQuote($sessionId, $uniqueId, $pax);
        if ($quote && isset($quote['Success']) && $quote['Success'] === 'true') {
            echo json_encode(['success' => true, 'data' => $quote]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Refund Quote failed: ' . ($quote['Errors'][0]['ErrorMessage'] ?? '')]);
        }
        break;

    case 'process_refund':
        // Usually requires refund_quote first to get ptrUniqueID, but we'll attempt direct if allowed
        $pax = [['type' => 'ADT']];
        $refund = $trawex->processRefund($sessionId, $uniqueId, $pax);
        if ($refund && isset($refund['Success']) && $refund['Success'] === 'true') {
            echo json_encode(['success' => true, 'message' => 'Refund processed successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Refund failed: ' . ($refund['Errors'][0]['ErrorMessage'] ?? '')]);
        }
        break;

    case 'void_quote':
        $pax = [['type' => 'ADT']];
        $quote = $trawex->getVoidQuote($sessionId, $uniqueId, $pax);
        if ($quote && isset($quote['Success']) && $quote['Success'] === 'true') {
            echo json_encode(['success' => true, 'data' => $quote]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Void Quote failed: ' . ($quote['Errors'][0]['ErrorMessage'] ?? '')]);
        }
        break;

    case 'process_void':
        $pax = [['type' => 'ADT']];
        $void = $trawex->voidTicket($sessionId, $uniqueId, $pax);
        if ($void && isset($void['Success']) && $void['Success'] === 'true') {
            echo json_encode(['success' => true, 'message' => 'Ticket voided successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Void failed: ' . ($void['Errors'][0]['ErrorMessage'] ?? '')]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid post-ticketing action']);
}
