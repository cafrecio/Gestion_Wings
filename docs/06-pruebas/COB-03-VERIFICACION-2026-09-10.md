# COB-03 — verificada en cob-total, 10/09/2026

## Resultado definitivo de la repeticion

**Codex CyE. VERIFICADA en `cob-total`, commit
`52388256458a4bd0f0b138320a54b18169f18697`.** No mergeada ni desplegada por Codex.
La verificacion anterior, conservada abajo, corresponde a `02a5b5b` y su freno
queda levantado para esta nueva version.

Tres alumnos sinteticos separados, misma fecha 10/09, sin pagos previos. Casos 1
y 2: alta 20/08, regla unica 70%, agosto impago. Caso 1 con septiembre existente;
caso 2 sin septiembre (confirmado ausente en SQL antes del cobro), ofrecido por
pantalla como fila virtual. Caso 3: alta 01/08, sin regla aplicable, ambos meses.

| Control | Caso 1: septiembre existente | Caso 2: septiembre virtual | Caso 3: sin descuento |
|---|---|---|---|
| Cartel 70% | Visible | Visible | Ausente |
| Campos enviados agosto / septiembre | 28.000 / 10.000 | 28.000 / 10.000 | 28.000 / 10.000 |
| Total antes de confirmar | 29.600 | 29.600 | 38.000 |
| Agosto original y pagado | 19.600, PAGADA | 19.600, PAGADA | 28.000, PAGADA |
| Septiembre original | 28.000 | 28.000 (nueva) | 28.000 |
| Septiembre pagado / saldo | 10.000 / 18.000 | 10.000 / 18.000 | 10.000 / 18.000 |
| Septiembre estado | PENDIENTE | PENDIENTE | PENDIENTE |
| Pago / movimiento / total PDF | 29.600 | 29.600 | 38.000 |
| Imputaciones agosto + septiembre | 19.600 + 10.000 | 19.600 + 10.000 | 28.000 + 10.000 |

Chrome: login OPERATIVO, seleccion y boton Cobrar; captura del request multipart
real, sin fabricar payload. Los campos enteros son deliberados: solo el total
anticipa el descuento que aplica el servidor. Cada cobro devolvio HTTP 302 a caja.
Resumen por Efectivo Prueba y rubro Cuotas: **97.200**, egresos 0, tres movimientos.
Detalle muestra 29.600 / 29.600 / 38.000. Los tres recibos obtenidos desde sus
enlaces HTTP 200 se renderizaron y revisaron visualmente: alumno, agosto/septiembre,
imputaciones, total y medio correctos.

Comparacion real con main `3a8b856`: ambos casos con descuento fueron cobrados
tambien por Chrome. Septiembre queda 10.000 original, 10.000 pagado y PAGADA en
ambos; desaparecen los 18.000. El total visible anterior sigue en 38.000.

Suite de cob-total: **133 pruebas, 726 aserciones, verde, 50.02 s**, en base nueva
`wings_cob03_20260910_suite`; se cambio solo DB_DATABASE en el phpunit.xml de la
copia descartable. Sintaxis de controlador y servicio correcta; view:cache y
view:clear correctos. Diff de vistas/CSS del checkout compartido vacio.
No se modificaron archivos funcionales ni la rama de Claude. No se considera
verificado COB-02 por estar incluido: este recorrido no cambia de plan.

Base de navegador: `wings_cob03_20260910_total`; la comparacion usa
`wings_cob03_20260910_main`. Datos reales intactos. Capturas y PDF sinteticos
conservados fuera de Git en storage/app/cob03-20260910. No se ejecuto la corrida
mensual: se preparo directamente el estado sin deuda que requiere el caso 2.

## Antecedente: freno sobre cob-03 antes de la correccion del total

**Codex CyE. No verificada de punta a punta.** Rama corregida examinada:
`cob-03` en `02a5b5b41f856ba9c2f5c95e526596cf5fc2ef2f`. Copia de main preparada
en `3a8b856`, sin ejecutar aun el cobro de comparacion. No hubo checkout ni merge.

## Escenario autorizado

Fecha 10/09/2026, OPERATIVO, alta 20/08, plan 28.000, regla unica del 70%.
Agosto impago precargado. Caso 1: septiembre existente por 28.000. Caso 2 preparado
sin septiembre, para ofrecerlo como fila virtual del mes actual.
Bases nuevas `wings_cob03_20260910_main` y `wings_cob03_20260910_fix`, solo datos
sinteticos; ninguna conexion a gestion_wings ni a produccion.

## Primer caso observado en cob-03

Chrome real automatizado: login, ambos periodos seleccionados, agosto 28.000,
septiembre 10.000, medio Efectivo Prueba y boton Cobrar. CDP Network capturo
literalmente `montos_cuota[2026-08]=28.000` y `montos_cuota[2026-09]=10.000`.

| Control | Resultado |
|---|---|
| Total anunciado antes de confirmar | **$38.000** |
| Respuesta | HTTP 302 hacia caja |
| Caja despues del cobro | **$29.600**, un movimiento |
| Agosto | Original 19600.00, pagado 19600.00, PAGADA |
| Septiembre | Original 28000.00, pagado 10000.00, PENDIENTE; diferencia 18000.00 |
| Pago | monto_final 29600.00 |
| Imputaciones | Agosto 19600.00, septiembre 10000.00 |
| Movimiento | 29600.00 |

Los importes persistidos del caso 1 coinciden con lo pedido, pero la pantalla
anterior al cobro anuncia otro importe. No se afirma que hayan ingresado $38.000
reales: se verifico lo que indica el formulario y lo que registra el sistema.

## Causa leida y limite de la verificacion

`CajaWebController::cobrar()` solo muestra la regla de primer pago si mes de alta
y mes actual coinciden. En septiembre, para alta de agosto, no muestra ese aviso.
`calcularTotal()` en la vista suma los valores ingresados sin aplicar descuento.
El servicio si aplica el 70% al mes de alta incluido en el cobro.

Freno §6b al observar la diferencia. No se tocaron vistas ni logica funcional.
No se continuo con caso 2, comparacion de cobros en main, resumen por medio ni PDF;
no se ejecuto la suite. La correccion de saldos no queda rechazada por esta evidencia,
pero la cadena completa tampoco queda aprobada.

Decision pendiente: separar la diferencia visual como otra tarea y terminar la
verificacion financiera, o autorizar expresamente su correccion visual antes del
cierre. No inventar un alcance ni modificar la rama que Claude conserva fija.

Entornos sinteticos conservados en storage/app/cob03-20260910 (ignorados por Git).
Servidor de prueba detenido al frenar. No hubo despliegue ni publicacion.
