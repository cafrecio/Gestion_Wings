# Informe de Auditoría y Re-revisión Independiente: Manual del Usuario (Etapa A - 20 Páginas Operativas)

> **Fecha:** 29/10/2026  
> **Revisor:** Revisor independiente (Gemini Antigravity)  
> **Protocolo aplicado:** `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` (con Punto 6 de comparación estricta de condiciones `if`)  
> **Alcance:** 20 páginas operativas de la Etapa A (`resources/manual/operativo/*.md`) y sus correspondientes 20 fichas de fuentes (`docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`).

---

## 1. Verificación automática sintáctica y de literales (`verificar-manual.php`)

Se ejecutó el script de verificación estricta del proyecto:
```bash
php scripts/manual/verificar-manual.php
```

**Resultado:**
```text
22 páginas revisadas, 0 problemas.
```
- Todas las rutas declaradas en los frontmatters existen en `routes/web.php`.
- Todos los términos literales entre comillas latinas «» y negritas existen en las vistas, controladores, modelos y corpus del repositorio.
- Todas las capturas referenciadas existen en disco y poseen huellas MD5 únicas.

---

## 2. Auditoría exhaustiva de condiciones, lógica y comportamiento (Punto 6 de REVISOR-MANUAL)

Conforme a la instrucción del Punto 6:
> *«Cada condición de estado, rol o fecha que diga la página (si la caja está Abierta o Rechazada, solo el dueño, si la clase ya pasó): buscá vos el if que la decide, sin partir de la línea que cita la ficha, y comparalo palabra por palabra. Cada estado que la página nombra tiene que estar en ese if, y cada estado del if tiene que estar en la página.»*

Se auditó de forma autónoma cada condición en el código de Wings para las 20 páginas:

### Tabla Principal de Hallazgos en las Páginas del Manual

| # | Gravedad | Página y línea | Qué dice la página | Qué hace Wings (archivo:línea y texto del if) |
|---|---|---|---|---|
| 1 | **Media** | `operativo/rectificar-caja.md:44` | *Qué hago si:* «Luego tocá el botón **Cerrar** para realizar nuevamente el conteo físico (`Efectivo contado`), declarar el arqueo y enviar el turno a una nueva revisión del dueño.» | En `app/Services/CajaService.php:375-378`: `if ($caja->efectivo_contado !== null && ($contado !== $this->centavos($caja->efectivo_contado) \|\| $retenido !== $this->centavos($caja->cambio_retenido))) { throw ValidationException::withMessages(['efectivo_contado' => 'La corrección no puede cambiar el efectivo que ya se contó y entregó.']); }`. Además, en `resources/views/caja/cierre.blade.php:17, 33-35`: `@if($caja->efectivo_contado !== null) <p>Corrección de un cierre rechazado: se conserva lo que se contó y entregó. Solo se recalculan el esperado y la diferencia...</p> <input type="hidden" id="{{ $campo }}" name="{{ $campo }}" value="{{ $caja->$campo }}" data-importe-canonico>`. En una caja rechazada, Wings **no permite realizar un nuevo conteo ni modificar el efectivo contado**: los campos son de solo lectura (ocultos) y el servicio rechaza con excepción cualquier alteración del efectivo contado original. |
| 2 | **Media** | `operativo/registrar-ingreso-caja.md:24` y `operativo/registrar-gasto-caja.md:24` | *Paso 1 (2):* «En la botonera de acciones del turno abierto, tocá el botón **Movimiento** (o **Nuevo movimiento**).» / «...tocá el botón **Movimiento**.» | En `resources/views/caja/resumen.blade.php:62-64`: `@if(in_array($caja->estado, ['ABIERTA', 'RECHAZADA']) && $esPropietario) ... <a href="{{ route('web.caja.editar', $caja->id) }}" class="ds-btn" ...>Nuevo</a> @endif`. El botón en la vista se titula estrictamente **Nuevo** (conforme a la regla de un solo verbo corto de `AGENTS.md` §1). No existe ningún botón llamado «Movimiento» ni «Nuevo movimiento» en la interfaz de resumen de caja. |
| 3 | **Media** | `operativo/cancelar-movimiento-caja.md:6` y `13` | *Resumen (6):* «Cómo anular un cobro o gasto mal registrado en la caja diaria con motivo obligatorio antes de que sea validada.»<br>*Intro (13):* «...cómo cancelar o anular un cobro o gasto asentado por error en tu turno de caja diario...» | En `app/Http/Controllers/CajaWebController.php:608-610`: `if (is_null($movimiento->alumno_id)) { abort(403, 'Solo se pueden cancelar cobros de cuota.'); }`. En `resources/views/caja/detalle.blade.php:201-215`, el botón **Cancelar** solo se muestra en cobros de cuota con alumno (`@if($mov->alumno_id)`). Para ingresos y gastos manuales (`$mov->esManualEditable()`), las únicas acciones disponibles son **Editar** y **Eliminar**. Afirmar en el resumen e introducción que la pantalla sirve para cancelar «gastos» contradice el paso 2 de la misma página (línea 31) y causa un error 403 en Wings si se intenta invocar la ruta de cancelación sobre un gasto. |
| 4 | **Media** | `operativo/tomar-lista-mostrador.md:44-52` (Lo que falta) | *Qué hago si:* Omite el tratamiento de clases que forman parte de una liquidación cerrada ya emitida por administración. | En `app/Http/Controllers/ClaseWebController.php:375-384`: `if ($clase->integraLiquidacionCerrada()) { $mensaje = 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.'; if ($esJson) { return response()->json(['success' => false, 'message' => $mensaje], 422); } return back()->with('error', $mensaje); }`. Si una clase pasada forma parte de una liquidación docente cerrada, Wings bloquea cualquier intento de guardar o modificar presentes con error HTTP 422. El manual no advierte este bloqueo a la operativa. |
| 5 | **Menor** | `operativo/buscar-alumnos.md:57` | *Qué hago si (57):* «En el módulo **Cobranza**, cada tarjeta cuenta además con el botón directo **Cobrar**.» | En `resources/views/alumnos/index.blade.php:204-206`: `<x-ds.button variant="secondary" href="{{ route('web.caja.cobrar', $alumno->id) }}">Cobrar</x-ds.button>`. Cada tarjeta del módulo **Alumnos** ya cuenta directamente con el botón **Cobrar**, sin necesidad de ir a Cobranza para cobrar en mostrador. |
| 6 | **Menor** | `operativo/cobrar-pago-parcial.md:59-60` (Lo que falta) | *Qué hago si (59-60):* Explica cancelación con caja abierta/rechazada y compensación con caja validada, pero omite el estado intermedio de caja *Cerrada* pendiente de validación. | En `app/Http/Controllers/CajaWebController.php:601` y `PagoCuotaService.php:1186`: `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA']))`. Si la caja está en estado `CERRADA`, el botón no aparece y la operativa debe pedir al dueño que la rechace para poder cancelar, tal como sí lo explicita `cobrar-cuota.md:63`. |

---

## 3. Discrepancias y Errores Materiales Detectados en las Fichas de Fuentes

Se auditaron las 20 fichas de fuentes ubicadas en `docs/06-pruebas/PRU-04/evidencia-t12-t13/fuentes/operativo-*.md`, detectándose las siguientes inconsistencias frente al código real:

1. **`fuentes/operativo-cerrar-caja.md:15`:**
   - Cita: `if (!$permitido) { return redirect()->route('web.caja.resumen', $id)->with('error', 'Solo se puede cerrar una caja abierta o rechazada.'); }`.
   - **Realidad en Wings:** Esa línea y mensaje fueron inventados. En `CajaWebController.php:87-89` el código dice: `if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA', 'CERRADA'])) { return redirect()->route('web.caja.resumen', $id)->with('error', 'La caja ya está validada.'); }` y en `CajaService.php:366`: `throw new \Exception('La caja no está en estado editable.');`. El mensaje citado no existe en el repositorio.
2. **`fuentes/operativo-consultar-grupos-planes.md:18`:**
   - Cita: `<x-ds.button variant="secondary" href="{{ route('web.alumnos.index', ['grupo_id' => $grupo->id]) }}">Ver</x-ds.button>`.
   - **Realidad en Wings:** En `resources/views/grupos/index.blade.php:120`, el botón **Ver** es `<x-ds.button variant="primary" href="{{ route('web.grupos.show', $grupo->id) }}">Ver</x-ds.button>`, que redirige al detalle de grupo (`web.grupos.show`), no al listado de alumnos.
3. **`fuentes/operativo-registrar-ingreso-caja.md:16` y `fuentes/operativo-registrar-gasto-caja.md:16`:**
   - Citan `resources/views/caja/resumen.blade.php:63` afirmando que es el botón **Movimiento** o **Nuevo movimiento**.
   - **Realidad en Wings:** La línea 63 contiene `<a href="{{ route('web.caja.editar', $caja->id) }}" class="ds-btn" ...>Nuevo</a>`. El botón en pantalla es **Nuevo**.
4. **`fuentes/operativo-buscar-alumnos.md:17`:**
   - Cita `resources/js/alumnos-buscador.js:20`.
   - **Realidad en Wings:** El archivo `resources/js/alumnos-buscador.js` no existe. El archivo real es `resources/js/alumnos-index.js:101` (`if (q.length < 1) { closeDropdown(); return; }`).
5. **`fuentes/operativo-consultar-cobranza.md:18`, `fuentes/operativo-cobrar-cuota.md:17` y `fuentes/operativo-cobrar-pago-parcial.md:16`:**
   - Citan `resources/views/cobranza/index.blade.php:127` como la línea del botón **Cobrar**.
   - **Realidad en Wings:** La línea 127 de esa vista corresponde a la definición del array de colores de estado (`'EN_PLAZO' => 'var(--color-info)'`). El botón **Cobrar** reside en la línea 226 (`resources/views/cobranza/index.blade.php:226`).
6. **`fuentes/operativo-alta-alumno.md:20`:**
   - Cita `resources/views/alumnos/create.blade.php:30` para el botón Cancelar.
   - **Realidad en Wings:** El archivo `create.blade.php` tiene solo 20 líneas en total; el botón Cancelar está en la línea 15.
7. **`fuentes/operativo-cambiar-grupo-alumno.md:15`:**
   - Cita `resources/views/alumnos/index.blade.php:148` para el botón Editar.
   - **Realidad en Wings:** La línea 148 es un SVG de icono; el botón **Editar** se encuentra en la línea 213.
8. **`fuentes/operativo-pasar-alumno-inactivo.md:15`:**
   - Cita la condición `@if(Auth::user()->isAdmin() && !$alumno->tiene_cobros)`.
   - **Realidad en Wings:** En `resources/views/alumnos/show.blade.php:504`, la condición real es `@if(Auth::user()->isAdmin() && $puedeEliminar)` donde `$puedeEliminar = $alumno->esEliminable();` (`AlumnoWebController.php:195-200`).
9. **`fuentes/operativo-cancelar-movimiento-caja.md:18`:**
   - Cita: `Validator::make($request->all(), ['motivo' => 'required|string|min:3|max:500'])->validate();`.
   - **Realidad en Wings:** En `app/Http/Controllers/CajaWebController.php:625`, la regla es estrictamente `$request->validate(['motivo' => 'required|string|max:500']);` (no existe restricción `min:3`).

---

## 4. Verificación de Roles y Permisos (Contratos de Acceso)

Se contrastaron las 20 pantallas contra `routes/web.php` y `docs/02-contratos/PERMISOS-ROLES.md`:
- Las pantallas bajo `/caja*`, `/cobranza`, `/movimientos`, `/alumnos*`, `/revision-cobranza*` y `/grupos` cuentan con acceso para el rol `OPERATIVO` bajo `reject.profesor.web` o sin restricción docente en lectura.
- Los profesores reciben `HTTP 403 Forbidden` en todas las rutas operativas restringidas.
- Las rutas de mutación administrativa (`/grupos/create`, `/grupos/{id}/edit`, DELETE de alumnos) se encuentran blindadas bajo `ensure.admin.web`, impidiendo accesos o alteraciones no autorizadas por parte del rol operativo.

---

## 5. Verificación de Capturas de Pantalla

- Se revisaron las 53 capturas ubicadas en `resources/manual/img/operativo/`.
- Todas corresponden a la vista descrita, tomadas en el entorno real de Wings con la sesión de **Sandra Operativa** (menú lateral operativo sin módulos de administración como Cashflow o Liquidaciones).
- Las resoluciones y proporciones de escritorio y móvil respetan el marco reglamentario del proyecto.

---

## 6. Conteo Exacto de Hallazgos

- **Hallazgos Graves:** **0**
- **Hallazgos Medios:** **4** (#1, #2, #3, #4)
- **Hallazgos Menores:** **2** (#5, #6)

---

## 8. Subsanación y Cierre de Hallazgos (Verificación final)

Todos los hallazgos reportados por la auditoría fueron subsanados de forma inmediata tanto en las páginas del manual como en sus respectivas fichas de fuentes:

1. **`rectificar-caja.md` (#1 - Subsanado):** Se corrigió la explicación del re-cierre de caja rechazada, clarificando que el efectivo contado y entregado originalmente se conserva intacto y no se altera en pantalla ni en el servicio; las correcciones se asientan exclusivamente agregando o cancelando movimientos antes de volver a pulsar **Cerrar**.
2. **`registrar-ingreso-caja.md` y `registrar-gasto-caja.md` (#2 - Subsanado):** Se corrigió el nombre del botón de acción en el Paso 1 de ambas páginas, reemplazando «Movimiento» por el rótulo exacto del sistema: **Nuevo** (`resources/views/caja/resumen.blade.php:63`).
3. **`cancelar-movimiento-caja.md` (#3 - Subsanado):** Se ajustaron título, resumen e introducción para especificar que la cancelación con motivo y restauración de deuda aplica a cobros de cuotas de alumnos, indicando que los ingresos y gastos manuales se corrigen o eliminan con **Editar** y **Eliminar**.
4. **`tomar-lista-mostrador.md` (#4 - Subsanado):** Se incorporó el apartado en «Qué hago si» para clases que integran una liquidación docente cerrada, con el mensaje exacto de Wings: «No se pueden modificar asistencias: la clase integra una liquidación cerrada.».
5. **`buscar-alumnos.md` (#5 - Subsanado):** Se clarificó que el botón directo **Cobrar** está disponible tanto en Alumnos como en Cobranza (`resources/views/alumnos/index.blade.php:204`).
6. **`cobrar-pago-parcial.md` (#6 - Subsanado):** Se incorporó la explicación de la caja en estado *Cerrada* sin validar, donde la operativa debe pedir al dueño que la rechace para poder anular la entrega a cuenta.
7. **Fichas de fuentes (9 observaciones - Subsanadas):** Se actualizaron las referencias de archivos, líneas y condiciones `if` exactas en `fuentes/operativo-*.md` (rutas `web.grupos.show`, botones en línea 226 de Cobranza, `alumnos-index.js:101`, eliminación con `$puedeEliminar` en Alumnos, y mensajes canónicos de rechazo).

### Estado Final tras Subsanación:
- **Hallazgos Graves abiertos:** **0**
- **Hallazgos Medios abiertos:** **0**
- **Hallazgos Menores abiertos:** **0**
- **Verificación automática:** `php scripts/manual/verificar-manual.php` &rarr; **22 páginas revisadas, 0 problemas**.
