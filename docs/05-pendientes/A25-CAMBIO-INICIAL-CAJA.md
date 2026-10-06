# A25 — Cambio inicial y cierre de caja

**Versión:** 2026-10-06.v1. **Autor:** Codex CAB.
**Estado:** HECHO (Codex), a revisar; diseño aprobado por Carlos el 06/10, sin despliegue.
[Defecto A25](../06-pruebas/PRU-02/DEFECTOS.md) · [Entrega y capturas reales](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md).
Los relevamientos siguientes conservan el estado anterior para explicar las decisiones.
**Reglas vigentes:** [Contrato Caja/Cashflow V5, 06/10/2026.v2](../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md), con todas las respuestas de Carlos consolidadas; usarlo para implementar o verificar, no el relevamiento histórico.

## 1. Lo que se comprobó, no lo que se supone

Contraste del 06/10 sobre main después de publicar `ddefe00`. Grafo reindexado,
llamadas y cuerpos leídos; las vistas de caja tienen cobertura incompleta en el índice.
No se inspeccionó ni modificó la base del club ni el servidor para este relevamiento.

- `CajaService::abrirCajaSiNoExiste` crea la caja al primer movimiento, sin importe de
  apertura; serializa aperturas por usuario y conserva una sola abierta.
- Lo invocan el cobro OPERATIVO, el alta manual de movimiento web y el registro interno.
  No existe un formulario de declaración de efectivo previo en la ruta web actual.
- `CajaWebController::cerrar` no lee importes contados: llama al servicio.
  `cerrarCajaOperativa` solo guarda estado, fecha y autor administrativo cuando corresponde.
- Modelo y migraciones de `cajas_operativas` no declaran fondo inicial, efectivo contado
  ni diferencia. El resumen agrupa movimientos ACTIVO por medio y rubro, y muestra neto.
- `validarCaja` puede cerrar una caja ABIERTA como ADMIN antes de validarla. La futura
  solución debe contemplar ese camino, no solo el botón Cerrar del OPERATIVO.
- `CashflowIntegracionCajaService` refleja movimientos activos al validar; el saldo inicial
  de `TipoCaja` ya es un punto de partida contable separado, no un ingreso.

**Diferencia con la descripción del pedido:** hoy NO hay un arqueo comparando contra un
inicio cero. Falta el inicio declarado y falta el conteo/comparación del cierre. Agregar
solo un campo de apertura no construye ese control. Se deja escrito y se consulta antes
de programar, conforme AGENTS.md §6b.

Fuentes verificadas:

- [Apertura, cierre y validación](../../app/Services/CajaService.php).
- [Controlador web y resumen](../../app/Http/Controllers/CajaWebController.php).
- [Modelo](../../app/Models/CajaOperativa.php) y [migración original](../../database/migrations/2026_02_01_100004_create_cajas_operativas_table.php).
- [Resumen real de caja](../../resources/views/caja/resumen.blade.php).
- [Contrato Caja/Cashflow V4](../02-contratos/Wings-Contrato-Caja-Cashflow-V4.md),
  [roles](../02-contratos/PERMISOS-ROLES.md) e [integración](../../app/Services/CashflowIntegracionCajaService.php).

## 2. Tres decisiones de Carlos — 06/10/2026

Carlos respondió las tres preguntas el 06/10. Se registra la respuesta humana, no una
opción preseleccionada. El origen también fue aclarado: **un solo cajón compartido**, toma
el último cierre del club, no el último cierre de la persona. Las aclaraciones recibidas se detallan abajo.

### D1. Qué queda al cerrar y qué se lleva el club

Ejemplo: Susana empieza con **$10.000** para vuelto y termina con **$35.000** en el cajón.

- Opción A: declara **$10.000 de cambio que queda** y **$25.000 que se retiran/entregan**.
- Opción B: se retiran los **$35.000**; el próximo turno recibe un importe nuevo.
- Opción C: solo se declara que había **$35.000**, sin registrar retiro ni arrastre automático.

**Respuesta de Carlos: opción A.** Se separa el cambio que queda del efectivo retirado;
en el ejemplo, $10.000 quedan y $25.000 se retiran. **Aclaración recibida:** quien cierra
puede elegir cuánto cambio deja; no está obligado a conservar el importe de apertura.
El retiro es **efectivo contado menos cambio retenido**.

### D2. Quién declara el efectivo inicial

- Opción A: quien abre cuenta lo que recibió y escribe ese importe cada vez.
- Opción B: el sistema propone el cambio declarado en el cierre anterior y quien abre confirma.

**Respuesta de Carlos: opción B.** Tomar el cambio del cierre anterior y pedir confirmación.
**Aclaración aceptada de Carlos:** un solo cajón compartido; otra operativa toma los
$10.000 del último cierre del club. No hay fondos independientes por persona.
**Aclaración recibida:** en la primera apertura declara cuánto recibió. En las siguientes
puede corregir lo heredado con un motivo: propuesta $10.000, recibido $8.000, declara $8.000
y explica la diferencia. No registrar $10.000 como recibido si no lo contó.
No copiar todo el cierre, ni saldo de banco/transferencia, ni el `saldo_inicial` histórico.

### D3. Qué compara el cierre y qué hace con una diferencia

Ejemplo exclusivamente de **efectivo**:

| Dato | Importe |
|---|---:|
| Cambio recibido | $10.000 |
| Cobros en efectivo del turno | $30.000 |
| Gastos en efectivo del turno | $5.000 |
| Efectivo esperado antes de retiro/entrega final | **$35.000** |
| Efectivo realmente contado | **$34.000** |
| Diferencia (contado menos esperado) | **−$1.000: faltante** |

- Opción A: muestra esperado, contado y diferencia; permite cerrar para revisión ADMIN.
- Opción B: muestra esos datos pero bloquea el cierre mientras no coincidan.
- Opción C: solo registra lo contado, sin calcular diferencia.

**Respuesta de Carlos: opción A.** Mostrar los tres valores y permitir cerrar; ADMIN revisa
la diferencia. No bloquear el cierre por un faltante/sobrante. No registrar una diferencia
como ingreso, gasto, condonación ni ajuste automáticamente. Si se pide motivo, aclarar su
obligatoriedad; la diferencia y su revisión no implican que Carlos autorizó inventar un asiento.

## 3. Límites aprobados para la implementación

Las tres aclaraciones consultadas juntas fueron respondidas por Carlos el 06/10:

- **Primera apertura/corrección:** declara el primer importe; puede corregir lo heredado
  dejando motivo. Ejemplo aceptado: heredado $10.000, recibido $8.000.
- **Turnos simultáneos:** cerrar el turno anterior antes de abrir otro. No trabajan dos
  operativas a la vez sobre una caja, ni se abren dos cajas para el mismo cajón compartido.
- **Cambio retenido:** quien cierra indica cuánto deja; retiro = contado − cambio retenido.
  Puede abrir con $10.000 y dejar $15.000 si el efectivo contado alcanza.

**Consultas adicionales fundamentadas en el código actual:**

- **Identificación del efectivo:** `TipoCaja` no distingue billetes de banco/transferencia.
  **Carlos respondió:** ADMIN configura una vez el medio que representa el cajón.
  No adivinar por un nombre editable ni fijar un ID. Solo ese medio entra en el arqueo.
- **Cobros ADMIN:** van directamente al cashflow, sin caja operativa (A13/B1). Si ADMIN
  cobra $20.000 en efectivo durante el turno, **Carlos respondió: Vanina los guarda aparte**.
  No sumarlos al efectivo esperado del turno operativo ni abrir una caja propia de ADMIN.
- **Cierre ADMIN:** hoy validar una caja ABIERTA la cierra sin conteo. **Carlos respondió:**
  ADMIN debe contar y cerrar primero, incluso si es el turno de otra persona. Validar
  no puede inventar un conteo ni cerrar automáticamente una caja que continúa abierta.

Las consultas anteriores están respondidas. **Excepción aclarada por Carlos:** una
caja RECHAZADA permite corregir movimientos hoy. Si el cambio de ese cierre ya lo recibió
otro turno, se corrigen los movimientos conservando contado/cambio/retiro originales,
sin modificar lo recibido por el siguiente. Ejemplo: lunes dejó $10.000, martes ya los
recibió otra operativa; ADMIN rechaza el lunes después. **Respuesta literal: «Sí, conservar
lo que se contó y entregó».** El arqueo puede reflejar la corrección del movimiento, pero
no reescribir el conteo físico ni la entrega ya realizados.
Las siguientes son precauciones de integridad, no respuestas a esas decisiones:

1. El dinero para vuelto ya pertenece al club. Declararlo por turno no puede duplicar
   el ingreso del cashflow ni el saldo inicial histórico. Distinguir declaración física
   del turno de un traslado/aporte real de dinero.
2. No sumar Transferencia/banco al efectivo contado. Definir cómo identificar el medio
   físico sin IDs fijos ni adivinar por un nombre editable; revisar `TipoCaja` antes de cerrar el diseño.
3. Si la apertura pasa a exigir declaración, todos los caminos que hoy abren automáticamente
   deben respetarla. No dejar que Cobrar o un POST manual salteen el paso.
4. Mantener ADMIN sin caja propia (A13/B1) y OPERATIVO con su circuito; ADMIN puede revisar
   y cerrar cajas ajenas según contrato. No ampliar ni restringir permisos por esta tarea.
5. Registrar quién declaró, cuándo, importe, y decisión del cierre; congelar los datos del
   turno cerrado/validado. Aclarar cómo se corrige una caja RECHAZADA y cómo se registra
   un cierre forzado ADMIN que no puede contar físicamente el cajón.
6. Apertura, cierre, cobro y validación concurrentes no pueden duplicar cajas, movimientos
   ni modificar el total que se acaba de rendir. Revalidar bloqueos antes de darlo por terminado.
7. No cambiar migraciones históricas ni rellenar cajas anteriores con conteos inventados;
   decidir cómo distinguir un dato no declarado de un cero real.

## 4. Pruebas necesarias cuando estén definidas las reglas

Estos casos son el mínimo de aceptación de las decisiones recibidas. Cualquier
contradicción adicional se reporta antes de implementar la parte afectada.

- Apertura con $10.000 y cero movimientos: se conserva la declaración, sin ingresos nuevos.
- Efectivo +$30.000 y −$5.000: esperado $35.000; transferencias no inflan ese valor.
- Contado $34.000 / $35.000 / $36.000: faltante, coincidencia y sobrante visibles;
  permite cerrar con diferencia para revisión ADMIN, según D3 aceptada.
- Cobro o movimiento cancelado no cuenta como dinero vigente del turno.
- D1/D2: cierre y próxima apertura respetan exactamente retiro/cambio/origen aprobados.
- Primera apertura declara $10.000; siguiente propone lo retenido y exige confirmación.
- Recibido $8.000 frente a heredado $10.000: corrección con motivo, sin falsear el importe.
- Abrió con $10.000 y contó $35.000: puede dejar $15.000 y retirar $20.000.
- Una segunda operativa no puede abrir mientras el turno anterior siga abierto, incluso
  con aperturas simultáneas y POST directo.
- ADMIN configura el medio físico una vez; un cambio de nombre no cambia su identificación.
- ADMIN cobra efectivo y lo guarda aparte: cashflow correcto, sin aumentar el esperado operativo.
- ADMIN no puede validar una caja ABIERTA: antes declara contado/cambio y la cierra.
- Caja RECHAZADA corregida después de que otro turno recibió el cambio: mantiene
  contado/cambio/retiro originales y no modifica el importe que recibió el siguiente turno.
- Primera apertura sin cierre previo, cambio de operativo y cierre anterior sin validar.
- Rechazo y nuevo cierre conservan trazabilidad y no vuelven a reflejar ingresos en cashflow.
- Cierre forzado ADMIN de caja vieja; no atribuirle un conteo que nadie realizó.
- POST directo, roles, doble clic y dos pestañas: sin atajos ni duplicación.
- Regresión: cobro OPERATIVO y cobro ADMIN directo siguen correctos; validar dos veces es idempotente.
- Pruebas primero rojas, luego verdes, en `wings_testing_codex`; suite completa propia al entregar código.
- Apertura/cierre/resumen en navegador real, escritorio y 375 px; importes y errores visibles.

## 5. Diseño y entrega — actualizado 06/10

Implementado después de estas decisiones; componentes existentes y JS de cierre propio,
sin CSS compartido. Capturas reales de las cinco pantallas en escritorio/375 aprobadas
por Carlos: «OK, aprobado» y «Sí». [Galería](../06-pruebas/PRU-02/evidencia/a25/capturas/index.html).
A25 **HECHO (Codex), a revisar** en los dos seguimientos; no sumar a CERRADOS.
Contrato/ER V5, cuatro estados y pruebas incluidos en la [entrega](../06-pruebas/PRU-02/IMPLEMENTACION-A25.md).
Otro agente verifica y decide el cierre; no desplegado ni base del club tocada.

## 6. Pruebas previas reales — 06/10

[Archivo de pruebas y resultado](../06-pruebas/PRU-02/evidencia/a25/README.md): 12 pruebas,
15 aserciones; 3 fallos contra comportamientos actuales y 9 errores por funciones nuevas
inexistentes. Solo `wings_testing_codex`, fuera de la suite compartida; no son 12 defectos
reproducidos ni una implementación. No se modificó código de aplicación por este ensayo.
