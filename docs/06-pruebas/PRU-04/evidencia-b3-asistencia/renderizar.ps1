$taskFolder = $PSScriptRoot
$taskRepo = (Resolve-Path (Join-Path $taskFolder '../../../..')).Path
$taskChrome = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
foreach ($taskName in @('lista', 'motivos', 'guardada', 'inicio', 'login')) {
    $taskWidths = if ($taskName -eq 'login') { @(375) } else { @(1366, 375) }
    foreach ($taskWidth in $taskWidths) {
        $taskHeight = if ($taskName -eq 'login') { 880 } elseif ($taskWidth -eq 1366 -and $taskName -eq 'inicio') { 2600 } elseif ($taskWidth -eq 1366 -and $taskName -eq 'guardada') { 2200 } elseif ($taskWidth -eq 1366) { 1800 } elseif ($taskName -eq 'inicio') { 2580 } else { 2080 }
        $taskWindowWidth = if ($taskWidth -eq 375) { 600 } else { 1366 }
        $taskSource = if ($taskWidth -eq 375) { "marco-$taskName-375.html" } else { "$taskName-real.html" }
        $taskUrl = 'file:///' + (Join-Path $taskFolder $taskSource).Replace('\', '/')
        $taskProfile = Join-Path $taskRepo "storage/app/b3-chrome-$taskName-$taskWidth"
        $taskOutput = Join-Path $taskFolder "$taskName-final-$taskWidth.png"
        $taskArgs = @('--headless=new', '--disable-gpu', '--allow-file-access-from-files', '--no-first-run', '--no-default-browser-check', '--force-device-scale-factor=1', '--hide-scrollbars', '--virtual-time-budget=4000', "--window-size=$taskWindowWidth,$taskHeight", "--user-data-dir=$taskProfile", "--screenshot=$taskOutput", $taskUrl)
        $taskProcess = Start-Process -FilePath $taskChrome -ArgumentList $taskArgs -WindowStyle Hidden -Wait -PassThru -RedirectStandardError (Join-Path $taskFolder "$taskName-$taskWidth-chrome.txt")
        if ($taskProcess.ExitCode -ne 0 -or !(Test-Path -LiteralPath $taskOutput)) { throw "Falló la captura $taskName/$taskWidth" }
        Write-Output "$taskName/${taskWidth}: PNG creado"
    }
}
