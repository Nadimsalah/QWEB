<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$country = isset($_GET['country']) ? strtolower(trim($_GET['country'])) : '';
$countryCode = isset($_GET['countryCode']) ? strtoupper(trim($_GET['countryCode'])) : '';

if (empty($countryCode)) {
    echo json_encode(["success" => false, "plans" => []]);
    exit;
}

$token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
$apiUrl = "https://api.zendit.io/v1/esim/offers?_limit=50&_offset=0&country=" . urlencode($countryCode);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
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
    if (isset($data['list']) && is_array($data['list'])) {
        foreach ($data['list'] as $offer) {
            $dataAmount = ($offer['dataUnlimited'] ?? false) ? "Unlimited Data" : ($offer['dataGB'] ?? 0) . " GB";
            $validity = ($offer['durationDays'] ?? 0) . " Days";

            // Format price using price details or fallback to cost if price missing
            $priceVal = 0;
            $divisor = 1;

            if (isset($offer['price']) && isset($offer['price']['suggestedFixed'])) {
                $priceVal = $offer['price']['suggestedFixed'];
                $divisor = isset($offer['price']['currencyDivisor']) ? $offer['price']['currencyDivisor'] : 1;
            } elseif (isset($offer['cost']) && isset($offer['cost']['fixed'])) {
                $priceVal = $offer['cost']['fixed'] * 1.3;
                $divisor = isset($offer['cost']['currencyDivisor']) ? $offer['cost']['currencyDivisor'] : 1;
            }

            if ($divisor > 1 && $priceVal > 0) {
                $priceVal = $priceVal / $divisor;
            }

            if ($priceVal <= 0)
                $priceVal = 5.00; // Failsafe

            $priceStr = "$" . number_format($priceVal, 2);

            $plans[] = [
                "id" => $offer['offerId'],
                "data" => $dataAmount,
                "validity" => $validity,
                "price" => $priceStr,
                "country" => ucfirst($country ?: "Global"),
                "provider" => $offer['brandName'] ?? "Zendit"
            ];
        }
    }
}

// Sort plans by price (cheapest first)
usort($plans, function ($a, $b) {
    $pa = floatval(str_replace('$', '', $a['price']));
    $pb = floatval(str_replace('$', '', $b['price']));
    return $pa <=> $pb;
});

echo json_encode([
    "success" => true,
    "plans" => $plans
]);
?>