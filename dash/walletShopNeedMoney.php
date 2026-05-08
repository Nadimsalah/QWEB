<?php
require "conn.php";
mysqli_set_charset($con, "utf8mb4");

// Financial Portfolio Aggregations
$resShopBal = mysqli_query($con, "SELECT SUM(Balance) as val FROM Shops");
$shopBalTotal = mysqli_fetch_assoc($resShopBal)['val'] ?? 0;

// Pagination and Search
$page = isset($_GET["Page"]) ? (int)$_GET["Page"] : 0;
if($page < 0) $page = 0;
$limit = 12;
$offset = $page * $limit;

$view = $_GET['view'] ?? 'owed';
$searchName = isset($_GET["name"]) ? mysqli_real_escape_string($con, $_GET["name"]) : '';

if ($view === 'requests') {
    $where = "1=1";
    if($searchName != '') {
        $where .= " AND (Shops.ShopName LIKE '%$searchName%' OR Shops.ShopID = '$searchName')";
    }
    $resItems = mysqli_query($con, "
        SELECT RequestPay.*, Shops.ShopName, Shops.ShopLogo, Shops.BankName, Shops.BankNum, Shops.Balance as CurrentBalance 
        FROM RequestPay 
        JOIN Shops ON RequestPay.ShopID = Shops.ShopID 
        WHERE $where 
        ORDER BY CreatedAtRequestPay DESC 
        LIMIT $limit OFFSET $offset
    ");
} else {
    $where = "Balance > 0";
    if($searchName != '') {
        $where .= " AND (ShopName LIKE '%$searchName%' OR ShopID = '$searchName')";
    }
    $resItems = mysqli_query($con, "
        SELECT ShopID, ShopName, ShopLogo, Balance, BankName, BankNum 
        FROM Shops 
        WHERE $where 
        ORDER BY ShopID DESC 
        LIMIT $limit OFFSET $offset
    ");
}

$itemsList = [];
if($resItems) {
    while($row = mysqli_fetch_assoc($resItems)){
        $itemsList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Owed Balances | QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Excel Parsing Engine preserved from legacy system -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="js/jquery-3.2.1.min.js"></script>

    <style>
        :root {
            --bg-app: #F5F6FA; --bg-white: #FFFFFF;
            --text-dark: #2A3042; --text-gray: #A6A9B6;
            --accent-purple: #623CEA; --accent-purple-light: #F0EDFD;
            --accent-green: #10B981; --accent-blue: #007AFF; --accent-orange: #F59E0B; --accent-red: #EF4444;
            --border-color: #F0F2F6;
            --shadow-card: 0 8px 30px rgba(0, 0, 0, 0.03);
            --shadow-float: 0 12px 35px rgba(0, 0, 0, 0.05);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-app); display: flex; height: 100vh; overflow: hidden; }
        .app-envelope { width: 100%; height: 100%; display: flex; overflow: hidden; }

        .sidebar { width: 260px; background: var(--bg-white); display: flex; flex-direction: column; padding: 40px 0; border-right: 1px solid var(--border-color); flex-shrink: 0; }
        .logo-box { display: flex; align-items: center; padding: 0 30px; gap: 12px; margin-bottom: 50px; text-decoration: none; }
        .logo-box img { max-height: 50px; width: auto; object-fit: contain; }
        .nav-list { display: flex; flex-direction: column; gap: 5px; padding: 0 20px; flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 16px; padding: 14px 20px; border-radius: 12px; color: var(--text-gray); text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s ease; }
        .nav-item i { font-size: 18px; width: 20px; text-align: center; }
        .nav-item.active { background: var(--accent-purple-light); color: var(--accent-purple); position: relative; }
        .nav-item.active::before { content: ''; position: absolute; left: -20px; top: 50%; transform: translateY(-50%); height: 60%; width: 4px; background: var(--accent-purple); border-radius: 0 4px 4px 0; }

        .main-panel { flex: 1; padding: 35px 40px; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; }

        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; background: var(--bg-white); padding: 15px 25px; border-radius: 16px; box-shadow: var(--shadow-card); flex-shrink:0;}
        .breadcrumb { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 700; color: var(--text-dark); }
        .breadcrumb a { color: var(--text-gray); text-decoration: none; transition: 0.2s; }
        .breadcrumb a:hover { color: var(--accent-purple); }

        .kpi-master { display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, var(--accent-purple), #4A2BBF); border-radius: 20px; padding: 30px 40px; color: #FFF; margin-bottom: 30px; box-shadow: var(--shadow-float); flex-shrink:0; }
        .kpi-master h4 { font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-bottom: 5px; }
        .kpi-master h1 { font-size: 42px; font-weight: 800; display: flex; align-items: baseline; gap: 10px; }
        .kpi-master h1 span { font-size: 20px; font-weight: 700; opacity: 0.7; }

        .tools-bar { display: flex; gap: 15px; margin-bottom: 30px; flex-shrink:0; align-items: center; justify-content: space-between; }
        .tools-actions { display: flex; gap: 15px; }
        .btn-tool { padding: 14px 22px; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; display: flex; align-items: center; gap: 10px; transition: 0.2s; color: #FFF; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-tool.export { background: var(--accent-green); }
        .btn-tool.export:hover { background: #0EA5E9; transform: translateY(-2px); }
        .btn-tool.import { background: var(--accent-purple); }
        .btn-tool.import:hover { background: #4A2BBF; transform: translateY(-2px); }

        .table-container { background: var(--bg-white); border-radius: 20px; padding: 30px; box-shadow: var(--shadow-card); overflow: hidden; margin-bottom: 20px; flex-shrink:0; }
        .table-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { background: var(--bg-app); border-radius: 12px; padding: 10px 18px; display: flex; align-items: center; gap: 10px; width: 300px; }
        .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; }
        th { font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; padding: 15px; border-bottom: 2px solid var(--border-color); text-align: left; }
        td { font-size: 14px; font-weight: 600; color: var(--text-dark); padding: 18px 15px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        
        .part-node { display: flex; align-items: center; gap: 12px; text-decoration:none; color:var(--text-dark); transition:0.2s;}
        .part-node:hover { color:var(--accent-purple); }
        .part-node img { width: 35px; height: 35px; border-radius: 8px; object-fit: cover; }
        .rev-amt { font-size: 16px; font-weight: 800; color: var(--accent-green); }
        .bank-tag { display: inline-block; background: var(--bg-app); padding: 5px 10px; border-radius: 6px; font-size: 12px; color: var(--text-gray); }
        
        .pagination { display: flex; align-items: center; justify-content: space-between; margin-top: 30px; font-size: 13px; font-weight: 600; color: var(--text-gray); }
        .page-ctrls { display: flex; gap: 8px; }
        .page-btn { padding: 8px 16px; border-radius: 10px; background: var(--bg-app); color: var(--text-dark); text-decoration: none; transition: 0.2s; font-weight: 700; border: 1px solid var(--border-color); }
        .page-btn:hover { background: var(--accent-purple); color: #FFF; border-color: var(--accent-purple); }
        .page-btn.disabled { opacity: 0.5; pointer-events: none; }

        /* Status Badges for Requests */
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pill.started { background: #E0E7FF; color: #4338CA; }
        .status-pill.pending { background: #FEF3C7; color: #92400E; }
        .status-pill.done { background: #D1FAE5; color: #065F46; }
        .status-pill.rejected { background: #FEE2E2; color: #991B1B; }
        
        .view-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .v-tab { padding: 10px 20px; border-radius: 12px; font-size: 14px; font-weight: 700; text-decoration: none; color: var(--text-gray); background: var(--bg-white); border: 1px solid var(--border-color); transition: 0.2s; }
        .btn-action { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; color: #FFF; }
        .btn-action.done { background: var(--accent-green); }
        .btn-action.reject { background: var(--accent-red); margin-left: 5px; }
        .btn-action:hover { opacity: 0.8; transform: translateY(-1px); }
    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <header class="header">
                <div class="breadcrumb">
                    <a href="wallet.php"><i class="fas fa-wallet"></i> Financial Overview</a>
                    <span>/</span>
                    <span style="color: var(--accent-purple);">Shop Owed Balances</span>
                </div>
            </header>

            <div class="kpi-master">
                <div>
                    <h4><?= $view === 'requests' ? 'Total Money Requested' : 'Total Active Shop Debts' ?></h4>
                    <h1><?= number_format($shopBalTotal, 2) ?> <span>MAD</span></h1>
                </div>
                <div>
                    <i class="fas <?= $view === 'requests' ? 'fa-hand-holding-usd' : 'fa-store' ?> fa-3x" style="opacity:0.2;"></i>
                </div>
            </div>

            <div class="view-tabs">
                <a href="?view=owed" class="v-tab <?= $view === 'owed' ? 'active' : '' ?>"><i class="fas fa-coins"></i> Owed Balances</a>
                <a href="?view=requests" class="v-tab <?= $view === 'requests' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> Withdrawal Requests</a>
            </div>

            <div class="tools-bar">
                <div style="font-size: 14px; font-weight: 700; color: var(--text-dark);">
                    <i class="fas fa-file-excel" style="color:var(--accent-green);"></i> Payroll Export Operations
                </div>
                <div class="tools-actions">
                    <button class="btn-tool export" onclick="downloadExcel()">
                        <i class="fas fa-download"></i> Export Payout Matrix
                    </button>
                    <button class="btn-tool import" onclick="triggerFileUpload()">
                        <i class="fas fa-upload"></i> Process Payments Sheet
                    </button>
                    <input type="file" id="uploadFile" accept=".xlsx, .xls" style="display: none;" />
                </div>
            </div>

            <div class="table-container">
                <div class="table-head">
                    <h2 style="font-size:18px; font-weight:800;"><i class="fas <?= $view === 'requests' ? 'fa-list-ul' : 'fa-file-invoice-dollar' ?>" style="color:var(--accent-purple);"></i> <?= $view === 'requests' ? 'Withdrawal Queue' : 'Shop Payroll Log' ?></h2>
                    <form class="search-box" method="GET">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                        <i class="fas fa-search" style="color:var(--text-gray);"></i>
                        <input type="text" name="name" placeholder="Search..." value="<?= htmlspecialchars($searchName) ?>">
                    </form>
                </div>
                <!-- ID REQUIRED FOR EXCEL JS -->
                <table id="table-1">
                    <thead>
                        <tr>
                            <th>Participant Name</th>
                            <th>Shop ID</th>
                            <th><?= $view === 'requests' ? 'Requested Amount' : 'Available Balance' ?></th>
                            <?php if ($view === 'requests'): ?>
                            <th>Status</th>
                            <?php endif; ?>
                            <th>Routing Bank</th>
                            <th>Account Number</th>
                            <?php if ($view === 'requests'): ?>
                            <th>Request Date</th>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($itemsList) === 0): ?>
                        <tr><td colspan="<?= $view === 'requests' ? 8 : 5 ?>" style="text-align:center; padding:30px; color:var(--text-gray);">No data found for this context.</td></tr>
                        <?php endif; ?>
                        
                        <?php foreach($itemsList as $s): ?>
                        <tr>
                            <td>
                                <a href="shop-profile.php?id=<?= $s['ShopID'] ?>" class="part-node">
                                    <img src="<?= htmlspecialchars($s['ShopLogo']) ?>" onerror="this.src='images/placeholder.png'">
                                    <span><?= htmlspecialchars($s['ShopName']) ?></span>
                                </a>
                            </td>
                            <td><span style="font-weight: 800; color: var(--accent-blue); background: rgba(0, 122, 255, 0.1); padding: 5px 12px; border-radius: 8px; font-size: 12px;">#<?= $s['ShopID'] ?></span></td>
                            <td>
                                <span class="rev-amt" style="<?= ($view === 'requests' || $s['Balance'] > 0) ? 'color:var(--accent-orange);' : 'color:var(--text-gray);opacity:0.6;' ?>">
                                    <?= number_format($view === 'requests' ? (float)$s['Money'] : $s['Balance'], 2) ?> MAD
                                </span>
                            </td>
                            <?php if ($view === 'requests'): 
                                $stat = strtolower($s['RequestPayStatues']);
                                $pillClass = $stat;
                                if ($stat === 'started') $pillClass = 'started';
                                if ($stat === 'pending') $pillClass = 'pending';
                                if ($stat === 'done') $pillClass = 'done';
                                if ($stat === 'rejected') $pillClass = 'rejected';
                            ?>
                            <td><span class="status-pill <?= $pillClass ?>"><?= htmlspecialchars($s['RequestPayStatues']) ?></span></td>
                            <?php endif; ?>
                            <td><span class="bank-tag"><i class="fas fa-university"></i> <?= htmlspecialchars($s['BankName'] ?: 'N/A') ?></span></td>
                            <td><span style="font-family:monospace; font-size:14px; color:var(--text-dark); font-weight:700;"><?= htmlspecialchars($s['BankNum'] ?: 'N/A') ?></span></td>
                            <?php if ($view === 'requests'): ?>
                            <td style="font-size:12px; color:var(--text-gray);"><?= date('M j, Y', strtotime($s['CreatedAtRequestPay'])) ?></td>
                            <td>
                                <?php if ($stat !== 'done' && $stat !== 'rejected'): ?>
                                <button class="btn-action done" onclick="updateReqStatus(<?= $s['RequestPayID'] ?>, 'Done', <?= $s['ShopID'] ?>, <?= $s['Money'] ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-action reject" onclick="updateReqStatus(<?= $s['RequestPayID'] ?>, 'Rejected', <?= $s['ShopID'] ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php else: ?>
                                <span style="font-size:10px; color:var(--text-gray);">Processed</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <span>Listing platform debts owed to Stores</span>
                    <div class="page-ctrls">
                        <a href="?view=<?= urlencode($view) ?>&name=<?= urlencode($searchName) ?>&Page=<?= max(0, $page - 1) ?>" class="page-btn <?= $page <= 0 ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i> Prev</a>
                        <a href="?view=<?= urlencode($view) ?>&name=<?= urlencode($searchName) ?>&Page=<?= $page + 1 ?>" class="page-btn <?= count($itemsList) < $limit ? 'disabled' : '' ?>">Next <i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- EXTREME LEGACY PRESERVATION: Excel Parsing Engine Scripts -->
    <script>
        function downloadExcel() {
            const table = document.getElementById("table-1");
            const rows = Array.from(table.rows);
            const data = rows.map(row => Array.from(row.cells).map(cell => cell.textContent.trim()));

            data[0].push("PAID"); // Inject Header
            for (let i = 1; i < data.length; i++) {
                data[i].push("NO");
            }

            const worksheet = XLSX.utils.aoa_to_sheet(data);
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

            const today = new Date();
            const fileName = `shop-${today.getDate()}-${today.getMonth() + 1}-${today.getFullYear()}.xlsx`;
            XLSX.writeFile(workbook, fileName);
        }
    
        function triggerFileUpload() {
            const fileInput = document.getElementById('uploadFile');
            fileInput.click();
            fileInput.onchange = function () {
                readExcel(fileInput.files[0]);
            };
        }

        function readExcel(file) {
            if (!file) { alert('No file selected!'); return; }
            const reader = new FileReader();

            reader.onload = function(event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const sheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[sheetName];
                const rows = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
				
				for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    
                    const name = row[0] || ''; 
                    const id = row[1] ? row[1].replace('#', '').trim() : ''; // Fix ID if # is prepended
                    const balance = row[2] ? row[2].replace('MAD', '').trim() : ''; 
                    const bankName = row[3] || ''; 
                    const accountNumber = row[4] || ''; 
					const Pay = row[5] || ''; 
                    
					if(Pay === "YES"){
						var formData = new FormData();
						formData.append("Balance", balance);
						formData.append("ShopID", id);
						$.ajax({
							url: "UpdateShopBalncesApi.php",
							type: "POST",
							data: formData,
							processData: false,
							contentType: false,
							cache: false,
							success: function(dataResult){}
						});
					}
                }
				setTimeout(() => location.reload(), 1500); // Reloads the page after operations
            };
            reader.readAsArrayBuffer(file);
        }

        function updateReqStatus(id, stat, shopId, money = 0) {
            if(!confirm("Are you sure you want to mark this request as " + stat + "?")) return;
            
            $.ajax({
                url: "UpdateRequestPayStatus.php",
                type: "POST",
                data: {
                    RequestPayID: id,
                    Status: stat,
                    ShopID: shopId,
                    Money: money
                },
                success: function(resp) {
                    location.reload();
                },
                error: function() {
                    alert("Failed to update status.");
                }
            });
        }
    </script>
</body>
</html>