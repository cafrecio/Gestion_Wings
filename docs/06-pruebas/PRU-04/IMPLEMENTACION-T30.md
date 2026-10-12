# T30 — Mostrar los centavos en Wings

## Inventario inicial y decisiones, 12/10/2026

Primer entregable: relevamiento del código real, antes de modificar su presentación.
Implementación entregada a verificar; suite completa aprobada y capturas finales guardadas. No cerrar ni desplegar esta tarea.
Base del relevamiento: `8a8a6b8`, después de `git pull --ff-only` actualizado.
T29 ya está publicado en ese corte. T28 tiene cambios ajenos sin publicar: no se editan.
El índice se actualizó; Blade tiene cobertura parcial y Caja detalle/resumen no se
parsean correctamente. Se verificaron los formatos y sus usos en las fuentes reales.

Carlos confirmó en este chat:

- Listados con el mismo formato que la carga: `$48,000.50`.
- Siempre dos decimales: `$48,000.00` también para importes redondos.
- **Gráficos fuera de T30**, por su corrección posterior «Los graficos no, el resto si».
  La respuesta anterior de incluir centavos en gráficos queda reemplazada.
- Recibos PDF nuevos, mails y Telegram incluidos. Los PDF emitidos se conservan:
  «Como estamos en test, no cambiemos los anteriores. En produccion todos van a ser nuevos».
- Cashflow: «Gemini termina y publica T28; después Codex aplica T30».
- Excel: Carlos confirmó conservar celdas numéricas, dos decimales en el catálogo
  nuevo y los formatos del informe original. No convertir esos importes a texto.

Los formatos de abajo son **los de partida**, no los que debe dejar T30.
`AR/0` significa pesos enteros con punto de miles; `AR/2`, coma decimal y dos decimales.
`T24/2` significa coma de miles, punto decimal y dos decimales. El signo `$`, espacios,
signos negativos, estados «Sin datos» y permisos pertenecen a cada presentación.

## Pantallas, vista por vista

| Pantalla/ruta | Fuente | Cada importe mostrado hoy | Decimales de partida |
|---|---|---|---|
| Inicio ADMIN, `/admin/dashboard` | `admin/dashboard.blade.php`, `$dinero` | Ingresos y egresos del mes; alumnos por cobrar; profesores por pagar; resultado; disponible; saldo de cada cuenta; suma pendiente de validar. Valores recibidos en centavos y divididos por 100. | AR/0 en todos |
| Inicio OPERATIVO, `/operativo` | `operativo/dashboard.blade.php` | Total cobrado hoy. | AR/0 |
| Alumno nuevo/editar, `/alumnos/create`, `/alumnos/{id}/edit` | `alumnos/_form.blade.php`, `alumnos-form.js`, `alumnos-inscripcion.js`, `AlumnoWebController` | Precio mensual de cada opción de frecuencia; aviso de inscripción nueva/previa/corrección; opción de generar la primera cuota cuando el ingreso fue en mes cerrado. | Precio: AR/0; inscripción y primera cuota: AR/2 |
| Ficha de alumno, `/alumnos/{id}` | `alumnos/show.blade.php` | Saldo de inscripción; precio del plan; saldo de cada cuota (variantes pagada/pendiente); pago final de cada cobro (normal/anulado). | Inscripción AR/2; plan, cuotas y pagos AR/0 |
| Caja, `/caja` | `caja/index.blade.php` | Ingresos, egresos y neto de cada caja. | AR/0 |
| Apertura, `/caja/apertura` | `caja/apertura.blade.php`, `CajaService` | Efectivo del último cierre, repetido en el aviso de diferencia grande; efectivo recibido en el campo de carga. | Texto AR/2; campo T24/2 |
| Rectificación, `/caja/{id}/rectificar` | `caja/rectificar.blade.php` | Efectivo declarado actual; importe a rectificar en el campo. | Texto AR/2; campo T24/2 |
| Cierre, `/caja/{id}/cerrar` | `caja/cierre.blade.php`, `caja-arqueo.js` | Efectivo inicial y esperado; contado/cambio preservados en cierre rechazado; diferencia y efectivo a entregar; campos de conteo/cambio cuando editables. | Textos y JS AR/2; campos T24/2 |
| Resumen, `/caja/{id}/resumen` | `caja/resumen.blade.php` | Declaraciones de arqueo y efectivo inicial anterior; total por medio; total por rubro; ingresos, egresos y neto. | Arqueo AR/2; desgloses y totales AR/0 |
| Detalle, `/caja/{id}/detalle` | `caja/detalle.blade.php` | Monto por movimiento; ingresos, egresos y neto del listado. | AR/0 |
| Historial, `/caja/historial` | `caja/historial.blade.php` | Ingresos y egresos del filtro; monto firmado por fila, incluyendo filas canceladas. | AR/0 |
| Buscar para cobrar, `/caja/cobrar` | `caja/cobrar-cuota.blade.php` | Saldo adeudado de cada alumno. T29 ya diferencia deudores de resultados encontrados por búsqueda. | AR/0 |
| Cobrar alumno, `/caja/cobrar/{id}` | `caja/cobrar.blade.php`, `cobrar.js` | Total pendiente; precio mensual del plan; cuota original y pagada en cobro parcial; saldo de inscripción; total seleccionado; desglose de inscripción/cuotas; cuota, adelanto e importe entregado dentro de campos. | Total pendiente/inscripción AR/2; precio/original/pagado AR/0; JS total e inscripción 0–2, cuotas 2; campos T24/2. Total inicial literal `$0` |
| Agregar/editar movimiento de caja | `caja/editar.blade.php` | Monto en campo compartido; el resto no muestra otros importes. | T24/2 |
| Nuevo movimiento de caja, `/caja/movimiento` | `caja/movimiento.blade.php` | Monto en campo compartido. | T24/2 |
| Cancelar cobro, `/caja/{cajaId}/movimientos/{movId}/cancelar` | `caja/cancelar-movimiento.blade.php` | Importe del cobro de cuota que se cancela; solo se llega con alumno asociado y caja abierta/rechazada. | AR/0 |
| Cashflow, `/cashflow` | `cashflow/index.blade.php` | Ingresos, egresos, balance y monto de cada fila. **Vista reservada a Gemini hasta publicar T28.** | AR/0 |
| Movimientos, `/movimientos` | `movimientos/index.blade.php` | Monto de cada movimiento, ingresos, egresos y neto del filtro. | AR/0 |
| Nuevo movimiento de Cashflow, `/cashflow/movimiento` | `cashflow/movimiento.blade.php` | Monto en campo compartido. | T24/2 |
| Cobranza, `/cobranza` | `cobranza/index.blade.php`, `CobranzaEstadoService` | Total adeudado del listado; deuda por alumno; deuda de otro deporte dentro del aviso de estado. | AR/2; el `number_format` de cero decimales restante corresponde al DNI, no a pesos |
| Grupos, `/grupos` | `grupos/index.blade.php` | Precios de todas las frecuencias del grupo dentro del resumen de planes. | AR/0 |
| Ficha de grupo, `/grupos/{id}` | `grupos/show.blade.php` | Precio mensual de cada frecuencia. | AR/0 |
| Grupo nuevo/editar | `grupos/_form.blade.php` y su template | Precio mensual de cada fila existente/nueva dentro del campo. | T24/2 |
| Profesores, `/profesores` | `profesores/index.blade.php` | Valor hora cuando la modalidad es HORA. Comisión es porcentaje y queda fuera. | Valor hora AR/0 |
| Ficha de profesor, `/profesores/{id}` | `profesores/show.blade.php` | Valor hora. | AR/2 |
| Profesor nuevo/editar | `profesores/_form.blade.php` | Valor hora dentro del campo. | T24/2 |
| Medio de pago nuevo/editar | `tipos-caja/_form.blade.php` | Saldo inicial, editable o protegido por historial. | T24/2 |
| Configuración, `/configuraciones` | `configuraciones/_campo.blade.php`, `ConfiguracionWebController` | Importe de inscripción dentro del campo; máximo permitido en el mensaje de validación (`$99.999.999,99`). Días/porcentajes excluidos. | Campo T24/2; mensaje AR/2 |
| Liquidaciones, `/liquidaciones` | `liquidaciones/index.blade.php` | Monto a pagar de cada liquidación. | AR/2 |
| Liquidación HORA/COMISIÓN, `/liquidaciones/{id}` | `liquidaciones/show.blade.php`, `LiquidacionWebController` | Monto a pagar; tarifa por hora; total calculado; subtotal por clase/alumno; cálculo original, ajuste y monto final; total al pie; saldo disponible por medio de pago; ayuda de monto ajustado; campo ajustado. | Tarifa y saldos AR/0; subtotales/totales AR/2; campo T24/2; ayuda literal `$0` |
| Primera carga, `/sistema/primera-carga` | `primera-carga/index.blade.php` | Precio del catálogo; montos de cuotas, inscripción y total del resumen; ejemplos explicativos de cuotas, pagos y saldos. | Catálogo/resumen y ejemplos en pesos enteros AR/0; los ejemplos de entrada de Excel son instrucciones del lector, no su formato de visualización |
| Reportes, `/reportes` | `reportes/index.blade.php`, `$dinero` | Ingresos, egresos, resultado; variaciones expresadas en pesos; evolución en Detalle; cambios por rubro; desgloses y movimientos; disponible confirmado/pendiente/total por cuenta; aportes, retiros y resultado global; por cobrar por estado y persona; por pagar por profesor; gastos del club. Valores fuente en centavos. | AR/0 en textos, tarjetas y desgloses; gráficos inventariados aparte y excluidos |
| Sueldos, `/reportes/sueldos` | `reportes/sueldos.blade.php`, `$plata` | Costo de profesores; cuotas atribuidas; costo por asistencia; desgloses por mes, deporte y profesor; monto sin asistencia. Valores fuente en centavos. | AR/2; gráficos excluidos |
| Alumnos, `/reportes/alumnos` | `reportes/alumnos.blade.php` | **No muestra importes en pesos.** Cuenta alumnos, clases/asistencias y porcentajes. | Los formatos 0/1 corresponden a cantidades/porcentajes. No convertirlos a dinero |

Los campos ya unificados por T24 se inventarían como presentación correcta existente;
no habilita volver a cambiar cómo se tipea, valida, recorta o envía un importe.

## Avisos, PDF, comunicaciones y Excel

| Lugar | Fuente real | Importes y formato de partida |
|---|---|---|
| Confirmación de deuda anterior al cobrar | `CajaWebController::pagar` | Suma de saldo pendiente anterior, AR/0, incluida en el mensaje JSON del modal |
| Inscripción al dar de alta/corregir | `AlumnoWebController` | Saldo, importe nuevo o importe conservado, AR/2 |
| Diferencia grande al abrir | `CajaService::abrir` | Efectivo heredado incluido en el error, AR/2 |
| Saldo insuficiente para liquidación | `LiquidacionWebController` | Saldo disponible, AR/0 |
| Límite de inscripción | `ConfiguracionWebController` | Importe máximo literal AR/2; no modificar el límite |
| Avisos de movimiento/cobro/pago | `CajaWebController`, `CashflowWebController`, `LiquidacionWebController` → `AvisoAdminService` → `AvisoOperativo` | Importe entregado ya convertido a texto AR/2. Mails y Telegram reutilizan ese mismo texto |
| Resumen diario del ADMIN | `AvisoAdminService::resumenDiario` | Total de cajas y liquidaciones cerradas sin pagar: AR/0 si redondo, AR/2 si tiene centavos. Mensaje común reutilizado por mail/Telegram |
| Mail por cobro con deuda anterior | `CobroConDeudaAnteriorNotification::toMail` | Monto anterior pendiente AR/0; no usa Telegram |
| Recibo de cuota nuevo/anulado | `pdfs/recibo-cuota.blade.php`, `ReciboService` | Imputación por período/cargo y monto total AR/2. El servicio entrega valores numéricos del pago y sus imputaciones |
| Recibo nuevo HORA/COMISIÓN | `pdfs/recibo-liquidacion.blade.php`, `ReciboService` | Cuota base, subtotal, total calculado, ajuste y total final AR/2; tarifa HORA dentro de «Por Hora» AR/0. Porcentajes y duración fuera |
| PDF ya emitido | `ReciboController`, `ReciboService` | Se reutiliza el archivo existente salvo regeneración explícita/anulación. **Conservar por decisión de Carlos**; T30 no regenera masivamente ni altera archivos emitidos |
| Descripciones de detalles de comisión | `LiquidacionService` | Texto guardado «Pago $…» AR/2. Es narrativa histórica, no cálculo; no reescribir datos ya guardados para cambiar separadores |
| Excel de primera carga, hoja Alumnos | `PrimeraCargaExcelService::plantilla` | Pares de monto en P/R/…/AL, numéricos con máscara `#,##0.00`; dos decimales. Excel adapta separadores a la configuración del lector |
| Excel de primera carga, hoja Catálogos | `PrimeraCargaExcelService::plantilla` | Precio mensual numérico, formato General; no hay máscara fija de dos decimales |
| Excel con errores para descargar | `PrimeraCargaWebController::informe`, `PrimeraCargaExcelService::guardarInforme` | Copia el archivo recibido y sus tipos/valores/estilos; agrega errores en AM. Los importes conservan la presentación del archivo original, no se vuelven a escribir |
| Padrón exportado por consola | `ExportarPadronCargaInicialCommand` | Exporta personas y pares vacíos para completar; columnas de monto con formato Texto, no muestra importes guardados. Ejemplos de la hoja Instrucciones explican el lector anterior |

No hay un Excel financiero generado por `ReporteWebController`: sus tres acciones
actuales devuelven las vistas mensual, alumnos y sueldos. El barrido de generación
de Spreadsheet/Xlsx en `app` encuentra los exportables de primera carga/padrón anteriores.
No crear un exportable nuevo por arrastre de esta tarea.

## Gráficos y formatos fuera del cambio

- `reportes.js`: importes de tooltip AR/0 y eje abreviado en miles enteros.
- `reportes-visual.js`: tooltip AR/0, eje abreviado en miles con hasta un decimal;
  los porcentajes del donut son otra unidad.
- `reportes-analiticos.js`: tooltip de cantidades y series monetarias con hasta
  un decimal; ejes de cantidades/pesos sin el símbolo monetario. **Los tres se
  conservan por la decisión final de Carlos de excluir gráficos.**
- DNI, porcentajes, comisión, duración, contadores, datos JSON de cálculo, importes
  canónicos de guardado/Excel y redondeos de servicios no son formato visual de pesos.
  No cambiar sus reglas por encontrar `number_format`, `round` o `toFixed`.

## Implementación y comprobaciones

`App\Support\FormatoImporte` decide separadores, moneda y dos decimales. PHP aplica
esa política a vistas, avisos, PDF nuevos y Excel. El navegador recibe los mismos datos
y usa `formato-importe.js`, también desde T24. El parser, los mínimos y las validaciones
de T24 se conservan. No se modificaron reglas ni cálculos de servicios financieros.
Las narrativas históricas de comisión se convierten al mostrarlas, sin reescribirlas.

Los gráficos quedan fuera: se conservaron sus tres scripts y el número central del
gráfico circular. Los textos de tarjetas, desgloses y detalles sí usan T30. Excel sigue
numérico; el informe de errores conserva el tipo, valor y máscara del original.
Los PDF existentes conservan sus bytes y ruta al recuperarlos desde caché.

### Pruebas permanentes

- Ocho métodos nuevos: tres de unidad y cinco funcionales. La matriz visita 39 tipos
  de pantalla con dos perfiles: `1250.50` y `1250000.50` (78 respuestas reales).
  Los campos que ya estaban correctos en T24 son controles de conservación.
- Se comparan datos financieros antes/después de los GET; se ejercitan mensajes por
  pedido directo, mail y Telegram generados con transportes falsos, Excel y caché PDF.
- Se suman renglones realmente renderizados de Cashflow, Caja detalle, Historial y
  Movimientos; y subtotales de liquidaciones HORA/COMISIÓN. El navegador suma cuota y
  adelantado seleccionados. T24 conserva su comportamiento: 1351 controles de navegador sin fallas, 100 PNG
  en `evidencia-t30/controles-t24/`, sin sobrescribir la evidencia aprobada anterior.
  Los controles previos de cálculo, deuda y permisos siguen
  presentes; sus expectativas monetarias se actualizaron, sin cambiar resultados.
- `tests/Browser/recibos-importes-t30.py` lee los seis PDF reales con pypdf: conserva
  centavos en cuota, tarifa, comisión y totales; suma las dos clases por hora.
- Unidad/formato/T24: 9 pruebas, 207 aserciones en la primera comprobación dirigida.
  La comprobación ampliada encontró dos pendientes (fecha del historial ficticio y
  expectativa anterior de ficha). Ambos se corrigieron: repetición 6/6, 171 aserciones.
- Suite completa final: **669 aprobadas, 2 omitidas, 6395 aserciones, 585,49 s** en
  `wings_testing_codex`: 671 pruebas. Los dos omitidos son exportadores visuales
  opcionales de Cashflow/ficha. No se omitió ninguna prueba financiera.
  Salida en `evidencia-t30/resultado-suite.txt` y JUnit en `resultado-suite.xml`.

La primera suite completa detectó expectativas de presentación anteriores. PHPUnit
terminó, pero el presentador de errores de Artisan quedó consumiendo CPU al imprimir
comparaciones HTML; se detuvo solamente ese presentador, con el proceso de PHPUnit ya
terminado. Esa corrida no tiene un resultado final certificado. La repetición dirigida
usó PHPUnit directo para obtener los errores sin ese presentador; la suite final usa
`php artisan test` y guarda también JUnit. No se cambiaron dependencias por ese problema.

### Evidencia anterior y límites de la regresión

El primer recorrido guardó 72 respuestas reales en `evidencia-t30/antes/`, antes de
conectar las vistas. Marcó 50 expectativas faltantes; cuatro correspondían a buscar
`1250.50` literalmente en JSON, que puede serializarlo como `1250.5`. Esas expectativas
se corrigieron y el precio de la opción se comprueba en Chrome. Las otras 46 comparaciones
muestran el formato anterior en textos monetarios. No presentar las 50 como 50 bugs.

Ese corte anterior no incluye Movimientos, Apertura, Primera carga, comunicaciones ni
PDF, que se agregaron después. Tampoco prueba el JavaScript original completo: parte
del código del navegador ya estaba conectado. Los campos T24 correctos no debían fallar.
La matriz final y la comprobación de PDF sí incluyen esos lugares; no inventar una
captura anterior para completar la comparación.

### Capturas y revisión visual

[Visor](evidencia-t30/visor.html), con filtros por pantalla, centavos/millones y tamaño.
Las rutas exactas de cada respuesta están en `evidencia-t30/pantallas.json`.
Cada caso tiene `capturas/<caso>-<perfil>-escritorio.png` y
`capturas/<caso>-<perfil>-marco-360.png`. Hay 156 imágenes de la matriz, cuatro de
Cobrar con adelantado y dos de login: 162 PNG. Las capturas provienen de respuestas
HTTP de Laravel autenticadas con datos ficticios; Chrome las carga con assets congelados.
No son maquetas. En celular se usa un iframe real de 360 px; el login controla el método.

Chrome: **563 controles y cero fallas en la tanda definitiva**, incluido el último
ajuste de Reportes. Comprueba errores JS, importes visibles, campos
recortados, centavos en otra línea, tarjetas y adelantos. Se revisaron las imágenes de
millones, además de los controles automáticos. No confundir contar PNG con mirarlos.

Los recortes detectados se guardaron antes de ajustar en `recortes-detectados/`:
Cobrar (campo de 110 a 114 px); Inicio/Sueldos (texto compacto); liquidación HORA
(texto y padding compacto); Reportes (sus tres tarjetas pequeñas). Se avisó a Carlos
antes de esos ajustes. No se reordenaron pantallas ni se ensancharon columnas. El tamaño de los porcentajes
del gráfico se conserva.
La primera lectura de un PDF reducido fue incorrecta: la ampliación prueba que la
comisión estaba completa. Se retiró la propuesta de alterar columnas del PDF.

- PDF reales: `pdf/recibo-{cuota,hora,comision}-{centavos,millones}.pdf`; diez páginas
  renderizadas en `pdf/render/`. Resultado automatizado en `resultado-pdf.txt`.
- Comunicaciones generadas: `comunicaciones/mail-deuda-anterior.html`,
  `mail-resumen.html`, `telegram-resumen.txt`, `aviso-deuda-anterior.json`.
- Excel ficticio y su informe: `excel/original-ficticio.xlsx`, `informe-ficticio.xlsx`.
- Comprobaciones: `resultado-servidor.json`, `resultado-navegador.json`, `resultado-pdf.txt`.

Build, sintaxis de 60 archivos y compilación/limpieza de vistas correctos. El guardián
de documentación pasó con las 671 pruebas reales.

### Lo que no se probó

No se usó un teléfono físico ni la instalación PWA. El marco verifica el ancho del
contenido en Chrome, no un dispositivo concreto. La autenticación se ejecuta en Laravel
mediante pruebas; no se recorrió un login interactivo con credenciales del club.
No se enviaron mails/Telegram reales ni se probó su recepción en clientes externos.
No se verificaron configuraciones regionales de Excel instalado; sí valores, tipos y
máscaras. No se regeneraron PDF emitidos ni se reescribieron descripciones antiguas.
No se cambió ni validó un nuevo formato de gráficos, porque Carlos los excluyó.
La matriz usa casos ficticios concretos: no cubre todas las combinaciones posibles de
anulación, pagos parciales, ajustes, fechas y permisos; permanecen sus pruebas previas.
No se visitó el sitio de prueba, no se tocó su reloj, no se desplegó.

### Coordinación y estado

T29 estaba publicado al iniciar. Para T28 Carlos reiteró: «T28 independiente primero;
T30 después». Se mantuvieron fuera los archivos mezclados mientras Gemini trabajaba.
Gemini publicó `e1e73d8`, se comprobó `git pull --ff-only` actualizado y `HEAD...origin/main`
sin diferencias. Después se aplicaron únicamente los cinco formatos de la vista de
Cashflow y el texto del aviso de su controlador. No se incluyen modelos, integración,
migración, pruebas ni capturas de Gemini. Su commit incorporó el conteo local de 671
pruebas que estaba pendiente; T30 actualizó ese corte con el resultado comprobado.

**T30 no se cierra.** Carlos aprueba las capturas; después otro agente verifica la regla.
Tablero: `a_verificar`, `hizo=Codex`, `tiene=Carlos`. La publicación incluye solo
archivos propios de T30, después de T28. Sin cierre ni despliegue.

## Revisión visual pedida por Carlos: porcentaje junto al rubro en celular, 12/10/2026

Carlos señaló el porcentaje desbordado en Origen de ingresos y pidió: «poner en celular,
el porcentaje junta al titulo. Cuota mensual». Se cambió solo la presentación de esa
leyenda: nombre y porcentaje arriba; importe completo abajo. En escritorio, porcentaje
junto al importe. Fórmulas, valores, colores y formato monetario se conservan.
El importe móvil usa 0,85 rem para entrar completo.

La comprobación anterior no medía los hijos de la leyenda; por eso sus 563 controles
no detectaron este desborde. El control permanente ahora comprueba sus límites reales,
la ubicación del porcentaje y espera las fuentes antes de medir. Reproducción anterior:
dos fallos a 360 (centavos y millones); escritorio correcto. Resultado en
`evidencia-t30/recortes-detectados/reportes-porcentajes-antes.txt`.

Revisión guardada aparte, conservando la evidencia inicial:
`evidencia-t30/revision-porcentajes-ultima/`. Matriz repetida: **563 controles, cero
fallas, 162 PNG** incluyendo login y adelantos. El visor principal usa esa última tanda.
Capturas de Reportes: `capturas/reportes-{centavos,millones}-{escritorio,marco-360}.png`
dentro de esa carpeta. Respuestas originales de Laravel en `html/`, datos ficticios
en la base propia. Los recibos abiertos no se sobrescribieron.

Suite completa de revisión: **669 aprobadas, 2 omitidas, 6395 aserciones, 530,97 s**
en `wings_testing_codex`. Salidas en `revision-porcentajes-ultima/resultado-suite.{txt,xml}`. No se añadieron métodos de
PHPUnit; el total sigue siendo 671. Sin teléfono físico, sin sitio de prueba ni reloj
y sin despliegue. T30 sigue a verificar: Carlos aprueba la pantalla y después otro
agente verifica la regla.

**Aprobación parcial de Carlos, 12/10:** «El resto de las vistas OK». Quedan aprobadas
las otras pantallas; solo falta aprobar la corrección de la leyenda de Reportes.
La regla sigue pendiente de otro agente. No repetir la aprobación de las demás vistas.
