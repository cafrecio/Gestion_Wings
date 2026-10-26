# Implementación T84 — Ficha del alumno con interruptor Activo/Inactivo sin confirmación

**Fecha:** 26/10/2026  
**Responsable:** Gemini (Oficina CyE)  
**Verificador asignado:** Claude  

---

## 1. Contexto y decisión de Carlos (27/10/2026)

En la ficha del alumno (`resources/views/alumnos/show.blade.php`, líneas 55-62), existía un botón `Reactivar` envuelto en un formulario POST/PATCH con confirmación nativa del navegador (`data-confirmar`), visible únicamente cuando el alumno estaba inactivo. Al lado figuraba además una etiqueta `<span>` con el texto "Activo" o "Inactivo".

Por decisión de Carlos:
1. En la ficha de alumno se reemplaza el formulario y el botón `Reactivar` por el mismo componente `<x-ds.toggle>` utilizado en el listado (`alumnos/index.blade.php`), con `labelOn="Activo"`, `labelOff="Inactivo"` y `data-url="{{ route('web.alumnos.toggle-activo', $alumno->id) }}"`, visible tanto para alumnos activos como inactivos.
2. Se retira la etiqueta adyacente para no duplicar la indicación de estado.
3. Se elimina la confirmación nativa (`data-confirmar` / `confirm`), ya que la acción es simétrica y reversible de forma inmediata.
4. En `resources/js/ds-app.js`, se aprovecha el atributo `data.mensaje` devuelto por el controlador para mostrar un aviso de éxito (`.ds-flash--success`) en `.ds-content`.
5. En la ficha del alumno, tras cambiar el estado exitosamente, la página se recarga para reflejar deudas y cuotas generadas (regla de reingreso de T38), preservando el mensaje de éxito entre recargas mediante `sessionStorage`.
6. Se mantiene estricta la regla de cero JavaScript inline en Blade (`AGENTS.md` §5-bis).

---

## 2. Cambios realizados

### A. Vista de ficha de alumno (`resources/views/alumnos/show.blade.php`)
- Se removió el badge `<span style="...">{{ $alumno->activo ? 'Activo' : 'Inactivo' }}</span>`.
- Se removió el bloque condicional `@if(!$alumno->activo) <form ... data-confirmar="..."> <button>Reactivar</button> </form> @endif`.
- Se incluyó el interruptor:
  ```blade
  <x-ds.toggle
      labelOn="Activo"
      labelOff="Inactivo"
      :checked="(bool) $alumno->activo"
      data-url="{{ route('web.alumnos.toggle-activo', $alumno->id) }}"
  />
  ```

### B. JavaScript global (`resources/js/ds-app.js`)
- En el manejador del toggle (`.ds-toggle[data-url]`):
  - Al recibir respuesta exitosa con `data.mensaje`, si se encuentra en la ficha del alumno (`window.location.pathname.match(/\/alumnos\/\d+/)` sin tarjeta de listado `.alumno-card`), guarda el mensaje en `sessionStorage` y ejecuta `window.location.reload()`.
  - Para los demás toggles (listado de alumnos, deportes, grupos, etc.), renderiza directamente el banner `.ds-flash--success` en `.ds-content`.
  - Al cargar la página, comprueba si existe un mensaje en `sessionStorage` (`ds_flash_success`), lo remueve y lo inserta como `.ds-flash--success` en `.ds-content`.
  - Se compilaron los assets con `npm run build`.

---

## 3. Pruebas y verificación técnica

### Pruebas automatizadas
- Se creó `tests/Feature/FichaToggleActivoT84Test.php` con dos pruebas:
  1. `test_ficha_alumno_activo_muestra_toggle_sin_confirmacion_ni_boton_reactivar`: verifica presencia de `x-ds.toggle`, atributo `data-url`, estado checked, ausencia de `data-confirmar` y ausencia del botón `Reactivar`.
  2. `test_ficha_alumno_inactivo_muestra_toggle_sin_confirmacion_ni_form_reactivar`: verifica presencia de `x-ds.toggle`, atributo `data-url`, ausencia de `data-confirmar` y ausencia del botón `Reactivar`.
- Verificación de CSP y ausencia de código incrustado: `CspSinCodigoIncrustadoTest` en verde (0 bloques incrustados, 0 manejadores).
- Guardian de documentación: `DocumentacionNoMienteTest` en verde (corte actualizado a 1053 pruebas en `ESTADO-ACTUAL.md`, `CHECKLIST-CARLOS.md` y `PLAN-PRODUCCION.md`).
- Suite rápida: ejecutada con `DB_DATABASE=wings_testing_gemini php artisan test --exclude-group=lento`.

---

## 4. Evidencia visual para aprobación de diseño (Carlos)

Capturas reales generadas sobre el sistema funcionando con Chrome headless (en escritorio a 1366px y en dispositivo móvil dentro de marco iframe a 375px según protocolo `AGENTS.md` §1):

| Escenario | Escritorio (1366px) | Móvil (marco 375px) |
|---|---|---|
| **1. Alumno activo** (toggle "Activo" encendido) | [Ver captura 1366](evidencia-t84/01-ficha-alumno-activo-1366.png) | [Ver captura 375](evidencia-t84/02-ficha-alumno-activo-marco-375.png) |
| **2. Alumno inactivo** (toggle "Inactivo" apagado, sin botón Reactivar) | [Ver captura 1366](evidencia-t84/03-ficha-alumno-inactivo-1366.png) | [Ver captura 375](evidencia-t84/04-ficha-alumno-inactivo-marco-375.png) |
| **3. Alumno reactivado** (toggle "Activo" y aviso flash de cuota generada) | [Ver captura 1366](evidencia-t84/05-ficha-alumno-reactivado-cuota-1366.png) | [Ver captura 375](evidencia-t84/06-ficha-alumno-reactivado-cuota-marco-375.png) |

---

## 5. Próximo paso

Pase a verificación por Claude:
- Inspección del código en `resources/views/alumnos/show.blade.php` y `resources/js/ds-app.js`.
- Revisión de capturas visuales por Carlos para aprobación de diseño (`Diseno-autorizado`).
