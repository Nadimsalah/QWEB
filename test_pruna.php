<?php
$apiKey = "pru_AdE9f0Zx_wZMX8GJzqQjGvcB5CizoY5G";

// A public person image
$userUrl = "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400";
// A public garment image 
$prodUrl = "https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=400";

// 1. Upload to Pruna
function up($url, $key) {
    $tmp = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tmp, file_get_contents($url));
    $ch = curl_init("https://api.pruna.ai/v1/files");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['content' => new CURLFile($tmp, 'image/jpeg', 'file.jpg')]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $key", "Accept: application/json"]);
    $res = curl_exec($ch);
    curl_close($ch);
    unlink($tmp);
    return json_decode($res, true)['urls']['get'];
}

$u = up($userUrl, $apiKey);
$p = up($prodUrl, $apiKey);

echo "User: $u\nProd: $p\n";

$payload = json_encode([
    "input" => [
        "prompt" => "Virtual try-on: Take the clothing from the SECOND image and put it on the person in the FIRST image.",
        "images" => [$u, $p],
        "aspect_ratio" => "match_input_image",
        "turbo" => false
    ]
]);

$ch = curl_init("https://api.pruna.ai/v1/predictions");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apiKey", "Content-Type: application/json", "Model: p-image-edit", "Try-Sync: true"]);
$res = curl_exec($ch);
curl_close($ch);

echo "Submit: $res\n";
?>
