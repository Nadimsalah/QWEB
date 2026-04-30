<?php
header('Content-Type: application/json');
define('FROM_UI', true);

if (!function_exists('loadEnv')) require_once __DIR__ . '/security.php';
loadEnv(__DIR__ . '/.env');

$user_id  = getenv('TRAWEX_USER_ID');   // salaheddinenadim_testAPI
$password = getenv('TRAWEX_PASSWORD');
$access   = getenv('TRAWEX_ACCESS') ?: 'Test';
$ip       = getenv('TRAWEX_IP') ?: '127.0.0.1';

$payload = json_encode([
    'user_id'       => $user_id,
    'user_password' => $password,
    'access'        => $access,
    'ip_address'    => $ip,
    'requiredCurrency' => 'USD',
    'journeyType'   => 'OneWay',
    'OriginDestinationInfo' => [[
        'departureDate'          => '2026-06-15',
        'airportOriginCode'      => 'RBA',
        'airportDestinationCode' => 'BCN',
    ]],
    'class'   => 'Economy',
    'adults'  => 1,
    'childs'  => 0,
    'infants' => 0,
]);

// Try every plausible slug pattern
$slugs = [
    'aeroVE5',       // original (always fails)
    'testAPI',       // suffix of user_id
    'salaheddinenadim_testAPI', // full user_id as slug
    'aero',
    'aerob2b',
    'b2b',
    'flight',
    'flights',
    'booking',
    'api',
    'v1',
    'v2',
];

$results = [];
foreach ($slugs as $slug) {
    $url = "https://travelnext.works/api/{$slug}/search";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($raw, true);
    $results[] = [
        'slug'      => $slug,
        'http_code' => $code,
        'response'  => $decoded ?: substr($raw, 0, 80),
    ];

    // Stop if we get something other than 404
    if ($code !== 404) break;
}

echo json_encode($results, JSON_PRETTY_PRINT);
