# T12 y T13 · Etapa C, la última: el resto del dueño, la profesora y el **?** en todas las pantallas

> Prompt para Gemini, escrito por Claude CAB el 30/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Siguen vigentes
`PROMPT-GEMINI-T12-T13-ETAPAS.md` (método, cierre y reglas) y la sección 2 de
`PROMPT-GEMINI-T12-T13-ETAPA-B-DEVOLUCION.md` (el método entero, el `@if` de la vista y el camino
prohibido). Leer el control de la B: `VERIFICACION-T12-T13-ETAPA-B-SEGUNDA-CLAUDE.md`. Marcar T12 y T13
`en_curso, tiene=Gemini`.

## Qué decidió Carlos

La etapa B está aprobada (30/10). La C cierra el manual: páginas 37 a 49 del dueño, 50, 52, 53 y 54 de la
profesora, las pantallas que la lista no cubre y el **?** en todas las pantallas de Wings.

## Paso 0 · Dos arreglos antes de empezar

1. **El ? de Clases le da un error a la profesora.** `clases/index.blade.php:6` apunta a
   `consultar-clases-operativo`, una página de la operativa. La profesora entra a Clases, toca **?** y Wings
   responde «No tenés permisos para ver esta sección de la ayuda.» (`ManualService::buscarPagina`). En una
   pantalla que ven varios roles, el **?** elige la página según el rol, como ya hace `clases/show.blade.php:23`.
   **Prueba nueva:** para cada pantalla con **?** y para cada rol que la puede abrir, el enlace del **?**
   responde 200. Esa prueba tiene que fallar hoy y pasar con el arreglo.
2. **`validar-rechazar-caja`.** La ficha copió el `@if` de los botones incompleto: `caja/resumen.blade.php:72`
   pide además `efectivo_contado !== null`, y `CajaService.php:432` rechaza validar sin efectivo contado.
   Copiá la condición completa en la ficha y agregá a la página qué ve el dueño en una caja Cerrada sin
   efectivo contado. Desde ahora, el `if` se copia **literal**, no resumido.

## Etapa C

**Páginas de la lista:** 37 a 49 y 50, 52, 53 y 54 de `T12-LISTA-DE-PAGINAS.md`. Claude contrastó la lista
con las rutas: las pantallas existen. Igual, cada página se escribe sobre lo que hace Wings, no sobre lo que
dice la lista. Avisos de Claude, todos para comprobar antes de escribir:

- **43, profesores:** la lista dice «subrubro contable». Fijate si existe en el alta del profesor.
- **48, parámetros:** la lista dice «día de vencimiento, recargos punitorios por mora y plazos de pago».
  `configuraciones/index` es una vista que el índice del código no lee entera: leé el archivo.
- **49, usuarios:** la lista dice «reseteo de claves». Fijate qué permite de verdad el formulario de edición.
- **52, la profesora corrige una clase anterior:** Wings rechaza con «No se pueden modificar asistencias: la
  clase integra una liquidación cerrada.» (`ClaseWebController.php:376`). Junto con el motivo de corrección,
  son las dos reglas de esa página.
- **53, clases sin lista:** el contador del menú (`layouts/ds-app.blade.php:46` y `:111`). Fijate qué ve la
  profesora y qué ve el dueño.

**Pantallas sin página en la lista.** Hay cuatro pantallas de Wings que ninguna página cubre. Cada una
lleva su página, con el mismo método:

- crear y editar clases, solo el dueño (`clases/create`, `clases/edit`; incluí las series y **Eliminar serie**, que está en la ficha de la clase);
- configurar el mostrador (`caja/configuracion`, solo el dueño);
- el Inicio del dueño (`admin/dashboard`) y el de la operativa (`operativo/dashboard`): qué muestra y adónde
  lleva cada aviso.

Agregalas a `T12-LISTA-DE-PAGINAS.md` con número nuevo.

**El ? en todas las pantallas.** Hoy faltan en estas vistas, además de las del paso 0:
`admin/dashboard`, `operativo/dashboard`, `caja/index`, `caja/editar`, `caja/cobrar-cuota`,
`caja/cancelar-movimiento`, `caja/configuracion`, `cashflow/detalle`, `cashflow/eliminar`, `clases/create`,
`clases/edit`, `configuraciones/index`, `deportes/*`, `grupos/create`, `grupos/edit`, `grupos/show`,
`niveles/*`, `profesores/*`, `reportes/*`, `rubros/*`, `subrubros/*`, `tipos-caja/*` y `usuarios/*`. Para
encontrarlas:
`grep -rlE "@extends\('layouts\.(ds-app|app|panel)'\)" resources/views | xargs grep -L "ayuda-slug"`. No
llevan **?** el login, las páginas de error, los layouts ni la Ayuda. Cada **?** apunta a la página de esa
tarea para el rol que mira la pantalla. **Al terminar, ese comando no devuelve otra pantalla que esas.**

## Cierre

1. `php scripts/manual/verificar-manual.php` en **0 problemas** en todo el manual.
2. Fichas con la columna «Condición en el código», con el `if` literal y el `@if` de la vista.
3. Tu revisor independiente sobre todas las páginas de la C y las cuatro nuevas, con `REVISOR-MANUAL.md`.
   Pedile que lea cada método entero y el `@if` de la vista. Repetir hasta que no quede nada grave ni medio, y
   guardar cada informe.
4. Pruebas: la del paso 0 y las del **?** nuevo; suite rápida en `wings_testing_gemini` y contador de
   `ESTADO-ACTUAL.md` solo con tus pruebas.
5. Galería `APROBAR-T12-T13-ETAPA-C.html`. Las capturas de la profesora, con el usuario de la profesora: el
   menú lo delata.
6. **Frenar.** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa C: control de Claude y OK
   de Carlos». Firma en `LOG-GEMINI.md` y commit.

## Reglas

- Ejecutá en tu base local, nunca en el sitio de prueba.
- Los huecos de Wings se listan en «Huecos para Carlos», no se arreglan.
- Sin JavaScript dentro de las vistas; botones de una palabra; agregar el **?** es el único cambio de diseño
  autorizado.
- Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios ajenos,
  frenar y avisar. Sin despliegue.
