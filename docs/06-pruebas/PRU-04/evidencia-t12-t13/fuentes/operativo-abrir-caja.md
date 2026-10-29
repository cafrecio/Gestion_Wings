# Ficha de fuentes: Abrir el turno de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/abrir-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/apertura`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Regla de control** | Contar efectivo real antes de abrir; el fondo de cambio recibido no es ingreso del club. | `resources/views/caja/apertura.blade.php:30`<br>`app/Services/CajaService.php` | Comprobado: texto explicativo visible en la cabecera del formulario de apertura. |
| **Paso 1: Ingresar a Caja** | En menú lateral hacer clic en **Caja**. Muestra **Apertura de caja**, **Medio** (Efectivo) y **Último cierre**. | `resources/views/layouts/ds-app.blade.php:101`<br>`resources/views/caja/apertura.blade.php:3, 32, 33` | Comprobado: acceso sin turno abierto redirige a la vista de apertura. |
| **Paso 2: Efectivo y confirmar** | Ingresar **Efectivo recibido**, **Motivo si recibiste otro importe** opcional, casilla **Confirmo que conté y recibí este efectivo.** y botón **Abrir**. | `resources/views/caja/apertura.blade.php:54, 61, 82, 86`<br>`app/Http/Controllers/CajaWebController.php:120-150` | Comprobado: formulario procesa apertura, crea caja con estado `ABIERTA` y redirige al panel. |
| **Qué hago si: turno previo** | Si hay turno previo abierto, Wings indica «Debe cerrarse antes de abrir el siguiente.»; debe cerrarse antes de continuar. | `resources/views/caja/apertura.blade.php:15`<br>`app/Services/CajaService.php:260` | Comprobado: bloqueo operativo estricto si hay caja abierta pendiente. |
| **Qué hago si: diferencia grande** | Si difiere > 50% del cierre previo, muestra advertencia y exige tildar **Confirmo que el efectivo recibido difiere más del 50% del cierre anterior.**. | `resources/views/caja/apertura.blade.php:66-77` | Comprobado: validación condicional se activa ante discrepancias significativas de monto. |
| **Qué hago si: corregir apertura** | Si se cometió error de tipeo en apertura, se utiliza la pantalla de rectificación de apertura (`/caja/{id}/rectificar`). | `resources/views/caja/rectificar.blade.php`<br>`app/Http/Controllers/CajaWebController.php:360` | Comprobado: botón **Rectificar** permite ajustar el saldo inicial con motivo. |
| **Qué hago si: caja día anterior** | Si quedó caja abierta de día previo, bloquea con «Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.» y exige cerrarla con botón **Cerrar**. | `app/Services/CajaService.php:271`<br>`resources/views/caja/cierre.blade.php` | Comprobado: excepción bloquea operaciones hasta completar el arqueo de la caja vieja. |
