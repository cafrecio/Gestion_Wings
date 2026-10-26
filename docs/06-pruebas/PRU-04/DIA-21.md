# PRU-04 · Día 21 · entrega con diferencias

**Codex CyE · 26/10/2026 · TEST/wingstest · RECORRIDO, CAJA VALIDADA; cobertura limitada donde se indica.**

[Guion](GUION-DIAS-19-22.md) · [Autorización de Carlos](evidencia-dia-19/10-autorizacion-reanudacion.md) · [Primera corrida conservada](evidencia-dia-21/informe-primera-corrida.md).

El navegador volvió a responder tras «Fijate ahora». Se retomó20.8 sin repetir cobros, ventas ni mañana20. Último reloj del paquete: **31/10/2026 15:00**. No repetir operaciones ni reloj para revisar.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 21.1 · Verónica | Toma la lista de **Patín Intermedias 16–17** (#22): 18 alumnas; todas presentes menos **Berenice Sánchez**.<br>**Esperado:** Esperado según el catálogo: la lista indica que Berenice, que es Deudor, no puede entrar por deuda, sin mostrar importes (ASI-013). Guarda con 17 presentes. | **Parcial** | Lista22 guardada y recargada:17/18 presentes; Berenice ausente. No muestra aviso de deuda ni bloqueo de entrada para Berenice. Ocho excesos muestran EXTRA predeterminado, conservado. [TXT](evidencia-dia-21/21.1-antes-sin-aviso-deuda.txt) · [TXT](evidencia-dia-21/21.1-persistencia-17.txt) |
| 21.2 · Pablo | **Berenice Sánchez** (debe septiembre y octubre, $86.000) paga **$20.000** de septiembre en efectivo. Primero sin motivo; si lo pide, «Paga el resto el sábado».<br>**Esperado:** Esperado según el catálogo: sin motivo no cobra (COB-009). Con motivo, septiembre queda con saldo $23.000, Berenice sigue Deudor y el total adeudado baja $20.000 (COB-008). | **Parcial** | Aceptó $20.000 efectivo sin motivo (rechazo esperado no ocurrió). Guardado una vez, recibo170; Berenice Deudor $66.000: septiembre23.000/octubre43.000. No se repitió el cobro ni se agregó motivo después. [TXT](evidencia-dia-21/21.2-parcial-20000-sin-motivo.txt) · [TXT](evidencia-dia-21/21.2-guardado-sin-motivo-saldo66000.txt) |
| 21.3 · Pablo | **Felipe Sosa** paga agosto y octubre, **$76.000** en efectivo, y Pablo descarga el recibo.<br>**Esperado:** Pasa a **Al día**. El recibo detalla agosto $38.000 y octubre $38.000, total $76.000 (REC-002). | **Sí** | Felipe quedó Al día tras un único pago EFE76.000. PDF CUOTA171 descargado, texto y render comprobados: agosto38.000 y octubre38.000, total76.000. [TXT](evidencia-dia-21/21.3-felipe-al-dia.txt) · [PDF](evidencia-dia-21/21.3-recibo-cuota-171.pdf) · [PNG](evidencia-dia-21/21.3-recibo-cuota-171.png) |
| 21.4 · Pablo | **Santino Romero** deja el club debiendo octubre ($28.000). Pablo lo da de baja **con el interruptor del listado de Alumnos**.<br>**Esperado:** Queda Inactivo y su cuota pendiente sigue a la vista en la ficha (ALU-025). Sale de Cobranza: 103 activos y el total adeudado baja $28.000. Ya no aparece en el buscador de Cobrar (ALU-017). | **Sí** | Un solo toque en listado dejó Santino inactivo. Ficha conserva octubre28.000 pendiente. Cobranza66.000,103activos/102AlDía/1Deudor; búsqueda Cobrar por DNI no lo encuentra. No reactivado. [TXT](evidencia-dia-21/21.4-santino-inactivo-listado.txt) · [TXT](evidencia-dia-21/21.4-ficha-inactivo-deuda28000.txt) · [TXT](evidencia-dia-21/21.4-cobranza66000-103activos.txt) · [TXT](evidencia-dia-21/21.4-cobrar-sin-resultados.txt) |
| 21.5 · Dueño | Lleva **$500.000** del efectivo al **Banco Galicia** (Cashflow → Transferir).<br>**Esperado:** Un solo formulario: salida Efectivo, destino Galicia. Efectivo baja y Galicia sube $500.000; los ingresos y gastos del mes no cambian (CSH-018). | **Sí** | Transferencia2 registrada una vez, EFE -500.000/Galicia +500.000:1.460.300/637.550. Dashboard conserva ingresos3.126.850,75/egresos270.450 y disponible4.730.400,75. Cashflow Año sí suma ambos tramos en totales brutos (ingresos/egresos+500.000; resultado igual); diferencia de presentación registrada. [TXT](evidencia-dia-21/21.5-antes-dashboard.txt) · [TXT](evidencia-dia-21/21.5-transferencia2-guardada.txt) · [TXT](evidencia-dia-21/21.5-despues-dashboard.txt) |
| 21.6 · Rocío | Pasa a mediodía y, desde Movimientos, intenta **editar el arreglo de $12.000** de la caja de Pablo (y, si no hay botón, entrando a su dirección de edición).<br>**Esperado:** Acceso restringido; la caja de Pablo no cambia (MOV-016). | **Sí** | Rocío ve gasto12.000 en Movimientos sin Editar. Ruta de edición216 observada como Admin devuelve Sin permiso en castellano. No se envió modificación; gasto12.000 conservado luego en caja de Pablo. [TXT](evidencia-dia-21/21.6-rocio-movimientos-sin-editar.txt) · [TXT](evidencia-dia-21/21.6-edicion-ajena-restringida.txt) |
| 21.7 · Pablo | En **Movimientos**, busca el cobro de Joaquín que hizo Sandra el 28/10.<br>**Esperado:** Lo encuentra, con quién lo cobró. No ve la transferencia ni los gastos del dueño (MOV-012). | **Parcial** | Pablo encuentra cobro Joaquín44.000 EFE28/10 en caja36; no muestra quién cobró. Movimientos28–30 ofrece solo rubros operativos y no la transferencia del dueño; filtro28 muestra13filas. [TXT](evidencia-dia-21/21.7-movimientos-28a30.txt) · [TXT](evidencia-dia-21/21.7-joaquin-sin-autor.txt) |
| 21.8 · Pablo | Antes de que corra el cierre de la noche, mira su caja.<br>**Esperado:** El esperado, **$270.000**, cuenta solo el efectivo; los $171.000 de Mercado Pago figuran aparte (CAJ-036). | **Sí** | Caja40 Pablo abierta: esperado270.000=10.000+260.000netoEFE; MP171.000 aparte. Gasto Reparaciones sigue12.000. [TXT](evidencia-dia-21/21.8-caja40-esperado270000.txt) |

## Cierre y diferencias

Caja40 VALIDADA: esperado/contado270.000, diferencia0, cambio10.000. Deuda66.000(Berenice septiembre23.000/octubre43.000);103activos,102Al día/1Deudor. Disponible4.730.400,75: EFE1.460.300/MP2.504.550,75/Galicia637.550/Nación128.000. Ingresos3.126.850,75/egresos270.450/resultado2.856.400,75: ingresos y resultado121.000 menores que guion. Por validar0 y sin asistencia0.

Berenice pagó20.000 sin motivo: rechazo esperado no ocurrió; guardado una vez. Felipe pagó76.000 y PDF171 revisado en texto y render. Santino baja en listado un solo toque, deuda conservada, sin reactivar. Traspaso2 EFE→Galicia500.000: Reportes mantiene ingresos/egresos; Cashflow Año suma ambos tramos a sus totales brutos y conserva resultado. Ocho excesos de plan al tomar22 conservaron EXTRA predeterminado; no se cambió el guion ni se corrigieron datos.

## Salidas de consola de la reanudación

### 10-reloj-reanudacion.txt

[Archivo](evidencia-dia-21/10-reloj-reanudacion.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es viernes 30 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 11-manana-simulacion-reanudacion.txt

[Archivo](evidencia-dia-21/11-manana-simulacion-reanudacion.txt)

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Morales, Agustina $30.000,00 por Efectivo — Pago registrado para Morales, Agustina.
 3. Pablo Ledesma cobra a Ortiz, Micaela $40.000,00 por Mercado Pago — Pago registrado para Ortiz, Micaela.
 4. Pablo Ledesma cobra a Garcia, Melina $43.000,00 por Efectivo — Pago registrado para Garcia, Melina.
 5. Pablo Ledesma cobra a Martinez, Delfina $33.000,00 por Mercado Pago — Pago registrado para Martinez, Delfina.
 6. Pablo Ledesma cobra a Castro, Catalina $45.000,00 por Efectivo — Pago registrado para Castro, Catalina.
 7. Pablo Ledesma cobra a Diaz, Catalina $50.000,00 por Mercado Pago — Pago registrado para Diaz, Catalina.
 8. Pablo Ledesma cobra a Gutierrez, Tomas $28.000,00 por Efectivo — Pago registrado para Gutierrez, Tomas.
 9. Pablo Ledesma cobra a Ferrari, Nicolas $48.000,00 por Mercado Pago — Pago registrado para Ferrari, Nicolas.
10. Pablo Ledesma cobra a Rios, Lola $30.000,00 por Efectivo — Pago registrado para Rios, Lola.
11. Pablo Ledesma Reparaciones menores $12000 por Efectivo — Movimiento registrado.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
SIMULADO: no se guardó nada.
```

### 12-manana-real-reanudacion.txt

[Archivo](evidencia-dia-21/12-manana-real-reanudacion.txt)

```text
 1. Pablo Ledesma abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Pablo Ledesma cobra a Morales, Agustina $30.000,00 por Efectivo — Pago registrado para Morales, Agustina.
 3. Pablo Ledesma cobra a Ortiz, Micaela $40.000,00 por Mercado Pago — Pago registrado para Ortiz, Micaela.
 4. Pablo Ledesma cobra a Garcia, Melina $43.000,00 por Efectivo — Pago registrado para Garcia, Melina.
 5. Pablo Ledesma cobra a Martinez, Delfina $33.000,00 por Mercado Pago — Pago registrado para Martinez, Delfina.
 6. Pablo Ledesma cobra a Castro, Catalina $45.000,00 por Efectivo — Pago registrado para Castro, Catalina.
 7. Pablo Ledesma cobra a Diaz, Catalina $50.000,00 por Mercado Pago — Pago registrado para Diaz, Catalina.
 8. Pablo Ledesma cobra a Gutierrez, Tomas $28.000,00 por Efectivo — Pago registrado para Gutierrez, Tomas.
 9. Pablo Ledesma cobra a Ferrari, Nicolas $48.000,00 por Mercado Pago — Pago registrado para Ferrari, Nicolas.
10. Pablo Ledesma cobra a Rios, Lola $30.000,00 por Efectivo — Pago registrado para Rios, Lola.
11. Pablo Ledesma Reparaciones menores $12000 por Efectivo — Movimiento registrado.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
GUARDADO.
```

### 30-noche-reanudacion.txt

[Archivo](evidencia-dia-21/30-noche-reanudacion.txt)

```text
 1. Pablo Ledesma cierra la caja #40: esperado $270.000,00, contado $270.000,00 — Caja cerrada.
 2. Admin Prueba valida la caja #40 (12 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $66.000,00 · {"AL_DIA":102,"EN_PLAZO":0,"MOROSO":0,"DEUDOR":1}
GUARDADO.
```

## Entrega

T14 sigue en curso, tiene=Claude: revisión independiente, control interno y velocidad pendientes. No aceptación integral. Tabla externa, producto y ESTADO-ACTUAL.md intactos; sin suite, despliegue ni push. [Resumen](evidencia-dia-19/resumen-reanudacion.json).
