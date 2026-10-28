# T62 · Cancelar y reactivar una clase: aviso por lista tomada, choque y motivo conservado

> Prompt para Gemini, escrito por Claude CAB el 28/10/2026. Carlos lo copia y se lo pasa a Gemini.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Antes de tocar vistas, leer `docs/03-diseno-ui/wings-design/SKILL.md` y `docs/03-diseno-ui/design-system/DESIGN-RULES.md`. Marcar T62 `en_curso, tiene=Gemini` con `php scripts/tablero/tablero.php cambiar ...`.

**Qué decidió Carlos (DU-30 y DU-31, 13/10).** Casos CLA-012 y CLA-015 en `docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/CLA.json`.

**Cómo está hoy** (leído por Claude en `ClaseWebController::toggleCancelada`, líneas 589-646):

- Se cancela con motivo obligatorio y sin mirar si la clase tiene lista tomada.
- Lo de la liquidación cerrada o pagada ya está resuelto (T90, línea 605): no tocarlo.
- Al reactivar, `motivo_cancelacion` se pone en `null` (línea 639) y no se controla si la profesora ya tiene otra clase a esa hora.

**Qué hay que hacer**

1. **CLA-012.a.** Si la clase tiene asistencia registrada, presente o ausente, avisar antes de cancelar que la lista ya está tomada y pedir confirmación. Vale también para la cancelación de la serie: decir cuántas clases de la serie tienen lista.
2. **CLA-012.b y c.** Comprobar con pruebas que, al confirmar, las marcas quedan guardadas y la clase deja de contar para la liquidación, la comisión y la generación del mes siguiente. Es lo que hace hoy: verificarlo, no cambiarlo. Si no se cumple, informarlo antes de corregir.
3. **CLA-015.b.** Al reactivar, si alguna profesora de la clase ya tiene otra clase activa que se superpone en horario, no reactivar y explicar con cuál choca. Usar `ClaseService::verificarDisponibilidadProfesor` (línea 331) si sirve; leer su cuerpo antes de usarlo.
4. **CLA-015.c.** Al reactivar, conservar que estuvo cancelada, con su motivo, y que se vea en la ficha de la clase. Cómo se guarda lo decidís vos; no se borra.
5. **CLA-015.a y d.** Comprobar con pruebas que la clase vuelve a programada, permite tomar asistencia y vuelve a contar en el encabezado y en «Sin asistencia» si ya pasó.

**Reglas**

- Sin JavaScript dentro de la vista; antes de escribir, buscar en `ds-app.js` si ya existe lo necesario.
- Botones de una palabra, salvo que una sola no describa la acción. Panel de confirmación con el formato de botón del sistema (`ds-btn ds-btn--...`): es justo el defecto que tuvo T65.
- Tocar solo lo de T62. Lo que encuentres de más se anota como mejora futura: el alcance hasta producción está cerrado.

**Pruebas y evidencia**

- Pruebas nuevas para cada punto.
- Suite rápida con `DB_DATABASE=wings_testing_gemini`.
- Capturas reales en Chrome, a 1366 y 375 px, del aviso de lista tomada, del rechazo por choque y del motivo conservado.
- Galería `docs/06-pruebas/PRU-04/APROBAR-T62.html` y entrega en `IMPLEMENTACION-T62.md`.
- Actualizar el contador de `ESTADO-ACTUAL.md` solo con tus pruebas.

**Cierre.** T62 `a_verificar, verifica=Claude`. Firmar en `LOG-GEMINI.md`. Commit: `git status`, agregar por nombre, nunca `git add -A`; si un archivo propio tiene cambios ajenos, frenar y avisar. Sin despliegue.
