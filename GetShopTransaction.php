<?php

require "conn.php";
$test=0;

$ShopID = $_POST["ShopID"];
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

$FirstDayInYear = date('Y-01-01', strtotime('first day of this year', time()));




if($Type==""){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND CreatedAtShopLastTransaction LIKE '%$todayDate%' order by ShopTransactionID desc";
	
	
}else if($Type=="Today"){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND CreatedAtShopLastTransaction LIKE '%$todayDate%' order by ShopTransactionID desc";;
	
}else if($Type=="Yesterday"){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND CreatedAtShopLastTransaction LIKE '%$YesterDate%' order by ShopTransactionID desc";;
		
}else if($Type=="Week"){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND (CreatedAtShopLastTransaction  >= '$FirstDayInWeak') order by ShopTransactionID desc";;

}else if($Type=="Month"){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND (CreatedAtShopLastTransaction  >= '$FirstDayInMonth') order by ShopTransactionID desc";;

}else if($Type=="Year"){
	
	$Query = "SELECT * FROM ShopLastTransaction WHERE ShopID ='$ShopID' AND (CreatedAtShopLastTransaction  >= '$FirstDayInYear') order by ShopTransactionID desc";;

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
	
	
	$SevenDayDate = date('Y-m-d',strtotime("-7 days"));
	
	
	$CanGet = "0";
	$Hold   = "0";

		$res = mysqli_query($con,"SELECT sum(OrderPriceFromShop) FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done') AND ShopRecive = 'NO' AND CreatedAtOrders < '$SevenDayDate'");
	while($row = mysqli_fetch_assoc($res)){
		
		$CanGet = $row["sum(OrderPriceFromShop)"];
		
	}
	
		$res = mysqli_query($con,"SELECT sum(OrderPriceFromShop) FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done') AND ShopRecive = 'NO' AND CreatedAtOrders > '$SevenDayDate'");
	while($row = mysqli_fetch_assoc($res)){
		
		$Hold = $row["sum(OrderPriceFromShop)"];
		
	}

	
	$res = mysqli_query($con,"SELECT * FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done')");
	while($row = mysqli_fetch_assoc($res)){
		
		$OrderState      = $row["OrderState"];
		$CreatedAtOrders = $row["CreatedAtOrders"];
		$OrderPrice    = $row["OrderPrice"];
		$PaidForDriver = $row["PaidForDriver"];
		$OrderPriceFromShop = $row["OrderPriceFromShop"];
		
		 $a = $CreatedAtOrders;
		
		if(preg_match("/{$search}/i", $a)){
		        
		        $daily_profit = $daily_profit + $OrderPriceFromShop;
		        
		}
		
		if(preg_match("/{$search2}/i", $a)){
		        
		        $yesterday_profit = $yesterday_profit + $OrderPriceFromShop;
		        
		}
		
		if($PaidForDriver == 'NotPaid'){

		        $MustPaid = $MustPaid + $OrderPriceFromShop;
		        
		    }
		
		
	}
	
	
	$res = mysqli_query($con,"SELECT sum(OrderPriceFromShop) FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInWeak' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Week_profit = $row["sum(OrderPriceFromShop)"];
	}
	
	$res = mysqli_query($con,"SELECT sum(OrderPriceFromShop) FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInMonth' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Month_profit = $row["sum(OrderPriceFromShop)"];
		
	}
	
	$res = mysqli_query($con,"SELECT sum(OrderPriceFromShop) FROM Orders WHERE ShopID='$ShopID' AND (OrderState='Rated' OR OrderState='Done') AND (CreatedAtOrders >= '$FirstDayInYear' AND  CreatedAtOrders <= '$todayDate')");
	while($row = mysqli_fetch_assoc($res)){
		
		$Year_profit = $row["sum(OrderPriceFromShop)"];
	}



		$res = mysqli_query($con,"SELECT Balance FROM Shops WHERE ShopID='$ShopID'");
	while($row = mysqli_fetch_assoc($res)){
		
		$Balance = $row["Balance"];
		
	}



if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
			if($Hold==""){
			
			$Hold = "0";
		}
		
		if($Balance==""){
			
			$Balance = "0";
		}
		if($CanGet==""){
			
			$CanGet = "0";
		}
		if($Week_profit==""){
			
			$Week_profit = "0";
		}
		
		if($Month_profit==""){
			
			$Month_profit = "0";
		}
		
		if($Year_profit==""){
			
			$Year_profit = "0";
		}
	
	
	 	$res2 = mysqli_query($con,"SELECT * FROM OrdersJiblerpercentage");
									while($row22 = mysqli_fetch_assoc($res2)){
										
										$percent = $row22["percent"];
										
									}
	
	$ww = $CanGet;
	
	$CanGet = $CanGet + $Balance;
	
	
	$Hold = $ww - ($ww*$percent/100);
	
	$Hold = $ww;
	

echo json_encode(array('status_code' => $status_code,'success' => $success ,"jibler_hold" => (string)$Hold,"totalBalance" => (string)$Balance,"TopBalance"=>(string)$CanGet,"Today"=>(string)$daily_profit,"Yesterday"=>(string)$yesterday_profit,"Week"=>(string)$Week_profit,"Month"=>(string)$Month_profit,"Year"=>(string)$Year_profit,"data"=>$result,"message"=>$message));
}
else{
	$message ="No data";
    $success = true;
    $status_code = 200;
		$result = []; 
		
		if($Hold==""){
			
			$Hold = "0";
		}
		
		if($Balance==""){
			
			$Balance = "0";
		}
		if($CanGet==""){
			
			$CanGet = "0";
		}
		if($Week_profit==""){
			
			$Week_profit = "0";
		}
		
		if($Month_profit==""){
			
			$Month_profit = "0";
		}
		
		if($Year_profit==""){
			
			$Year_profit = "0";
		}
		
		
	//	$CanGet = $CanGet + $Balance;
		
			$ww = $CanGet;
	
	$CanGet = $CanGet + $Balance;
	
	
	$Hold = $ww - ($ww*$percent/100);
		

echo json_encode(array('status_code' => $status_code,'success' => $success ,"jibler_hold" => (string)$Hold,"totalBalance" => (string)$Balance,"TopBalance"=>(string)$CanGet,"Today"=>(string)$daily_profit,"Yesterday"=>(string)$yesterday_profit,"Week"=>(string)$Week_profit,"Month"=>(string)$Month_profit,"Year"=>(string)$Year_profit,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>