<?php
// Debug: show last VTO result — open in browser: http://localhost/userDriver/UserDriverApi/debug_last_vto.php
header('Content-Type: text/html; charset=utf-8');

$storageDir = __DIR__ . '/vto_temp/';
$files = glob($storageDir . 'task_*.json') ?: [];
usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

echo "<h2>Last VTO Task Results</h2>";
if (!$files) { echo "<p>No cached task files found in vto_temp/</p>"; exit; }

foreach (array_slice($files, 0, 3) as $f) {
    $data = json_decode(file_get_contents($f), true);
    $name = basename($f);
    $age  = round((time() - filemtime($f)) / 60) . ' min ago';
    echo "<h3>$name ($age)</h3>";
    echo "<pre style='background:#111;color:#0f0;padding:10px;border-radius:6px;overflow:auto;font-size:12px'>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

    // Try to display the result image
    $url = $data['data']['response']['resultImageUrl']
        ?? $data['data']['generation_url']
        ?? '';
    if ($url) {
        echo "<p><strong>Result URL:</strong> <a href='$url' target='_blank'>$url</a></p>";
        echo "<img src='$url' style='max-width:300px;border:2px solid #0f0;border-radius:8px;display:block;margin:10px 0'
              onerror=\"this.style.border='2px solid red';this.alt='FAILED TO LOAD'\">";
        echo "<p style='color:red'><em>(If image above is broken, the URL is inaccessible from browser)</em></p>";
    } else {
        echo "<p style='color:orange'>⚠️ No result URL found in this task file</p>";
    }
    echo "<hr>";
}
?>
