<?php
header('Content-Type: application/json');

$API_TOKEN = '0ca3dc3467606e4a114830217d4adf73';

$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['depart_date'] ?? '';
$return_date = $_GET['return_date'] ?? '';
$trip_class = $_GET['trip_class'] ?? '0'; // 0 = Economy, 1 = Business, 2 = First Class

if (empty($origin) || empty($destination) || empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Map trip class to v3 format (Y, C, F)
$classMap = ['0' => 'Y', '1' => 'C', '2' => 'F'];
$v3Class = $classMap[$trip_class] ?? 'Y';

// --- 1. TRY AVIASALES V3 (LIVE PRICES) ---
$urlV3 = "https://api.travelpayouts.com/aviasales/v3/prices_for_dates?origin={$origin}&destination={$destination}&departure_at={$date}&currency=usd&sorting=price&direct=false&limit=30&token={$API_TOKEN}";
if (!empty($return_date)) {
    $urlV3 .= "&return_at={$return_date}";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlV3);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$responseV3 = curl_exec($ch);
$dataV3 = json_decode($responseV3, true);

$finalFlights = [];

if (isset($dataV3['success']) && $dataV3['success'] && !empty($dataV3['data'])) {
    foreach ($dataV3['data'] as $f) {
        $finalFlights[] = [
            'price' => $f['value'] ?? $f['price'],
            'airline' => $f['airline'],
            'flight_number' => $f['flight_number'],
            'departure_at' => $f['departure_at'],
            'return_at' => $f['return_at'] ?? null,
            'expires_at' => $f['expires_at'] ?? null
        ];
    }
}

// --- 2. FALLBACK TO V1 (CACHED PRICES) IF V3 IS EMPTY ---
if (empty($finalFlights)) {
    $urlV1 = "https://api.travelpayouts.com/v1/prices/cheap?currency=usd&origin={$origin}&destination={$destination}&depart_date={$date}&token={$API_TOKEN}";
    if (!empty($return_date)) {
        $urlV1 .= "&return_date={$return_date}";
    }
    
    curl_setopt($ch, CURLOPT_URL, $urlV1);
    $responseV1 = curl_exec($ch);
    $dataV1 = json_decode($responseV1, true);
    
    if (isset($dataV1['success']) && $dataV1['success'] && !empty($dataV1['data'])) {
        foreach ($dataV1['data'] as $airportCode => $flights) {
            foreach ($flights as $f) {
                $finalFlights[] = [
                    'price' => $f['price'],
                    'airline' => $f['airline'],
                    'flight_number' => $f['flight_number'] ?? '',
                    'departure_at' => $f['departure_at'] ?? $date,
                    'return_at' => $f['return_at'] ?? null
                ];
            }
        }
    }
}

// --- 3. LAST RESORT: CALENDAR (ANY DATE IN THE MONTH) ---
$isAlternative = false;
if (empty($finalFlights)) {
    $month = substr($date, 0, 7);
    $urlCal = "https://api.travelpayouts.com/v1/prices/calendar?currency=usd&origin={$origin}&destination={$destination}&depart_date={$month}&token={$API_TOKEN}";
    
    curl_setopt($ch, CURLOPT_URL, $urlCal);
    $responseCal = curl_exec($ch);
    $dataCal = json_decode($responseCal, true);
    
    if (isset($dataCal['success']) && $dataCal['success'] && !empty($dataCal['data'])) {
        $isAlternative = true;
        foreach ($dataCal['data'] as $fDate => $f) {
            $finalFlights[] = [
                'price' => $f['price'],
                'airline' => $f['airline'],
                'flight_number' => $f['flight_number'] ?? '',
                'departure_at' => $f['departure_at'] ?? $fDate,
                'return_at' => $f['return_at'] ?? null
            ];
        }
    }
}

curl_close($ch);

// Sort by price
usort($finalFlights, function($a, $b) {
    return $a['price'] <=> $b['price'];
});

// Limit to 20 results for performance
$finalFlights = array_slice($finalFlights, 0, 20);

echo json_encode([
    'success' => true,
    'is_alternative_dates' => $isAlternative,
    'data' => [
        $destination => $finalFlights
    ]
]);
?>
