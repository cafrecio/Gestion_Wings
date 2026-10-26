# PRU-04 · Día21 · reanudación autorizada

**Codex CyE · 26/10/2026 · TEST/wingstest · PENDIENTE DE REANUDAR.**

## Autorización y trazabilidad

[Carlos autorizó](evidencia-dia-19/10-autorizacion-reanudacion.md) volver al28/10, repetir19.5 con confirmación manual suya, completar19.4a/19.6b/19.7a, cerrar y repetir noche19 y controles20–22. No se repiten cobros ni mañana19. [Primera corrida conservada](evidencia-dia-21/informe-primera-corrida.md). [Guion vigente](GUION-DIAS-19-22.md).

Último reloj de esta reanudación: **29/10/2026 15:00**. La primera corrida había llegado al31/10, con los días20–22 sin operar. No confundir ambos intentos.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 21.1 · Verónica | Toma la lista de **Patín Intermedias 16–17** (#22): 18 alumnas; todas presentes menos **Berenice Sánchez**.<br>**Esperado:** Esperado según el catálogo: la lista indica que Berenice, que es Deudor, no puede entrar por deuda, sin mostrar importes (ASI-013). Guarda con 17 presentes. | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.2 · Pablo | **Berenice Sánchez** (debe septiembre y octubre, $86.000) paga **$20.000** de septiembre en efectivo. Primero sin motivo; si lo pide, «Paga el resto el sábado».<br>**Esperado:** Esperado según el catálogo: sin motivo no cobra (COB-009). Con motivo, septiembre queda con saldo $23.000, Berenice sigue Deudor y el total adeudado baja $20.000 (COB-008). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.3 · Pablo | **Felipe Sosa** paga agosto y octubre, **$76.000** en efectivo, y Pablo descarga el recibo.<br>**Esperado:** Pasa a **Al día**. El recibo detalla agosto $38.000 y octubre $38.000, total $76.000 (REC-002). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.4 · Pablo | **Santino Romero** deja el club debiendo octubre ($28.000). Pablo lo da de baja **con el interruptor del listado de Alumnos**.<br>**Esperado:** Queda Inactivo y su cuota pendiente sigue a la vista en la ficha (ALU-025). Sale de Cobranza: 103 activos y el total adeudado baja $28.000. Ya no aparece en el buscador de Cobrar (ALU-017). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.5 · Dueño | Lleva **$500.000** del efectivo al **Banco Galicia** (Cashflow → Transferir).<br>**Esperado:** Un solo formulario: salida Efectivo, destino Galicia. Efectivo baja y Galicia sube $500.000; los ingresos y gastos del mes no cambian (CSH-018). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.6 · Rocío | Pasa a mediodía y, desde Movimientos, intenta **editar el arreglo de $12.000** de la caja de Pablo (y, si no hay botón, entrando a su dirección de edición).<br>**Esperado:** Acceso restringido; la caja de Pablo no cambia (MOV-016). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.7 · Pablo | En **Movimientos**, busca el cobro de Joaquín que hizo Sandra el 28/10.<br>**Esperado:** Lo encuentra, con quién lo cobró. No ve la transferencia ni los gastos del dueño (MOV-012). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |
| 21.8 · Pablo | Antes de que corra el cierre de la noche, mira su caja.<br>**Esperado:** El esperado, **$270.000**, cuenta solo el efectivo; los $171.000 de Mercado Pago figuran aparte (CAJ-036). | **No ejecutado** | Todavía no reanudado. Los controles aquí enlazados son los de la primera corrida fallida, no un intento nuevo. No repetir cobros anteriores. [TXT](evidencia-dia-21/00-reloj.txt) · [TXT](evidencia-dia-21/01-manana-simulacion.txt) |

## Salidas de consola

**Estos dos archivos corresponden a la primera corrida fallida; no se repitieron aún en la reanudación.**

### 00-reloj.txt

[Archivo](evidencia-dia-21/00-reloj.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es viernes 30 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 01-manana-simulacion.txt

[Archivo](evidencia-dia-21/01-manana-simulacion.txt)

```text
Paso 1 falló, no se guardó NADA del plan: Hay un turno abierto. Cerralo antes de abrir otro.
```

## Entrega y límites

Objetivo pendiente, no cierre deT14. Tabla externa y ESTADO-ACTUAL.md intactos. Sin producto ni despliegue. Se conserva evidencia del intento anterior; el control documental independiente anterior vale solo para febabcd6. Sin push.
