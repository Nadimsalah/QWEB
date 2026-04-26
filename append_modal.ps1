$modal = Get-Content 'extracted_modal.txt' -Raw
$index = Get-Content 'index.php' -Raw
$newIndex = $index.Replace('</body>', "$modal
</body>")
[IO.File]::WriteAllText('index.php', $newIndex)
