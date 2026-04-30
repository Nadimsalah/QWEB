<?php
header('Content-Type: application/json');

require_once 'includes/TrawexAPI.php';

$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';
$date = $_GET['depart_date'] ?? '';
$return_date = $_GET['return_date'] ?? '';
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;
$infants = $_GET['infants'] ?? 0;
$trip_class = $_GET['trip_class'] ?? '0'; // 0 = Economy, 1 = Business, 2 = First Class

if (empty($origin) || empty($destination) || empty($date)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Map trip class to standard names
$classMap = ['0' => 'Economy', '1' => 'Business', '2' => 'First'];
$cabinClass = $classMap[$trip_class] ?? 'Economy';

$trawex = new TrawexAPI();
$results = $trawex->searchFlights($origin, $destination, $date, $return_date, $adults, $children, $infants, $cabinClass);

echo json_encode($results);
?>
