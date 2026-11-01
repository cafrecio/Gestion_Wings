$ErrorActionPreference='Stop'
$e='C:\xampp\htdocs\gestion-wings\docs\06-pruebas\PRU-04\evidencia-control-t102-codex'
$app='C:\xampp\htdocs\gestion-wings\storage\app\control-gw-261101\app'
if (!(Test-Path -LiteralPath "$e\resultado-suite.json")) {throw 'La completa sigue activa'}
$s=[IO.File]::ReadAllText("$e\suite-completa.txt")
$clases=@([regex]::Matches($s,'(?m)^\s*FAIL\s+Tests\\Feature\\([A-Za-z0-9_]+)') | ForEach-Object {$_.Groups[1].Value} | Select-Object -Unique)
$resultados=@()
$env:DB_DATABASE='wings_testing_codex'
foreach($nombre in $clases){
 $previas=@(Get-CimInstance Win32_Process -Filter "name='php.exe'" | Where-Object {$_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111'})
 if($previas.Count){throw 'Hay una prueba PHP ajena antes del siguiente archivo'}
 $p=Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList @('artisan','test',"tests/Feature/$nombre.php",'--no-ansi') -WorkingDirectory $app -WindowStyle Hidden -RedirectStandardOutput "$e\aislada-$nombre.txt" -RedirectStandardError "$e\aislada-$nombre.stderr.txt" -PassThru
 $raiz=[int]$p.Id;$solapamientos=@()
 while(!$p.HasExited){
  $ps=@(Get-CimInstance Win32_Process);$propios=@($raiz)
  do{$n=$propios.Count;foreach($q in $ps){if($q.ParentProcessId -in $propios -and $q.ProcessId -notin $propios){$propios+=[int]$q.ProcessId}}}while($propios.Count -ne $n)
  $otros=@($ps | Where-Object {$_.Name -eq 'php.exe' -and $_.ProcessId -notin $propios -and $_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111'})
  $solapamientos+=@($otros | Select-Object ProcessId,CommandLine)
  @{fecha=(Get-Date).ToString('o');clase=$nombre;raiz=$raiz;arbol=@($ps | Where-Object {$_.ProcessId -in $propios} | Select-Object ProcessId,ParentProcessId,Name,CommandLine);ajenos=@($otros | Select-Object ProcessId,ParentProcessId,CommandLine)} | ConvertTo-Json -Compress -Depth 6 | Add-Content -LiteralPath "$e\monitor-aisladas.jsonl" -Encoding utf8
  Start-Sleep -Seconds 5;$p.Refresh()
 }
 $p.WaitForExit();$resultados+=@{clase=$nombre;codigo=$p.ExitCode;solapamientos=$solapamientos}
 $resultados | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath "$e\resultado-aisladas.json" -Encoding utf8
 Write-Output "$nombre salida $($p.ExitCode)"
}
