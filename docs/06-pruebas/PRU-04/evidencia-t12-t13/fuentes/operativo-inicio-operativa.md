# Ficha de fuentes: inicio-operativa

- **Página:** `resources/manual/operativo/inicio-operativa.md` · **Rol:** operativo · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Estados del mostrador | `resources/views/operativo/dashboard.blade.php:111` | `@elseif($bloqueo['activo'] && $cajaPropia)` | Captura de la pantalla real (CapturasEtapaCTest). |
| Aviso de caja rechazada | `resources/views/operativo/dashboard.blade.php:31` | `@if($cajasRechazadasCount > 0)` | Leído en el código; no ejecutado en pantalla. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «El administrador rechazó el cierre. Revisá las observaciones y corregí el arqueo.» | `resources/views/operativo/dashboard.blade.php:39` | Sí — El mostrador |
