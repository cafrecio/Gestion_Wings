$ErrorActionPreference='Stop'
$e=Join-Path $PSScriptRoot ''
$app=Join-Path (Get-Location) 'storage/app/control-gw-261101/app'
$env:DB_DATABASE='wings_testing_codex'
$prev=@(Get-CimInstance Win32_Process | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan test|phpunit' })
if($prev.Count){$prev | ConvertTo-Json -Depth 4 | Set-Content (Join-Path $e 'bloqueo-previo.json');throw 'Otra suite en curso, no se inicio la completa'}
$started=Get-Date
$p=Start-Process -FilePath 'C:\xampp\php\php.exe' -ArgumentList @('artisan','test','--no-ansi') -WorkingDirectory $app -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $e 'suite-completa.txt') -RedirectStandardError (Join-Path $e 'suite-completa.stderr.txt')
$handle=$p.Handle
$foreignIds=[System.Collections.Generic.HashSet[int]]::new()
@{inicio=$started.ToString('o');pid=$p.Id;base=$env:DB_DATABASE;app=$app} | ConvertTo-Json | Set-Content (Join-Path $e 'suite-inicio.json')
while(-not $p.HasExited){
 $all=@(Get-CimInstance Win32_Process);$ids=[System.Collections.Generic.HashSet[int]]::new();[void]$ids.Add($p.Id)
 do{$added=$false;foreach($node in $all){if($ids.Contains([int]$node.ParentProcessId) -and -not $ids.Contains([int]$node.ProcessId)){[void]$ids.Add([int]$node.ProcessId);$added=$true}}}while($added)
 $other=@($all | Where-Object { $_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan test|phpunit' -and -not $ids.Contains([int]$_.ProcessId) } | Select-Object ProcessId,ParentProcessId,CommandLine)
 foreach($node in $other){[void]$foreignIds.Add([int]$node.ProcessId)}
 $php=@($all | Where-Object { $_.Name -eq 'php.exe' -and -not $ids.Contains([int]$_.ProcessId) } | Select-Object ProcessId,ParentProcessId,CommandLine)
 @{hora=(Get-Date).ToString('o');propios=@($ids);otras_suites=$other;otros_php=$php} | ConvertTo-Json -Depth 5 -Compress | Add-Content (Join-Path $e 'monitor-completa.jsonl')
 Start-Sleep -Seconds 5;$p.Refresh()
}
$p.WaitForExit()
@{inicio=$started.ToString('o');fin=(Get-Date).ToString('o');segundos=[Math]::Round(((Get-Date)-$started).TotalSeconds,2);codigo=$p.ExitCode;otras_suites=@($foreignIds);base=$env:DB_DATABASE} | ConvertTo-Json -Depth 5 | Set-Content (Join-Path $e 'suite-resultado.json')
Get-Content (Join-Path $e 'suite-resultado.json')
Get-Content (Join-Path $e 'suite-completa.txt') -Tail 12
