# T70 y T71 — Inactivos, fecha y deuda separada

Codex CyE · 28/10/2026 · implementación local entregada a Claude para verificación.

## Regla aplicada

El pedido del 28/10 distingue eliminar de pasar a Inactivo. Se conservan las rutas,
el interruptor y sus respuestas de T84/T85, sin confirmación ni motivo. No se cambia
la eliminación de T65. El único agregado a su protección es que una auditoría de
pérdida impide borrar al alumno: no se puede destruir el rastro exigido por T71.

T70 guarda `inactivo_desde` y un historial de transiciones. Reactivar limpia la fecha
del tramo actual y conserva los eventos anteriores. La migración no completa fechas
de inactivos viejos. Revisión y reactivación por cobro pasan por el mismo registro;
las dos actualizaciones masivas del cobro se reemplazan por guardar el modelo, dentro
de las transacciones existentes. No cambia la cuota de reingreso de T38 ni la decisión T25.

Las altas del mes usan `fecha_alta`, sin sumar reactivaciones. Los pases a Inactivo
cuentan registros distintos con evento en ese mes; alternar varias veces suma uno.
Reactivar después no borra el pase de aquel mes. Dos deportes son dos registros,
igual que la matrícula. Los indicadores nuevos muestran solo cantidades.
El selector incluye meses con alumnos aunque no haya clases.

## Deuda y pérdida

`DeudaInactivosService` calcula el apartado actual de Cobranza y Reportes. Incluye el
saldo de cuotas de meses iniciados, inscripción y punitorios vigentes. No alimenta
Inicio ni las tarjetas anteriores. En un reporte de otro mes se lo rotula **actual**,
con fecha propia: no se presenta un saldo actual como histórico.

La inscripción es única por DNI. Si hay un registro activo de esa persona, ya suma
en el circuito activo y se excluye del nuevo apartado. Si todos son inactivos, se
imputa una sola vez al registro del cargo, o al primer registro disponible por DNI.
Se asigna antes de filtrar por deporte/grupo para no cambiar el dueño del importe
según el filtro.

ADMIN activo puede perder el saldo mostrado; OPERATIVO y PROFESOR reciben 403 al
forzar la ruta. Se bloquean persona, registros del mismo DNI, cuotas, cargos e
imputaciones; se recalcula el saldo y se rechaza una decisión desactualizada. Un
segundo envío no genera otra pérdida. La operación guarda en una misma transacción
importes por partida, autor, fecha y total. Si falla el registro, se revierte todo.

**Se eligió un campo propio `monto_perdido`, separado de la condonación.** El esquema
actual de cuotas no tiene `monto_condonado`: condona mediante `estado=CONDONADA`;
los cargos sí tienen ese campo. Esta precisión corrige la descripción inicial del
pedido, sin modificar el mecanismo de perdón. No se reutiliza el perdón porque
mezclaría una pérdida con un regalo y permitiría reactivarla mediante sus rutas.

La pérdida no modifica el precio original, el pago previo ni el devengado analítico;
no crea un pago, recibo, ingreso ni egreso. Solo reduce el saldo cobrable. El historial
de saldos de Reportes registra la reducción en su fecha y los cargos usan la auditoría
de pérdidas para reconstruir cortes previos. La ficha muestra el total perdido,
autor y fecha, separado de pagos y condonaciones.

**Al volver, lo perdido permanece perdido.** La deuda que no se perdió vuelve al
circuito normal. T38 genera su cuota de reingreso cuando corresponde; si ese período
ya existía y se perdió, no lo regenera. Los períodos futuros todavía no iniciados
no se incluyen en una pérdida actual.

## Interfaz y evidencia

[Galería para Carlos](APROBAR-T70-T71.html). Cuatro pantallas solicitadas, más consulta
del operativo y login de calibración. Datos sintéticos; HTML pedido a Laravel y
exportado por `InactivosT70T71Test`, no una maqueta. Las imágenes se generan en Chrome
con `scripts/pruebas/capturar-t70-t71.mjs`: escritorio de 1366 y marco de 375 px.

No hay JavaScript en Blade ni cambios de CSS compartido. `ds-app.js` ya confirma
formularios; se reutiliza esa confirmación. El módulo nuevo solo guarda la decisión
y actualiza fila, cantidad y total sin recargar. El error queda junto a la fila.

El script de Chrome incluye, después de tomar las capturas, una comprobación de la
interfaz con respuestas simuladas: rechazo sin perder fila/total, reintento, doble
clic y actualización sin navegación. Es una comprobación del cliente, distinta de
las peticiones reales y los efectos en MariaDB comprobados por PHPUnit.

## Pruebas y alcance

Quince métodos propios en `tests/Feature/InactivosT70T71Test.php`: fecha, historia,
deduplicación mensual, fechas viejas desconocidas, altas y filtro por deporte,
meses distintos, deuda separada, permisos forzados, saldo desactualizado, repetición,
pagos parciales, inscripción compartida, cargos, períodos futuros, reingreso, rollback,
conservación de cortes anteriores y presentación de cantidades/auditoría.

ALU-017/ALU-025 se recorren por rutas reales: apagar, ficha con deuda, buscador de
cobro, clase futura sin alumno, clase anterior con su asistencia intacta, Inicio,
generación del mes siguiente sin cuota ni Revisión, y reingreso con deuda previa y
cuota T38. El interruptor de ficha se conserva; sus regresiones T84 se corren aparte.

La búsqueda de impacto pasó primero por codebase-memory. La cobertura informó
metadatos no comprobables y un índice ocupado durante una actualización; se completó
con las fuentes reales y búsqueda en `app/`. Los consumidores de saldo compartido
incluyen ficha, selector de cobro, Cobranza e Inicio; ReporteMensualService también
alimenta Inicio, evolución y saldos de Cashflow. Por eso se exige la suite completa.

Todos los ensayos usan `wings_testing_codex`. No se toca la base del club ni el sitio
de prueba. Los cambios concurrentes T62 quedaron en su commit `5b0869cd`: no se incluyen
en esta entrega ni se certifican por leerlos en la suite. El contador compartido 1220
incluye 1195 anteriores, 15 propias y 10 T62; la anotación de las 15 propias quedó incorporada
durante las actualizaciones documentales concurrentes, antes del commit de esta entrega.

### Resultados finales

- **Completa: 1214 aprobadas, 6 omitidas, 10081 aserciones, 1671,44 s; salida0.**
  [Salida completa](evidencia-t70-t71/suite-completa.txt). Se esperó a que terminaran
  los procesos35584/22056 antes de iniciarla. Las23 fuentes propias del corte siguen
  iguales: [huellas comprobadas](evidencia-t70-t71/fuentes-suite-completa.json).
- **Área y repetición: 37 aprobadas, 222 aserciones, 27,35 s; salida0.** Incluye15
  propias, T84, T25, guardia documental y10 T62 ajenas repetidas sin corregirlas.
  [Salida](evidencia-t70-t71/area-y-repeticion.txt).
- Rápida inicial:1072 aprobadas,6 omitidas,6 fallas,8259 aserciones,717,66 s.
  Cinco fallas de T62 y una del contador, con trabajo concurrente todavía en curso.
  Las seis pasaron después al repetirlas; no se presenta aquella rápida como verde.
  [Salida original](evidencia-t70-t71/suite-rapida.txt). La completa posterior es verde.
- PHP sin errores en los 16 archivos nuevos/modificados; Blade compila y limpia;
  JavaScript sin errores de sintaxis y build Vite correcto. No se modificó CSS compartido.
- Después de actualizar tablero y documentación: 9 guardianes/36 aserciones verdes,
  [salida](evidencia-t70-t71/guardianes-finales.txt). No hubo más cambios de producto propios.
- **Chrome: 12 imágenes revisadas**, seis pantallas a 1366/marco375, incluido el login
  de calibración. Sin errores de JavaScript; botones 96×32. El marco tiene 375px;
  el canal normal de desplazamiento deja 360px interiores, sin desborde del documento.
  [Medidas](evidencia-t70-t71/medidas-chrome.json) ·
  [Cliente: rechazo, reintento, doble clic y sin navegación](evidencia-t70-t71/interaccion-cliente.json).

Después de la completa, T62 volvió a cambiar el generador mensual y agregó otra prueba.
Se repitieron las 15 propias contra ese generador: todas verdes; el grupo final dio 21
aprobadas y una falla documental mientras el contador ajeno cambiaba de 1220 a 1221.
[Salida posterior](evidencia-t70-t71/area-final-cambios-concurrentes.txt). La completa
conserva su corte; no certifica esos cambios posteriores ajenos. Los cambios propios
se guardan desde el índice ya revisado, conservando fuera del commit los nuevos ajustes
de T62 y sus modificaciones del contador en los documentos compartidos.
El contador posterior se repitió aislado y pasó, 1/7:
[salida](evidencia-t70-t71/contador-posterior.txt). No se corrigieron pruebas ajenas.

T70/T71 quedan `a_verificar`, `hizo=Codex`, `tiene=Claude`, `verifica=Claude`.
Faltan revisión independiente de lógica y aprobación visual de Carlos. No se cierran
por el implementador. Sin despliegue ni push manual con esta entrega.

## Propuestas para la tabla de pruebas, sin editarla

- **ALU-017.b:** «Deja de figurar; activos −1; el total adeudado baja por toda su
  deuda» → «Sale del listado activo; activos −1; el total adeudado activo baja por
  su deuda. Aparece aparte en Deuda de alumnos inactivos, sin sumarse al por cobrar».
- **ALU-025.b:** «En la ficha, Reactivar pide confirmación con el nombre del alumno»
  → «En la ficha, el mismo interruptor reactiva al instante, sin confirmación».
  El texto actual quedó anterior a T84 y al pedido expreso del 28/10 de conservarlo.
- **REP-006:** agregar a las cantidades «Altas por fecha de ingreso y registros que
  pasaron a Inactivo en el mes; cada registro cuenta una vez aunque alterne su estado».

No se cambia la copia ni las respuestas DU-11/DU-39. El alcance hasta producción
queda limitado a T70/T71; los restantes trabajos conservan sus dueños.
