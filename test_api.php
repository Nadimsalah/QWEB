<?php
$apiKey = "0f42b65364c4334e76c294a44a82e6dc";
$payloadArray = [
    "prompt"      => "virtual try on",
    "type"        => "IMAGETOIAMGE",
    "imageUrls"   => ["https://i.ibb.co/60q8jM6/user.jpg", "https://i.ibb.co/TmsZzKV/product.jpg"],
    "numImages"   => 1,
    "image_size"  => "9:16",
    "callBackUrl" => "https://example.com/callback"
];

$ch = curl_init("https://api.nanobananaapi.ai/api/v1/nanobanana/generate");
curl_setopt_array($ch, [
    CURLOPT_POST           => 1,
    CURLOPT_POSTFIELDS     => json_encode($payloadArray),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]
]);
$response = curl_exec($ch);
echo "Response for IMAGETOIAMGE with image_size: " . $response . "\n";

$payloadArray["type"] = "IMAGETOIMAGE";
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadArray));
$response = curl_exec($ch);
echo "Response for IMAGETOIMAGE with image_size: " . $response . "\n";

unset($payloadArray["image_size"]);
$payloadArray["type"] = "IMAGETOIAMGE";
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadArray));
$response = curl_exec($ch);
echo "Response for IMAGETOIAMGE without image_size: " . $response . "\n";

$payloadArray["type"] = "IMAGETOIMAGE";
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadArray));
$response = curl_exec($ch);
echo "Response for IMAGETOIMAGE without image_size: " . $response . "\n";

curl_close($ch);
?>
