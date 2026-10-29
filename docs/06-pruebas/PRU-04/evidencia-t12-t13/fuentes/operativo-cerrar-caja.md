# Ficha de fuentes: Cerrar el turno de caja y rendir el arqueo (Operativo)

- **Página del manual:** `resources/manual/operativo/cerrar-caja.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/{cajaId}/cerrar`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Solo efectivo** | Contar solo efectivo físico del cajón; no sumar transferencias. Se puede cerrar con diferencia (sobrante o faltante). | `resources/views/caja/cierre.blade.php:15, 47`<br>`app/Services/CajaService.php:310` | Comprobado: instrucción visible en la cabecera; admite arqueo con diferencia. |
| **Paso 1: Entrar al cierre** | En **Caja**, tocar botón **Cerrar**. Muestra pantalla de cierre con **Efectivo inicial** y **Efectivo esperado**. | `resources/views/caja/index.blade.php:78`<br>`resources/views/caja/cierre.blade.php:3, 20, 21` | Comprobado: acceso a `/caja/{id}/cerrar` calcula el saldo esperado del sistema. |
| **Paso 2: Contado y cambio** | Ingresar **Efectivo contado** y **Cambio que queda**. Calcula **Diferencia** y **Retiro / entrega**. | `resources/views/caja/cierre.blade.php:26, 44, 45`<br>`resources/js/caja-arqueo.js` | Comprobado: cálculo dinámico en pantalla actualiza retiro y diferencia en tiempo real. |
| **Paso 3: Confirmar cierre** | Revisar importes y tocar botón **Cerrar**. Pasa caja a estado Cerrada bloqueando nuevas operaciones. | `resources/views/caja/cierre.blade.php:50`<br>`app/Http/Controllers/CajaWebController.php:210-235` | Comprobado: estado cambia a `CERRADA`; turno listo para validación del dueño. |
| **Qué hago si: diferencia** | Ingresar el efectivo real; no forzar números. El dueño revisará la discrepancia al auditar la caja. | `app/Services/CajaService.php:320`<br>`resources/views/caja/cierre.blade.php:47` | Comprobado: cierre procesa correctamente guardando la diferencia calculada. |
| **Qué hago si: cobro olvidado** | Una vez cerrada no se pueden cargar cobros; avisar al dueño para que la rechace si aún no la validó. | `app/Http/Controllers/CajaWebController.php:215` | Comprobado: rechazo de caja permite reabrir la operatoria de corrección. |
