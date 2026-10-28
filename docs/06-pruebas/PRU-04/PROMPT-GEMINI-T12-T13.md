# T12 y T13 · Manual del usuario y Ayuda dentro de Wings — primero dos muestras

> Prompt para Gemini, escrito por Claude CyE el 28/10/2026. Carlos lo copia y se lo pasa a Gemini.

*Inicio.* Aplicar docs/00-estado/PROTOCOLO-CONTINUIDAD.md y leer AGENTS.md. Antes de tocar vistas, leer docs/03-diseno-ui/wings-design/SKILL.md, docs/03-diseno-ui/design-system/DESIGN-RULES.md y resources/views/alumnos/index.blade.php. Antes de tocar permisos, docs/02-contratos/PERMISOS-ROLES.md. Marcar T12 y T13 en_curso, tiene=Gemini.

## Cómo se trabaja: dos etapas, con una parada en el medio

La tarea es grande. **No la hagas entera de una vez.** Primero armás la estructura mínima, dos páginas de muestra y la lista de todas las páginas, y **frenás**. Carlos las mira. Solo con su OK seguís con el resto. Si después de la etapa 1 seguís sin su respuesta, no avances.

## Qué decidió Carlos

- *T12, manual (10/10 y 13/10):* una tarea por página, con pasos numerados, una captura por paso y un apartado «Qué hago si…». Separado por rol. La primera carga es un capítulo aparte del dueño. Tiene que cubrir tres casos:
  - **DU-20:** el dueño nunca usa la plata del cajón; si la usa, lo registra la operativa en su caja.
  - **DU-27:** un movimiento mal cargado en una caja ya validada se corrige con un ajuste directo en Cashflow, con motivo.
  - **DU-29:** cómo registrar que se lleva efectivo al banco.
- *T13, ayuda (10/10 y 28/10):* una entrada «Ayuda» en el grupo **Sistema** del menú (resources/views/layouts/ds-app.blade.php, línea 241), y un **?** en cada pantalla que abre su página del manual. Con buscador y opción de imprimir. Cada rol ve lo suyo, y solo con sesión iniciada. **El ? abre las mismas páginas del manual: el contenido se escribe una sola vez.**
- *Capturas (regla de Carlos):* reales, tomadas de la pantalla efectiva de cada tarea. **Prohibido inventarlas, ilustrarlas o reusar la de otra pantalla.** Cada paso se hace en el sistema antes de escribirlo; si no se pudo hacer, se marca *pendiente* y no se rellena.

## Qué ve cada rol

- ADMIN ve todas las páginas: las suyas y las de la operativa. El dueño nunca tiene menos que la operativa.
- OPERATIVO ve las de la operativa.
- PROFESOR ve solo las suyas: clases y asistencia.

Forzando la dirección de una página que no le toca, cada rol recibe el mismo rechazo que en el resto de Wings.

## Dónde sacar las capturas

En **tu entorno local, con una base propia** (por ejemplo, `wings_manual_gemini`) armada con migraciones y los seeders de ejemplo de database/seeders. **Nunca** en el sitio de prueba (test.gestionar-te.com.ar): ahí corre la prueba por días y hacer los pasos le cambiaría los datos. Tampoco en la base de trabajo local ni en producción. Nombres y DNI de las capturas: los de prueba, nunca de personas reales.

---

## Etapa 1 · Estructura, dos muestras y la lista completa. Después, frenar.

1. **Estructura mínima de T13:** la entrada «Ayuda» en Sistema, la página de inicio de la ayuda con el índice por rol, y el **?** solo en las dos pantallas de las muestras. Imprimir tiene que funcionar ya: una página impresa se lee completa, sin el menú. El buscador queda para la etapa 2.
2. **Dónde vive el contenido:** cada página del manual en un archivo propio, fuera del código de las vistas, para poder agregar páginas sin programar. Cómo lo resolvés lo decidís vos; explicalo en la entrega.
3. **Dos páginas de muestra**, de punta a punta, con todo lo que va a tener cada página definitiva:
   - **Operativa: «Cobrar una cuota».** Es lo que más se usa.
   - **Dueño: «Corregir un movimiento mal cargado en una caja ya validada» (DU-27).** Es una tarea con reglas, para ver cómo se explica el porqué.
4. **La lista completa de páginas** que propones, separada por rol, con una línea por página que diga qué tarea cubre. Armala recorriendo el menú de cada rol y el catálogo de la tabla de pruebas (docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/), para que no falte ninguna tarea real. Marcá dónde van DU-20, DU-27 y DU-29, y el capítulo de primera carga.
5. **Entrega de la etapa 1:**
   - Galería docs/06-pruebas/PRU-04/APROBAR-T12-T13-MUESTRAS.html, con las dos páginas tal como se ven en Wings (1366 y 375 px), la vista de impresión y el ? en su pantalla.
   - La lista de páginas en docs/06-pruebas/PRU-04/T12-LISTA-DE-PAGINAS.md.
   - Informe en IMPLEMENTACION-T12-T13.md. Pruebas de acceso por rol (cada rol ve lo suyo, forzar la dirección se rechaza, sin sesión no entra).
   - T12 y T13 en el tablero con tiene=Carlos y paso «Etapa 1: OK de Carlos a las muestras y a la lista». Firma en LOG-GEMINI.md, commit y **frenar**.

## Etapa 2 · El resto, solo con el OK de Carlos

Con lo que Carlos corrija en las muestras y en la lista:

- Todas las páginas de la lista, con el mismo formato que las muestras aprobadas.
- El ? en cada pantalla que tenga página.
- El buscador.
- Galería docs/06-pruebas/PRU-04/APROBAR-T12-T13.html con todas las páginas, para el OK visual final.
- Las páginas cuyo paso no se pudo hacer quedan marcadas *pendiente* y van listadas en la entrega, no rellenadas.

---

## Cómo se escribe cada página

- Castellano rioplatense, de vos, frases cortas. Sin palabras técnicas: nada de «registro», «módulo», «sistema», «base de datos».
- El nombre de cada botón y campo, **exactamente** como aparece en pantalla, en negrita.
- Arriba, en una línea: para qué sirve la tarea y quién la hace.
- «Qué hago si…» con los errores reales que muestra Wings en esa pantalla (copiá el mensaje tal cual aparece) y qué hacer en cada caso.
- Si una regla tiene un porqué que evita errores (por ejemplo, por qué un cobro no se borra sino que se cancela con motivo), una frase que lo explique.

## Reglas

- Sin JavaScript dentro de la vista; antes de escribir, buscar en ds-app.js si ya existe lo necesario.
- Botones de una palabra, salvo que una sola no describa la acción. Formato de botón del sistema (ds-btn ds-btn--...).
- No cambiar nada del funcionamiento de las pantallas que documentás. Si al hacer un paso encontrás un defecto, anotalo en la entrega como hallazgo; no lo corrijas.
- Tocar solo T12 y T13.

## Pruebas y cierre

- Pruebas nuevas de acceso por rol y de las páginas, además de los guardianes (CspSinCodigoIncrustadoTest, TableroAlDiaTest, DocumentacionNoMienteTest).
- Suite rápida con DB_DATABASE=wings_testing_gemini.
- Actualizar el contador de ESTADO-ACTUAL.md solo con tus pruebas.
- Commit: git status, agregar por nombre, nunca git add -A; si un archivo propio tiene cambios ajenos, frenar y avisar. Sin despliegue.
- Al final de la etapa 2: T12 y T13 a_verificar, verifica=Claude.
