# T24 — Un solo modo de cargar importes en Wings

## Entrega en desarrollo, 11/10/2026

**Implementada; suite completa verde. No cerrada ni desplegada.**
La pantalla queda pendiente de aprobación de Carlos y la regla de otro agente.
[Visor de las 90 capturas](evidencia-t24/visor.html): escritorio, marco real de 360 px,
importe válido, tercer decimal en rojo, dos variantes protegidas y login de control.

El primer inventario lo relevó Codex CAB sobre `dd62c64f2401e1c5c074d66c2b500dfd48b8b87b`, rama `main`.
`git pull --ff-only`: ya actualizado; el árbol estaba limpio al comenzar.
Leídos AGENTS, CLAUDE, protocolo, resumen, tres logs activos, índice compacto,
skill wings-design, DESIGN-RULES y vista canónica Alumnos.

## Decisiones confirmadas por Carlos, 11/10

- Punto decimal y hasta dos decimales. `48000` significa cuarenta y ocho mil.
- Cambio respecto de la orden inicial: al tipear `48.505`, el valor queda `48.50`,
  el texto se pone rojo y se muestra un aviso por 4 segundos:
  «No se pueden colocar más de dos decimales». No bloquea continuar ni guardar
  el valor corregido. Carlos: «Pero no rechaza».
- Coma decimal: Carlos respondió «Convertirla en punto: 48.50».
- Importe guardado: Carlos confirmó «Sí: 48,000.50»; coma para miles y punto decimal.
- JavaScript externo a Blade; un comportamiento común para todos los campos.

El servidor recibe el valor corregido y canónico del campo. Un pedido directo
que conserve tres decimales debe rechazarse sin escribir, como exige la orden.
La corrección durante el tipeo no habilita guardar precisión adicional.

## Método y alcance del inventario

Primero se reindexó y consultó `codebase-memory-mcp`; después se leyeron las
fuentes reales. La cobertura notificó vistas Blade incompletas y metadatos
cambiados: el inventario se contrastó con una búsqueda de todos los `<input>`,
componentes de importe y campos generados desde `resources/js`, además de
`routes/web.php` y los cuerpos de controladores y validadores correspondientes.

Hay **16 pantallas o modos de carga**, incluyendo Rectificar de T23. Los modos de alta
y edición se separan aunque compartan archivo. En Cobrar hay cuatro clases de
campo, una generada dinámicamente. El precio de Grupos también tiene filas nuevas
desde un `<template>`; ambas deben entrar en la cobertura.

La tabla preserva **cómo funcionaba antes de T24**; no describe el código final.
Ese inventario se entregó primero, por lectura de fuente y ejecución aislada del
lector. Las verificaciones posteriores de pedidos y navegador están más abajo.

### Comportamientos actuales compartidos

- **M1 — componente `x-ds.money-input`:** [componente](../../../resources/views/components/ds/money-input.blade.php)
  presenta el valor con punto de miles y cero decimales (`number_format(..., 0, ',', '.')`).
  [ds-app.js](../../../resources/js/ds-app.js) descarta todo lo que no sea dígito,
  agrega puntos de miles y quita puntos/comas al enviar. No rechaza los caracteres
  incorrectos en rojo: los transforma. Por ejemplo, al tipear `48.50` se forma
  `4.850`; letras y signo negativo se eliminan. El componente redondea los centavos
  del valor guardado al mostrarlo.
- **M2 — input de texto con `data-money="true"`, sin componente:** usa el mismo
  JavaScript M1, pero su marcado y presentación inicial son propios. Si recibe
  directamente un valor canónico `48.50`, la inicialización elimina el punto y
  forma `4.850`. No todos los campos de la pista inicial son instancias del componente.
- **N — input `number`:** usa las restricciones `min/max/step` del navegador;
  no usa M1. `step="0.01"` comprueba el salto numérico, no la cantidad de dígitos
  del texto: `48.000` puede representar numéricamente 48. El comportamiento del
  teclado/coma depende del navegador; no se presenta como probado en pantalla.
- **A — adelantado con código propio:** texto sin M1; `cobrar.js::parseMonto`
  elimina todos los puntos, transforma la coma en punto y usa `parseFloat`.
  La propuesta y las filas agregadas se muestran con cero decimales mediante
  `formatoMonto`. No hay un rechazo compartido de letras, negativos o tercer decimal.

## Inventario vista por vista

| N.º | Pantalla / ruta GET | Archivo y campo | Hoy en pantalla (fuente) | Pedido directo y regla actual |
|---|---|---|---|---|
| 1 | Apertura, `/caja/apertura` | `caja/apertura.blade.php`: `efectivo_inicial` | M1; muestra el cambio heredado redondeado sin centavos. No aparece si hay turno abierto o falta medio de efectivo activo. | POST `/caja/apertura`: normaliza con lector Excel y luego CajaService exige `required/numeric/min:0/max:9999999999.99/decimal:0,2`. Cero permitido. |
| 2 | Cierre, `/caja/{id}/cerrar` | `caja/cierre.blade.php`: `efectivo_contado`, `cambio_retenido` | M1 al declarar por primera vez. `caja-arqueo.js` quita puntos para calcular diferencia/entrega. En corrección de cierre ya contado son texto de solo lectura + inputs ocultos canónicos. | POST `/caja/{id}/cerrar`: lector Excel antes de CajaService; ambos `required/numeric/min:0/decimal:0,2`; contado tiene máximo 9999999999.99 y cambio no supera contado. Cero permitido; conservación del conteo previo es una regla aparte. |
| 3 | Cobrar, `/caja/cobrar/{alumnoId}` | `caja/cobrar.blade.php`: `montos_cuota[periodo]`, `adelanto-monto`, `monto_entregado`; `cobrar.js`: filas de `montos_cuota[periodo]` adelantadas | Cuotas existentes: M2, inicialmente deshabilitadas, se habilitan al tildar; saldo renderizado sin decimales. Borrador de adelantado y filas nuevas: A, sin componente ni `data-money`. Entregado: N, opcional, `min=0.01/step=0.01`. Borrador no tiene `name`: se transmite por la fila agregada. | POST mismo destino: elimina puntos/comas de **todos** los `montos_cuota` antes de `nullable/numeric/min:0.01`, sin límite de decimales; entregado `nullable/numeric/min:0.01/max:99999999.99`, sin límite de decimales. Cero no admitido en un importe enviado. El cobro usa FormData/fetch y confirmaciones propias que requieren el mismo validador común. |
| 4 | Agregar a una caja, `/caja/{id}/editar` | `caja/editar.blade.php`: `monto` | M2; vacío o `old()`. | POST `/caja/{id}/editar`: `required/numeric/min:0.01`, sin límite de decimales. |
| 5 | Editar movimiento, `/caja/{cajaId}/movimientos/{movId}/editar` | Mismo `caja/editar.blade.php`: `monto` | M2; inicializa con `old()` o `movimiento->monto`, sin conversión específica en Blade. La inicialización común descarta el punto decimal. | PUT `/caja/{cajaId}/movimientos/{movId}`: `required/numeric/min:0.01`, sin límite de decimales. |
| 6 | Nuevo movimiento de Caja, `/caja/movimiento` | `caja/movimiento.blade.php`: `monto` | M2; vacío o `old()`. | POST mismo destino: `required/numeric/min:0.01`, sin límite de decimales. |
| 7 | Nuevo movimiento de Cashflow, `/cashflow/movimiento` | `cashflow/movimiento.blade.php`: `monto` | M2; vacío o `old()`. | POST mismo destino: `required/numeric/min:0.01`, sin límite de decimales. |
| 8 | Alta de Grupo, `/grupos/create` | `grupos/_form.blade.php`: `planes[i][precio_mensual]` y template `planes[__IDX__][precio_mensual]` | M2; marcado manual con prefijo $, presentación a cero decimales. `grupos.js` inicializa M1 en cada fila nueva. | POST `/grupos`: cada precio `required/numeric/gt:0`, sin límite de decimales. |
| 9 | Edición de Grupo, `/grupos/{id}/edit` | Mismo formulario: precios existentes y filas nuevas | M2; `number_format` con cero decimales en precios guardados; filas nuevas como alta. | PUT `/grupos/{id}`: mismos límites de importe; conserva las reglas de pertenencia, frecuencia y eliminación. |
| 10 | Alta de Profesor, `/profesores/create` | `profesores/_form.blade.php`: `valor_hora` | M1; el campo se muestra cuando el deporte liquida por HORA. | POST `/profesores`: `nullable/numeric/min:0`, sin límite de decimales. El servidor web admite cero y vacío actualmente. |
| 11 | Edición de Profesor, `/profesores/{id}/edit` | Mismo formulario: `valor_hora` | M1; valor guardado redondeado a pesos enteros. Al elegir COMISION, el JavaScript existente vacía `valor_hora`. | PUT `/profesores/{id}`: mismo validador web que alta. No cambiar el porcentaje ni la selección del tipo de liquidación. |
| 12 | Alta de Medio de pago, `/tipos-caja/create` | `tipos-caja/_form.blade.php`: `saldo_inicial` | M1; valor inicial 0. | POST `/tipos-caja`: `required/numeric/min:0`, sin límite de decimales. |
| 13 | Edición de Medio de pago, `/tipos-caja/{id}/edit` | Mismo formulario: `saldo_inicial` | Sin historial: M1, redondea centavos al mostrar. Con historial: input manual `readonly` sin `name` ni `data-money`, muestra dos decimales con coma y punto de miles. | PUT `/tipos-caja/{id}`: editable `required/numeric/min:0`, sin límite de decimales; con historial se excluye el saldo antes de validar y no se modifica. Preservar esa protección. |
| 14 | Configuración, `/configuraciones` | `configuraciones/_campo.blade.php`, incluida desde `index`: `valor` para `inscripcion_importe` | N, `min=0.01/max=99999999.99/step=0.01`, formulario `novalidate`. `configuraciones.js` manda el texto por JSON y muestra error del servidor con `aria-invalid`; no valida durante la escritura. | PATCH `/configuraciones/inscripcion_importe`: `required/numeric/min:0.01/max:99999999.99/decimal:0,2`. Ya limita la precisión por servidor. |
| 15 | Ajustar una liquidación, `/liquidaciones/{id}` | `liquidaciones/show.blade.php`: `monto_final` | N, `min=0/max=9999999999.99/step=0.01`, requerido; presenta `old()` o `monto_a_pagar`. | POST `/liquidaciones/{id}/ajustar`: regex de 1–10 dígitos enteros y opcional 1–2 decimales con punto. Admite cero y rechaza tercer decimal, coma, negativos y letras. El ajuste conserva cálculo original, motivo y control del monto anterior. |
| 16 | Rectificar apertura (T23), `/caja/{id}/rectificar` | `caja/rectificar.blade.php`: `efectivo_inicial` | T23 incorporó una instancia M1 con el importe guardado. Se incluyó al estar disponible durante la integración. | POST mismo destino, `guardarRectificacion`: el mismo normalizador de arqueo, seguido de las reglas de `rectificarApertura`; importe no negativo, dos decimales, motivo y caja abierta. T24 cambia formato/validación del importe, no esas reglas. |

### Fuentes del servidor

- [CajaWebController](../../../app/Http/Controllers/CajaWebController.php): abrir,
  normalizarImportesArqueo, editarStore, updateMovimiento, cerrar, pagar y movimientoStore.
- [CajaService](../../../app/Services/CajaService.php): abrirCajaOperativa y cerrarCajaOperativa.
- [CashflowWebController](../../../app/Http/Controllers/CashflowWebController.php): store.
- [GrupoWebController](../../../app/Http/Controllers/GrupoWebController.php): store, update y storePlan.
- [ProfesorWebController](../../../app/Http/Controllers/ProfesorWebController.php): store, update y validationRules.
- [TipoCajaWebController](../../../app/Http/Controllers/TipoCajaWebController.php): store, update y saldoInicialEditable.
- [ConfiguracionWebController](../../../app/Http/Controllers/ConfiguracionWebController.php): CAMPOS y update.
- [LiquidacionWebController](../../../app/Http/Controllers/LiquidacionWebController.php): ajustar.
- [Rutas web](../../../routes/web.php).

## Otros caminos que deben quedar cubiertos

- **Alta de plan por pedido directo:** POST `/grupos/{id}/planes`,
  `GrupoWebController::storePlan`, recibe `precio_mensual` con `required/numeric/gt:0`
  sin límite de decimales. La ficha actual `grupos/show` no tiene campo ni formulario
  de alta de precio, pero la ruta existe: requiere validación por pedido directo.
- **Campos ocultos de importes:** `inscripcion_importe_visto` y `cuota_importe_visto`
  en Alumnos; `efectivo_contado/cambio_retenido` de cierre ya declarado;
  `monto_anterior` y `monto_esperado` en Liquidaciones. Son valores de confirmación
  o control, no campos de escritura. Conservar sus comparaciones y el valor canónico;
  mantienen su valor canónico y controles existentes, sin someterlos al formateo del teclado.
- **T23, coordinación con Gemini:** T24 se reserva el campo/componente y el formato
  recibido; Gemini mantiene aviso, rectificación y reglas de apertura. Si T23
  incorpora un campo nuevo para rectificar `efectivo_inicial`, debe usar el mismo
  componente/regla y agregarse a la matriz y las capturas de T24. Rectificar ya quedó
  incluido. Los cambios de T24 del controlador compartido quedaron incorporados en
  el commit T23 `03aaf11` de Gemini mientras se trabajaba. Este cambio completa sus
  dependencias y el resto de T24; no atribuye el aviso >50% ni la rectificación a Codex.

## Lector de Excel: comprobación antes de cambiarlo

[FormatoExcelCargaService::monto](../../../app/Services/FormatoExcelCargaService.php)
lee punto de miles y coma decimal en strings. Los números nativos siguen otro
camino y se formatean a dos decimales. Se ejecutó el cuerpo actual aisladamente
con PHP, sin iniciar Laravel ni acceder a ninguna base:

| String de entrada | Salida del lector |
|---|---|
| `48000` | `48000.00` |
| `48.50` | `null` |
| `48.000` | `48000.00` |
| `48.0000` | `null` |
| `48,50` | `48.50` |
| `0`, `-1`, `abc` | `null` |

`normalizarImportesArqueo` solo sustituye si el resultado no es `null`. Por eso
un punto decimal puede pasar por la validación posterior si el lector no lo
reconoció; no corresponde afirmar que apertura/cierre rechazan todo punto decimal.
En cambio `48.000` sí se convierte en 48000 antes de la validación de precisión.

El mismo lector sirve a las importaciones. T24 desacopló su uso en los formularios
web. `FormatoExcelCargaService` no fue modificado; el padrón/Excel conserva su formato.

## Fuera de los campos de importe tipeables

- Configuración: días de gracia, porcentajes y días de reglas de primera cuota,
  correo y número de chat de Telegram.
- Profesores: `porcentaje_comision`, CBU/alias y demás datos personales.
- Liquidaciones/create: año, mes, rango de fechas, selección de profesor.
- Alumnos: el importe de inscripción y la primera cuota se informan/calculan;
  no hay un campo visible para escribir un precio en `_form`.
- Grupos/show, listados de Grupos/Profesores, Caja/configuracion, Cobranza,
  Revisión de cobranza, Movimientos/index, Reportes, fichas, recibos y PDFs:
  muestran importes o permiten seleccionar registros, pero no agregan un input
  de pesos en la búsqueda de marcado/campos dinámicos.
- API deshabilitada: conservar ese estado; no se reabre para T24.
- Primera carga por Excel: entrada de archivo, no campo de pesos de formulario.
  No cambiar el lector/importador ni los formatos de su planilla por esta tarea.

## Qué se implementó

- Todos los campos editables de la tabla, los saldos de solo lectura y las filas
  nuevas usan `x-ds.money-input`. Configuración conserva sus campos de días,
  porcentajes, correo y Telegram; Profesores conserva la comisión.
- `money-input.js`, importado por `ds-app.js`, concentra formato, precisión,
  validación, aviso, foco, lectura de importes y envío. Se retiró el bloque antiguo.
  Cobrar y Arqueo leen mediante este código; se conservaron sus cálculos y topes.
- FormData y los formularios normales envían el valor canónico. Configuración usa
  el mismo campo al guardar por JSON y compara correctamente el valor al salir.
- `App\Rules\ImportePesos` normaliza entradas válidas y valida precisión. Los
  pedidos inválidos no se redondean ni recortan en servidor. Se aplica también
  al alta de plan por ruta directa y al formato de ajuste del modelo Liquidacion.
- Se conservaron mínimos, máximos, campos opcionales, confirmaciones, permisos,
  bloqueos, cálculo original e inmutabilidad de importes. El saldo inicial con
  historial sigue excluyéndose del pedido aunque intenten falsificarlo.
- El JavaScript de selección HORA/COMISION de Profesores se trasladó a
  `profesores-form.js`, usando los `data-tipo` existentes; salen dos scripts inline.
  `CspSinCodigoIncrustadoTest` ahora vigila 13, sin activar CSP bloqueante.
- En Grupos a 360 px, la frecuencia queda encima del importe para que
  `48,000.50` se lea completo. Escritorio conserva la fila. El cambio se limita
  a los controles de precio; el navegador mide que el texto entra en el campo.
- La regla queda en DESIGN-RULES §10. No hubo cambio del importador, esquema ni
  datos del club por T24. La migración de rectificación pertenece a T23.

## Pruebas permanentes y evidencia

Se agregaron **6 pruebas PHP permanentes**: cinco en
[ImportesPesosT24Test](../../../tests/Feature/ImportesPesosT24Test.php) y una en
[ImportePesosTest](../../../tests/Unit/ImportePesosTest.php). El soporte está en
[ImportesT24](../../../tests/Support/ImportesT24.php); rechaza bases que no sean descartables.
Todas las ejecuciones de Codex usaron **wings_testing_codex**.

La matriz cubre 20 caminos de escritura: los campos de las 16 pantallas,
contado/cambio separados, cuota/adelantado/entregado separados y alta de plan
directa. Comprueba los importes guardados con `48.50`, `48,50`, `48,000.50` y
`48000`; rechazo de `48.505`, `48.000`, `48.0000`, negativos, letras y exponentes;
fotografía de las tablas antes/después para cada rechazo; cero según cada flujo;
saldo protegido y presentación de centavos al editar. No basta una inspección del marcado.

**Regresiones comprobadas antes de arreglar el comportamiento:** dos pruebas
fallaron (16 aserciones, 10,39 s). Apertura aceptaba `48.000` por $48.000,
y cobrar `48.50` guardaba `4850.00` en lugar de `48.50`. Las primeras dos
corridas de preparación tuvieron errores del fixture (subrubro existente y nombre
de tabla); fueron corregidos y no se presentan como fallos de Wings.

[Prueba de navegador permanente](../../../tests/Browser/importes-t24.mjs):
Chrome sobre HTML emitido por Laravel, con assets compilados y servidor auxiliar
que solo sirve archivos. Se bloquean los pedidos de escritura del navegador;
los guardados se comprueban por los pedidos a Laravel de la matriz PHP.
La pantalla no fue fabricada a mano. Solo el marco de 360 px es HTML auxiliar.

Navegador: **1292 controles, cero fallos**, 19 casos de campos en escritorio y
marco, dos clases de filas dinámicas, dos variantes protegidas y login.
Punto/coma, truncado y rojo, aviso, desbloqueo del valor corregido, negativos,
letras, cero, recuperación, lectura de guardados, FormData y texto que entra
completo en el campo y tipeo carácter por carácter. El login y las pantallas editables miden 360 px, sin
desborde de página. Aviso medido cerca de 3,9 s en ambas corridas de resolución.
[Resultado completo](evidencia-t24/resultado-navegador.json).
Los assets exactos están fijados en `evidencia-t24/build/`: una recompilación
concurrente había borrado el CSS que referenciaba el primer HTML. El login lo
detectó; se renovaron las respuestas y las 90 capturas sobre la copia fija.
La prueba permanente usa el build actual por defecto; para reproducir esta
entrega se indica `WINGS_T24_BUILD_DIR=docs/06-pruebas/PRU-04/evidencia-t24/build`.

Comprobaciones PHP afectadas: **57 aprobadas / 1288 aserciones**, 23,35 s.
Las pruebas históricas que enviaban punto de miles se adaptaron a los mismos
importes con el formato nuevo: apertura/cierre, cobro, Inicio operativo,
adelantados y saldo de solo lectura. Después, 21 controles de Inicio/adelantados
y documentación pasaron (188 aserciones, 18,40 s).

La primera suite completa dio **627 aprobadas / 2 omitidas / 10 fallas**,
5936 aserciones, 469,12 s. Nueve fallas eran esos datos de prueba con formato
viejo; otra era el conteo, porque durante la corrida entraron cuatro pruebas
de Historial de una tarea ajena (639 → 643). Se corrigieron los datos de prueba
de T24 y el conteo; no se modificó la lógica de Inicio ni de adelantados.
[Primera salida conservada](evidencia-t24/suite-completa-primera.txt).

**Segunda suite completa: 641 aprobadas / 2 omitidas, 643 en total**;
6075 aserciones, 428,69 s, wings_testing_codex, 11/10.
[Salida completa](evidencia-t24/suite-completa.txt).
Sintaxis PHP de archivos propios y Blade correcta; `view:cache/view:clear`
correctos; build correcto y sintaxis de JavaScript comprobada.

## Rutas exactas de las capturas

Carpeta: `docs/06-pruebas/PRU-04/evidencia-t24/capturas/`.
Visor: `docs/06-pruebas/PRU-04/evidencia-t24/visor.html`.
HTML emitido por la aplicación y marcos: `docs/06-pruebas/PRU-04/evidencia-t24/html/`.
Inventario de rutas/selecciones reales de los escenarios: [pantallas.json](evidencia-t24/pantallas.json).

Para cada identificador de la lista siguiente existen los cuatro PNG:
`<identificador>-escritorio-valido.png`, `<identificador>-escritorio-aviso.png`,
`<identificador>-marco-360-valido.png`, `<identificador>-marco-360-aviso.png`.

`apertura`, `rectificacion`, `cierre-contado`, `cierre-cambio`, `cuota`,
`adelantado`, `adelantado-fila-nueva`, `entregado`, `caja-agregar`, `caja-editar`,
`caja-movimiento`, `cashflow`, `grupo-alta`, `grupo-editar`, `grupo-fila-nueva`,
`profesor-alta`, `profesor-editar`, `medio-alta`, `medio-editar`, `configuracion`,
`liquidacion`.

Además: `medio-historial-escritorio-valido.png`, `medio-historial-marco-360-valido.png`,
`cierre-con-conteo-escritorio-valido.png`, `cierre-con-conteo-marco-360-valido.png`,
`login-escritorio.png`, `login-marco-360.png`. Las variantes protegidas no
permiten tipear: se capturan sin fabricar un estado rojo. Total: **90 PNG**.

## Alcance de la evidencia y lo que no se probó

- No se usó un teléfono físico ni su teclado Android/iOS. El marco hace que
  Chrome maquete el contenido a 360 px; la medición y el login controlan el método.
- No hubo navegación manual con login desde el navegador: la autenticación de
  las respuestas fue por `actingAs` en la aplicación de pruebas. Los campos sí
  se operaron en Chrome sobre esas respuestas reales.
- El aviso se comprueba sobre el HTML local; no se envía desde Chrome al sitio
  del club. Los guardados y rechazos se probaron por HTTP contra Laravel y base propia.
- La comparación adicional se ejecutó sobre una copia aislada de `dd62c64`,
  con sus fuentes y su propio build: **18 de 18 campos de las 15 pantallas
  originales detectan la regresión** en Chrome. También se registraron los
  57 pedidos de tres decimales de 19 caminos del servidor anterior.
  [Navegador anterior](evidencia-t24/antes/navegador.json) ·
  [Pedidos anteriores](evidencia-t24/antes/pedidos.json) ·
  [Generador de esa evidencia](evidencia-t24/antes/T24AntesRunner.php).
  Rectificar se agregó después por T23 y no existía en esa versión; está cubierto
  por las comprobaciones finales del componente/regla que comparte con apertura.
- No se modificaron el sitio de prueba, su reloj ni producción por Codex. No hay
  despliegue ni aprobación visual de Carlos ni verificación independiente de la regla.
- No se incluye en el commit de T24 el trabajo ajeno de Historial/permisos. Se
  preservan sus archivos y su estado; el controlador compartido ya incorporó
  las líneas de T24 en `03aaf11` como se explicó arriba.

## Entrega y paso siguiente

Tablero entregado en `a_verificar`, hizo Codex, tiene Carlos,
paso «Carlos aprueba las capturas; despues verifica otro agente».
Publicar solo los archivos propios, con el motivo real `Diseno-autorizado`.
Carlos aprueba las pantallas con este visor; después otro agente verifica la
regla. **Codex no cierra T24.**
