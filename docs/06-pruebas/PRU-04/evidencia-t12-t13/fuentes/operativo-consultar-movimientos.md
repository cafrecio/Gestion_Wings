# Ficha de fuentes: Consultar el registro de movimientos (Operativo)

- **Página del manual:** `resources/manual/operativo/consultar-movimientos.md`
- **Rol:** Operativo
- **Pantalla:** `/movimientos`
- **Fecha de verificación:** 29/10/2026
- **Entorno de ejecución:** Wings local sobre base `wings_manual_gemini` / `wings_testing_gemini`

---

## 1. Verificación paso a paso y correspondencia con el código

| Paso / Caso | Qué se afirma en el manual | Archivo y línea en Wings | Resultado al ejecutar en el sistema |
|---|---|---|---|
| **Paso 1: Módulo Movimientos** | En menú lateral hacer clic en **Movimientos**. Muestra filtros de fecha y listado cronológico de operaciones. | `resources/views/layouts/ds-app.blade.php:110`<br>`resources/views/movimientos/index.blade.php:12` | Comprobado: acceso a `/movimientos` lista últimos cobros y egresos registrados. |
| **Paso 2: Filtrar** | Campos **Desde**, **Hasta**, selector **Tipo** (**Ingreso** / **Egreso**), **Medio**. Botones **Filtrar** y **Limpiar**. | `resources/views/movimientos/index.blade.php:15, 23, 31, 33, 53, 56`<br>`app/Http/Controllers/MovimientoWebController.php:20-45` | Comprobado: formulario GET filtra por rango temporal y atributos contables. |
| **Paso 3: Auditar** | Columnas: fecha y hora, tipo con badge, concepto o alumno, medio e importe. | `resources/views/movimientos/index.blade.php:90-130` | Comprobado: tabla detalla montos, recibos y conceptos con formato monetario canónico. |
| **Qué hago si: modificar** | Es un log inmutable de auditoría. Cancelaciones se efectúan en la caja abierta/rechazada correspondiente. | `app/Http/Controllers/MovimientoWebController.php`<br>`app/Http/Controllers/CajaWebController.php:601` | Comprobado: la pantalla no tiene acciones de edición directa sobre los registros. |
| **Qué hago si: no veo bancos** | Filtro en **Medio** acota a esa cuenta; botón **Limpiar** muestra todas las operaciones. | `resources/views/movimientos/index.blade.php:56` | Comprobado: botón **Limpiar** remueve filtros de medio. |
