<?php
$OPENAI_API_KEY = "sk-d25ba3eadc464644a051ea2fe7d83f7a";
$apiUrl   = "https://api.deepseek.com/chat/completions";
$model    = "deepseek-chat";

$payload = [
    "model"           => $model,
    "response_format" => ["type" => "json_object"],
    "temperature"     => 0.1,
    "messages"        => [
        ["role" => "system", "content" => "You must respond with valid JSON containing a key 'decision'."],
        ["role" => "user",   "content" => "Hello"],
    ],
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $OPENAI_API_KEY",
        "Content-Type: application/json",
    ],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 20,
]);
$raw  = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);
echo "err: $err\n";
echo "raw: $raw\n";
