# Ficha de fuentes: Consultar el historial de turnos de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-historial-cajas.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/historial`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso y rol** | Disponible para el personal operativo y admin. | `routes/web.php:67`<br>`docs/02-contratos/PERMISOS-ROLES.md` | `Route::get('/caja/historial', [CajaWebController::class, 'historial'])->name('web.caja.historial');` | Si un usuario con rol `PROFESOR` intenta acceder a `/caja/historial`, Wings devuelve inmediatamente `HTTP 403 Forbidden`. |
| **Paso 1: Entrar al Historial** | En **Caja**, tocar botón **Historial**. Despliega listado de movimientos de turnos pasados. | `resources/views/caja/index.blade.php:38`<br>`resources/views/caja/historial.blade.php:2` | `@if(Auth::user()->isOperativo() \|\| Auth::user()->isAdmin())` | Carga la vista con los filtros y la grilla de movimientos consolidados. |
| **Paso 2: Filtrar** | Filtrar por texto (alumno, subrubro u observación), tipo (**Ingresos** o **Egresos**) y rango de fechas. | `resources/views/caja/historial.blade.php:20-40`<br>`app/Http/Controllers/CajaWebController.php:200` | `if ($request->filled('tipo')) { ... }`<br>`if ($request->filled('desde')) { ... }` | Al aplicar filtros sin operaciones, muestra 0 movimientos. Botón **Limpiar** reinicia los criterios. |
| **Paso 3: Analizar resultados** | Muestra totales de ingresos (verde) y egresos (rojo), fecha, medio, usuario y monto final. Botón **Volver**. | `resources/views/caja/historial.blade.php:50-80` | `<div>Ingresos: {{ ... }}</div><div>Egresos: {{ ... }}</div>` | Despliega los totales acumulados de las operaciones filtradas. |
| **Qué hago si: movimientos cancelados** | Movimientos marcados con «· Cancelado» fueron anulados en su turno con motivo y no suman a los balances. | `resources/views/caja/historial.blade.php:75`<br>`app/Models/MovimientoOperativo.php:25` | `if ($m->cancelado)` | Los registros cancelados aparecen con estilo atenuado y badge rojo, excluyéndose de la sumatoria de ingresos activos. |
| **Qué hago si: anulación desde historial** | El historial es exclusivamente de consulta y auditoría: no permite anular ni editar operaciones pasadas. | `resources/views/caja/historial.blade.php:65-95` | La vista no cuenta con botones de cancelación ni edición. | Solo la pantalla de detalle del turno correspondiente (`/caja/{id}/detalle`) admite cancelaciones si la caja está abierta o rechazada. |
