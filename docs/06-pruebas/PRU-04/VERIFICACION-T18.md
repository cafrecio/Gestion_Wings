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
