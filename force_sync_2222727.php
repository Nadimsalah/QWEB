<?php
$oId = '2222727';
$fbData = ['current_status' => 'CANCELLED', 'updated_at' => time()];
$chFb = curl_init();
curl_setopt($chFb, CURLOPT_URL, "https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/$oId.json");
curl_setopt($chFb, CURLOPT_POST, true);
curl_setopt($chFb, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chFb, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($chFb, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($chFb, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($chFb, CURLOPT_POSTFIELDS, json_encode($fbData));
curl_exec($chFb);
curl_close($chFb);

echo "Firebase updated for $oId";
?>
