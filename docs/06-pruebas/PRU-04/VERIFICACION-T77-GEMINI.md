# T77 — Verificación independiente de Gemini (Antigravity)

- **Tarea:** T77 — Cobrar muestra solamente la deuda guardada.
- **Implementador:** Codex CAB (commits `5f618c9c` y `a034d990`).
- **Verificador:** Gemini CAB (`LOG GEM CAB`).
- **Fecha:** 25/10/2026.
- **Veredicto:** **APROBADA** (reglas de negocio, seguridad del servidor, permisos y no interferencia financiera verificadas satisfactoriamente).

---

## 1. Contexto y decisión de Carlos

En la implementación previa, `CajaWebController::cobrar` instanciaba en memoria un objeto `DeudaCuota` para el período corriente (octubre 2026) cuando no existía en la base de datos y lo inyectaba a la colección `$alumno->deudaCuotas`. Esto provocaba que a los 63 alumnos que ingresaron «Al día» en la primera carga se les ofreciera octubre como deuda y se les sumara al «Total pendiente», contradiciendo la definición de negocio de Carlos:
> *«Si no tiene deuda, ¿por qué ofrece cobrar octubre? No se puede cobrar dos veces.»*

Para cobrar un período que el alumno no debe formalmente como deuda devengada, Codex implementó la sección **«Mes sin deuda o cobro adelantado»** en la pantalla de cobro. El aspecto visual fue previamente aprobado por Carlos («Ahora esta Ok», 25/10/2026). Esta verificación audita la lógica financiera, el rechazo a nivel servidor y el impacto en el resto del sistema.

---

## 2. Puntos verificados vs. inferidos

### 2.1. Sin deuda inventada
* **Verificado en código:**
  - En [`app/Http/Controllers/CajaWebController.php`](../../../app/Http/Controllers/CajaWebController.php) (líneas 784–799) se eliminó de raíz el bloque que verificaba si existía la cuota del mes vigente e instanciaba una `DeudaCuota` en memoria para pushearla a la relación.
  - La colección `$alumno->deudaCuotas` se restringe exclusivamente a las cuotas existentes en la base de datos con estado `PENDIENTE` o `AJUSTADA` (y cargos punitorios aplicables).
  - En la vista [`resources/views/caja/cobrar.blade.php`](../../../resources/views/caja/cobrar.blade.php), el «Total pendiente» se calcula sumando el saldo de las cuotas guardadas. Si el alumno está al día y no posee deudas, la lista muestra el aviso «Sin cuotas pendientes.» y el «Total pendiente» es `$0.00`.
* **Verificado en ejecución:**
  - La prueba `test_01_sin_deuda_inventada_en_pantalla_cobrar_y_total_pendiente_cero` en `VerificacionT77GeminiTest` demuestra que al consultar `/caja/cobrar/{id}` para un alumno al día sin cuotas en DB, la lista de cuotas está vacía, no se renderizan checkboxes de deudas corrientes (`name="periodos[]"`), el total visible es `$0.00`, y el snapshot de la base de datos se mantiene 100% idéntico (entrar a la pantalla no genera escrituras).
* **Inferido:** Al no existir una cuota en memoria, es imposible que el formulario estándar envíe accidentalmente un período no adeudado como si fuera una deuda regular impaga.

---

### 2.2. Cobro de un mes sin deuda (sección manual)
* **Verificado en código:**
  - En `CajaWebController::cobrar`, `$mesesAdelantables` ahora itera sobre `range(0, self::MESES_ADELANTABLES)`. El índice 0 calcula el mes corriente (`now()->format('Y-m')`). Si el período no existe en `deuda_cuotas`, se agrega a la lista de opciones con el precio obtenido de `GrupoPlan::precioParaPeriodo()`. Si ya existe una cuota (en cualquier estado), se excluye mediante `reject()`.
  - En [`resources/js/cobrar.js`](../../../resources/js/cobrar.js), al seleccionar el mes actual en el selector y hacer clic en «Agregar», se genera una fila con la insignia «Mes actual» y un campo oculto `periodos_manuales[] = '2026-10'`.
  - En `CajaWebController::pagar`, se valida que los elementos de `periodos_manuales` sean equivalentes al mes actual (`Rule::in([now()->format('Y-m')])`).
* **Verificado en ejecución:**
  - La prueba `test_04_cobro_manual_del_mes_sin_deuda_crea_cuota_pagada_y_pago_sin_duplicaciones` confirma que al procesar el cobro con `periodos_manuales`:
    1. Se crea en `deuda_cuotas` el período 2026-10 directamente con `estado = 'PAGADA'`, `monto_original = 30000` y `monto_pagado = 30000`.
    2. Se crea un registro en `pagos` por $30.000 con su imputación en `pago_deuda_cuota`.
    3. Se genera el movimiento de caja operativo correspondiente por $30.000.
    4. Al volver a consultar la pantalla `/caja/cobrar/{id}`, el mes de octubre desaparece automáticamente del selector de meses adelantables.
* **Inferido:** No se generan cuotas huérfanas ni duplicación con comandos de cierre o regeneración de cuotas gracias a las invariantes de `PagoCuotaService`.

---

### 2.3. Rechazos del servidor y seguridad bajo bloqueo
* **Verificado en código:**
  - En `CajaWebController::pagar` (líneas 1005–1011), dentro de la transacción de base de datos y bajo bloqueo exclusivo del registro del alumno (`lockForUpdate`), el servidor ejecuta una verificación defensiva:
    ```php
    $periodoActual = now()->format('Y-m');
    if (in_array($periodoActual, $request->input('periodos'), true)
        && !$deudas->has($periodoActual)
        && (!in_array($periodoActual, $request->input('periodos_manuales') ?? [], true)
            || !is_numeric($request->input("montos_cuota.{$periodoActual}")))) {
        throw new \RuntimeException('Ese mes no tiene una deuda guardada. Elegilo en el selector de meses e indicá el importe para cobrarlo.');
    }
    ```
  - Si un usuario malintencionado intenta cobrar el mes vigente como deuda enviando `periodos[] = '2026-10'` sin pasar por la selección manual ni indicar importe, la transacción se revierte de inmediato.
  - Si un período ya figura como `PAGADA` o `CONDONADA`, tanto `CajaWebController::pagar` como `PagoCuotaService::aplicarPagoADeudas` bloquean el pago lanzando una excepción que impide cualquier sobrepago o duplicación.
* **Verificado en ejecución:**
  - La prueba `test_05_servidor_rechaza_pagar_como_deuda_el_mes_sin_deuda_sin_seleccion_manual` corrobora que la omisión de `periodos_manuales` devuelve error en sesión («Ese mes no tiene una deuda guardada...») con **cero escrituras** en las tablas de base de datos.
  - La prueba `test_06_servidor_rechaza_cobrar_mes_ya_pagado_incluso_con_payload_manual` corrobora que un intento forzado de cobrar un mes saldado es rechazado sin alterar saldos ni movimientos.

---

### 2.4. Lo que no se rompe (integridad funcional)
* **Deudas reales guardadas:**
  - En `test_07_deudas_reales_siguen_apareciendo_y_cobrandose_normalmente`, un alumno que debe septiembre 2026 ($30.000) ve la fila en la tabla principal con checkbox, suma $30.000 al Total pendiente, y se cobra por la vía ordinaria sin requerir flags manuales.
* **Pagos parciales:**
  - En `test_08_pago_parcial_de_deuda_real_deja_saldo_pendiente`, un pago por $12.000 sobre una deuda de $30.000 actualiza `monto_pagado` a 12.000, mantiene `estado = 'PENDIENTE'` y deja `saldo_pendiente` en $18.000.
* **Cobro adelantado con deuda vieja:**
  - En `test_09_adelantado_con_deuda_vieja_exige_confirmacion_y_motivo`, un alumno con deuda en septiembre que intenta pagar noviembre por adelantado recibe una respuesta HTTP 409 con `requiere_confirmacion: true`. Con la confirmación y el motivo presentes, la transacción se autoriza y registra.
* **Separación de roles (OPERATIVO vs. ADMIN):**
  - En `test_10_admin_cobra_mes_manual_directo_a_cashflow_sin_abrir_caja`, el dueño (`ADMIN`) procesa el mes manual impactando directamente en `cashflow_movimientos` sin requerir caja operativa abierta, cumpliendo el contrato de permisos de `PERMISOS-ROLES.md`.

---

### 2.5. Otras pantallas y servicios del negocio
* **Verificado mediante `codebase-memory` y lectura de código:**
  - Se rastrearon todas las invocaciones a `saldoDeAlumnos`, `listadoCobranza`, `resumenDashboard` y `AlumnoWebController::show`.
  - Ninguna de estas vistas u operaciones dependía de la deuda en memoria que antes generaba `CajaWebController::cobrar`. Todas consultan directamente la persistencia en `deuda_cuotas`.
* **Verificado en ejecución:**
  - La prueba `test_11_cobranza_ficha_e_inicio_no_muestran_deuda_inventada` confirma que:
    1. `CobranzaEstadoService::saldoDeAlumnos` reporta `total = 0.0` y estado `AL_DIA`.
    2. `CobranzaEstadoService::resumenDashboard` reporta `total_adeudado = 0.0`.
    3. `AlumnoWebController::show` (ficha del alumno) no lista cuotas pendientes.
    4. `CobranzaWebController::index` muestra `$0.00` de deuda en el resumen superior.
    5. Los dashboards de Admin y Operativo mantienen sus contadores de deuda y alumnos en plazo en cero.

---

## 3. Pruebas automatizadas ejecutadas

| Archivo de prueba | Pruebas | Aserciones | Resultado |
|---|---|---|---|
| `tests/Feature/CobrarMesSinDeudaT77Test.php` (Codex) | 9 | 43 | **PASS** |
| `docs/06-pruebas/PRU-04/evidencia-verificacion-t77-gemini/VerificacionT77GeminiTest.php` (Gemini) | 11 | 88 | **PASS** |
| **Total dirigidas T77** | **20** | **131** | **PASS (100%)** |

### Regresiones del área de cobro y cambios de plan
- `CambioPlanConDescuentoMatrizTest`: 7 pruebas (PASS).
- `CobrarPrimeraCuotaWebTest`: 4 pruebas (PASS).
- `DescuentoNoAlteraOtroPeriodoTest`: 1 prueba (PASS).
- `DescuentoPrimerPagoMatrizTest`: 10 pruebas (PASS).
- `P2Entrega2FichaCobroAdelantadoTest`: 12 pruebas (PASS).
- `PrecioPlanVigenciaT44Test`: 19 pruebas (PASS).
- `DetalleCajaCanceladoYFichaAltaT78Test`: 3 pruebas (PASS).
- **Subtotal regresiones:** **56 pruebas aprobadas (367 aserciones)** sin fallos.

### Suite rápida del proyecto (`--exclude-group=lento`)
- **922 aprobadas, 6 omitidas, 0 fallidas** (7.397 aserciones, 163,60 s, salida 0) en `wings_testing_gemini`.
- Guardianes de documentación (`DocumentacionNoMienteTest`, `TableroAlDiaTest`) y de seguridad CSP en verde.

---

## 4. Veredicto

**APROBADA.**

La lógica implementada por Codex en T77 resuelve con precisión la decisión de negocio de Carlos:
1. No se inventa deuda en memoria para alumnos al día.
2. El selector manual permite cobrar el mes corriente cuando no existe deuda al precio real del plan.
3. El backend rechaza intentos de cobro fraudulento o duplicado bajo bloqueo exclusivo.
4. No altera el comportamiento de deudas reales, pagos parciales, cashflow del admin ni las métricas de Cobranza y Fichas.
