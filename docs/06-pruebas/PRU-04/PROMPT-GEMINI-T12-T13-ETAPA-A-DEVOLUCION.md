# T12 y T13 · La etapa A vuelve: una regla mal escrita y una ficha que no la comprobó

> Prompt para Gemini, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Sigue vigente
`PROMPT-GEMINI-T12-T13-ETAPAS.md`. Leer el control de Claude: `VERIFICACION-T12-T13-ETAPA-A-CLAUDE.md`.
Marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó

El buscador y el **?** están bien y no se tocan. Las páginas no: `rectificar-caja.md` dice que se puede
rectificar una caja **Rechazada**, y Wings solo lo permite con la caja **Abierta**. La ficha de esa página
cita una línea que no es la regla y dice «Comprobado». El revisor no lo vio. Por la regla del prompt de
etapas, **vuelve la etapa entera**: si pasó en una de seis páginas controladas, puede estar en las otras.

## 1. Corregir lo encontrado

1. **`rectificar-caja.md` (grave).** Rectificar es solo con la caja **Abierta**
   (`CajaWebController.php:102` y `:113`, `CajaService.php:161`). Corregí el título, el resumen, la nota y
   el paso 1: no hay «alerta de observación» y la pantalla rectifica la apertura, no el cierre. Agregá el
   «Qué hago si» de la caja rechazada con lo que Wings deja hacer de verdad (mirá `caja/resumen.blade.php:65`).
2. **`tomar-lista-mostrador.md:35` (media).** No existe «excede los días de su plan». El motivo se pide si el
   alumno está **Deudor** o si es **nuevo, sin ningún pago de cuota, y ya tiene 2 presentes**
   (`ExcepcionCobranzaService.php:40-48`). Usá los dos mensajes de Wings tal cual.
3. **`pasar-alumno-inactivo.md` (menor).** Agregá el caso en que el cobro incluye el mes en curso: no aparece
   **No** (`CajaWebController.php:938-952`). Sacá «dar de baja temporal o definitiva», porque Inactivo no es
   baja, y no nombres tareas internas como «T65».

## 2. Revisar las otras páginas, con un control nuevo

Por cada una de las 20 páginas de la etapa, buscá **cada condición** de estado, rol o fecha que dice la
página («si la caja está…», «solo el dueño…», «si la clase ya pasó…») y hacé esto:

- **Encontrá vos el `if` que la decide** y copiá la condición en la ficha, en una columna nueva
  «Condición en el código». No alcanza `archivo:línea`: tiene que estar el texto del `if`.
- **Probá el camino prohibido** en Wings local: intentá la acción en el estado que la página dice que no se
  puede, o en el que no nombra. En «Qué pasó al ejecutarlo» va el mensaje que mostró Wings, o qué botón no
  apareció. «Comprobado» solo, sin eso, no vale.
- Cada estado que nombra la página tiene que estar en el `if`, y cada estado del `if` tiene que estar en la
  página.

Anotá en `IMPLEMENTACION-T12-T13.md` cuántas condiciones revisaste y cuáles cambiaron.

## 3. Cierre, igual que antes

1. `php scripts/manual/verificar-manual.php` en **0 problemas**.
2. **Lanzá de nuevo tu revisor independiente** sobre las 20 páginas, con `REVISOR-MANUAL.md` actualizado: el
   punto 6 nuevo le pide comparar cada condición contra el `if`, buscado por él. Guardá el informe en
   `evidencia-t12-t13/revisiones/` y repetí hasta que no quede nada grave ni medio.
3. Corregí en tu informe de entrega la frase «con ranking y snippet contextual»: el buscador filtra por
   palabras y nada más.
4. Si cambió alguna captura, actualizá `APROBAR-T12-T13-ETAPA-A.html`.
5. **Frenar.** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa A corregida: control de
   Claude y OK de Carlos». Firma en `LOG-GEMINI.md` y commit. La etapa B no empieza sin el OK de Carlos.

## Reglas

- Los huecos de Wings se listan, no se arreglan.
- Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios
  ajenos, frenar y avisar. Sin despliegue.
