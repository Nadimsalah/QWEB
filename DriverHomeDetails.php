<?php
require "conn.php";


$DriverID = $_POST["DriverID"];



$order_delivered = 0;
$daily_profit    = 0;
$total_profit    = 0;


$todayDate = date("Y-m-d");

$a = 'How are you?';
$search = $todayDate;

$MustPaid = 0;
$MoneyStopNumber = 0;
$subscription = 0;


    $res = mysqli_query($con,"SELECT * FROM `MoneyStop`");
	while($row = mysqli_fetch_assoc($res)){
	    
	    $MoneyStopNumber = $row["MoneyStopNumber"];
		$subscription = $row["subscription"];
		
	}

	$res = mysqli_query($con,"SELECT * FROM Orders WHERE DelvryId='$DriverID'");
	while($row = mysqli_fetch_assoc($res)){
		
	$OrderID = 	$row["OrderID"];
	$UserFees  = 0;
						$res22 = mysqli_query($con,"SELECT UserFees FROM UserTransaction WHERE OrderID = $OrderID");
                        
                        
                                        while($row22 = mysqli_fetch_assoc($res22)){
                        
                                       
										$UserFees = $row22["UserFees"];
                                                               
                                        }
		
		
		
		$OrderState      = $row["OrderState"];
		$CreatedAtOrders = $row["CreatedAtOrders"];
		$OrderPrice    = $row["OrderPrice"];
		$PaidForDriver = $row["PaidForDriver"];
		if($UserFees!='-'){
		$OrderPriceFromShop = $row["OrderPriceFromShop"] + $UserFees;
		$OrderPriceFromShop = (string)$OrderPriceFromShop;
		}else{
			$OrderPriceFromShop = $row["OrderPriceFromShop"];
		}
		$Method 			= $row["Method"];
		$ShopID			= $row["ShopID"];
		$DriverOmola			= $row["DriverOmola"];
		$DriverOmolaPaid			= $row["DriverOmolaPaid"];
		$IsPrepared = $row["IsPrepared"];
		
		
	$res2 = mysqli_query($con,"SELECT Type FROM Shops WHERE ShopID='$ShopID'");
	while($row22 = mysqli_fetch_assoc($res2)){
		$Type = $row22["Type"];
	}


		if($OrderState=='Rated'||$OrderState=='Done'){
		    
		    $order_delivered++;
		    
		    $a = $CreatedAtOrders;
		    
		    $total_profit = $total_profit + $OrderPrice;
		    
		    if(preg_match("/{$search}/i", $a)){
		        
		        $daily_profit = $daily_profit + $OrderPrice;
		        
		    }
		    
		    if($PaidForDriver == 'NotPaid'){

				if($Method=="CASH"){
					$MustPaid = $MustPaid + $OrderPriceFromShop;
		        }else{
			//		if($Type=="Our"){
			//			$MustPaid = $MustPaid - $OrderPrice;
			//		}else{
			//			$MustPaid = $MustPaid - $OrderPriceFromShop - $OrderPrice;
			//		}
				}
		    }else{
				if($Method!="CASH"){
		
				//	$MustPaid = $MustPaid - $OrderPriceFromShop - $OrderPrice;
				}
				
			}
		}
		
		if($DriverOmolaPaid=="NO"){
			
			$MustPaid = $MustPaid + $DriverOmola;
		}
		
		if($OrderState=='Cancelled'){



			if($PaidForDriver == 'NotPaid'){



				if($IsPrepared=="YES"){



					if($Method=="CASH"){

							$MustPaid = $MustPaid + $OrderPriceFromShop;			

						
					}
				}
			
			}
		}
		
		

	}
	
	
		$res = mysqli_query($con,"SELECT count(*) FROM SubscriptionDriver WHERE DriverID='$DriverID' AND Paid = 'NO'");
	while($row = mysqli_fetch_assoc($res)){
		
		$SubscriptionNotPaidtCount = $row["count(*)"];
		
	}

	if($SubscriptionNotPaidtCount>1){
		
		$MustPaid = $MustPaid + (($SubscriptionNotPaidtCount-1)*$subscription);
	}

    $accountStat = "Worked";
    if($MoneyStopNumber < $MustPaid ){
        
		
		
		
        $accountStat = "Stoped";
        
		
		
		
    }


//if($MustPaid<0){
	
//	$MustPaid = 0;
//}

  $array = [
            "daily_profit" => $daily_profit,
            "total_profit"   => $total_profit,
			"order_delivered"   => $order_delivered,
			"subscription"   => (int)$subscription,
			"MustPaid"  =>  (int)$MustPaid,
			"AccoundStat" => $accountStat,
            ];
	
				$message ="Done";
			$success = true;
			$status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$array,"message"=>$message));
   
   
   
die;
mysqli_close($con);
?>