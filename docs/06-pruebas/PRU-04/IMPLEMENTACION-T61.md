# T61 — Transferir entre cuentas propias

Codex CyE, 20/10/2026. **Corrección implementada; visual aprobado por Carlos; lógica a verificar por Claude.**
La entrega anterior resolvía dos cargas independientes. Carlos necesita un formulario con cuenta de salida, cuenta de destino e importe; también pidió rubros y subrubros exclusivos del sistema, sin renombrar ni editar.

## Seguimiento FIN-09 — fecha de un mes anterior

Al revisar el contrato Caja/Cashflow V4 §3.6 se detectó que el formulario nuevo omitía la confirmación de una fecha de otro mes. Corrección implementada: valida antes de avisar, no escribe en el primer envío, pide Confirmar y luego guarda ambas patas con su fecha real, avisando al dueño por el mecanismo existente. La repetición del mismo envío conserva una sola cabecera y dos movimientos. Sin JavaScript incrustado.

13 pruebas T61 / 143 aserciones verdes, 9,68 s, salida 0. [Salida](evidencia-t61-transferencia/seguimiento-fecha.txt). Completa del corte 983: **977 aprobadas, 6 omitidas, 8541 aserciones, 697,65 s, salida 0**, wings_testing_codex; [salida](evidencia-t61-transferencia/suite-seguimiento-fecha.txt). La completa de 982 citada abajo corresponde al commit 43a70976 anterior a este seguimiento. Capturas nuevas del aviso 1366/375 en la galería; Carlos aprobó ese estado adicional el 20/10/2026: «Apruebo el aviso». El visual principal y Rubros ya aprobados se conservan.

## Funcionamiento solicitado

Una transferencia registra dos movimientos vinculados por una cabecera: egreso en la cuenta de salida e ingreso en la de destino, con igual importe y fecha. Una transacción confirma ambos o revierte todo. Las cuentas deben ser distintas y activas; importe positivo con hasta dos decimales. El doble envío de la misma operación no duplica movimientos.

El sistema asigna los conceptos de transferencia. El formulario no pide rubro ni subrubro. El catálogo queda reservado y el formulario de movimiento común no permite registrar una pata sola. La corrección individual de esos movimientos queda bloqueada para conservar el par; no se implementa aquí otro circuito de anulación de transferencias.

La salida y entrada cuentan en el libro Cashflow; su efecto neto sobre el disponible global y los indicadores del club es cero. Cada cuenta cambia por el importe transferido. Los movimientos anteriores no se emparejan por conjetura ni se reescriben.

## Cambios actuales

- Migración `2026_10_20_160000_crear_transferencias_cashflow.php`: cabecera y protección del catálogo existente.
- `TransferenciaCashflowService`: validación, permisos, cuentas bloqueadas en orden estable, clave de operación única y escritura atómica.
- `TransferenciaCashflowWebController`, rutas ADMIN, formulario y acceso Transferir desde Cashflow.
- Catálogo compartido por seeder y migración: reserva automática de los dos rubros y subrubros dedicados.
- Pruebas T61 adaptadas al funcionamiento solicitado: 12 casos, incluyendo rollback de la segunda escritura, reenvío, cuentas inválidas, catálogo protegido e invariancia de reportes.

## Revisión de botones solicitada por Carlos

Medición real: Volver y Transferir ya tenían 96 × 32 px, pero estaban alineados al borde exterior. Ahora quedan dentro de la tarjeta, alineados con el borde derecho de los campos. En Rubros, Subrubro medía 83,09 × 32 y Editar/Eliminar 76 × 32; los tres se fijaron en 96 × 32 en las nueve botoneras de escritorio, sin cambiar el CSS compartido. Capturas actualizadas 1366/375; **Carlos aprobó el visual corregido:** «Apruebo el visual corregido» (20/10/2026).

## Verificación actual

Dirigidas: **22 aprobadas, 287 aserciones, 11,26 s, salida 0** en `wings_testing_codex`. Suite completa: **976 aprobadas, 6 omitidas, 8527 aserciones, 804,11 s, salida 0**, corte 982. [Salida](evidencia-t61-transferencia/suite-completa.txt). [Dirigidas finales con CSP](evidencia-t61-transferencia/dirigidas-finales.txt): **24 aprobadas, 289 aserciones, 10,15 s, salida 0**. Sintaxis PHP y compilación/limpieza de vistas correctas. Recorrido real: un envío guardó salida y entrada de $1.233.545 en base ficticia. Once capturas 1366/375 en [galería actual](RECORRIDO-T61.html). Pendiente: verificación independiente de lógica por Claude (visual aprobado por Carlos el 20/10: «Apruebo el visual corregido»). La galería anterior se conserva solo como antecedente.

[Antecedente íntegro y resultados de la entrega anterior](IMPLEMENTACION-T61-ANTECEDENTE-DOS-CARGAS.md). Sin despliegue ni escritura en bases remotas.

## Para la ayuda

### Llevar efectivo al banco

Una transferencia mueve plata del club entre cuentas. No es una venta, un gasto, un aporte ni un retiro.

1. En Cashflow, tocá **Transferir**. Seleccioná **Efectivo** como cuenta de salida y **Banco** como destino. Indicá **$300.000** y la fecha.
2. Tocá **Transferir**. Wings guarda juntas la salida y la entrada: Efectivo baja $300.000 y Banco sube $300.000. El resultado del club no cambia.

Los conceptos **Salida a otro medio** y **Entrada desde otro medio** los asigna el sistema. No se editan ni renombran desde Rubros. Texto para T13; no se construye una pantalla de ayuda.

## Segunda vuelta — H1 a H3

Codex CAB, 21/10/2026. Pedido ejecutado en la copia aislada `codex/t61-segunda-vuelta`, base `2c7cebe`, porque la carpeta principal tenía cambios ajenos de T50. La toma del tablero está en `1668ca4`. La transferencia aprobada, su servicio/controlador/formulario, sus dos patas, idempotencia y saldos no se modificaron. Esta sección complementa, no reemplaza, la entrega anterior.

### Cambios por archivo y puertas revisadas

| Archivo / puerta | Cambio o comprobación |
|---|---|
| `ClasificacionSubrubros::permitidas` | Solo NEGOCIO/APORTE para ingreso y NEGOCIO/RETIRO para egreso. TRASPASO queda fuera del catálogo editable. Nuevo `exigirSinTraspaso` rechaza clase actual o foto histórica TRASPASO con error `subrubro_id` en castellano. No toca datos. |
| `SubrubroWebController::reglaClasificacion` | Usa la lista restringida en alta y edición; las solicitudes manipuladas con TRASPASO reciben 422. El mensaje obligatorio deja de ofrecer traspasos. Sigue protegiendo reservados. |
| `RubroWebController::guardarCambios` | Su uso de `permitidas()` impide cambiar el tipo de un rubro que conserva un subrubro común TRASPASO. Mensaje general de clasificación incompatible; no lo llama incorrectamente plata de los dueños. No reclasifica ni borra nada. |
| `subrubros/_form.blade.php` | El mismo uso de `permitidas()` deja de ofrecer TRASPASO. Si un valor antiguo no está permitido, muestra «Elegí una opción», sin elegir NEGOCIO automáticamente. Se retira solo la explicación que ofrecía traspasos; estilos, layout, botones y JavaScript intactos. |
| `CajaService` | Alta manual, alta en caja y edición comparten el rechazo; también el método interno usado por cobros. Rechaza antes de abrir/escribir cuando corresponde. Reservados y permisos anteriores se conservan. |
| `CashflowService::registrarMovimientoAdmin` | Mantiene el rechazo que ya existía, ahora con la validación compartida. Cubre formulario web y controlador API antiguo, aunque la API sigue apagada. |
| `CashflowCorreccionService` | Edición rechaza destino TRASPASO antes de modificar el directo o crear historial. Anulación también rechaza generar un contraasiento TRASPASO antiguo fuera de una transferencia. |
| `CashflowIntegracionCajaService` | Revisa todos los movimientos antes del primer reflejo. Un subrubro o foto histórica TRASPASO bloquea la operación completa; no deja un reflejo parcial ni arregla la clasificación por conjetura. |
| `PagoCuotaService` | Rechazo defensivo en asientos de cuota, inscripción, punitorios y contraasientos de anulación. Todo dentro de sus transacciones existentes; la caja usa además el método interno protegido. No cambia importes, aplicación de cobros ni reglas de deuda. |
| `LiquidacionPagoService` | Valida el subrubro antes de registrar el egreso o completar un pago con asiento previo. Un concepto antiguo TRASPASO no pasa por este flujo. La transferencia no usa este servicio. |
| `TransferenciaCashflowService` | Sin cambios: sigue asignando únicamente los dos subrubros reservados y creando el par vinculado. Las 13 pruebas anteriores se mantienen sin editar sus métodos. |

Búsqueda inicial con grafo `C-xampp-htdocs-gestion-wings`: `permitidas`, traza entrante y cuerpo. La traza dio los controladores de subrubro/rubro; búsqueda literal y lectura real agregaron Blade. Tras implementar se indexó por separado la copia aislada (`wings-t61-segunda-vuelta`) para no cambiar el índice de otra sesión. El índice es orientación; las conclusiones se contrastan con los cuerpos reales y el barrido de creaciones de `MovimientoOperativo`/`CashflowMovimiento`. La cobertura con advertencias se conserva en evidencia; no se afirma que la ausencia de nodos pruebe ausencia de puertas.

### Inventario de datos existentes — solo lectura

Antes de sembrar el escenario o ejecutar las regresiones se consultaron únicamente las bases locales propias con prefijo `wings_testing_codex`: se encontró **una**, `wings_testing_codex`. No se consultaron bases de otros agentes, del club, producción, sitio de prueba ni servidor.

| Hallazgo buscado en la base propia | Cantidad | Filas concretas (hasta 5) |
|---|---:|---|
| Subrubros no reservados, clasificación TRASPASO | 0 | Ninguna |
| Movimientos operativos con foto TRASPASO | 0 | Ninguna |
| Movimientos Cashflow con foto TRASPASO sin referencia TRANSFERENCIA | 0 | Ninguna |

[Inventario previo](evidencia-t61-segunda-vuelta/inventario-local-previo.json), generado con PDO y SELECT/SHOW exclusivamente, sin iniciar Laravel ni ejecutar seeders. Cada consulta guarda conteo y muestra concreta. Es un resultado de esta base descartable en ese instante, no una afirmación sobre las demás instalaciones. No se migraron, emparejaron, borraron ni reclasificaron registros existentes. Los datos anómalos de las regresiones son ficticios y se crean dentro del entorno de pruebas. Si aparecen casos antiguos en otra instalación, Carlos decide qué hacer con ellos; esta entrega bloquea nuevas escrituras indebidas y no los corrige automáticamente.

El [inventario posterior](evidencia-t61-segunda-vuelta/inventario-local-posterior.json), después de la suite, volvió a dar **0/0/0** en esa única base propia. Esta consulta adicional contempla clasificación efectiva (foto o catálogo), referencias huérfanas y subrubros no reservados. Es estado sintético posterior a pruebas, separado del inventario previo; no se extrapola a otras bases.

### Pruebas y capturas

- Tres regresiones nuevas en `TraspasosEntreMediosT61Test`, una por hallazgo, más las 13 originales: **16 aprobadas, 198 aserciones, 14,40 s**, salida 0, en `wings_testing_codex`. Las tres nuevas daban rojo antes de corregir: [antes](evidencia-t61-segunda-vuelta/regresiones-antes.txt), [después](evidencia-t61-segunda-vuelta/dirigidas.txt).
- H1 cubre altas/ediciones INGRESO y EGRESO, formularios, valor antiguo sin selección automática y cambio de tipo de rubro bloqueado sin tocar el subrubro.
- H2 cubre alta/edición reales de caja, método manual e interno, fila persistida intacta y reflejo rechazado antes de cualquier asiento.
- H3 cubre corrección rechazada, movimiento/historial/egresos intactos, alta manual Cashflow todavía rechazada y anulación de un directo histórico TRASPASO sin crear contraasientos.
- Cobros, inscripción y punitorios tras corregir la consulta del subrubro del cargo: **125 aprobadas, 851 aserciones, 214,18 s**, salida 0; [salida del área](evidencia-t61-segunda-vuelta/area-cobros.txt). Una primera completa se detuvo al detectar ese error propio y no cuenta como validación; [constancia](evidencia-t61-segunda-vuelta/corrida-descartada.json). La completa final con el código corregido pasó; se detalla abajo.
- El archivo de controles de Claude se conserva **sin modificaciones**. Sus pruebas tienen guardia para `wings_testing_claude`, por eso no se ejecutaron contra otra base. Hay una precisión respecto del pedido: el control de H3 termina con `assertTrue(true)` y escribe un JSON; no puede ponerse rojo por el rechazo nuevo. No se alteró para forzar ese resultado. Las tres regresiones propias sí prueban la conducta que debe rechazarse. La primera corrida de las 16 tuvo una falla del arnés propio al comparar un modelo recién creado contra una fila con defaults de BD; se corrigió leyendo la fila persistida antes de la operación, y las 16 pasaron.
- Ocho capturas **de la aplicación real**: alta y edición, antes/después, escritorio **1366 × 1000** y móviles **375 × 1000** comprobados por cabecera JPEG. La auditoría inicial detectó cuatro móviles de 360 y no los dio por válidos como 375. Se repitieron directamente en Wings HTTP local (no abriendo ni sirviendo la galería file bloqueada), con viewport y medición DOM guardados en [medición 375](evidencia-t61-segunda-vuelta/medicion-375.json); consola sin errores/avisos. Las cuatro capturas anteriores de 360 se reemplazaron por las nuevas, sin redimensionar ni fabricar imágenes. Para el antes se cargaron temporalmente los dos archivos originales de 2c7cebe, y después se restauraron sus bytes finales: las 612 fuentes siguen idénticas a la suite aprobada. No se hicieron nuevas operaciones de negocio. Pendiente aprobación visual de Carlos, no recaptura. [Ver únicamente esos formularios](REVISION-T61-SEGUNDA.html). Las opciones y consola sin errores/avisos se guardan en [recorrido](evidencia-t61-segunda-vuelta/recorrido.json). La transferencia aprobada no se vuelve a presentar.
- **Suite completa: 1020 aprobadas, 6 omitidas, 8846 aserciones, 934,96 s, salida 0**, en `wings_testing_codex`, corte **1026 pruebas**. [Salida final](evidencia-t61-segunda-vuelta/suite-completa.txt). Los 612 archivos de fuente controlados no cambiaron durante la corrida ([resultado](evidencia-t61-segunda-vuelta/resultado-suite.json)); cero suites ajenas detectadas en 175 observaciones cada 5 segundos ([monitor](evidencia-t61-segunda-vuelta/control-procesos-suite.json)), sin afirmar cobertura continua entre muestras. Contadores actualizados en ESTADO-ACTUAL, CHECKLIST-CARLOS y PLAN-PRODUCCION. HTML T34 generado por la suite restaurado con `git checkout --`, fuera de la entrega.

Guardianes finales de documentación/tablero: **7 aprobados, 34 aserciones, 0,42 s**, salida 0 ([salida](evidencia-t61-segunda-vuelta/guardianes.txt)). No se repitió la completa tras cambios exclusivamente documentales. Se entrega en la rama aislada; la carpeta principal avanzó con T50 ajena y no se integró ni sobrescribió.

### Tabla de pruebas y entrega

La tabla de Carlos/Claude y las respuestas DU no se editaron. CSH-018 a ya exige subrubros exclusivos del sistema: no necesita cambiar su regla. Como propuesta a Carlos/Claude, puede agregarse un control negativo explícito: «Alta/edición de subrubro común no ofrece ni acepta TRASPASO; si existía uno antiguo, caja y corrección de Cashflow lo rechazan sin escritura». Su fuente puede mencionar esta segunda vuelta cuando la verifique Claude.

Sin servidor, despliegue, migraciones nuevas ni reparación histórica. Sin modificaciones en CSS o JavaScript ni código incrustado en Blade. Código entregado en a5d5cc4; complemento de evidencia móvil sin cambios de producto. Pendiente verificación independiente por Claude y revisión de Carlos de la retirada de contenido del selector; la aprobación de la transferencia anterior se conserva. No se autocierra la tarea ni se hace push manual.