<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$country = isset($_GET['country']) ? strtolower(trim($_GET['country'])) : '';
$countryCode = isset($_GET['countryCode']) ? strtoupper(trim($_GET['countryCode'])) : '';

if (empty($countryCode)) {
    echo json_encode(["success" => false, "plans" => []]);
    exit;
}

$accessCode = '473ead5bb2944631b9c313ae4713a450';
$apiUrl = "https://api.esimaccess.com/api/v1/open/package/list";

$payload = json_encode([
    'locationCode' => $countryCode
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "RT-AccessCode: $accessCode",
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$plans = [];

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success'] == true && isset($data['obj']['packageList'])) {
        foreach ($data['obj']['packageList'] as $offer) {
            
            // Volume is in bytes (usually), let's parse it to GB or MB
            $volumeBytes = isset($offer['volume']) ? floatval($offer['volume']) : 0;
            $dataAmount = "Unlimited Data";
            if ($volumeBytes > 0) {
                $gb = $volumeBytes / (1024 * 1024 * 1024); // Assuming bytes
                // Sometimes volume is in Megabytes? Actually the API doc says "Data values are in Bytes."
                if ($gb >= 1) {
                    $dataAmount = round($gb, 1) . " GB";
                } else {
                    $mb = $volumeBytes / (1024 * 1024);
                    $dataAmount = round($mb) . " MB";
                }
            }
            
            $validity = ($offer['duration'] ?? 0) . " " . ($offer['durationUnit'] ?? "Days");
            
            // eSIM Access API price is typically in cents or directly in currency?
            // "retailPrice": 71000 => usually this means cents if it's high, wait, 71000 might be $7.1 or something.
            // Documentation says "price: 10000". It's likely 4 decimals or cents.
            // Let's divide by 10000 for a realistic price, usually APIs use 10000 for $1.0000
            $rawPrice = isset($offer['retailPrice']) ? floatval($offer['retailPrice']) : (isset($offer['price']) ? floatval($offer['price']) : 50000);
            $priceVal = $rawPrice / 10000;
            if ($priceVal < 0.1) $priceVal = $rawPrice / 100; // fallback if it was cents
            
            // Add a 20% margin
            $priceVal = $priceVal * 1.20;
            $priceStr = "$" . number_format($priceVal, 2);

            // Extract operator from locationNetworkList
            $operator = "Multiple Networks";
            if (isset($offer['locationNetworkList']) && is_array($offer['locationNetworkList'])) {
                foreach ($offer['locationNetworkList'] as $loc) {
                    if (strtoupper($loc['locationCode'] ?? '') === $countryCode) {
                        if (isset($loc['operatorList']) && is_array($loc['operatorList']) && count($loc['operatorList']) > 0) {
                            $operatorNames = array_map(function($op) { return $op['operatorName']; }, $loc['operatorList']);
                            $operator = implode(", ", $operatorNames);
                        }
                    }
                }
            }

            $plans[] = [
                "id" => $offer['packageCode'] ?? '',
                "data" => $dataAmount,
                "validity" => $validity,
                "price" => $priceStr,
                "country" => ucfirst($country ?: "Global"),
                "provider" => $operator
            ];
        }
    }
}

// Sort plans by price (cheapest first)
usort($plans, function($a, $b) {
    $pa = floatval(str_replace('$', '', $a['price']));
    $pb = floatval(str_replace('$', '', $b['price']));
    return $pa <=> $pb;
});

echo json_encode([
    "success" => true,
    "plans" => $plans
]);
?>
