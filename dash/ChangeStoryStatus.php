<?php
require "conn.php";
$PostId = (int)$_GET["PostId"];
$StoryStatus = $_GET["StoryStatus"];
$ShopID = (int)$_GET["ShopID"]; 

$sql = "UPDATE ShopStory SET StoryStatus = '$StoryStatus' WHERE StotyID=$PostId";

if(mysqli_query($con,$sql)){
    // Recalculate Active Stories for this Shop based on the database
    $res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM ShopStory WHERE ShopID='$ShopID' AND StoryStatus = 'ACTIVE'");
    $row = mysqli_fetch_assoc($res);
    $activeCount = (int)$row["cnt"];
    
    $hasStory = ($activeCount > 0) ? "YES" : "No";
    $sql2 = "UPDATE Shops SET HasStory='$hasStory', StoryCount=$activeCount WHERE ShopID=$ShopID";
    mysqli_query($con, $sql2);

    if($StoryStatus == "ACTIVE"){
        $resPush = mysqli_query($con,"SELECT ShopFirebaseToken,LANG FROM Shops WHERE ShopID='$ShopID'");
        while($rowP = mysqli_fetch_assoc($resPush)){
            $ShopFirebaseToken = $rowP["ShopFirebaseToken"];
            $ShopLang = $rowP["LANG"];
            if(strtolower($ShopLang)=="ar"){
                $ShopTitle = "تم الموافقة على المحتوى للنشر ✅";
                $ShopBody  = "تمت الموافقة على محتواك للنشر من قبل فريق جيبلر";
            }else if(strtolower($ShopLang)=="en"){
                $ShopTitle = "Content Approved for Publication ✅";
                $ShopBody  = "Your content has been approved for publication by Jibler Team.";
            }else{
                $ShopTitle = "Contenu approuvé pour la publication ✅";
                $ShopBody  = "Votre contenu a été approuvé pour la publication par l'équipe Jibler.";
            }
            if(function_exists("ResturantNotification")) {
                ResturantNotification($ShopFirebaseToken,$ShopTitle,$ShopBody);
            }
        }
    }
    echo " Done ";
}
?>
