<?php
$files = glob('*.php');
$collection = [
    'info' => [
        'name' => 'QOON Backend API Collection',
        'description' => 'Auto-generated API documentation for the QOON mobile application.',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
    ],
    'item' => []
];

$markdown = "# QOON Full API Documentation\n\n";

$exclude = ['index.php', 'conn.php', 'scratch_db.php', 'settings.php', 'profile.php', 'shop.php', 'category.php', 'reel.php', 'flights.php', 'esim.php', 'hotels.php', 'track_order.php', 'topup.php', 'send.php', 'qpay.php', 'parse_postman.php'];

$folders = [];

foreach ($files as $file) {
    if (in_array(strtolower($file), array_map('strtolower', $exclude)) || strpos($file, 'test_') === 0 || strpos($file, 'ui_') === 0) continue;
    
    $content = file_get_contents($file);
    
    // Quick heuristic: does it have json_encode or look like an API?
    if (strpos($content, 'json_encode') === false && strpos($content, '$_POST') === false && strpos($content, '$_GET') === false && strpos($content, '$_FILES') === false) continue;
    
    preg_match_all('/\$_POST\s*\[\s*[\'"](.*?)[\'"]\s*\]/', $content, $postMatches);
    preg_match_all('/\$_GET\s*\[\s*[\'"](.*?)[\'"]\s*\]/', $content, $getMatches);
    preg_match_all('/\$_FILES\s*\[\s*[\'"](.*?)[\'"]\s*\]/', $content, $filesMatches);
    
    $postParams = array_unique($postMatches[1]);
    $getParams = array_unique($getMatches[1]);
    $fileParams = array_unique($filesMatches[1]);
    
    if (strpos($content, 'extract($_POST)') !== false) {
        $postParams[] = '(Dynamically Extracted Parameters)';
    }

    $folder = 'General';
    if (strpos(strtolower($file), 'driver') !== false) $folder = 'Driver';
    elseif (strpos(strtolower($file), 'shop') !== false) $folder = 'Shop';
    elseif (strpos(strtolower($file), 'user') !== false) $folder = 'User';
    elseif (strpos(strtolower($file), 'order') !== false) $folder = 'Orders';
    elseif (strpos(strtolower($file), 'post') !== false || strpos(strtolower($file), 'comment') !== false || strpos(strtolower($file), 'reel') !== false || strpos(strtolower($file), 'like') !== false) $folder = 'Posts & Comments';
    elseif (strpos(strtolower($file), 'jibler') !== false) $folder = 'Jibler';
    
    // POSTMAN
    $request = [
        'name' => $file,
        'request' => [
            'method' => (!empty($postParams) || !empty($fileParams)) ? 'POST' : 'GET',
            'url' => [
                'raw' => '{{base_url}}/' . $file . (!empty($getParams) ? '?' . implode('&', array_map(function($p){return $p.'=<value>';}, $getParams)) : ''),
                'host' => ['{{base_url}}'],
                'path' => [$file],
                'query' => []
            ]
        ],
        'response' => []
    ];
    
    foreach ($getParams as $param) {
        $request['request']['url']['query'][] = ['key' => $param, 'value' => '<value>'];
    }
    
    if (!empty($postParams) || !empty($fileParams)) {
        $request['request']['body'] = [
            'mode' => 'formdata',
            'formdata' => []
        ];
        foreach ($postParams as $param) {
            $request['request']['body']['formdata'][] = ['key' => $param, 'value' => '<value>', 'type' => 'text'];
        }
        foreach ($fileParams as $param) {
            $request['request']['body']['formdata'][] = ['key' => $param, 'type' => 'file'];
        }
    }
    
    if (!isset($collection['item'][$folder])) {
        $collection['item'][$folder] = [
            'name' => $folder,
            'item' => []
        ];
    }
    $collection['item'][$folder]['item'][] = $request;

    // MARKDOWN
    if(!isset($folders[$folder])) $folders[$folder] = [];
    $folders[$folder][] = [
        'name' => $file,
        'method' => (!empty($postParams) || !empty($fileParams)) ? 'POST' : 'GET',
        'post' => $postParams,
        'get' => $getParams,
        'files' => $fileParams
    ];
}

$collection['item'] = array_values($collection['item']);
file_put_contents('QOON_Postman_Collection.json', json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Generate Markdown
foreach($folders as $folderName => $apis) {
    $markdown .= "## 📁 $folderName\n\n";
    foreach($apis as $api) {
        $markdown .= "### 🔹 `{$api['name']}`\n";
        $markdown .= "**Method:** `{$api['method']}`\n\n";
        if (!empty($api['get'])) {
            $markdown .= "**GET Parameters:**\n";
            foreach($api['get'] as $p) $markdown .= "- `$p`\n";
            $markdown .= "\n";
        }
        if (!empty($api['post'])) {
            $markdown .= "**POST Parameters:**\n";
            foreach($api['post'] as $p) $markdown .= "- `$p`\n";
            $markdown .= "\n";
        }
        if (!empty($api['files'])) {
            $markdown .= "**FILE Parameters:**\n";
            foreach($api['files'] as $p) $markdown .= "- `$p` (File)\n";
            $markdown .= "\n";
        }
        $markdown .= "---\n";
    }
}

file_put_contents('API_DOCUMENTATION.md', $markdown);
echo "Documentation Rebuilt Successfully.";
