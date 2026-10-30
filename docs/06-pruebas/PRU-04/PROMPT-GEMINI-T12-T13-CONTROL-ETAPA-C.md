# T12 y T13 · Control de la etapa C del manual (la hizo Claude) — Gemini

> Prompt para Gemini (Carlos, 30/10: controla Gemini con otro modelo), escrito por Claude CAB el 30/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó

Gemini se quedó sin crédito y Carlos le pidió a Claude que hiciera la etapa C del manual: 22 páginas nuevas (59 en total) y
el **?** en todas las pantallas. Claude no puede controlar su propio trabajo: el control final es tuyo. Hacelo con el método de `REVISOR-MANUAL.md`, sin el contexto de quien escribió. Detalle de lo hecho en
`docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md` §15. Galería: `APROBAR-T12-T13-ETAPA-C.html`.

## Qué controlar

1. **Páginas contra el código.** Elegí vos al menos 6 de las 22 (la lista está en `T12-LISTA-DE-PAGINAS.md`, 37 a 59), incluidas
   `admin/programar-clases`, `admin/administrar-grupos` y una de la profesora. Para cada condición de estado, rol, tipo o fecha
   que diga la página, buscá vos el `if` completo y el `@if` de la vista, como pide `REVISOR-MANUAL.md` punto 6. Un camino que
   Wings no permite es grave.
2. **El ? por rol, en pantalla.** En Wings local, con un usuario de cada rol, abrí al menos 8 pantallas que la etapa C tocó (por
   ejemplo Clases con la profesora, Caja con la operativa y con el dueño, Deportes, Usuarios, Reportes) y tocá el **?**: tiene que
   abrir la página de esa tarea, no un error. La prueba `tests/Feature/AyudaBotonPorRolTest.php` cubre las pantallas sin
   parámetros; mirá en especial las que tienen parámetros (ficha de grupo, ficha de clase, movimiento de Cashflow).
3. **Las capturas.** Se generaron con `docs/06-pruebas/PRU-04/evidencia-t12-t13/etapa-c/CapturasEtapaCTest.php`. Comprobá que
   ese archivo solo muestra paneles ocultos u oculta secciones, y que no agrega texto. Mirá que cada captura corresponda al rol
   de su página.
4. **Los huecos para Carlos** (§15, nueve): comprobá que existan de verdad. El 6 (sacar una frecuencia que tuvo alumnos da
   error) es inferido del código: ejecutalo en una base de prueba y anotá qué pasa.
5. **Las cinco revisiones** en `evidencia-t12-t13/revisiones/2026-10-30-etapa-c*.md`: que lo marcado como resuelto lo esté.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-ETAPA-C-GEMINI.md`: lo comprobado y lo inferido por separado, cada
  hallazgo con gravedad y `archivo:línea`.
- Si no hay graves ni medios: T12 y T13 `a_verificar, tiene=Carlos`, paso «Etapa C controlada por Gemini: OK de Carlos». Si los
  hay: `devuelto, tiene=Claude`.
- Pruebas solo en `wings_testing_gemini`. Sin despliegue. Commit: `git status`, agregar por nombre, nunca `git add -A`; si un
  archivo propio tiene cambios ajenos, frenar y avisar.
