<?php

// رابط الـ API
$url = "https://zeewana.com/QoonDriverApis/getNearOrders.php";

// البيانات التي سيتم إرسالها
$postData = [
    'DriverLat' => $_POST['DriverLat'] ?? '',
    'DriverLongt' => $_POST['DriverLongt'] ?? ''
];

// تهيئة جلسة cURL
$ch = curl_init($url);

// إعدادات cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // تجاهل التحقق من SSL (فقط أثناء التطوير)
curl_setopt($ch, CURLOPT_POST, true); // تحديد أن الطلب من نوع POST
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); // تمرير البيانات

// تنفيذ الطلب
$response = curl_exec($ch);

// التحقق من وجود أخطاء
if (curl_errno($ch)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        "status_code" => 500,
        "success" => false,
        "message" => "cURL Error: " . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

// إغلاق الجلسة
curl_close($ch);

// تحديد نوع المخرجات JSON
header('Content-Type: application/json');

// طباعة الاستجابة كما هي
echo $response;

?>
