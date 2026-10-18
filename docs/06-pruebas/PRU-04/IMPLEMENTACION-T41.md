# Implementación T41 — No se puede cerrar una liquidación antes de que termine el mes (DU-37 · LIQ-005)

**Fecha:** 18/10/2026
**Autor:** Gemini (CAB)
**Verifica:** Claude
**Referencia:** DU-37 · LIQ-005 (`docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/LIQ.json`)

---

## 1. Regla de negocio y decisiones de Carlos

- **DU-37:** *«Impedir cerrar antes de que termine el mes»*.
- **Aclaración explícita de Carlos (18/10/2026):** *«A partir del 1ro de cada mes»*.
- **Motivo:** Una liquidación cerrada y luego pagada deja afuera las clases que restan del mes, porque no se admite una segunda liquidación activa para el mismo profesor y período.
- **Definición de «terminó el mes»:** Una liquidación correspondiente al período `(mes, anio)` solo puede cerrarse cuando la fecha actual en la zona horaria de Wings (`America/Argentina/Buenos_Aires`, respetando `today()` y el reloj simulado del sitio de prueba) es mayor o igual al día `01` del mes siguiente a las `00:00:00`.

---

## 2. Qué se modificó

| Archivo | Cambio |
|---|---|
| `app/Models/Liquidacion.php` | Nuevos métodos `fechaHabilitacionCierre(): Carbon` (calcula el día 1 del mes siguiente a `(anio, mes)` a las `00:00` en `config('app.timezone')`) y `puedeCerrarse(): bool` (`!today()->lt($this->fechaHabilitacionCierre())`). |
| `app/Services/LiquidacionService.php` | En `cerrarLiquidacion(int $liquidacionId)` (:252), si `!$liquidacion->puedeCerrarse()`, lanza excepción en castellano: `No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el DD/MM/AAAA.` Protege tanto `LiquidacionWebController::cerrar` como `LiquidacionController::cerrar`. |
| `resources/views/liquidaciones/show.blade.php` | En la barra de acciones de una liquidación `ABIERTA`, si `$liquidacion->puedeCerrarse()` es verdadero se muestra el formulario con el botón `Cerrar`; en caso contrario se oculta el botón `Cerrar` y se muestra el texto `Se puede cerrar desde el DD/MM/AAAA`. Sin JavaScript incrustado. `Recalcular`, `Eliminar` y `Volver` permanecen iguales. |
| `docs/02-contratos/LIQUIDACIONES_CONTRATO_V2.md` | Enmienda 18/10/2026 en §2.4 y actualización de §5.2 y §8. |
| `tests/Feature/CerrarLiquidacionMesTerminadoTest.php` | 5 pruebas nuevas con reloj fijo (`travelTo`). |

### Relevamiento de otras pantallas de liquidaciones
- Se verificaron `resources/views/liquidaciones/index.blade.php` y `resources/views/liquidaciones/create.blade.php`: ninguna de las dos ofrece el botón ni la acción de cerrar liquidación (el listado ofrece únicamente `Ver` y `Eliminar`). La única pantalla que ofrece `Cerrar` es `resources/views/liquidaciones/show.blade.php`.
- Cancelar una liquidación cerrada no pagada (`FIN-12`) y pagar (`LiquidacionPagoService::marcarComoPagada`) no se modificaron.

---

## 3. Verificado vs. Inferido

### Verificado
1. **Límite exacto de fecha y hora (`America/Argentina/Buenos_Aires`):**
   - `2026-10-31 23:59:00` sobre una liquidación de `10/2026`: `POST /liquidaciones/{id}/cerrar` rechaza el cierre con el mensaje `No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el 01/11/2026.` y la liquidación permanece `ABIERTA`.
   - `2026-11-01 00:00:00` sobre la misma liquidación de `10/2026`: `POST /liquidaciones/{id}/cerrar` la cierra y pasa a `CERRADA`.
   - Liquidación de un mes anterior (`09/2026` consultada el `18/10/2026`): cierra normalmente.
   - Llamado directo a `LiquidacionService::cerrarLiquidacion` a las `23:59:59` del `31/10/2026` lanza la excepción; a las `00:00:00` del `01/11/2026` cierra la liquidación.
2. **Pruebas existentes de liquidaciones:**
   - Se corrieron `CancelarLiquidacionCerradaTest`, `LiquidacionHoraPorDuracionTest`, `LiquidacionHoraVistaYReciboTest` e `ImportesPesosT24Test` sobre `wings_testing_gemini`: todas usan períodos ya cerrados (agosto o septiembre de 2026) y pasaron sin requerir ajustes de fecha.
   - Nota sobre seeders: `database/seeders/ReportesSueldosEscenarioSeeder.php` (seeder manual para capturas de T30 que fija reloj en `2026-10-09` y cierra octubre) no forma parte de ninguna prueba automatizada ni fue modificado.
3. **Cumplimiento de CSP (`AGENTS.md §5-bis`):**
   - `CspSinCodigoIncrustadoTest` pasa en verde (`8` bloques `<script>` y `8` manejadores `on*=`, sin incremento).
4. **Verificación visual en navegador (Chrome headless sobre `wings_testing_gemini`):**
   - Capturas tomadas a `1366x900` y en marco celular `360px` (`<iframe>` de 360px dentro de ventana de `1000x1000`), más control de `/login` a `1366` y `360`:
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-octubre-mes-en-curso-1366.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-octubre-mes-en-curso-360.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-septiembre-mes-terminado-1366.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-septiembre-mes-terminado-360.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-octubre-1-noviembre-1366.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/liquidacion-octubre-1-noviembre-360.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/login-control-1366.png`
     - `docs/06-pruebas/PRU-04/evidencia-t41/login-control-360.png`

### Inferido
- Ningún punto quedó inferido.
