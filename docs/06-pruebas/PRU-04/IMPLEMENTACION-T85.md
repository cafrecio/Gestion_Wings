# Implementación T85 — El interruptor Activo/Inactivo no acepta un segundo toque mientras espera

**Fecha:** 27/10/2026  
**Autor:** Gemini CAB  
**Estado:** `a_verificar`  
**Verificador asignado:** Claude  

---

## 1. Contexto y problema

El 27/10/2026, durante la prueba por días en el sitio de prueba, el alumno Agustín Acosta recibió una cuota indebida de reingreso por \$14.000 debido a un doble toque rápido sobre el interruptor de estado.

El interruptor enviaba una petición `PATCH` al servidor solicitando alternar el estado (`!$alumno->activo`). Al hacer dos clics consecutivos en rápida sucesión:
1. La primera petición desactivaba al alumno.
2. La segunda petición reactivaba al alumno. Al reactivarlo, el servicio `PagoCuotaService::crearCuotaReingreso` detectaba que el alumno no poseía cuota en ese período y generaba automáticamente la cuota del mes en curso con porcentaje de reingreso.

### Decisión de Carlos (27/10/2026)
> «Mientras se espera la respuesta del servidor, el interruptor no acepta otro toque. No se pide nada más:
> - no cambiar la ruta ni el controlador para mandar el estado deseado;
> - no agregar una regla de «deshacer el mismo día».»

---

## 2. Alcance e inventario de interruptores

El manejador vive de forma centralizada en [`resources/js/ds-app.js`](../../../resources/js/ds-app.js) y aplica a cualquier `.ds-toggle[data-url]`.

Mediante `codebase-memory-mcp` se confirmó que las vistas del sistema que utilizan este componente son:
1. **Listado de Alumnos:** [`resources/views/alumnos/index.blade.php`](../../../resources/views/alumnos/index.blade.php) (tarjeta `.alumno-card`)
2. **Ficha del Alumno:** [`resources/views/alumnos/show.blade.php`](../../../resources/views/alumnos/show.blade.php) (cabecera de ficha, T84)
3. **Deportes:** [`resources/views/deportes/index.blade.php`](../../../resources/views/deportes/index.blade.php)
4. **Grupos:** [`resources/views/grupos/index.blade.php`](../../../resources/views/grupos/index.blade.php)
5. **Profesores:** [`resources/views/profesores/index.blade.php`](../../../resources/views/profesores/index.blade.php)
6. **Usuarios:** [`resources/views/usuarios/index.blade.php`](../../../resources/views/usuarios/index.blade.php)

El arreglo en `ds-app.js` protege de manera inmediata y uniforme a los 6 módulos.

---

## 3. Cambios implementados en `resources/js/ds-app.js`

1. **Bloqueo inmediato contra reentrancia (`guardando` e `input.disabled`):**
   - Se introdujo una variable de estado booleana `guardando = false` por instancia de toggle.
   - Se añadió un listener de captura en el `<label class="ds-toggle">`:
     ```javascript
     label.addEventListener('click', function (e) {
         if (guardando) {
             e.preventDefault();
             e.stopPropagation();
         }
     });
     ```
   - Al dispararse el evento `change`:
     ```javascript
     if (guardando) return;
     guardando = true;
     input.disabled = true;
     ```
   - Cualquier segundo clic, toque táctil o pulsación de teclado (Espacio/Enter) mientras la petición está en vuelo es completamente descartado y no emite ninguna petición de red adicional.

2. **Reversión completa y actualización de accesibilidad en errores:**
   - Se guarda el estado previo: `var estadoPrevio = !input.checked;`.
   - Se actualiza `aria-checked` inmediatamente al nuevo valor: `input.setAttribute('aria-checked', input.checked ? 'true' : 'false');`.
   - En caso de error del servidor (`422`, `data.error`), error de red (`catch`), o respuesta que no sea JSON (redirección HTML a login por sesión vencida o error 500):
     - Se revierte el checkbox: `input.checked = estadoPrevio;`.
     - **Se corrige la omisión detectada en T84:** Se actualiza `aria-checked` al estado previo: `input.setAttribute('aria-checked', estadoPrevio ? 'true' : 'false');`.
     - Se destraba el interruptor: `input.disabled = false; guardando = false;`.
     - Se muestra el banner de error `.ds-flash--error` en `.ds-content`.

3. **Destrabe en éxito:**
   - En la ficha del alumno, tras guardar el mensaje en `sessionStorage`, se recarga la página (`window.location.reload()`) para refrescar las tablas de cuotas y asistencias.
   - En los listados (alumnos, grupos, deportes, profesores, usuarios), se actualiza la opacidad si corresponde, se muestra el banner de éxito y se destraba el interruptor (`input.disabled = false; guardando = false;`).

4. **Aspecto visual:**
   - Se verificó [`resources/css/app.css`](../../../resources/css/app.css): no existen selectores `:disabled` para `.ds-toggle` (solo para `.ds-btn` y `.wings-btn`).
   - No se aplicó la clase `.ds-toggle--disabled` durante la espera para no alterar la opacidad ni el cursor durante los milisegundos de la petición. El aspecto visual **no cambia**.

---

## 4. Evidencia de pruebas en navegador real (Chrome / CDP)

Las pruebas se ejecutaron sobre Chrome headless con el script [`docs/06-pruebas/PRU-04/evidencia-t85/probar-t85-browser.mjs`](evidencia-t85/probar-t85-browser.mjs), monitoreando todas las peticiones a nivel de red con el dominio `Network` de CDP:

### Prueba A: Doble clic rápido en listado de Alumnos (`/alumnos`)
- Se dispararon dos eventos `click()` en inmediata sucesión (< 10 ms de intervalo) sobre el interruptor de Alan Acosta.
- **Resultado en Red:** Se envió **exactamente 1 petición `PATCH`** a `/alumnos/48/toggle-activo`.
- El segundo clic fue ignorado. El alumno quedó inactivo (`activo = 0`), sin cuotas creadas.
- **Capturas:**
  - Escritorio (1366 px): [`01-listado-doble-clic-1366.png`](evidencia-t85/01-listado-doble-clic-1366.png)
  - Celular (375 px): [`02-listado-doble-clic-375.png`](evidencia-t85/02-listado-doble-clic-375.png)

### Prueba B: Doble clic rápido en Ficha del Alumno (`/alumnos/3`)
- Se dispararon dos clics consecutivos en la cabecera de la ficha.
- **Resultado en Red:** Se envió **exactamente 1 petición `PATCH`** a `/alumnos/3/toggle-activo`.
- El alumno fue desactivado, la página se recargó con el banner «Alumno desactivado correctamente» y no se generó ninguna cuota duplicada.
- **Capturas:**
  - Escritorio (1366 px): [`03-ficha-doble-clic-1366.png`](evidencia-t85/03-ficha-doble-clic-1366.png)
  - Celular (375 px): [`04-ficha-doble-clic-375.png`](evidencia-t85/04-ficha-doble-clic-375.png)

### Prueba C: Error forzado (alumno sin plan), reversión y destrabe
- Alumno inactivo sin plan asignado para el mes en curso (`/alumnos/63`).
- Al pulsar el toggle, el servidor rechazó la reactivación con HTTP 422: `{"error": "El alumno no tiene un plan para este mes. Asigná un plan antes de reactivarlo."}`.
- **Resultado verificado:**
  - `checked`: `false` (revertido).
  - `aria-checked`: `'false'` (actualizado).
  - `disabled`: `false` (destrabado, listo para volver a interactuar).
  - Mensaje visible: Banner de error `.ds-flash--error` con el texto exacto devuelto por el servidor.
- **Capturas:**
  - Escritorio (1366 px): [`05-error-forzado-revertido-1366.png`](evidencia-t85/05-error-forzado-revertido-1366.png)
  - Celular (375 px): [`06-error-forzado-revertido-375.png`](evidencia-t85/06-error-forzado-revertido-375.png)

---

## 5. Resultados de pruebas automatizadas y compilación

- **Compilación de assets:** `npm run build` ejecutado exitosamente con Vite 7.3.6 (`public/build/assets/app-DOC7vG8b.js`).
- `tests/Feature/FichaToggleActivoT84Test.php`: **2 aprobadas** (11 aserciones).
- `tests/Feature/CspSinCodigoIncrustadoTest.php`: **2 aprobadas** (cero `<script>` y cero eventos inline en Blade).
- `tests/Feature/TableroAlDiaTest.php`: **6 aprobadas**.
- `tests/Feature/DocumentacionNoMienteTest.php`: **1 aprobada**.
- Suite rápida (`--exclude-group=lento`): **939 aprobadas, 6 omitidas, 0 fallidas** (7.629 aserciones).

---

## 6. Próximo paso

- Tarea T85 en estado `a_verificar`, asignada a `Claude` para su revisión técnica.
