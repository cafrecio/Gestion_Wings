# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md)

## 2026-10-27 — Claude CyE — T88 aprobada en lógica; B3 devuelta

- T88 y B3 dinero (Codex, `17f99d13`): aprobadas en lógica; falta el aspecto de Carlos. B3 asistencia (`51d26fb8`): devuelta a Codex.
- Defecto: todo alumno de la primera carga queda como nuevo y pide motivo desde la 3a clase. En el sitio de prueba son 25 alumnos; en producción sería todo el club. Prueba roja en `evidencia-verificacion-t88-b3-claude`. [Informe](../06-pruebas/PRU-04/VERIFICACION-T88-B3-CLAUDE.md).
- T54 asignada a Gemini y T65 a Codex para verificar (pedido de Carlos).
- B3 segunda vuelta (Codex, `1de85aef`): aprobada en lógica, la primera carga ya no es nueva. T88 y B3 esperan el aspecto de Carlos.
- T54 (Gemini, `c4094222`) devuelta: bien en listado, autocompletar y Cobrar; el Historial de caja sigue buscando exacto. [Informe](../06-pruebas/PRU-04/VERIFICACION-T54-CLAUDE.md).

## 2026-10-27 — Claude CyE — T87 verificada en lógica; contador corregido

- T87 (Gemini, `b079b0ca`): conteos de Nueva liquidación por mes elegido; prueba roja con el código anterior. Falta el OK visual de Carlos. [Informe](../06-pruebas/PRU-04/VERIFICACION-T87-CLAUDE.md).
- Gemini declaró 1098 pruebas contando 19 de Codex (T88/B3) sin commitear en la carpeta compartida. En el repositorio hay 1079; corregido. Codex suma las suyas al commitear.
- T88 y B3 asignadas a Codex con prompt (27/10).

## 2026-10-27 — Claude CyE — T93 verificada y subida; producción leída

- T93 (Gemini, `ada0ac06`) aprobada: sin rubro no hay subrubros; edición y vuelta de error conservan lo elegido. Sitio de prueba en `f8d71d5c`, sin migraciones, /login 200. Observación: `display: none` en opciones puede no ocultarlas en iPhone; no comprobado.
- Producción leída sin tocar (anotado en PLAN-PRODUCCION): `314e485` del 22/09, 7 migraciones pendientes de su propio código + 25 nuevas, 0 medios de pago y 3 subrubros.

## 2026-10-27 — Claude CyE — Verificación de T86 (Codex): lógica aprobada

- La migración, en solo lectura sobre el sitio de prueba, cambiaría exactamente Op-Sandra, Op-Pablo y Op-Rocío. Pruebas de Codex 32/32 en mi base.
- Prueba propia: sin la reserva, la operativa igual no se carga su sueldo desde la caja forzando (la caja exige renglón de operativa que afecte caja). La prueba del hallazgo 22.5 se dio vuelta. Seguridad 23/23.
- Falta el OK visual de Carlos de Reportes → Sueldos (Codex amplió el alcance con su confirmación). T92 cerrada por Codex. Sitio de prueba de `500ce7bc` a `79340268` (T90, T92, T85; T32 ya estaba): respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-27-0808.sql.gz` (el primer intento salió vacío y se rehizo antes de seguir trabajando; el despliegue no tocó la base), build, 0 migraciones, cachés; /login 200, CSP bloqueando, reloj simulado intacto. Después, con el «Parte visual OK» de Carlos, T86 cerrada y subida (`f5306953`): respaldo `wingstest-antes-t86-2026-10-27-0811.sql.gz`, migración DONE (solo las tres operativas sin reserva), /login 200. [Informe](../06-pruebas/PRU-04/VERIFICACION-T86-CLAUDE.md).

## 2026-10-27 — Claude CyE — Verificación de T85 (Gemini): aprobada

- Código de `ds-app.js` leído; los seis interruptores con `data-url` responden JSON; sin estilo `:disabled` (el aspecto no cambia).
- Chrome real, segundo clic de mouse a los 300 ms con la respuesta retenida 1,5 s: con T85, 1 pedido y sin cuota; con el código anterior, 2 pedidos y cuota de reingreso (caso Agustín). Control: un clic posterior sí se manda. [Informe](../06-pruebas/PRU-04/VERIFICACION-T85-CLAUDE.md).
- Observación menor: con sesión vencida el aviso no dice que hay que volver a entrar. T85 cerrada; se sube a prueba con T92.

## 2026-10-27 — Claude CyE — T92 segunda vuelta: Revisión no se cuenta dos veces

- Codex devolvió T92 (`5c851d38`): al repetir la generación, quien ya estaba en Revisión se volvía a contar (constancia 1, 2, 4 con dos alumnos). Correcto.
- `GenerarDeudasMensualesCommand`: solo cuenta la fila de Revisión si es nueva (`wasRecentlyCreated`). Prueba permanente con el caso de Codex (1, 1, 2), roja sin el arreglo. Corte 1059; rápida 933/6 omitidas (solo el contador en rojo, corregido).
- T90 y T32 cerradas por Codex y Gemini. T92 vuelve a Codex.
- **Error mío:** `6a06cf09` incluyó también la entrega de T85 de Gemini (`ds-app.js`, `IMPLEMENTACION-T85.md`, `evidencia-t85/`, `LOG-GEMINI.md`), que estaba preparada en la misma carpeta. Agregué lo mío por nombre pero hice `git commit` sin limitarlo a esos archivos. La autoría de T85 es de Gemini. Ya estaba subido: no se reescribe.
