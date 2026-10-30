# Ficha de fuentes: administrar-tipos-caja

- **Página:** `resources/manual/admin/administrar-tipos-caja.md` · **Rol:** admin · Escrita por Claude CAB el 30/10/2026 (etapa C).
- La columna «Condición en el código» es la línea literal, tomada del archivo por `etapa-c/generar-fichas.py`.

| Qué afirma la página | Archivo y línea | Condición en el código | Qué pasó al ejecutarlo |
|---|---|---|---|
| Descubierto: solo se controla al pagar una liquidación | `app/Http/Controllers/LiquidacionWebController.php:439` | `if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto) {` | Leído en el código; no ejecutado en pantalla. |
| Saldo inicial editable solo sin movimientos | `app/Http/Controllers/TipoCajaWebController.php:112` | `return !$tipoCaja->movimientosOperativos()->exists()` | Ejecutado (CapturasEtapaCTest): HTTP 200 · Campo saldo_inicial en el formulario: no |
| No desactiva la cuenta del mostrador | `app/Http/Controllers/TipoCajaWebController.php:120` | `if ($tipoCaja->activo && (int) $tipoCaja->id === (int) $this->cajaService->tipoCajaMostradorId()) {` | Ejecutado (CapturasEtapaCTest): HTTP 302 · «No se puede desactivar porque es el medio de pago del cajón de mostrador.» |
| Botón Desactivar / Activar | `resources/views/tipos-caja/index.blade.php:79` | `<button type="submit" style="{{ $tipoCaja->activo ? $btnBDang : $btnBSec }}">{{ $tipoCaja->activo ? 'Desactivar' : 'Activar' }}</button>` | Captura de la pantalla real (CapturasEtapaCTest). |

## Rechazos y avisos de la acción

Cada mensaje de Wings que cita la página, dónde está en el código y en qué sección de la página aparece. Los rechazos que la página no cita quedan en la tabla de arriba o en el informe del revisor.

| Mensaje de Wings | Dónde está | ¿Está en la página? |
|---|---|---|
| «Tipo de caja creado correctamente.» | `app/Http/Controllers/TipoCajaWebController.php:59` | Sí — Crear una cuenta |
| «No se puede desactivar porque es el medio de pago del cajón de mostrador.» | `app/Http/Controllers/TipoCajaWebController.php:98` | Sí — ¿Qué hago si dice «No se puede desactivar porque es el medio de pago del cajón de mostrador.»? |
