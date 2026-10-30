# Ficha de fuentes: Generar la liquidación mensual de profesores

- **Página del manual:** `resources/manual/admin/generar-liquidacion-profesor.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/liquidaciones/crear`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | El dueño gestiona y genera las liquidaciones mensuales de docentes según su modalidad (Por hora o Por comisión). | `routes/web.php:148`<br>`app/Http/Middleware/EnsureUserIsAdmin.php:17` | `if (!auth()->user()->isAdmin()) abort(403);` | Al solicitar la pantalla `/liquidaciones/crear` con rol profesor u operativo, Wings responde de inmediato con HTTP 403 Forbidden. |
| **Paso 1: Abrir formulario Nuevo** | En **Liquidaciones**, tocar el botón **Nuevo** para acceder al formulario de generación. | `resources/views/liquidaciones/index.blade.php:20`<br>`resources/views/liquidaciones/create.blade.php:15` | `if ($profesores->isEmpty())` | Si no hay profesores activos cargados en el club, el desplegable no ofrece docentes y la validación impide avanzar. |
| **Paso 2: Docente, período y cálculo** | Seleccionar docente, mes, año y opcionalmente clases de hoy. Tocar **Generar** crea la liquidación en estado Abierta. | `app/Http/Controllers/LiquidacionWebController.php:75-130`<br>`app/Services/LiquidacionService.php:456-469` | `if ($existe) throw new \Exception(sprintf('Ya existe una liquidación para el profesor en %02d/%d.', $mes, $anio));` | Al intentar generar una liquidación para un profesor y período que ya posee una liquidación activa (no cancelada), el sistema aborta y muestra: `Ya existe una liquidación para el profesor en MM/AAAA.`. |
| **Qué hago si: liquidación ya existente** | Wings no permite dos liquidaciones activas simultáneas para el mismo profesor en el mismo período. Se debe usar la existente y tocar Recalcular. | `app/Services/LiquidacionService.php:461-468` | `->where('estado', '!=', Liquidacion::ESTADO_CANCELADA)` | Comprobado en el sistema: si una liquidación previa fue cancelada, Wings sí permite generar una nueva para ese mismo período. Si está abierta, cerrada o pagada, rechaza la creación duplicada. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Ya existe una liquidación para el profesor en ` | `app/Services/LiquidacionService.php:466` | Sí | Qué hago si: ¿Qué hago si Wings dice que ya existe una liquidación para ese mes? |
| `Seleccioná un profesor.` | `app/Http/Controllers/LiquidacionWebController.php:82` | No | Validación requerida de formulario si se envía sin elegir profesor. |
| `Seleccioná el mes.` | `app/Http/Controllers/LiquidacionWebController.php:83` | No | Validación estándar de formulario para el mes de liquidación. |
| `Ingresá el año.` | `app/Http/Controllers/LiquidacionWebController.php:84` | No | Validación estándar de formulario para el año de liquidación. |
| `Liquidación generada correctamente.` | `app/Http/Controllers/LiquidacionWebController.php:125` | No | Mensaje flash emitido tras completar la redirección al desglose. |
