# Día 14 — viernes 23/10/2026

Sitio: test.gestionar-te.com.ar · staging/wingstest, datos ficticios. Ejecutor: Codex CAB.

Esperados literales congelados antes de operar. En el día 16 el usuario Dueño proviene del encabezado del guion. Carlos confirmó el despliegue y ordenó registrar y saltear dudas o pasos que no se puedan probar, sin detener el recorrido.

| # | Quién | Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|---|
| 14.1 | Pablo | Alta de **Valentín Ortega**: nacido el 10/02/2010, Fútbol Principiantes 2 veces, ingreso hoy. Primero mal: DNI «45A12» y nacimiento 10/02/2030. | Rechaza con avisos en castellano en cada campo, sin perder lo cargado (ALU-003). | No guardó y conservó nombre, apellido, DNI, fechas, grupo, frecuencia y tutor. Avisos en castellano: «Fecha de nacimiento: Año inválido» y «Fecha de alta: Esperá a verificar la cuota del alta». No apareció aviso específico para DNI 45A12. | Parcial |
| 14.2 | Pablo | Lo corrige: DNI 50123987, nacido el 10/02/2010, tutor Raúl Ortega (11-5551-3434). Guarda. | Inscripción $5.000; octubre **$24.500** (el 70% de $35.000); En plazo, vence 30/10; total $29.500. | Alta guardada una vez: inscripción $5.000 y octubre $24.500, total $29.500; Fútbol Principiantes 2 veces, En plazo, vence 30/10/2026. Tutor y fecha correctos en ficha. | Sí |
| 14.3 | Pablo | Raúl paga **$29.500** en efectivo. | **Al día**. | Pago CUOTA-59 por $29.500 EFE registrado una vez; ficha posterior Al día, inscripción pendiente $0, historial por $29.500. Total y reparto $5.000/$24.500 observados antes de confirmar. | Sí |
| 14.4 | Pablo | Intenta dar de alta otra vez a **Camila Benítez** en Patín, con su DNI 51200700. | No la deja: ya está anotada en Patín (ALU-004). | Rechazó el envío: «Ya existe un alumno con ese DNI en el mismo deporte», junto al DNI y en el resumen de errores. Conservó los datos y no creó otro registro. | Sí |
| 14.5 | Pablo | **Mora Benítez** se anota también en **Fútbol Principiantes**, 1 vez, ingreso hoy. | No le cobra otra inscripción: ya la pagó en Patín. Octubre **$19.600** (el 70% de $28.000) (ALU-005). | Segundo registro deportivo de Mora guardado en Fútbol Principiantes 1 vez. Mismo DNI y tutor, alta 23/10. Octubre $19.600 y sin otra inscripción: la ficha remite al cargo único de Patín ya pagado. | Sí |
| 14.6 | Pablo | Laura, la mamá de Mora, paga $19.600 por Mercado Pago. | La inscripción de Fútbol de Mora pasa a **Al día**. | Pago CUOTA-60 por $19.600 MP registrado una vez. La ficha posterior de Fútbol muestra Al día; se mantuvo inscripción única en Patín. | Sí |
| 14.7 | Dueño | **Perdona la inscripción** de Valentina López («Beca de inscripción por hermana»). | Con motivo la perdona. Le queda octubre $40.000 y pasa de Deudor a **Moroso** (CBZ-010). | Dueño condonó inscripción $5.000 con motivo exacto «Beca de inscripción por hermana». Ficha posterior muestra saldo de inscripción $0 y octubre $40.000, estado Moroso. No hubo ingreso de dinero. | Sí |
| 14.8 | Pablo | Valentina López paga octubre, $40.000 en efectivo. | **Al día**. | Pago CUOTA-61 por $40.000 EFE, solo octubre, registrado una vez por Pablo. Ficha posterior Al día; inscripción condonada sigue en $0. | Sí |
| 14.9 | Pablo | La familia de **Zoe Flores** había pagado **agosto** ($30.000) por Banco Nación el **30/09**, y nadie lo cargó. Pablo lo cobra con **fecha de pago 30/09/2026**. | Pide confirmar que es una fecha de un mes cerrado. Entra en la caja de hoy con fecha 30/09. El dueño recibe el aviso. El ingreso cuenta en **septiembre**, no en octubre (COB-022). Zoe pasa a **Al día**. | Advertencia de mes cerrado aceptada. CUOTA-62 $30.000 BNA: caja #13 del 23/10, fila y PDF con pago 30/09, período agosto; ficha Al día. Reportes septiembre muestra ingresos/resultado $30.000; octubre mantiene $1.750.850,75. No apareció aviso al dueño en Inicio; esa parte no comprobada. PDF descargado y página renderizada revisada. | Parcial |
| 14.10 | Dueño | Carga mal un movimiento directo en Cashflow: Luz con importe **0**; después con fecha **30/10** (futura). No lo corrige: lo deja sin guardar. | Rechaza los dos con avisos claros. No se guarda nada (CSH-006). | Rechazó por validación del formulario: monto 0 → «El importe debe ser mayor que cero»; monto $1.000 con fecha 30/10 → «El valor debe ser igual o anterior a 23/10/2026». Ambos permanecieron sin enviar; no se corrigió para guardar un movimiento. | Sí |

## Consola y rutina

### Reloj: una ejecución

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es viernes 23 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### Rutina de mañana: una ejecución

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Ramos, Dante por Efectivo — Pago registrado para Ramos, Dante.
 3. Pablo Ledesma cobra a Pereyra, Zoe por Mercado Pago — Pago registrado para Pereyra, Zoe.
 4. Pablo Ledesma cobra a Rossi, Morena por Efectivo — Pago registrado para Rossi, Morena.
 5. Pablo Ledesma cobra a Castro, Mateo por Banco Nación — Pago registrado para Castro, Mateo.
 6. Pablo Ledesma cobra a Lopez, Martina por Mercado Pago — Pago registrado para Lopez, Martina.
 7. Pablo Ledesma Librería $1500 por Efectivo — Movimiento registrado.
 8. Verónica Salinas lista de la #21: 18 alumnos, 11 presentes — Asistencias guardadas.
Cobranza: $516.000,00 · {"AL_DIA":94,"EN_PLAZO":0,"MOROSO":2,"DEUDOR":6}
GUARDADO.
```

### Rutina de noche: una ejecución

```text
 1. Pablo Ledesma cierra la caja #13: esperado $156.000,00, contado $156.000,00 — Caja cerrada.
 2. Admin Prueba valida la caja #13 (13 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $441.000,00 · {"AL_DIA":98,"EN_PLAZO":0,"MOROSO":2,"DEUDOR":4}
GUARDADO.
```

La lista #21 tuvo 18 alumnos y 11 presentes, frente a 19 y 12 esperados. No se repararon datos. El Resumen mostró esperado $156.000 antes de ejecutar la rutina nocturna.

## Cierre y diferencias

Deuda $441.000; 104 activos: 98 Al día, 0 En plazo, 2 Morosos y 4 Deudores. Ingresos de octubre $1.750.850,75; egresos $240.850; resultado $1.510.000,75. Disponible $3.263.000,75: EFE $1.108.900, MP $1.888.550,75, BGA $137.550 y BNA $128.000. Por validar $0, cajas pendientes 0 y sin asistencia 0. Septiembre muestra $30.000 de ingresos por cuota de agosto.

Frente al guion: falta una activa/Al día y $143.000 de ingresos, resultado, MP y disponible por 13.11 no completado; no se repararon datos. Lista #21: 18/11 frente a 19/12. 14.1 parcial por falta de aviso de DNI y 14.9 parcial por aviso al dueño no observado en Inicio.

## Incidente de evidencia

Después de recuperar la pestaña por el bloqueo de confirmación, los auxiliares iniciales conservaron la pestaña anterior. Se detectó contrastando los TXT con la pantalla activa. Los archivos con nombres de casos posteriores a esa recuperación se conservan como incidente y no acreditan esas pantallas. Las observaciones directas del recorrido sí se leyeron de la pestaña activa. Desde 14.8 se usa un auxiliar con pestaña explícita; los archivos con «recaptura» acreditan únicamente el estado posterior, sin repetir operaciones. No se presentan los archivos afectados como capturas válidas del caso.


## Evidencia y revisión

Resultado de esta jornada: 2 Parcial · 8 Sí. [Índice de evidencia](evidencia-dia-14/LEEME.md) · [Galería de las cuatro jornadas](EVIDENCIA-DIAS-13-16.html#dia-14).

T14 continúa en curso. Esta entrega requiere verificación independiente; no constituye cierre de la tarea ni aprobación de todos los casos. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se controlaron estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.
