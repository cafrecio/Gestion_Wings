# Verificación independiente de T38 — Reactivar a un alumno le genera la cuota del mes

- **Fecha:** 18/10/2026
- **Verificador:** Gemini (**LOG GEM CAB**)
- **Autor de la implementación:** Claude (`cbc851e`, `3cef48b`, `544ace4` y `a372008`)
- **Informe revisado:** [`docs/06-pruebas/PRU-04/IMPLEMENTACION-T38.md`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/IMPLEMENTACION-T38.md)
- **Base utilizada para pruebas y navegador:** `wings_testing_gemini` (sin despliegue ni uso de la base de trabajo)
- **Veredicto:** **APROBADO (`estado=cerrado`, `verifica=Gemini`)**

---

## 1. Inspección del código y de todas las puertas de reactivación

### 1.1. Lectura de los cuerpos implementados

Se leyeron completos los cuerpos de los métodos y cambios introducidos en `cbc851e`:

- [`PagoCuotaService::crearCuotaReingreso(Alumno $alumno): ?DeudaCuota`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php#L489-L519):
  - Usa `Carbon::now()` (respetando `America/Argentina/Buenos_Aires` y el reloj simulado) y toma `$periodo = $hoy->format('Y-m')`.
  - Si el alumno tiene `fecha_alta` en un mes posterior al actual (`$alumno->fecha_alta->gt($hoy->copy()->endOfMonth())`), devuelve `null` sin crear deuda del mes en curso.
  - Si ya existe una fila en `deuda_cuotas` para `(alumno_id, periodo)` (consultada con `lockForUpdate()`), devuelve `null` sin crear duplicados ni tocar la existente.
  - Busca el plan vigente para ese período con `obtenerPlanParaPeriodo($alumno->id, $periodo)`. Si no tiene plan aplicable, lanza excepción en castellano (`El alumno no tiene un plan para este mes. Asigná un plan antes de reactivarlo.`).
  - Calcula el porcentaje con `reglaDelDia($hoy->day, $periodo)` y el importe con `aplicarPorcentaje((float) $alumnoPlan->plan->precio_mensual, $porcentaje)`.
  - Crea la `DeudaCuota` en estado `PENDIENTE` con `monto_original => $monto`, `monto_pagado => 0`, `porcentaje_alta => $porcentaje` y `fecha_reingreso => $hoy->toDateString()`.
- [`PagoCuotaService::completarReingresoPorCobro(Alumno $alumno, bool $cuotaDelMesExistia, float $porcentaje, ?string $periodoConDescuento): void`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php#L529-L551):
  - Llamado desde [`registrarPagoCuotaOperativo`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php#L110-L114) y [`registrarPagoCuotaAdmin`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php#L243-L247) cuando el alumno estaba inactivo antes del cobro (`if (!$alumno->activo)`), habiendo tomado previamente `$cuotaDelMesExistia` bajo `lockForUpdate()`.
  - Resuelve cualquier revisión pendiente del mes actual con `AlumnoRevisionCobranza::reactivarAuto($alumno->id, $periodo)`.
  - Si la cuota del mes ya existía antes del cobro (`$cuotaDelMesExistia === true`), no toca nada.
  - Si no existía antes del cobro:
    - Cuando el cobro **no** creó la cuota del mes actual (por ejemplo, pagó solo deuda vieja) y el alumno tiene plan aplicable al mes actual, llama a `$this->crearCuotaReingreso($alumno)`.
    - Cuando el cobro **sí** auto-creó la cuota del mes actual en `asegurarDeudasExisten`, actualiza esa cuota con `fecha_reingreso => now()->toDateString()` y `porcentaje_alta => $deuda->porcentaje_alta ?? ($periodoConDescuento === $periodo ? $porcentaje : 100.0)` para que su saldo pendiente conserve el plazo de reingreso.
- [`PagoCuotaService::calcularReglaPrimerPago(int $alumnoId, array $items): array`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PagoCuotaService.php#L837-L897):
  - En el **Caso 1** (`!$tienePagos && $alumno->fecha_alta`), la lectura previa de `porcentaje_alta` filtra ahora `->whereNull('fecha_reingreso')` (línea 859) para no confundir el porcentaje de una cuota de reingreso con el metadato del alta original.
  - Además, en las líneas 872–874 y 885–887, si la cuota del período ya tiene `porcentaje_alta` no nulo (porque ya fue creada al reactivar con su porcentaje de reingreso), devuelve `[100.0, null, null]` para que el cobro no vuelva a descontarla ni la recalcule con el día del alta.
- [`AlumnoWebController::toggleActivo(Request $request, int $id)`](file:///c:/xampp/htdocs/gestion-wings/app/Http/Controllers/AlumnoWebController.php#L337-L375):
  - Ejecuta dentro de `DB::transaction` el bloqueo de la fila (`Alumno::whereKey($id)->lockForUpdate()->firstOrFail()`), invierte `activo`, y cuando pasa a `true` llama a `crearCuotaReingreso($alumno)` y `AlumnoRevisionCobranza::reactivarAuto($alumno->id, now()->format('Y-m'))`.
  - Si `crearCuotaReingreso` lanza excepción (alumno sin plan para el mes), la transacción hace rollback completo («todo o nada»: el alumno sigue inactivo y no se crea cuota) y responde con `422` en JSON o redirección con `error` en sesión.
  - Cuando genera cuota, agrega al mensaje: `Se generó la cuota de <mes>: $<importe> (<porcentaje> % por reingreso).` (omitiendo el paréntesis cuando el porcentaje es `100 %`).
- [`CobranzaEstadoService::limiteDeLaCuota(Alumno $alumno, DeudaCuota $deuda): ?Carbon`](file:///c:/xampp/htdocs/gestion-wings/app/Services/CobranzaEstadoService.php#L752-L759) y [`calcularEstadoDesdeDeudas`](file:///c:/xampp/htdocs/gestion-wings/app/Services/CobranzaEstadoService.php#L651-L691):
  - Si `$deuda->fecha_reingreso` no es nula, calcula el límite con `limiteVencimientoAlta(Carbon::parse($deuda->fecha_reingreso), $diasGracia, $diasPlazoAlta)`: toma el mayor entre `fecha_reingreso->startOfDay()->addDays($diasPlazoAlta)` y `fecha_reingreso->startOfMonth()->addDays($diasGracia)`.
  - Si `$deuda->fecha_reingreso` es nula y el período coincide con `$alumno->fecha_alta?->format('Y-m')`, usa la fecha de alta; en cualquier otra cuota devuelve `null` (rige el calendario general del mes).
  - En `calcularEstadoDesdeDeudas`, cuando una cuota tiene límite propio (`$limiteAlta`), mientras `$fechaReferencia->lt($limiteAlta)` cuenta como `$tieneDeudaEnPlazo = true` (incluso si el plazo cruzó al mes siguiente); una vez alcanzado `$limiteAlta`, si la cuota es del mes actual pasa a `$tieneDeudaVencidaMesActual = true` (`MOROSO`) y si es de un mes anterior pasa a `$tieneDeudaMesAnterior = true` (`DEUDOR`). En Cobranza agrega el indicador `Reingreso` cuando la cuota con `fecha_reingreso` está dentro de su plazo.
- [`PunitorioService::aplicar()`](file:///c:/xampp/htdocs/gestion-wings/app/Services/PunitorioService.php#L53-L55):
  - Reemplaza el chequeo exclusivo de mes de alta por `$limite = app(CobranzaEstadoService::class)->limiteDeLaCuota($alumno, $deuda); if ($limite && $hoy->lt($limite)) return 0;`. Así, mientras la cuota del reingreso está dentro de su plazo, el motor diario de mora no le aplica recargo; vencido el plazo (y pasado el día de mora), calcula el porcentaje sobre `$deuda->monto_original` (el importe ya descontado de la cuota de reingreso).

### 1.2. Barrido de todas las puertas donde un alumno puede pasar de inactivo a activo

Se buscó con `codebase-memory` (`search_code` / `search_graph`) todas las referencias a `activo` y `Alumno` en `app/` y `routes/`:

| Puerta / Método | Ruta / Consumidor | ¿Pasa de `inactivo` a `activo`? | ¿Tiene la regla de T38? |
|---|---|---|---|
| `AlumnoWebController::toggleActivo` (`app/Http/Controllers/AlumnoWebController.php:337`) | `PATCH /alumnos/{id}/toggle-activo` (`web.alumnos.toggle-activo`), usado tanto por el botón **Reactivar** de la ficha como por el interruptor del listado | **Sí** | **Sí** (`crearCuotaReingreso` + `reactivarAuto` en transacción) |
| `PagoCuotaService::registrarPagoCuotaOperativo` (`app/Services/PagoCuotaService.php:111`) | `POST /caja/cobrar/{alumnoId}` (`CajaWebController::pagar` cuando cobra `OPERATIVO`) | **Sí** | **Sí** (`completarReingresoPorCobro`) |
| `PagoCuotaService::registrarPagoCuotaAdmin` (`app/Services/PagoCuotaService.php:244`) | `POST /caja/cobrar/{alumnoId}` (`CajaWebController::pagar` cuando cobra `ADMIN`) | **Sí** | **Sí** (`completarReingresoPorCobro`) |
| `AlumnoWebController::update` (`app/Http/Controllers/AlumnoWebController.php:404`) | `PUT /alumnos/{id}` (`web.alumnos.update`) | **No** (`validationRules()` en líneas 465–497 no incluye `'activo'`, por lo que `$validated` nunca modifica `activo`) | N/A |
| `RevisionCobranzaService::resolver` (`app/Services/RevisionCobranzaService.php:27`) | `POST /revision-cobranza/{id}/resolver` (`web.revision-cobranza.resolver`) | **No** (`RESOLUCION_CONTINUA` en líneas 44–53 solo llama a `crearDeudaSiNoExiste` y no modifica `$revision->alumno->activo`; solo `RESOLUCION_INACTIVO` pone `activo => false`) | N/A |
| `Admin\AlumnoController::update` (`app/Http/Controllers/Admin/AlumnoController.php:76`) + `UpdateAlumnoRequest` (`app/Http/Requests/Admin/UpdateAlumnoRequest.php:39`) | `PUT /api/admin/alumnos/{id}` en `routes/api.php` | Permite `'activo' => 'boolean'`, pero **la API está apagada** en `bootstrap/app.php:17` (`routes/api.php` deshabilitado) | Inalcanzable por ruta (`AGENTS.md §6c.2`) |

**Conclusión sobre puertas de entrada (verificado):** Ninguna puerta alcanzable de la aplicación quedó sin la regla de T38.

---

## 2. Verificación independiente de casos sobre `wings_testing_gemini`

Además de correr las 18 pruebas de `ReingresoCuotaT38Test.php`, se ejecutó una comprobación independiente sobre `wings_testing_gemini` con el plan de `$10.000,00` y las reglas de `CatalogosSeeder` (días 1–15 al `100 %`, 16–23 al `70 %`, 24–31 al `40 %`; `dias_gracia_cobranza = 10`, `dias_plazo_alta = 7`, `mora_dia = 10`, `mora_porcentaje = 5`):

### 2.1. Los importes

| Caso probado | Día de reactivación | Reglas activas para ese día | `monto_original` | `porcentaje_alta` | `fecha_reingreso` | Resultado |
|---|---|---|---|---|---|---|
| Tramo 16 al 23 (borde inferior, medio y borde superior) | `16/10`, `20/10`, `23/10` | 1 regla (`70 %`) | `7000.00` | `70.00` | `2026-10-16` / `20` / `23` | **PASA** |
| Tramo 24 al 31 (borde inferior, medio y borde superior) | `24/10`, `28/10`, `31/10` | 1 regla (`40 %`) | `4000.00` | `40.00` | `2026-10-24` / `28` / `31` | **PASA** |
| Día sin regla (regla 16–23 desactivada) | `20/10` | 0 reglas | `10000.00` | `100.00` | `2026-10-20` | **PASA** |
| Día con más de una regla (segunda regla superpuesta 18–22 al `50 %`) | `20/10` | 2 reglas | `10000.00` | `100.00` | `2026-10-20` | **PASA** |

### 2.2. El plazo

- **Reactivado el 20/10 (`fecha_reingreso = 2026-10-20`):**
  - El límite calculado por `limiteDeLaCuota` es `max(2026-10-20 + 7 días = 2026-10-27 00:00:00, 2026-10-01 + 10 días = 2026-10-11 00:00:00)` = `2026-10-27 00:00:00`.
  - Desde el `2026-10-20 10:00:00` hasta el `2026-10-26 23:59:59`: estado **`EN_PLAZO`**.
  - A partir del `2026-10-27 00:00:00`: estado **`MOROSO`** (porque `2026-10` es el mes en curso y el plazo venció).
- **Reactivado el 28/10 (`fecha_reingreso = 2026-10-28`):**
  - El límite calculado por `limiteDeLaCuota` es `2026-10-28 + 7 días = 2026-11-04 00:00:00`.
  - El `28/10`, el `31/10` y hasta el `03/11 23:59:59` (ya en noviembre): estado **`EN_PLAZO`**.
  - A partir del `04/11 00:00:00`: estado **`DEUDOR`** (porque la deuda corresponde a `2026-10`, que en noviembre ya es un mes cerrado, y su plazo de 7 días venció).
- **Las otras cuotas del alumno no cambian:**
  - Un alumno inactivo que debía `2026-09` (`$10.000,00`, `fecha_reingreso = null`, `limiteDeLaCuota = null`) y es reactivado el `20/10` recibe la cuota de `2026-10` con límite `2026-10-27 00:00:00`, pero su estado general en `CobranzaEstadoService::estadoAlumno` sigue siendo **`DEUDOR`** porque la cuota impaga de septiembre mantiene su propio vencimiento.

### 2.3. El recargo por mora

- Con `mora_porcentaje = 5` y alumno reactivado el `20/10` (cuota de octubre de `$7.000,00`, límite `2026-10-27 00:00:00`):
  - Al correr `PunitorioService::aplicar()` el `21/10` y el `26/10`: **no aplica recargo** a la cuota del reingreso (`CargoAlumno` inexistente), mientras que a una cuota común de octubre sin `fecha_reingreso` sí se lo aplica el `21/10`.
  - Al correr `PunitorioService::aplicar()` el `27/10`: **sí aplica recargo** por `$350,00` (`5 %` sobre los `$7.000,00` de `monto_original` de la cuota de reingreso).

### 2.4. El cobro a un alumno inactivo y comparación con «Sin verificar o fuera de alcance»

Se probaron los tres casos pedidos y se contrastaron con lo que declara el informe de Claude en «Sin verificar o fuera de alcance» (líneas 98–100: *«Cobro a un inactivo que nunca pagó: sigue la regla del alumno nuevo (§4, caso 1), no la del reingreso: su descuento es el del mes de alta. El botón Reactivar, en cambio, siempre usa el porcentaje del día. Lo dejé como estaba porque el código lo documenta como decisión.»*):

1. **Paga solo deuda vieja (`2026-09`):**
   - Al cobrarle `2026-09` el `20/10` a un inactivo con pagos previos, el alumno queda `activo = true`, `2026-09` queda `PAGADA` y `completarReingresoPorCobro` detecta que `2026-10` no fue creada por el pago y llama a `crearCuotaReingreso`, generando `2026-10` por **`$7.000,00` (`70 %`, `fecha_reingreso = 2026-10-20`, `monto_pagado = 0.00`)**.
2. **Paga parte del mes (`2026-10`):**
   - Al cobrarle `$2.500,00` a cuenta de `2026-10` el `20/10` a un inactivo con pagos previos, `calcularReglaPrimerPago` entra por el Caso 2 (`!$alumno->activo && $tienePagos`), aplica el `70 %` del día (`monto_original = 7000.00`, `monto_pagado = 2500.00`), y `completarReingresoPorCobro` completa `porcentaje_alta = 70.00` y `fecha_reingreso = 2026-10-20`. El saldo de `$4.500,00` queda **`EN_PLAZO`** hasta el `26/10` y pasa a **`MOROSO`** el `27/10`.
3. **Nunca había pagado (`$tienePagos = false`):**
   - Se comprobaron las tres variantes posibles en el código:
     - **Variante 3.a (nunca pagó y en el cobro paga solo deuda vieja, p. ej. `2026-09`):** como el pago no incluye `2026-10`, `completarReingresoPorCobro` no encuentra fila de `2026-10` y llama a `crearCuotaReingreso($alumno)`, que **sí** crea la cuota de `2026-10` con el porcentaje de reingreso del día (`$7.000,00`, `70 %`, `fecha_reingreso = 2026-10-20`).
     - **Variante 3.b (nunca pagó, tiene `fecha_alta` de un mes anterior `2026-03-01` y en el cobro paga `2026-10` directo sin haber usado antes el botón Reactivar):** `calcularReglaPrimerPago` entra por el Caso 1 (`!$tienePagos && $alumno->fecha_alta`), ve que `periodoAlta` (`2026-03`) no está en `$periodosCobrados` (`['2026-10']`) y devuelve `100 %`; el cobro crea `2026-10` a **`$10.000,00` (`porcentaje_alta = 100.00`, `fecha_reingreso = 2026-10-20`)**.
     - **Variante 3.c (nunca pagó, tiene `fecha_alta` de este mismo mes `2026-10-17` sin cuota creada, y el `24/10` le cobran `2026-10` directo sin haber tocado antes el botón Reactivar):** `calcularReglaPrimerPago` entra por el Caso 1 y calcula el descuento con `$alumno->fecha_alta->day` (`17/10` → **`70 %`, `$7.000,00`**) en vez del día del cobro (`24/10` → `40 %`), y `completarReingresoPorCobro` le guarda `porcentaje_alta = 70.00` y `fecha_reingreso = 2026-10-24`.
   - **Comparación con el informe de Claude:** Lo que dice Claude en «Sin verificar o fuera de alcance» es **exacto** para las variantes 3.b y 3.c (cuando el cobro de un inactivo sin pagos previos incluye y crea en ese acto la cuota del mes en curso, `calcularReglaPrimerPago` aplica el Caso 1 del contrato §4); y en la variante 3.a (cuando paga solo deuda vieja), `completarReingresoPorCobro` delega en `crearCuotaReingreso`, aplicando el porcentaje del día de reingreso.

### 2.5. El caso del mes del alta (alta el 17 y reactivado el 24)

- Un alumno dado de alta el `2026-10-17` (tramo del `70 %`), dado de baja sin cuota de octubre, y reactivado el `2026-10-24` (tramo del `40 %`) mediante `crearCuotaReingreso` (botón **Reactivar** o interruptor):
  - Al reactivarse el `24/10`, nace la cuota `2026-10` con `monto_original = 4000.00`, `porcentaje_alta = 40.00` y `fecha_reingreso = 2026-10-24`.
  - Al cobrarle después esa cuota de `2026-10` (incluso sin pagos previos), `calcularReglaPrimerPago` ignora `porcentaje_alta` en la línea 859 por tener `fecha_reingreso` no nula y corta en la línea 872 devolviendo `[100.0, null, null]` (`$periodoConDescuento = null`), por lo que el cobro **respeta los `$4.000,00` (`40 %`) del reingreso** y no la recalcula al `70 %` del día 17.

---

## 3. Verificación en navegador (Chrome) sobre `wings_testing_gemini`

Se levantó el servidor local de Wings apuntando exclusivamente a `wings_testing_gemini` y se recorrieron las pantallas con Chrome Headless CDP a **`1366 px`** de escritorio y **`360 px`** de celular dentro de un marco `<iframe>` de `360 px` de ancho real (con captura previa de `/login` como control del método, `scrollWidth = 345 <= 360`).

Todas las capturas y el JSON de telemetría están guardados en [`docs/06-pruebas/PRU-04/evidencia-t38-gemini/`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/resultado-navegador.json):

| Pantalla / Paso | Escritorio (`1366 px`) | Celular (`360 px` en marco `<iframe>`) | Qué se comprobó |
|---|---|---|---|
| Control del método (`/login`) | — | [`00-login-control-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/00-login-control-360.png) | La tarjeta de ingreso entra completa sin recortes (`innerWidth = 360`, `scrollWidth = 345`). |
| Ficha de Ana antes de reactivar (`/alumnos/{id}`) | [`01-ficha-inactivo-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/01-ficha-inactivo-1366.png) | [`02-ficha-inactivo-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/02-ficha-inactivo-360.png) | Badge `Inactivo`, botón `Reactivar` con `data-confirmar="¿Reactivar a Ana Reingreso?"` y deuda de `Sep 2026` por `$10,000.00`. Sin desborde (`scrollWidth = 345`). |
| Ficha de Ana tras confirmar `Reactivar` | [`03-ficha-reactivado-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/03-ficha-reactivado-1366.png) | [`04-ficha-reactivado-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/04-ficha-reactivado-360.png) | Diálogo nativo disparado (`¿Reactivar a Ana Reingreso?`), banner verde `Alumno activado correctamente. Se generó la cuota de octubre: $7,000.00 (70 % por reingreso).`, badge `Activo`, deuda de `Sep 2026` (`$10,000.00`) conservada y nueva cuota `Oct 2026` (`$7,000.00`). Sin desborde (`scrollWidth = 345`). |
| Listado de inactivos (`/alumnos?activo=0`) e interruptor de Bruno | [`05-listado-inactivo-bruno-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/05-listado-inactivo-bruno-1366.png) / [`07-listado-reactivado-bruno-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/07-listado-reactivado-bruno-1366.png) | [`06-listado-inactivo-bruno-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/06-listado-inactivo-bruno-360.png) | Al accionar el interruptor `.ds-toggle__input` en la tarjeta de Bruno, pasa a `Activo` por `PATCH` JSON y genera su cuota de octubre por `$7.000,00`. Sin desborde (`scrollWidth = 345`). |
| Cobranza filtrada por `Deudor` (`/cobranza?estado=DEUDOR`) | [`08-cobranza-deudor-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/08-cobranza-deudor-1366.png) | [`09-cobranza-deudor-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/09-cobranza-deudor-360.png) | Ana figura como `Deudor` con `Sep. 2026 (2 cuotas)` y deuda total `$17,000.00` (`$10.000` de septiembre + `$7.000` de octubre). Sin desborde (`scrollWidth = 345`). |
| Cobranza filtrada por `En plazo` (`/cobranza?estado=EN_PLAZO`) | [`10-cobranza-en-plazo-1366.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/10-cobranza-en-plazo-1366.png) | [`11-cobranza-en-plazo-360.png`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-t38-gemini/11-cobranza-en-plazo-360.png) | Bruno figura como `En plazo` con `Oct. 2026 (1 cuota)` por `$7,000.00`; tarjetas superiores muestran `$24,000.00` total adeudado, `2` activos, `1` en plazo y `1` deudor. Sin desborde (`scrollWidth = 345`). |

---

## 4. Revisión de las 18 pruebas de `ReingresoCuotaT38Test.php`, fallo inicial en T36 y suite completa

### 4.1. Qué comprueban las 18 pruebas de `tests/Feature/ReingresoCuotaT38Test.php`

Se leyeron los cuerpos completos de los 18 métodos en [`tests/Feature/ReingresoCuotaT38Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/ReingresoCuotaT38Test.php#L48-L305):

1. `test_reactivar_genera_la_cuota_del_mes_con_el_porcentaje_del_dia_y_conserva_la_deuda_vieja`: verifica redirección, mensaje flash (`Se generó la cuota de octubre` + `70 % por reingreso`), `monto_original = 7000.00`, `porcentaje_alta = 70.00`, `fecha_reingreso = 2026-10-20` y que la deuda de septiembre (`10000.00`) quede intacta y con `fecha_reingreso = null`.
2. `test_lo_que_ve_la_operativa_despues_de_reactivar`: sigue la redirección hasta el HTML de la ficha verificando el aviso visible y consulta `GET /cobranza?estado=EN_PLAZO` verificando que se vea `Reingreso`.
3. `test_el_interruptor_del_listado_tambien_la_genera`: verifica `patchJson` a `toggle-activo` y que cree la cuota de `$7.000,00`.
4. `test_si_ya_tiene_la_cuota_del_mes_no_se_genera_otra_ni_se_toca`: verifica que si ya existía `2026-10` por `$10.000,00`, no cree otra ni modifique `monto_original`, `fecha_reingreso` ni `porcentaje_alta`.
5. `test_dar_de_baja_y_volver_a_reactivar_en_el_mismo_mes_no_duplica`: reactiva el 20/10 (`$7.000,00`), da de baja y vuelve a reactivar el 28/10; comprueba que siga habiendo una sola cuota de octubre con los valores del 20/10.
6. `test_dar_de_baja_no_genera_nada`: pasar de activo a inactivo no crea filas en `deuda_cuotas`.
7. `test_sin_plan_para_el_mes_no_se_reactiva_y_avisa`: sin plan, ni el `PATCH` web ni el `PATCH` JSON (`422`) activan al alumno ni crean cuota.
8. `test_si_todavia_no_empezo_reactivar_no_genera_la_cuota_de_este_mes`: con `fecha_alta = 2026-11-05` y reloj en octubre, reactivar no crea cuota de octubre.
9. `test_sin_una_unica_regla_para_el_dia_va_al_100`: borrando la regla de los días 16–23, reactivar el 20/10 crea la cuota al `100 %` (`$10.000,00`) y el aviso no menciona `% por reingreso`.
10. `test_reactivar_responde_la_revision_pendiente_del_mes`: una revisión pendiente de `2026-10` pasa a `RESUELTO` con `REACTIVADO_AUTO`.
11. `test_la_cuota_del_reingreso_esta_en_plazo_siete_dias_y_despues_vence`: reactivado el 20/10, comprueba `EN_PLAZO` el 20 y el 26, `MOROSO` el 27, y consistencia con `estadosParaAlumnos`.
12. `test_reactivado_a_fin_de_mes_el_plazo_cruza_al_mes_siguiente`: reactivado el 28/10 (`$4.000,00`), comprueba `EN_PLAZO` el 03/11 y `DEUDOR` el 04/11.
13. `test_las_demas_cuotas_del_alumno_siguen_con_su_vencimiento`: con septiembre impago, reactivar el 20/10 deja al alumno `DEUDOR`.
14. `test_el_recargo_por_mora_espera_el_plazo_del_reingreso`: con `mora_porcentaje = 5`, comprueba que el 21/10 se recargue la cuota control sin `fecha_reingreso` pero no la de reingreso; que el 26/10 siga sin recargo; y que el 27/10 reciba `$350,00` (`5 %` de `$7.000,00`).
15. `test_cobrar_solo_deuda_vieja_a_un_inactivo_genera_la_cuota_del_mes`: cobrar septiembre a un inactivo con pagos previos lo activa y le crea octubre a `$7.000,00` con `fecha_reingreso = 2026-10-20`.
16. `test_cobrar_parte_del_mes_a_un_inactivo_deja_el_saldo_con_plazo`: cobrar septiembre + `$3.000,00` de octubre a un inactivo con pagos previos deja octubre con `monto_original = 7000.00`, `monto_pagado = 3000.00`, `porcentaje_alta = 70.00`, `fecha_reingreso = 2026-10-20` y estado `EN_PLAZO` el 25/10.
17. `test_cobrar_a_un_activo_no_cambia`: cobrar septiembre a un alumno que ya estaba activo no le crea la cuota de octubre.
18. `test_el_cobro_no_recalcula_la_cuota_del_reingreso_en_el_mes_del_alta`: alta el 17/10, baja y reactivación el 24/10 (`$4.000,00`); al cobrarle `$4.000,00` de octubre, queda `PAGADA` en `$4.000,00` sin recalcularse al `70 %` del día 17.

### 4.2. Sobre el fallo inicial de una prueba de T38 mencionado por Codex en T36

Se leyó [`docs/06-pruebas/PRU-04/IMPLEMENTACION-T36.md`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/IMPLEMENTACION-T36.md#L73-L82). En la corrida completa de Codex, `ReingresoCuotaT38Test::test_el_cobro_no_recalcula_la_cuota_del_reingreso_en_el_mes_del_alta` falló porque esperaba `4000.00` y obtuvo `7000.00`.

- **Causa verificada:** la causa estuvo en la **carpeta de trabajo compartida durante la edición en paralelo**, antes de que Claude terminara y commiteara `cbc851e`. Mientras Claude probaba la mutación/ajuste del Caso 1 en `PagoCuotaService::calcularReglaPrimerPago` (líneas 858–874, que él mismo documenta en `IMPLEMENTACION-T38.md:65`: *«Rompí a propósito cada pieza (plazo en estados, mora, cobro y el ajuste del caso 1). En cada caso alguna prueba falló. Después restauré el código»*), la corrida de Codex leyó ese estado intermedio sin el filtro `->whereNull('fecha_reingreso')` / guarda de `porcentaje_alta`, haciendo que `calcularReglaPrimerPago` recalculara la cuota del 24/10 (`40 %` = `4000.00`) con el día de alta 17/10 (`70 %` = `7000.00`).
- Una vez publicado `cbc851e`, tanto la repetición de Codex (`IMPLEMENTACION-T36.md:79-80`) como nuestras corridas sobre `wings_testing_gemini` pasaron en verde.

### 4.3. Resultados de `php artisan test` en `wings_testing_gemini`

- **Archivo `tests/Feature/ReingresoCuotaT38Test.php`:** **18 aprobadas**, 0 fallidas (`78 aserciones`, `32,37 s`).
- **Suite completa (`$env:DB_DATABASE='wings_testing_gemini'; php artisan test`):** **821 aprobadas, 2 omitidas, 0 fallidas** (`7.593 aserciones`, `852,91 s`).

---

## 5. Separación explícita entre lo verificado y lo inferido (`AGENTS.md §6c.3`)

- **Verificado directamente por Gemini CAB:**
  - Código de `PagoCuotaService`, `AlumnoWebController`, `CobranzaEstadoService`, `PunitorioService`, migración `2026_10_17_220000_add_fecha_reingreso_to_deuda_cuotas.php` y contratos actualizados en `cbc851e`.
  - Ausencia de otras puertas activas de reactivación en `app/` y `routes/web.php` (`routes/api.php` verificado apagado en `bootstrap/app.php:17`).
  - Pruebas independientes de importes (días 16, 20, 23, 24, 28, 31, 0 reglas y 2 reglas superpuestas), plazos (reactivado el 20, el 28 y preservación de vencimiento en otras cuotas), recargo por mora (días 21, 26 y 27), cobro a inactivo (solo deuda vieja, parte del mes y las 3 variantes de quien nunca había pagado) y caso del mes del alta (alta el 17 + reingreso el 24) en `wings_testing_gemini`.
  - Recorrido en Chrome local sobre `wings_testing_gemini` con 12 capturas reales (`1366 px` y `360 px` en marco `<iframe>` + control de `/login`).
  - Suite completa en `wings_testing_gemini`: **821 aprobadas, 2 omitidas, 0 fallidas (7.593 aserciones, 852,91 s)**.
- **Inferido / no ejecutado en servidor remoto:**
  - No se desplegó ni se probó en el servidor del sitio de prueba ni en un teléfono físico (el alcance del prompt prohíbe desplegar).
