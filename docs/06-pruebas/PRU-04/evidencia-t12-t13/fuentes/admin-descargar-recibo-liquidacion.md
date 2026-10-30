# Ficha de fuentes: Descargar el recibo oficial de liquidación

- **Página del manual:** `resources/manual/admin/descargar-recibo-liquidacion.md`
- **Rol:** Admin (Dueño)
- **Pantalla analizada:** `/recibos/liquidacion/{id}`
- **Fecha de verificación:** 30/10/2026
- **Entorno de verificación:** Wings local sobre base `wings_testing_gemini`

---

## 1. Verificación paso a paso, condiciones en código y caminos prohibidos

| Paso / Caso / Regla | Qué afirma el manual | Archivo y línea en Wings | Condición en el código (`if`) | Qué pasó al ejecutar el camino prohibido en el sistema |
|---|---|---|---|---|
| **Requisito: liquidación pagada** | El recibo oficial solo se puede generar y descargar una vez que la liquidación fue efectivamente pagada. | `app/Http/Controllers/ReciboController.php:125-130`<br>`resources/views/liquidaciones/partials/acciones.blade.php:62-72` | `@if($esPagada)`<br>`if (!$liquidacion->estaPagada()) { return response()->json(['error' => 'Liquidación no pagada', 'message' => 'Solo se pueden generar recibos de liquidaciones que han sido pagadas'], 400); }` | En una liquidación en estado `ABIERTA` o `CERRADA` sin pagar, el botón **Recibo** no aparece en la barra superior. Si se solicita directamente la URL `/recibos/liquidacion/{id}` mediante GET, el servidor rechaza con HTTP 400 y mensaje JSON: `Solo se pueden generar recibos de liquidaciones que han sido pagadas`. |
| **Paso 1: Abrir liquidación pagada** | En **Liquidaciones**, ubicar al docente con badge verde **Pagada** y abrir su detalle. | `resources/views/liquidaciones/index.blade.php:40`<br>`resources/views/liquidaciones/show.blade.php:85` | `if ($liquidacion->estaPagada() && $liquidacion->pagada_fecha)` | Si la liquidación está cancelada o no existe (id 99999), Wings devuelve HTTP 404: `No existe una liquidación con ID 99999`. |
| **Paso 2: Visualizar comprobante PDF** | Al tocar **Recibo**, abre en pestaña nueva el PDF A5 vertical con dos páginas: cuerpo principal y anexo cronológico de clases o alumnos. | `app/Services/ReciboService.php:140-210`<br>`resources/views/pdfs/recibo-liquidacion.blade.php:1-180` | `if (!$storage->exists($ruta))`<br>`$dompdf->render();` | Si falla el almacenamiento en disco o se interrumpe DomPDF, el controlador captura la excepción y retorna HTTP 500 informando que no se pudo generar el comprobante. |
| **Qué hago si: botón Recibo ausente** | Si no aparece el botón, la liquidación está abierta o cerrada sin asentar el egreso de fondos. Debe pagarse primero. | `resources/views/liquidaciones/partials/acciones.blade.php:62` | `@if($esPagada)` | Comprobado en pantalla: mientras la liquidación permanece en `CERRADA`, solo se muestran los bloques de **Registrar pago**, **Cancelar liquidación** y el botón **Volver**. El botón **Recibo** solo se inyecta tras registrar el pago. |

---

## 2. Rechazos y avisos de la acción

| Rechazo / Aviso | Archivo:Línea | ¿Está en la página? | Sección en la página / Motivo si no está |
|---|---|---|---|
| `Solo se pueden generar recibos de liquidaciones que han sido pagadas` | `app/Http/Controllers/ReciboController.php:128` | Sí | Qué hago si: ¿Qué hago si el botón Recibo no aparece? |
| `No existe una liquidación con ID :id` | `app/Http/Controllers/ReciboController.php:120` | No | Excepción técnica de ruta HTTP 404 cuando se ingresa un identificador inexistente. |
| `No se pudo generar el comprobante. Por favor, intentá nuevamente o comunicate con administración.` | `app/Http/Controllers/ReciboController.php:146` | No | Manejo de excepción interna HTTP 500 ante fallo crítico de renderizado de PDF en disco. |
