# Deja una computadora con acceso al servidor por `ssh vps`.
#
# Por que existe: el servidor solo acepta claves autorizadas una por una y el
# acceso por contrasena esta apagado a proposito. Hasta el 22/09/2026 solo estaba
# autorizada la clave de CAB, asi que desde CyE ningun agente podia entrar y todo
# trabajo de servidor quedaba para "cuando estes en la otra maquina".
#
# La clave privada NO esta en el repositorio y no tiene que estar nunca: abre el
# servidor como root. Se genera en una maquina que ya tiene acceso, se autoriza
# ahi y se trae a mano (pendrive). Este script solo la instala.
#
# Uso, desde la raiz del proyecto:
#   powershell -ExecutionPolicy Bypass -File scripts\maquina\instalar-acceso-servidor.ps1
#   powershell -ExecutionPolicy Bypass -File scripts\maquina\instalar-acceso-servidor.ps1 -Origen E:\CLAVE-SERVIDOR-PARA-CYE

param(
    [string]$Origen = (Join-Path $env:USERPROFILE 'Desktop\CLAVE-SERVIDOR-PARA-CYE')
)

$ErrorActionPreference = 'Stop'

$Servidor = '2.25.204.38'
$SshDir   = Join-Path $env:USERPROFILE '.ssh'
$Clave    = Join-Path $SshDir 'id_ed25519_vps'
$Config   = Join-Path $SshDir 'config'

function Paso($texto) { Write-Host "`n== $texto ==" }

Paso '1. clave'
$claveOrigen = Join-Path $Origen 'id_ed25519_vps'
if (Test-Path $Clave) {
    Write-Host "Ya hay una clave en $Clave; no se pisa."
} elseif (Test-Path $claveOrigen) {
    New-Item -ItemType Directory -Force -Path $SshDir | Out-Null
    Copy-Item $claveOrigen $Clave
    Copy-Item "$claveOrigen.pub" "$Clave.pub" -ErrorAction SilentlyContinue
    Write-Host "Copiada desde $Origen"
} else {
    Write-Host "No encuentro la clave en $claveOrigen." -ForegroundColor Red
    Write-Host 'Pasa la carpeta CLAVE-SERVIDOR-PARA-CYE a esta maquina o indica donde esta con -Origen.'
    exit 1
}

# OpenSSH de Windows rechaza una clave privada que otros usuarios puedan leer:
# falla con "UNPROTECTED PRIVATE KEY FILE" y no dice nada mas util.
& icacls $Clave /inheritance:r | Out-Null
& icacls $Clave /grant:r "$($env:USERNAME):(R)" | Out-Null
Write-Host 'Permisos restringidos al usuario actual.'

Paso '2. alias vps'
$bloque = @"

Host vps
    HostName $Servidor
    User root
    IdentityFile ~/.ssh/id_ed25519_vps
    IdentitiesOnly yes
"@
$yaEsta = (Test-Path $Config) -and (Select-String -Path $Config -Pattern '^\s*Host\s+vps\s*$' -Quiet)
if ($yaEsta) {
    Write-Host "Ya existe un 'Host vps' en $Config; no se toca. Si apunta a otra clave, corregirlo a mano."
} else {
    Add-Content -Path $Config -Value $bloque -Encoding ascii
    Write-Host "Agregado a $Config"
}

Paso '3. prueba'
$salida = & ssh -o ConnectTimeout=15 -o BatchMode=yes -o StrictHostKeyChecking=accept-new vps hostname 2>&1 |
    Where-Object { $_ -notmatch 'post-quantum|store now|openssh.com|vulnerable' }
if ($LASTEXITCODE -eq 0) {
    Write-Host "Acceso OK: $salida" -ForegroundColor Green
    Write-Host ''
    Write-Host "Borra ahora la copia de $Origen (pendrive o escritorio): la clave ya quedo en $Clave."
} else {
    Write-Host "No entra: $salida" -ForegroundColor Red
    Write-Host 'Si dice "Permission denied (publickey)", la clave no esta autorizada en el servidor:'
    Write-Host 'autorizarla desde una maquina que ya tenga acceso.'
    exit 1
}
