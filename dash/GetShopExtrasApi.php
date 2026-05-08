<?php
/**
 * GetShopExtrasApi.php
 * Returns all Extra Modifier groups + values for a given shop's products.
 *
 * GET  ?ShopID=123          → All extras for every product of that shop
 * GET  ?ProductID=456       → Extras for one specific product
 *
 * Response format:
 * {
 *   "success": true,
 *   "extras": {
 *     "789": [                       // keyed by ProductID
 *       {
 *         "id": 12,
 *         "name": "Colors",
 *         "multy": "YES",
 *         "items": [
 *           {
 *             "id": 45,
 *             "name": "Red",         // display name (without hex)
 *             "color": "#EF4444",    // null if not a color option
 *             "price": 0.00
 *           }
 *         ]
 *       }
 *     ]
 *   }
 * }
 */

require "conn.php";
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$shopID    = isset($_GET['ShopID'])    ? (int)$_GET['ShopID']    : 0;
$productID = isset($_GET['ProductID']) ? (int)$_GET['ProductID'] : 0;

if (!$shopID && !$productID) {
    echo json_encode(['success' => false, 'error' => 'ShopID or ProductID required']);
    exit;
}

// Build WHERE clause
if ($productID) {
    $where = "ec.ProductID = '$productID'";
} else {
    // Get all products for this shop then fetch extras
    $where = "ec.ShopId = '$shopID'";
}

// Fetch all extra groups + their items in one join
$sql = "
    SELECT
        ec.ExtraCategoryID,
        ec.ProductID,
        ec.ExtraCategoryName,
        ec.Multy,
        ei.ExtraInSideCategotyID,
        ei.Name   AS ItemName,
        ei.Price  AS ItemPrice
    FROM ExtraCategory ec
    LEFT JOIN ExtraInSideCategoty ei ON ei.ExtraCategoryID = ec.ExtraCategoryID
    WHERE $where
    ORDER BY ec.ExtraCategoryID ASC, ei.ExtraInSideCategotyID ASC
";

$res = mysqli_query($con, $sql);
if (!$res) {
    echo json_encode(['success' => false, 'error' => mysqli_error($con)]);
    exit;
}

$extras = [];

while ($row = mysqli_fetch_assoc($res)) {
    $pID  = (string)$row['ProductID'];
    $gID  = (int)$row['ExtraCategoryID'];

    if (!isset($extras[$pID])) {
        $extras[$pID] = [];
    }

    // Init group if not seen yet
    $grpIdx = null;
    foreach ($extras[$pID] as $i => $g) {
        if ($g['id'] === $gID) { $grpIdx = $i; break; }
    }
    if ($grpIdx === null) {
        $extras[$pID][] = [
            'id'    => $gID,
            'name'  => $row['ExtraCategoryName'],
            'multy' => $row['Multy'],
            'items' => []
        ];
        $grpIdx = count($extras[$pID]) - 1;
    }

    // Add item if it exists (LEFT JOIN may produce NULL item)
    if ($row['ExtraInSideCategotyID'] !== null) {
        $rawName = (string)$row['ItemName'];
        $price   = (float)$row['ItemPrice'];

        // Parse "ColorName|#hexcode" format stored by the seller dashboard
        $parts     = explode('|', $rawName, 2);
        $dispName  = $parts[0];
        $colorHex  = isset($parts[1]) && preg_match('/^#[0-9A-Fa-f]{6}$/', $parts[1])
                     ? $parts[1] : null;

        $extras[$pID][$grpIdx]['items'][] = [
            'id'    => (int)$row['ExtraInSideCategotyID'],
            'name'  => $dispName,
            'color' => $colorHex,
            'price' => $price
        ];
    }
}

echo json_encode([
    'success' => true,
    'extras'  => $extras
], JSON_UNESCAPED_UNICODE);
