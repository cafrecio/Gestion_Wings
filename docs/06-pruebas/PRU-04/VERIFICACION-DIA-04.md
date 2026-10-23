# Día 4 — contraste técnico del recorrido humano

**Aclaración 23/10:** ejecución y contraste fueron de Codex. Son trabajos distintos, pero no
constituyen revisión independiente según AGENTS §6a. La firma de otro agente sigue pendiente;
los resultados y evidencias siguientes se conservan como entrega a revisar.

22/10/2026 — Codex CyE, chat Casos prueba humana I.
Entrega contrastada: `99d1f64a`, [informe humano](DIA-04.md). No se repitieron operaciones.

**Dictamen: conciliados los registros de dinero, alta y asistencias del recorrido.**
Se mantienen las dos tareas parciales del informe humano. Queda una discrepancia funcional
confirmada entre el estado Al día y la cuota ofrecida por Cobrar; no se declara aceptación integral.
T14 sigue en curso. Esta revisión no cierra los casos completos del catálogo ni habilita producción.

## 1. Evidencia obtenida por este chat

- Consultas concretas en `wingstest`, con snapshot SQL en transacción de solo lectura.
- Llamadas de lectura a servicios de cobranza/reportes con conexión MySQL en modo de solo lectura.
  Entorno comprobado `staging`, base efectiva `wingstest`, fecha de aplicación **13/10/2026 19:51:14 −03**.
  No se alteró ni detuvo el reloj. `session.expire_on_close=true` leído, sin modificar configuración.
- Lectura directa de los cuerpos del controlador de cobro, imputación de pagos, integración de caja,
  estados de cobranza, reportes y partes de pago; revisión de cuatro imágenes ya conservadas:
  ficha de Mora, caja cerrada, reporte de octubre y render del recibo CUOTA-9.
- Código de prueba `56379295`; diff de app/routes/resources con el HEAD de la entrega: vacío.

Fuentes y comprobaciones reproducibles:
[SQL](evidencia-verificacion-dia-04/consultas.sql) ·
[snapshot](evidencia-verificacion-dia-04/consulta-db.tsv) ·
[lector de servicios](evidencia-verificacion-dia-04/consulta-servicios.php) ·
[salida](evidencia-verificacion-dia-04/servicios.json) ·
[conciliador local](evidencia-verificacion-dia-04/conciliar.py) ·
[resultado](evidencia-verificacion-dia-04/conciliacion.json).

## 2. Dinero: filas y cálculos contrastados

| Control | Resultado comprobado |
|---|---|
| Caja 2 | Operativa 2; origen caja 1; heredado/inicial $10.000; VALIDADA por admin 1. Cierre 13/10 19:33:31 y validación 19:36:02. |
| Arqueo | Esperado y contado $119.500; diferencia $0; retenido $10.000; retirado declarado $109.500. Se valida el registro, no una entrega física de billetes. |
| Pagos 5–9 | Renata $43.000, Emma $45.000, Dante $30.000, Mateo $38.000 y **Julieta Morales $30.000**: $186.000, todos COMPLETADO, fecha de pago 13/10. |
| Imputaciones | Seis filas: Renata octubre 43; Emma septiembre 45; Dante septiembre 20 y octubre 10; Mateo octubre 38; Julieta noviembre 30 (miles de pesos). Cada pago coincide con la suma de sus imputaciones. |
| Movimientos 6–12 | Cinco cobros, remera $15.000 APORTE y limpieza $8.500 EGRESO NEGOCIO; siete activos y verificados. Ingresos $201.000, egresos $8.500, neto $192.500. |
| Reflejos de caja | Cada uno de esos siete movimientos tiene exactamente un asiento en Cashflow, con fecha, medio, clasificación y monto con el signo correcto. Sin duplicación en este conjunto ejecutado. |
| Medios del turno | Efectivo neto $109.500, MP $45.000 y Galicia $38.000. El efectivo contado incluye los $10.000 iniciales. |
| Disponible global | Efectivo $250.000 iniciales + $235.500 netos = $485.500; MP $1.320.000 + $83.000 = $1.403.000; Galicia $38.000. Total $1.926.500. |
| Reportes de octubre | Cuotas agosto $45.000 + septiembre $103.000 + octubre $167.000 + inscripción $5.000 = ingresos económicos $320.000. Egresos $8.500; resultado $311.500. |
| Aportes y adelanto | Aporte $15.000 fuera del resultado. Noviembre $30.000 en caja/Cashflow y fuera del ingreso económico de octubre. Neto global $356.500 = $311.500 + $15.000 + $30.000. |
| Deuda guardada | Cuotas pendientes de activos $1.844.000 + once cargos de inscripción de $5.000 = $1.899.000. Coincide con el reporte; no certifica cuotas hipotéticas que no estén guardadas (§4). |

El período `mes=10` del pago 9 no significa que haya cobrado octubre: sus imputaciones vinculan
el pago a la deuda 247 de **2026-11**, íntegramente PAGADA por $30.000. El render del recibo 9
muestra Julieta, noviembre 2026 y $30.000; coincide con esas filas.
La deuda futura tiene un evento de creación +$30.000 y otro de pago −$30.000, saldo cero.

## 3. Alumnos, clases y las dos tareas parciales

Mora es el alumno 341, activa en grupo 1 y plan 2 desde 13/10: dos clases semanales, $40.000.
Tiene una cuota de octubre por $40.000 y el cargo de inscripción 28 por $5.000, ambos pendientes.
Hay 101 activos: Patín 66/Fútbol 35; Principiantes de Patín 27. Contadores recalculados:
**64 Al día / 1 En plazo / 15 Morosos / 21 Deudores**. No se sustituyen por los antecedentes 65/0/22/13.

Clase 28: doce marcas, diez presentes; Emma y Martina ausentes. Clase 74: catorce marcas, doce presentes;
Dante y Leon ausentes. No hay duplicados alumno/clase en esas dos listas. Clase futura 6: cero marcas.
El aviso del servicio de reportes informa 32 clases sin asistencia. Las marcas persistidas coinciden
con el informe humano; no se atribuye un cambio de contador a usuarios o filtros distintos.

| Parcial | Contraste y alcance |
|---|---|
| T2: ficha de Mora | Datos y deuda correctos. Estado EN_PLAZO y seis días restantes reproducidos en el servicio; límite 20/10 a las 00:00. La fórmula cuenta los días restantes sin incluir hoy. La captura y la vista muestran «6 días de gracia», inscripción y cuota separadas, sin vencimiento explícito ni total conjunto. Se conserva como diferencia de presentación frente al guion, sin afirmar un error de capital o de fecha de vencimiento. |
| T6: pago de Dante | Base: septiembre PAGADA por $20.000; octubre tiene $10.000 pagados y saldo $38.000; estado MOROSO. El servicio aplica el monto de cada ítem recibido; la pantalla se operó con dos importes. No acredita reparto automático de un importe global ni rechazo de un pago fuera del orden cronológico. El aviso al usar fill no se reprodujo aquí como error de uso manual. |

T3 fue una variante de navegación: el ejecutor usó filtro En plazo porque no encontró buscador por
nombre en Cobranza. T10 conservó el egreso, pero la observación guardada es «Artículos para pista»,
sin el número de ticket del guion por la sustitución informada. No se acredita un adjunto de comprobante.
Se preservan el guion original y las columnas humanas; no se reescribe el esperado después de actuar.

## 4. Observación abierta: Al día y cuota que ofrece Cobrar

**D4-OBS-01 — discrepancia confirmada, sin corrección de producto en esta tarea.**

Julieta Morales (331) tiene plan vigente de $30.000, no tiene deuda guardada de octubre y solo tiene
noviembre PAGADA. CobranzaEstadoService devuelve AL_DIA. El ejecutor observó que Cobrar ofrecía octubre.
En `CajaWebController::cobrar`, si no existe una fila del período vigente, se añade a la colección
un `new DeudaCuota` por el precio del plan; ese objeto de octubre no se persiste por esa rama.
El estado general usa las deudas guardadas, mientras ese formulario agrega la cuota potencial en memoria.
Esto explica la diferencia observada; no demuestra que Julieta adeude efectivamente octubre.

Sofía (241), la persona del guion, tiene inscripción 17 VIGENTE por $5.000 sin pagos y estado DEUDOR;
no recibió ninguno de los cinco pagos nuevos. La premisa «Sofía al día» del cuento era incorrecta.
La revisión previa de este chat comprobó su plan, pero no acreditó esa premisa incluyendo inscripción.
La sustitución por Julieta se conserva con la autorización informada por el ejecutor.

Antes de resolver el producto hay que distinguir la regla de deuda exigible de la opción de crear/cobrar
una cuota no guardada. Opciones a resolver con Carlos: generar la deuda que realmente falta; no ofrecer
como deuda una cuota ya resuelta fuera del registro; o presentar explícitamente la creación de una cuota
como acción distinta del saldo actual. No se eligió una interpretación ni se creó octubre para forzar el cuadre.
El contrato de cobranza exige coherencia de saldos/listados; el comportamiento actual queda documentado
en T14 para decidir su tratamiento y preparar un caso específico, sin repetir el adelanto ya cobrado.

## 5. Procesos, seguridad, rendimiento y límites

- El informe humano relata el arreglo de sesión y la limpieza puntual del contador de ingreso.
  Esta revisión confirma la configuración actual de sesión y el reloj del 13/10. No reproduce el
  bloqueo original ni certifica retrospectivamente el contenido de las filas de caché ya retiradas.
- No se repitió la validación de caja ni se enviaron pagos, formularios o peticiones prohibidas.
  Siete reflejos únicos hoy no acreditan concurrencia, doble envío ni rechazo HTTP por rol.
- Lectura CLI de `ReporteMensualService::obtener('2026-10')`: **29 consultas SQL, 11,37 ms SQL y
  35,83 ms de duración** con 101 activos. Es una única observación del backend, sin navegador,
  concurrencia ni aumento de volumen; no certifica performance con una base grande.
- La consulta del reporte dio cero sin clasificar, cero cajas pendientes y deuda/disponible iguales
  a las sumas SQL. Los siete movimientos del turno tienen responsable y verificación persistidos.
- Los procesos de las 06:00, 06:15 y 08:00 no se acreditan por esta tarde ejecutada. No duplicación
  del 1/11 y liquidaciones siguen pendientes; el adelanto a seguir ahora es el de **Julieta**, no Sofía.
- Mora: primera asistencia pendiente el 14/10. Si no paga, EN_PLAZO hasta 19/10 y MOROSO desde
  20/10; comprobarlo cuando avancemos, no darlo por ejecutado hoy.
- Se revisaron cuatro imágenes de escritorio/render ya existentes, no todo el juego de capturas
  ni variantes a 375. El render del recibo no es captura del navegador; conserva esa procedencia.

## 6. Método y siguiente paso

El índice orientó la búsqueda, con cobertura metadata_changed/coverage_unavailable; las conclusiones
se apoyan en cuerpos de funciones y filas concretas. Se paginó la relación de llamadas del controlador.
No es un barrido exhaustivo de Wings. Un primer cursor quedó viejo al reindexarse y se repitió la consulta.

Se ajustó el SQL auxiliar al comprobar que Cashflow guarda egresos con signo negativo y movimientos
operativos con monto positivo; el control final compara signos normalizados y suma Cashflow sin invertirlos
otra vez. También se ajustó el lector TSV para los resultados vacíos. Esas correcciones son de los
auxiliares de auditoría, no defectos de Wings. Conciliador local final y sintaxis PHP correctos.

No se ejecutó suite porque no cambió código de aplicación. No hubo escrituras de negocio, cambios de
reloj/configuración, migraciones, limpieza de caché ni despliegue por este chat.

Sigue: registrar D4-OBS-01 y los alcances T2/T6, conservar estos datos, y preparar el Día 5 con actividad
habitual y la clase real de Mora. No duplicar el Día 4 ni dar por cerrada T14 o la aceptación integral.

## 7. Seguimiento incorporado después — 23/10/2026

Los resultados anteriores corresponden al contraste del 22/10. Faltaba volcarlos a la tabla común;
la revisión por lectura no había ejecutado controles negativos de acceso. Omisión de este chat.
La [tabla integral actualizada](control-integral/CONTROL-INTEGRAL.html) incorpora las 16 tareas,
una medición y D4-OBS-01; vincula criterios/variantes sin cerrar casos completos por una sola operación.

El 23/10 se ejecutaron 41 métodos existentes de permisos, validaciones e integridad en
`wings_testing_codex`: **41 aprobados, 331 aserciones, salida 0**, versión local `a15d6095`.
[Salida](control-integral/evidencia-seguridad-2026-10-23/salida.txt) y
[alcance por método](control-integral/CONTROL-INTEGRAL.html#seguridad).
HTTP interno/servicios y fixtures; sin tocar wingstest, su reloj o producción. No acredita CSRF
real, otras acciones por rol, sesiones entre dispositivos, concurrencia ni carga. Pendientes visibles
en la misma tabla. Consolidación y corrida esperan revisión independiente; T14 sigue en curso.
