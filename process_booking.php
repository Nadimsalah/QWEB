<?php
header('Content-Type: application/json');

require_once 'includes/TrawexAPI.php';

// Read JSON input
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$sessionId = $data['session_id'] ?? '';
$fareSourceCode = $data['fare_source_code'] ?? '';
$paxDetails = $data['paxDetails'] ?? [];

if (empty($sessionId) || empty($fareSourceCode) || empty($paxDetails)) {
    echo json_encode(['success' => false, 'message' => 'Missing session_id, fare_source_code, or paxDetails']);
    exit;
}

$trawex = new TrawexAPI();

// 1. Validate Fare (Revalidate)
$revalidateResponse = $trawex->checkAvailability($sessionId, $fareSourceCode);

if (!$revalidateResponse || !isset($revalidateResponse['IsValid']) || $revalidateResponse['IsValid'] === 'false') {
    $error = $revalidateResponse['Errors']['ErrorMessage'] ?? 'Selected fare is no longer available or price changed.';
    echo json_encode(['success' => false, 'message' => 'Validation Failed: ' . $error, 'step' => 'Revalidate']);
    exit;
}

// Fare rules (Optional, we log it or ignore for now, the UI doesn't explicitly display it unless requested)
// $trawex->getFareRules($sessionId, $fareSourceCode);

// 2. Book Flight
$bookResponse = $trawex->bookFlight($sessionId, $fareSourceCode, $paxDetails);

if (!$bookResponse || $bookResponse['Success'] !== 'true') {
    $error = $bookResponse['Errors'][0]['ErrorMessage'] ?? 'Booking failed at the airline level.';
    echo json_encode(['success' => false, 'message' => 'Booking Failed: ' . $error, 'step' => 'Booking']);
    exit;
}

$uniqueId = $bookResponse['UniqueID'] ?? '';
$status = $bookResponse['Status'] ?? '';

if (empty($uniqueId)) {
    echo json_encode(['success' => false, 'message' => 'Booking succeeded but no UniqueID returned', 'step' => 'Booking']);
    exit;
}

$isTicketed = ($status === 'Ticketed');

// 3. Ticket Order (If not already ticketed, Non-LCC)
if (!$isTicketed && in_array($status, ['Confirmed', 'Ticket In Process'])) {
    $ticketResponse = $trawex->ticketOrder($sessionId, $fareSourceCode, $uniqueId);
    if ($ticketResponse && $ticketResponse['Success'] === 'true') {
        $isTicketed = true;
    }
}

// 4. Trip Details (Get final PNR and E-Ticket)
$tripResponse = $trawex->getTripDetails($sessionId, $uniqueId);

$pnr = '';
$ticketNumbers = [];

if ($tripResponse && $tripResponse['Success'] === 'true' && isset($tripResponse['TripDetails'])) {
    $paxList = $tripResponse['TripDetails']['PassengerDetails'] ?? [];
    foreach ($paxList as $pax) {
        if (!empty($pax['TicketNumber'])) {
            $ticketNumbers[] = $pax['TicketNumber'];
        }
    }
    
    // Grab PNR from the flight segments or top level if available
    $segments = $tripResponse['TripDetails']['FlightSegments'] ?? [];
    foreach ($segments as $seg) {
        if (!empty($seg['PNR'])) {
            $pnr = $seg['PNR'];
            break; // take first PNR
        }
    }
}

if (empty($pnr)) {
    $pnr = 'TRW-' . substr(md5($uniqueId), 0, 6); // Fallback reference
}

echo json_encode([
    'success' => true,
    'message' => 'Booking successfully completed!',
    'unique_id' => $uniqueId,
    'pnr' => strtoupper($pnr),
    'tickets' => $ticketNumbers,
    'is_ticketed' => $isTicketed,
    'raw_trip' => $tripResponse
]);
