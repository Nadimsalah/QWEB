<?php

require "conn.php";
$test=0;

$DriverID = $_POST["DriverID"];
$Type     = $_POST["Type"];


	$todayDate = date("Y-m-d");
	$search = $todayDate;
	
	$YesterDate = date('Y-m-d',strtotime("-1 days"));
	
	$search2 = $YesterDate;
	
	$Week_profit  = "0";
	$Month_profit = "0";
	$Year_profit  = "0";
	
	$daily_profit  = "0";
	$yesterday_profit  = "0";
	
	$MustPaid = "0";


$FirstDayInWeak = date('Y-m-d', strtotime('this week Monday', time()));

$FirstDayInMonth = date('Y-m-d', strtotime('first day of this month', time()));

$FirstDayInYear = date('Y-m-d', strtotime('first day of this year', time()));

if($Type==""){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND CreatedAtDriverTransactions LIKE '%$todayDate%' order by DriverTransactionsID desc";
	
	
}else if($Type=="Today"){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND CreatedAtDriverTransactions LIKE '%$todayDate%' order by DriverTransactionsID desc";;
	
}else if($Type=="Yesterday"){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND CreatedAtDriverTransactions LIKE '%$YesterDate%' order by DriverTransactionsID desc";;
		
}else if($Type=="Week"){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND (CreatedAtDriverTransactions  >= '$FirstDayInWeak') order by DriverTransactionsID desc";;

}else if($Type=="Month"){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND (CreatedAtDriverTransactions  >= '$FirstDayInMonth') order by DriverTransactionsID desc";;

}else if($Type=="Year"){
	
	$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' AND (CreatedAtDriverTransactions  >= '$FirstDayInYear') order by DriverTransactionsID desc";;

}

//$Query = "SELECT * FROM DriverTransactions WHERE DriverID ='$DriverID' order by DriverTransactionsID desc";

$res = mysqli_query($con,$Query);

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));


$todayDate = date("Y-m-d");
	$search = $todayDate;
	
	$YesterDate = date('Y-m-d',strtotime("-1 days"));
	
	$search2 = $YesterDate;
	

	$WALLET = 0;

	
	$res = mysqli_query($con,"SELECT * FROM Orders WHERE DelvryId='$DriverID' AND (OrderState='Rated' OR OrderState='Done')");
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
		$Method = $row["Method"];
		$ShopID			= $row["ShopID"];
		$DriverOmola			= $row["DriverOmola"];
		$DriverOmolaPaid			= $row["DriverOmolaPaid"];
		
		
	$res2 = mysqli_query($con,"SELECT Type FROM Shops WHERE ShopID='$ShopID'");
	while($row22 = mysqli_fetch_assoc($res2)){
		$Type = $row22["Type"];
	}
		
		 $a = $CreatedAtOrders;
		
		if(preg_match("/{$search}/i", $a)){
		        
		        $daily_profit = $daily_profit + $OrderPrice;
		        
		}
		
		if(preg_match("/{$search2}/i", $a)){
		        
		        $yesterday_profit = $yesterday_profit + $OrderPrice;
		        
		}
		
			if($PaidForDriver == 'NotPaid'){

		       // $MustPaid = $MustPaid + $OrderPriceFromShop;
				if($Method=="CASH"){
					$MustPaid = $MustPaid + $OrderPriceFromShop;
		        }else{
					if($Type=="Our"){
					//	$MustPaid = $MustPaid - $OrderPrice;
						$WALLET = $WALLET + $OrderPrice;
						
					}else{
					//	$MustPaid = $MustPaid - $OrderPriceFromShop - $OrderPrice;
						$WALLET = $WALLET + $OrderPriceFromShop + $OrderPrice;
					}	
				}
		        
		    }else{
				if($Method!="CASH"){
		
				//	$MustPaid = $MustPaid - $OrderPriceFromShop - $OrderPrice;
				$OrderPrice = $OrderPrice + $OrderPriceFromShop + $OrderPrice;
				}
				
			}
		
		
		if($DriverOmolaPaid=="NO"){
			
			$MustPaid = $MustPaid + $DriverOmola;
		}
		
	}
	
	
	$res = mysqli_query($con,"SELECT * FROM Orders WHERE DelvryId='$DriverID' AND OrderState='Cancelled'");
	while($row = mysqli_fetch_assoc($res)){
		
		$OrderState      = $row["OrderState"];
		$CreatedAtOrders = $row["CreatedAtOrders"];
		$OrderPrice    = $row["OrderPrice"];
		$PaidForDriver = $row["PaidForDriver"];
		$OrderPriceFromShop = $row["OrderPriceFromShop"];
		$Method 			= $row["Method"];
		$ShopID			= $row["ShopID"];
		$DriverOmola			= $row["DriverOmola"];
		$DriverOmolaPaid			= $row["DriverOmolaPaid"];
		$IsPrepared = $row["IsPrepared"];
		
		
		
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
	
	$subscription = 0;
	
	    $res = mysqli_query($con,"SELECT * FROM `MoneyStop`");
	while($row = mysqli_fetch_assoc($res)){
	    
		$subscription = $row["subscription"];
	}
	
	
	$res = mysqli_query($con,"SELECT count(*) FROM SubscriptionDriver WHERE DriverID='$DriverID' AND Paid = 'NO'");
	while($row = mysqli_fetch_assoc($res)){
		
		$SubscriptionNotPaidtCount = $row["count(*)"];
		
	}

	if($SubscriptionNotPaidtCount>1){
		
		$MustPaid = $MustPaid + (($SubscriptionNotPaidtCount-1)*$subscription);
	}
	
	
	$res = mysqli_query($con,"SELECT sum(OrderPrice) FROM Orders WHERE DelvryId='$DriverID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInWeak' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Week_profit = $row["sum(OrderPrice)"];
	}
	
	$res = mysqli_query($con,"SELECT sum(OrderPrice) FROM Orders WHERE DelvryId='$DriverID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInMonth' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Month_profit = $row["sum(OrderPrice)"];
		
	}
	
	$res = mysqli_query($con,"SELECT sum(OrderPrice) FROM Orders WHERE DelvryId='$DriverID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInYear' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Year_profit = $row["sum(OrderPrice)"];
	}
	
	
	$res = mysqli_query($con,"SELECT sum(OrderPrice) FROM Orders WHERE DelvryId='$DriverID' AND (OrderState='Rated' OR OrderState='Done') AND Method = 'WALLET'");
	while($row = mysqli_fetch_assoc($res)){
		
	//	$WALLET = $row["sum(OrderPrice)"];
	}



if($MustPaid<0){
	
	$MustPaid = 0;
}

if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
	
	
	
	
	
	

echo json_encode(array('status_code' => $status_code,'success' => $success ,"JiblerBalance" => (string)$WALLET ,"TopBalance"=>(string)$MustPaid,"Today"=>(string)$daily_profit,"Yesterday"=>(string)$yesterday_profit,"Week"=>(string)$Week_profit,"Month"=>(string)$Month_profit,"Year"=>(string)$Year_profit,"data"=>$result,"message"=>$message));
}
else{
	$message ="No data";
    $success = true;
    $status_code = 200;
		$result = []; 

echo json_encode(array('status_code' => $status_code,'success' => $success ,"JiblerBalance" => (string)$WALLET,"TopBalance"=>(string)$MustPaid,"Today"=>(string)$daily_profit,"Yesterday"=>(string)$yesterday_profit,"Week"=>(string)$Week_profit,"Month"=>(string)$Month_profit,"Year"=>(string)$Year_profit,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>