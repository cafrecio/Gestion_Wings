# Día 29 — sábado 7/11/2026

Codex CAB, ejecución real 29/10/2026 en Chrome sobre `test.gestionar-te.com.ar`, wingstest ficticia. [Guion](GUION-DIAS-29-33.md) leído entero; revisión previa de Claude contrastada en `ESTADO-PRUEBA.md` (commits dfe05842/69cb8ba5). Arranque por pantalla: deuda $2.119.000, 106 activos, caja #53 abierta con esperado $155.500. No se repitió nada de días 24–28.

Reloj 7/11 09:00 una vez. **29.1–29.3 se hicieron antes de la rutina.** Mañana/noche simuladas y guardadas una sola vez. Sin consultas ni cambios directos de base, reparaciones ni cambios de producto.

| # | Caso | Resultado | Pantalla y evidencia |
|---|---|---|---|
| 29.1 | CAJ-015 e; CAJ-009 | cumple | Pablo entra a Caja → Abrir: no ofrece formulario de efectivo; muestra «Hay un turno abierto de Rocío Paz. Debe cerrarse antes de abrir el siguiente.». Bloquea la apertura; variante de texto frente a «Hay un turno abierto. Cerralo antes de abrir otro.» del guion. Inicio ya avisaba «Caja abierta de Rocío Paz», «Abierta ayer a las 09:00. Para atender tu turno, Rocío debe cerrar su caja.». [Captura](evidencia-dias-29-33/29.1-apertura-bloqueada.jpg), [texto](evidencia-dias-29-33/29.1-apertura-bloqueada.txt). |
| 29.2 | CAJ-016 a; CAJ-019: cierre por dueño | cumple | Dueño abre caja #53 de Rocío del 6/11, esperado $155.500; contado $155.000 y cambio $10.000. Previo: «Diferencia Faltante $500.00 Retiro / entrega $145,000.00». Guarda «Caja cerrada.». Resumen CERRADA, cierre 07/11/2026 09:01, contado $155.000, diferencia −$500, cambio $10.000, entrega $145.000. No se validó. [Previo](evidencia-dias-29-33/29.2-previo-cierre-faltante.jpg), [resultado](evidencia-dias-29-33/29.2-caja53-cerrada-por-dueno.jpg). |
| 29.2 | Autor del cierre persistido | no probado | El cierre se ejecutó en sesión Admin Prueba, visible en captura. El Resumen muestra operativo Rocío y fecha de cierre, pero no un campo con quién cerró. No se acredita el registro interno del autor sin consultar la base; pendiente control de Claude. [Texto completo](evidencia-dias-29-33/29.2-caja53-cerrada-por-dueno.txt). |
| 29.3 | CAJ-016 b; CAJ-005 | cumple | Pablo: «Último cierre Caja #53 · $10,000.00», recibido prellenado $10.000. Se confirma el efectivo y abre: «Caja abierta con el efectivo declarado.», caja #54, 07/11 09:02, inicial $10.000. [Propuesta](evidencia-dias-29-33/29.3-apertura-propone-10000.jpg), [abierta](evidencia-dias-29-33/29.3-caja-pablo-abierta.jpg). |
| 29.4 | REP-016 g | cumple | Inicio de Pablo: Federadas 10:00 «Todos ausentes»; Fútbol Avanzadas 11:00 «14 presentes»; caja «Abierta por vos a las 09:02». [Captura](evidencia-dias-29-33/29.4-inicio-todos-ausentes.jpg), [texto](evidencia-dias-29-33/29.4-inicio-todos-ausentes.txt). |
| 29.5 | PRO-004 a | cumple | Intento de desactivar Verónica rechazado: «Tiene 7 clases futuras o en curso. Reasignalas antes de desactivar a la profesora.». El interruptor vuelve a Activo; acceso Reasignar lleva al listado filtrado. La captura `29.5-veronica-baja-bloqueada` es intermedia durante envío; [resultado final](evidencia-dias-29-33/29.5-resultado-bloqueo.jpg), [texto](evidencia-dias-29-33/29.5-resultado-bloqueo.txt). |
| 29.6 | PRO-004 c; CLA-009 | cumple | Se reasignaron por formularios individuales las siete Intermedias: lunes 9, 16, 23 y 30 a las 17–18; viernes 13, 20 y 27 a las 16–17. Se quitó Verónica y se eligió Lucía, conservando fechas/horas. Cada resultado «Clase actualizada correctamente.», Gaitán, Lucía. Sin choque. Después baja: «Profesora inactiva. El usuario vinculado quedó desactivado y sus sesiones cerradas.». [Listado inicial](evidencia-dias-29-33/29.6-siete-clases-antes.txt), capturas `29.6-09nov-reasignada`/`13nov`/`16nov`/`20nov`/`23nov`/`27nov`/`30nov`, [baja final](evidencia-dias-29-33/29.6-veronica-baja-confirmada.jpg). |
| 29.7 | ACC-004 | cumple | Ingreso con correo y clave de prueba de Verónica: «Tu cuenta fue desactivada.». Permanece en login; no entra. No se registran claves en esta entrega. [Captura](evidencia-dias-29-33/29.7-veronica-cuenta-desactivada.jpg). |
| 29.8 | LIQ-006 a | cumple | Nueva liquidación, noviembre 2026: «Salinas, Verónica · Inactiva, con clases dictadas pendientes Patín Por hora 2 clases liquidables». No se seleccionó Generar. [Captura](evidencia-dias-29-33/29.8-veronica-inactiva-liquidacion.jpg), [texto](evidencia-dias-29-33/29.8-veronica-inactiva-liquidacion.txt). |

La revisión automática bloqueó inicialmente entrar a Caja porque no reconoció la revisión previa de tanda 1. No envió apertura/cierre. Se volvió a comprobar el registro versionado de Claude y sus commits; después se permitió el mismo acceso y se completaron los pasos. No quedó ese bloqueo pendiente.

## Cierre por pantalla

Cobranza: **$1.874.000**, **106 activos**, **53 Al día**, **52 En plazo**, **0 Morosos**, **1 Deudor**. [Captura](evidencia-dias-29-33/29-cierre-cobranza.jpg).

Reportes: ingresos **$2.225.000**, egresos **$2.667.780**, resultado **−$442.780**, disponible **$4.462.120,75**, por pagar $0. Deuda, estados, ingresos y egresos coinciden con el guion. [Texto](evidencia-dias-29-33/29-cierre-reportes.txt).

| Medio | Disponible | Confirmado | Por validar |
|---|---:|---:|---:|
| Banco Galicia | $201.050 | $201.050 | $0 |
| Banco Nación | $58.000 | $58.000 | $0 |
| Banco Nacion ahorro | $0 | $0 | $0 |
| Efectivo | $1.725.200 | $1.445.700 | $279.500 |
| Mercado Pago | $2.477.870,75 | $2.214.870,75 | $263.000 |

[Medios](evidencia-dias-29-33/29-cierre-reportes-medios.txt). Inicio: **2 cajas por validar**, **Clases sin asistencia 0**, liquidaciones pendientes 0; «Incluye arrastre · $542,500.00 por validar». [Captura](evidencia-dias-29-33/29-cierre-inicio.jpg).

Noche [GUARDADO](evidencia-dias-29-33/29.9-noche-real.txt): #54 Pablo esperado/contado **$144.000**, Cerrada; ninguna validación hoy. Mañana [GUARDADO](evidencia-dias-29-33/29.0-manana-real.txt): seis cobros $245.000, Federadas 0/8 presentes y Fútbol Avanzadas 14/15. [Reloj](evidencia-dias-29-33/29.0-reloj.txt). Siguiente: día 30 a las 11:00, sin repetir reloj ni rutinas de este día.
