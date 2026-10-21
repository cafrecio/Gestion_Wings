param([int]$Launcher,[string]$Destino)
$ErrorActionPreference='Stop'
$observaciones=0;$ajenos=@()
while(Get-Process -Id $Launcher -ErrorAction SilentlyContinue){
    $todos=@(Get-CimInstance Win32_Process);$mapa=@{};foreach($p in $todos){$mapa[[int]$p.ProcessId]=$p}
    foreach($p in ($todos|Where-Object {$_.Name -eq 'php.exe' -and $_.CommandLine -match 'artisan test|phpunit'})){
        $ancestro=[int]$p.ProcessId;$propio=$false;$saltos=0
        while($mapa.ContainsKey($ancestro) -and $saltos -lt 20){if($ancestro -eq $Launcher){$propio=$true;break};$ancestro=[int]$mapa[$ancestro].ParentProcessId;$saltos++}
        if(!$propio){$ajenos+=@{fecha=(Get-Date).ToString('o');pid=$p.ProcessId;padre=$p.ParentProcessId}}
    }
    $observaciones++
    @{intervalo_segundos=5;observaciones=$observaciones;launcher=$Launcher;procesos_ajenos=$ajenos;terminado=$false}|ConvertTo-Json -Depth 4|Set-Content -LiteralPath $Destino -Encoding utf8
    Start-Sleep -Seconds 5
}
@{intervalo_segundos=5;observaciones=$observaciones;launcher=$Launcher;procesos_ajenos=$ajenos;terminado=$true}|ConvertTo-Json -Depth 4|Set-Content -LiteralPath $Destino -Encoding utf8
