# PRU-04 · Día20 · reanudación autorizada

**Codex CyE · 26/10/2026 · TEST/wingstest · EN CURSO: mañana y20.1–20.7 recorridos; pendiente20.8–20.10 por navegador desactivado.**

## Autorización y trazabilidad

[Carlos autorizó](evidencia-dia-19/10-autorizacion-reanudacion.md) volver al28/10, repetir19.5 con confirmación manual suya, completar19.4a/19.6b/19.7a, cerrar y repetir noche19 y controles20–22. No se repiten cobros ni mañana19. [Primera corrida conservada](evidencia-dia-20/informe-primera-corrida.md). [Guion vigente](GUION-DIAS-19-22.md).

Último reloj de esta reanudación: **29/10/2026 15:00**. La primera corrida había llegado al31/10, con los días20–22 sin operar. No confundir ambos intentos.

## Situaciones

| Caso | Tarea y esperado | Resultado | Observado y evidencia |
|---|---|---|---|
| 20.1 · Rocío | Vende un par de **patines, $85.000 por MP** (Indumentaria → Patines) y una **remera, $18.000 en efectivo**.<br>**Esperado:** Los dos aparecen como ingresos de la caja; solo la remera sube el efectivo esperado (MOV-002). | **Sí** | Patines85000MP y remera18000efectivo registrados una vez; esperado178200, MP241000, ingresos413000. [TXT](evidencia-dia-20/20.1-caja-178200.txt) · [TXT](evidencia-dia-20/20.1-ventas-guardadas.txt) |
| 20.2 · Dueño | Se inunda la cancha: **cancela la clase de hoy de Fútbol Avanzadas 19–20** (#71). Primero sin motivo, después con «Cancha inundada».<br>**Esperado:** Sin motivo no cancela. Con motivo sigue en el listado de hoy, apagada, con «Cancelada» y el motivo (CLA-011). | **Sí** | Sin motivo no cancela (El motivo es obligatorio); Cancha inundada cancela solo71 y permanece en hoy, Cancelada con motivo. [TXT](evidencia-dia-20/20.2-sin-motivo.txt) · [TXT](evidencia-dia-20/20.2-cancelada-con-motivo.txt) · [TXT](evidencia-dia-20/20.2-listado-cancelada.txt) |
| 20.3 · Hernán | Entra a la #71 para tomar lista.<br>**Esperado:** La ve cancelada: aviso de bloqueo y Guardar deshabilitado; no se guarda ninguna marca (ASI-005). | **Sí** | Hernán ve clase71 Cancelada, aviso no se pueden registrar asistencias y Guardar deshabilitado. No se enviaron marcas. Ingreso recuperado mediante reinicio de un contador autorizado por Carlos. [TXT](evidencia-dia-20/20.3-hernan-clase-cancelada-bloqueada.txt) · [MD](evidencia-dia-20/32-autorizacion-y-alcance-contador.md) |
| 20.4 · Dueño | Crea una **clase única de recuperación**: Fútbol Avanzadas, **sábado 31/10**, con Hernán. Primero pone de **14:00 a 13:00**; después corrige a **14:00 a 15:00**.<br>**Esperado:** Con la hora de fin antes del inicio, rechaza con un aviso claro y conserva lo cargado (CLA-005). Con 14 a 15 se crea; solo ofrece profesores de Fútbol, y Hernán la ve en sus clases del 31 (CLA-002). | **Parcial** | Horario14–13 rechazado en formulario conservando campos. Corregido14–15 creó una clase única31/10, grupoFútbolAvanzadas, solo Martín/Hernán ofrecidos. Verificación de visibilidad por Hernán pendiente para22.2. [TXT](evidencia-dia-20/20.4-horario-invalido-rechazado.txt) · [TXT](evidencia-dia-20/20.4-clase-creada.txt) |
| 20.5 · Mariela | Quiere adelantar la lista de la clase del **sábado 31, Federadas 10–11** (#50).<br>**Esperado:** No deja guardar una clase cuyo día no llegó, y lo explica (ASI-011). | **Sí** | Mariela abrió50 del31/10 estando29/10. Aviso Esta clase todavía no ocurrió; casillas y Guardar deshabilitados. No se guardaron marcas. [TXT](evidencia-dia-20/20.5-mariela-clase-futura-bloqueada.txt) |
| 20.6 · Rocío | La familia de **Lucas Gómez** cambió de celular: lo corrige de 1145123401 a **1145123499**.<br>**Esperado:** La ficha y el listado muestran el número nuevo; sus pagos y cuotas no cambian (ALU-012). | **Sí** | Celular1145123401→1145123499 en ficha y listado. Tutor conserva1145123401. Al día y pagoOct33.000/recibo4 iguales; sin nueva cuota visible. [TXT](evidencia-dia-20/20.6-lucas-antes.txt) · [TXT](evidencia-dia-20/20.6-listado-telefono-nuevo.txt) · [TXT](evidencia-dia-20/20.6-ficha-telefono-nuevo.txt) |
| 20.7 · Rocío | En su **Inicio**, a mitad del turno.<br>**Esperado:** «Caja abierta», con la hora en que la abrió, Cobrar y Registrar (REP-016). | **Sí** | Inicio Rocío muestra Caja abierta, hora15:00, Cobrar y Registrar. [TXT](evidencia-dia-20/20.7-inicio-rocio-caja-abierta.txt) |
| 20.8 · Rocío | En **Movimientos**, busca los cobros del miércoles.<br>**Esperado:** Ve los cobros de cuota de Sandra del 28/10, con quién los hizo, y puede abrir sus recibos. No ve gastos del dueño (MOV-011). | **No ejecutado** | Pendiente desde20.8: la revisión automática del navegador primero agotó uso; al continuar, la herramienta informó navegador local desactivado en Work. El ingreso de Hernán ya se resolvió. No se ejecutó esta acción ni se sustituyó por consola. [MD](evidencia-dia-20/40-bloqueo-navegador.md) |
| 20.9 · Rocío | En **Caja**, el listado de cajas.<br>**Esperado:** Esperado según el catálogo: ve solo sus cajas de los últimos 30 días (la del 26/10 y la de hoy), no las de Sandra ni Pablo (CAJ-035). | **No ejecutado** | Pendiente desde20.8: la revisión automática del navegador primero agotó uso; al continuar, la herramienta informó navegador local desactivado en Work. El ingreso de Hernán ya se resolvió. No se ejecutó esta acción ni se sustituyó por consola. [MD](evidencia-dia-20/40-bloqueo-navegador.md) |
| 20.10 · Rocío | Quiere crear una clase de prueba para una amiga: Clases → Nueva (y, si no aparece el botón, entrando a la dirección `/clases/create`).<br>**Esperado:** No puede: crear clases es del dueño; forzando, acceso restringido (CLA-008). | **No ejecutado** | Pendiente desde20.8: la revisión automática del navegador primero agotó uso; al continuar, la herramienta informó navegador local desactivado en Work. El ingreso de Hernán ya se resolvió. No se ejecutó esta acción ni se sustituyó por consola. [MD](evidencia-dia-20/40-bloqueo-navegador.md) |

## Estado de la reanudación

Simulación13/13 y mañana real13/13 guardada. Caja38 de Rocío: inicial10000, esperado178200, MP241000, ingresos413000, gastos3800. Las ventas85000MP/18000efectivo no se repiten.

20.2:71 cancelada con Cancha inundada.20.3: Hernán ve aviso de cancelación y Guardar deshabilitado.20.4: clase82 creada para31/10,14–15; el horario14–13 se rechazó conservando datos; solo profesores de Fútbol. Ver visibilidad por Hernán en22.2.20.5: Mariela ve50 futura y bloqueada.20.6: celularLucas cambiado, ficha/listado y pago anterior comprobados.20.7: InicioRocío con hora de apertura y acciones del turno.

## Incidente de ingreso resuelto con autorización

El login siguió rechazado tras87s. Carlos autorizó diagnóstico/reinicio de un contador. SSH en staging/wingstest comprobó un temporizador hasta31/10 mientras la prueba estaba en29/10; quedaban171386segundos. Se limpió una única clave con RateLimiter::clear; después0intentos/0segundos, y Hernán ingresó. No se cambió la regla5/minuto, usuarios, claves ni datos de negocio.

[Diagnóstico](evidencia-dia-20/30-diagnostico-contador-login.txt) · [Reinicio](evidencia-dia-20/31-reinicio-unico-contador-autorizado.txt) · [Autorización](evidencia-dia-20/32-autorizacion-y-alcance-contador.md).

## Interrupción actual

Después de20.7, la acción de abrir Movimientos no llegó a ejecutarse por límite de uso de la revisión automática. Al pedir continuar, ese límite estaba liberado pero el navegador local estaba desactivado en Work. [Registro](evidencia-dia-20/40-bloqueo-navegador.md). Pendiente habilitarlo; no se sustituyen estos casos por consola. Noche20 aún no ejecutada;21–22 aún no reanudados.

## Salidas de consola

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

## Entrega y límites

Objetivo pendiente, no cierre deT14. Tabla externa y ESTADO-ACTUAL.md intactos. Sin producto ni despliegue. Se conserva evidencia del intento anterior; el control documental independiente anterior vale solo para febabcd6. Sin push.
