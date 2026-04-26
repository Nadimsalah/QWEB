<?php
$token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
$apiUrl = "https://api.zendit.io/v1/esim/offers?_limit=1000&_offset=0";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
$response = curl_exec($ch);
file_put_contents('test_esim_dump.json', $response);
echo "Done";
