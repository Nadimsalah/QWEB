<?php
$apiKey = "pru_AdE9f0Zx_wZMX8GJzqQjGvcB5CizoY5G";
$payload = json_encode(["input" => ["prompt" => "Virtual try on", "images" => ["https://picsum.photos/200/300", "https://picsum.photos/200/300"]]]);

function testModel($m, $payload, $apiKey) {
    $ch = curl_init("https://api.pruna.ai/v1/predictions");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey", "Content-Type: application/json", "Model: $m", "Try-Sync: true"]);
    $res = curl_exec($ch);
    curl_close($ch);
    echo "Model $m: $res\n";
}

testModel("p-try-on", $payload, $apiKey);
testModel("p-try-on-clothes", $payload, $apiKey);
testModel("p-vton", $payload, $apiKey);
?>
