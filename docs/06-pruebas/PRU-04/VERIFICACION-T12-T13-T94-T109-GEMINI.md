# Verificación Manual T12 y T13 (Puesta al día con T94 a T109) — Gemini

**Fecha:** 01/11/2026  
**Entorno:** CAB (`LOG GEM CAB`)  
**Base de pruebas:** `wings_testing_gemini`  
**Veredicto:** **DEVUELTO a Claude** (por Hallazgo Grave en Camino 1: afirmación en el manual sobre un flujo que Wings rechaza y no permite).

---

## 1. Resumen ejecutivo

Se realizó el control integral del manual de usuario tras los cambios introducidos por las tareas T94 a T109, auditando:
1. Las 59 páginas del manual contra las tareas T94 a T109.
2. Los 4 caminos críticos solicitados en el prompt mediante pruebas automatizadas ejecutadas en `wings_testing_gemini` (`tests/Feature/VerificacionManualT94T109GeminiTest.php`).
3. Las 7 capturas nuevas generadas por `scripts/pruebas/capturar-manual-t94-t109.mjs` y el estado de las capturas de las páginas afectadas.
4. El botón `?` por rol y la redirección de `/caja/configuracion`.
5. La clasificación de las 45 citas a mano de `docs/06-pruebas/PRU-04/evidencia-t12-t13/citas-a-mano-t94-t109.txt`.
6. La galería de aprobación `APROBAR-T12-T13-T94-T109.html`.

### Hallazgo Grave detectado (Bloqueante)
- **Ubicación:** `resources/manual/admin/dinero-del-dueno-retiros-aportes.md:30-35`
- **Código en Wings:** `app/Services/CajaService.php:673` y `app/Http/Controllers/CajaWebController.php:490-497`
- **Descripción:** El manual indica que si el dueño saca efectivo físico del cajón de la operativa durante un turno, entra a **Caja** y en la tarjeta del turno toca **Agregar**, seleccionando **Retiros** → **Retiro de dueños**.  
  Si bien `CajaWebController::editar` ofrece el subrubro al usuario administrador (línea 493), al enviar el formulario `POST /caja/{id}/editar`, `CajaWebController::editarStore` delega en `CajaService::registrarMovimientoEnCaja`, el cual invoca `validarSubrubroManual` (línea 580).  
  En `CajaService.php:673`:
  ```php
  if ($subrubro->permitido_para !== 'OPERATIVO') {
      throw new \Exception('El subrubro seleccionado no está permitido para usuarios operativos.');
  }
  ```
  `CajaService` rechaza la operación sin importar si el usuario autenticado es `ADMIN`, arrojando una excepción y cancelando la transacción. **Wings no permite que el dueño registre un retiro en el cajón de la operativa por esta vía**.
- **Impacto:** Grave. Describe un camino de uso documentado para el dueño que el sistema rechaza con error.

---

## 2. Auditoría de los 4 caminos ejecutados

Todos los caminos fueron implementados y ejecutados en `tests/Feature/VerificacionManualT94T109GeminiTest.php` sobre `wings_testing_gemini`. (Corrida: 4 tests, 35 aserciones, OK en 15.61 s). *Nota de Claude CAB, 01/11: la prueba se movió a `docs/06-pruebas/PRU-04/evidencia-t12-t13/`: retrata el defecto como correcto y dejaba `DocumentacionNoMienteTest` en rojo.*

| Camino | Descripción | Resultado | Clasificación | Evidencia |
|---|---|---|---|---|
| **Camino 1** | El dueño carga con *Agregar* lo que saca del cajón y la operativa no puede cargar un retiro. | **FALLA EN WINGS** | **Grave** | Operativa: no ve la opción en el select y un POST directo es rechazado (403/error de validación).<br>Admin: ve la opción en el select, pero al enviar `POST /caja/{id}/editar` con `subrubro_id = 18` ("Retiro de dueños"), `CajaService::validarSubrubroManual` lanza excepción: *"El subrubro seleccionado no está permitido para usuarios operativos."*. |
| **Camino 2** | Anular un cobro del dueño: cuándo aparece el botón y qué pasa con el mes. | **APROBADO** | Comprobado | En `alumnos/show.blade.php:390`, el botón solo aparece para `ADMIN`, en pagos no anulados y que no provengan de una caja (`sinCaja()`). Si el mes ya existía, vuelve a `PENDIENTE` (`monto_pagado = 0`). Si era un adelanto (mes > mes actual), el registro de deuda se elimina por completo (`$deuda->delete()`), deshaciendo el adelanto. |
| **Camino 3** | La inscripción y los recargos no tienen casilla al cobrar. | **APROBADO** | Comprobado | En `/caja/cobrar/{alumno}`, solo las cuotas normales tienen checkboxes (`<input type="checkbox" name="periodos[]">`). `INSCRIPCION` y `PUNITORIO` no tienen casilla y su imputación es automática con el monto entregado respetando el orden de cascada (inscripción -> cuota capital -> punitorio). |
| **Camino 4** | Un ajuste de saldo cambia los totales de Cashflow, pero no el resultado de Reportes. | **APROBADO** | Comprobado | Subrubro "Ajuste de saldo: entrada" (`clasificacion_resultado = 'APORTE'`). En Cashflow suma al total de ingresos del período y al saldo de la cuenta. En `ReporteMensualService::obtener($mes)` no altera el `resultado` operativo (queda en 0) y computa bajo `aportes`. |

---

## 3. Matriz de tareas T94 a T109 contra el manual (59 páginas)

| Tarea | Qué cambió en Wings | Páginas afectadas | Estado en el manual | Comprobado vs Inferido |
|---|---|---|---|---|
| **T94** | Sacar una frecuencia: rechaza, elimina o desactiva; previene frecuencias duplicadas | `admin/administrar-grupos.md` | Actualizado | **Comprobado:** El texto y la captura reflejan la regla de negocio y la validación de no duplicación. |
| **T95** | Tarifa de profesor obligatoria (>0) según forma de pago; si hay cambio programado, ambas son obligatorias | `admin/administrar-profesores.md`, `admin/generar-liquidacion-profesor.md` | Actualizado | **Comprobado:** Paso 3 de `administrar-profesores.md` documenta los mensajes exactos de validación y la exigencia de ambas tarifas. |
| **T96** | Un solo cajón de Efectivo; saldo inicial se carga una única vez; `/caja/configuracion` redirige a `/caja` una vez configurado | `operativo/abrir-caja.md`, `operativo/configurar-mostrador.md`, `admin/administrar-tipos-caja.md`, `admin/inicio-dueno.md` | Actualizado | **Comprobado:** `abrir-caja.md` agrega la sección de aviso cuando Efectivo no fue configurado. Redirección verificada en `AyudaBotonPorRolTest`. |
| **T97** | Anular una transferencia entre cuentas deshace ambos movimientos en Cashflow | `admin/transferir-entre-cuentas.md`, `admin/corregir-movimiento-cashflow.md` | Actualizado | **Comprobado:** Texto actualizado con los pasos para anular transferencias. *(Nota: captura `corregir-movimiento-paso4.png` pendiente por decisión de catálogo de Carlos).* |
| **T98** | Mensajes explicativos al no poder eliminar alumno con movimientos históricos | `operativo/eliminar-alumno.md`, `admin/condonar-deuda-alumno.md` | Actualizado | **Comprobado:** Captura `condonar-deuda-alumno-paso1.png` y texto actualizados con el aviso y alternativas. |
| **T99** | Filas repetidas de planes y grupos sin nombres internos | `admin/administrar-grupos.md`, `operativo/modificar-alumno.md` | Actualizado | **Comprobado:** Documentada la advertencia de duplicados al editar y nombres limpios de presentación. |
| **T100** | Cancelar un cobro revierte el adelanto eliminando la cuota futura | `operativo/cancelar-movimiento-caja.md`, `operativo/cobrar-cuota.md`, `operativo/cobrar-pago-parcial.md`, `admin/corregir-movimiento-caja-validada.md` | Actualizado | **Comprobado (ejecutado en test):** `cancelar-movimiento-caja.md` y `corregir-movimiento-caja-validada.md` reflejan exactamente la reversión del adelanto. |
| **T101** | El gasto del dueño en un turno de caja queda registrado a su nombre; columna "Registró" | `admin/dinero-del-dueno-retiros-aportes.md`, `operativo/registrar-gasto-caja.md`, `operativo/consultar-movimientos.md`, `admin/auditar-detalle-caja.md` | **ERROR EN MANUAL (Hallazgo Grave)** | **Comprobado (ejecutado en test):** La columna "Registró" funciona en vistas, pero el flujo documentado en `dinero-del-dueno-retiros-aportes.md` para retirar efectivo del cajón operativo falla en backend. |
| **T102** | Inactivar nivel y reasignar grupos; control de dependencias | `admin/administrar-niveles.md`, `admin/administrar-grupos.md` | Actualizado | **Comprobado:** Mapeo de rutas y ayuda contextual verificados. |
| **T105 / T109** | Recibos livianos (~225 KB); control de filtros vacíos e ID inexistente | `operativo/descargar-recibo-alumno.md`, `admin/descargar-recibo-liquidacion.md` | Actualizado | **Comprobado:** Documentación y manejo de errores 404 / mensajes amigables verificados. |
| **T106** | Transferencias no suman en totales de ingresos/egresos de Cashflow | `admin/consultar-cashflow.md`, `operativo/consultar-historial-cajas.md` | Actualizado | **Comprobado:** Totales y filtros actualizados en el manual y capturas `consultar-cashflow-paso1.png` y `paso2.png`. |
| **T107** | Concurrencia al cerrar caja: aviso preventivo si la pantalla quedó vieja | `operativo/cerrar-caja.md` | Actualizado | **Comprobado:** Sección de preguntas frecuentes documenta el aviso cuando el turno ya fue cerrado. |
| **T108** | Segunda ficha de alumno en otro deporte no cobra inscripción si ya pagó; opción "No" en Excel | `operativo/alta-alumno.md`, `operativo/cobrar-cuota.md`, `admin/primera-carga-descargar-plantilla.md` | Actualizado | **Comprobado (ejecutado en test):** `alta-alumno.md` y `primera-carga-descargar-plantilla.md` describen el comportamiento real verificado. |

---

## 4. Auditoría de capturas

### Las 7 capturas nuevas
Se revisaron las 7 imágenes generadas con `scripts/pruebas/capturar-manual-t94-t109.mjs`:
1. `operativo/alta-alumno-paso3.png`: Rol Operativo (Sandra Vidal), alta con aviso real de inscripción sin errores de consulta.
2. `operativo/cancelar-movimiento-caja-paso2.png`: Rol Operativo, vista de detalle de caja con botón "Cancelar" resaltado con contorno rojo.
3. `operativo/cancelar-movimiento-caja-paso3.png`: Rol Operativo, panel de confirmación con texto real de cancelación.
4. `admin/condonar-deuda-alumno-paso1.png`: Rol Admin, panel de condonar deuda con textos actualizados.
5. `admin/administrar-grupos-paso1.png`: Rol Admin, formulario de creación con botón "Nuevo" y línea visual.
6. `admin/consultar-cashflow-paso1.png`: Rol Admin, listado con catálogo real de movimientos.
7. `admin/consultar-cashflow-paso2.png`: Rol Admin, sección de totales y saldo por cuenta.

**Comprobaciones de integridad:**
- Las 7 provienen del sistema real levantado en navegador contra base de prueba.
- Cada una corresponde al rol de la sección (3 de operativo, 4 de admin).
- El script CDP solo aplica `outline` (`3px solid ...`) y `outlineOffset`, sin inyectar texto DOM ni alterar maquetas.

### Capturas que permanecen viejas (anteriores a T94-T109)
Las siguientes capturas de las páginas afectadas aún datan de versiones anteriores (29/10/2026 o anteriores) y deben ser actualizadas cuando corresponda:
- `operativo/cobrar-cuota-paso2.png`: Muestra el formulario previo a las modificaciones de T100/T108 (sin reflejar la distribución de importes sin casilla de inscripción/punitorio).
- `admin/dinero-del-dueno-retiros-aportes-paso2.png`: Formulario anterior a los cambios de T101.
- `operativo/consultar-movimientos-paso3.png`: No muestra la nueva columna **Registró** agregada en T101.
- `admin/primera-carga-descargar-plantilla-paso2.png`: Captura previa a la actualización de opciones de inscripción en la plantilla.
- `admin/corregir-movimiento-paso4.png`: Muestra un rubro no presente en el catálogo (tema conocido pendiente de decisión de Carlos).

---

## 5. Auditoría del botón `?` por rol y redirección de `/caja/configuracion`

- **Verificación automatizada:** Se ejecutaron las suites `tests/Feature/AyudaManualT12T13Test.php` y `tests/Feature/AyudaBotonPorRolTest.php` en `wings_testing_gemini` (23 tests, 119 aserciones, todas en verde).
- **Cobertura de roles en T96, T97 y T102:**
  - En `/cashflow` (T96/T97/T106): ADMIN accede y el botón `?` abre `admin/consultar-cashflow`. OPERATIVO y PROFESOR reciben 403.
  - En `/grupos` y `/niveles` (T94/T102): ADMIN accede a la administración con ayuda `admin/administrar-grupos`. OPERATIVO accede a vista de consulta con ayuda `operativo/consultar-grupos-planes`.
- **Comportamiento en `/caja/configuracion` (T96):**
  - Cuando el tipo de caja de mostrador ya está configurado (`tipoCajaMostradorId() !== null`), cualquier petición a `/caja/configuracion` emite una redirección HTTP 302 hacia `/caja` (`route('web.caja.index')`).
  - Al llegar a `/caja`, el dueño ve la pantalla normal de gestión de cajas (listado de turnos/cajas, estado de los cajones y el botón **Abrir** si no hay caja abierta hoy).

---

## 6. Clasificación de las 45 citas a mano (`citas-a-mano-t94-t109.txt`)

Ninguna de las 45 citas a mano oculta un error en las páginas del manual. Todas corresponden a una de las siguientes cuatro categorías técnicas del rastreo de fichas:

### (a) Texto armado por partes o dinámicamente en el código (3 citas)
Cadenas donde intervienen variables o interpolación en tiempo de ejecución:
- `admin-dinero-del-dueno-retiros-aportes.md:28` (`Saldo insuficiente en {$cuenta}`)
- `admin-registrar-movimiento-cashflow.md:27` (`Saldo insuficiente en {$cuenta}`)
- `admin-descargar-recibo-liquidacion.md:27` (Mensaje de error general con interpolaciones)

### (b) Avisos del sistema y validaciones de Laravel (11 citas)
Mensajes estándar generados por el validador de Laravel o el archivo de traducción `lang/es/validation.php`:
- `admin-registrar-movimiento-cashflow.md:28` (`El campo monto debe ser al menos...`)
- `admin-registrar-movimiento-cashflow.md:29` (`El campo subrubro es obligatorio`)
- `operativo-alta-alumno.md:32` (`El campo dni ya ha sido registrado`)
- `operativo-buscar-alumnos.md:29` (`El alumno no existe`)
- `operativo-cancelar-movimiento-caja.md:30` (`El campo motivo es obligatorio`)
- `operativo-cerrar-caja.md:30` (`The cambio retenido field must be less than or equal to...`)
- `operativo-modificar-alumno.md:28` (`El campo motivo fecha alta es obligatorio`)
- `operativo-rectificar-caja.md:29` (`El campo motivo rectificación es obligatorio`)
- `operativo-registrar-gasto-caja.md:31` (`The monto field must be at least...`)
- `operativo-registrar-ingreso-caja.md:30` (`The monto field must be at least...`)
- `operativo-alta-alumno.md:31` (`Debe seleccionar la frecuencia semanal`)

### (c) Citas corridas por desplazamiento de líneas o cambios de archivo (28 citas)
Fragmentos de código o llamadas que existen en el sistema pero cambiaron su número de línea o estructura tras T94-T109:
- `admin-auditar-detalle-caja.md:15` (`CajaWebController.php: abort(403)`)
- `admin-registrar-movimiento-cashflow.md:18` (`permite_descubierto`)
- `admin-revisar-desglose-liquidacion.md:16` (`estaAbierta()`)
- `operativo-abrir-caja.md:29` (`Caja abierta de un día anterior`)
- `operativo-buscar-alumnos.md:15` y `md:19` (rutas y directivas Blade)
- `operativo-cerrar-caja.md:19` (`->diferencia =`)
- `operativo-cobrar-cuota.md:34`, `md:36`, `md:41` (mensajes y excepciones de cobranza)
- `operativo-cobrar-pago-parcial.md:32`
- `operativo-consultar-clases-operativo.md:18`
- `operativo-consultar-cobranza.md:15`, `md:18`, `md:19`, `md:28`
- `operativo-consultar-grupos-planes.md:15`, `md:18`
- `operativo-consultar-historial-cajas.md:15`, `md:29`
- `operativo-consultar-movimientos.md:15`
- `operativo-pasar-alumno-inactivo.md:29`, `md:30`
- `operativo-registrar-gasto-caja.md:17`
- `operativo-registrar-ingreso-caja.md:17`
- `operativo-tomar-lista-mostrador.md:30`, `md:31`, `md:38`

### (d) Citas con múltiples apariciones en el archivo fuente (3 citas)
El script detectó más de una ocurrencia idéntica y no puede enlazarla unívocamente:
- `operativo-cerrar-caja.md:27` (`La caja no está en estado editable`, 3 apariciones)
- `operativo-consultar-historial-cajas.md:18` (`Ingresos`, 2 apariciones)
- `operativo-registrar-ingreso-caja.md:31` (`Movimiento registrado`, 2 apariciones)

---

## 7. Verificación de la Galería `APROBAR-T12-T13-T94-T109.html`

- **Estructura HTML:** Archivo válido y bien formateado, con 14 secciones correspondientes a las páginas modificadas.
- **Rutas de imágenes:** Las 7 etiquetas `<img>` apuntan a `../../../resources/manual/img/...`, ruta relativa que resuelve correctamente hacia las imágenes en disco.
- **Visualización de textos:** Los fragmentos `<pre>` reflejan exactamente los textos presentes en los archivos Markdown de `resources/manual/`.

---

## 8. Veredicto y próximos pasos

Debido al **Hallazgo Grave** detectado en el **Camino 1** (`resources/manual/admin/dinero-del-dueno-retiros-aportes.md:30-35`), donde el manual describe un procedimiento que `CajaService::validarSubrubroManual` rechaza arrojando excepción de permisos (`permitido_para !== 'OPERATIVO'`), las tareas **T12 y T13 quedan en estado DEVUELTO a Claude**.

**Próximo paso para Claude:**
- Alinear el procedimiento entre el código (`CajaService.php` / `CajaWebController.php`) y el manual: o bien permitir que el dueño autenticado pueda registrar `Retiro de dueños` en el turno operativo mediante `CajaService`, o bien rectificar el manual indicando el canal real permitido por Wings para retiros de efectivo.
