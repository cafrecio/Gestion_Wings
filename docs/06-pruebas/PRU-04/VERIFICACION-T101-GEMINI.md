# Verificación T101: Gasto que el dueño agrega en el turno de una operativa

> **Tarea:** T101 (CAJ-041 d) · **Fecha:** 01/11/2026 · **Agente:** Gemini (Antigravity) · **Máquina:** CAB · **Firma:** `LOG GEM CAB`.  
> **Resultado del control:** APROBADA (sin hallazgos de gravedad Alta o Media).  
> **Estado en tablero:** `cerrado` · **Hizo:** Claude · **Verifica:** Gemini · **Tiene:** nadie.

---

## 1. Resumen ejecutivo

Se ejecutó el control funcional, de permisos, de concurrencia y de datos sobre la implementación de **T101** realizada por Claude en commit `0805d759`.

El problema original (detectado en el día 45.8 de la simulación, caso `CAJ-041 d`) consistía en que cuando el dueño (`ADMIN`) cargaba un gasto dentro del turno de una operativa (vía **Agregar**, `editarStore`), el movimiento quedaba forzado a nombre de la titular de la caja (`'usuario_id' => $caja->usuario_operativo_id`).

Con el cambio en `CajaWebController::editarStore` (`'usuario_id' => $user->id`) y en `CajaService::registrarMovimientoEnCaja` (`'usuario_id' => $data['usuario_id'] ?? $caja->usuario_operativo_id`):
1. El gasto entra a la caja de la operativa y reduce el efectivo esperado de la caja por el importe exacto.
2. El autor del movimiento queda registrado con el ID del dueño que lo cargó.
3. En la pantalla de **Movimientos** (`/movimientos`), tanto la operativa como el dueño ven el gasto a nombre del dueño en la columna *Registró*.
4. Al validar la caja, en **Cashflow** el asiento se genera con `usuario_admin_id` del dueño, y la pantalla de Cashflow muestra al dueño en la columna *Registró*, cerrando el saldo al centavo.
5. Los datos históricos previos (como el caso 45.8 del sitio de prueba) permanecen intactos (no se ejecutaron migraciones de datos destructivas).

---

## 2. Matriz de casos verificados

| # | Caso | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|
| **1** | Dueño agrega un gasto en el turno de la operativa | Figura a su nombre en Movimientos, también para la operativa; el efectivo esperado baja por el importe | **Comprobado.** Gasto de $3.500 entra en la caja de la operativa con `usuario_id` del dueño. En `/movimientos`, la operativa ve la fila con «Carlos Dueño» en *Registró*. El arqueo de la caja descuenta exactamente $3.500 de `efectivo_esperado`. | **VERDE** |
| **2** | Operativa agrega en su propio turno, por **Agregar** (`editarStore`) y por movimiento de caja (`movimientoStore`) | Sigue a su nombre | **Comprobado.** Dos gastos cargados ($1.200 y $800) quedan con `usuario_id` de la operativa y se listan a su nombre en Movimientos. | **VERDE** |
| **3** | Otra operativa o la profesora, y un pedido directo con otro `usuario_id` | Rechazado con 403, o el autor no cambia (se ignora el spoofing) | **Comprobado.** Otra operativa (`403 Forbidden`), profesora (`403 Forbidden`). Envíos directos con `usuario_id` alterado en el request son ignorados, guardando siempre el usuario autenticado (`Auth::id()`). | **VERDE** |
| **4** | Validar esa caja | En Cashflow, «Registró» dice quién lo cargó (`CashflowIntegracionCajaService:81`) y el saldo da al peso | **Comprobado.** Al validar la caja, `cashflow_movimientos` registra el gasto del dueño con su ID (-$3.000) y el de la operativa con el suyo (-$1.000). En `/cashflow`, la tabla muestra a cada autor respectivo. Saldo de efectivo acumulado da -$4.000,00 exacto. | **VERDE** |
| **5** | Editar, cancelar o corregir ese gasto en la caja; recibo y resumen de cierre | Funciona igual; verificar qué pasa con el autor | **Comprobado.** Al editar (`updateMovimiento`), `usuario_id` original no muta; la auditoría (`MovimientoOperativoCorreccion`) registra quién editó. Cancelar rechaza con 403 en GET y error de sesión en POST (no es pago de alumno). Eliminar registra soft/hard delete con auditoría. Resumen suma egresos al peso. | **VERDE** |

---

## 3. Comprobado e inferido por separado

### Comprobado fehacientemente (ejecutado con pruebas reales contra `wings_testing_gemini`)
1. **Atribución de autoría:** Comprobado que `MovimientoOperativo::usuario_id` recibe el ID del usuario autenticado en `CajaWebController::editarStore` (`app/Http/Controllers/CajaWebController.php:489`).
2. **Visualización en Movimientos:** Comprobado que la vista `resources/views/movimientos/index.blade.php:158` imprime `$mov->usuario?->name`, reflejando a Carlos Dueño cuando la operativa consulta la lista.
3. **Efectivo esperado:** Comprobado que `app/Services/CajaService.php:642` (`arqueoCaja`) totaliza los movimientos operativos de la caja por tipo de medio sin discriminar por usuario, reflejando la salida de efectivo en el cajón de la operativa.
4. **Seguridad y permisos:**
   - Otra operativa intentando acceder o enviar POST a `caja/{id}/editar`: `403 Forbidden` (`CajaWebController.php:470`).
   - Profesora: `403 Forbidden`.
   - Inyección de campo `usuario_id` en el body de la petición HTTP: `CajaWebController.php:489` no lee `usuario_id` de la entrada del usuario sino directamente de `$user->id` (`Auth::user()`), neutralizando cualquier suplantación.
5. **Integración con Cashflow:**
   - `CashflowIntegracionCajaService.php:81` asigna `'usuario_admin_id' => $movimiento->usuario_id ?? $adminId`.
   - `CashflowWebController.php:140` asigna `$quienRegistro = $mo->usuario?->name`.
   - Comprobado que el gasto del dueño aparece en Cashflow con autoría de Carlos Dueño y el saldo de la cuenta Efectivo se debita al peso.
6. **Ciclo de vida (Edición, Corrección, Cancelación, Eliminación):**
   - **Edición:** `CajaService::actualizarMovimientoEnCaja` (`app/Services/CajaService.php:625-631`) actualiza `tipo_caja_id`, `subrubro_id`, `monto`, `fecha` y `observaciones`, pero no toca `usuario_id`. El autor original se conserva intacto.
   - **Auditoría:** `CajaCorreccionService.php:35-39` crea `MovimientoOperativoCorreccion` guardando `usuario_id` y `autor` de quien ejecutó la modificación o eliminación.
   - **Cancelación:** La acción de cancelación de movimientos de caja (`web.caja.movimientos.cancelar`) está reservada exclusivamente a cobros vinculados a un alumno (`pago_id`). Para gastos manuales, el formulario devuelve `403 Forbidden` y el endpoint POST rechaza con aviso: *«Este cobro no tiene registro de pago vinculado. No se puede cancelar automáticamente.»*.
   - **Recibos:** Los gastos manuales en caja no generan comprobantes PDF (los recibos son de cobros de alumnos y liquidaciones a profesores).
   - **Resumen:** `CajaWebController::resumen` totaliza por tipo de medio y rubro sin distinguir autor, mostrando los egresos totales y el neto de la caja con total exactitud.
7. **Suite técnica de control:**
   - 8 pruebas de [`tests/Feature/GastoDuenoEnTurnoT101Test.php`](file:///c:/xampp/htdocs/gestion-wings/tests/Feature/GastoDuenoEnTurnoT101Test.php) pasando en verde.
   - 5 pruebas de verificación integral en [`docs/06-pruebas/PRU-04/evidencia-control-t101/VerificacionT101GeminiTest.php`](file:///c:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-control-t101/VerificacionT101GeminiTest.php) pasando en verde (61 aserciones).

### Inferido
- En el sitio de prueba, el gasto registrado en el día 45.8 conserva a Sandra como autora debido a que fue guardado previo al commit `0805d759`. Esto es intencional y preserva la integridad del historial ya asentado.

---

## 4. Hallazgos

Ninguno de gravedad Bloqueante, Alta o Media.  
El comportamiento es consistente en controladores, vistas, reportes de movimientos y Cashflow.

---

## 5. Propuesta para la Tabla de Pruebas (CAJ-041 d)

En cumplimiento de `AGENTS.md §5-ter`, se propone a Claude actualizar el catálogo:
- **Archivo:** `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/CAJ.json`
- **Caso:** `CAJ-041` inciso `d`
- **Texto actual:** Gasto agregado por dueño figura a nombre de la operativa.
- **Texto propuesto:** Gasto agregado por el dueño en el turno de una operativa ingresa a esa caja modificando el esperado, pero registra y exhibe al dueño como autor tanto en Movimientos como en Cashflow.
