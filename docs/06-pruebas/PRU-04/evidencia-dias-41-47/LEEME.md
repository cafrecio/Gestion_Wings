# Tanda 4 — días 41 a 47

Recorrido de Codex CyE, 30/10/2026, en **test.gestionar-te.com.ar**, con datos ficticios. Se entrega a Claude para revisión independiente; no cierra T14 ni los casos del catálogo.

| Día | Informe | Deuda | Ingresos noviembre | Egresos noviembre | Disponible |
|---|---|---:|---:|---:|---:|
| 41 · 19/11 | [Día 41](../DIA-41.md) | $205.200 | $4.179.300 | $2.748.580 | $6.468.620,75 |
| 42 · 20/11 | [Día 42](../DIA-42.md) | $205.200 | $4.191.300 | $2.788.980 | $6.573.220,75 |
| 43 · 21/11 | [Día 43](../DIA-43.md) | $205.200 | $4.191.300 | $2.788.980 | $6.573.220,75 |
| 44 · 22/11 | [Día 44](../DIA-44.md) | $205.200 | $4.191.300 | $2.788.980 | $6.563.820,75 |
| 45 · 23/11 | [Día 45](../DIA-45.md) | $205.200 | $4.244.100 | $2.810.080 | $6.669.520,75 |
| 46 · 24/11 | [Día 46](../DIA-46.md) | $227.510 | $4.281.100 | $2.824.980 | $6.753.620,75 |
| 47 · 25/11 | [Día 47](../DIA-47.md) | $215.910 | $4.327.900 | $2.833.880 | $7.441.520,75 |

Los siete cierres monetarios coinciden al peso. Final: **114 activos, 107 Al día / 2 En plazo / 5 Morosos / 0 Deudores; Sin asistencia 0, cajas por validar 0 y ninguna abierta**. El sábado quedó una caja sin validar hasta el domingo.

## Diferencias conservadas

- **41.2 / T99:** dos frecuencias iguales producen la página «Algo falló de nuestro lado». Luego se creó el grupo correcto.
- **42.1 / T100:** Clara queda con diciembre original $300.000, pagado $30.000 y saldo **$270.000**. No se corrigió. No integra la deuda de noviembre.
- **45.8 / T101:** el gasto registrado por el dueño en el turno figura como **Sandra Vidal** en Movimientos.
- **46.7:** cierre viejo aceptado: esperado $55.600, contado $11.600, diferencia **−$44.000**. Dueño la vio y la rutina validó conservando el faltante.
- **44.1:** Historial del 16 al 21 incluye Internet y su anulación. Muestra ingresos $1.921.200 y egresos $1.565.200. Cashflow de la semana muestra los mismos importes y resultado $356.000. Difiere de los importes brutos y del alcance de Historial previstos por el guion; se conserva sin investigar ni reparar.
- **45.10:** Inicio muestra Sin asistencia **0** durante la mañana. La clase de Fútbol de las 16 seguía Pendiente y sin lista; Hernán la cargó el 46. El prompt indicó no fijar las horas del relato.
- Los frenos nativos y el recorte del tercer decimal conservan los textos efectivamente observados. No se presentan los textos del guion como si fueran mensajes vistos.

## Límites que debe revisar Claude

- **375 px no acreditado:** la capacidad de viewport aceptó el pedido pero el DOM siguió en 1905 px, incluso con recarga y pestaña nueva. Se restableció el tamaño. Mariela y Hernán operaron sus listas por pantalla de escritorio. El archivo `41.9-mariela-375-antes` conserva un intento fallido del método, no prueba teléfono.
- **47.4:** diez intentos documentados, una descarga acreditada; las repeticiones no devolvieron el evento de descarga. No se certifican diez aperturas ni escrituras internas. [Registro](47.4-diez-recibos.json).
- **46.6:** la pestaña que debía quedar con otra edición se abrió en listado. Se probó el reenvío del formulario ya usado, que rechaza con otro mensaje; el conflicto entre dos ediciones independientes queda sin probar.
- **46.2:** un solo pago y formulario sin deuda tras el segundo intento, pero no se conservó el aviso exacto esperado.
- **41.6:** Sandra descargó el recibo de Dante; los intentos de Lucía abortaron en el navegador, sin rechazo acreditado.
- Los informes separan otros avisos breves, accesos sin botón y controles internos que no quedaron observados. Lo marcado Solo prueba técnica no se forzó.

## Recargo, recibos y evidencia

El 46 se esperó más de tres minutos reales desde las 06:13. La pantalla confirmó el recargo de Emma antes de fijar las 09:00, sin ejecutarlo manualmente. [Espera](46.0-espera.txt) · [Cobranza](46.0-recargo-automatico-confirmado.txt).

Quince adelantos: EFE $294.000, MP $208.000, BGA $102.000; precios sugeridos conservados. Quince recibos 404–418 guardados. Sol y Agustín se descargaron desde ficha tras una demora, sin repetir pagos. [Detalle](47.9-cobros.json).

264 capturas originales JPEG con transcripciones; 18 PDF descargados del sitio. Los 18 se abren y tienen una página. Inspección visual de los renderizados de Dante, Tobías anulado y Helena: textos y montos legibles. Tobías conserva noviembre $11.200 + inscripción $8.000, total revertido $19.200 y motivo. Las imágenes PNG del PDF son renderizados, no capturas nuevas del navegador.

[Control de integridad y enlaces](control-entrega.json) · [Manifiesto SHA256](MANIFIESTO-SHA256.csv). El manifiesto corresponde a los bytes versionados por Git (textos normalizados a LF) y excluye su propio archivo. Los TXT conservan espacios literales de la consola y del navegador; los avisos de espacios finales en esas salidas son esperados. Los nombres anteriores a `*-final` conservan también estados transitorios: prevalece la evidencia final citada por cada informe. No se borraron capturas fallidas ni se reescribieron como éxitos.

## Continuidad

**Queda el 25/11 simulado**, con cambio de $10.000. No repetir estas operaciones, generar las cuotas del 1/12 ni reparar a Clara. Diciembre tiene las series creadas por rutina y las cuatro Infantiles y diez Adultos por pantalla. Lautaro permanece Inactivo. Sigue revisión independiente de Claude antes de la tanda 5.

Consola remota usada exclusivamente para reloj y rutinas, con `/usr/bin/php82`. Sin consultas directas a base, despliegues, cambios de aplicación, vistas/CSS, catálogo o tablero. Sin suite por esta entrega documental.
