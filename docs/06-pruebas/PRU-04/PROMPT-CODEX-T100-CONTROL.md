# PROMPT — T100 y T101: controlar el adelanto cancelado y el autor del gasto — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa a Codex cuando termine T97.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T100 `en_curso, tiene=Codex`.

## Qué pasó

Clara paga diciembre por adelantado y escriben $300.000 por un cero de más. Se cancela y se cobran los $30.000 correctos. Wings dejaba diciembre valiendo $300.000: los $30.000 entraban como cobro parcial y quedaban $270.000 de saldo, que el 1/12 pasaban a deuda (día 42.1 de la prueba, `docs/06-pruebas/PRU-04/DIA-42.md`).

**Decisión de Carlos, 01/11/2026, reemplaza CJ-26:** cancelar o anular un adelanto deja todo como si ese cobro no hubiera existido.

Claude lo hizo y no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt. No hay cambios de pantalla.

## Qué se cambió

- Migración `2026_11_01_130000_add_pago_origen_to_deuda_cuotas.php`: la cuota recuerda el cobro del que nació. Las cuotas anteriores quedan sin dato y siguen como estaban.
- `app/Services/PagoCuotaService.php`: marca la cuota al crearse en `relacionarPagoConDeudas`, y `deshacerCuotaNacidaDeCobro` corre en `cancelarCobroOperativo` y en `anularCobroAdmin`.
- `tests/Feature/AdelantoCanceladoT100Test.php`, y la prueba técnica `prueba-tecnica/SeguridadDiasPruebaTest.php`, que retrataba el defecto.
- Contratos `Wings-contrato-cuotas-deudas-pagos-V2.md` §3.b y `Wings-contrato-alumno-grupo-deporte-deuda-v5.md`, manual `operativo/cancelar-movimiento-caja`, caso COB-019.

## Qué controlar

Ejecutalo, no alcanza con leer: pantalla y pedidos directos, en una base tuya, con las cifras al peso.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Operativa: adelanto de $300.000, cancelar, cobrar $30.000 | Diciembre vale $30.000 y queda pago; no pide motivo de cobro parcial; sin saldo |
| 2 | Dueño: lo mismo, anulando desde la ficha | Igual, y Cashflow queda en cero para el cobro anulado |
| 3 | Cancelar y no volver a cobrar; llega el mes, con asistencia el mes anterior | Cuota a precio del plan |
| 4 | Lo mismo, sin asistencia | Va a Revisión y no debe nada |
| 5 | Cancelar con el mes ya empezado, con una caja rechazada | No se borra; queda pendiente al precio del plan |
| 6 | Cobro de varios meses: uno debido y uno adelantado | Solo se borra el adelantado; el debido vuelve a pendiente |
| 7 | Cuota futura que ya existía antes del cobro | Sigue igual, con su importe |
| 8 | Dos cobros sobre la misma cuota, anulando uno y después el otro | Recién se deshace al anular el último |
| 9 | Cuota con recargo o liquidación atados, o con una parte dada por perdida | No se borra y no da error |
| 10 | Adelanto del mes de una primera cuota con descuento | Decí qué pasa y si está bien |
| 11 | Dos adelantos seguidos (noviembre y diciembre), cancelando noviembre | Decí qué pasa con el orden de meses y con diciembre |

**Prestá atención a esto, que Claude no comprobó:**

- Efectivo esperado de la caja, Cashflow, Inicio, Cobranza, Reportes, ingresos por período (T42) y comisión de ese mes (T43), antes y después de cancelar. El borrado se lleva en cascada los eventos de `reporte_eventos` de esa cuota: comprobá que ningún cierre o reporte ya mirado cambie cuando no debe.
- El recibo anulado y la ficha del alumno, con la cuota ya borrada.
- Cualquier otra lectura que dé por hecho que una cuota de un cobro sigue existiendo. Buscá también si hay otro camino que revierta un cobro y no pase por estas dos funciones.
- Dos pedidos a la vez: cancelar y cobrar el mismo mes con dos conexiones reales.
- Una base en uso, como el sitio de prueba: la migración sobre datos reales y un adelanto cobrado antes de T100.
- El texto del panel de cancelar, «La deuda del alumno volverá a estado pendiente.» (`caja/detalle.blade.php:306`), ya no es cierto para un adelanto. Claude no lo cambió porque es una vista; proponé el texto en el informe.

**También controlar:**

- El manual `cancelar-movimiento-caja` y los contratos contra el código, condición por condición (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6), y `php scripts/manual/verificar-manual.php`.
- Las pruebas nuevas tienen que fallar si se saca cada regla. Probalo con mutaciones y dejá el código como estaba.
- Suite completa en `wings_testing_codex`, porque toca plata compartida.

## T101: en el mismo control

Marcá también T101 `en_curso, tiene=Codex`. Si el dueño agrega un gasto en el turno de una operativa (**Agregar**, `editarStore`), el gasto entra en esa caja pero figura a nombre de quien lo cargó. Antes quedaba siempre a nombre de la operativa (día 45.8, caso CAJ-041 d). Cambios: `CajaService::registrarMovimientoEnCaja`, `CajaWebController::editarStore` y `tests/Feature/GastoDuenoEnTurnoT101Test.php`. Claude la hizo: el control es tuyo, con las mismas reglas.

| # | Caso | Resultado esperado |
|---|---|---|
| 12 | Dueño agrega un gasto en el turno de la operativa | Figura a su nombre en Movimientos, también para la operativa; el efectivo esperado baja por el importe |
| 13 | Operativa agrega en su propio turno, por **Agregar** y por movimiento de caja | Sigue a su nombre |
| 14 | Otra operativa o la profesora, y un pedido directo con otro `usuario_id` | Rechazado, o el autor no cambia |
| 15 | Validar esa caja | En Cashflow, «Registró» dice quién lo cargó (`CashflowIntegracionCajaService:81`) y el saldo da al peso |
| 16 | Editar, cancelar o corregir ese gasto en la caja; recibo y resumen de cierre | Funciona igual; decí qué pasa con el autor |

**Prestá atención a esto, que Claude no comprobó por pantalla:** cualquier lugar que dé por hecho que el autor de un movimiento es la operativa de su caja, como `CajaResumenService`, `CierreDiaResumenService` y `CashflowWebController`. El gasto ya cargado en el sitio de prueba (45.8) sigue a nombre de Sandra: no se corrige el dato, y por eso el control CAJ-6 sigue valiendo.

El informe y el tablero son los mismos para las dos: una fila por caso y `cambiar T101` con su propio resultado.

## Reglas

- Pruebas solo en `wings_testing_codex`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- No corregir el producto. Lo que encuentres se informa.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los cambios en el informe.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T100-CODEX.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T100 estado=cerrado verifica=Codex tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
