# Ficha de fuentes: Consultar el registro de movimientos (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-movimientos.md`
- **Rol:** Operativo
- **Pantalla:** `/movimientos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso y rol** | Disponible para Operativo y Admin. | `routes/web.php:74`<br>`docs/02-contratos/PERMISOS-ROLES.md` | `Route::middleware(['auth', 'role:ADMIN,OPERATIVO'])` | Si un usuario con rol `PROFESOR` intenta ingresar a `/movimientos`, recibe error `HTTP 403 Forbidden`. |
| **Paso 1: Libro cronológico** | Muestra el registro ordenado cronológicamente de operaciones monetarias. | `resources/views/movimientos/index.blade.php:20, 60`<br>`app/Http/Controllers/MovimientoWebController.php:25` | `MovimientoOperativo::with(['subrubro', 'tipoCaja'])->orderByDesc('fecha')->orderByDesc('id')` | Exhibe la lista paginada de cobros, ingresos y egresos con su fecha, hora, tipo e importe. |
| **Paso 2: Filtros** | Filtros por rango de fechas (**Desde** / **Hasta**), **Tipo** (**Ingreso** / **Egreso**) y **Medio**. Botones **Filtrar** y **Limpiar**. | `resources/views/movimientos/index.blade.php:25-45`<br>`app/Http/Controllers/MovimientoWebController.php:30-45` | `if ($request->filled('tipo')) { $query->where('tipo', ...); }`<br>`if ($request->filled('tipo_caja_id')) { ... }` | Al aplicar filtros sin resultados coincidentes, la grilla se vacía informando 0 movimientos. Al presionar **Limpiar**, restablece el filtro. |
| **Paso 3: Datos de auditoría** | Tabla indica fecha/hora, Ingreso/Egreso, concepto/alumno, medio de pago e importe. | `resources/views/movimientos/index.blade.php:75-95` | `<td>{{ $m->fecha->format('d/m/Y') }}</td>` | Muestra los valores exactos registrados en la auditoría contable. |
| **Inmutabilidad del registro** | El listado de movimientos es un log inmutable de auditoría: no posee botones de edición o cancelación directa. | `resources/views/movimientos/index.blade.php:70-110` | Vista no define formularios de edición ni eliminación directa. | Las correcciones de cobros o gastos se gestionan exclusivamente en el turno de caja abierto o rechazado correspondiente (`/caja/{id}/detalle`). |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `HTTP 403 Forbidden` | `routes/web.php:74`<br>`app/Http/Middleware/CheckRole.php:25` | No | Control de autorización para rol Profesor; la operativa tiene acceso pleno a Movimientos. |
| `No hay movimientos registrados.` | `resources/views/movimientos/index.blade.php:100` | No | Estado vacío visual en pantalla cuando el filtro de fechas o tipo no devuelve operaciones. |
