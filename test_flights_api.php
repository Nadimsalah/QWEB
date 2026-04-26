<?php
$API_TOKEN = '0ca3dc3467606e4a114830217d4adf73';
$origin = 'LON';
$destination = 'PAR';
$date = date('Y-m-d', strtotime('+7 days'));

// Try v1 cheap
$url1 = "https://api.travelpayouts.com/v1/prices/cheap?currency=usd&origin={$origin}&destination={$destination}&depart_date={$date}&token={$API_TOKEN}";
$res1 = file_get_contents($url1);
echo "V1 Cheap:\n" . $res1 . "\n\n";

// Try v3
$url3 = "https://api.travelpayouts.com/v3/prices_for_dates?origin={$origin}&destination={$destination}&departure_at={$date}&currency=usd&token={$API_TOKEN}";
$res3 = file_get_contents($url3);
echo "V3 Prices:\n" . $res3 . "\n\n";
?>
