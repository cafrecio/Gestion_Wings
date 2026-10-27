$taskFolder = $PSScriptRoot
$taskRepo = (Resolve-Path (Join-Path $taskFolder '../../../..')).Path
foreach ($taskShot in @(@('lista',1366,1366,1600), @('lista',375,600,1880), @('login',375,600,880))) {
    $taskName, $taskWidth, $taskWindow, $taskHeight = $taskShot
    $taskSource = if($taskWidth -eq 375) { "marco-$taskName-375.html" } else { "$taskName-real.html" }
    $taskUrl = 'file:///' + (Join-Path $taskFolder $taskSource).Replace('\','/')
    $taskOutput = Join-Path $taskFolder "$taskName-$taskWidth.png"
    $taskProfile = Join-Path $taskRepo "storage/app/b3-devolucion-chrome-$taskName-$taskWidth"
    $taskArgs = @('--headless=new','--disable-gpu','--allow-file-access-from-files','--no-first-run','--no-default-browser-check','--force-device-scale-factor=1','--hide-scrollbars','--virtual-time-budget=4000',"--window-size=$taskWindow,$taskHeight","--user-data-dir=$taskProfile","--screenshot=$taskOutput",$taskUrl)
    $taskProcess = Start-Process -FilePath 'C:\Program Files\Google\Chrome\Application\chrome.exe' -ArgumentList $taskArgs -WindowStyle Hidden -Wait -PassThru -RedirectStandardError (Join-Path $taskFolder "$taskName-$taskWidth-chrome.txt")
    if($taskProcess.ExitCode -ne 0 -or !(Test-Path -LiteralPath $taskOutput)) { throw "Captura fallida $taskName/$taskWidth" }
    Write-Output "$taskName/$taskWidth"
}
