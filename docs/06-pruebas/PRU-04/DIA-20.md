# PRU-04 · Día 20 · entrega con diferencias

**Codex CyE · 26/10/2026 · TEST/wingstest · RECORRIDO, CAJA VALIDADA; cobertura limitada donde se indica.**

[Guion](GUION-DIAS-19-22.md) · [Autorización de Carlos](evidencia-dia-19/10-autorizacion-reanudacion.md) · [Primera corrida conservada](evidencia-dia-20/informe-primera-corrida.md).

El navegador volvió a responder tras «Fijate ahora». Se retomó20.8 sin repetir cobros, ventas ni mañana20. Último reloj del paquete: **31/10/2026 15:00**. No repetir operaciones ni reloj para revisar.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 20.1 · Rocío | Vende un par de **patines, $85.000 por MP** (Indumentaria → Patines) y una **remera, $18.000 en efectivo**.<br>**Esperado:** Los dos aparecen como ingresos de la caja; solo la remera sube el efectivo esperado (MOV-002). | **Sí** | Patines85000MP y remera18000efectivo registrados una vez; esperado178200, MP241000, ingresos413000. [TXT](evidencia-dia-20/20.1-caja-178200.txt) · [TXT](evidencia-dia-20/20.1-ventas-guardadas.txt) |
| 20.2 · Dueño | Se inunda la cancha: **cancela la clase de hoy de Fútbol Avanzadas 19–20** (#71). Primero sin motivo, después con «Cancha inundada».<br>**Esperado:** Sin motivo no cancela. Con motivo sigue en el listado de hoy, apagada, con «Cancelada» y el motivo (CLA-011). | **Sí** | Sin motivo no cancela (El motivo es obligatorio); Cancha inundada cancela solo71 y permanece en hoy, Cancelada con motivo. [TXT](evidencia-dia-20/20.2-sin-motivo.txt) · [TXT](evidencia-dia-20/20.2-cancelada-con-motivo.txt) · [TXT](evidencia-dia-20/20.2-listado-cancelada.txt) |
| 20.3 · Hernán | Entra a la #71 para tomar lista.<br>**Esperado:** La ve cancelada: aviso de bloqueo y Guardar deshabilitado; no se guarda ninguna marca (ASI-005). | **Sí** | Hernán ve clase71 Cancelada, aviso no se pueden registrar asistencias y Guardar deshabilitado. No se enviaron marcas. Ingreso recuperado mediante reinicio de un contador autorizado por Carlos. [TXT](evidencia-dia-20/20.3-hernan-clase-cancelada-bloqueada.txt) · [MD](evidencia-dia-20/32-autorizacion-y-alcance-contador.md) |
| 20.4 · Dueño | Crea una **clase única de recuperación**: Fútbol Avanzadas, **sábado 31/10**, con Hernán. Primero pone de **14:00 a 13:00**; después corrige a **14:00 a 15:00**.<br>**Esperado:** Con la hora de fin antes del inicio, rechaza con un aviso claro y conserva lo cargado (CLA-005). Con 14 a 15 se crea; solo ofrece profesores de Fútbol, y Hernán la ve en sus clases del 31 (CLA-002). | **Sí** | Horario14–13 rechazado conservando campos;14–15 creó clase82 única31/10 con Hernán. Solo Martín/Hernán de Fútbol ofrecidos. Visibilidad por Hernán comprobada22.2. [TXT](evidencia-dia-20/20.4-horario-invalido-rechazado.txt) · [TXT](evidencia-dia-20/20.4-clase-creada.txt) · [TXT](evidencia-dia-22/22.2-hernan-ve-recuperacion82.txt) |
| 20.5 · Mariela | Quiere adelantar la lista de la clase del **sábado 31, Federadas 10–11** (#50).<br>**Esperado:** No deja guardar una clase cuyo día no llegó, y lo explica (ASI-011). | **Sí** | Mariela abrió50 del31/10 estando29/10. Aviso Esta clase todavía no ocurrió; casillas y Guardar deshabilitados. No se guardaron marcas. [TXT](evidencia-dia-20/20.5-mariela-clase-futura-bloqueada.txt) |
| 20.6 · Rocío | La familia de **Lucas Gómez** cambió de celular: lo corrige de 1145123401 a **1145123499**.<br>**Esperado:** La ficha y el listado muestran el número nuevo; sus pagos y cuotas no cambian (ALU-012). | **Sí** | Celular1145123401→1145123499 en ficha y listado. Tutor conserva1145123401. Al día y pagoOct33.000/recibo4 iguales; sin nueva cuota visible. [TXT](evidencia-dia-20/20.6-lucas-antes.txt) · [TXT](evidencia-dia-20/20.6-listado-telefono-nuevo.txt) · [TXT](evidencia-dia-20/20.6-ficha-telefono-nuevo.txt) |
| 20.7 · Rocío | En su **Inicio**, a mitad del turno.<br>**Esperado:** «Caja abierta», con la hora en que la abrió, Cobrar y Registrar (REP-016). | **Sí** | Inicio Rocío muestra Caja abierta, hora15:00, Cobrar y Registrar. [TXT](evidencia-dia-20/20.7-inicio-rocio-caja-abierta.txt) |
| 20.8 · Rocío | En **Movimientos**, busca los cobros del miércoles.<br>**Esperado:** Ve los cobros de cuota de Sandra del 28/10, con quién los hizo, y puede abrir sus recibos. No ve gastos del dueño (MOV-011). | **Parcial** | Ve los 11 cobros de cuota del 28/10 y otros 2 movimientos operativos; no muestra quién los hizo ni enlaces a recibos. El enlace a caja 36 devuelve Sin permiso. Rubros del dueño no ofrecidos en el filtro. [TXT](evidencia-dia-20/41-20.8-movimientos-28.txt) · [TXT](evidencia-dia-20/42-20.8-caja-ajena-restringida.txt) |
| 20.9 · Rocío | En **Caja**, el listado de cajas.<br>**Esperado:** Esperado según el catálogo: ve solo sus cajas de los últimos 30 días (la del 26/10 y la de hoy), no las de Sandra ni Pablo (CAJ-035). | **Parcial** | Listado muestra 3 cajas: 29/10 #38, 26/10 #16 y 19/10 #8. El guion esperaba dos. No aparecen cajas de Sandra ni Pablo; no se verificó aquí el límite temporal de 30 días. [TXT](evidencia-dia-20/43-20.9-cajas-rocio.txt) |
| 20.10 · Rocío | Quiere crear una clase de prueba para una amiga: Clases → Nueva (y, si no aparece el botón, entrando a la dirección `/clases/create`).<br>**Esperado:** No puede: crear clases es del dueño; forzando, acceso restringido (CLA-008). | **Sí** | No hay Nuevo en Clases. /clases/create muestra restricción en castellano; Volver regresa a Inicio de Rocío. [TXT](evidencia-dia-20/44-20.10-clases-sin-nuevo.txt) · [TXT](evidencia-dia-20/45-20.10-acceso-restringido.txt) |

## Cierre y diferencias

Caja38 VALIDADA: esperado/contado178.200, diferencia0, cambio10.000. Deuda190.000;104activos,101Al día/1Moroso/2Deudores. Disponible4.299.400,75: EFE1.700.300/MP2.333.550,75/Galicia137.550/Nación128.000. Ingresos2.683.850,75/egresos258.450/resultado2.425.400,75: ingresos y resultado121.000 menores que guion, causa sin atribuir. Por validar0 y sin asistencia0.

20.8 ve cobros ajenos pero sin autor ni recibo accesible; caja36 restringida.20.9 muestra3cajas propias(19/26/29), guion2.20.10 restringido correctamente.20.4 queda completado al ver Hernán su recuperación82 en22.2. Incidentes históricos: [contador autorizado](evidencia-dia-20/32-autorizacion-y-alcance-contador.md) y [navegador recuperado](evidencia-dia-20/40-bloqueo-navegador.md). No se reenviaron formularios financieros ante respuestas pendientes: se verificó su efecto desde otra pestaña.

## Salidas de consola de la reanudación

### 10-reloj-reanudacion.txt

[Archivo](evidencia-dia-20/10-reloj-reanudacion.txt)

```text

   INFO  Configuration cache cleared successfully.

Wings ahora cree que es jueves 29 de octubre de 2026, 15:00
El reloj avanza solo desde esa hora y se detiene a la medianoche de ese día.
Las sesiones abiertas se caen al saltar de día: hay que volver a entrar.
```

### 11-manana-simulacion-reanudacion.txt

[Archivo](evidencia-dia-20/11-manana-simulacion-reanudacion.txt)

```text
 1. Rocío Paz abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Rocío Paz cobra a Iglesias, Clara $30.000,00 por Efectivo — Pago registrado para Iglesias, Clara.
 3. Rocío Paz cobra a Molina, Milagros $30.000,00 por Mercado Pago — Pago registrado para Molina, Milagros.
 4. Rocío Paz cobra a Ferrari, Josefina $33.000,00 por Efectivo — Pago registrado para Ferrari, Josefina.
 5. Rocío Paz cobra a Flores, Juana $33.000,00 por Mercado Pago — Pago registrado para Flores, Juana.
 6. Rocío Paz cobra a Alvarez, Camila $35.000,00 por Efectivo — Pago registrado para Alvarez, Camila.
 7. Rocío Paz cobra a Benitez, Milagros $45.000,00 por Mercado Pago — Pago registrado para Benitez, Milagros.
 8. Rocío Paz cobra a Flores, Santino $28.000,00 por Efectivo — Pago registrado para Flores, Santino.
 9. Rocío Paz cobra a Fernandez, Benjamin $48.000,00 por Mercado Pago — Pago registrado para Fernandez, Benjamin.
10. Rocío Paz cobra a Herrera, Facundo $28.000,00 por Efectivo — Pago registrado para Herrera, Facundo.
11. Rocío Paz Limpieza $3800 por Efectivo — Movimiento registrado.
12. Lucía Gaitán lista de la #33: 12 alumnos, 11 presentes — Asistencias guardadas.
13. Mariela Ocampo lista de la #44: 8 alumnos, 7 presentes — Asistencias guardadas.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
SIMULADO: no se guardó nada.
```

### 12-manana-real.txt

[Archivo](evidencia-dia-20/12-manana-real.txt)

```text
 1. Rocío Paz abre caja con $10.000,00 — Caja abierta con el efectivo declarado.
 2. Rocío Paz cobra a Iglesias, Clara $30.000,00 por Efectivo — Pago registrado para Iglesias, Clara.
 3. Rocío Paz cobra a Molina, Milagros $30.000,00 por Mercado Pago — Pago registrado para Molina, Milagros.
 4. Rocío Paz cobra a Ferrari, Josefina $33.000,00 por Efectivo — Pago registrado para Ferrari, Josefina.
 5. Rocío Paz cobra a Flores, Juana $33.000,00 por Mercado Pago — Pago registrado para Flores, Juana.
 6. Rocío Paz cobra a Alvarez, Camila $35.000,00 por Efectivo — Pago registrado para Alvarez, Camila.
 7. Rocío Paz cobra a Benitez, Milagros $45.000,00 por Mercado Pago — Pago registrado para Benitez, Milagros.
 8. Rocío Paz cobra a Flores, Santino $28.000,00 por Efectivo — Pago registrado para Flores, Santino.
 9. Rocío Paz cobra a Fernandez, Benjamin $48.000,00 por Mercado Pago — Pago registrado para Fernandez, Benjamin.
10. Rocío Paz cobra a Herrera, Facundo $28.000,00 por Efectivo — Pago registrado para Herrera, Facundo.
11. Rocío Paz Limpieza $3800 por Efectivo — Movimiento registrado.
12. Lucía Gaitán lista de la #33: 12 alumnos, 11 presentes — Asistencias guardadas.
13. Mariela Ocampo lista de la #44: 8 alumnos, 7 presentes — Asistencias guardadas.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
GUARDADO.
```

### 47-noche-reanudacion.txt

[Archivo](evidencia-dia-20/47-noche-reanudacion.txt)

```text
 1. Rocío Paz cierra la caja #38: esperado $178.200,00, contado $178.200,00 — Caja cerrada.
 2. Admin Prueba valida la caja #38 (12 renglones tildados) — Caja validada y reflejada en cashflow.
Cobranza: $190.000,00 · {"AL_DIA":101,"EN_PLAZO":0,"MOROSO":1,"DEUDOR":2}
GUARDADO.
```

## Entrega

T14 sigue en curso, tiene=Claude: revisión independiente, control interno y velocidad pendientes. No aceptación integral. Tabla externa, producto y ESTADO-ACTUAL.md intactos; sin suite, despliegue ni push. [Resumen](evidencia-dia-19/resumen-reanudacion.json).
