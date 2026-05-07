<?php
require "conn.php";

header('Content-Type: application/json');

$PostID = $_POST['PostID'] ?? ''; 
if (empty($PostID)) {
    echo json_encode(['success' => false, 'message' => 'No PostID provided']);
    exit;
}

$PostIDSafe = mysqli_real_escape_string($con, $PostID);

// Resolve the author: could be a standard user or a shop
$sql = "
    SELECT 
        c.CommentID, c.UserID, c.ShopID as PostOwnerID, c.CommentText, c.PostID, c.CreatedAtComments,
        COALESCE(u.name, s.ShopName, 'Unknown') as AuthorName,
        COALESCE(u.UserPhoto, s.ShopLogo) as AuthorPhoto
    FROM Comments c
    LEFT JOIN Users u ON c.UserID = u.UserID
    LEFT JOIN Shops s ON c.UserID = s.ShopID
    WHERE c.PostID = '$PostIDSafe'
    ORDER BY c.CommentID DESC
";

$res = mysqli_query($con, $sql);

$result = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Resolve the photo URL using the central logic in conn.php
        $row['UserPhoto'] = resolvePhotoUrl($row['AuthorPhoto'], $row['AuthorName']);
        
        $CreatedAtTrips = $row["CreatedAtComments"]; 
        $newDate = date('Y-m-d H:i:s', strtotime($CreatedAtTrips. ' + 1 hours'));
        $row["CreatedAtComments"] = $newDate;
        
        $CommentID = $row['CommentID'];
        $res2 = mysqli_query($con, "
            SELECT r.*, 
                   COALESCE(u.name, s.ShopName, 'Unknown') as AuthorName,
                   COALESCE(u.UserPhoto, s.ShopLogo) as AuthorPhoto
            FROM Replies r
            LEFT JOIN Users u ON r.UserID = u.UserID
            LEFT JOIN Shops s ON r.UserID = s.ShopID
            WHERE r.CommentID = '$CommentID'
            ORDER BY r.ReplyID ASC
        ");
        $replies = [];
        if ($res2) {
            while ($row2 = mysqli_fetch_assoc($res2)) {
                $row2['UserPhoto'] = resolvePhotoUrl($row2['AuthorPhoto'], $row2['AuthorName']);
                $CreatedAtTrips2 = $row2["CreatedAtReplies"]; 
                if ($CreatedAtTrips2) {
                    $row2["CreatedAtReplies"] = date('Y-m-d H:i:s', strtotime($CreatedAtTrips2. ' + 1 hours'));
                }
                $replies[] = $row2;
            }
        }
        $row['replies'] = $replies;
        $row['0'] = $replies;
        
        $result[] = $row;
    }
}

if (count($result) > 0) {
    echo json_encode([
        'status_code' => 200,
        'success'     => true,
        'data'        => $result,
        'message'     => 'successfully'
    ]);
} else {
    echo json_encode([
        'status_code' => 200,
        'success'     => false,
        'data'        => [],
        'message'     => 'No data'
    ]);
}
mysqli_close($con);
?>
