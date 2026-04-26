<?php
header('Content-Type: application/json; charset=utf-8');

// اظهار الاخطاء وقت التطوير (اختياري)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// خلي mysqli ترمي استثناءات بدل الـ warnings
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require "conn.php";
    $test = 0;

    $CategoryID = $_POST["CategoryID"];
    $UserLat    = $_POST["UserLat"];
    $UserLongt  = $_POST["UserLongt"];
    $UserID     = $_POST["UserID"];
    $SearchWord = $_POST["SearchWord"];
    $BoostPage  = 0;
    $ShowAdds   = false;

    // الاستعلام الأساسي
    
    if($SearchWord==""){
        $res = mysqli_query($con, "
            SELECT *, (6372.797 * acos(
                cos(radians($UserLat)) * cos(radians(ShopLat)) *
                cos(radians(ShopLongt) - radians($UserLongt)) +
                sin(radians($UserLat)) * sin(radians(ShopLat))
            )) AS distance 
            FROM Shops 
            WHERE CategoryID ='$CategoryID' 
            AND HasStory='YES' 
            AND Shops.Status = 'ACTIVE' 
            HAVING distance <= 100000 
            ORDER BY priority DESC , distance ASC
        ");
    }else{
       $res = mysqli_query($con, "
            SELECT *, (6372.797 * acos(
                cos(radians($UserLat)) * cos(radians(ShopLat)) *
                cos(radians(ShopLongt) - radians($UserLongt)) +
                sin(radians($UserLat)) * sin(radians(ShopLat))
            )) AS distance 
            FROM Shops 
            WHERE CategoryID ='$CategoryID' 
            AND HasStory='YES' AND Shops.ShopName LIKE '%$SearchWord%'
            AND Shops.Status = 'ACTIVE' 
            HAVING distance <= 100000 
            ORDER BY priority DESC , distance ASC
        "); 
        
    }

    $result = [];
    $i = 0;

    while ($row = mysqli_fetch_assoc($res)) {

        $row["TypeSlider"] = "Slider";

        // كل 3 متاجر حط إعلان
        if (($i + 1) % 3 == 0) {
            $ShowAdds = true;

            $res2 = mysqli_query($con, "
                SELECT *, (6372.797 * acos(
                    cos(radians($UserLat)) * cos(radians(BoostLat)) *
                    cos(radians(BoostLongt) - radians($UserLongt)) +
                    sin(radians($UserLat)) * sin(radians(BoostLat))
                )) AS distance 
                FROM BoostsByShop 
                JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID 
                WHERE BoostTypeID ='3' AND BoostStatus = 'Active' 
                HAVING distance <= 125 
                UNION 
                SELECT *, (6372.797 * acos(
                    cos(radians($UserLat)) * cos(radians(BoostLat)) *
                    cos(radians(BoostLongt) - radians($UserLongt)) +
                    sin(radians($UserLat)) * sin(radians(BoostLat))
                )) AS distance 
                FROM BoostsByShop 
                JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID 
                WHERE BoostTypeID ='3' 
                AND BoostStatus = 'Active' 
                AND BoostCity='MOROCCO' 
                HAVING distance <= 22125 
                ORDER BY distance ASC 
                LIMIT $BoostPage, 1
            ");

            while ($row2 = mysqli_fetch_assoc($res2)) {

                $ShopID = $row2["ShopID"];
                $BoostsByShopID = $row2["BoostsByShopID"];

                $row2["HasStory"]   = "YES";
                $row2["StoryCount"] = "1";
                $row2["TypeSlider"] = "Boost";

                if ($row2["BoostAction"] == "PRODUCT") {
                    $row2["ProductID"] = $row2["BoostLinkOrProductID"];
                } elseif ($row2["BoostAction"] == "LINK") {
                    $row2["OpenType"] = "LINK";
                }

                if ($row2["BoostAction"] == "LINK") {
                    $row2["OpenType"] = "LINK";
                    $row2["OpenNow"] = $row2["BoostLinkOrProductID"];
                    $row2["ProductID"] = "0";
                } elseif ($row2["BoostAction"] == "STORE") {
                    $row2["OpenType"] = "SHOP";
                    $row2["ShopID"] = $row2["BoostLinkOrProductID"];
                    $row2["ProductID"] = "0";
                } else {
                    $row2["OpenType"] = "PRODUCT";
                    $row2["OpenNow"] = $row2["BoostLinkOrProductID"];
                    $row2["ProductID"] = $row2["BoostLinkOrProductID"];
                }

                $result[] = $row2;
                $i++;

                break;
            }

            $BoostPage++;
        }

        // القصص الخاصة بكل متجر
        $result[] = $row;
        $ShopID = $row["ShopID"];
        $res2 = mysqli_query($con, "SELECT * FROM ShopStory WHERE ShopID='$ShopID'");
        $result2 = [];

        while ($rowStory = mysqli_fetch_assoc($res2)) {
            if ($rowStory["ProductId"] == "0") {
                $rowStory["ProductId"] = "";
            }

            $foodid = $rowStory["ProductId"];
            $result233 = [];

            if ($foodid != "") {
                $res233 = mysqli_query($con, "SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE FoodID= $foodid");
                while ($row233 = mysqli_fetch_assoc($res233)) {
                    // ExtraCategory
                    $result2334 = [];
                    $res2334 = mysqli_query($con, "SELECT * FROM ExtraCategory WHERE ProductID='$foodid'");
                    while ($row2334 = mysqli_fetch_assoc($res2334)) {
                        $ExtraCategoryID = $row2334["ExtraCategoryID"];

                        $result355 = [];
                        $res355 = mysqli_query($con, "SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
                        while ($row355 = mysqli_fetch_assoc($res355)) {
                            $result355[] = $row355;
                        }
                        $row2334["Extras"] = $result355;
                        $result2334[] = $row2334;
                    }
                    $row233["0"] = $result2334;
                    $result233[] = $row233;
                }
                $rowStory["0"] = $result233[0] ?? null;
            }

            $result2[] = $rowStory;
        }

        $row["0"] = $result2;
        $result[$i] = $row;
        $i++;
        $test = 4;
    }

    if ($test == 4) {
        $message = "sucssesfully";
        $success = true;
        $status_code = 200;
    } else {
        $message = "No data";
        $success = false;
        $status_code = 200;
        $result = [];
    }

    echo json_encode([
        'status_code' => $status_code,
        'success' => $success,
        'data' => $result,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status_code' => 500,
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($con) && $con) {
        mysqli_close($con);
    }
}
?>