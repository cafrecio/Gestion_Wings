$ErrorActionPreference='Stop'
$e='C:\xampp\htdocs\gestion-wings\docs\06-pruebas\PRU-04\evidencia-control-t102-codex'
$app='C:\xampp\htdocs\gestion-wings\storage\app\control-gw-261101\app'
$reserva='C:\xampp\htdocs\gestion-wings\docs\00-estado\RESERVA-SUITE-T100-T106-T102.md'
function PruebasAjenas {
 @(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object { $_.CommandLine -and $_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111' })
}
$libreDesde=$null
$esperaInicio=Get-Date
while ($true) {
 $p=@(PruebasAjenas)
 $row=@{fecha=(Get-Date).ToString('o');fase='espera';procesos=@($p | Select-Object ProcessId,CommandLine)}
 $row | ConvertTo-Json -Compress -Depth 5 | Add-Content -LiteralPath "$e\monitor-suite.jsonl" -Encoding utf8
 if ($p.Count -eq 0) {if ($null -eq $libreDesde) {$libreDesde=Get-Date};if (((Get-Date)-$libreDesde).TotalSeconds -ge 120) {break}} else {$libreDesde=$null}
 if (((Get-Date)-$esperaInicio).TotalMinutes -gt 45) {throw 'No hubo ventana exclusiva de dos minutos'}
 Start-Sleep -Seconds 5
}
$s=[IO.File]::ReadAllText($reserva).Replace('Estado: SOLICITADA; aún no comenzó la suite completa.','Estado: EN USO; suite completa iniciada tras dos minutos libres.');[IO.File]::WriteAllText($reserva,$s)
$env:DB_DATABASE='wings_testing_codex'
$inicio=Get-Date
$proc=Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList @('artisan','test','--no-ansi') -WorkingDirectory $app -WindowStyle Hidden -RedirectStandardOutput "$e\suite-completa.txt" -RedirectStandardError "$e\suite-completa.stderr.txt" -PassThru
$own=@([int]$proc.Id)
$solapamientos=@()
while (!$proc.HasExited) {
 $php=@(Get-CimInstance Win32_Process -Filter "name='php.exe'")
 foreach($q in $php){if($q.ParentProcessId -in $own -and $q.ProcessId -notin $own){$own+= [int]$q.ProcessId}}
 $other=@($php | Where-Object { $_.ProcessId -notin $own -and $_.CommandLine -and $_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111' })
 if($other.Count){$solapamientos+=@($other | Select-Object ProcessId,CommandLine)}
 @{fecha=(Get-Date).ToString('o');fase='suite';propios=$own;ajenos=@($other | Select-Object ProcessId,CommandLine)} | ConvertTo-Json -Compress -Depth 5 | Add-Content -LiteralPath "$e\monitor-suite.jsonl" -Encoding utf8
 Start-Sleep -Seconds 5
 $proc.Refresh()
}
$proc.WaitForExit()
@{inicio=$inicio.ToString('o');fin=(Get-Date).ToString('o');codigo=$proc.ExitCode;procesos_propios=$own;solapamientos=$solapamientos;base='wings_testing_codex'} | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath "$e\resultado-suite.json" -Encoding utf8
$s=[IO.File]::ReadAllText($reserva).Replace('Estado: EN USO; suite completa iniciada tras dos minutos libres.','Estado: LIBERADA; suite final terminada.');[IO.File]::WriteAllText($reserva,$s)
Write-Output "Completa finalizada: salida $($proc.ExitCode); solapamientos $($solapamientos.Count)"
exit $proc.ExitCode
