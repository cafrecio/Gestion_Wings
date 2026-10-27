# Wings — Bitácora activa de CLAUDE

[Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo común](PROTOCOLO-CONTINUIDAD.md)

Máximo 150 líneas o 12.000 caracteres; hasta 10 entradas recientes de 5–10 líneas.
Cada agente escribe solo aquí su trabajo. Nuevas entradas arriba.
Los extractos del 11/09 fueron preparados por Codex CAB el 12/09 desde el histórico;
no son nuevas verificaciones ni nuevas firmas del agente resumido.

[Histórico completo hasta el corte](../99-archivo/bitacoras/2026-09-12/LOG-CLAUDE.md)
· [Índice y huellas](../99-archivo/bitacoras/2026-09-12/INDICE.md).
· [Entradas archivadas el 17/09](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-17/LOG-CLAUDE-2.md) · [Entradas archivadas el 21/09](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-09-21/LOG-CLAUDE-2.md) · [Entradas archivadas el 02/10](../99-archivo/bitacoras/2026-10-02/LOG-CLAUDE.md) · [Entradas archivadas el 10/10](../99-archivo/bitacoras/2026-10-10/LOG-CLAUDE.md) · [Entradas archivadas el 13/10](../99-archivo/bitacoras/2026-10-13/LOG-CLAUDE.md) · [Entradas archivadas el 05/10](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE.md) y [segundo corte](../99-archivo/bitacoras/2026-10-05/LOG-CLAUDE-2.md) · [Entradas archivadas el 07/10](../99-archivo/bitacoras/2026-10-07/LOG-CLAUDE.md) · [Entradas archivadas el 08/10](../99-archivo/bitacoras/2026-10-08/LOG-CLAUDE.md) · [Entradas archivadas el 12/10](../99-archivo/bitacoras/2026-10-12/LOG-CLAUDE.md) · [Entradas archivadas el 16/10](../99-archivo/bitacoras/2026-10-16/LOG-CLAUDE.md) · [Entradas archivadas el 19/10](../99-archivo/bitacoras/2026-10-19/LOG-CLAUDE.md) · [Entradas archivadas el 21/10](../99-archivo/bitacoras/2026-10-21/LOG-CLAUDE.md) · [Entradas archivadas el 20/10](../99-archivo/bitacoras/2026-10-20/LOG-CLAUDE.md) · [Entradas archivadas el 27/10](../99-archivo/bitacoras/2026-10-27/LOG-CLAUDE.md)

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

## 2026-10-26 — Claude CyE — Guion de los días 17 y 18 (lunes 26 y martes 27)

- [Guion](../06-pruebas/PRU-04/GUION-DIAS-17-18.md) y cuatro rutinas. Retoma lo que la semana pasada no salió: Julieta, Rocío con clave conocida, Martín por comisión, la #76 y Botiquín para la operativa.
- Números de partida tomados del texto de las pantallas del día 16. Los códigos de caso se controlaron contra el catálogo y se corrigieron tres.
- `rutina.php`: las listas también se piden por grupo y hora. 1 prueba propia en verde (`prueba-tecnica/RutinaClasePorGrupoTest.php`).
- `ssh vps` desde CyE: esta red corta toda salida por el puerto 22 (GitHub incluido; por el 443 SSH pasa). No es la clave ni el servidor, y desde acá no se puede arreglar.

## 2026-10-26 — Claude CyE — Días 13 a 16 revisados (T14)

- Ningún defecto nuevo de Wings. Las cuentas cuadran al peso: toda la diferencia son los $143.000 de Julieta (13.11), que no se hizo por un límite del navegador automático.
- Errores de mi guion: motivo en la #49 (mismo día) y en la #77 (futura); Martín por hora en Fútbol, que liquida por comisión.
- Rocío: la clave del día 9 no se guardó.
- Para Carlos: el Telegram de Zoe (14.9; `ssh vps` no respondió desde CyE), el DNI con letras (14.1, no hay regla) y la liquidación del mes en curso (16.5).
- Prueba técnica 7–16, seguridad: 4/4 bien (archivo 11/11 pruebas, 81 aserciones, con control positivo). Funcionamiento interno y performance sobre el sitio, pendientes: `ssh vps` no responde desde CyE. [Informe](../06-pruebas/PRU-04/prueba-tecnica/INFORME.md).
- Orden de Carlos escrita en CLAUDE.md: en TEST, historias, revisión y prueba técnica sin que lo pida.
- Error mío: no revisé los días 7 a 16 cuando volvieron de Codex. Carlos ya lo había pedido el 24/10 sin necesidad de repetirlo. Faltan los días 7 a 12. [Revisión](../06-pruebas/PRU-04/ESTADO-PRUEBA.md).

## 2026-10-25 — Claude CAB — T77 a T80 cerradas y desplegadas en el sitio de prueba

- Carlos: «si están bien, subilas». Verificadas por otro agente cada una; cerradas en el tablero.
- Sitio de prueba de `56379295` a `b816d181`: respaldo `/root/respaldos/wingstest-antes-despliegue-2026-10-25.sql.gz`,
  down → merge --ff-only → npm ci/build → 0 migraciones → cachés → up. /login 200, CSP bloqueando,
  reloj simulado intacto (15/10). Producción no se tocó.

