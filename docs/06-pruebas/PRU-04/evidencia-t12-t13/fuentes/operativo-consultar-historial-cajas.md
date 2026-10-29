# Ficha de fuentes: Consultar el historial de turnos de caja (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-historial-cajas.md`
- **Rol:** Operativo
- **Pantalla:** `/caja/historial`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Auditoría histórica** | Permite revisar cobros o egresos asentados en turnos anteriores para verificar comprobantes o resolver dudas. | `resources/views/caja/historial.blade.php`<br>`app/Http/Controllers/CajaWebController.php:700` | Comprobado: listado histórico carga movimientos cerrados y consolidados. |
| **Paso 1: Entrar a Historial** | En **Caja**, tocar botón **Historial**. Muestra pantalla con filtros y listado consolidado. | `resources/views/caja/index.blade.php:82`<br>`resources/views/caja/historial.blade.php:3` | Comprobado: acceso a `/caja/historial` carga log general de transacciones. |
| **Paso 2: Filtrar** | **Alumno, subrubro u observación...**, selectores **Subrubro**, **Ingreso / Egreso**, rango **Desde** y **Hasta**. Botones **Filtrar** y **Limpiar**. | `resources/views/caja/historial.blade.php:35, 43, 52, 61, 68, 75, 76`<br>`app/Http/Controllers/CajaWebController.php:710-740` | Comprobado: filtrado exacto por fechas y criterios contables; botón **Limpiar** resetea campos. |
| **Paso 3: Analizar resultados** | Muestra total movimientos, total **Ingresos** (verde), **Egresos** (rojo) y tabla con Fecha, Detalle, Medio, Registró y Monto. Botón **Volver**. | `resources/views/caja/historial.blade.php:88, 89, 91, 117-121`<br>`app/Support/FormatoImporte.php` | Comprobado: métricas de totales y tabla con importes formateados en pesos. |
| **Qué hago si: cancelado** | Movimiento marcado con «· Cancelado» indica que fue anulado en su turno abierto; no suma al balance. | `resources/views/caja/historial.blade.php:140`<br>`app/Http/Controllers/CajaWebController.php:725` | Comprobado: transacciones canceladas se exhiben con badge distintivo y no acumulan en el total. |
| **Qué hago si: anular viejo** | Es de solo consulta; cancelaciones se realizan en el turno abierto o rechazado (`/caja/{id}/detalle`). | `app/Http/Controllers/CajaWebController.php` | Comprobado: la pantalla no contiene acciones destructivas sobre turnos cerrados. |
