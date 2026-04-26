<?php
$_POST['cart'] = '[{"id":"1","name":"Test","qty":1,"price":40}]';
$_POST['shopId'] = 1;
$_POST['shopName'] = 'Test';
$_REQUEST['platformFee'] = 3.50;
$_POST['total'] = 43.50;
require 'delivery_offers.php';
?>