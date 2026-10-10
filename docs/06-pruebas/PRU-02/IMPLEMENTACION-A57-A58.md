# Entrega de Implementación: Defectos A57 y A58 (Móvil 360px)

**Fecha:** 10/10/2026  
**Autor:** Gemini (CYE)  
**Aprobado visualmente por:** Carlos («Apruebo la propuesta de A57 y A58. Aplicar la solución de CSS y cerrar la tarea.» / «Que no agregues estilos style="..." manuales en el HTML (usar solo reglas en app.css compiladas con Vite/Tailwind)»)  
**Herramienta de medición:** `scripts/medir-ancho-movil.mjs`  
**Visor interactivo y capturas:** [`docs/06-pruebas/PRU-02/evidencia/a57-a58/visor.html`](evidencia/a57-a58/visor.html)

---

## 1. Resumen Ejecutivo

Carlos relevó desde su teléfono móvil (360px de ancho) dos anomalías en el sitio de prueba:
1. **A57:** En `/caja`, el desplegable «Operativo» medía todo el ancho mientras que el selector de fecha «octubre de 2026» medía sustancialmente menos, viéndose desparejo.
2. **A58:** En `/clases`, la pantalla se podía deslizar lateralmente a la derecha sin que hubiera contenido.

Se auditó el sistema completo en las tres resoluciones móviles de control (**360px**, 375px y 320px) cubriendo **82 pantallas/roles**. Ambos defectos quedaron resueltos exclusivamente mediante reglas globales compartidas en `resources/css/app.css` compiladas con Vite/Tailwind, **sin modificar una sola línea de HTML ni agregar atributos `style="..."` manuales en vistas Blade**.

---

## 2. Diagnóstico y Causa Raíz

### Defecto A57 — Ancho dispar en campos de filtros
* **Palabras de Carlos:** *«El tamaño de los select debería ser el mismo […] fecha es más chico que operativo y queda feo.»*
* **Causa técnica:** En `resources/css/app.css` (línea 967), la regla responsive `@media (max-width: 768px)` forzaba `width: 100%` únicamente sobre `.filtros-select` y `.filtros-control[type="date"]`.
  El control de Caja era `<input type="month">` con clase `.filtros-control` y un estilo inline `width: auto`. En 360px, «Operativo» medía **278px** y «Mes» medía **230px** (diferencia de 48px).
* **Solución aplicada:** Se generalizó el selector en `resources/css/app.css` bajo `@media (max-width: 768px)` para que todos los hijos de `.filtros-row` (selects, inputs de cualquier tipo, search inputs y labels contenedores) tengan `width: 100% !important; min-width: 100% !important; flex: 1 1 100% !important;`.
* **Vistas alcanzadas:** 14 pantallas con barra de filtros compartida (`/caja`, `/clases`, `/alumnos`, `/cobranza`, `/cashflow`, `/movimientos`, `/grupos`, `/profesores`, `/deportes`, `/niveles`, `/rubros`, `/liquidaciones`, `/reportes/alumnos`, `/reportes/sueldos`).

### Defecto A58 — Desplazamiento lateral innecesario en Clases
* **Palabras de Carlos:** *«tiene un scroll lateral la pantalla al pedo, no hay nada a la derecha.»*
* **Causa técnica:** Cada tarjeta de clase en `resources/views/clases/_card.blade.php` utilizaba una grilla con `grid-template-columns: repeat(3, 1fr);`. En viewport móvil de 360px, los tres bloques de información empujaban el ancho intrínseco de la tarjeta a **451px**.
  A su vez, `#clases-hoy-container` tenía `overflow-y: auto`. Por especificación de CSS (Box Alignment & Overflow), cuando un eje se define en `auto`, el eje transversal no puede ser `visible` y conmuta a `auto`. Debido a que sus tarjetas hijas medían 451px, el contenedor medía `scrollWidth: 455px` contra un `clientWidth: 328px`, habilitando 127px de scroll lateral hacia el vacío.
* **Solución aplicada:** Se agregó una regla en `resources/css/app.css` bajo `@media (max-width: 640px)` para apilar la grilla de información de la tarjeta a una columna (`grid-template-columns: 1fr !important;`) y declarar `overflow-x: hidden !important;` en `#clases-hoy-container`.
* **Resultado:** El ancho total del contenedor bajó a **328px**, eliminando el desplazamiento horizontal al 100%. En escritorio (1280px), las tarjetas conservan intactas sus 3 columnas (318px).

---

## 3. Tabla Comparativa de Medición (Móvil 360px)

| Pantalla | Selector / Elemento | Medición ANTES | Medición DESPUÉS | Estado |
|---|---|---|---|---|
| **Caja (`/caja`)** — Filtro Operativo | `.filtros-select[name="operativo_id"]` | 278px | 278px | ✅ Parejo |
| **Caja (`/caja`)** — Filtro Mes | `.filtros-control[name="mes"]` | 230px | 278px | ✅ Parejo (0px dif) |
| **Clases (`/clases`)** — Tarjetas | `.alumno-card .alumno-info` | 451px (3 cols) | 291px (1 col) | ✅ Contenido |
| **Clases (`/clases`)** — Contenedor | `#clases-hoy-container` | scrollWidth: 455px (desborda 127px) | scrollWidth: 328px (clientWidth 328px) | ✅ Sin scroll lateral |
| **Alumnos (`/alumnos`)** | `.filtros-row` | Parejos | Parejos | ✅ OK |
| **Cobranza (`/cobranza`)** | `.filtros-row` | Parejos | Parejos | ✅ OK |
| **Cashflow (`/cashflow`)** | `.filtros-row` | Parejos | Parejos | ✅ OK |
| **Grupos (`/grupos`)** | `.filtros-row` | Parejos | Parejos | ✅ OK |
| **Movimientos (`/movimientos`)** | `.filtros-row` | Parejos | Parejos | ✅ OK |
| **Inicio Operativo (`/operativo`)** | `#caja-card` | Sin desborde | Sin desborde | ✅ OK |

---

## 4. Script de Medición Automatizado

Para evitar regresiones en futuros despliegues, se creó la herramienta CLI:
```bash
node scripts/medir-ancho-movil.mjs http://127.0.0.1:8088
```
El script inicia Chrome headless con emulación móvil de 360px, realiza login y recorre las rutas críticas verificando que `scrollWidth <= innerWidth` y que los anchos de los controles en `.filtros-row` sean uniformes.

---

## 5. Lo que NO se verificó / Límites de Alcance
* **Dispositivos físicos reales:** La verificación se ejecutó en motor Chromium headless con Device Metrics Override a 360x740, 375x667 y 320x568. La prueba en el teléfono físico de Carlos queda pendiente para cuando Claude despliegue en el sitio de prueba.
* **Modificación de archivos Blade:** No se modificó ningún archivo `.blade.php` para cumplir la regla estricta de no agregar CSS manual en el HTML.
