# VERIFICACIÓN T18 — Cobro Adelantado

**Resultado: DEVUELTO**

Verificación de la lógica de cobro adelantado (commit `3a3849a` de Claude) realizada por Gemini en base aislada `wings_testing_gemini`.
Suite de comprobaciones independientes ejecutada desde `docs/06-pruebas/PRU-04/evidencia-t18/VerificacionT18Test.php`.

---

## 1. Tabla de comprobaciones

| # | Punto a verificar | Qué se probó | Resultado |
|---|---|---|---|
| **1** | Sin `confirmar_pago_adelantado` no se cobra mes futuro | Intentos por Operativo (JSON), Admin (JSON), POST tradicional HTML directo sin JSON, y con `confirmar_pago_adelantado=0`. | **APROBADO**. Todos responden HTTP 409 con `requiere_confirmacion_adelantado => true`. No se inserta deuda ni pago en la base. |
| **2** | Importe cambiado: mes queda PAGADO con `monto_original` igual a lo cobrado | Cobro de $42.000 para plan de $35.000 (más alto) y cobro de $25.000 para plan de $35.000 (más bajo). | **APROBADO**. En ambos casos la `DeudaCuota` se crea con `monto_original` = `monto_pagado` = lo cobrado, estado `PAGADA` y saldo pendiente $0,00. |
| **3** | Precio congelado frente a aumento y `cobranza:generar-deudas` | Cobro de noviembre adelantado a $38.000 en octubre; aumento del plan a $55.000; simulación de fecha `2026-11-01` y ejecución de `cobranza:generar-deudas`. | **APROBADO**. El mes no se duplica (sigue habiendo 1 solo registro), no cambia de monto ($38.000) y permanece en estado `PAGADA`. |
| **4** | Bordes del período | Mes actual (no exige confirmación); 12 meses hacia adelante (permitido); 13 meses hacia adelante (rechazado 422); mes pasado sin deuda previa (rechazado); formatos inválidos de texto. | **FALLA EN BORDE (Ver Hallazgo 2)**. El regex `^\d{4}-\d{2}$` acepta meses fuera de rango como `2026-13`, provocando excepción fatal no controlada (500) en `Carbon::parse()` en vez de error 422 de validación. |
| **5** | Mes futuro que YA tiene deuda guardada (pendiente, pagada o condonada) | - Ya PAGADA: excluida de selector y rechaza POST si se intenta pagar de nuevo.<br>- Ya CONDONADA: excluida de selector y rechaza POST con error.<br>- Ya PENDIENTE: no aparece en selector adelantado, pero sí en cuotas pendientes con badge «Adelantado». | **OBSERVACIÓN (Ver Hallazgo 1)**. Si la deuda futura está `PENDIENTE` en la base (ej. tras anulación), `CobranzaEstadoService::saldoDeAlumnos()` la computa como deuda activa. |
| **6** | Cruces con otras operaciones | - Adelantado + deuda vieja impaga: exige confirmación de adelantado (409), confirmación de deuda vieja (409) y motivo obligatorio (422).<br>- Adelantado + inscripción pendiente: distribuye correctamente inscripción y cuota adelantada.<br>- Adelantado + descuento primer pago: aplica descuento solo al mes de alta; mes adelantado no sufre descuento indebido.<br>- Adelantado + cambio de plan en el mismo cobro: aplica el nuevo plan correctamente.<br>- Dos meses adelantados juntos: exige confirmación en plural y cobra ambos dejándolos PAGADOS. | **APROBADO**. Todos los cruces se resolvieron coherentemente. |
| **7** | Anular o cancelar cobro adelantado | Admin anula el cobro vía `PagoCuotaService::anularCobroAdmin` (o cancelación en caja). Pago pasa a `ANULADO`, `DeudaCuota` pasa a `monto_pagado=0`, `estado=PENDIENTE`. Se vuelve a cobrar exitosamente. | **FALLA (Ver Hallazgo 1)**. Al quedar como `PENDIENTE`, pasa a considerarse deuda exigible en el buscador y en Cobranza. |
| **8** | Caja, Cashflow, Recibo y Cobranza reflejan el cobro una sola vez | Operativo genera 1 `MovimientoOperativo` en su caja y 1 recibo PDF válido. Admin genera 1 `CashflowMovimiento`. Cobranza registra saldo en 0. | **APROBADO**. No hay duplicación de asientos ni movimientos fantasma. |
| **9** | «Total pendiente» y buscador de Cobrar no cuentan meses futuros como deuda | Comparación entre `/caja/cobrar` (ficha del alumno) y `/caja/cobrar-cuota` (buscador) cuando existe una cuota futura pendiente en base. | **FALLA (Ver Hallazgo 1)**. La ficha muestra `Total pendiente $0,00` pero el buscador muestra `Saldo $35.000` (1 cuota pendiente) y `/cobranza` muestra `deuda_total $35.000`. |

---

## 2. Lo que NO se probó

- Cobro adelantado desde la API REST (se encuentra deshabilitada deliberadamente en `bootstrap/app.php` según AGENTS.md §7). Específicamente escrito: **no lo probé**.
- Cobro en moneda extranjera o cajas no activas: **no lo probé**.
- Generación de PDF de recibo con impresora física: **no lo probé** (se verificó generación en memoria y respuesta HTTP 200 con `Content-Type: application/pdf`).

---

## 3. Detalle de las fallas encontradas (Motivo de devolución)

### Hallazgo 1 (Principal): Un mes futuro pendiente se computa como deuda en el buscador de Cobrar y en Cobranza

**Causa:**
Carlos decidió el 10/10/2026:
> *«Un mes que todavía no empezó no es deuda y no se cuenta.»*
> *«"Total pendiente" y el buscador de Cobrar: que ningún mes futuro cuente como deuda en ninguna pantalla.»*

En el commit `3a3849a`, Claude corrigió el cartel de «Total pendiente» en la ficha de cobro (`resources/views/caja/cobrar.blade.php`), agregando:
```blade
$alumno->deudaCuotas->where('periodo', '<=', now()->format('Y-m'))->sum('saldo_pendiente')
```
Esto funciona bien si el alumno nunca pagó adelantado o si la cuota adelantada está pagada.
Sin embargo, si se anula o cancela un cobro adelantado (Punto 7), o si por cualquier vía existe una fila en `deuda_cuotas` con `periodo > now()->format('Y-m')` y `estado = 'PENDIENTE'`:
1. En `/caja/cobrar` (la ficha del alumno), el encabezado dice **Total pendiente: $0,00**.
2. Pero en `/caja/cobrar-cuota` (el listado / buscador de Cobrar), el cálculo proviene de:
   ```php
   CobranzaEstadoService::saldoDeAlumnos(...)
   ```
   En `app/Services/CobranzaEstadoService.php` (método `saldoDeAlumnos`), se itera sobre todas las deudas impagas del alumno sin filtrar por período vigente:
   ```php
   foreach ($deudasPorAlumno->get($alumno->id, collect()) as $deuda) {
       if (!$this->estaImpaga($deuda)) continue;
       $cuotas += max(0, (float) $deuda->monto_original - ...);
   ```
3. En consecuencia, el buscador `/caja/cobrar-cuota` y la pantalla `/cobranza` muestran al alumno con **Saldo: $35.000** y **1 cuota pendiente**, contradiciendo abiertamente la ficha y violando la regla de que un mes que no empezó no es deuda.

**Solución requerida a Claude:**
- En `CobranzaEstadoService::saldoDeAlumnos()` (y donde corresponda calcular saldos de alumnos para cobro y cobranza), ignorar deudas cuyo período sea posterior al mes actual (`periodo > now()->format('Y-m')`).
- Evaluar además si al anular un cobro adelantado (`anularCobroAdmin` / `cancelarCobroOperativo`), si la cuota fue generada exclusivamente con motivo del pago adelantado y corresponde a un mes futuro, debe eliminarse (`$deuda->delete()`) en lugar de quedar como `PENDIENTE`, o si basta con que los servicios de saldo no la totalicen antes de su inicio de mes.

---

### Hallazgo 2 (Borde): Período mal formado con mes > 12 provoca HTTP 500 no capturado

**Causa:**
En `CajaWebController::pagar()`, la regla de validación para los períodos es:
```php
'periodos.*' => 'required|string|regex:/^\d{4}-\d{2}$/'
```
Si un cliente o atacante envía un valor como `2026-13` o `2026-00`:
- El string cumple la expresión regular `^\d{4}-\d{2}$`.
- Pasa la validación de Laravel sin emitir 422.
- Luego, en la línea 928:
  ```php
  $nombres = $adelantados->map(fn ($periodo) => Carbon::parse($periodo.'-01')->locale('es')->translatedFormat('F Y'));
  ```
  `Carbon::parse('2026-13-01')` lanza una excepción no controlada `Carbon\Exceptions\InvalidFormatException: Could not parse '2026-13-01'`, resultando en un error HTTP 500.

**Solución requerida a Claude:**
- Ajustar la regla regex en `CajaWebController` a:
  ```php
  'regex:/^\d{4}-(?:0[1-9]|1[0-2])$/'
  ```
  para que meses fuera de 01–12 se rechacen limpiamente con error 422 de validación.

---

## 4. Estado de Stash en el repositorio

Respecto al stash presente en el workspace:
`stash@{0}: On main: dashboard local changes`
Se verificó su contenido (`git stash show "stash@{0}"`): corresponde a modificaciones locales exploratorias en `WebController.php`, `app.css` y `dashboard.blade.php` realizadas por Gemini en la sesión previa de trabajo sobre el dashboard.
Tal como instruyó Carlos, **se dejó intacto en el stash, no se aplicó y no se borró**.

---

## 5. Corrida de la suite completa

Ejecución sobre base descartable `wings_testing_gemini`:
```text
  Tests:    2 skipped, 609 passed (5065 assertions)
  Duration: 499.09s
```
Suite de verificación propia `VerificacionT18Test.php`:
```text
OK (13 tests, 104 assertions)
```

---

# SEGUNDA VUELTA — VERIFICACIÓN Y CIERRE (11/10/2026)

**Resultado: APROBADO**

Segunda verificación de T18 tras las correcciones de Claude en el commit `567f030` (`app/Services/CobranzaEstadoService.php`, `app/Http/Controllers/CajaWebController.php`).
Verificación ejecutada por Gemini en base descartable propia `wings_testing_gemini` sin tocar el sitio de prueba ni su reloj simulado.
Suite de comprobaciones independientes ejecutada desde `docs/06-pruebas/PRU-04/evidencia-t18/VerificacionT18Test.php`.

---

## 1. Comprobaciones de las dos fallas devueltas

### Hallazgo 1: Mes futuro guardado y pendiente en la base
Carlos decidió el 10/10/2026:
> *«Si se anula un cobro adelantado, el mes queda guardado y pendiente con el importe pactado. No se borra. Lo que tiene que cumplirse es que ese mes no cuente como deuda en ninguna pantalla hasta que empiece.»*

Claude corrigió `CobranzaEstadoService::estaImpaga()` agregando la condición `&& $deuda->periodo <= now()->format('Y-m')`.

**Comprobaciones realizadas en `VerificacionT18Test::test_09`:**
1. **Mismo saldo en las 5 pantallas ante cuota futura pendiente (importe pactado $42.000 tras anulación):**
   - **Pantalla de cobro del alumno (`/caja/cobrar/{id}`):** Cabecera «Total pendiente» muestra `$0,00`. La fila de cuotas pendientes lista noviembre con badge «Adelantado» e importe $42.000, pero el total no la suma.
   - **Buscador de Cobrar (`/caja/cobrar-cuota`):** El alumno muestra `Saldo: $0` y `0 pendientes`. No muestra $42.000. `saldoDeAlumnos` retorna `total: 0.0` y `cuotas_impagas: 0`.
   - **Cobranza (`/cobranza`):** `resumenDashboard` retorna `total_adeudado: 0.0`. En `listadoCobranza('TODOS')`, el alumno tiene `deuda_total: 0.0` y `cantidad_cuotas_impagas: 0`. Con el filtro por defecto (`DEUDORES`), el alumno no figura en el listado.
   - **Inicio (`/admin/dashboard`):** La tarjeta «Alumnos por cobrar» toma `reporte['deuda']['total'] = 0` (0 centavos). Coincide exactamente con Cobranza.
   - **Reportes (`/reportes`):** «Por cobrar» toma `reporte['deuda']['total'] = 0` y `reporte['deuda']['cuotas'] = 0`.
2. **Estado del alumno:**
   - `CobranzaEstadoService::estadoAlumno()` devuelve `estado: AL_DIA`, con 0 deudas pendientes computables. No es `MOROSO` ni `DEUDOR`.
3. **Cuando el mes empieza (inicio del período):**
   - Se simuló el avance de fecha a `2026-11-02` y un aumento previo del plan a $55.000 (de $35.000).
   - Se ejecutó `cobranza:generar-deudas`. El registro no se duplicó (permanece exactamente 1 cuota para 2026-11).
   - La cuota pasa a computarse como deuda por el **importe pactado de $42.000** y **NO por el nuevo precio del plan ($55.000)** en las 5 pantallas:
     * Ficha `/caja/cobrar`: Total pendiente muestra `$42.000,00`.
     * Buscador `/caja/cobrar-cuota`: `Saldo: $42.000` y `1 pendiente`.
     * Cobranza: `total_adeudado = 42000.0`.
     * Inicio: `deuda['total'] = 4200000` ($42.000).
     * Reportes: `deuda['total'] = 4200000` ($42.000).
   - El estado del alumno pasa a `EN_PLAZO` durante los días de gracia (día 2 <= 10) y a `MOROSO` pasado el plazo (día 15 > 10).
4. **Volver a cobrar tras anulación:**
   - Estando en octubre, ese mismo mes noviembre pendiente se cobra nuevamente como adelantado (`confirmar_pago_adelantado = 1`).
   - El cobro se procesa exitosamente, la cuota pasa a `PAGADA` por $42.000 y **no se duplica** (se mantiene exactamente 1 fila en `deuda_cuotas`).

**Resultado: APROBADO.**

---

### Hallazgo 2: Períodos fuera de 01–12 (`2026-13` y `2026-00`)
Claude ajustó la regla de validación en `CajaWebController::pagar()` a:
`'periodos.*' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']`.

**Comprobaciones realizadas en `VerificacionT18Test::test_10` y `test_04`:**
- POST con `periodos = ['2026-13']` responde **HTTP 422** con error de validación en `periodos.0`. No lanza error 500.
- POST con `periodos = ['2026-00']` responde **HTTP 422** con error de validación en `periodos.0`. No lanza error 500.
- No se inserta ninguna cuota ni pago en la base de datos (0 deudas y 0 pagos creados).
- Período válido (`2026-11`) sigue funcionando: sin confirmación exige confirmación (HTTP 409 con `requiere_confirmacion_adelantado => true`); con confirmación procesa exitosamente (HTTP 302 / éxito en sesión y cuota `PAGADA`).

**Resultado: APROBADO.**

---

## 2. Revalidación de puntos previamente aprobados

Se volvieron a ejecutar todos los casos de la suite de verificación propia (`VerificacionT18Test.php`):

| # | Punto | Resultado |
|---|---|---|
| **1** | Sin confirmación de adelantado rechaza por todos los caminos (Operativo JSON, Admin JSON, POST HTML tradicional, `confirmar_pago_adelantado=0`). | **REVALIDADO / APROBADO** (HTTP 409, 0 escrituras). |
| **2** | Importe adelantado cambiado (más alto $42.000 y más bajo $25.000) fija `monto_original` = `monto_pagado` = lo cobrado con estado `PAGADA`. | **REVALIDADO / APROBADO**. |
| **3** | Precio congelado frente a aumentos y ejecución de `cobranza:generar-deudas` en día 1. | **REVALIDADO / APROBADO** (no se duplica ni reliquida). |
| **6a** | Cruce adelantado + deuda vieja impaga: exige confirmación de adelantado (409), de deuda anterior (409) y motivo obligatorio (422). Con motivo registra observación en pago. | **REVALIDADO / APROBADO**. |
| **6b** | Cruce adelantado + inscripción pendiente: imputa inscripción ($5.000) y cuota adelantada ($35.000). | **REVALIDADO / APROBADO**. |
| **6c** | Cruce adelantado + descuento primer pago: aplica 50% al mes de alta ($17.500) y 100% al adelantado ($35.000). | **REVALIDADO / APROBADO**. |
| **6d** | Cruce adelantado + cambio de plan en el mismo cobro: fija cuota adelantada al precio del nuevo plan ($45.000). | **REVALIDADO / APROBADO**. |
| **6e** | Dos meses adelantados juntos: mensaje en plural y cobro de ambos dejándolos `PAGADOS`. | **REVALIDADO / APROBADO**. |
| **8** | Caja, Cashflow, Recibo y Cobranza reflejan el cobro una sola vez (1 movimiento operativo, 1 recibo PDF válido, 1 CashflowMovimiento admin, Cobranza en 0). | **REVALIDADO / APROBADO**. |

---

## 3. Lo que expresamente NO se probó

- Cobro adelantado desde la API REST (se encuentra deshabilitada deliberadamente en `bootstrap/app.php` según AGENTS.md §7). Específicamente escrito: **no lo probé**.
- Cobro en moneda extranjera o cajas no activas: **no lo probé**.
- Impresión física de recibos en papel con impresora: **no lo probé** (se verificó generación en memoria y respuesta HTTP 200 con `Content-Type: application/pdf`).

---

## 4. Estado de Stash en el repositorio

El stash local preexistente:
`stash@{0}: WIP on main: dfd6d48 Caso 3 TO-BE: auto-creación deuda, FIFO fuerte, subrubro unique, DB dump`
**Se conservó intacto: no se aplicó ni se borró**, respetando la regla estricta de Carlos.

---

## 5. Corridas de suites

- **Suite de verificación T18 (`docs/06-pruebas/PRU-04/evidencia-t18/VerificacionT18Test.php`):**
  ```text
  PASS  Tests\Feature\VerificacionT18Test
  Tests:    14 passed (157 assertions)
  Duration: 15.23s
  ```

- **Suite completa de la aplicación (`DB_DATABASE=wings_testing_gemini php artisan test`):**
  ```text
  Tests:    2 skipped, 614 passed (5096 assertions)
  Duration: 432.39s
  ```
  100% verde sin errores ni advertencias.
