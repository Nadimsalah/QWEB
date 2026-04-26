<?php
header('Content-Type: application/json');

// ==========================================
// eSIM Access API Configuration
// Replace with your actual RT-AccessCode from https://esimaccess.com
// ==========================================
$accessCode = '473ead5bb2944631b9c313ae4713a450';
$apiUrl = 'https://api.esimaccess.com/api/v1/open/package/list';

// Request all packages to extract unique countries
// According to eSIM Access docs, you query packages and filter locations.
$payload = "{}";

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
$curlError = curl_error($ch);
curl_close($ch);

$countries = [];

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    
    // Check if eSIM access returns success
    if (isset($data['success']) && $data['success'] == true && isset($data['obj']['packageList'])) {
        $packages = $data['obj']['packageList'];
        
        foreach ($packages as $pkg) {
            // Extract location info from package
            if (isset($pkg['locationNetworkList']) && is_array($pkg['locationNetworkList'])) {
                foreach ($pkg['locationNetworkList'] as $loc) {
                    $code = strtolower($loc['locationCode'] ?? '');
                    $name = $loc['locationName'] ?? '';
                    
                    if (!empty($code) && !isset($countries[$code])) {
                        $countries[$code] = [
                            'code' => $code,
                            'name' => $name,
                            // Use flagcdn for high-quality flags, or fallback to the API's logo
                            'flag' => 'https://flagcdn.com/w80/' . $code . '.png'
                        ];
                    }
                }
            }
        }
    }
}

// Sort alphabetically by name
$output = array_values($countries);
usort($output, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

echo json_encode([
    'success' => true,
    'countries' => $output
]);
?>
