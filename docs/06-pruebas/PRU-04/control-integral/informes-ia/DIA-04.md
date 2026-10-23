# Entrega técnica — Día 4

Corte documental: 2026-10-23. Club: 2026-10-13. Ejecución humana: 2026-10-22. Contraste: 2026-10-22.

**Revisión independiente pendiente.** Ejecución y contraste de Codex no constituyen dos verificadores. Esta reorganización no repitió operaciones, suites ni consultas de negocio.

## Lectura y límites

Los 16 escenarios, los métodos automatizados y los casos fuente son unidades distintas. Conservar los 14 Coincide / 2 Parcial del ejecutor; no convertirlos en casos de catálogo completos.
Las horas de cierre/validación del campo de contexto no son la hora individual de todos los pasos. No reconstruir snapshots previos inexistentes.

## Entorno y fuentes

[Informe del ejecutor](../../DIA-04.md) · [Contraste técnico](../../VERIFICACION-DIA-04.md) · [Registro canónico](../control-integral.json) · [Síntesis humana trazable](../devolucion-humana.json)

- Staging: test.gestionar-te.com.ar; base wingstest; versión leída 56379295a33782858ce9c986e200f13382d61cc4; reloj leído 13/10 19:51 el 22/10, sin cambio por este contraste.
- Corrida dirigida posterior: local testing, wings_testing_codex, a15d6095000e0605cae71fc89211f4db111e6983; 23/10. HTTP interno/servicios, sin CSRF real ni certificación remota.

## Hallazgos y orden de resolución

1. **D4-OBS-01:** alumno 331 AL_DIA sin deuda octubre persistida; CajaWebController::cobrar agrega una DeudaCuota en memoria. La clasificación usa filas persistidas. Alcance y cuerpos leídos al corte en el contraste; no una nueva auditoría del código actual.
   - Riesgo: discrepancia de concepto entre deuda exigible y cuota potencial; octubre no se cobró. Confirmar regla de negocio antes de escribir deudas o cambiar la pantalla.
   - Alternativas: generar una deuda realmente faltante; no ofrecer como deuda una cuota ya resuelta; separar explícitamente la acción de crear/cobrar cuota del saldo exigible. No elegir por conveniencia de cuadre.
   - Verificar después: misma persona/período/rol entre Cobranza, Cobrar, ficha y reportes; rechazo sin escrituras si la operación no procede; conservar noviembre pagado.
2. **T2:** alta y deuda correctas, presentación sin vencimiento explícito/total conjunto. Propuesta humana: fecha y total. Requiere flujo visual del repo antes de implementación; no confundir con error del plazo calculado.
3. **T6:** 20.000 Sep + 10.000 Oct escritos en campos separados; saldo Oct 38.000. Reparto global, excepción FIFO y rechazo sin motivo no acreditados. Ensayar en base aislada; no repetir el cobro real de la jornada.

## Seguimiento temporal

- Mora: presente pendiente el 14/10; EN_PLAZO hasta 19/10, MOROSO desde 20/10 si conserva deuda.
- Julieta, no Sofía: adelanto noviembre 30.000; comprobar no duplicación 01/11 y comisión de noviembre con asistencia aplicable. No inferir comisión por observar el pago.
- Corridas 06:00/06:15/08:00 y liquidaciones del 02/11 pendientes; la tarde ejecutada no las demuestra.

## Situaciones: evidencia, impacto y cobertura

### D4-T01 — Sandra abre la caja con el cambio anterior

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Aceptó los $10.000 sugeridos y abrió el turno por pantalla.
- Esperado resumido: Heredar $10.000 y habilitar los cobros del turno.
- Observado técnico conservado: Caja 2 abierta con $10.000 heredados de caja 1 validada; sin motivo.
- Límite técnico: Pendiente variante con caja anterior sin validar, otra operativa y cruces antes de operar.
- Impacto: Recepción puede comenzar a cobrar con el efectivo inicial registrado.
- Propuesta/siguiente comprobación, no ejecutada: Conservar esta apertura y cubrir esas variantes en otro turno.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json) · [dia04-01-caja-apertura.png](../../evidencia-dia-04/dia04-01-caja-apertura.png)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| CAJ-005.a | Parcial | Solo cierre anterior validado; propuesta y apertura $10.000. |
| CAJ-005.b | Parcial | Solo cierre anterior validado; propuesta y apertura $10.000. |
| CAJ-005.c | Parcial | Solo cierre anterior validado; propuesta y apertura $10.000. |
| CAJ-005.e | Parcial | Solo cierre anterior validado; propuesta y apertura $10.000. |

### D4-T02 — Inscripción de Mora en Patín

- Resultado humano: **Parcial**. Rol: OPERATIVO.
- Método por pantalla: Cargó los datos de Mora, su tutora, grupo y plan; guardó y abrió la ficha.
- Esperado resumido: Alta en Principiantes, inscripción $5.000 y octubre $40.000; total $45.000 y vencimiento explícito al 20/10.
- Observado técnico conservado: Mora 341, grupo 1, plan 2. Cuota 246 $40.000 y cargo 28 $5.000; EN_PLAZO.
- Límite técnico: T2: ficha dice 6 días de gracia; no muestra vencimiento explícito ni total conjunto. No se intentó alta sin tutor.
- Impacto: La presentación obliga a interpretar el plazo; no se demostró un error de monto o de cálculo.
- Propuesta/siguiente comprobación, no ejecutada: Mostrar fecha límite y total junto al detalle, sujeto a revisión visual. No se cambió el producto.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| ALU-002.b | Parcial | Alta con tutor, ficha, deuda y cruces del relato; no se probó celular del alumno vacío ni todos los efectos. |
| ALU-002.c | Parcial | Alta con tutor, ficha, deuda y cruces del relato; no se probó celular del alumno vacío ni todos los efectos. |
| ALU-002.d | Parcial | Alta con tutor, ficha, deuda y cruces del relato; no se probó celular del alumno vacío ni todos los efectos. |
| ALU-002.e | Parcial | Alta con tutor, ficha, deuda y cruces del relato; no se probó celular del alumno vacío ni todos los efectos. |

### D4-T03 — Mora aparece en Cobranza

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Usó el filtro En plazo al no encontrar un buscador por nombre.
- Esperado resumido: Encontrar a Mora con $45.000 pendientes y ver 101 alumnos activos.
- Observado técnico conservado: Mora visible por filtro En plazo, deuda $45.000; matrícula 101.
- Límite técnico: Se usó filtro, no búsqueda textual. Cruce propio sin equivalencia completa con un caso del catálogo.
- Impacto: Recepción puede encontrar la deuda nueva desde Cobranza.
- Propuesta/siguiente comprobación, no ejecutada: Registrar la navegación real en el manual; probar por separado la búsqueda donde esté disponible.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)
- Cruce propio; no forzar equivalencia a un caso fuente.

### D4-T04 — Renata paga octubre en efectivo

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Cobró octubre por $43.000 en efectivo y consultó la ficha y el comprobante.
- Esperado resumido: Octubre pagado, deuda cero, recibo correcto y $43.000 sumados al efectivo.
- Observado técnico conservado: Pago 5, Renata, $43.000 EFE. Octubre pagado y AL_DIA; movimiento y reflejo únicos.
- Límite técnico: Recibo desde confirmación. No se comprobaron todos los accesos al PDF ni comisión y contadores intermedios.
- Impacto: La cuota quedó saldada y el dinero integra la recaudación del turno.
- Propuesta/siguiente comprobación, no ejecutada: Mantener el cobro y seguir esos efectos en sus próximas pruebas.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| COB-003.b | Parcial | Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes. |
| COB-003.c | Parcial | Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes. |
| COB-003.d | Parcial | Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes. |
| COB-003.g | Parcial | Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes. |
| COB-003.k | Parcial | Cobro, ficha/estado y registros; efectos intermedios y comisión pendientes. |
| REC-001.a | Parcial | PDF 5 y evidencia visual aportada por Carlos; sin centavos, sin tres accesos ni estabilidad posterior. |
| REC-001.b | Parcial | PDF 5 y evidencia visual aportada por Carlos; sin centavos, sin tres accesos ni estabilidad posterior. |

### D4-T05 — Emma paga septiembre por Mercado Pago

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Seleccionó solo septiembre y registró $45.000 por Mercado Pago.
- Esperado resumido: Septiembre pagado, octubre pendiente y sin sumar billetes al cajón.
- Observado técnico conservado: Pago 6, Emma, septiembre $45.000 MP; octubre conserva $45.000. Estado MOROSO.
- Límite técnico: Variante de período anterior; fuente pide mes corriente. No acredita todos los cruces de COB-003.
- Impacto: Bajó la deuda anterior sin alterar el efectivo físico esperado.
- Propuesta/siguiente comprobación, no ejecutada: Cubrir los cruces pendientes con cobros de próximas jornadas, sin repetir este pago.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| COB-004.a | Parcial | Mercado Pago, septiembre, $45.000; no aumenta efectivo. |
| COB-004.b | Parcial | Mercado Pago, septiembre, $45.000; no aumenta efectivo. |
| COB-004.c | Parcial | Mercado Pago, septiembre, $45.000; no aumenta efectivo. |
| COB-004.d | Parcial | Mercado Pago, septiembre, $45.000; no aumenta efectivo. |
| COB-004.f | Parcial | Mercado Pago, septiembre, $45.000; no aumenta efectivo. |

### D4-T06 — Dante entrega $30.000 a cuenta

- Resultado humano: **Parcial**. Rol: OPERATIVO.
- Método por pantalla: Ingresó $20.000 en septiembre y $10.000 en octubre; confirmó un cobro de $30.000.
- Esperado resumido: Cancelar septiembre y dejar octubre con saldo de $38.000.
- Observado técnico conservado: Pago 7 $30.000 EFE: $20.000 a septiembre y $10.000 a octubre, saldo $38.000.
- Límite técnico: T6: se escribieron dos importes. No demuestra distribución automática global, rechazo fuera de FIFO, segundo pago ni comisión.
- Impacto: La prueba valida el resultado final, no un reparto automático de un único importe.
- Propuesta/siguiente comprobación, no ejecutada: Ensayar el reparto y el orden de pago en una base aislada; no modificar el cobro de Dante.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| COB-008.a | Parcial | Variante: parcial de octubre $10.000 luego de cancelar septiembre; difiere de los $20.000/$28.000 fuente. |
| COB-008.c | Parcial | Variante: parcial de octubre $10.000 luego de cancelar septiembre; difiere de los $20.000/$28.000 fuente. |
| COB-008.d | Parcial | Variante: parcial de octubre $10.000 luego de cancelar septiembre; difiere de los $20.000/$28.000 fuente. |
| COB-008.e | Parcial | Variante: parcial de octubre $10.000 luego de cancelar septiembre; difiere de los $20.000/$28.000 fuente. |

### D4-T07 — Mateo paga por Banco Galicia

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Registró octubre por $38.000 como transferencia a Banco Galicia.
- Esperado resumido: Cuota pagada, alumno al día y dinero en el medio bancario.
- Observado técnico conservado: Pago 8, Mateo, octubre $38.000 BGA; pagado, AL_DIA; no aumenta efectivo.
- Límite técnico: Medio bancario, variante de Mercado Pago. Sin cubrir todos los cruces de la fuente.
- Impacto: El ingreso queda disponible en el medio correcto después de validar la caja.
- Propuesta/siguiente comprobación, no ejecutada: Mantener la conciliación por medio de pago en las jornadas siguientes.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| COB-004.a | Parcial | Transferencia Galicia $38.000; variante del medio. |
| COB-004.b | Parcial | Transferencia Galicia $38.000; variante del medio. |
| COB-004.c | Parcial | Transferencia Galicia $38.000; variante del medio. |
| COB-004.d | Parcial | Transferencia Galicia $38.000; variante del medio. |
| COB-004.f | Parcial | Transferencia Galicia $38.000; variante del medio. |

### D4-T08 — Julieta adelanta noviembre

- Resultado humano: **Coincide**. Rol: OPERATIVO / confirmación Carlos.
- Método por pantalla: Por indicación de Carlos, Julieta reemplazó a Sofía. Se cobró únicamente noviembre: $30.000 en efectivo.
- Esperado resumido: Noviembre pagado, recibo de noviembre y entrada de dinero ese día sin inflar los ingresos económicos de octubre.
- Observado técnico conservado: Julieta sustituyó a Sofía por orden de Carlos. Pago 9 $30.000 EFE; cuota 247 noviembre PAGADA; recibo 9.
- Límite técnico: D4-OBS-01: AL_DIA frente a octubre potencial en Cobrar. No repetir cobro. Generación 1/11, aumento y comisión pendientes.
- Impacto: La oferta de octubre puede confundir sobre una deuda que no figura en Cobranza. No se cobró octubre.
- Propuesta/siguiente comprobación, no ejecutada: Distinguir deuda exigible de creación de cuota y definir qué debe ofrecerse. Conservar el adelanto ya realizado.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| COB-012.a | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |
| COB-012.b | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |
| COB-012.c | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |
| COB-012.e | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |
| COB-012.h | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |
| COB-012.i | Parcial | Variante Julieta $30.000; ingreso/comisión etiquetados noviembre; no se liquidó comisión. No se acredita deuda antes/después de la selección. |

### D4-T09 — Venta de una remera del club

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Registró $15.000 en efectivo por indumentaria institucional.
- Esperado resumido: Sumar efectivo y mostrar el concepto en su clasificación correspondiente.
- Observado técnico conservado: Movimiento 11 $15.000 EFE, Indumentaria institucional APORTE, reflejo único.
- Límite técnico: Solo efectivo; venta por MP pendiente. Aporte separado del resultado. No se liquidó comisión.
- Impacto: Aumenta el dinero disponible sin confundirse con una cuota deportiva.
- Propuesta/siguiente comprobación, no ejecutada: Probar otro medio en una venta futura; conservar esta clasificación mientras siga vigente.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| MOV-002.a | Parcial | Una venta efectivo; la fuente pide también MP. Clasificación APORTE según catálogo actual. |
| MOV-002.b | Parcial | Una venta efectivo; la fuente pide también MP. Clasificación APORTE según catálogo actual. |
| MOV-002.c | Parcial | Una venta efectivo; la fuente pide también MP. Clasificación APORTE según catálogo actual. |

### D4-T10 — Compra de artículos de limpieza

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Registró $8.500 en efectivo; la observación guardada fue «Artículos para pista».
- Esperado resumido: Restar $8.500 del efectivo y registrar el gasto con el detalle del ticket.
- Observado técnico conservado: Movimiento 12 $8.500 EFE, EGRESO NEGOCIO, cashflow -$8.500; observación Artículos para pista.
- Límite técnico: No acredita ticket adjunto ni número del guion. Falta comparación de cada vista en estado pendiente.
- Impacto: La caja refleja la salida; esta evidencia no demuestra que se haya adjuntado un ticket.
- Propuesta/siguiente comprobación, no ejecutada: En el próximo gasto, conservar y comprobar el respaldo que efectivamente admita el sistema.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| MOV-001.a | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |
| MOV-001.b | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |
| MOV-001.c | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |
| MOV-001.d | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |
| MOV-001.e | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |
| MOV-001.g | Parcial | Gasto guardado, arqueo y resultado conciliados; estados previos y desglose completo pendientes. |

### D4-T11 — Lucía toma lista y encuentra a Mora en su grupo

- Resultado humano: **Coincide**. Rol: PROFESOR.
- Método por pantalla: Consultó la clase futura de Principiantes y guardó la lista de Avanzadas de ese martes.
- Esperado resumido: Mora en la nómina de su próxima clase y 10 presentes, 2 ausentes en Avanzadas.
- Observado técnico conservado: Clase 28: 12 marcas, 10 presentes/2 ausentes. Clase futura 6: Mora en nómina, cero marcas.
- Límite técnico: Variante 12 alumnos frente a 8 fuente. Historial individual, sueldo, comisiones y generación mensual pendientes.
- Impacto: La profesora ve el alta y queda registrada la actividad real de su clase.
- Propuesta/siguiente comprobación, no ejecutada: Seguir con la clase de Mora del miércoles y comprobar esos efectos al llegar sus fechas.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| ASI-001.a | Parcial | Lista 28 guardada y recargada, 10 presentes y 2 ausentes; no suplencia. |
| ASI-001.b | Parcial | Lista 28 guardada y recargada, 10 presentes y 2 ausentes; no suplencia. |
| ASI-001.c | Parcial | Lista 28 guardada y recargada, 10 presentes y 2 ausentes; no suplencia. |

### D4-T12 — Hernán completa una lista del sábado

- Resultado humano: **Coincide**. Rol: PROFESOR.
- Método por pantalla: Cargó 12 presentes y 2 ausentes en la clase de Fútbol que había quedado pendiente.
- Esperado resumido: Guardar la lista histórica reciente y reducir en uno el contador pendiente.
- Observado técnico conservado: Clase 74 del 10/10: 14 marcas, 12 presentes/2 ausentes, sin duplicados; contador actual 32.
- Límite técnico: El ejecutor observó 33→32. No hay snapshot previo propio ni prueba de antigüedad extrema CLA-018.
- Impacto: La clase deja de quedar sin lista; sus consecuencias económicas requieren el control mensual.
- Propuesta/siguiente comprobación, no ejecutada: Conservar los valores antes y después en próximas cargas históricas.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)
- Cruce propio; no forzar equivalencia a un caso fuente.

### D4-T13 — Sandra cierra el turno sin diferencia

- Resultado humano: **Coincide**. Rol: OPERATIVO.
- Método por pantalla: Declaró $119.500 contados, retuvo $10.000 y registró retiro de $109.500.
- Esperado resumido: Caja cerrada, diferencia cero y separación entre cambio y retiro.
- Observado técnico conservado: Caja 2 CERRADA; esperado/contado $119.500, diferencia $0, retenido $10.000, retiro declarado $109.500.
- Límite técnico: Importes del relato, diferentes de la fuente. No acredita entrega física, próxima apertura ni intento HTTP posterior al cierre.
- Impacto: Queda una rendición clara para que el dueño revise. Es una simulación: se comprobó el registro, no una entrega física de billetes.
- Propuesta/siguiente comprobación, no ejecutada: Cubrir esos controles en el siguiente turno, conservando este cierre.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| CAJ-018.a | Parcial | Arqueo sin diferencia y resumen cerrado; sin faltante ni cambio aumentado. |
| CAJ-018.b | Parcial | Arqueo sin diferencia y resumen cerrado; sin faltante ni cambio aumentado. |
| CAJ-018.c | Parcial | Arqueo sin diferencia y resumen cerrado; sin faltante ni cambio aumentado. |
| CAJ-018.h | Parcial | Arqueo sin diferencia y resumen cerrado; sin faltante ni cambio aumentado. |

### D4-T14 — El dueño revisa y valida la caja

- Resultado humano: **Coincide**. Rol: ADMIN.
- Método por pantalla: Revisó y tildó los siete movimientos, validó y consultó el resultado.
- Esperado resumido: Caja validada y un solo reflejo financiero por cada movimiento activo.
- Observado técnico conservado: Caja 2 VALIDADA; siete movimientos verificados y siete reflejos únicos con responsables.
- Límite técnico: No había inscripción cobrada ni cancelado; sin doble envío ni intento de modificación posterior por otro rol.
- Impacto: La recaudación pasa a confirmada en la administración financiera.
- Propuesta/siguiente comprobación, no ejecutada: Ensayar los rechazos y dobles envíos en una base aislada; no volver a validar esta caja para probarlos.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| CAJ-025.a | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |
| CAJ-025.b | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |
| CAJ-025.c | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |
| CAJ-025.d | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |
| CAJ-025.e | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |
| CAJ-025.g | Parcial | Variante: cinco cuotas, aporte y gasto; condiciones parciales del caso fuente. |

### D4-T15 — El dueño comprueba la matrícula

- Resultado humano: **Coincide**. Rol: ADMIN.
- Método por pantalla: Buscó a Mora y comparó la matrícula y el grupo con el reporte.
- Esperado resumido: 101 activos, con el alta de Mora reflejada en Patín Principiantes.
- Observado técnico conservado: 101 activos: Patín 66, Fútbol 35, Patín Principiantes 27. Mora visible en ficha y reporte.
- Límite técnico: Matrícula actual, no altas mensuales. Cruce propio; sin equivalencia completa asignada a un caso fuente.
- Impacto: El reporte permite controlar la población actual del club.
- Propuesta/siguiente comprobación, no ejecutada: Mantener separada la matrícula actual del seguimiento mensual de altas y bajas.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)
- Cruce propio; no forzar equivalencia a un caso fuente.

### D4-T16 — El dueño compara deuda, saldos y reportes

- Resultado humano: **Coincide**. Rol: ADMIN.
- Método por pantalla: Recorrió las pantallas; después se contrastaron sus importes con los registros guardados.
- Esperado resumido: Totales coherentes, aporte separado y adelanto de noviembre fuera del ingreso económico de octubre.
- Observado técnico conservado: Deuda $1.899.000; disponible $1.926.500; resultado octubre $311.500. APORTE $15.000 y adelanto $30.000 separados.
- Límite técnico: D4-OBS-01 abierta. Falta estado previo completo por operación, bajas, cancelaciones, condonación, liquidaciones y demás variantes.
- Impacto: El disponible global no se confunde con lo contado en ese cajón. Queda abierta la cuota que ofrece Cobrar sin deuda de octubre guardada.
- Propuesta/siguiente comprobación, no ejecutada: Aclarar la regla de deuda exigible y continuar conciliando cada jornada, sin alterar datos para forzar un resultado.
- Base/entorno: wingstest / staging, test.gestionar-te.com.ar; commit 56379295a33782858ce9c986e200f13382d61cc4.
- Revisión independiente: Pendiente.
- Evidencia: [DIA-04.md](../../DIA-04.md) · [VERIFICACION-DIA-04.md](../../VERIFICACION-DIA-04.md) · [consulta-db.tsv](../../evidencia-verificacion-dia-04/consulta-db.tsv) · [servicios.json](../../evidencia-verificacion-dia-04/servicios.json)

| Caso.letra | Cobertura | Alcance de la variante |
|---|---|---|
| CBZ-018.a | Parcial | 101 activos, estados 64/1/15/21 y deuda final; no todos los filtros ni todos los movimientos de la fuente. |
| CBZ-018.b | Parcial | 101 activos, estados 64/1/15/21 y deuda final; no todos los filtros ni todos los movimientos de la fuente. |
| CBZ-018.c | Parcial | 101 activos, estados 64/1/15/21 y deuda final; no todos los filtros ni todos los movimientos de la fuente. |
| CBZ-018.d | Parcial | 101 activos, estados 64/1/15/21 y deuda final; no todos los filtros ni todos los movimientos de la fuente. |
| REP-001.a | Parcial | Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones. |
| REP-001.b | Parcial | Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones. |
| REP-001.c | Parcial | Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones. |
| REP-001.e | Parcial | Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones. |
| REP-001.g | Parcial | Cierre con cero cajas pendientes, deuda conciliada y 32 sin asistencia; fuente exige otras condiciones. |
| REP-003.a | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.b | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.c | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.d | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.e | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.f | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.g | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |
| REP-003.h | Parcial | Totales de octubre y disponibles conciliados al 13/10; no mes completo ni todas las variantes. |

## Métodos automatizados complementarios

41 métodos vinculados a esta jornada; resultados conservados, no reejecutados para esta entrega.

| Método | Resultado | Aserciones | Duración del test (s) |
|---|---|---|---|
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_alumnos | Aprobado | 9 | 32.846691 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_caja | Aprobado | 9 | 0.050588 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_grupos | Aprobado | 9 | 0.035545 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_cashflow | Aprobado | 9 | 0.058854 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_liquidaciones | Aprobado | 9 | 0.040682 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_usuarios | Aprobado | 9 | 0.028867 |
| AccesoSinPermisoTest::test_profesor_sin_permiso_en_configuraciones | Aprobado | 9 | 0.050764 |
| AccesoSinPermisoTest::test_operativo_sin_permiso_en_cashflow | Aprobado | 9 | 0.280122 |
| AccesoSinPermisoTest::test_operativo_sin_permiso_en_liquidaciones | Aprobado | 9 | 0.225931 |
| AccesoSinPermisoTest::test_operativo_sin_permiso_en_usuarios | Aprobado | 9 | 0.219903 |
| AccesoSinPermisoTest::test_operativo_sin_permiso_en_configuraciones | Aprobado | 9 | 0.073699 |
| AccesoSinPermisoTest::test_admin_rechazado_en_cuenta_protegida_vuelve_a_su_tablero | Aprobado | 9 | 0.526771 |
| AccesoSinPermisoTest::test_sin_sesion_se_sigue_pidiendo_ingresar | Aprobado | 2 | 0.266388 |
| AccesoSinPermisoTest::test_cuenta_inactiva_no_conserva_acceso | Aprobado | 3 | 0.271446 |
| CajaRechazoMotivoT37Test::test_rechazar_sin_motivo_muestra_el_error_en_ambas_pantallas | Aprobado | 18 | 1.376741 |
| CajaRechazoMotivoT37Test::test_rechazar_con_solo_espacios_no_cambia_la_caja | Aprobado | 18 | 0.069809 |
| CajaRechazoMotivoT37Test::test_rechazar_con_mas_de_500_caracteres_no_guarda | Aprobado | 35 | 0.160371 |
| CajaRechazoMotivoT37Test::test_motivo_valido_se_guarda_y_la_operativa_lo_ve_en_el_resumen | Aprobado | 10 | 0.042384 |
| CajaRechazoMotivoT37Test::test_servicio_directo_rechaza_vacio_y_espacios_sin_escribir | Aprobado | 7 | 0.031875 |
| CajaRechazoMotivoT37Test::test_servicio_directo_rechaza_mas_de_500_y_acepta_500 | Aprobado | 4 | 0.063692 |
| CajaRechazoMotivoT37Test::test_ambos_modales_exigen_motivo_y_no_inventan_un_minimo | Aprobado | 9 | 0.210443 |
| CajaRechazoMotivoT37Test::test_consultar_rechazada_historica_no_rellena_su_motivo | Aprobado | 3 | 0.06139 |
| CajaVerificacionMovimientosT36Test::test_tildar_destildar_y_recargar_conserva_el_estado_guardado | Aprobado | 12 | 0.412554 |
| CajaVerificacionMovimientosT36Test::test_validar_con_todo_tildado_conserva_marcas_y_refleja_una_sola_vez | Aprobado | 9 | 0.081675 |
| CajaVerificacionMovimientosT36Test::test_validar_con_uno_sin_tildar_avisa_y_no_escribe | Aprobado | 9 | 0.271469 |
| CajaVerificacionMovimientosT36Test::test_validar_desde_resumen_lleva_a_detalle_con_cantidad_faltante | Aprobado | 7 | 0.126486 |
| CajaVerificacionMovimientosT36Test::test_servicio_directo_no_salta_la_verificacion | Aprobado | 4 | 0.051667 |
| CajaVerificacionMovimientosT36Test::test_caja_sin_movimientos_se_valida | Aprobado | 3 | 0.085625 |
| CajaVerificacionMovimientosT36Test::test_la_operativa_y_el_profesor_no_pueden_tildar | Aprobado | 7 | 0.085233 |
| CajaVerificacionMovimientosT36Test::test_rechazar_conserva_marcas_y_corregir_solo_destilda_el_movimiento_cambiado | Aprobado | 8 | 0.132612 |
| CajaVerificacionMovimientosT36Test::test_una_actualizacion_masiva_tambien_invalida_la_revision | Aprobado | 5 | 0.086158 |
| CajaVerificacionMovimientosT36Test::test_cancelados_visibles_exigen_tilde_pero_no_se_suman_en_cashflow | Aprobado | 4 | 0.110427 |
| CajaVerificacionMovimientosT36Test::test_una_pantalla_vieja_no_puede_marcar_contenido_distinto | Aprobado | 5 | 0.08259 |
| CajaVerificacionMovimientosT36Test::test_no_se_verifica_un_movimiento_por_otra_caja | Aprobado | 3 | 0.091431 |
| CobroReciboAccesoTest::test_despues_de_cobrar_la_confirmacion_incluye_enlace_al_recibo | Aprobado | 10 | 2.381369 |
| CobroReciboAccesoTest::test_ficha_del_alumno_muestra_enlace_al_recibo_en_cada_fila_del_historial | Aprobado | 7 | 0.46314 |
| CobroReciboAccesoTest::test_profesor_no_puede_acceder_al_recibo | Aprobado | 1 | 0.063735 |
| ReciboErrorSanitizadoTest::test_error_en_recibo_cuota_devuelve_mensaje_amigable_sin_detalles_tecnicos | Aprobado | 7 | 0.834428 |
| ReciboErrorSanitizadoTest::test_error_en_recibo_liquidacion_devuelve_mensaje_amigable_sin_detalles_tecnicos | Aprobado | 7 | 0.043318 |
| SeguridadRutasProfesorTest::test_profesor_no_puede_entrar_a_dominios_operativos_o_de_dinero | Aprobado | 5 | 0.071401 |
| SeguridadRutasProfesorTest::test_profesor_conserva_acceso_a_clases | Aprobado | 1 | 0.042346 |

[JUnit](../evidencia-seguridad-2026-10-23/junit.xml) · [Preflight, incidencias del arnés y límites](../evidencia-seguridad-2026-10-23/LEEME.md)

## Producción y continuidad

Selección de trabajo: 69 códigos únicos; 20 con evidencia parcial. No reemplaza PRU-05. Los requisitos previos siguientes son estados documentales del tablero, no nuevos bugs reproducidos.

- B3: Las excepciones del contrato no existen — sin_empezar; responsable Codex.
- T12: Manual del usuario: contenido por tarea y por rol — sin_empezar; responsable nadie.
- T13: Ayuda dentro de Wings: entrada en el menu y signo de pregunta en cada pantalla — sin_empezar; responsable Carlos.
- T25: Cobrar y Caja: cuatro reglas decididas por Carlos que el sistema hoy no cumpliria — sin_empezar; responsable nadie.
- T45: Cambiar hora o comision de un deporte: solo sin liquidaciones abiertas y rige desde el mes siguiente — sin_empezar; responsable nadie.
- T46: Desactivar a una profesora: avisar, reasignar clases futuras, poder liquidarle lo dictado y desactivar su usuario — sin_empezar; responsable nadie.
- T51: Editar o eliminar un movimiento manual de caja deja rastro, igual que en Cashflow — sin_empezar; responsable nadie.
- T54: Buscar alumnos: con y sin acento, por cualquier parte del nombre y ofreciendo opciones desde la primera letra — sin_empezar; responsable nadie.
- T62: Cancelar una clase con lista tomada avisa; no se cancela si esta en una liquidacion cerrada o pagada. Reactivar controla choques y conserva el motivo — sin_empezar; responsable nadie.
- T65: El duenio puede eliminar un alumno o una clase creados por error, si no tienen cobros ni asistencias — sin_empezar; responsable nadie.
- T70: Reporte de Alumnos: cantidad de altas y de bajas del mes — sin_empezar; responsable nadie.
- T71: Deuda de alumnos dados de baja: se muestra aparte, la baja avisa cuanto debe y pide motivo, y el duenio puede darla por perdida — sin_empezar; responsable nadie.

Otro agente revisa código/evidencia/pantallas con herramientas permitidas, deja su identidad y reporte y actualiza el registro. No firmar por el nombre de otro chat. Si también usa Codex, debe ser otra cuenta conforme al acuerdo de Carlos.
Las propuestas no son órdenes de alterar datos de negocio ni de cambiar vistas sin el protocolo del repositorio.
