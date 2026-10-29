# Ficha de fuentes: Consultar el estado de cobranza (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-cobranza.md`
- **Rol:** Operativo
- **Pantalla:** `/cobranza`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Paso 1: Módulo Cobranza** | En menú lateral hacer clic en **Cobranza**. Muestra resumen general con total adeudado y estados: Al día, En plazo, Morosos, Deudores. | `resources/views/layouts/ds-app.blade.php:97`<br>`resources/views/cobranza/index.blade.php:9-15` | Comprobado: panel superior despliega contadores por condición y monto total adeudado. |
| **Paso 2: Filtrar** | Selectores **Deporte**, **Grupo** y de estado: **Con deuda (Deudores y morosos)**, **Moroso**, **Deudor**, **Al día**. Botón **Limpiar**. | `resources/views/cobranza/index.blade.php:50-76`<br>`app/Http/Controllers/CobranzaWebController.php:30-55` | Comprobado: filtrado dinámico responde a los parámetros; botón **Limpiar** resetea los filtros. |
| **Paso 3: Cobro directo** | Botón **Cobrar** en cada tarjeta abre directamente la pantalla de cobro del alumno con sus deudas precargadas. | `resources/views/cobranza/index.blade.php:224-226` | Comprobado: botón **Cobrar** en la tarjeta redirige a `/caja/cobrar/{alumnoId}`. |
| **Qué hago si: moroso vs deudor** | Moroso: cuota vencida del mes en curso. Deudor: dos o más cuotas consecutivas adeudadas. | `app/Services/CobranzaService.php:80-110`<br>`app/Models/DeudaCuota.php` | Comprobado: clasificación calculada por cantidad de períodos impagos y fecha de vencimiento. |
| **Qué hago si: inactivos** | El panel lista deportistas activos; deudas de inactivos se consultan en su ficha o pantalla general de cobro. | `app/Http/Controllers/CobranzaWebController.php:35` | Comprobado: query por defecto filtra `activo = 1`. |
