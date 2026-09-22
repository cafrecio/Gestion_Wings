# Deja una computadora entrando al servidor con `ssh vps`. Lo corre el agente, no Carlos.
#
# Como funciona el acceso: el servidor acepta solo claves autorizadas y el acceso por
# contrasena esta apagado a proposito. La clave de cada maquina es su propio
# ~/.ssh/id_ed25519, cargada en la cuenta de GitHub de Carlos; una maquina que ya
# tiene acceso la autoriza en el servidor bajandola de https://github.com/cafrecio.keys.
# Ninguna clave privada viaja entre maquinas ni pasa por el repositorio.
#
# Estado al 22/09/2026: autorizadas CAB (cafre@CAB-vps) y CyE (cafre@CyE-github).
#
# Este script solo arma el alias `vps` con la clave que la maquina ya tiene, y prueba.
# Uso, desde la raiz del proyecto:
#   powershell -ExecutionPolicy Bypass -File scripts\maquina\instalar-acceso-servidor.ps1

$ErrorActionPreference = 'Stop'

$Servidor = '2.25.204.38'
$SshDir   = Join-Path $env:USERPROFILE '.ssh'
$Config   = Join-Path $SshDir 'config'

function Paso($texto) { Write-Host "`n== $texto ==" }

Paso '1. clave de esta maquina'
$clave = @('id_ed25519', 'id_ed25519_vps', 'id_rsa') |
    ForEach-Object { Join-Path $SshDir $_ } |
    Where-Object { Test-Path $_ } |
    Select-Object -First 1

if (-not $clave) {
    Write-Host 'Esta maquina no tiene clave SSH. Se crea una.'
    New-Item -ItemType Directory -Force -Path $SshDir | Out-Null
    $clave = Join-Path $SshDir 'id_ed25519'
    & ssh-keygen -q -t ed25519 -N '""' -C "cafre@$env:COMPUTERNAME" -f $clave
    Write-Host ''
    Write-Host 'Clave nueva creada. Falta autorizarla una sola vez:' -ForegroundColor Yellow
    Write-Host '  1. Cargar esta clave publica en GitHub (Settings > SSH keys):'
    Get-Content "$clave.pub"
    Write-Host '  2. Desde una maquina que ya entra, un agente la autoriza bajandola de'
    Write-Host '     https://github.com/cafrecio.keys'
    exit 1
}
Write-Host "Se usa $clave"

Paso '2. alias vps'
$yaEsta = (Test-Path $Config) -and (Select-String -Path $Config -Pattern '^\s*Host\s+vps\s*$' -Quiet)
if ($yaEsta) {
    Write-Host "Ya existe 'Host vps' en $Config."
} else {
    $relativa = '~/.ssh/' + (Split-Path $clave -Leaf)
    $bloque = @"

Host vps
    HostName $Servidor
    User root
    IdentityFile $relativa
    IdentitiesOnly yes
"@
    Add-Content -Path $Config -Value $bloque -Encoding ascii
    Write-Host "Agregado a $Config"
}

Paso '3. prueba'
$salida = & ssh -o ConnectTimeout=15 -o BatchMode=yes -o StrictHostKeyChecking=accept-new vps hostname 2>&1 |
    Where-Object { $_ -notmatch 'post-quantum|store now|openssh.com|vulnerable' }
if ($LASTEXITCODE -eq 0) {
    Write-Host "Acceso OK: $salida" -ForegroundColor Green
} else {
    Write-Host "No entra: $salida" -ForegroundColor Red
    Write-Host 'Si dice "Permission denied (publickey)", la clave de esta maquina no esta'
    Write-Host 'autorizada: cargarla en GitHub y autorizarla desde una maquina que ya entra.'
    exit 1
}
