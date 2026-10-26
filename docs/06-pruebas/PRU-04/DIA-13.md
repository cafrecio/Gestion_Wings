# Día 13 — jueves 22/10/2026

Sitio: test.gestionar-te.com.ar · staging/wingstest, datos ficticios. Ejecutor: Codex CAB.

Esperados literales congelados antes de operar. En el día 16 el usuario Dueño proviene del encabezado del guion. Carlos confirmó el despliegue y ordenó registrar y saltear dudas o pasos que no se puedan probar, sin detener el recorrido.

| # | Quién | Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|---|---|
| 13.1 | Sandra | Entra primero con una **clave equivocada** y después con la buena. | Con la mala: un aviso en castellano que no dice si el mail existe, y no entra. Con la buena entra (ACC-002). | Primer intento con clave equivocada: Las credenciales ingresadas no son válidas, en castellano, sin indicar existencia del mail y sin entrar. Con la clave buena ingresó Sandra a su mostrador; caja abierta por la rutina. | Sí |
| 13.2 | Sandra | Carga una **clase particular**: Otras clases → Clase particular, en efectivo, «Clase particular de patín, Lucía». Se equivoca y pone **25000**: eran $20.000. | Queda en su caja por $25.000. El error es a propósito y todavía no se nota. | Registrado ingreso EFE Otras clases/Clase particular por $25.000, observación Clase particular de patín, Lucía, en caja #12 de Sandra. Detalle posterior muestra el renglón por $25.000. | Sí |
| 13.3 | Sandra | **Felipe Sosa** (Deudor: agosto y octubre, $38.000 cada uno) quiere pagar **noviembre**, $38.000 en efectivo, y dejar lo atrasado para fin de mes. Sandra confirma el adelanto y **sigue hasta el final**: si pide motivo, «La familia paga lo atrasado a fin de mes». | Avisa que debe meses anteriores y pide confirmar (COB-033). Confirmado, noviembre queda pagado **sin tocar** agosto ni octubre. Felipe sigue **Deudor** con $76.000. | Total $38.000 EFE comprobado. Se aceptó adelanto y apareció Deudas anteriores pendientes: agosto/octubre $76.000; se confirmó con motivo literal. CUOTA-53 por noviembre, recibo descargado. Ficha posterior: Deudor, agosto $38.000 y octubre $38.000 intactos, pago de noviembre $38.000. | Sí |
| 13.4 | Sandra | Cierra la caja contando **el efectivo real: $113.200**. | Esperado **$118.200**: incluye los $25.000 de la clase. Muestra **faltante $5.000** antes de confirmar y deja cerrar. Retiene $10.000. | Caja #12 CERRADA: esperado $118.200, contado $113.200, faltante $5.000 visible antes de confirmar. Retiene $10.000 y entrega $103.200; comprobados en resumen posterior. | Sí |
| 13.5 | Dueño | En su Inicio toca el aviso de **cajas por validar**. | Lo lleva a la caja de Sandra (REP-017). | Desde Inicio se tocó Cajas por validar 1. Abrió Caja filtrada por pendientes y mostró únicamente la caja #12 de Sandra, cerrada con 11 movimientos, ingresos $160.000/egresos $3.800/neto $156.200. | Sí |
| 13.6 | Dueño | En **Cobranza**, la lista para llamar. | Cada fila muestra el **teléfono** y no el DNI. Primero aparece **Alma Navarro**, con deuda desde noviembre de 2025 (CBZ-019, T81). | Cobranza por defecto lista 13 alumnos; cada celda muestra teléfono, no DNI. Primera Alma Navarro, teléfono 1145129905, deuda desde noviembre de 2025. Total $707.000 y estados 89 Al día/0 En plazo/5 Morosos/8 Deudores. | Sí |
| 13.7 | Dueño | Ve el faltante de $5.000 y no valida. **Rechaza** la caja: primero sin motivo, después con «La clase particular fue de $20.000, no de $25.000». | Sin motivo no rechaza. Con motivo la caja queda **Rechazada**. Nada pasa a Cashflow (CAJ-029). | Sin motivo, Completa este campo y caja CERRADA. Con motivo literal quedó RECHAZADA. Cashflow la conserva en Mostrador · sin validar, con aviso No suman a los totales de Cashflow de arriba; no entró al listado confirmado. Saldos confirmados permanecen EFE $859.700 y MP $1.740.950,75. | Sí |
| 13.8 | Sandra | Ve su caja rechazada con el motivo. **Corrige** la clase particular de 25000 a **20000**. Al volver a cerrar, primero intenta cambiar lo contado a 118200. | Con otro contado: «La corrección no puede cambiar el efectivo que ya se contó y entregó» (CAJ-031). | Sandra vio RECHAZADA con motivo literal y corrigió el movimiento a $20.000. En el recierre la pantalla conserva contado $113.200/cambio $10.000/entrega $103.200 como texto, sin campos editables; no permite intentar 118200 por pantalla. El mensaje de rechazo del servidor para otro contado no se pudo ejercitar por esta vía. | Parcial |
| 13.9 | Sandra | Vuelve a cerrar con lo contado original, **$113.200**. | Ahora el esperado es **$113.200**, diferencia $0. Queda Cerrada (CAJ-030). | Caja #12 volvió a CERRADA. Antes de confirmar: esperado/contado $113.200, diferencia $0, cambio $10.000, entrega $103.200. Resumen posterior conserva esos importes. | Sí |
| 13.10 | Dueño | Tilda y **valida**. | Validada y reflejada. La clase particular entra por **$20.000**. | Resumen comprobado antes de validar: ingresos $155.000, egresos $3.800, neto $151.200, esperado/contado $113.200, diferencia cero. Se verificaron 11/11 movimientos y quedó VALIDADA. Cashflow confirmado muestra la clase particular del 22/10 por $20.000. | Sí |
| 13.11 | Dueño | **Julieta Álvarez** vuelve: la reactiva desde su ficha y le cobra los **$143.000** por Mercado Pago, sin caja. | Vuelve con su deuda tal cual, Deudor, sin una cuota de octubre nueva. Con el cobro, recibo de cinco meses y **Al día** (ALU-018). | no probado por la herramienta: se intentó la reactivación desde la ficha y se reinició el recorrido una sola vez. La confirmación nativa falló por Emulation.setFocusEmulationEnabled; la ficha del reintento seguía Inactivo, Deudor, con las cinco cuotas originales por $143.000. No se cobró ni se modificó por consola. | Parcial |
| 13.12 | Dueño | Cierre del día | Deuda **$707.000**; **103 activos: 90 Al día, 0 En plazo, 5 Morosos, 8 Deudores**. Ingresos de octubre **$1.613.750,75** (el noviembre de Felipe no suma), egresos **$239.350**, resultado **$1.374.400,75**. Disponible **$3.097.400,75**: Efectivo $962.900, MP $1.931.950,75, Galicia $137.550, Nación $65.000. «Sin asistencia» 0. | Deuda $707.000; 102 activos: 89 al día, 0 en plazo, 5 morosos y 8 deudores. Ingresos octubre $1.470.750,75; egresos $239.350; resultado $1.231.400,75. Disponible $2.954.400,75: EFE $962.900, MP $1.788.950,75, BGA $137.550 y BNA $65.000. Sin asistencia 0; cajas por validar 0. Frente al esperado faltan la reactivación y el cobro de Julieta del paso 13.11: 1 activa y $143.000 de ingreso/MP; no se corrigió fuera del guion. | No |

## Consola y rutina

### Reloj: una ejecución

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es jueves 22 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### Rutina de mañana: una ejecución

```text
 1. Sandra Vidal abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Sandra Vidal cobra a Nuñez, Guadalupe por Efectivo — Pago registrado para Nuñez, Guadalupe.
 3. Sandra Vidal cobra a Ramirez, Lucia por Mercado Pago — Pago registrado para Ramirez, Lucia.
 4. Sandra Vidal cobra a Castro, Martina por Efectivo — Pago registrado para Castro, Martina.
 5. Sandra Vidal cobra a Morales, Carla por Efectivo — Pago registrado para Morales, Carla.
 6. Sandra Vidal cobra a Pereyra, Liam por Efectivo — Pago registrado para Pereyra, Liam.
 7. Sandra Vidal cobra a Gutierrez, Olivia por Mercado Pago — Pago registrado para Gutierrez, Olivia.
 8. Sandra Vidal Limpieza $3800 por Efectivo — Movimiento registrado.
 9. Lucía Gaitán lista de la #31: 12 alumnos, 8 presentes — Asistencias guardadas.
10. Mariela Ocampo lista de la #42: 8 alumnos, 8 presentes — Asistencias guardadas.
11. Hernán Quintana lista de la #69: 15 alumnos, 9 presentes — Asistencias guardadas.
Cobranza: $707.000,00 · {"AL_DIA":89,"EN_PLAZO":0,"MOROSO":5,"DEUDOR":8}
GUARDADO.
```

## Cierre y diferencias

Ver13.12: deuda $707.000;102 activos(89 Al día/0 En plazo/5 Morosos/8 Deudores), ingresos octubre $1.470.750,75, egresos $239.350 y resultado $1.231.400,75. Disponible $2.954.400,75; EFE $962.900, MP $1.788.950,75, BGA $137.550 y BNA $65.000. Cajas pendientes0 y sin asistencia0. Falta la reactivación y cobro de Julieta de13.11: una activa y $143.000 de ingreso/MP frente al guion. No se corrigieron datos por consola.13.8 no permitió editar contado en el recierre; negativo servidor no ejercitado.

## Incidente de evidencia

Después de recuperar la pestaña por el bloqueo de confirmación, los auxiliares iniciales conservaron la pestaña anterior. Se detectó contrastando los TXT con la pantalla activa. Los archivos con nombres de casos posteriores a esa recuperación se conservan como incidente y no acreditan esas pantallas. Las observaciones directas del recorrido sí se leyeron de la pestaña activa. Desde 14.8 se usa un auxiliar con pestaña explícita; los archivos con «recaptura» acreditan únicamente el estado posterior, sin repetir operaciones. No se presentan los archivos afectados como capturas válidas del caso.

## Evidencia y revisión

Resultado de esta jornada: 9 Sí · 2 Parcial · 1 No. [Índice de evidencia](evidencia-dia-13/LEEME.md) · [Galería de las cuatro jornadas](EVIDENCIA-DIAS-13-16.html#dia-13).

T14 continúa en curso. Esta entrega requiere verificación independiente; no constituye cierre de la tarea ni aprobación de todos los casos. La herramienta de Chrome bloqueó el protocolo file al abrir la galería local: se controlaron estructura y enlaces, sin afirmar render de esa galería. Las capturas de Wings sí provienen del sitio en marcha.
