<?php
// Simulate the POST request
$_POST = [
    'UserID' => 'test_uid',
    'UserLat' => '33.5731',
    'UserLongt' => '-7.5898',
    'DestnationLat' => '33.5731',
    'DestnationLongt' => '-7.5898',
    'DestinationName' => 'Store',
    'DestnationPhoto' => '',
    'UserAddress' => 'Address',
    'DestnationAddress' => 'Store Address',
    'OrderPriceFromShop' => '100',
    'OrderDetails' => '100 MAD',
    'Method' => 'CASH',
    'OrderType' => 'Fast',
    'FoodIDs' => '1*1**#',
    'UserName' => 'QOON User',
    'UserPhone' => '00000000',
    'UserEmail' => 'user@qoon.app',
    'CarTypeID' => '1',
    'WeightsId' => '1',
    'UserCitiesID' => '1',
    'DestnationCitiesID' => '1',
    'UserCountryId' => '1',
    'DestnationCountryId' => '1',
    'OrderDelvTime' => '30',
    'ShopID' => '0',
    'RealType' => 'QOON'
];

require "conn.php";
// Let's copy the exact SQL insert from AddOrder.php to see the error

$desg = rand(1000, 9999);
$PDFPAth = "dummy.pdf";
$OrderPriceForOur = "0";

$sql="INSERT INTO Orders (FourDigit,UserID,DestinationName,DestnationAddress,DestnationLat,DestnationLongt,DestnationPhoto,
   OrderDetails,OrderState,UserLat,UserLongt,OrderDelvTime,ShopID,OrderPriceForOur,OrderPriceFromShop,UserReview,OrderType,ShowOrder,Method,ReadyTime,MaxDeliveryPrice,FatoraDetails,Comment,UserName,UserPhone,UserEmail,UserAddress,CarTypeID,WeightsId,UserCitiesID,DestnationCitiesID,UserCountryId,DestnationCountryId,CompanyID,RealType,PDF)
   VALUES ('$desg','test_uid','Store','Store Address','33.5731','-7.5898','',
   '100 MAD','waiting','33.5731','-7.5898','30','0','0','100','-','Fast','YES','CASH','1','100000','','-','QOON User','00000000','user@qoon.app','Address','1','1','1','1','1','1','0','QOON','dummy.pdf');";

if(mysqli_query($con,$sql)) {
    echo "Success! Order ID: " . mysqli_insert_id($con);
} else {
    echo "Error: " . mysqli_error($con);
}
?>
