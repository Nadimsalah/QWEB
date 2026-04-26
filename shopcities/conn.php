<?php

$con=mysqli_connect("localhost","user_jibler","ba897ad272231711fdcd05a964d34cb2","Jibler");

  if (mysqli_connect_errno($con))
  {
   echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

//$ip_server = $_SERVER['SERVER_ADDR'];
  
// Printing the stored address
//echo "Server IP Address is: $ip_server";

  $DomainNamee ="https://jibler.app/jibler/UserDriverApi/";
  
?>
