# PROMPT — Control por etapas: T101 y T105 — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Va después de entregar la segunda vuelta de T94.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado.

**Por etapas, en este orden:** 1) T101, 2) T105. Cada etapa termina con su informe, su tablero y su commit antes de empezar la siguiente. Una etapa devuelta no frena la otra.

**Ejecutalo, no alcanza con leer:** pantalla y pedidos directos, en una base tuya, con las cifras al peso. Lo que no ejecutaste se declara como leído, no como comprobado.

# Etapa 1: T101

Marcá T101 `en_curso, tiene=Gemini`. Si el dueño agrega un gasto en el turno de una operativa (**Agregar**, `editarStore`), el gasto entra en esa caja pero figura a nombre de quien lo cargó. Antes quedaba siempre a nombre de la operativa (día 45.8, caso CAJ-041 d). Cambios: `CajaService::registrarMovimientoEnCaja`, `CajaWebController::editarStore` y `tests/Feature/GastoDuenoEnTurnoT101Test.php`. Claude la hizo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Dueño agrega un gasto en el turno de la operativa | Figura a su nombre en Movimientos, también para la operativa; el efectivo esperado baja por el importe |
| 2 | Operativa agrega en su propio turno, por **Agregar** y por movimiento de caja | Sigue a su nombre |
| 3 | Otra operativa o la profesora, y un pedido directo con otro `usuario_id` | Rechazado, o el autor no cambia |
| 4 | Validar esa caja | En Cashflow, «Registró» dice quién lo cargó (`CashflowIntegracionCajaService:81`) y el saldo da al peso |
| 5 | Editar, cancelar o corregir ese gasto en la caja; recibo y resumen de cierre | Funciona igual; decí qué pasa con el autor |

**Prestá atención a esto, que Claude no comprobó por pantalla:** cualquier lugar que dé por hecho que el autor de un movimiento es la operativa de su caja, como `CajaResumenService`, `CierreDiaResumenService` y `CashflowWebController`. El gasto ya cargado en el sitio de prueba (45.8) sigue a nombre de Sandra: no se corrige el dato, y por eso el control CAJ-6 sigue valiendo.

# Etapa 2: T105

Marcá T105 `en_curso, tiene=Gemini`. Claude la hizo: el control es tuyo. El recibo pesaba 1,7 MB porque llevaba las fuentes enteras; con el recorte, unos 225 KB.

`config/dompdf.php` activa `enable_font_subsetting`. Generá recibos reales de cuota, cuota anulada y liquidación por hora y por comisión, con acentos, ñ y los símbolos que usan, y compará contra la versión sin recorte: que se lean igual y que no falte ninguna letra. Medí el tiempo en la misma máquina. **Medí también cuánto tarda registrar un cobro con el PDF real:** el código lo arma después de guardar el cobro (`PagoCuotaService.php:172`) y la prueba técnica midió 72 ms; las dos cosas no cierran.

## Reglas

- Pruebas solo en `wings_testing_gemini`. **No uses la base local de trabajo.** Sin despliegue: Cora trabaja en el sitio de prueba.
- No corregir el producto. Lo que encuentres se informa.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los cambios en el informe.
- Suite rápida (`--exclude-group=lento`) al final, una sola vez, y nunca con otra suite corriendo en la misma máquina. El informe lleva sus números.

## Cómo entregar

- Un informe por etapa, `docs/06-pruebas/PRU-04/VERIFICACION-T101-GEMINI.md` y `VERIFICACION-T105-GEMINI.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Por cada tarea, sin graves ni medios: `php scripts/tablero/tablero.php cambiar T101 estado=cerrado verifica=Gemini tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
