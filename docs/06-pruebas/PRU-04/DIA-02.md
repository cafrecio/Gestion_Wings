# Día 2 — domingo 11/10/2026

Inicio, Cobranza y Reportes coinciden: ingresos $164.000 y deuda $2.010.000.
Los 22 alumnos en plazo pasaron a Moroso; hoy no hay clases y Mariela conserva su asistencia.
El día tiene hallazgos: Mariela abre clases ajenas; Sandra pudo entrar tras un bloqueo y recibe el pedido de abrir caja.

Sitio de prueba, por menú y botones en Chrome; expectativas anotadas antes de actuar. Reloj indicado: domingo 11/10/2026, 10:00, sin modificarlo. El control integrado falló; se usó Chrome con clics automatizados, como el Día 1. Sin consultar código ni base, ejecutar órdenes contra Wings, cobrar, abrir caja o arreglar nada.

| Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|
| 1. Inicio admin | Domingo 11; ingresos $164.000; deuda $2.010.000; avisos pertinentes. | Hasta el 11 de octubre; Clases confirma domingo. $164.000 y $2.010.000. Cajas/revisión/liquidaciones pendientes: 0; Sin asistencia: 33 pendientes históricos. | Sí |
| 2. Cobranza y dos fichas | Octubre vencido; deuda $2.010.000; contar estados y comparar con ayer. | 65 al día, 0 en plazo, 22 morosos, 13 deudores; ayer 65/22/0/13, comprobado en su captura. Total intacto. Renata Cabrera: solo octubre $43.000, Moroso. Bautista Ferrari: solo octubre $15.000, Moroso. | Sí |
| 3. Clases | Ninguna hoy; identificar qué clases cuenta Sin asistencia. | Domingo 11: 0. Dos páginas: 33 clases Finalizada/Pendiente del 24/09 al 10/10, 14 de septiembre y 19 de octubre. Ayer eran 32; la adicional es Fútbol del sábado 10, 11–12, Quintana. Es pendiente histórico, no actividad de hoy. | Sí |
| 4. Sandra | Caja del sábado cerrada y validada; sin reclamos; no cobra sin abrir. | Ingresó tras unos diez minutos de bloqueo. Su Caja muestra sábado VALIDADA; hoy $0 cobrado/0 cajas, sin reclamo del turno anterior. Inicio y Caja piden abrir/contar efectivo aunque es domingo. Cobrar desde Cobranza la lleva a Apertura, no al cobro. No abrió. | Parcial |
| 5. Mariela | Sin clases hoy; clase del sábado conserva 6 presentes/2 ausentes; sin plata ni listado general de alumnos. | Domingo: 0. Por filtros/Ver, sábado 10–11: 8 alumnos, 6 presentes/2 ausentes. Menú solo Clases. También abre una clase de Lucía con 26 alumnos y Guardar. No guardó. | Parcial |
| 6. Reportes octubre | Ingresos $164.000; por cobrar $2.010.000; inscripción separada; nada sin clasificar. | Octubre hasta 11/10: ingresos $164.000, cuotas $159.000 e inscripción $5.000 en renglón propio. Por cobrar $2.010.000; coincide con Inicio/Cobranza. Sin aviso de sin clasificar. | Sí |

Hallazgos:

- **Frena:** Mariela sigue abriendo clases ajenas: Lucía, lunes 12, 16–17, Patín Principiantes; ve 26 alumnos y Guardar. No se probó guardar. [Listado](dia02-03-profesora-clases-ajenas.png) / [Clase](dia02-04-profesora-abre-clase-ajena.png).
- **Molesta:** al principio Sandra recibe «Demasiados intentos seguidos», sin plazo de espera; recién pudo entrar unos diez minutos después. Admin y Mariela ingresaban. Se destrabó sin intervención de Codex; causa no investigada. [Captura](dia02-02-login-espera.png).
- **Molesta:** domingo sin actividad, Inicio pide declarar el cambio y ofrece Abrir; Caja pide contar efectivo. No reclama deuda del turno anterior, pero tampoco distingue el día cerrado esperado. [Captura](dia02-05-domingo-ofrece-abrir.png).

No se probó enviar un pago ni guardar asistencia ajena: el pedido prohíbe cobros/cambios. El bloqueo de Cobrar sin caja se comprobó hasta su desvío a Apertura. Se recorrieron las seis tareas; no se investigaron causas en código/base.

Para mañana, lunes 12 feriado: quedan tres clases Programada, ya previstas para cancelar en el paso de T14: Patín Principiantes 16–17, Fútbol Principiantes 16–17 y Patín Intermedias 17–18. No se cancelaron hoy. Caja, importes y asistencia se dejaron como estaban; T14 sigue abierta. El Día 1 conserva sus hallazgos históricos.

Tiempo: unos 21 minutos, incluido preparar y publicar el informe. Lo más caro fue resolver el control del navegador y esperar/reintentar accesos; no dispongo del costo monetario.

---

## Verificación de Claude y decisiones de Carlos — 10/10/2026

Comprobado contra la base del sitio de prueba: el día no escribió nada (4 pagos, 1 caja,
5 movimientos, 8 asistencias, igual que al cierre del día 1) y los estados de cobranza
coinciden con el informe: 65 al día, 0 en plazo, 22 morosos, 13 deudores; $2.010.000.

Los tres hallazgos:

- **La profesora abre clases ajenas — no es defecto.** `PERMISOS-ROLES.md:113` lo permite
  para cubrir suplencias. No volver a informarlo.
- **«Demasiados intentos seguidos» — corregido.** Lo causaba el reloj simulado clavado;
  desde `2e059ff` el reloj avanza solo.
- **Un domingo ofrece abrir caja — queda así, por decisión de Carlos:** «a fin de año, que
  hay eventos, los domingos ensayan, y si son locales en algún torneo también. Los
  domingos podrían ser activos». No volver a informarlo.
