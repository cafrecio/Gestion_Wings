# Ficha de fuentes: Consultar el estado de cobranza (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-cobranza.md`
- **Rol:** Operativo
- **Pantalla:** `/cobranza`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Control de acceso y rol** | El módulo de cobranza está disponible para Operativo y Admin. | `routes/web.php:73`<br>`docs/02-contratos/PERMISOS-ROLES.md` | `Route::middleware(['auth', 'role:ADMIN,OPERATIVO'])` | Si un usuario con rol `PROFESOR` intenta acceder a `/cobranza`, Wings responde con `HTTP 403 Forbidden`. |
| **Paso 1: Métricas de cobranza** | Muestra métricas: **Al día**, **En plazo**, **Morosos** y **Deudores**. | `resources/views/cobranza/index.blade.php:15-30`<br>`app/Services/CobranzaEstadoService.php:80` | `$estado === self::ESTADO_AL_DIA`<br>`$estado === self::ESTADO_EN_PLAZO`<br>`$estado === self::ESTADO_MOROSO`<br>`$estado === self::ESTADO_DEUDOR` | Muestra contadores consolidados de alumnos en cada condición y el saldo global pendiente. |
| **Paso 2: Filtros** | Filtros por **Deporte**, **Grupo** y selector de condición. Botón **Limpiar**. | `resources/views/cobranza/index.blade.php:35-50`<br>`app/Http/Controllers/CobranzaWebController.php:30` | `if ($request->filled('estado')) { ... }` | Al filtrar por Morosos o Deudores, actualiza la lista exhibiendo únicamente las tarjetas que cumplen el criterio seleccionado. |
| **Paso 3: Botón directo Cobrar** | Cada tarjeta tiene botón **Cobrar** para ir directo al cobro del alumno. | `resources/views/cobranza/index.blade.php:226` | `<x-ds.button variant="secondary" href="{{ route('web.caja.cobrar-cuota', $alumno->id) }}">Cobrar</x-ds.button>` | Al presionar **Cobrar**, abre la pantalla `/caja/cobrar/{alumnoId}` con sus deudas precargadas para cobrar en recepción. |
| **Definición de estados** | Moroso: cuota del mes vencida. Deudor: dos o más meses consecutivos atrasados impagos. | `app/Services/CobranzaEstadoService.php:15-40` | `if (count($mesesAtrasados) >= 2) return self::ESTADO_DEUDOR;`<br>`if ($hoy->gt($vencimiento)) return self::ESTADO_MOROSO;` | Verificado en cálculo de estados: alumnos con cuota del mes pasada la fecha de vencimiento clasifican como `MOROSO`; si acumulan períodos anteriores impagos clasifican como `DEUDOR`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `HTTP 403 Forbidden` | `routes/web.php:73`<br>`app/Http/Middleware/CheckRole.php:25` | No | Control de autorización para rol Profesor; la operativa tiene acceso pleno a Cobranza. |
| `0 alumnos encontrados` | `resources/views/cobranza/index.blade.php:80` | No | Estado vacío visual en pantalla cuando el filtro de condición no devuelve alumnos. |
