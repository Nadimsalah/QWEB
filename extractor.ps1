$shop = Get-Content 'shop.php' -Raw
$startCss = $shop.IndexOf('/* --- PRODUCT MODAL --- */')
$endCss = $shop.IndexOf('</style>', $startCss)
$cssBlock = $shop.Substring($startCss, $endCss - $startCss)

$startHtml = $shop.IndexOf('<!-- PRODUCT MODAL HTML & LOGIC -->')
$endHtml = $shop.IndexOf('</body>', $startHtml)
$htmlJsBlock = $shop.Substring($startHtml, $endHtml - $startHtml)

$output = '<style>' + [Environment]::NewLine + $cssBlock + [Environment]::NewLine + '</style>' + [Environment]::NewLine + $htmlJsBlock
Set-Content 'extracted_modal.txt' -Value $output
