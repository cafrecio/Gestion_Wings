# Ficha de fuentes: Transferir entre medios propios

- **Página del manual:** `resources/manual/admin/transferir-entre-cuentas.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/cashflow/transferencia`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Acceso exclusivo admin** | La transferencia interna entre medios propios es una acción exclusiva del dueño en Cashflow. No altera el resultado global del club. | `routes/web.php:138`<br>`app/Services/TransferenciaCashflowService.php:18` | `if (!$admin->isAdmin() \|\| !$admin->isActivo()) throw new AuthorizationException();` | Al intentar ingresar a `/cashflow/transferencia` con un usuario no administrador, el sistema aborta de inmediato con HTTP 403 Forbidden. |
| **Paso 1 y 2: Formulario y cuentas distintas** | Seleccionar cuenta de salida, cuenta de destino e importe. Ambas cuentas deben ser distintas. | `resources/views/cashflow/transferencia.blade.php:25-50`<br>`app/Services/TransferenciaCashflowService.php:31` | `'destino_id' => 'required\|integer\|different:salida_id\|exists:tipos_caja,id'` | Si se selecciona la misma cuenta tanto en salida como en destino, la validación del sistema bloquea el guardado y muestra el error: «Elegí una cuenta de destino distinta de la salida.». |
| **Fecha de un mes anterior** | Si se elige una fecha de un mes anterior, Wings pide confirmar porque cambiará los saldos históricos de ambas cuentas desde ese período. | `app/Http/Controllers/TransferenciaCashflowWebController.php:25-29`<br>`resources/views/cashflow/transferencia.blade.php:55` | `if ($fechaVieja && !$request->boolean('confirmar_fecha_vieja')) { return back()->withInput()->with('aviso_fecha_vieja', 'La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.'); }` | Al cargar una transferencia con fecha anterior sin marcar la confirmación requerida, la pantalla no guarda los movimientos, recarga con el aviso textual «La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.» y el botón cambia a **Confirmar**. |
| **Registro indivisible de salida y entrada** | Al confirmar, Wings registra la salida y la entrada enlazadas en una única transacción atómica y muestra «salida y entrada guardadas.». | `app/Services/TransferenciaCashflowService.php:12, 21-24`<br>`app/Http/Controllers/TransferenciaCashflowWebController.php:40` | `return DB::transaction(function () use ($adminId, $datos) { return $this->guardar($adminId, $datos); }, 3);` | La operación es indivisible: si falla la inserción de una de las dos patas, la transacción se revierte por completo evitando saldos inconsistentes. Al completarse con éxito, redirige a Cashflow confirmando que ambas operaciones fueron guardadas. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Elegí una cuenta de destino distinta de la salida.` | `app/Services/TransferenciaCashflowService.php:35` | Sí | Qué hago si: ¿Qué hago si elijo la misma cuenta en salida y destino? |
| `La transferencia quedará con esa fecha y cambiará los saldos de ambas cuentas desde ese mes. El resultado del club no cambia. Confirmá para guardar la salida y la entrada.` | `app/Http/Controllers/TransferenciaCashflowWebController.php:28` | Sí | Qué hago si: ¿Qué hago si la fecha es de un mes anterior? |
| `salida y entrada guardadas.` | `app/Http/Controllers/TransferenciaCashflowWebController.php:40` | Sí | Paso 2: Indicar salida, destino e importe |
| `El campo monto debe ser al menos 0.01.` | `app/Services/TransferenciaCashflowService.php:32` | No | Validación requerida de monto numérico positivo. |
| `La fecha no puede ser futura.` | `app/Services/TransferenciaCashflowService.php:33` | No | Validación estándar que impide transferencias con fechas posteriores a hoy. |
