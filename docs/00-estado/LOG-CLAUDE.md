# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md)

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

## 2026-10-27 — Claude CyE — T92: la segunda generación ya no pisa la constancia; Revisión planificada

- Carlos: «Corregir». `GenerarDeudasMensualesCommand`: la primera corrida del mes deja la constancia; otra posterior suma lo que crea o manda a Revisión y no toca la hora ni los existentes. La columna `terminada_at` tiene ON UPDATE CURRENT_TIMESTAMP en MariaDB: se repite su valor al actualizar. Las otras dos columnas así (`cargo_alumno_eventos.created_at`, `reporte_eventos.registrado_en`) nunca se actualizan.
- Prueba nueva en `GeneracionCuotasReintentoT40Test` (roja sin el arreglo). Corte 1058. La constancia de noviembre del sitio sigue en 0 creadas: no se repara.
- Error mío de planificación: octubre no dejó a nadie para Revisión aunque Carlos lo había pedido. Plan para el guion de noviembre en ESTADO-PRUEBA (día 23); regla guardada en memoria.

## 2026-10-27 — Claude CyE — T90 reparada; T91 pasa a mejora futura MF10

- Pedido de Carlos: reparar T90. `ClaseWebController::toggleCancelada` rechaza cancelar una clase que integra una liquidación cerrada o pagada (DU-30 / CLA-013), con el texto que la pantalla ya muestra en el panel. Sin cambios de vista. Reactivar y el aviso por lista tomada siguen en T62.
- `CancelarClaseLiquidadaT90Test` 4/4: rojo sin el arreglo (3 fallan), verde con él. La prueba técnica del hallazgo se dio vuelta. Corte 1057.
- Carlos: T91 como mejora futura → MF10. Error mío: T90 ya estaba decidido en DU-30 y anotado en T62; no lo busqué antes de crear la tarea.
- Día 23, preguntas de Carlos: 105 alumnos (103 activos, 2 inactivos); los 103 tuvieron un presente en octubre, por eso Revisión 0 y CBZ-013 sigue sin probar. La segunda generación pisó la constancia de la primera (observación).

## 2026-10-27 — Claude CyE — Día 23 revisado, con su prueba técnica

- Generación de noviembre igual a la calculada aparte: corrió sola a las 06:00, 100 cuotas, $3.783.000, y la segunda corrida no creó nada. Las ocho liquidaciones dan lo esperado ($1.370.280); 13 atrasadas validadas con registro.
- 3 cerradas ($345.000) y 5 abiertas por orden de Carlos; ninguna pagada. «Por pagar» del Inicio y Reportes = cerradas.
- Errores de mi guion: ingresos de noviembre son $103.000 (los tres adelantos, T42), no $0; el aumento rige desde el 18/10, no desde noviembre.
- Técnica: interno 21/21 (cinco controles nuevos de liquidaciones y del reporte de octubre), seguridad 22/22, velocidad < 271 ms. Hallazgo **T90**: una clase de una liquidación cerrada se puede cancelar, también la operativa y desde la pantalla. **T91**: ventana propia de confirmación, pedida por Carlos. [Revisión](../06-pruebas/PRU-04/ESTADO-PRUEBA.md) · [Informe](../06-pruebas/PRU-04/prueba-tecnica/INFORME.md).
- Bitácora: entradas anteriores al 25/10 (desde «Control cruzado de T77») archivadas intactas.

## 2026-10-27 — Claude CyE — Preparación del 1/11: T84 desplegada y lo esperado calculado aparte

- Respaldo `/root/respaldos/wingstest-antes-generacion-noviembre-2026-10-27.sql.gz`. Sitio de prueba de `ec1b0c6e` a `500ce7bc` (T84): build, 0 migraciones, cachés; /login 200, reloj simulado intacto.
- Lo que tiene que generar noviembre, con las reglas del contrato y consultas propias, sin el código del generador: 100 cuotas, $3.783.000, 3 con adelanto, Revisión 0. Comparador alumno por alumno. Liquidaciones esperadas: octubre y septiembre, por pagar $1.370.280. Todo en `PRU-04/generacion-noviembre/`.
- [Guion del día 23](../06-pruebas/PRU-04/GUION-DIA-23.md): reloj a las 05:55 para que la generación corra sola a las 06:00, como en producción.
