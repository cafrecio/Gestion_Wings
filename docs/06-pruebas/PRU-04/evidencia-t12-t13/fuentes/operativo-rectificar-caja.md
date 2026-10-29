# Ficha de fuentes: Rectificar una apertura o cierre observado (Operativo)

- **Página del manual:** `resources/manual/operativo/rectificar-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/rectificar`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Requisito** | Se puede rectificar si la caja está Abierta o si fue Rechazada por el dueño. Siempre exige motivo de rectificación. | `app/Http/Controllers/CajaWebController.php:360`<br>`resources/views/caja/rectificar.blade.php:36` | Comprobado: formulario bloquea el envío si falta el motivo. |
| **Paso 1: Pantalla** | En **Caja**, tocar botón **Rectificar**. Muestra **Responsable**, **Apertura** y **Efectivo declarado actual**. | `resources/views/caja/rectificar.blade.php:3, 18-20`<br>`resources/views/caja/index.blade.php:79` | Comprobado: acceso a `/caja/{id}/rectificar` exhibe datos de la apertura inicial. |
| **Paso 2: Valor y motivo** | Ingresar **Nuevo efectivo recibido**, **Motivo de la rectificación** y tocar **Guardar**. Recalcula saldo esperado. | `resources/views/caja/rectificar.blade.php:29, 36, 44`<br>`app/Http/Controllers/CajaWebController.php:370` | Comprobado: actualización procesa nuevo saldo inicial y actualiza el saldo teórico. |
| **Qué hago si: validada** | Caja validada no se puede rectificar; corrección monetaria posterior por ajuste en Cashflow (DU-27). | `app/Services/CashflowService.php` | Comprobado: caja en `VALIDADA` no tiene ruta ni botón de rectificación. |
| **Qué hago si: volver atrás** | Botón **Volver** regresa al resumen sin modificar el efectivo inicial declarado. | `resources/views/caja/rectificar.blade.php:43` | Comprobado: botón **Volver** cancela la acción sin alterar datos. |
