# T12 y T13 · La etapa B vuelve: el ajuste de la liquidación y el `if` completo

> Prompt para Gemini, escrito por Claude CAB el 30/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Siguen vigentes
`PROMPT-GEMINI-T12-T13-ETAPAS.md` y la sección 2 de `PROMPT-GEMINI-T12-T13-ETAPA-A-DEVOLUCION.md`
(condición copiada del `if` y camino prohibido probado). Leer el control:
`VERIFICACION-T12-T13-ETAPA-B-CLAUDE.md`. Marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó

El paso 0 y el **?** están bien. `recalcular-ajustar-liquidacion.md` dice que el monto se ajusta en
cualquier liquidación y solo con la liquidación **Abierta**. Wings lo permite **solo a comisión**, y
**abierta o cerrada** sin pagar. La ficha cita una función que no existe y toma la regla general sin ver
la excepción. Las fichas de la B no traen la columna «Condición en el código». Vuelve la etapa entera.

## 1. Corregir lo encontrado

1. **`recalcular-ajustar-liquidacion.md` (grave).** Leé `Liquidacion::ajustarMontoFinal` entero
   (`Liquidacion.php:180-225`) y `liquidaciones/show.blade.php:236-262`. La página tiene que decir:
   - el ajuste existe solo para liquidaciones a **comisión**, no por hora;
   - se puede ajustar con la liquidación abierta **o cerrada**, mientras no esté pagada;
   - se abre con **Editar monto final** y pide **Motivo del ajuste**.

   Corregí el «Qué hago si… ya fue cerrada»: a comisión, se ajusta; por hora, lo que Wings deja de verdad.
   Sacá «anticipo»: Wings no tiene adelantos.
2. **`primera-carga-deshacer.md` (media).** Wings rechaza deshacer por cuatro motivos, no por uno
   (`PrimeraCargaExcelService.php:326-343`). El caso más común es que la profesora ya tomó lista. Contá cada
   rechazo con su mensaje.
3. **Comillas de código (menor, todo el manual).** El verificador ahora marca cada texto entre comillas de
   código: hoy son 57 en 18 páginas, incluidas tres de la A y la de la profesora. Un mensaje de Wings va
   entre «» y un ejemplo que escribe el usuario, entre comillas comunes. No nombres rutas (`/caja/{id}/detalle`):
   decí qué se toca.
4. **«Banco Galicia» (menor).** Las cuentas las configura cada club: decí «la cuenta», como con los medios
   de pago.
5. **MF12.** Sacalo de «Huecos para Carlos» en `IMPLEMENTACION-T12-T13.md`: Claude lo arregló el 29/10
   (`ac712594`). No es un error tuyo; el prompt de la B se escribió antes.

## 2. Revisar las 15 páginas del dueño

Para cada condición de estado, tipo, rol o fecha que diga una página:

- **Leé el método entero que la decide, no la primera excepción que encuentres.** Si hay un `if` general y
  otro que lo habilita en un caso (como `ajusteFinalEnCurso` en `Liquidacion.php:106-122`), los dos van a la
  ficha.
- **Columna «Condición en el código»** en la ficha, con el texto del `if`, como en la A. Sin eso la ficha no
  está terminada.
- **Mirá también el `@if` de la vista** que muestra el botón o el panel: el tipo de liquidación, el estado y
  el rol que lo hacen aparecer van a la página.
- Probá el camino prohibido en tu base local y anotá el mensaje de Wings.

## 3. Cierre

1. `php scripts/manual/verificar-manual.php` en **0 problemas**, con la regla nueva de las comillas de código.
2. Tu revisor independiente sobre las 15 páginas del dueño, con `REVISOR-MANUAL.md` (punto 6). Pedile
   expresamente que lea cada método entero y el `@if` de la vista. Repetir hasta que no quede nada grave ni
   medio, y guardar cada informe.
3. Si cambió alguna captura, actualizá `APROBAR-T12-T13-ETAPA-B.html`.
4. **Frenar.** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa B corregida: control de
   Claude y OK de Carlos». Firma en `LOG-GEMINI.md` y commit. La etapa C no empieza sin el OK de Carlos.

## Reglas

- Ejecutá en tu base local, nunca en el sitio de prueba.
- Los huecos de Wings se listan, no se arreglan.
- Commit: `git status`, agregar por nombre, nunca `git add -A`. Hoy hay cambios ajenos sin subir en
  `AlumnoWebController.php`, `alumnos/show.blade.php` y `lang/es/validation.php`: no son tuyos, no los
  agregues. Sin despliegue.
