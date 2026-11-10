param([int]$UserId = 5, [string]$UserName = 'admin', [string[]]$Uris)
$php = 'C:\wamp64\bin\php\php8.0.30\php.exe'
foreach ($item in $Uris) {
    $line = (& $php "$PSScriptRoot\render_page.php" $UserId $UserName $item 2>&1) -join ' '
    if ($line.Length -gt 600) { $line = $line.Substring(0, 600) }
    $line
}
