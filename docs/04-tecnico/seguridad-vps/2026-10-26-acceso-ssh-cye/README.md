# T83 — Acceso SSH desde CyE verificado — 26/10/2026

Implementó Claude; verificó Codex CyE, independientemente, sobre `26e7f559`.

## Comprobación real

En `VENTAS_CYE`, a las 09:25–09:27 de Argentina:

```text
ssh -G vps → port 2222
ssh -o BatchMode=yes -o ConnectTimeout=15 vps hostname
srv1753195.hstgr.cloud
Salida: 0
```

El alias se conecta al servidor desde CyE por el puerto 2222. Se comprobó el acceso sin cambiar claves, configuración ni servicios del VPS.

## Revisión del cambio

Leído completo `scripts/maquina/instalar-acceso-servidor.ps1`: antes de probar los puertos cambia `$ErrorActionPreference` a `Continue`, recorre 22, 2222 y 8022 y corta ante una conexión exitosa. Cuando responde un puerto alternativo, escribe su valor en el bloque `Host vps`. `CLAUDE.md` documenta el acceso alternativo desde CyE.

El índice MCP no acredita cobertura actual del archivo; se verificó su fuente real. No se volvió a ejecutar el instalador ni a recrear el alias: el criterio de T83 pide confirmar `ssh vps hostname` en CyE y esa comprobación pasó. No se certifica aquí una instalación desde cero, el puerto 8022 ni la causa concreta del bloqueo anterior del 22.

T83 queda cerrada. Este control levanta el bloqueo de acceso; no ejecuta los días 17–18, no mueve el reloj ni corre sus rutinas. El control previo de esas jornadas sigue pendiente. Sin cambios de aplicación, suite ni despliegue.
