# A14/A27/A53 — Diagnóstico y preparación del diseño

07/10/2026, Codex CyE. Base de código `ed30215`; el antecedente `1dcf02f` agrega el cierre
documental de A15/A16 tras el control de Gemini. `git pull --ff-only`: sin novedades al iniciar. Base descartable
`wings_testing_codex`; sin servidor, producción ni datos del club.

**Diseño aprobado por Carlos el 07/10/2026, después de abrir la comparación.**
Autorización textual: «Ahi vi el archivo antes y despues y parece que esta bien. Doy el OK».
Antes había aprobado el enfoque A27/A53 y pedido que el cartel A14 se actualice al cambiar
los datos, sin JavaScript en el HTML. Esa condición se implementó en
`resources/js/form-errors.js` y se comprobó en pantalla. La aprobación se registra con
sus palabras en el commit del paquete; no se atribuye al autor una autorización nueva.
A14/A27/A53 pasan a verificación independiente; no se cierran. Sin despliegue.

## Capturas reales disponibles

[Visor de la comparación completa](evidencia/a14-a27-a53/visor-comparacion.html)
· [34 capturas ANTES](evidencia/a14-a27-a53/visor-antes.html).
El visor comparativo enlaza cada archivo original ANTES/DESPUÉS, móvil y escritorio.
Imágenes del navegador sobre respuestas HTTP de Laravel; no maquetas de Wings.
Marco original `capturas-cashflow/marco-375.html`, ancho CSS 375 y alto 667.
El JPEG móvil contiene también medio píxel de margen por cada lado (376 píxeles
de archivo); el ancho de maquetación del iframe es 375. Archivos de escritorio 1280 × 613;
el control del navegador pidió 1280 × 900, pero el raster exportado tiene 613 de alto.
No se presenta la altura solicitada como tamaño real del archivo.
El login se capturó primero como control del método.

| Defecto / control | Captura móvil exacta | Escritorio exacto |
|---|---|---|
| Login | [375](evidencia/a14-a27-a53/capturas/login-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/login-antes-desktop.jpg) |
| A14: alta Alumno ADMIN | [375](evidencia/a14-a27-a53/capturas/admin-alumnos-alta-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-alumnos-alta-antes-desktop.jpg) |
| A14: edición Alumno ADMIN | [375](evidencia/a14-a27-a53/capturas/admin-alumnos-editar-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-alumnos-editar-antes-desktop.jpg) |
| A14: alta Alumno OPERATIVO | [375](evidencia/a14-a27-a53/capturas/operativo-alumnos-alta-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-alumnos-alta-antes-desktop.jpg) |
| A14: edición Alumno OPERATIVO | [375](evidencia/a14-a27-a53/capturas/operativo-alumnos-editar-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-alumnos-editar-antes-desktop.jpg) |
| A14: alta Profesor | [375](evidencia/a14-a27-a53/capturas/admin-profesores-alta-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-profesores-alta-antes-desktop.jpg) |
| A14: edición Profesor | [375](evidencia/a14-a27-a53/capturas/admin-profesores-editar-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-profesores-editar-antes-desktop.jpg) |
| A27: Movimiento ADMIN | [375](evidencia/a14-a27-a53/capturas/admin-movimiento-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-movimiento-antes-desktop.jpg) |
| A27: Movimiento OPERATIVO | [375](evidencia/a14-a27-a53/capturas/operativo-movimiento-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-movimiento-antes-desktop.jpg) |
| A53: Grupos ADMIN | [375](evidencia/a14-a27-a53/capturas/admin-grupos-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-grupos-antes-desktop.jpg) |
| A53: Grupos OPERATIVO | [375](evidencia/a14-a27-a53/capturas/operativo-grupos-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-grupos-antes-desktop.jpg) |
| A53: selector ADMIN | [375](evidencia/a14-a27-a53/capturas/admin-selector-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-selector-antes-desktop.jpg) |
| A53: selector OPERATIVO | [375](evidencia/a14-a27-a53/capturas/operativo-selector-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-selector-antes-desktop.jpg) |
| Error POST Alumno | [375](evidencia/a14-a27-a53/capturas/admin-alumno-error-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-alumno-error-antes-desktop.jpg) |
| Error POST Profesor | [375](evidencia/a14-a27-a53/capturas/admin-profesor-error-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/admin-profesor-error-antes-desktop.jpg) |
| Error POST Movimiento | [375](evidencia/a14-a27-a53/capturas/operativo-movimiento-error-antes-375.jpg) | [1280](evidencia/a14-a27-a53/capturas/operativo-movimiento-error-antes-desktop.jpg) |

Detalles adicionales: [tarifas grandes](evidencia/a14-a27-a53/capturas/admin-grupos-detalle-antes-375.jpg)
y [error perdido al llegar al pie](evidencia/a14-a27-a53/capturas/admin-alumno-error-abajo-antes-375.jpg).

## Causa comprobada y solución local

- **A14:** las cuatro vistas de alta/edición de Alumnos/Profesores colocan las acciones
  después de `_form`; no tienen posición fija. El resumen de Alumno está antes del
  formulario y también participa del desplazamiento normal. En esta reproducción,
  Cancelar/Guardar de alta Alumno comienzan en y=1345. Al enfocar Teléfono del tutor
  aparecen las acciones; el resumen dejó de verse, como muestra la captura del pie.
- **A27:** `caja/movimiento.blade.php` coloca Cancelar/Registrar después de todos los
  campos. El marco 667 vuelve a mostrar el formulario sin esas acciones. Observaciones
  sigue opcional: este cambio no debe alterar la validación ni el registro financiero.
- **A53:** `grupos/index` y `caja/cobrar-cuota` siguen usando `info-value ds-truncate`
  en tarifas/grupo. La regla global combina `white-space: nowrap`, `overflow: hidden`
  y elipsis. La tarifa de tres frecuencias se corta; el selector corta el nombre de grupo.

A14/A27 comparten causa: se propone una barra móvil de acciones, con espacio reservado
al pie para alcanzar los últimos campos. El resumen de errores quedaría pegado bajo
la barra superior, con altura limitada y desplazamiento propio si hay muchos errores.
Profesor/Movimiento necesitan un resumen móvil de errores del POST; Alumno conserva
su aviso y su validación existentes.

A53 necesita otra regla: permitir varias líneas únicamente en esas dos tarjetas,
sin ocultar precios ni nombres y sin desbordar la página. No se reducirá la letra.

## Alcance previsto, antes de tocar

[Inventario completo de consumidores](evidencia/a14-a27-a53/alcance-antes.json),
contrastado con MCP y `git grep`: **40 vistas** usan `filtros-actions`; **5** usan
`ds-truncate` (Alumnos índice, Grupos índice, Profesores índice, selector e Historial).
No se propone modificar esas reglas globales ni `ds-btn` ni los botones existentes.

Selectores nuevos, activados expresamente: `mobile-form`, `mobile-form-actions`,
`mobile-error-summary` y `mobile-readable-card`. Reglas móviles hasta 768;
la sustitución de cuatro atributos inline de las acciones conservaría su borde y
alineación actuales en escritorio. Sin estilos inline nuevos, Alpine ni Livewire.

Vistas cambiadas localmente: `alumnos/create`, `alumnos/edit`, `alumnos/_errores`,
`profesores/create`, `profesores/edit`, `caja/movimiento`, `grupos/index`,
`caja/cobrar-cuota`. Componente nuevo propuesto: `components/ds/mobile-errors`.
Las ocho vistas y ese componente son las únicas consumidoras previstas de las reglas.
Archivo CSS previsto: `resources/css/app.css`, sin cambiar las reglas compartidas anteriores.

No se modificarán ni publicarán `resources/views/clases/**` ni `clases-form.js`.
PROFESOR: control de regresión con clase de 20 alumnos y 20 casillas Presente comprobadas.
[375](evidencia/a14-a27-a53/capturas/profesor-clase-control-despues-375.jpg)
· [escritorio](evidencia/a14-a27-a53/capturas/profesor-clase-control-despues-desktop.jpg).

## T1 y pendientes de aceptación

Las capturas nuevas cubrirían las dos tarjetas de Grupos/selector y el formulario de
Movimiento. No certifican Caja índice, Cobrar alumno ni los recortes de Clases.
`grupos/show` no cambiaría; su par de capturas propias ya está en la verificación del
06/10. No se rehacen las 102 ni se da T1 por cerrado.

Preparación final del escenario: **1 aprobada, 11 aserciones, 6,38 s**; ensayo fuera de
la suite permanente, base Codex. Datos: 20 alumnos, nombres largos y tarifas millonarias.
La caja ficticia usa el día real: se quitó el reloj congelado del ensayo base. Se repitieron
las capturas que mostraban la redirección por caja antigua, el recorte o una imagen gris.
Para el ANTES se restituyeron temporalmente solo los archivos propios a HEAD y se compiló
Wings original; después se reaplicó exactamente el parche local y se compiló el DESPUÉS.
Se restableció el control de viewport antes de cada captura para evitar la superficie vieja.

## Cartel dinámico comprobado

- POST real de Profesor vacío: ocho errores. Completar Nombre deja siete; se retira también
  el mensaje viejo bajo ese campo. [Edición](evidencia/a14-a27-a53/capturas/profesor-cartel-editado-despues-375.jpg).
- Completar todos los obligatorios: cartel oculto. Volver a vaciar Nombre: aparece solo ese
  error. [Reaparición](evidencia/a14-a27-a53/capturas/profesor-cartel-un-error-despues-375.jpg).
- POST real con DNI existente: rechazo del servidor. Editar ese DNI cambia el aviso a
  «dato modificado; se comprueba al guardar», sin afirmar unicidad ni seguir mostrando el
  error anterior bajo el campo. [Pendiente](evidencia/a14-a27-a53/capturas/profesor-dni-pendiente-despues-375.jpg).
- Alumno al pie: cartel a y=60 y acciones a y=610,4, ambos dentro del marco.
  [375](evidencia/a14-a27-a53/capturas/admin-alumno-error-abajo-despues-375.jpg).
- Movimiento y sus errores: Registrar/Cancelar visibles en ADMIN y OPERATIVO.
- Grupos: las tres frecuencias millonarias se leen completas en varias líneas.
  [Detalle](evidencia/a14-a27-a53/capturas/admin-grupos-detalle-despues-375.jpg).
- El código nuevo del cartel está fuera de Blade; no se agregan scripts inline. No se
  extrajeron los scripts anteriores del formulario de Profesor, ajenos al cartel.
- Escritorio: 16 pares inspeccionados. Se conserva grilla y posición de las acciones;
  seis pares son idénticos como raster. Los demás tienen diferencias de puntero/representación
  tipográfica; no se certifica identidad píxel a píxel. [Comparación](evidencia/a14-a27-a53/comparacion-desktop.json).

Verificado: **74 capturas**, sus archivos completos inspeccionados y las interacciones anteriores.
Inferido: apariencia con teclado virtual real de teléfono; el marco no lo reproduce.
Suite completa: **489 aprobadas / 2 omitidas, 3924 aserciones, 257,64 s**, base Codex,
07/10. [Salida](evidencia/a14-a27-a53/suite-completa.txt). Build correcto; nueve vistas
sin errores de sintaxis; `view:cache` y `view:clear` correctos. No cambia el número de pruebas.

Siguiente: asignar verificador independiente a cada defecto tras publicar el paquete
aprobado. Ningún defecto se cierra por su autor; T1 conserva los pendientes indicados.
