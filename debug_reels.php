<?php
require_once 'conn.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Reel Data Debug</title>
<style>
body { font-family: monospace; background: #111; color: #eee; padding: 20px; }
table { border-collapse: collapse; width: 100%; font-size: 12px; }
th { background: #2cb5e8; color: #000; padding: 8px; text-align: left; }
td { padding: 6px 8px; border-bottom: 1px solid #333; word-break: break-all; max-width: 300px; }
tr:hover td { background: #1a1a1a; }
.tag-video { background: #16a34a; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
.tag-image { background: #2563eb; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
h2 { color: #2cb5e8; margin: 24px 0 8px; }
</style>
</head>
<body>

<h2>📹 Posts (Video)</h2>
<table>
<tr><th>PostID</th><th>Video (raw)</th><th>BunnyS</th><th>BunnyV</th><th>Resolved URL</th></tr>
<?php
$DomainNamee = 'https://qoon.app/dash/';
$res = $con ? $con->query("SELECT PostID, Video, BunnyS, BunnyV FROM Posts WHERE PostStatus='ACTIVE' AND Video != '' AND Video != '0' ORDER BY PostID DESC LIMIT 20") : null;
if ($res) while ($r = $res->fetch_assoc()):
    $raw = trim($r['Video'] ?? '');
    $resolved = (strpos($raw,'http') !== false) ? $raw : $DomainNamee . 'photo/' . $raw;
?>
<tr>
    <td><?= $r['PostID'] ?></td>
    <td><?= htmlspecialchars($raw) ?></td>
    <td><?= htmlspecialchars($r['BunnyS'] ?? '') ?></td>
    <td><?= htmlspecialchars($r['BunnyV'] ?? '') ?></td>
    <td><a href="<?= htmlspecialchars($resolved) ?>" target="_blank" style="color:#2cb5e8"><?= htmlspecialchars($resolved) ?></a></td>
</tr>
<?php endwhile; ?>
</table>

<h2>📸 ShopStory</h2>
<table>
<tr><th>StotyID</th><th>StoryPhoto (raw)</th><th>BunnyS</th><th>StotyType</th><th>Resolved URL</th></tr>
<?php
$res2 = $con ? $con->query("SELECT StotyID, StoryPhoto, BunnyS, StotyType FROM ShopStory WHERE StoryStatus='ACTIVE' ORDER BY StotyID DESC LIMIT 20") : null;
if ($res2) while ($r = $res2->fetch_assoc()):
    $raw = trim($r['StoryPhoto'] ?? '');
    $resolved = (strpos($raw,'http') !== false) ? $raw : $DomainNamee . 'photo/' . $raw;
    $type = strtoupper($r['StotyType'] ?? '');
?>
<tr>
    <td><?= $r['StotyID'] ?></td>
    <td><?= htmlspecialchars($raw) ?></td>
    <td><?= htmlspecialchars($r['BunnyS'] ?? '') ?></td>
    <td><span class="<?= $type === 'VIDEO' ? 'tag-video' : 'tag-image' ?>"><?= $type ?></span></td>
    <td><a href="<?= htmlspecialchars($resolved) ?>" target="_blank" style="color:#2cb5e8"><?= htmlspecialchars($resolved) ?></a></td>
</tr>
<?php endwhile; ?>
</table>

<?php if (isset($con) && $con) mysqli_close($con); ?>
</body></html>
