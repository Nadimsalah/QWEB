<?php
$file = 'NanoBananaApi.php';
$content = file_get_contents($file);

$search3 = "    // Sync mode immediately returns a URL\n    if (!empty(\$data['generation_url'])) {\n        \$syncId = 'SYNC_' . uniqid();\n        file_put_contents(\$storageDir . 'task_' . \$syncId . '.json', json_encode([\n            'data' => [\n                'successFlag' => 1,\n                'response' => ['resultImageUrl' => \$data['generation_url']]\n            ]\n        ]));\n        echo json_encode(['code' => 200, 'data' => ['taskId' => \$syncId]]);\n        exit;\n    }";

$replace3 = <<<PHP
    // Sync mode immediately returns a URL
    if (!empty(\$data['generation_url'])) {
        \$genUrl = \$data['generation_url'];
        
        // Proxy download to ensure accessibility
        \$ch2 = curl_init(\$genUrl);
        curl_setopt(\$ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch2, CURLOPT_HTTPHEADER, ["apikey: \$apiKey"]);
        \$imgData = curl_exec(\$ch2);
        curl_close(\$ch2);
        
        if (\$imgData && strlen(\$imgData) > 100) {
            \$finalFilename = 'vto_res_' . uniqid() . '.jpg';
            \$finalPath = \$storageDir . \$finalFilename;
            file_put_contents(\$finalPath, \$imgData);
            \$finalUrl = uploadToPublicHost(\$finalPath, \$debugErr) ?: \$genUrl;
        } else {
            \$finalUrl = \$genUrl;
        }

        \$syncId = 'SYNC_' . uniqid();
        file_put_contents(\$storageDir . 'task_' . \$syncId . '.json', json_encode([
            'data' => [
                'successFlag' => 1,
                'response' => ['resultImageUrl' => \$finalUrl]
            ]
        ]));
        echo json_encode(['code' => 200, 'data' => ['taskId' => \$syncId]]);
        exit;
    }
PHP;

$content = str_replace($search3, $replace3, $content);

$search4 = "    if ((\$data['status'] ?? '') === 'succeeded' && !empty(\$data['generation_url'])) {\n        \$out = [\n            'data' => [\n                'successFlag' => 1,\n                'response' => ['resultImageUrl' => \$data['generation_url']]\n            ]\n        ];\n        file_put_contents(\$resultFile, json_encode(\$out));\n        echo json_encode(\$out);\n        exit;\n    }";

$replace4 = <<<PHP
    if ((\$data['status'] ?? '') === 'succeeded' && !empty(\$data['generation_url'])) {
        \$genUrl = \$data['generation_url'];
        \$ch2 = curl_init(\$genUrl);
        curl_setopt(\$ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(\$ch2, CURLOPT_HTTPHEADER, ["apikey: \$apiKey"]);
        \$imgData = curl_exec(\$ch2);
        curl_close(\$ch2);
        
        if (\$imgData && strlen(\$imgData) > 100) {
            \$finalFilename = 'vto_res_' . uniqid() . '.jpg';
            \$finalPath = \$storageDir . \$finalFilename;
            file_put_contents(\$finalPath, \$imgData);
            \$finalUrl = uploadToPublicHost(\$finalPath, \$debugErr) ?: \$genUrl;
        } else {
            \$finalUrl = \$genUrl;
        }

        \$out = [
            'data' => [
                'successFlag' => 1,
                'response' => ['resultImageUrl' => \$finalUrl]
            ]
        ];
        file_put_contents(\$resultFile, json_encode(\$out));
        echo json_encode(\$out);
        exit;
    }
PHP;

$content = str_replace($search4, $replace4, $content);

file_put_contents($file, $content);
echo "Fixed proxying Pruna output.\n";
?>
