# IMPLEMENTACIÓN T20 — Pantalla de Cobrar en el Celular

**Resultado: IMPLEMENTADO Y LISTO PARA VERIFICACIÓN VISUAL DE CARLOS**

Cambios de diseño solicitados por Carlos el 10/10/2026 tras revisar la pantalla de cobro desde su teléfono móvil (ancho de referencia: 360px).
Modificaciones aplicadas exclusivamente en la vista `resources/views/caja/cobrar.blade.php`, manteniendo 100% intacta la lógica en controladores, servicios, nombres/ids de campos y scripts JS (`resources/js/cobrar.js`).

---

## 1. Detalle de los cambios implementados

1. **Planes del mismo tamaño:**
   - En celular (360px): las opciones («1 vez/semana» y «2 veces/semana») miden exactamente el mismo ancho ocupando el 100% del contenedor una debajo de la otra (`grid grid-cols-1`).
   - En escritorio (1366px): se organizan lado a lado en dos columnas simétricas de idéntico tamaño (`sm:grid-cols-2`), con contenido justificado en los extremos (`w-full justify-between`).
2. **Íconos en los rótulos:**
   - Se incorporaron íconos SVG idénticos al patrón visual del sistema (`resources/views/subrubros/_form.blade.php`) para los campos:
     - **Mes** (en Cobro adelantado): ícono de calendario.
     - **Importe** (en Cobro adelantado): ícono de moneda.
     - **Medio de pago**: ícono de tarjeta/medio de pago.
     - **Fecha del pago**: ícono de calendario.
     - **Observaciones**: ícono de nota/comentario.
3. **Disposición responsiva en Medio de pago, Fecha y Observaciones:**
   - En celular (360px): se distribuyen en **3 filas verticales** a ancho completo (`grid grid-cols-1 gap-4`), garantizando que el selector de medio de pago disponga de espacio óptimo y legible.
   - En escritorio (1366px): se mantienen en **3 columnas horizontales** (`md:grid-cols-3 gap-4`).
4. **Condiciones preservadas:**
   - DNI oculto en móvil (`hidden sm:block`) y visible en escritorio.
   - Bloque de «Cobro adelantado» completamente funcional (selección de mes, ajuste de importe sugerido, agregar y suma en el total a cobrar).
   - Botones con verbos únicos: `Cobrar`, `Cancelar`, `Agregar`.

---

## 2. Evidencia visual comparativa (Antes y Después)

Todas las capturas fueron extraídas directamente de la aplicación funcionando con datos reales, respetando el ancho exacto de marco (360px para móvil e iframe sin recorte artificial, y 1366px para escritorio).

### Zona 1: Planes — frecuencia semanal

| Ancho | Antes | Después |
|---|---|---|
| **Móvil (360px)** | [01-planes-antes-360.png](evidencia-t20/01-planes-antes-360.png) | [01-planes-despues-360.png](evidencia-t20/01-planes-despues-360.png) |
| **Escritorio (1366px)** | [01-planes-antes-1366.png](evidencia-t20/01-planes-antes-1366.png) | [01-planes-despues-1366.png](evidencia-t20/01-planes-despues-1366.png) |

### Zona 2: Cobro adelantado (Mes e Importe con íconos)

| Ancho | Antes | Después |
|---|---|---|
| **Móvil (360px)** | [02-adelanto-antes-360.png](evidencia-t20/02-adelanto-antes-360.png) | [02-adelanto-despues-360.png](evidencia-t20/02-adelanto-despues-360.png) |
| **Escritorio (1366px)** | [02-adelanto-antes-1366.png](evidencia-t20/02-adelanto-antes-1366.png) | [02-adelanto-despues-1366.png](evidencia-t20/02-adelanto-despues-1366.png) |

### Zona 3: Medio de pago, Fecha y Observaciones

| Ancho | Antes | Después |
|---|---|---|
| **Móvil (360px)** | [03-medio-pago-antes-360.png](evidencia-t20/03-medio-pago-antes-360.png) | [03-medio-pago-despues-360.png](evidencia-t20/03-medio-pago-despues-360.png) |
| **Escritorio (1366px)** | [03-medio-pago-antes-1366.png](evidencia-t20/03-medio-pago-antes-1366.png) | [03-medio-pago-despues-1366.png](evidencia-t20/03-medio-pago-despues-1366.png) |

### Vistas completas de pantalla

- **Móvil (360px):** [pantalla-antes-360.png](evidencia-t20/pantalla-antes-360.png) vs [pantalla-despues-360.png](evidencia-t20/pantalla-despues-360.png)
- **Escritorio (1366px):** [pantalla-antes-1366.png](evidencia-t20/pantalla-antes-1366.png) vs [pantalla-despues-1366.png](evidencia-t20/pantalla-despues-1366.png)

---

## 3. Pruebas de funcionamiento y usabilidad

1. **Desplazamiento horizontal en 360px:**
   - Comprobación: `scrollWidth = clientWidth = 360px` tanto en `document` como en `body`. Ningún campo excede el recuadro ni produce scroll horizontal.
2. **Flujo interactivo completo probado en 360px:**
   - Se tildó una cuota pendiente ($28.000).
   - Se seleccionó un medio de pago en el desplegable.
   - Se seleccionó un mes futuro para cobro adelantado (Noviembre 2026), se editó el importe a $35.000 y se presionó «Agregar».
   - El mes adelantado se agregó a la lista con badge «Adelantado», el total a cobrar sumó correctamente **$63.000** ($28.000 + $35.000) y el botón **Cobrar** quedó debidamente habilitado.
   - Evidencia capturada: [04-interaccion-360.png](evidencia-t20/04-interaccion-360.png).

---

## 4. Resultado de la suite completa

Ejecución sobre base aislada `wings_testing_gemini`:
```text
OK, but some tests were skipped!
Tests: 613, Assertions: 5087, Skipped: 2.
```
Cero errores de sintaxis (`php -l`), vistas compiladas correctamente (`php artisan view:cache`).
