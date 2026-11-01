$ErrorActionPreference='Stop'
$repoT97='C:\xampp\htdocs\gestion-wings'
$copiaT97='C:\Users\cafre\Documents\Codex\T97-verificacion-20261101'
$evidenciaT97=Join-Path $repoT97 'docs\06-pruebas\PRU-04\evidencia-t97'
$env:DB_DATABASE='wings_testing_codex'
$env:APP_ENV='testing'
$intentoT97=1
while ($true) {
    $libreDesdeT97=$null
    Write-Output 'T97: esperando 120 segundos sin pruebas ajenas'
    while ($true) {
        $ajenasT97=@(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object CommandLine -match 'artisan test|phpunit')
        if ($ajenasT97.Count -gt 0) { $libreDesdeT97=$null }
        elseif ($null -eq $libreDesdeT97) { $libreDesdeT97=Get-Date }
        elseif (((Get-Date)-$libreDesdeT97).TotalSeconds -ge 120) { break }
        Start-Sleep -Seconds 1
    }
    $intentoT97++
    $salidaT97=Join-Path $evidenciaT97 ('suite-completa-intento-'+$intentoT97+'.txt')
    $erroresT97=Join-Path $evidenciaT97 ('suite-completa-intento-'+$intentoT97+'.stderr.txt')
    Write-Output ('T97: comienza intento '+$intentoT97+' sin otras pruebas')
    $procesoT97=Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList @('artisan','test') -WorkingDirectory $copiaT97 -WindowStyle Hidden -PassThru -RedirectStandardOutput $salidaT97 -RedirectStandardError $erroresT97
    $interrumpidaT97=$false
    while (!$procesoT97.HasExited) {
        $todosT97=@(Get-CimInstance Win32_Process -Filter "name='php.exe' or name='cmd.exe'")
        $propiosT97=@($procesoT97.Id) + @($todosT97 | Where-Object { $_.CommandLine -like "*$copiaT97*phpunit.xml*" } | Select-Object -ExpandProperty ProcessId)
        do {
            $antesT97=$propiosT97.Count
            foreach ($pT97 in $todosT97) { if ($pT97.ParentProcessId -in $propiosT97 -and $pT97.ProcessId -notin $propiosT97) { $propiosT97 += $pT97.ProcessId } }
        } while ($propiosT97.Count -gt $antesT97)
        $ajenasT97=@($todosT97 | Where-Object { $_.Name -eq 'php.exe' -and $_.ProcessId -notin $propiosT97 -and $_.CommandLine -match 'artisan test|phpunit' })
        if ($ajenasT97.Count -gt 0) {
            [pscustomobject]@{fecha=(Get-Date -Format o); intento=$intentoT97; inicio=$procesoT97.StartTime.ToString('o'); otras=@($ajenasT97 | Select-Object ProcessId,CommandLine); accion='Detenida solo la suite T97; no se atribuye resultado final'} | ConvertTo-Json -Depth 5 -Compress | Add-Content (Join-Path $evidenciaT97 'solapamientos.jsonl')
            foreach ($idT97 in ($propiosT97 | Sort-Object -Descending)) { Stop-Process -Id $idT97 -ErrorAction SilentlyContinue }
            $interrumpidaT97=$true
            Write-Output ('T97: intento '+$intentoT97+' detenido; aparecieron pruebas ajenas')
            break
        }
        Start-Sleep -Seconds 1
        $procesoT97.Refresh()
    }
    if ($interrumpidaT97) { continue }
    $procesoT97.WaitForExit()
    $codigoT97=$procesoT97.ExitCode
    Copy-Item -LiteralPath $salidaT97 -Destination (Join-Path $evidenciaT97 'suite-completa.txt')
    Copy-Item -LiteralPath $erroresT97 -Destination (Join-Path $evidenciaT97 'suite-completa.stderr.txt')
    $codigoT97 | Set-Content (Join-Path $evidenciaT97 'suite-completa.exit.txt')
    Write-Output ('T97: suite completa terminada, salida '+$codigoT97)
    Get-Content $salidaT97 -Tail 8
    exit $codigoT97
}