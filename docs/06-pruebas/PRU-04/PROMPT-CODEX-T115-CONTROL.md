# PROMPT — Control de T115 — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026, a pedido de Carlos.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T115 `en_curso, tiene=Codex`.

Claude hizo T115 y no controla su propio trabajo. No des por cierto nada de este prompt. **Ejecutalo, no alcanza con leer:** lo que no ejecutaste se declara como leído.

## Qué se controla

**El problema:** en enero, Nueva liquidación propone el año nuevo. Quien cambiaba solo el mes generaba una liquidación de diciembre de ese año, vacía y sin aviso.

**El arreglo** (commit `30ea9a41`, sin desplegar): `generarLiquidacionMensual` rechaza un período posterior al mes en curso, con «No se puede liquidar un mes que todavía no empezó. Revisá el mes y el año.» Está en `app/Services/LiquidacionService.php`. Pruebas en `tests/Feature/LiquidacionMesFuturoT115Test.php`. Mutaciones de Claude en `docs/06-pruebas/PRU-04/evidencia-t113/mutaciones-control-claude.txt`: leelas, no las heredes.

| # | Caso, por pantalla o pedido real con el reloj en el 2/1/2027 | Resultado esperado |
|---|---|---|
| 1 | Profesora por hora: diciembre de 2027 | El aviso exacto; no se crea liquidación ni detalle; la pantalla conserva lo cargado |
| 2 | Profesor por comisión: diciembre de 2027 | Lo mismo |
| 3 | Febrero de 2027, también el 31/1 a las 23:59 | Lo mismo |
| 4 | Diciembre de 2026 | Se genera con sus clases. Cerrarla y pagarla sigue funcionando |
| 5 | Enero de 2027, el mes en curso, con y sin `incluir_hoy` | Se genera como antes |
| 6 | Mes y año inválidos: mes 13, año 2019, año 2028, letras | Rechazados, sin error del servidor |
| 7 | Profesora inactiva con clases pendientes de un mes pasado | Sigue pudiendo liquidarse (T46) |
| 8 | Cambio de mes a las 00:00 del día 1, en hora de Argentina | Rige la fecha de Buenos Aires, no la del servidor ni la universal |

**Mirá además, y decí qué encontraste:**

- **¿Hay otro camino que genere una liquidación?** Claude puso el control en el servicio para cubrirlos a todos. Buscá quién llama a `generarLiquidacionMensual`. `LiquidacionController` es de la API, que está apagada.
- **`recalcular`, `cerrar` y `pagar`:** ¿una liquidación futura que ya exista queda trabada o da error? Hoy no debería haber ninguna en el sitio de prueba: comprobalo en solo lectura.
- **¿Se entiende en pantalla?** El aviso aparece y el dueño puede corregir el año sin volver a cargar todo. No se tocó la propuesta de enero: la pantalla sigue proponiendo el año nuevo.
- **El manual:** `resources/manual/admin/generar-liquidacion-profesor.md` agrega esa pregunta. Que diga lo que hace Wings.
- **Liquidación de $0 de un mes pasado:** forzada, se crea vacía; por pantalla la profesora sin clases no se puede elegir. Quedó fuera de T115. Solo decí si llegás por pantalla.

**También controlá este cambio chico, que nadie miró:** el commit agregó `->orderBy('origen_id')` en `app/Services/ReporteMensualService.php`, en la consulta que ordenaba solo por período. Claude lo hizo porque dos pruebas de `ReporteMensualTest` fallaban: las filas del mismo período salían en cualquier orden. No tenía que cambiar ningún importe: comprobalo comparando Reportes antes y después sobre los mismos datos, y decí si esa consulta u otra igual de ese servicio puede seguir saliendo en distinto orden.

## Reglas

- Es un control: no corrijas. Hallazgos con gravedad y `archivo:línea` reales. Separá lo comprobado de lo inferido.
- Pruebas solo en `wings_testing_codex`, sobre una copia limpia de `main`: en la carpeta compartida hay trabajo sin subir de otros. Corré la suite rápida; si corrés la completa, avisá con una reserva y mirá las de `docs/00-estado/RESERVA-SUITE-*.md`. Las pruebas de un control van a tu carpeta de evidencia, no a `tests/`. Sin desplegar.
- El sitio de prueba y producción, solo en lectura. El reloj del sitio no se mueve: el 2/1/2027 va en tu copia.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T115-CODEX.md`, con evidencia en una carpeta propia.
- Sin hallazgos graves ni medios: T115 `cerrado, verifica=Codex`. Con hallazgos: `devuelto, tiene=Claude`.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Subí tu trabajo a `main`:** T119 y T82 quedaron en tu rama `codex/control-t119-t82` y no llegaron. Decile a Carlos cuando esté.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
