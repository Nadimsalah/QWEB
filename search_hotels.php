<?php
header('Content-Type: application/json');

$dest = $_GET['dest'] ?? 'Paris';

// 1. Fetch real hotels using Nominatim (OpenStreetMap)
$url = "https://nominatim.openstreetmap.org/search?q=" . urlencode("hotel in " . $dest) . "&format=json&limit=5&email=contact@qoon.app";

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
        "timeout" => 5
    ]
];
$context = stream_context_create($opts);

$hotels = [];

// Fallback images
$images = [
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1551882547-ff40c0d5f502?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1564501049412-61c2a3083791?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1542314831-c6a4d27ce66f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
];

try {
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && count($data) > 0) {
            foreach ($data as $i => $item) {
                // Nominatim returns e.g. "Hotel Atlantic, Rue Ibn Sina, Safi,..."
                $parts = explode(',', $item['display_name']);
                $name = trim($parts[0]);
                
                // Exclude generic names if it matched the city instead of a hotel
                if (stripos($name, $dest) !== false && count($parts) < 3) {
                    continue; 
                }

                $hotels[] = [
                    'name' => $name,
                    'stars' => rand(3, 5), // Mock rating since OSM doesn't always have it
                    'price' => rand(60, 250), // Mock price
                    'img' => $images[$i % count($images)], // Random high quality hotel image
                    'features' => ['Free WiFi', 'City Center']
                ];
            }
        }
    }
} catch (Exception $e) {
    // Ignore and fallback
}

// 2. Fallback if Nominatim fails or finds no hotels
if (empty($hotels)) {
    // Generate realistic, authentic-sounding names based on the region
    $hotels = [
        ['name' => "Riad Al $dest", 'stars' => 5, 'price' => 145, 'img' => $images[0], 'features' => ['Traditional', 'Free Breakfast', 'Courtyard']],
        ['name' => "Dar $dest Boutique", 'stars' => 4, 'price' => 110, 'img' => $images[1], 'features' => ['City Center', 'Terrace', 'Authentic']],
        ['name' => "Palais de $dest", 'stars' => 5, 'price' => 280, 'img' => $images[2], 'features' => ['Luxury', 'Spa', 'Pool']],
        ['name' => "Hotel Medina $dest", 'stars' => 3, 'price' => 75, 'img' => $images[3], 'features' => ['Budget Friendly', 'Central Location']],
        ['name' => "Le Grand Hotel $dest", 'stars' => 4, 'price' => 165, 'img' => $images[4], 'features' => ['Business Center', 'Restaurant', 'Sea View']]
    ];
}

echo json_encode(['hotels' => array_slice($hotels, 0, 5)]);
