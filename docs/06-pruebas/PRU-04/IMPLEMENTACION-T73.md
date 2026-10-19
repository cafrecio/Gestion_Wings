# T73 — Sin JavaScript dentro de las vistas

19/10/2026 · Claude CyE. Implementación local, a verificar en navegador. Sin cambios de aspecto.

Orden de Carlos (13/10): ningún código dentro de una vista (AGENTS.md §5-bis). Quedaban 8 bloques `<script>` en 8 vistas y 8 `onsubmit="return confirm(...)"`. Quedan **cero y cero**.

## Qué se hizo, por vista

| Vista | Antes | Ahora |
|---|---|---|
| `auth/login` | Bloque propio para el ojo de la contraseña | El botón usa `.btn-toggle-password` + `data-target`, que ya resuelve `ds-app.js` (lo mismo que usuarios). Los íconos suman `.icon-eye` / `.icon-eye-off`; se conservan sus `id`. |
| `grupos/index`, `profesores/index` | Bloque que enviaba el filtro al cambiar el deporte | `data-enviar-al-cambiar` en el selector, ya resuelto por `ds-app.js`. El selector está dentro del formulario de filtros. |
| `caja/editar` | Bloque de tipo/rubro/subrubro | `resources/js/caja-editar.js`. El rubro y subrubro actuales viajan en `data-actual` de cada selector. Dos variables que el bloque declaraba y no usaba (`subrubrosData`, `rubrosData`) no se trasladaron. |
| `clases/show` | Bloque de asistencia, cancelación y profesoras | `resources/js/clases-show.js`. Datos en un `div#clase-show-datos` oculto: id de la clase, info de la semana (JSON) y si se exige motivo. |
| `clases/index` | Bloque de ventana de hoy y filtros | `resources/js/clases-index.js`, sin datos de Blade. |
| `liquidaciones/create` | Bloque de profesor elegido, aviso e «incluir hoy» | `resources/js/liquidaciones-create.js`. Los profesores viajan en `div#liquidacion-create-datos` oculto. Sigue publicando `window.toggleIncluirHoy`, que usa `ds-app.js`. |
| `alumnos/index` | Bloque del buscador con sugerencias | `resources/js/alumnos-index.js`. La dirección de sugerencias va en `data-autocomplete-url` del buscador. |
| `grupos/show`, `liquidaciones/index`, `liquidaciones/show` (3), `niveles/index`, `rubros/index` (2) | `onsubmit="return confirm('…')"` | `data-confirmar-envio="…"` con el mismo texto. En rubros el nombre ya no pasa por `addslashes`: en un atributo alcanza con el escape de Blade. |

El código se trasladó tal cual: solo cambiaron las líneas que leían valores de Blade.

**`ds-app.js`** suma un manejador de `submit` en fase de captura para `form[data-confirmar-envio]`. Corre antes que el anti doble-submit: si la persona cancela, el envío queda cancelado y el formulario no se traba. Es el mismo criterio que ya usaba `caja-detalle.js` para su confirmación.

## Riesgo conocido

Si `app.js` no carga, un borrado se enviaría sin preguntar. Es el mismo riesgo de todos los `data-confirmar` que ya existían y de la confirmación de caja/detalle (T36): `app.js` se carga en todas las pantallas desde el layout.

## Pruebas

- `CspSinCodigoIncrustadoTest` baja a **0 bloques y 0 manejadores**.
- `AlumnoAutocompleteViewSecurityTest` lee ahora `alumnos-index.js` (las sugerencias siguen insertándose como texto, no como HTML) y exige que la vista cargue ese módulo.
- Suite rápida en `wings_testing_claude`: **819 aprobadas, 4 omitidas, 1 fallida** (esa prueba, antes de apuntarla al módulo); después, las dos de arriba en verde.
- `php -l` de las vistas, `node --check` de los módulos, `view:cache`/`view:clear` y `npm run build` correctos.

## Para verificar en un navegador real (con clics)

Lo que las pruebas automáticas no pueden comprobar, porque no ejecutan JavaScript:

1. Ingreso: el ojo muestra y oculta la contraseña.
2. Grupos y Profesores: cambiar el deporte filtra.
3. Caja → editar movimiento: Ingreso/Egreso cambia colores y filtra rubros; el rubro filtra subrubros; al abrir, quedan elegidos los actuales.
4. Ficha de clase: tildar presentes actualiza el contador y el aviso de exceso; Guardar; cancelar (motivo obligatorio); cambiar profesoras.
5. Clases: la ventana de hoy se ubica en la clase actual; los filtros envían.
6. Nueva liquidación: elegir profesor marca la fila y muestra el aviso sin asistencia; Incluir hoy / No incluir.
7. Alumnos: escribir dos letras muestra sugerencias; flechas, Enter y Escape.
8. Cada una de las 8 confirmaciones: Cancelar no envía y el botón sigue usable; Aceptar envía.
9. Consola del navegador sin errores y sin avisos de la política de seguridad en esas pantallas.
