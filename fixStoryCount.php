<?php
require "conn.php";


$res = mysqli_query($con,"SELECT count(*),ShopID  FROM ShopStory where StoryStatus = 'ACTIVE' group by ShopID");

		$result = array();

			while($row = mysqli_fetch_assoc($res)){


		$ShopID = $row["ShopID"];
		$count = $row["count(*)"];

   $sql="UPDATE Shops SET StoryCount='$count' WHERE ShopID='$ShopID'";
   if(mysqli_query($con,$sql))
   {
	   	
	
	} 
	
			}
	
	
die;
mysqli_close($con);
?>