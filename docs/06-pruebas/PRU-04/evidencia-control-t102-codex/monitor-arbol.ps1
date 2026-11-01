$ErrorActionPreference='Stop'
$e='C:\xampp\htdocs\gestion-wings\docs\06-pruebas\PRU-04\evidencia-control-t102-codex'
$raiz=28568
while(Get-Process -Id $raiz -ErrorAction SilentlyContinue){
 $ps=@(Get-CimInstance Win32_Process)
 $propios=@($raiz)
 do{$n=$propios.Count;foreach($q in $ps){if($q.ParentProcessId -in $propios -and $q.ProcessId -notin $propios){$propios+=[int]$q.ProcessId}}}while($propios.Count -ne $n)
 $php=@($ps | Where-Object {$_.Name -eq 'php.exe' -and $_.CommandLine -notmatch 'artisan serve| -S 127\.0\.0\.1:8111'})
 $ajenos=@($php | Where-Object {$_.ProcessId -notin $propios})
 @{fecha=(Get-Date).ToString('o');raiz=$raiz;arbol=@($ps | Where-Object {$_.ProcessId -in $propios} | Select-Object ProcessId,ParentProcessId,Name,CommandLine);ajenos=@($ajenos | Select-Object ProcessId,ParentProcessId,CommandLine)} | ConvertTo-Json -Compress -Depth 6 | Add-Content -LiteralPath "$e\monitor-arbol.jsonl" -Encoding utf8
 Start-Sleep -Seconds 5
}
