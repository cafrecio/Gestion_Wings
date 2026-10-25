# T78 — Motivo del cobro cancelado en Detalle de caja, y vencimiento y total en la ficha del alumno nuevo

- **Agente:** Gemini CAB (`LOG GEM CAB`)
- **Fecha:** 25/10/2026
- **Estado:** Implementado localmente (`a_verificar` — falta el visto de Carlos en las capturas y la verificación de lógica por otro agente; sin despliegue).
- **Origen:** Dos arreglos chicos surgidos de la prueba por días (`PRU-04`, Días 4 y 5), pedidos por Carlos el 25/10/2026.

---

## 1. Cambios implementados

### 1.1. Motivo del cobro cancelado en el detalle de caja (Día 5, caso `CAJ-033 b`)
- **Archivo:** [`resources/views/caja/detalle.blade.php`](../../../resources/views/caja/detalle.blade.php) (líneas 167–176).
- **Qué pasaba:** En la tabla de movimientos del detalle de caja, un movimiento con `estado === 'CANCELADO'` se veía tachado y solo mostraba el texto «Cancelado» en la columna de acciones cuando `$hasEditar` era verdadero (caja `ABIERTA` o `RECHAZADA` vista por la operativa propietaria). Además, no mostraba `movimientos_operativos.motivo_cancelacion`, que solo aparecía en el recibo PDF anulado. Cuando el dueño (`ADMIN`) o la operativa miraban una caja `CERRADA` o `VALIDADA`, no aparecía ni «Cancelado» ni el motivo.
- **Solución:** En la celda `Alumno / Obs.` de cada fila cancelada se muestra debajo de la descripción tachada (sin tachar y en `var(--color-danger)`):
  ```blade
  Cancelado{{ $mov->motivo_cancelacion ? ': ' . $mov->motivo_cancelacion : '' }}
  ```
  De este modo, el motivo guardado en `movimientos_operativos.motivo_cancelacion` se ve junto a «Cancelado» tanto para la operativa como para el dueño en cualquier estado de la caja (`ABIERTA`, `CERRADA`, `VALIDADA`, `RECHAZADA`).

### 1.2. Vencimiento y total en la ficha del alumno nuevo (Día 4)
- **Archivos:**
  - [`app/Http/Controllers/AlumnoWebController.php`](../../../app/Http/Controllers/AlumnoWebController.php) (`show`, líneas 156–208).
  - [`resources/views/alumnos/show.blade.php`](../../../resources/views/alumnos/show.blade.php) (líneas 168–194).
- **Qué pasaba:** En la ficha de un alumno recién inscripto en estado `En plazo` (como Mora Benítez, alta `13/10/2026`, inscripción `$5.000` y cuota octubre `$40.000`), la sección `Estado de cobranza` mostraba «En plazo · 6 días de gracia», pero no la fecha en que vence ni el total adeudado (inscripción + cuotas).
- **Solución:**
  - En `AlumnoWebController::show`:
    - Se obtiene el saldo total unificado de la persona desde `CobranzaEstadoService::saldoDeAlumnos($mismoDni)[$alumno->id]` (`$totalAdeudado`), garantizando que la ficha muestre exactamente el mismo importe total que `Cobranza` (inscripción única por DNI + cuotas pendientes + punitorios vigentes).
    - Cuando `$estadoCobranza['estado'] === CobranzaEstadoService::ESTADO_EN_PLAZO`, se obtiene la fecha de vencimiento reutilizando los métodos existentes de `CobranzaEstadoService` (`limiteDeLaCuota($alumno, $deudaPendiente)` y, si debe inscripción, `limiteDeAlta($alumno)`), sin recalcular fechas en la vista. Para Mora Benítez (alta `13/10/2026`, plazo de alta 7 días), devuelve `20/10/2026`.
    - Para un alumno `Al día`, `$fechaVencimiento` es `null` y `$totalAdeudado` es `0.0`.
  - En `resources/views/alumnos/show.blade.php`:
    - Junto al sello de estado y los días de gracia se muestran:
      - `Vence {{ $ecVencimiento->format('d/m/Y') }}` cuando `$ecVencimiento` está presente (`Vence 20/10/2026`).
      - `Total adeudado: {{ FormatoImporte::pesos($ecTotal) }}` con clase `ds-money ds-money--row` cuando `$ecTotal > 0` (`Total adeudado: $45,000.00`).
    - Un alumno `Al día` no muestra ni vencimiento ni total adeudado.
    - **Ajuste visual pedido por Carlos (Opción A y padding, 25/10/2026):** Se retiró el botón `Cobrar` duplicado que aparecía dentro del recuadro celeste de cada cuota (`$ecDeudas`) y de cada cargo punitorio (`$punitorios`), conservando un único botón `Cobrar` en la barra superior de acciones de la ficha (junto a `Activo` y `Editar`) y manteniendo los botones `Condonar` / `Reactivar` para `ADMIN`. Además, se agregó padding (`padding: 8px 12px;`) al recuadro celeste para que el período y el monto no queden pegados al borde en pantallas móviles (375 px).

---

## 2. Reglas de diseño y alcance verificadas

- Leídos antes de tocar vistas: `docs/03-diseno-ui/design-system/DESIGN-RULES.md`, `docs/03-diseno-ui/wings-design/SKILL.md` y la vista canónica `resources/views/alumnos/index.blade.php`.
- Cero bloques `<script>` y cero atributos de evento inline (`CspSinCodigoIncrustadoTest` en verde).
- Sin colores hexadecimales hardcodeados; se usan tokens CSS (`var(--color-danger)`, `var(--color-text)`, `var(--color-text-muted)`) y `FormatoImporte::pesos()`.
- No se tocaron `CajaWebController::cobrar`, `resources/views/caja/cobrar.blade.php`, `ReporteMensualService`, `CashflowWebController`, `CobranzaEstadoService`, `SecurityHeaders` ni `docs/06-pruebas/PRU-04/tabla-de-pruebas/`.

---

## 3. Pruebas automatizadas

Archivo creado: [`tests/Feature/DetalleCajaCanceladoYFichaAltaT78Test.php`](../../../tests/Feature/DetalleCajaCanceladoYFichaAltaT78Test.php) (3 pruebas, 23 aserciones):

1. `test_el_detalle_de_caja_muestra_el_motivo_de_un_cobro_cancelado_para_operativa_y_dueno`: comprueba que un cobro cancelado muestra `Cancelado: Medio equivocado: cobró MP, era Efectivo` en `caja.detalle` tanto para la operativa (caja abierta y cerrada) como para el dueño (caja cerrada).
2. `test_la_ficha_de_un_alumno_con_alta_reciente_muestra_vencimiento_y_total_igual_a_cobranza`: con reloj fijado en `2026-10-13 18:15:00`, alta `2026-10-13`, inscripción `$5.000` y cuota octubre `$40.000`, comprueba que la ficha de Mora Benítez muestra `En plazo`, `6 días de gracia`, `Vence 20/10/2026` y `Total adeudado: $45,000.00`, idéntico a `deuda_total` de `CobranzaEstadoService::listadoCobranza('TODOS')`.
3. `test_un_alumno_al_dia_no_muestra_vencimiento_ni_total`: comprueba que un alumno con cuota pagada (`Al día`) no muestra `Vence` ni `Total adeudado:`.

- **Suite rápida (`--exclude-group=lento`) en `wings_testing_gemini`:** **920 aprobadas, 6 omitidas, 0 fallidas** (7.378 aserciones, 209,66 s, código de salida 0). Registro en [`evidencia-t78/suite-rapida.txt`](evidencia-t78/suite-rapida.txt).
- **Guardianes:** `CspSinCodigoIncrustadoTest`, `DocumentacionNoMienteTest` y `TableroAlDiaTest` en verde.

---

## 4. Evidencia visual en Chrome real (1366 px y 375 px)

Capturas tomadas en Chrome real sobre la aplicación andando (`wings_testing_gemini`, sin marcos ni HTML estático), guardadas en [`docs/06-pruebas/PRU-04/evidencia-t78/`](evidencia-t78/):

| Pantalla | Rol | Escritorio (1366 px) | Celular (375 px) |
|---|---|---|---|
| Ficha de Mora Benítez (`En plazo`, `Vence 20/10/2026`, `Total adeudado: $45,000.00`) | OPERATIVO | [`ficha-mora-en-plazo-1366.png`](evidencia-t78/ficha-mora-en-plazo-1366.png) | [`ficha-mora-en-plazo-375.png`](evidencia-t78/ficha-mora-en-plazo-375.png) |
| Ficha de Lucas Gómez (`Al día`, sin vencimiento ni total) | OPERATIVO | [`ficha-alumno-al-dia-1366.png`](evidencia-t78/ficha-alumno-al-dia-1366.png) | [`ficha-alumno-al-dia-375.png`](evidencia-t78/ficha-alumno-al-dia-375.png) |
| Detalle de caja abierta con motivo de cobro cancelado | OPERATIVO | [`caja-detalle-operativa-1366.png`](evidencia-t78/caja-detalle-operativa-1366.png) | [`caja-detalle-operativa-375.png`](evidencia-t78/caja-detalle-operativa-375.png) |
| Detalle de caja cerrada con motivo de cobro cancelado | ADMIN | [`caja-detalle-dueno-1366.png`](evidencia-t78/caja-detalle-dueno-1366.png) | [`caja-detalle-dueno-375.png`](evidencia-t78/caja-detalle-dueno-375.png) |

Mediciones de viewport y contenido en [`evidencia-t78/mediciones.json`](evidencia-t78/mediciones.json) (`innerWidth === scrollWidth` en 1366 y 375).
