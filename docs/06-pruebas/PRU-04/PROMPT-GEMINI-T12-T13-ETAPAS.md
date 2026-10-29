# T12 y T13 · El resto del manual en tres etapas, con freno y control después de cada una

> Prompt para Gemini, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Siguen vigentes
`PROMPT-GEMINI-T12-T13.md` y, sobre todo, el **método** de `PROMPT-GEMINI-T12-T13-CORRECCION.md` §2:
ejecutar antes de escribir, ficha de fuentes, textos literales, «Qué hago si» con la regla a la vista,
verificador en 0 y revisor independiente. Todos esos puntos rigen en cada página. Marcar T12 y T13
`en_curso, tiene=Gemini`.

## Qué decidió Carlos

La corrección de las tres muestras está aprobada ([control de Claude](VERIFICACION-T12-T13-ETAPA1-CLAUDE.md)).
Las 51 páginas que faltan se hacen en **tres etapas**, con un freno después de cada una. Si una etapa se
desvía, se corrige esa etapa y no todo el manual.

## Paso 0 · Antes de empezar

1. **Los dos detalles del control** (ver el control de Claude): en `cobrar-cuota.md`, desde Cobranza el
   botón **Cobrar** va directo al cobro del alumno, no a la pantalla con el buscador. En
   `corregir-movimiento-caja-validada.md`, cargar la fecha del turno es una fecha pasada y Wings pide
   confirmarla (`confirmar_fecha_vieja` en `cashflow/movimiento.blade.php`); la página tiene que decirlo.
2. **Revisar la lista contra el código.** `T12-LISTA-DE-PAGINAS.md` describe funciones que Wings **no
   tiene**. Claude comprobó algunas: no hay exportación a Excel ni CSV (página 39; ninguna ruta), el deporte
   no tiene color (40; `Deporte::$fillable` es nombre, tipo de liquidación y activo), el grupo no tiene cupo
   ni edades (20 y 42), no hay adelantos en la liquidación (34; ninguna ruta) ni sedes (50). Además, «Dar
   de baja a un alumno» (7) mezcla dos cosas que Carlos separó el 28/10: pasar a **Inactivo** no es dar de
   baja; dar de baja es eliminar al alumno cargado por error (T65). Revisá **todas** las páginas así,
   no solo estas. Una página de algo que Wings no hace no se escribe: se reescribe para lo que Wings sí hace,
   o se saca de la lista. Anotá cada cambio, con `archivo:línea`, en `IMPLEMENTACION-T12-T13.md`.

## Las tres etapas

| Etapa | Páginas de la lista | Además |
|---|---|---|
| **A** | Operativa, 2 a 20 | El **buscador** de la Ayuda y el **?** en las pantallas de esas páginas |
| **B** | Dueño, 21 a 36, salvo la 29 que ya está | El **?** en sus pantallas |
| **C** | Dueño 37 a 49 y profesora 50, 52, 53 y 54 | El **?** en sus pantallas. Al terminar, ninguna pantalla de Wings queda sin su **?** |

Los números son los de la lista **después** de corregirla en el paso 0.

## Cómo cierra cada etapa

1. `php scripts/manual/verificar-manual.php` termina con **0 problemas** en todo el manual, no solo en
   las páginas nuevas.
2. **Lanzar tu revisor independiente** sobre todas las páginas de la etapa, siguiendo
   `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`: un subagente o una conversación nueva, sin el contexto de
   la que escribió. Corregís lo grave y lo medio, y lo volvés a lanzar hasta que no quede nada. Guardá
   cada informe en `evidencia-t12-t13/revisiones/`, también los que tuvieron hallazgos.
3. Pruebas de lo que sea código (buscador y **?**): pruebas nuevas, suite rápida en
   `wings_testing_gemini` y contador de `ESTADO-ACTUAL.md` solo con tus pruebas.
4. Galería `APROBAR-T12-T13-ETAPA-<A|B|C>.html` con las páginas de la etapa.
5. **Frenar.** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa X: control de Claude
   y OK de Carlos». Firma en `LOG-GEMINI.md`, commit, y no empezar la etapa siguiente sin el OK de Carlos.

Claude va a controlar contra el código algunas páginas de cada etapa, elegidas por él. Si en ese control
aparece un error grave, o más de un error medio por página, **vuelve la etapa entera**, no solo esas
páginas.

## Reglas

- Los huecos de Wings no se arreglan ni se rodean: se listan en «Huecos para Carlos» de
  `IMPLEMENTACION-T12-T13.md`, con `archivo:línea`.
- Sin JavaScript dentro de las vistas; botones de una palabra.
- Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios
  ajenos, frenar y avisar. Sin despliegue.
