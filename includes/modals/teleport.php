<?php
$teleportCities = [];
if (isset($con)) {
    $res = $con->query("SELECT DeliveryZoneID as CityID, CityName, Photo, CityLat, CityLongt FROM DeliveryZone ORDER BY CityName ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $teleportCities[] = $row;
        }
    }
}
// Helper for generating deterministic colors based on string
if (!function_exists('stringToColorCode')) {
    function stringToColorCode($str) {
        $hash = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $hash = ord($str[$i]) + (($hash << 5) - $hash);
        }
        $c = ($hash & 0x00FFFFFF);
        return str_pad(dechex($c), 6, '0', STR_PAD_LEFT);
    }
}
$domain = $DomainNamee ?? 'https://qoon.app/dash/';
?>
<style>
    #teleport-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.9); z-index: 99999; backdrop-filter: blur(15px);
        display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
        opacity: 0; pointer-events: none; transition: opacity 0.4s ease;
    }
    #teleport-modal-overlay.open {
        opacity: 1; pointer-events: auto;
    }
    #teleport-modal {
        width: 100%; max-width: 600px; height: 90vh;
        background: #000; border-radius: 30px 30px 0 0;
        display: flex; flex-direction: column; position: relative; overflow: hidden;
        transform: translateY(100%); transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
        border: 1px solid rgba(255,255,255,0.1);
    }
    #teleport-modal-overlay.open #teleport-modal {
        transform: translateY(0);
    }
    .teleport-close {
        position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.5); border: none; color: #fff;
        width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; z-index: 10; backdrop-filter: blur(5px);
    }
    #teleport-map-container {
        flex: 1; width: 100%; position: relative; background: #111;
    }
    #teleport-map { width: 100%; height: 100%; }
    
    .teleport-pin {
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -100%);
        z-index: 1000; pointer-events: none; font-size: 48px; color: #ff0080;
        filter: drop-shadow(0 10px 10px rgba(0,0,0,0.6));
        animation: floatPin 2s infinite ease-in-out;
    }
    @keyframes floatPin {
        0%, 100% { transform: translate(-50%, -100%); }
        50% { transform: translate(-50%, -110%); }
    }
    
    .teleport-bottom-sheet {
        background: #0a0a1a; padding: 20px 0; display: flex; flex-direction: column; gap: 15px;
        position: relative; z-index: 10; box-shadow: 0 -10px 30px rgba(0,0,0,0.5);
    }
    .teleport-search-wrapper { padding: 0 20px; position: relative; display: flex; gap: 10px; }
    #teleport-search-bar {
        flex: 1; padding: 15px 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05); color: #fff; font-size: 16px; outline: none;
    }
    .t-loc-btn {
        width: 52px; border-radius: 15px; border: none; background: rgba(44, 181, 232, 0.15); color: #2cb5e8; font-size: 20px; cursor: pointer; transition: background 0.3s; display: flex; justify-content: center; align-items: center;
    }
    .t-loc-btn:active { background: rgba(44, 181, 232, 0.3); }
    #teleport-results {
        position: absolute; top: 100%; left: 20px; right: 20px; background: #111;
        border: 1px solid rgba(255,255,255,0.2); border-radius: 15px; margin-top: 10px;
        max-height: 200px; overflow-y: auto; display: none; z-index: 99999;
        box-shadow: 0 15px 30px rgba(0,0,0,0.9);
    }
    .teleport-result-item { padding: 12px 20px; color: #fff; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .teleport-result-item:hover { background: rgba(255,255,255,0.1); }

    .teleport-cities-scroll {
        display: flex; gap: 15px; padding: 0 20px 10px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none;
    }
    .teleport-cities-scroll::-webkit-scrollbar { display: none; }
    .t-city-card {
        width: 120px; aspect-ratio: 9/12; border-radius: 16px; flex-shrink: 0; scroll-snap-align: start;
        position: relative; overflow: hidden; cursor: pointer; border: 1px solid rgba(255,255,255,0.1);
    }
    .t-city-img { width: 100%; height: 100%; object-fit: cover; }
    .t-city-overlay { position: absolute; bottom: 0; left: 0; width: 100%; height: 50%; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); display: flex; align-items: flex-end; padding: 10px; }
    .t-city-name { color: #fff; font-size: 14px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.8); }

    .teleport-confirm-btn {
        margin: 0 20px 10px; padding: 15px; background: #2cb5e8;
        color: #fff; border: none; border-radius: 15px; font-size: 18px; font-weight: 700; cursor: pointer;
        display: flex; justify-content: center; align-items: center; gap: 10px; transition: transform 0.2s;
    }
    .teleport-confirm-btn:active { transform: scale(0.95); }
</style>

<div id="teleport-modal-overlay">
    <div id="teleport-modal">
        <button class="teleport-close" id="teleport-close-btn"><i class="fa-solid fa-xmark"></i></button>
        
        <div id="teleport-map-container">
            <div id="teleport-map"></div>
            <div class="teleport-pin"><i class="fa-solid fa-location-dot"></i></div>
        </div>

        <div class="teleport-bottom-sheet">
            <div class="teleport-search-wrapper">
                <input type="text" id="teleport-search-bar" placeholder="Search globally..." autocomplete="off">
                <button class="t-loc-btn" onclick="geolocateOnMap()"><i class="fa-solid fa-location-crosshairs"></i></button>
                <div id="teleport-results"></div>
            </div>

            <?php if(!empty($teleportCities)): ?>
            <div class="teleport-cities-scroll">
                <?php foreach($teleportCities as $city): 
                    $cName = htmlspecialchars($city['CityName']);
                    $cLat = floatval($city['CityLat']);
                    $cLng = floatval($city['CityLongt']);
                    $cPhoto = trim($city['Photo'] ?? '');
                    
                    if (!empty($cPhoto) && $cPhoto !== '0' && $cPhoto !== 'NONE') {
                        $imgUrl = (strpos($cPhoto, 'http') === 0) ? $cPhoto : rtrim($domain, '/') . '/photo/' . ltrim($cPhoto, '/');
                    } else {
                        $bgColor = stringToColorCode($cName);
                        $imgUrl = "https://ui-avatars.com/api/?name=".urlencode($cName)."&background=".$bgColor."&color=fff&size=200&bold=true";
                    }
                ?>
                <div class="t-city-card" onclick="flyToCity(<?= $cLat ?>, <?= $cLng ?>)">
                    <img src="<?= $imgUrl ?>" class="t-city-img" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($cName) ?>&background=random&color=fff'">
                    <div class="t-city-overlay"><span class="t-city-name"><?= $cName ?></span></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button class="teleport-confirm-btn" onclick="confirmTeleport()">
                <i class="fa-solid fa-earth-americas"></i> Save Location
            </button>
        </div>
    </div>
</div>