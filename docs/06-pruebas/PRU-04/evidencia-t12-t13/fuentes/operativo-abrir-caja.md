# Ficha de fuentes: Abrir el turno de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/abrir-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/apertura`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Turno abierto previo** | Wings solo permite un turno abierto por vez. Si existe un turno abierto anterior, muestra: «Debe cerrarse antes de abrir el siguiente.». | `resources/views/caja/apertura.blade.php:15-22`<br>`app/Services/CajaService.php:95` | `if ($turnoAbierto)`<br>`if (CajaOperativa::where('estado', 'ABIERTA')->lockForUpdate()->exists())` | En pantalla muestra: `Hay un turno abierto de <usuario>. Debe cerrarse antes de abrir el siguiente.`, ocultando el formulario de apertura. Si se fuerza el envío POST, rechaza con: `Hay un turno abierto. Cerralo antes de abrir otro.`. |
| **Paso 1: Pantalla Apertura** | En menú lateral hacer clic en **Caja**. Muestra **Medio** (Efectivo) y **Último cierre**. | `resources/views/caja/apertura.blade.php:32-34`<br>`app/Services/CajaService.php:65` | `public function propuestaApertura(): array` | Sugiere como saldo inicial el cambio retenido declarado en el último cierre del cajón. |
| **Paso 2: Efectivo y confirmación obligatoria** | Ingresar **Efectivo recibido**, marcar casilla obligatoria **Confirmo que conté y recibí este efectivo.** y tocar **Abrir**. | `resources/views/caja/apertura.blade.php:60, 85`<br>`app/Services/CajaService.php:80` | `'confirmacion' => 'required\|accepted'` | Si no se marca la casilla de confirmación, Wings bloquea la apertura con el error de validación correspondiente. |
| **Diferencia mayor al 50%** | Si difiere más del 50% del cierre previo, exige tildar **Confirmo que el efectivo recibido difiere más del 50% del cierre anterior.**. | `resources/views/caja/apertura.blade.php:65-75`<br>`app/Services/CajaService.php:83` | `if ($diferenciaGrande && !$request->boolean('confirmar_diferencia_grande'))` | La pantalla despliega la alerta en amarillo y el botón **Abrir** se deshabilita hasta tildar la confirmación expresa de la diferencia. |
| **Qué hago si: rectificar apertura** | Si se cometió error de tipeo en el efectivo recibido y la caja sigue abierta, se usa la opción **Rectificar**. | `CajaWebController.php:102, 113`<br>`CajaService.php:161` | `if ($caja->estado !== CajaOperativa::ESTADO_ABIERTA)` | La rectificación es válida exclusivamente mientras la caja está `ABIERTA`. Con caja rechazada, cerrada o validada no se puede rectificar la apertura. |
