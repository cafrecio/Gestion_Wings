# T33 — Perdonar inscripción y reactivar perdones

13/10/2026 · Codex CyE · DU-25, DU-58 · CBZ-009, CBZ-010.
Implementación entregable en desarrollo; falta verificación de Claude. Sin despliegue,
sin modificar el sitio de prueba ni su reloj. Datos sintéticos en `wings_testing_codex`.

## Decisiones y cambios

Carlos, DU-25: «Sí, con motivo, igual que una cuota».
DU-58: «Reactivar la perdonada: en la ficha, la cuota perdonada se puede volver a cobrar,
con motivo, y queda el historial (perdonada tal día, reactivada tal día). Vale igual para
la inscripción». La decisión reemplaza crear de nuevo a mano; no se duplica ningún registro.

- `InscripcionService`: condona el saldo pendiente, conserva original/parciales e inserta
  evento CONDONAR. Reactivar pone `monto_condonado=0` y agrega REACTIVAR, con autor,
  fecha, motivo, importe, valor anterior/nuevo y saldo resultante.
- `PagoCuotaService`: reactivar admite solamente CONDONADA, vuelve a PENDIENTE,
  conserva importes/imputaciones y agrega observación fechada sin borrar el perdón.
  Condonar y reactivar toman bloqueos persona → alumno → deuda, como el cobro.
- Tres rutas web dentro de `ensure.admin.web`: inscripción condonar/reactivar y cuota
  reactivar. Motivo recortado en bordes, obligatorio, 10–500 caracteres; servicio vuelve
  a validarlo. POST del OPERATIVO o PROFESOR recibe 403.
  Las tres líneas propias de `routes/web.php` entraron en `602efcc` de Claude el 13/10
  mientras trabajábamos en la carpeta compartida (comprobado en el diff histórico).
  Se verificaron en HEAD; por eso ese archivo no vuelve a figurar en el commit propio.
- Ficha: Condonar en inscripción pendiente y Reactivar en inscripción perdonada;
  sección con todas las cuotas CONDONADAS, incluido un mes futuro, con Reactivar ADMIN.
  Se usa el mismo modal. Código de la ficha extraído a `alumnos-show.js`; guardián CSP
  baja de 12 a 11 bloques incrustados. Sin CSS compartido ni otra pantalla modificada.
  Botones con la clase existente `ds-btn-row`: CSS real 72×28 (la guía dice 64×26).
  Se conserva el tamaño existente por el pedido expreso de reutilizar los botones de fila;
  el control visual compara contra Cobrar de la propia ficha, sin cambiar el design system.
- Contrato actualizado en [Condonación V1](../../02-contratos/Wings-Contrato-Condonacion-V1.md).
  Los archivos reservados a Gemini no se editaron ni se agregan al commit de T33.

## Resultado de cada borde

| Borde | Resultado comprobado |
|---|---|
| 1. Inscripción con parcial y posterior anulación | Original $5,000.00, cobrado $2,000.00, perdón $3,000.00, saldo $0.00. Al anular el parcial por el servicio real del dueño, saldo $2,000.00. Si después reactiva el perdón, saldo $5,000.00. Sin anular, reactivar deja $3,000.00; se cobró ese resto y quedó $0.00. |
| 2. Inscripción perdonada, pagada o ANULADA | Rechazo con mensaje; no agrega evento, pago ni movimiento. Un perdón previo debe reactivarse antes de volver a condonar, incluso si la anulación de un parcial deja saldo. |
| 3. Reactivar sin perdón | Cuota PENDIENTE/PAGADA/AJUSTADA: rechazo, fila intacta. Inscripción sin importe perdonado: rechazo, ningún evento nuevo. Inscripción ANULADA: rechazo de ambas acciones. |
| 4. Cuota futura | Noviembre $42,000.00 reactivado el 13/10: Cobranza, Inicio/Reportes y buscador $0.00. El 01/11 suma $42,000.00; no se duplica ni cambia precio. |
| 5. Alumno dado de baja | ADMIN puede condonar y reactivar inscripción y cuota. Sigue inactivo; ficha conserva inscripción $5,000.00 y cuota $48,000.00 pendientes. Cobranza/Reportes $0.00 porque sigue de baja. No se reactiva matrícula ni plan. |
| 6. Dos pedidos | Ocho cruces con dos conexiones MariaDB: parcial primero, completo primero, perdón primero y doble perdón, tanto inscripción como cuota. SELECT FOR UPDATE espera en persona; timeout revierte sin INSERT/UPDATE/DELETE; al reintentar relee saldo/estado. Dos pruebas adicionales con segundo proceso PHP observan su SELECT FOR UPDATE en PROCESSLIST al menos un segundo y lo dejan continuar después del commit, sin timeout ni reintento: inscripción perdona $3,000.00 tras parcial $2,000.00; cuota perdona $38,000.00 tras parcial $10,000.00. |
| 7. Mes anterior | Septiembre: cuota $48,000.00 + inscripción $5,000.00 = $53,000.00. Perdón en octubre de ambas: septiembre sigue con cuota histórica $48,000.00 y la inscripción baja a $0.00, total $48,000.00; octubre total $0.00. Reactivar en octubre: septiembre $53,000.00. Eventos de cuota: 20/09 +$48,000.00; 13/10 −$48,000.00 y +$48,000.00. Inscripción usa el perdón actual: T33 no cambia ese comportamiento. |
| 8. Recibos, caja y cashflow | Se compararon filas completas de pagos, imputaciones, cajas, movimientos operativos y cashflow antes/después de perdonar/reactivar, incluso con parciales reales: idénticas. Intentos de cobro sobre perdonadas no escriben ni generan archivo. La anulación del borde 1 sí mueve dinero por su propio flujo; el perdón/reactivación no. |

Cuota con parcial: original $48,000.00, pagado $10,000.00; condona $38,000.00,
reactiva $38,000.00 y luego se cobra hasta PAGADA. Historial del perdón intacto más
línea de reactivación con autor y fecha del 14/10 en la prueba.

**Persona en dos deportes:** la ficha que tiene el cargo muestra saldo de inscripción
$0.00 y Reactivar tras perdonar; después de reactivar, $5,000.00 y Condonar.
La otra ficha mantiene «La inscripción se cobra una sola vez… Podés consultar el estado
de ese cargo en su ficha de…», sin botón de inscripción. Un solo cargo en base.

**Cuadre con el mismo juego de datos:** cuota $48,000.00 + inscripción $5,000.00.
Cobranza, Inicio ADMIN y Reportes: $53,000.00 → $0.00 → $53,000.00,
antes/perdón/reactivación. Buscador sin búsqueda: 1 → 0 → 1 alumnos con deuda.

## Pruebas y evidencia

### Corrección visual pedida por Carlos — 13/10/2026

Carlos devolvió la pantalla: «Separa el boton de la linea, queda feo» y preguntó
por qué Reactivar estaba a la izquierda en inscripción y a la derecha en cuota.
La inscripción usaba `alumno-actions`, que alinea a la izquierda en escritorio;
`acciones-celular-derecha` cambiaba solo el celular. La cuota ya alineaba a la derecha.
Se ajustó únicamente el contenedor de acciones de inscripción: derecha en ambos
anchos y 12 px debajo de la línea. Se conservaron tamaño, colores y funcionamiento.
El control del navegador ahora exige ambas condiciones y registra las medidas reales:
ocho visitas sin fallos, cuatro botones de inscripción con separación de 12 px y los
seis botones de inscripción/cuota a la derecha. Capturas regeneradas desde Laravel.
La prueba que produce el HTML pasó: 1 aprobada, 7 aserciones, 10.34 s.
Sintaxis PHP/JavaScript correcta; vistas compiladas y limpiadas. La revisión visual
de Carlos y la verificación de lógica por Claude siguen pendientes.

Suite completa de esta revisión, en `wings_testing_codex`: 734 aprobadas, 2 omitidas,
2 fallidas, 6981 aserciones, 1186.19 s. Las 31 de T33 pasaron. Los dos fallos están
en `CashflowCorreccionesT27Test`, líneas 210 y 259: `disponible` devuelve 0 frente a
47925.25 y 48124.25 esperados. Repetidos por separado, siguen fallando; no se atribuye
una causa ni se cambia lógica de Cashflow en esta corrección visual.
[Suite de la revisión](evidencia-t33/suite-revision-botones.txt) ·
[Repetición de los dos fallos](evidencia-t33/cashflow-recontrol-revision-botones.txt).

31 pruebas nuevas: 21 en `PerdonReactivacionT33Test` y 10 en `PerdonConcurrenteT33Test`.
Propias iniciales: 21 aprobadas / 194 aserciones; ocho cruces 8 / 102.
Los dos procesos reales se incluyen también en la suite final.
Primer intento de suite: `Tests:    3 failed, 2 skipped, 733 passed (6985 assertions)`;
`Duration: 1170.22s`. Los tres fallos fueron propios: MoneyLocking exigía tres referencias
al bloqueo del alumno y ahora son cuatro; se actualizó ese chequeo estructural. Los dos
procesos esperaban en SELECT FOR UPDATE (STATE=Statistics, comprobado por conexión),
pero INNODB_LOCK_WAITS no los publicaba durante la suite. El test ahora observa la
conexión en PROCESSLIST durante al menos un segundo, sin RESULT antes del commit,
y comprueba el saldo real al terminar el mismo pedido. Control posterior: 4 aprobadas,
22 aserciones, 32.13 s. No se cambió código de negocio por estos fallos.
[Primera corrida completa, conservada](evidencia-t33/suite-primer-intento.txt).

Suite completa de la primera entrega, antes de esta corrección visual,
únicamente `DB_DATABASE=wings_testing_codex`:

```text
Tests:    2 skipped, 736 passed (7001 assertions)
Duration: 895.64s
```

Incluye las 18 pruebas sin publicar de T31: no hubo fallos atribuibles a Gemini.
La carpeta tiene 738 pruebas; el commit propio agrega 31 sobre las 689 versionadas,
por lo que publica 720 hasta que Gemini publique T31. Los cuatro contadores documentales
del commit dicen 720 y distinguen expresamente la corrida local de 738. En la carpeta
compartida se conserva el número 738 y los cambios ajenos; no se publica T31 por arrastre.
[Salida íntegra de la suite](evidencia-t33/suite-completa.txt).
Build Vite correcto; sintaxis de todos los PHP propios correcta. Vistas compiladas/limpiadas.

Capturas desde respuestas reales de Laravel obtenidas por HTTP en la prueba,
con assets compilados preservados. Chrome usa ventana 1366 para escritorio e iframe
de ancho real 360 para celular, con login de control. Recortes de las zonas modificadas.
[Visor](evidencia-t33/visor.html) · [Control del navegador](evidencia-t33/resultado-navegador.json).

| Estado | Escritorio 1366 | Celular 360 |
|---|---|---|
| Inscripción pendiente, Condonar | [Captura](evidencia-t33/capturas/inscripcion-pendiente-escritorio.png) | [Captura](evidencia-t33/capturas/inscripcion-pendiente-marco-360.png) |
| Inscripción perdonada, Reactivar | [Captura](evidencia-t33/capturas/inscripcion-condonada-escritorio.png) | [Captura](evidencia-t33/capturas/inscripcion-condonada-marco-360.png) |
| Cuota perdonada, Reactivar | [Captura](evidencia-t33/capturas/cuota-condonada-escritorio.png) | [Captura](evidencia-t33/capturas/cuota-condonada-marco-360.png) |

Navegador: ocho visitas, sin errores de JavaScript ni desborde; abrir/cerrar modal,
destino correcto, motivo corto rechazado, válido aceptado, limpieza de motivo anterior
y alternancia Reactivar/Condonar comprobados. Los guardados se prueban por HTTP real
en PHPUnit; el visor responde solo GET y no puede escribir.

**No lo probé:** teléfono físico, servidor/sitio de prueba, envíos de notificaciones,
edición manual de base, concurrencia con cambios de DNI, alta/cambio de grupo,
anular pagos contra reactivación, ni todas las combinaciones entre tres o más pedidos.
No se reconstruye historia de inscripción ni se reemiten recibos anteriores.

## Reproducción y pase

```powershell
$env:DB_DATABASE='wings_testing_codex'; php artisan test --filter='Perdon(Reactivacion|Concurrente)T33Test'
$env:DB_DATABASE='wings_testing_codex'; $env:WINGS_CAPTURAS_T33='1'
php artisan test --filter=test_evidencia_ficha_real_opcional
Remove-Item Env:WINGS_CAPTURAS_T33
npm run build
node docs/06-pruebas/PRU-04/evidencia-t33/tomar-capturas.mjs
```

Tablero de entrega: T33 `a_verificar`, hizo Codex, verifica Claude, tiene Claude.
Carlos revisa las capturas; Claude verifica la lógica antes de cerrar/desplegar.
Los archivos propios preparados para publicación se nombran uno por uno en
[este inventario](evidencia-t33/archivos-publicados.txt).
