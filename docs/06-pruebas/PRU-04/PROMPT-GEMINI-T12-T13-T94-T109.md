# PROMPT — T12 y T13: control del manual después de T94 a T109 — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos lo copia y se lo pasa.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T12 y T13 `en_curso, tiene=Gemini`.

## Qué pasó y para qué es este control

El 30/10 controlaste la etapa C del manual, con 0 hallazgos. Después entraron las tareas T94 a T109 y cambiaron reglas y pantallas. Claude puso el manual al día: **vos controlás ese trabajo, sin dar por cierto nada de lo que dice este prompt.** Carlos va a aprobar el manual después de este control, y Wings sale a producción con estos resultados: lo que el manual diga mal lo va a leer la gente del club.

Lo que hizo Claude (detalle en `docs/06-pruebas/PRU-04/IMPLEMENTACION-T12-T13.md` §15, «Puesta al día con T94 a T109»):

- 14 páginas corregidas: las de la galería `docs/06-pruebas/PRU-04/APROBAR-T12-T13-T94-T109.html`.
- 7 capturas rehechas del Wings real con `scripts/pruebas/capturar-manual-t94-t109.mjs`.
- 70 citas corridas en 26 fichas, con `scripts/manual/actualizar-citas-fichas.php`, y una sección nueva al final de 15 fichas.
- 45 citas que el script no pudo corregir, en `evidencia-t12-t13/citas-a-mano-t94-t109.txt`. Muchas son condiciones parafraseadas en las etapas A y B.

## Qué controlar

**1. Las tareas contra todo el manual, no solo contra lo que tocó Claude.** Punto de partida: `git diff d797d0b5..HEAD -- app resources/views resources/js lang routes config`. Leé el cuerpo de lo que cambió. Para cada tarea buscá en las 59 páginas lo que quedó falso o incompleto. Las que más importan:

| Tarea | Qué cambió | Qué mirar |
|---|---|---|
| T94 | Sacar una frecuencia: rechaza, elimina o desactiva | `administrar-grupos` |
| T95 | Tarifa del profesor obligatoria | `administrar-profesores`, `generar-liquidacion-profesor` |
| T96 | Un solo cajón en Efectivo, saldo inicial una sola vez | `configurar-mostrador`, `administrar-tipos-caja`, `abrir-caja`, `inicio-dueno` |
| T97 | Anular una transferencia | `transferir-entre-cuentas`, `corregir-movimiento-cashflow` |
| T98 | Texto al no poder eliminar | `eliminar-alumno` |
| T99 | Filas repetidas y avisos sin nombres internos | `administrar-grupos`, `modificar-alumno` |
| T100 | Cancelar un adelanto lo deshace | `cancelar-movimiento-caja`, `cobrar-cuota`, `cobrar-pago-parcial`, `corregir-movimiento-caja-validada` |
| T101 | El gasto del dueño en un turno queda a su nombre | `dinero-del-dueno-retiros-aportes`, `registrar-gasto-caja`, `consultar-movimientos`, `auditar-detalle-caja` |
| T102 | Inactivar un nivel y pasar un grupo a otro | `administrar-niveles`, `administrar-grupos` |
| T105 y T109 | Recibo liviano; filtros inválidos; recibo inexistente | Las páginas de recibos |
| T106 | Las transferencias no suman en los totales | `consultar-cashflow`, `consultar-historial-cajas` |
| T107 | Cerrar con la pantalla vieja | `cerrar-caja` |
| T108 | Segunda ficha sin inscripción | `alta-alumno`, `cobrar-cuota`, `primera-carga-*` |

**2. Lo que Claude cambió, palabra por palabra.** Seguí `docs/06-pruebas/PRU-04/REVISOR-MANUAL.md`, en especial el punto 6: cada condición de estado, rol o fecha, buscando vos el `if` completo y el `@if` de la vista. **Revisá en particular cuatro afirmaciones nuevas:**

- El dueño carga con **Agregar** lo que saca del cajón y la operativa no puede cargar un retiro.
- Anular un cobro del dueño: cuándo aparece el botón y qué pasa con el mes.
- La inscripción y los recargos no tienen casilla al cobrar.
- Un ajuste de saldo cambia los totales de Cashflow, pero no el resultado de Reportes.

Ejecutá esos cuatro caminos, no los leas solamente. Un camino que Wings no permite es grave.

**3. Las capturas.** Las 7 nuevas: que sean del rol de su página, que correspondan a su paso, y que el script solo marque con un contorno, sin agregar texto. Además, abrí las capturas de las demás páginas de la tabla de arriba y decí cuáles quedaron viejas. Ya se sabe que `corregir-movimiento-paso4.png` muestra un rubro que el catálogo no trae: espera una decisión de Carlos. No lo cuentes como hallazgo.

**4. El ? por rol.** Probá en el navegador, con cada rol, las pantallas que cambiaron con T96, T97 y T102: que abra la página de esa tarea. La ruta `/caja/configuracion` redirige a Caja una vez cargado el saldo: decí qué ve el dueño.

**5. Las 45 citas a mano.** Clasificá cada una: texto que el código arma por partes, aviso del sistema, cita corrida o afirmación sin respaldo. **Solo importa si alguna esconde un error de la página:** eso es hallazgo. Lo demás lo informás en una lista y no frena.

**6. La galería.** Comprobá que `APROBAR-T12-T13-T94-T109.html` muestre el texto actual y que abra las imágenes. Carlos aprueba con eso.

## Reglas

- Es un control: no corrijas páginas, capturas ni fichas. Lo que encuentres se informa con gravedad y `archivo:línea`.
- Separá lo comprobado de lo inferido. No declares «comprobado en navegador» lo que fue un pedido HTTP, ni «ejecutado» lo que leíste.
- Pruebas solo en `wings_testing_gemini`. Sin desplegar. No toques el sitio de prueba: Codex está recorriendo ahí los días 48 a 57.
- Comando: `php scripts/manual/verificar-manual.php`, más `tests/Feature/AyudaManualT12T13Test.php` y `tests/Feature/AyudaBotonPorRolTest.php`.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T12-T13-T94-T109-GEMINI.md`: una fila por tarea, lo comprobado y lo inferido por separado.
- Sin graves ni medios: T12 y T13 `a_verificar, tiene=Carlos`, paso «Manual al día con T94 a T109, controlado por Gemini: OK de Carlos con APROBAR-T12-T13-T94-T109.html». Con hallazgos: `devuelto, tiene=Claude`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
