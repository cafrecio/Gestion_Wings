# T12 y T13 · Etapa B: las páginas del dueño (21 a 36), después de dos arreglos de la A

> Prompt para Gemini, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Sigue vigente
`PROMPT-GEMINI-T12-T13-ETAPAS.md`: método, cierre de cada etapa y reglas. Leer el control de la A:
`VERIFICACION-T12-T13-ETAPA-A-TERCERA-CLAUDE.md`. Marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué decidió Carlos

La etapa A está aprobada (29/10). Antes de empezar la B van dos arreglos menores de la A.

## Paso 0 · Dos arreglos de la etapa A

1. **El «Sí» de la tabla de rechazos solo va si la página lo dice.** Hay filas marcadas «Sí» que la página no
   cuenta:
   - `cobrar-cuota`: la ficha dice «hasta 6 meses», pero el código dice **12** (`MESES_ADELANTABLES`,
     `CajaWebController.php:34`). La página no dice el tope ni «Indicá el importe del mes que se cobra por
     adelantado.» (`:920`).
   - `alta-alumno`: «La fecha de ingreso no puede ser posterior al …» (`FechaIngresoRegla.php:42`).
   - `resolver-revision-cobranza`: la nota de al menos 5 caracteres (`RevisionCobranzaWebController.php:49`).

   Para cada uno: agregalo a la página o pasalo a «No» con el motivo. Después **revisá todas las filas «Sí»
   de las 20 fichas** y en cada una anotá la sección de la página donde está (por ejemplo, «Qué hago si: …»).
   Si no podés nombrar la sección, no es «Sí».
2. **Sacá de `cobrar-cuota.md` la frase sin fuente** «cobrar un mes por adelantado no habilita al alumno a
   tomar clases antes del primer día de ese mes». Wings no tiene esa regla.

## Etapa B

Las páginas 21 a 36 de `T12-LISTA-DE-PAGINAS.md`, salvo la 29, que ya está, y el **?** en sus pantallas.
Primera carga, validar y rechazar cajas, Cashflow, transferencias, la regla del dinero del dueño y
liquidaciones.

Desde la primera página:

- **La ficha de fuentes trae la tabla «Rechazos y avisos de la acción»** que se agregó en la A, con la regla
  del «Sí» del paso 0. No es un control para el final.
- **Sin códigos internos en títulos ni texto.** La lista todavía tiene «[DU-29]» en la 30 y «[DU-20]» en la 31:
  en la página, la regla se dice en palabras.
- **Página 31 (dinero del dueño):** antes de escribir, fijate qué hace Wings de verdad. En la caja, la
  operativa lo asienta como gasto (ya está en `registrar-gasto-caja.md`). Los subrubros tienen una
  clasificación «Retiro de los dueños» y otra «Aporte de los dueños» (`ClasificacionSubrubros.php`). La página cuenta eso,
  no una política inventada.
- **Página 32 (generar la liquidación):** hay un hueco conocido, **MF12**. En Nueva liquidación, el contador
  de clases pendientes cuenta también los meses de un profesor por comisión que ya fueron liquidados
  (`LiquidacionWebController::create`). Wings igual rechaza el duplicado: «Ya existe una liquidación para el
  profesor en MM/AAAA.» (`LiquidacionService.php:466`). La página no presenta ese número como confiable para un profesor por comisión, y el
  hueco va a «Huecos para Carlos» citando MF12. No se arregla.
- **Ejecutá en tu base local, nunca en el sitio de prueba:** Codex está corriendo ahí los días 29 a 33.

## Cierre

El de `PROMPT-GEMINI-T12-T13-ETAPAS.md` («Cómo cierra cada etapa»):

1. Verificador en **0 problemas** en todo el manual.
2. Revisor independiente con `REVISOR-MANUAL.md`, incluida la regla del «Sí» (agregala). Repetir hasta
   que no quede nada grave ni medio, y guardar cada informe.
3. Pruebas del **?** nuevo.
4. Galería `APROBAR-T12-T13-ETAPA-B.html`.
5. **Frenar:** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa B: control de Claude y
   OK de Carlos». Firma en `LOG-GEMINI.md` y commit.

Las decisiones de Carlos (por ejemplo MF12 y MF13) no se contestan en tu informe: son de él.

## Reglas

- Los huecos de Wings se listan, no se arreglan.
- Sin JavaScript dentro de las vistas; botones de una palabra.
- Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios ajenos,
  frenar y avisar. Sin despliegue.
