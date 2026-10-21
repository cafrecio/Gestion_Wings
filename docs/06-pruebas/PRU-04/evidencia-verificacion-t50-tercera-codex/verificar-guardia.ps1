$ErrorActionPreference = 'Stop'
$repoT50 = (Resolve-Path (Join-Path $PSScriptRoot '../../../..')).Path
if (Test-Path (Join-Path $repoT50 'bootstrap/cache/config.php')) {
    throw 'No reproducir con configuracion cacheada: las variables de entorno podrian no aplicar.'
}

# Todas las conexiones apuntan a loopback:1. Ninguna prueba apunta a una base real.
$casosT50 = @(
    @{ nombre='base-no-autorizada'; entorno='testing'; base='wings_t50_no_autorizada'; url='' },
    @{ nombre='entorno-production'; entorno='production'; base='wings_testing_codex'; url='' },
    @{ nombre='url-base-no-autorizada'; entorno='testing'; base='wings_testing_codex'; url='mysql://root@127.0.0.1:1/wings_t50_no_autorizada' }
)
$resultadosT50 = foreach ($casoT50 in $casosT50) {
    $inicioT50 = [System.Diagnostics.ProcessStartInfo]::new()
    $inicioT50.FileName = (Get-Command php).Source
    $inicioT50.WorkingDirectory = $repoT50
    $inicioT50.UseShellExecute = $false
    $inicioT50.CreateNoWindow = $true
    $inicioT50.RedirectStandardOutput = $true
    $inicioT50.RedirectStandardError = $true
    $inicioT50.ArgumentList.Add('docs/06-pruebas/PRU-04/evidencia-t50/segunda-vuelta/preparar-base.php')
    $inicioT50.Environment['APP_ENV'] = $casoT50.entorno
    $inicioT50.Environment['DB_CONNECTION'] = 'mysql'
    $inicioT50.Environment['DB_HOST'] = '127.0.0.1'
    $inicioT50.Environment['DB_PORT'] = '1'
    $inicioT50.Environment['DB_USERNAME'] = 'root'
    $inicioT50.Environment['DB_PASSWORD'] = ''
    $inicioT50.Environment['DB_DATABASE'] = $casoT50.base
    $inicioT50.Environment['DB_URL'] = $casoT50.url
    $inicioT50.Environment['SESSION_DRIVER'] = 'array'
    $inicioT50.Environment['CACHE_STORE'] = 'array'
    $procesoT50 = [System.Diagnostics.Process]::Start($inicioT50)
    $salidaT50 = $procesoT50.StandardOutput.ReadToEnd()
    $errorT50 = $procesoT50.StandardError.ReadToEnd()
    $procesoT50.WaitForExit()
    [System.IO.File]::WriteAllText((Join-Path $PSScriptRoot "guardia-$($casoT50.nombre).txt"), $salidaT50 + $errorT50)
    [pscustomobject]@{
        caso=$casoT50.nombre
        salida=$procesoT50.ExitCode
        rechazo_por_guardia=($errorT50 -match 'ERROR DE SEGURIDAD')
        llego_a_preparacion=($salidaT50 -match 'Preparando base')
        destino_efectivo_no_autorizado=($salidaT50 -match 'Database: wings_t50_no_autorizada')
    }
    $procesoT50.Dispose()
}
$resultadosT50 | ConvertTo-Json | Set-Content (Join-Path $PSScriptRoot 'guardia-resultados.json') -Encoding utf8
$resultadosT50 | Format-Table
if (@($resultadosT50 | Where-Object { -not $_.rechazo_por_guardia }).Count -gt 0) { exit 1 }
