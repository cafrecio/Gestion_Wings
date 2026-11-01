# PROMPT — T107 y T108: controlar el cierre con pantalla vieja y la inscripción — para Gemini

> Prompt para Gemini, escrito por Claude CAB el 01/11/2026. Carlos se lo pasa cuando termine T105 y T109.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T107 y T108 `en_curso, tiene=Gemini`.

Claude las hizo en `a85d5c39` y no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt. No corrijas el producto: lo que encuentres se informa.

## T107

Se cierra la caja con una pantalla vieja: se veían $11.600, entraron $44.000 en otra pestaña y quedaba un faltante falso de $44.000 (día 46.7). Ahora la pantalla guarda el esperado en un campo oculto y `CajaService::cerrarCajaOperativa` rechaza si cambió.

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Por pantalla: abrir el cierre, cobrar en efectivo en otra pestaña, volver y cerrar | No cierra; «Cambió el efectivo esperado: ahora es $…»; la pantalla vuelve con el esperado al día |
| 2 | Cerrar de nuevo con lo contado de verdad | Cierra sin diferencia |
| 3 | Cancelar o corregir un movimiento en otra pestaña | También avisa |
| 4 | Movimiento en otra cuenta, que no cambia el efectivo | Cierra sin aviso |
| 5 | Faltante real con la pantalla al día | Cierra con la diferencia |
| 6 | Doble clic | El segundo envío: «La caja no está en estado editable.» |
| 7 | El dueño cerrando el turno de una operativa; una caja rechazada que se vuelve a cerrar | Decí qué pasa y si está bien |
| 8 | Pedido directo sin `esperado_visto`, o con un valor inválido | Sin el dato cierra como antes; inválido se rechaza. Decí si esto te parece un riesgo |

Mirá también: dos conexiones reales (cobro y cierre a la vez), y el arqueo en Cashflow al validar, al peso.

## T108

**Decisión de Carlos:** quien ya tiene una ficha en el club no vuelve a pagar inscripción, aunque no tenga una registrada. Thiago vino de la primera carga con «Inscripción: No» y en su segunda ficha le cobraba $8.000 (día 46.4). Cambios: `InscripcionService::yaEsDelClub` y `AlumnoWebController` (`store` e `inscripcionPreview`).

| # | Caso | Resultado esperado |
|---|---|---|
| 9 | Segunda ficha de quien no tiene inscripción, por pantalla | No se crea inscripción; el aviso previo dice «Esta persona ya es del club: no paga inscripción.» |
| 10 | Persona nueva | Paga, como siempre |
| 11 | Quien ya tiene inscripción, pendiente o cobrada | Sigue con una sola |
| 12 | DNI con puntos o espacios; ficha inactiva | Cuenta como la misma persona |
| 13 | Se eliminó su única ficha y se la vuelve a dar de alta | Persona nueva: paga |
| 14 | Primera carga por Excel con «Inscripción: Sí», y con «No» | La primera la crea y la segunda no, también con dos filas del mismo DNI |
| 15 | Cobranza, ficha, deuda total y Reportes de esa segunda ficha | Solo la cuota, sin inscripción, al peso |

## También controlar

- Manual `operativo/cerrar-caja` y `operativo/alta-alumno`, condición por condición (`docs/06-pruebas/PRU-04/REVISOR-MANUAL.md` punto 6), los contratos V5 y `alumno-grupo-deporte-deuda-v5`, y los casos CAJ-023 y ALU-005.
- `CierrePantallaViejaT107Test` e `InscripcionSegundaFichaT108Test`: tienen que fallar si se saca cada regla. Claude no hizo mutaciones. Hacelas **en una copia**, no en la carpeta compartida.
- Suite rápida en `wings_testing_gemini`, sin otra suite corriendo.

**«Comprobado» es solo lo que ejecutaste.** Lo que leíste o dedujiste va en «Inferido». Una prueba de concurrencia tiene que usar dos conexiones reales.

## Reglas

- Pruebas solo en `wings_testing_gemini`. No uses la base local de trabajo. Sin despliegue: Cora trabaja en el sitio de prueba.
- La tabla de pruebas no se toca (`AGENTS.md` §5-ter): proponé los cambios en el informe.

## Cómo entregar

- Informe en `docs/06-pruebas/PRU-04/VERIFICACION-T107-T108-GEMINI.md`: una fila por caso, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T107 estado=cerrado verifica=Gemini tiene=nadie`, y lo mismo con T108, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`.
- Entrada en `docs/00-estado/LOG-GEMINI.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo y su `git diff`, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
