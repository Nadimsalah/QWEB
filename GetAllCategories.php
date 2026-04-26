<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;



$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;
$Pro = $_POST["Pro"];
$Page = $_POST["Page"] ?? 0;

if($Pro==""){
	
	$Pro = "Normal";
}

$result = array();

if ($con) {
    try {
        $res = mysqli_query($con,"SELECT * FROM Categories WHERE Type='Top' AND Pro='$Pro' ORDER BY priority DESC LIMIT $Page, 10");
        if ($res && mysqli_num_rows($res) > 0) {
            $i = 0;
            while($row = mysqli_fetch_assoc($res)){
                $CategoryId = $row["CategoryId"];
                $res333 = mysqli_query($con,"SELECT count(*) FROM Shops WHERE CategoryId = '$CategoryId'");
                while($row333 = mysqli_fetch_assoc($res333)){
                     $row["countShops"] = $row333["count(*)"];
                }
                $result[] = $row;
                $test=4;
            }
        }
    } catch(Throwable $e) {}
}

// Dynamically determine host and directory path
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$dir = dirname($_SERVER['SCRIPT_NAME']);
// Remove trailing slash if present
$dir = rtrim($dir, '/\\');
$baseUrl = $protocol . $host . $dir;

// Inject "Book Flight" and "eSIM" categories natively at the end
array_push($result, [
    'CategoryId' => 'flights',
    'EnglishCategory' => 'Book Flight',
    'ArabCategory' => 'حجز طيران',
    'NameEn' => 'Book Flight',
    'Photo' => $baseUrl . '/flight_category.jpg',
    'countShops' => '1'
]);
array_push($result, [
    'CategoryId' => 'esims',
    'EnglishCategory' => 'Global eSIM',
    'ArabCategory' => 'إنترنت دولي',
    'NameEn' => 'Global eSIM',
    'Photo' => $baseUrl . '/esim_category.jpg',
    'countShops' => '1'
]);

// Fallback logic for offline mode / disconnected database
if (empty($result)) {
    $mockCategories = [
        ['CategoryId' => '1', 'EnglishCategory' => 'Food Delivery', 'ArabCategory' => 'توصيل الطلبات', 'Photo' => 'https://via.placeholder.com/300/2cb5e8/ffffff?text=Food', 'countShops' => '120'],
        ['CategoryId' => '2', 'EnglishCategory' => 'Groceries', 'ArabCategory' => 'بقالة', 'Photo' => 'https://via.placeholder.com/300/4a25e1/ffffff?text=Groceries', 'countShops' => '15'],
        ['CategoryId' => '3', 'EnglishCategory' => 'Pharmacy', 'ArabCategory' => 'صيدلية', 'Photo' => 'https://via.placeholder.com/300/9b2df1/ffffff?text=Pharmacy', 'countShops' => '8'],
        ['CategoryId' => '4', 'EnglishCategory' => 'Electronics', 'ArabCategory' => 'إلكترونيات', 'Photo' => 'https://via.placeholder.com/300/000000/ffffff?text=Electronics', 'countShops' => '22'],
        ['CategoryId' => '5', 'EnglishCategory' => 'Fashion', 'ArabCategory' => 'أزياء', 'Photo' => 'https://via.placeholder.com/300/f12d8a/ffffff?text=Fashion', 'countShops' => '45']
    ];
    $result = $mockCategories;
    $test = 4;
}

if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>