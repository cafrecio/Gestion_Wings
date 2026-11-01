# Ficha de fuentes: configurar-mostrador

- **Página:** `resources/manual/admin/configurar-mostrador.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Paso 1: fila y Configurar en Caja (T96) | `resources/views/caja/index.blade.php:47` | `<span class="stats-info">{{ $mostradorConfigurado ? 'Cajón del mostrador: abrile la caja a un operativo' : 'Falta cargar el saldo de Efectivo' }}</span>` | Ejecutado (MedioPagoCajonTest, T96): sin configurar solo ofrece Configurar; configurada solo Abrir. |
| Paso 2: pantalla Saldo inicial de Efectivo, cuenta fija | `resources/views/caja/configuracion.blade.php:5`<br>`resources/views/caja/configuracion.blade.php:23`<br>`app/Http/Controllers/CajaWebController.php:46` | `@php($title = 'Saldo inicial de Efectivo')`<br>`<input type="text" id="cuenta" value="{{ \App\Models\TipoCaja::NOMBRE_EFECTIVO }}" readonly class="w-full px-4 py-2.5 text-sm wings-input">`<br>`$efectivo = TipoCaja::where('nombre', TipoCaja::NOMBRE_EFECTIVO)->first();` | Captura de la pantalla real (CapturasEtapaCTest). |
| Paso 3: Guardar y aviso | `app/Http/Controllers/CajaWebController.php:59` | `return redirect()->route('web.caja.index')->with('success', 'Efectivo configurado.');` | Ejecutado (MedioPagoCajonTest, T96): guarda saldo, cuenta, autor y fecha. |
| Saldo obligatorio, cero admitido | `app/Services/CajaService.php:87` | `'saldo_inicial.required' => 'El saldo inicial es obligatorio.',` | Ejecutado (MedioPagoCajonTest, T96): vacío, negativo, tres decimales y letras no configuran; 0 sí. |
| Una vez guardado no cambia | `app/Services/CajaService.php:17` | `public const EFECTIVO_YA_CONFIGURADO = 'Efectivo ya está configurado y no se puede cambiar.';` | Ejecutado (MedioPagoCajonTest, T96): con y sin caja abierta, y por pedido directo. |
| Con movimientos el saldo queda como está | `resources/views/caja/configuracion.blade.php:13` | `<p class="text-sm text-wings-muted mb-4">Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar. Cualquier ajuste se hace con un movimiento.</p>` | Ejecutado (MedioPagoCajonTest, T96). |
| Aviso a la operativa | `resources/views/caja/apertura.blade.php:24` | `<p class="text-sm">El dueño tiene que configurar Efectivo antes de abrir la caja.</p>` | Ejecutado (MedioPagoCajonTest, T96): sin Configurar y sin abrir caja. |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «El dueño tiene que configurar Efectivo antes de abrir la caja.» | `app/Services/CajaService.php:127` | Sí — Cargar el saldo inicial de Efectivo |
| «Efectivo configurado.» | `app/Http/Controllers/CajaWebController.php:59` | Sí — Pasos |
| «Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar.» | `resources/views/caja/configuracion.blade.php:13` | Sí — ¿Qué hago si dice «Efectivo ya tiene movimientos: su saldo inicial no se puede cambiar.»? |
