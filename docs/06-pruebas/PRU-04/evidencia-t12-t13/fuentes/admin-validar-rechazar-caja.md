# Ficha de fuentes: Validar o rechazar el cierre de caja de un turno

- **Página del manual:** `resources/manual/admin/validar-rechazar-caja.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/caja/{id}/resumen`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | La validación o rechazo del arqueo de cierre de un turno operativo es una responsabilidad exclusiva del dueño. | `app/Services/CajaService.php:412, 502`<br>`app/Http/Controllers/CajaWebController.php:665` | `abort_unless($admin->isAdmin() && $admin->activo, 403);` | Al invocar `/caja/{id}/validar` o `/caja/{id}/rechazar` con un usuario no administrador, el sistema aborta de inmediato con HTTP 403 Forbidden. |
| **Paso 1: Abrir resumen de caja cerrada** | En Caja, abrir la caja en estado **Cerrada**. Revisar el cuadro Efectivo del mostrador (Inicial, Esperado, Contado, Diferencia, Cambio que queda, Retiro). | `resources/views/caja/resumen.blade.php:25-80`<br>`app/Models/CajaOperativa.php:65` | `@if($caja->estado === 'CERRADA')`<br>`<x-ds.button ...>Validar</x-ds.button>`<br>`<x-ds.button ...>Rechazar</x-ds.button>`<br>`@endif` | Si la caja se encuentra en estado `ABIERTA` o ya fue `VALIDADA`, los botones **Validar** y **Rechazar** no se renderizan en el resumen. |
| **Paso 2: Opción A: Rechazar con motivo** | Si hay diferencias o cobros mal anotados, tocar **Rechazar**, indicar el motivo en el panel y confirmar. La caja pasa a Rechazada para que la operativa corrija. | `app/Services/CajaService.php:504-513`<br>`resources/views/caja/resumen.blade.php:140-165` | `if ($caja->estado !== 'CERRADA' \|\| $caja->efectivo_contado === null) throw new \Exception('Contá y cerrá la caja antes de rechazarla.');` | Al enviar el formulario de rechazo sin completar el motivo, la validación bloquea con: `El motivo del rechazo es obligatorio.`. Al intentar rechazar por POST una caja en estado `ABIERTA`, el servicio rechaza con: `Contá y cerrá la caja antes de rechazarla.`. |
| **Paso 3: Opción B: Validar el turno** | Si el arqueo coincide, tocar **Validar**. Pasa a Validada, el efectivo se impacta en Cashflow y el turno queda archivado definitivamente. | `app/Services/CajaService.php:418-430`<br>`app/Http/Controllers/CajaWebController.php:675` | `if ($caja->estado === 'ABIERTA') throw new \Exception('Contá el efectivo y cerrá la caja antes de validarla.');`<br>`if ($caja->estado !== 'CERRADA') throw new \Exception('Solo se pueden validar cajas cerradas.');` | Al intentar forzar la validación por POST sobre una caja en estado `ABIERTA`, el sistema rechaza con: `Contá el efectivo y cerrá la caja antes de validarla.`. Sobre una caja rechazada devuelve: `Solo se pueden validar cajas cerradas.`. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `El motivo del rechazo es obligatorio.` | `app/Http/Controllers/CajaWebController.php:694` | No | Validación requerida al intentar rechazar sin ingresar texto en el panel. |
| `Contá el efectivo y cerrá la caja antes de validarla.` | `app/Services/CajaService.php:419` | No | Excepción técnica si se invoca la ruta de validación en una caja aún abierta. |
| `Solo se pueden validar cajas cerradas.` | `app/Services/CajaService.php:430` | No | Excepción técnica si la caja no está cerrada. |
| `Contá y cerrá la caja antes de rechazarla.` | `app/Services/CajaService.php:506` | No | Excepción técnica al intentar rechazar una caja sin cierre previo. |
| `Caja validada y reflejada en cashflow.` | `app/Http/Controllers/CajaWebController.php:675` | No | Mensaje flash emitido tras la validación exitosa. |
| `Caja rechazada.` | `app/Http/Controllers/CajaWebController.php:705` | No | Mensaje flash emitido tras el rechazo exitoso. |
