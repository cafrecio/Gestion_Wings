# Día 15 — sábado 24/10/2026

Sitio: test.gestionar-te.com.ar · staging/wingstest, datos ficticios. Ejecutor: Codex CAB.

Esperados literales congelados antes de operar. En el día 16 el usuario Dueño proviene del encabezado del guion. Carlos confirmó el despliegue y ordenó registrar y saltear dudas o pasos que no se puedan probar, sin detener el recorrido.

| # | Quién | Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|---|
| 15.1 | Mariela | Se equivocó en la #49: no vino **Helena**, vino **Olivia Rossi**. Corrige la lista. | Pide motivo porque ya tenía lista tomada. Con «Me confundí de alumna» guarda: Helena ausente y Olivia presente. Propone «Clase adicional» para **Olivia** y **Berenice**, que ya vinieron martes y jueves (ASI-004). | Guardó la corrección y persistió al recargar: Helena ausente, Olivia y Berenice presentes; ambas con Clase adicional (EXTRA), 3/2. No pidió motivo de modificación de lista ya tomada: no hubo campo ni diálogo donde ingresar «Me confundí de alumna». | Parcial |
| 15.2 | Rocío | Llega para un turno corto e intenta **abrir caja**. | «Hay un turno abierto. Cerralo antes de abrir otro.» Muestra la caja abierta de Pablo. No puede cobrar (CAJ-009). | No probado: el sitio rechazó el ingreso de Rocío con la clave de prueba documentada («Las credenciales ingresadas no son válidas»). No se abrió sesión, no se cambió su clave ni se operó por otra cuenta en su lugar. Se saltea y continúa con el dueño. | Parcial |
| 15.3 | Dueño | Alta de un profesor de fútbol: **Martín Acosta**, por hora, $15.000. | Queda activo. En Sueldos aparece «Fútbol-Martín Acosta» (PRO-001). | No se guardó: al seleccionar Fútbol, el alta solo ofrece «Porcentaje de comisión (%)» obligatorio; no aparece modalidad por hora ni valor/hora para ingresar $15.000. Se saltea sin cambiar el deporte ni inventar una comisión. Datos complementarios del intento fueron ficticios. | Parcial |
| 15.4 | Dueño | Hernán avisó que no viene: cambia la profesora de la **#76** (Fútbol Avanzadas 11–12, hoy) a **Martín Acosta**, con el motivo «Hernán enfermo». | Solo ofrece profesores de Fútbol. Queda Martín y el motivo guardado (CLA-009). | La edición de #76 ofrece únicamente «Quintana, Hernán — Fútbol», sin profesoras de Patín. Martín no existe porque 15.3 no pudo guardarse por hora. No se cambió profesor ni se inventó sustituto o motivo guardado. | Parcial |
| 15.5 | Martín | Toma lista en la **#76**: 15 alumnos; presentes **Lucas Gómez y Tomás Giménez**. | Propone «Clase adicional» para los dos: Lucas ya vino el martes y Tomás, martes y jueves. | No probado: depende del alta y asignación de Martín (15.3/15.4), no completadas. No existe su cuenta/clase asignada para ingresar. No se tomó la lista como otro usuario ni se cambiaron las asistencias de #76. | Parcial |
| 15.6 | Dueño | Cambia el horario de la **#77** (sábado 31/10, Fútbol Avanzadas) de 11–12 a **12–13**, con motivo. | Se guarda. Hernán la ve en el horario nuevo (CLA-016). | Dueño guardó #77 del 31/10 de 12:00 a 13:00 con Hernán. Se ingresó luego como Hernán y se abrió esa misma ficha: ve 12–13. No apareció campo ni diálogo para ingresar motivo; esa parte del esperado no se cumplió. | Parcial |
| 15.7 | — | **Pablo se va sin cerrar la caja.** No se cierra. | Queda abierta para el domingo. | Se dejó caja #14 de Pablo ABIERTA, apertura 24/10 15:00, sin cierre ni contado declarado. Inicial $10.000, esperado $84.000, dos movimientos por $74.000. No se pulsó Cerrar. | Sí |

## Consola y rutina

### Reloj: una ejecución

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es sábado 24 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### Rutina de mañana: una ejecución

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Rossi, Berenice por Efectivo — Pago registrado para Rossi, Berenice.
 3. Pablo Ledesma Inscripciones $24000 por Efectivo — Movimiento registrado.
 4. Mariela Ocampo lista de la #49: 8 alumnos, 2 presentes — Asistencias guardadas.
Cobranza: $391.000,00 · {"AL_DIA":99,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":4}
GUARDADO.
```

## Cierre y diferencias

Deuda $391.000; 104 activos: 99 Al día, 0 En plazo, 1 Moroso y 4 Deudores. Ingresos de octubre $1.824.850,75; egresos $240.850; resultado $1.584.000,75. Disponible $3.337.000,75, con $74.000 por validar en caja #14 abierta. EFE $1.182.900, MP $1.888.550,75, BGA $137.550 y BNA $128.000. Inicio muestra sin asistencia 0.

Frente al guion, faltan una activa/Al día y $143.000 por 13.11 no completado. 15.1 guardó sin pedir motivo; 15.2 no probado por credencial rechazada; 15.3/15.4/15.5 no completados porque Fútbol solo ofreció comisión en el alta; 15.6 horario visible para Hernán pero sin campo de motivo. La caja se preserva abierta para el domingo.

## Evidencia y revisión

Resultado de esta jornada: 6 Parcial · 1 Sí. [Índice de evidencia](evidencia-dia-15/LEEME.md) · [Galería de las cuatro jornadas](EVIDENCIA-DIAS-13-16.html#dia-15).

T14 continúa en curso. Esta entrega requiere verificación independiente; no constituye cierre de la tarea ni aprobación de todos los casos. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se controlaron estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.
