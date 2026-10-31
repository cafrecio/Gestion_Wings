# Ficha de fuentes: configurar-mostrador

- **Página:** `resources/manual/admin/configurar-mostrador.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Paso 2: pantalla Efectivo del mostrador, Medio de efectivo, solo cuentas activas | `resources/views/caja/configuracion.blade.php:3`<br>`resources/views/caja/configuracion.blade.php:14`<br>`app/Http/Controllers/CajaWebController.php:44` | `@section('module-title', 'Efectivo del mostrador')`<br>`Medio de efectivo <span class="form-required">*</span>`<br>`$tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();` | Captura de la pantalla real (CapturasEtapaCTest). |
| Paso 3: Guardar y aviso | `app/Http/Controllers/CajaWebController.php:57` | `return redirect()->route('web.caja.index')->with('success', 'Medio de efectivo configurado.');` | Leído en el código; no ejecutado en pantalla. |
| No se cambia con una caja abierta | `app/Services/CajaService.php:54` | `throw new \DomainException('No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.');` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.» |
| Paso 1: Configurar en Caja, inhabilitado con una caja abierta (T96) | `resources/views/caja/index.blade.php:49` | `<x-ds.button variant="secondary" href="{{ route('web.caja.configuracion') }}" :disabled="$hayCajaAbierta" :title="$hayCajaAbierta ? 'No se puede cambiar el medio del cajón mientras haya una caja abierta.' : null">Configurar</x-ds.button>` | Ejecutado (MedioPagoCajonTest, T96): habilitado sin caja abierta; con caja abierta, sin enlace y con disabled. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Medio de efectivo configurado.» | `app/Http/Controllers/CajaWebController.php:57` | Sí — Pasos |
| «Configurado actualmente. Podés cambiarlo seleccionando otro medio activo.» | `resources/views/caja/configuracion.blade.php:26` | Sí — Pasos |
| «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.» | `app/Services/CajaService.php:54` | Sí — ¿Qué hago si dice «No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.»? |
