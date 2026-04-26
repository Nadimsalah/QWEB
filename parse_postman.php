<?php
$url = "https://docs.esimaccess.com/api/collections/11154627/2s93mBxf3q?environment=11154627-1a6283c8-0422-49ea-adea-0919cf18c0cc&segregateAuth=true&versionTag=latest";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    "Accept: application/json"
]);
$data = curl_exec($ch);
curl_close($ch);

$json = json_decode($data, true);

if (!$json || !isset($json['collection']['item'])) {
    echo "Failed to get collection data.\n";
    echo $data;
    exit;
}

function extractUrls($items) {
    foreach ($items as $item) {
        if (isset($item['item'])) {
            extractUrls($item['item']);
        } elseif (isset($item['request']['url']['raw'])) {
            echo $item['name'] . ": " . $item['request']['method'] . " " . $item['request']['url']['raw'] . "\n";
        } elseif (isset($item['request']['url'])) {
            $url = is_string($item['request']['url']) ? $item['request']['url'] : (isset($item['request']['url']['raw']) ? $item['request']['url']['raw'] : json_encode($item['request']['url']));
            echo $item['name'] . ": " . $item['request']['method'] . " " . $url . "\n";
        }
    }
}

extractUrls($json['collection']['item']);
