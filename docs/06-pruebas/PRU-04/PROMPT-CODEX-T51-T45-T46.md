# T51, T45 y T46 · Rastro en caja, cambio de forma de liquidar y desactivar a una profesora

> Prompt para Codex, escrito por Claude CyE el 28/10/2026. Carlos lo copia y se lo pasa a Codex.

*Inicio.* Aplicar docs/00-estado/PROTOCOLO-CONTINUIDAD.md y leer AGENTS.md. Antes de tocar vistas, leer docs/03-diseno-ui/wings-design/SKILL.md y docs/03-diseno-ui/design-system/DESIGN-RULES.md. Antes de tocar permisos, docs/02-contratos/PERMISOS-ROLES.md.

*Cómo se trabaja.* Son tres tareas independientes, una detrás de otra y en este orden: **T51, T45, T46**. Cada una con sus pruebas, su entrega, su commit y su firma en LOG-CODEX.md. Al terminar cada una, pasala a a_verificar con verifica=Claude y avisale a Carlos con un resumen corto; después seguí con la siguiente sin esperar. Marcá en_curso solo la que estás haciendo.

Casos en docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/ y dudas en tabla-de-pruebas/dudas/.

---

## 1. T51 · Editar o eliminar un movimiento manual de caja deja rastro

*Qué decidió Carlos (DU-07).* Casos MOV-007 y MOV-008 (MOV.json). Que quede rastro **igual que en Cashflow**: toda corrección deja historia.

*Cómo está hoy* (leído por Claude):
- CajaWebController::updateMovimiento (línea 521) pisa el valor; destroyMovimiento (línea 578) borra sin dejar nada.
- El modelo a seguir es CashflowCorreccionService (T27): leelo antes de diseñar.
- Los cobros de cuota e inscripción no se editan ni se eliminan: se cancelan con motivo (cancelarMovimiento, línea 633). No tocar eso.

*Qué hay que hacer*
1. MOV-007: al editar, queda el importe nuevo, el arqueo y el gasto pendiente del mes se recalculan, y **queda registrado el valor anterior**, quién lo cambió y cuándo. El caso del catálogo dice en d) que hoy no queda rastro: eso es lo que se corrige.
2. MOV-008: al eliminar, desaparece de la caja y del arqueo, y queda registro de qué era, quién lo eliminó y cuándo.
3. El dueño ve ese rastro donde ya revisa la caja (caja/detalle). La operativa también ve lo de su caja.
4. Comprobar que los cobros automáticos siguen sin ofrecer Editar ni Eliminar, ni forzando la ruta.

---

## 2. T45 · Cambiar la forma de liquidar de un deporte

*Qué decidió Carlos (DU-42).* Caso CAT-002 (CAT.json): **solo si no hay liquidaciones abiertas, y rige desde el mes siguiente.**

*Cómo está hoy* (leído por Claude):
- DeporteWebController::update (línea 50) cambia tipo_liquidacion siempre.
- La profesora no tiene forma propia: Profesor (línea 89) toma la del deporte. LiquidacionService la lee de la profesora (líneas 34, 429 y 543). Por eso hoy un cambio cambia también el mes en curso y lo ya liquidado.

*Qué hay que hacer*
1. CAT-002.a: si el deporte tiene alguna liquidación abierta, no dejar cambiar la forma, y explicarlo con cuáles.
2. CAT-002.b: sin abiertas, se puede cambiar, pero **rige desde el mes siguiente**: la liquidación del mes en curso sigue con la forma anterior. Hace falta guardar la vigencia; cómo, lo decidís vos (decilo en la entrega).
3. CAT-002.c: las liquidaciones cerradas o pagadas conservan su tipo y su importe.
4. CAT-002.d: las del mes siguiente salen con la forma nueva. Si pasa a comisión y una profesora no tiene porcentaje cargado, avisarlo al cambiar y al liquidar, sin inventar un porcentaje.
5. Cambiar solo el nombre del deporte sigue funcionando como hoy.

---

## 3. T46 · Desactivar a una profesora

*Qué decidió Carlos (DU-44).* Caso PRO-004 (PRO.json): **avisar, pedir reasignar las clases futuras, permitir liquidarle lo dictado y desactivar su usuario.**

*Cómo está hoy* (leído por Claude):
- ProfesorWebController::toggleActivo (línea 116) desactiva sin avisar.
- Nueva liquidación solo ofrece profesoras activas (LiquidacionWebController, líneas 60 y 107): a una inactiva no se le puede liquidar lo dictado.
- El usuario vinculado (Profesor::user, línea 108) sigue entrando.

*Qué hay que hacer*
1. PRO-004.a: al desactivarla, avisar cuántas clases futuras tiene y pedir que se reasignen. Si quedan clases futuras sin reasignar, no se desactiva. Lo decidís vos, siempre que ninguna clase futura quede a nombre de una inactiva; decilo en la entrega. Reasignar usa ClaseWebController::actualizarProfesores, con el control de choque de horario que ya existe (T62 lo ajustó: las canceladas no cuentan).
2. PRO-004.b: se le puede liquidar lo dictado aunque esté inactiva, en Nueva liquidación. Una profesora inactiva sin nada pendiente no aparece.
3. PRO-004.c: su usuario queda desactivado y no puede volver a entrar. Si ya tenía una sesión abierta, también se corta. Al reactivarla, decidí qué pasa con el usuario y decilo.
4. PRO-004.d: deja de ofrecerse para clases nuevas y para reasignar.

---

*Reglas (las tres tareas)*
- Sin JavaScript dentro de la vista; antes de escribir, buscar en ds-app.js si ya existe lo necesario.
- Botones de una palabra, salvo que una sola no describa la acción. Formato de botón del sistema (ds-btn ds-btn--...).
- Plata compartida (CajaService, LiquidacionService): antes de cambiar un cálculo, buscar quién más lo usa. Suite completa antes de entregar cada una.
- Tocar solo T51, T45 y T46. Lo que encuentres de más se anota como mejora futura: el alcance hasta producción está cerrado.

*Pruebas y evidencia (por cada tarea)*
- Pruebas nuevas por cada punto, incluidas las forzadas por fuera de la pantalla (operativo o profesor intentando lo que no le toca, rutas forzadas).
- Suite completa con DB_DATABASE=wings_testing_codex.
- Capturas reales en Chrome, a 1366 y 375 px, de lo que cambia en pantalla.
- Galería docs/06-pruebas/PRU-04/APROBAR-T51.html (y T45, T46) y entrega en IMPLEMENTACION-T51.md (y T45, T46).
- Actualizar el contador de ESTADO-ACTUAL.md solo con tus pruebas, y el contrato del área con la regla nueva.

*Cierre de cada una.* a_verificar, verifica=Claude. Firmar en LOG-CODEX.md. Commit: git status, agregar por nombre, nunca git add -A; si un archivo propio tiene cambios ajenos, frenar y avisar. Sin despliegue.
