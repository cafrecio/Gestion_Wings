# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md)

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

## 2026-10-27 — Claude CyE — Días 19 a 22 revisados, prueba técnica y planilla

- Octubre cerró con deuda $0 y 103 Al día. Diferencias explicadas: +$250.000 por el sueldo no pagado y −$121.000 de ingresos por error de mi guion (Indumentaria es aporte, no ingreso).
- Defectos nuevos verificados en código y base: T86, no hay forma de pagar el sueldo de una operativa (subrubro reservado; también se rechaza forzando); T87, Nueva liquidación cuenta clases de todos los meses (Lucía 20 = 17 + 3 de septiembre); T88, gasto mayor que el cajón y parcial sin motivo; T89, faltas de pantalla. Contrato de Reportes corregido.
- Técnica: interno 16/16 al 31/10, seguridad 18/18 (cinco pruebas retratan hallazgos), velocidad < 192 ms. Planilla: 22 días, 134/332. [Revisión](../06-pruebas/PRU-04/ESTADO-PRUEBA.md).

## 2026-10-27 — Claude CyE — Verificación de T84 (Gemini): lógica aprobada

- Interruptor en la ficha sin confirmación y aviso de la cuota generada, también en el listado. CSP en verde; prueba propia 2/2. [Informe](../06-pruebas/PRU-04/VERIFICACION-T84-CLAUDE.md).
- Rápida 927/6 omitidas/1 fallida ajena: `CobrarMesSinDeudaT77Test` dependía de la fecha real (la cobertura de reportes se fija «desde hoy»). Arreglado en la prueba; Wings no cambia.
- Observación → T85: dos toques seguidos generan una cuota de reingreso (el interruptor no se bloquea y la ruta invierte el estado). Falta el OK visual de Carlos; no desplegar hasta terminar el día 22.

## 2026-10-27 — Claude CyE — Planilla «Prueba por días» al día 18; casos nuevos corregidos

- Planilla (artifact 8cgmP6kq) con los días 17 y 18, cobertura 114/332, defectos y lo que sigue. Desde ahora se actualiza con cada paquete de días (CLAUDE.md, «Prueba en TEST», punto 4).
- Error mío corregido: de los 27 casos «nuevos» del guion 19–22, 15 ya estaban probados según la planilla, porque los había sacado solo de los informes de Codex. Reemplazados: ahora son 25 nuevos de verdad (`d66eba1e`).

## 2026-10-27 — Claude CyE — Guion de los días 19 a 22 (28 al 31/10), armado contra la base

- [Guion](../06-pruebas/PRU-04/GUION-DIAS-19-22.md): 27 casos nuevos, unas 20 a 25 operaciones por día y cierre de mes. Datos leídos de `wingstest` y cada rutina simulada con su fecha (`rutina.php --simular --fecha`, nuevo): así salieron los precios de octubre y el aumento de Principiantes 2 veces, que rige desde noviembre.
- `rutina.php`: muestra el importe de cada cobro y acepta el mes elegido a mano (`manuales`), como la pantalla (T77).
- Hallazgos: Agustín Acosta tiene una cuota de reingreso de $14.000 por un toque del interruptor el 27/10, sin registrar (se condona en el 19.2; nota en T84). Un gasto en efectivo mayor que el cajón se acepta (MOV-004 pide rechazo). El servidor acepta un pago parcial sin motivo (COB-009). Las dos cosas se comprueban en la prueba técnica.
- Liquidación de Lucía simulada al 31/10: hoy 15 clases, $180.000; no cierra antes de fin de mes, con el texto esperado.

## 2026-10-27 — Claude CyE — Días 17 y 18 revisados, con su prueba técnica

- La plata cierra al peso: la única diferencia es Julieta (17.7, tercera vez que el Chrome automático no acepta la confirmación nativa). Decide Carlos: lo hace a mano o se cambia esa confirmación por la ventana propia de Wings.
- 17.6 pasa a Sí. 17.1/17.5 bien, hechos por vía técnica a pedido de Carlos. Defecto menor: el rechazo de una clave corta sale en inglés si se saltea el formulario (falta `password.min`).
- Prueba técnica: interno 16/16 sobre `wingstest` (por `ssh vps` en el 2222), seguridad 12/12 (nueva: abrir con otro turno abierto) y velocidad entre 2 y 211 ms. [Revisión](../06-pruebas/PRU-04/ESTADO-PRUEBA.md) · [Informe](../06-pruebas/PRU-04/prueba-tecnica/INFORME.md).
