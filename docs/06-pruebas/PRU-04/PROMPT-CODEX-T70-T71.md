# T70 + T71 · Inactivos: fecha, cantidad del mes y deuda aparte

> Prompt para Codex, escrito por Claude CyE el 28/10/2026. Carlos lo copia y se lo pasa a Codex.

*Inicio.* Aplicar docs/00-estado/PROTOCOLO-CONTINUIDAD.md y leer AGENTS.md. Antes de tocar vistas, leer docs/03-diseno-ui/wings-design/SKILL.md y docs/03-diseno-ui/design-system/DESIGN-RULES.md. Marcar T70 y T71 en_curso, tiene=Codex, con php scripts/tablero/tablero.php cambiar ....

*Qué decidió Carlos.*

- DU-39 (13/10), caso REP-006: el Reporte de Alumnos muestra *solo la cantidad* de altas y bajas del mes, sin nombres.
- DU-11 (13/10), casos ALU-017 y ALU-025: la deuda de quien pasa a Inactivo se muestra aparte, *no suma al por cobrar* (decisión del 10/10), y el dueño la puede dar por perdida para sacarla de la lista.
- 28/10: *inactivo y baja son dos cosas distintas.* Baja es eliminar, y solo se puede sin movimientos (ya lo hace T65: no tocarlo). Pasar a Inactivo sigue *instantáneo, sin motivo y sin aviso* (T84 y T85: no tocar el interruptor). En T70, las «bajas del mes» son los pases a Inactivo.

Casos en docs/06-pruebas/PRU-04/tabla-de-pruebas/catalogo/ (REP.json, ALU.json) y dudas en tabla-de-pruebas/dudas/ (DU-11, DU-39).

*Cómo está hoy* (leído por Claude en el código actual):

- AlumnoWebController::toggleActivo (línea 428) solo invierte activo; no guarda cuándo. No hay columna de fecha de baja en ninguna migración.
- Al reactivar, la misma transacción genera la cuota de reingreso (T38) y cierra la Revisión: no romperlo.
- CobranzaEstadoService filtra Alumno::where('activo', true) en varios lugares (líneas 286, 359, 475, 577): por eso la deuda del inactivo desaparece de todo total.
- Ya existe *perdonar* (condonar, T33): monto_condonado en cuotas e inscripción. Dar por perdida *no es* perdonar: lo perdonado es un regalo del club; lo perdido es plata que se debía y no se va a cobrar. Si reusás la condonación, que se pueda distinguir en reportes y recibos; si no, explicá por qué elegiste otra cosa.

*Qué hay que hacer*

1. *T70 · fecha.* Guardar la fecha en que el alumno pasa a Inactivo (y limpiarla o conservar historia al reactivar: decidís vos, decilo en la entrega). Migración nueva; los inactivos que ya existen quedan sin fecha y no cuentan en ningún mes.
2. *T70 · reporte.* En el Reporte de Alumnos (ReporteAlumnosService), cantidad de altas y de pases a Inactivo del mes elegido. Solo números. Reactivar en el mismo mes no debe contar dos veces de forma engañosa: explicá cómo lo contás.
3. *T71 · deuda aparte.* En Cobranza y en Reportes, un total «Deuda de alumnos inactivos» con su lista (nombre, cuánto debe, desde cuándo está inactivo). No suma al por cobrar, a Inicio ni a ningún total existente: comprobarlo con pruebas (ALU-017.b y d siguen valiendo).
4. *T71 · dar por perdida.* Solo el dueño (ADMIN). Desde esa lista, marcar la deuda de un inactivo como perdida; sale de la lista, queda el rastro (quién, cuándo, cuánto) y la ficha lo muestra. El operativo no puede, ni forzando la ruta. Si el alumno vuelve después, decidí qué pasa con lo perdido y decilo; no inventar cobros automáticos.
5. *ALU-017 y ALU-025.* Correr esos casos de punta a punta con pruebas; el interruptor sigue como está.

*Reglas*

- Sin JavaScript dentro de la vista; antes de escribir, buscar en ds-app.js si ya existe lo necesario.
- Botones de una palabra, salvo que una sola no describa la acción. Formato de botón del sistema (ds-btn ds-btn--...).
- Permisos: leer docs/02-contratos/PERMISOS-ROLES.md antes de tocar el acceso.
- Plata compartida (CobranzaEstadoService, ReporteMensualService): antes de cambiar un cálculo, buscar quién más lo usa. Suite completa antes de entregar.
- Tocar solo T70 y T71. Lo que encuentres de más se anota como mejora futura: el alcance hasta producción está cerrado.

*Pruebas y evidencia*

- Pruebas nuevas por cada punto, incluida la negativa del operativo forzando la ruta de «dar por perdida».
- Suite completa con DB_DATABASE=wings_testing_codex.
- Capturas reales en Chrome, a 1366 y 375 px: Reporte de Alumnos con las cantidades, total y lista de deuda de inactivos en Cobranza y en Reportes, y la ficha de un alumno con deuda dada por perdida.
- Galería docs/06-pruebas/PRU-04/APROBAR-T70-T71.html y entrega en IMPLEMENTACION-T70-T71.md.
- Actualizar el contador de ESTADO-ACTUAL.md solo con tus pruebas, y el contrato de cobranza con la regla nueva.

*Cierre.* T70 y T71 a_verificar, verifica=Claude. Firmar en LOG-CODEX.md. Commit: git status, agregar por nombre, nunca git add -A; si un archivo propio tiene cambios ajenos, frenar y avisar. Sin despliegue.
