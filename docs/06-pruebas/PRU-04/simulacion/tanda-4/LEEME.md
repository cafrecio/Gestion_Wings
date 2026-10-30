# Tanda 4 (días 41 a 47, 19 al 25/11): simulada, falta el guion — Claude CAB, 30/10/2026

Estado al pasar a CyE: **los siete días están simulados enteros** sobre la base del sitio de prueba al 18/11
(después de la tanda 3 de Codex), con 0 pasos no esperados. Las rutinas están en `../../rutina/dia-41-*.json` a
`dia-47-*.json`. **Falta:** escribir `GUION-DIAS-41-47.md` y `PROMPT-CODEX-DIAS-41-47.md` con estos resultados,
subir las rutinas al sitio de prueba (`git merge --ff-only origin/main` en `/home/wingstest/app`) y correr ahí
`dia-41-manana.json --simular` antes de entregar el prompt (CLAUDE.md, punto 5).

## Cómo retomar en otra máquina

1. Bajar la base del sitio al 18/11 (el reloj sigue ahí hasta que Codex corra la tanda 4):
   `ssh vps "mysqldump --single-transaction wingstest | gzip" > fotos/d40.sql.gz`
2. `bash correr.sh 41`, después 42 … 47. Cada día arranca de la foto del anterior y deja la suya en `fotos/`
   (no se sube). Las salidas quedan en `salida-*.txt`; las de esta corrida ya están subidas.
3. `cierres-41-47.txt`: lo que lee Codex al cierre de cada día (con `cierre.php`).

## Qué pasa cada día (lo nuevo por pantalla; la rutina hace cajas, listas y gastos)

| Día | Fecha | Turno | Lo nuevo |
|---|---|---|---|
| 41 | jue 19/11 | Sandra | Nivel «Infantil» → «Infantiles», repetido rechazado (CAT-004); grupo mal cargado y después Fútbol Infantiles 1×$25.000 y 2×$38.000 (GRU-002); profesor mal cargado y después Gonzalo Ibarra, Fútbol, 35% (PRO-002); alta de **Renzo Acuña** con ingreso 1/12 (4/1/2027 rechazado) y paga solo la inscripción (ALU-007); recibos y caja ajena: Sandra abre el recibo de Rocío, el 99999 no existe, Lucía recibe 403, detalle de caja ajena sí y resumen no (REC-004, ACC-016) |
| 42 | vie 20/11 | Rocío | Diciembre adelantado de Clara Iglesias con el importe mal escrito antes (COB-011); resma con doble Guardar (MOV-015) y corregida en la caja abierta; un movimiento de su caja validada no se cambia (MOV-009); Lucía mira la lista que tomó Mariela y no abre fichas (ASI-016); Internet cargado dos veces y uno eliminado (CSH-008); cierre dejando $15.000 de cambio con $10.000 recibidos (CAJ-022). **La lista de Intermedias la toma Mariela** (cubre a Lucía) |
| 43 | sáb 21/11 | Pablo | Lluvia. La apertura propone los $15.000 (CAJ-022 b); el dueño suma a Lucía a Federadas (CLA-020); Pablo cancela Fútbol Avanzadas por lluvia; caja ajena forzando (ACC-016); menús de cada rol (SIS-013); cierre sin ningún movimiento, $15.000 contados y dejados (CAJ-037) |
| 44 | dom 22/11 | Dueño | Historial de la semana contra Cashflow (MOV-013/014: ver hallazgos); valida la caja sin movimientos (CAJ-037 b); inscripción a $8.000: la de Emma sigue en $5.000 (CFG-003); fichas de Gonzalo y Mariela, con datos reales y sin «Próximamente» (PRO-005, SIS-012) |
| 45 | lun 23/11 | Sandra | Alta de **Valentino Godoy** al 70% ($30.800) con inscripción $8.000: paga $38.800 y **no viene hasta diciembre** (CBZ-017 a el 1/12); el dueño cobra diciembre de Morena sin caja por MP, lo anula (motivo corto rechazado) y lo cobra por Galicia (COB-016, COB-021); Sandra ve los cobros sin Anular; Renzo no cambia la fecha de ingreso porque pagó la inscripción (ALU-015); Cashflow con la columna Val. (CSH-014) |
| 46 | mar 24/11 | Pablo | **Reloj a las 06:13 y esperar las 06:15:** corre el recargo de **Emma Villalba ($2.310)**; ese día ya está Morosa ($30.410). Alta de **Milagros Ferreyra** al 40% ($14.000) + $8.000, paga por MP y va a su primera clase sin motivo; el dueño pasa la clase de Intermedias del 20/11 a Mariela (sin motivo pide, con motivo cambia; Pablo recibe 403) (CLA-010); Internet corregido desde dos pestañas (CSH-010); cierre con doble clic: el segundo «La caja no está en estado editable.» (CAJ-024) |
| 47 | mié 25/11 | Rocío | Calendario de diciembre por rutina (64 clases); **Patín Adultos por pantalla** (aviso de cancha, confirmar: 10 clases) y **Fútbol Infantiles** con Hernán, que choca con Fútbol Avanzadas del 5/12, y después solo con Gonzalo, sábados 10:00 (CLA-006); Emma Morosa y su ficha; el recibo de Valentino diez veces (REC-006); usuario de Gonzalo con datos mal cargados y después bien (USU-002) |

## Cierres

| Día | Deuda | Estados | Ingresos | Egresos | Disponible |
|---|---:|---|---:|---:|---:|
| 41 | $205.200 | 103 Al día, 1 En plazo, 5 Morosos (109 activos, con Renzo) | $4.114.900 | $2.743.280 | $6.373.520,75 |
| 42 | $205.200 | igual | $4.126.900 | $2.774.080 | $6.384.720,75 |
| 43 | $205.200 | igual; 1 caja por validar | $4.126.900 | $2.774.080 | $6.384.720,75 |
| 44 | $205.200 | igual | $4.126.900 | $2.774.080 | $6.384.720,75 |
| 45 | $205.200 | 104 Al día, 1 En plazo, 5 Morosos (110) | $4.179.700 | $2.779.280 | $6.476.320,75 |
| 46 | $207.510 | 105 Al día, 6 Morosos (111) | $4.201.700 | $2.784.580 | $6.493.020,75 |
| 47 | $207.510 | igual | $4.217.700 | $2.788.180 | $6.505.420,75 |

Saldos al 25/11: Efectivo $1.358.800; Mercado Pago $3.405.070,75; Galicia $1.683.550; Nación $58.000.

## Hallazgos de la simulación (antes de Codex)

- **Error del servidor al crear un grupo con dos planes de la misma frecuencia** (dos filas «1 vez/semana»): no
  guarda nada, pero el dueño ve la página de error. La edición sí filtra repetidas; el alta no
  (`GrupoWebController::store`). Se llega por pantalla. Anotado como T99.
- **Avisos con nombres internos**, solo forzando el envío: «El campo planes.0.clases_por_semana no puede ser mayor
  que 7.», «El campo montos_cuota.2026-12 debe ser al menos 0.01.»; y por pantalla «El campo motivo fecha alta es
  obligatorio.» al cambiar la fecha de ingreso. Faltan nombres en `lang/es/validation.php`. En T99.
- «**la profesora** Hernán Quintana ya tiene asignada la clase…»: el aviso de choque nombra en femenino a un profesor.
- **Menús:** la profesora ve Clases y Ayuda; la operativa no tiene «Sin asistencia» en el menú; el dueño ve «Tipos
  de Caja» donde el catálogo dice «Medios de pago». El catálogo (SIS-013) está viejo en eso.
- **Historial:** muestra solo lo que pasó por las cajas, sin límite de 90 días y sin los movimientos directos ni
  los cobros del dueño. El catálogo (MOV-013/014) espera otra cosa. Semana 16 a 21/11: Historial ingresos
  $217.800 y egresos $28.300; Cashflow egresos $50.300 (la diferencia es el Internet del dueño).
- **Mi guion de la tanda 3 decía que el recargo de Emma corría el 25/11:** corre el 24/11 a las 06:15 (corregido).
- **El ejecutor de rutinas** tomaba por buena una serie que pedía confirmar el aviso de cancha y no creaba nada.
  Corregido en `scripts/pruebas/rutina/rutina.php`: ahora falla y pide hacerla por pantalla.
- Diciembre tiene clases el **8/12 y el 25/12 (feriados)**: para la tanda 5, el dueño las cancela.

## Preparado para el 1/12 (tanda 5)

- **Revisión:** Pilar Ramos, Alma Navarro, Facundo Ríos, Valentina Suárez y **Emma Villalba** (alta del 17/11, sin
  pago ni presentes, Morosa con recargo).
- **No va a Revisión aunque no vino:** **Valentino Godoy** (ingresó el 23/11 y pagó; CBZ-017 a).
- **Diciembre ya existe:** Renzo ($25.000 pendiente, ingreso 1/12, inscripción pagada), y pagados Joaquín Vega
  ($48.000), Emma Fernández ($45.000), Dante ($52.000), Clara Iglesias ($30.000) y Morena ($44.000, cobrada dos
  veces por el dueño: la primera anulada).
- **Deudor el 1/12:** Liam (viene y no paga) y los morosos que no pagaron.
- **Liquidación de noviembre:** la clase de Intermedias del 20/11 es de Mariela (cambiada con motivo); Federadas
  del 21/11 tiene dos profesoras; Fútbol Avanzadas del 21/11 cancelada por lluvia.
