# T12 y T13 · Etapa A, segunda vuelta: tres páginas que no cuentan lo que pide Wings

> Prompt para Gemini, escrito por Claude CAB el 29/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Sigue vigente
`PROMPT-GEMINI-T12-T13-ETAPAS.md`. Leer el control: `VERIFICACION-T12-T13-ETAPA-A-SEGUNDA-CLAUDE.md`. Marcar
T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó

Lo que te devolví está bien corregido. **Un punto de mi devolución estaba mal, y es mío:** te dije que
«excede los días de su plan» no existía, y existe. Además, tres páginas no cuentan avisos que la operativa
ve todos los días, y tu revisor dio 0. No vuelve la etapa entera: vuelven estas páginas y un control más.

## 1. Corregir

1. **`tomar-lista-mostrador.md`:** volvé a poner el exceso del plan, ahora bien contado. Si un presente ya vino
   esa semana las veces de su plan, la lista muestra «Excede el plan — indicar motivo» con «Clase adicional
   (EXTRA)» y «Recupera clase perdida (RECUPERA)». Si no se elige, queda EXTRA, y no traba el guardado
   (`clases/show.blade.php:497-505`, `ClaseWebController.php:471-473`). Es otra cosa que el motivo de deuda:
   dejá los dos mensajes de B3 como están.
2. **`registrar-gasto-caja.md`:** un gasto en efectivo mayor que lo que hay en el cajón no se guarda, ni al
   cargarlo ni al editarlo: «El gasto supera el efectivo disponible en el cajón. Primero registrá un aporte de
   efectivo y después el gasto.» (`CajaService.php:614-615`). Agregalo como «Qué hago si».
3. **`cobrar-pago-parcial.md`:** al tocar Cobrar con un importe menor que la cuota, Wings abre un panel y pide
   motivo: «Es un cobro parcial: queda parte de la cuota pendiente. Indicá el motivo; el dueño lo verá en
   Inicio y en el resumen diario.» Sin motivo no cobra. Va en el paso 3, y aclarar que Observaciones es otra
   cosa.
4. **`cobrar-cuota.md`:** un «Qué hago si» por cada aviso que puede aparecer dentro del cobro, con el texto de
   Wings: meses anteriores impagos (pide motivo; `ExcepcionCobranzaService.php:96`), cobro por adelantado
   (`CajaWebController.php:928`), fecha de un mes ya cerrado (`:900`) y alumno inactivo (remitir a
   `pasar-alumno-inactivo.md`).
5. **Códigos internos:** sacá «[DU-20]» del título y de la nota de `registrar-gasto-caja.md`, y «DU-27» de
   las otras páginas. La regla se dice en palabras.

## 2. El control que faltó

El que agregaste mira las condiciones **que la página dice**. Faltó el otro lado: **los rechazos y avisos que
la acción tiene**. Por cada una de las 20 páginas:

- Buscá en el controlador y el servicio de la acción cada `throw`, `withMessages`, `abort`, `->with('error'`
  y cada `'message'` de un panel de confirmación que pueda ver la operativa.
- Por cada uno: ¿la página lo cuenta, con el texto de Wings? Si no, agregalo o anotá por qué no corresponde
  (por ejemplo: solo lo ve el dueño, o no se alcanza desde la pantalla).
- En la ficha, una tabla nueva «Rechazos y avisos de la acción» con: mensaje, archivo:línea, ¿está en la
  página?

Un barrido grueso mío dio 48 de 50 mensajes sin su texto literal en el manual operativo; muchos son del
dueño o internos, y otros el manual los dice con otras palabras. Es por dónde empezar, no un resultado.

En la columna «Condición en el código» va un `if` o una regla de validación, no un `create(...)` ni la firma
de una función, y la fuente es el código, nunca la propia página del manual.

## 3. Cierre, igual que antes

1. `php scripts/manual/verificar-manual.php` en **0 problemas**; tus 18 pruebas en verde.
2. Revisor independiente con `REVISOR-MANUAL.md` actualizado con este control. Guardá el informe en
   `evidencia-t12-t13/revisiones/` y repetí hasta que no quede nada grave ni medio.
3. Si cambió alguna captura, actualizá `APROBAR-T12-T13-ETAPA-A.html`.
4. **Frenar.** T12 y T13 `a_verificar, tiene=Claude, verifica=Claude`, paso «Etapa A, segunda vuelta: control
   de Claude y OK de Carlos». Firma en `LOG-GEMINI.md` y commit. La etapa B no empieza sin el OK de Carlos.

## Reglas

- Los huecos de Wings se listan, no se arreglan.
- Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios
  ajenos, frenar y avisar. Sin despliegue.
