# A10 — Verificación independiente de la lógica

08/10/2026 · Claude CyE, que no lo implementó. Hizo: Codex CyE. Orden: [VERIFICAR-A10.md](VERIFICAR-A10.md).
Sobre la carpeta de trabajo sin commitear (base `1e00ac9`), base `wings_testing_claude`. No se tocó código, vistas ni CSS.

## Veredicto: CERRADO, verificado Claude (segunda revisión)

La primera revisión lo devolvió por un defecto: Limpiar perdía el período. Codex corrigió ese
enlace y nada más. Repetido en el navegador, Limpiar conserva el período en los cuatro modos.
El resto de la lógica quedó comprobado en la primera revisión y el controlador no cambió.
El aspecto lo aprobó Carlos.

## Segunda revisión — corridas nuevas

**Fuente.** `resources/views/cashflow/index.blade.php:75` usa ahora `:href="route(...)"`.
`CashflowWebController.php` no se modificó desde las 10:00:54, antes de mi ensayo (10:50) y de
mi recorrido (10:56) de la primera revisión; releído, mismas reglas. SHA256 al cerrar:
`7e87564e…ac36657` el controlador, `ba783459…bb40f4` la vista.

**Limpiar en Chrome, pulsado con el mouse**, después de elegir Caja Alfa y Tipo Ingresos con
el teclado sobre una fecha lejana:

| Modo | Período antes | Período después | Fecha antes → después | Caja y Tipo después |
|---|---|---|---|---|
| Día | 29 de febrero de 2024 | 29 de febrero de 2024 | 2024-02-29 → 2024-02-29 | vacíos |
| Semana | Del 26/02/2024 al 03/03/2024 | Del 26/02/2024 al 03/03/2024 | 2024-02-29 → 2024-02-29 | vacíos |
| Mes | Febrero 2024 | Febrero 2024 | 2024-02-08 → 2024-02-08 | vacíos |
| Año | Año 2024 completo | Año 2024 completo | 2024-10-08 → 2024-10-08 | vacíos |

Después de Limpiar vuelven también las filas y los totales de antes del filtro, y el botón
desaparece. En Mes y Año la fecha es la de referencia oculta (día de hoy dentro del período).

**El enlace como lo entiende el navegador**, sin pasar por `Request::create`: el atributo
`href` leído en Chrome no contiene `amp;` y `new URL(href)` da exactamente `periodo`, `anio`,
`mes`, `fecha`, en los cuatro modos. La dirección a la que llega tampoco.

**Resto del recorrido**, repetido entero: 45 comprobaciones, 0 fallos
([registro](evidencia/a10-verificacion-claude/resultado-navegador.json) ·
[recorrido](evidencia/a10-verificacion-claude/recorrer-navegador.mjs)). Login por formulario,
nueve consultas con sus importes, Día → Semana → Mes → Febrero → Año con el teclado, campo
Fecha, página 2 pulsada (30 + 11 filas, mismos totales), Nuevo hasta «Nuevo movimiento»,
cuatro inválidos sin 500, cuatro modos y una selección en el marco de 375 sin desborde.

Capturas nuevas, 18, en [capturas/](evidencia/a10-verificacion-claude/capturas/). Antes y después de Limpiar:
[día](evidencia/a10-verificacion-claude/capturas/04-dia-con-filtros-antes-de-limpiar-escritorio.png) →
[día](evidencia/a10-verificacion-claude/capturas/05-dia-despues-de-limpiar-escritorio.png) ·
[semana](evidencia/a10-verificacion-claude/capturas/04-semana-con-filtros-antes-de-limpiar-escritorio.png) →
[semana](evidencia/a10-verificacion-claude/capturas/05-semana-despues-de-limpiar-escritorio.png) ·
[mes](evidencia/a10-verificacion-claude/capturas/04-mes-con-filtros-antes-de-limpiar-escritorio.png) →
[mes](evidencia/a10-verificacion-claude/capturas/05-mes-despues-de-limpiar-escritorio.png) ·
[año](evidencia/a10-verificacion-claude/capturas/04-anio-con-filtros-antes-de-limpiar-escritorio.png) →
[año](evidencia/a10-verificacion-claude/capturas/05-anio-despues-de-limpiar-escritorio.png).

**Pruebas HTTP, corrida nueva en mi base:** 19 aprobadas, 6.819 aserciones, 46,74 s.

- Las diez A10 del autor, incluida su regresión nueva de Limpiar: 10 aprobadas, 110 aserciones.
- Mi ensayo, con un control agregado sobre el `href` decodificado una sola vez: 9 aprobadas,
  6.709 aserciones ([código](evidencia/a10-verificacion-claude/VerificacionA10ClaudeTest.php) ·
  [resultados](evidencia/a10-verificacion-claude/resultado-http.json)).

No vi en rojo la regresión del autor: eso lo informa Codex. Lo que sí consta es mi propio
recorrido en rojo sobre la versión anterior, abajo.

## Primera revisión — antecedente negativo, conservado

A10 se devolvió porque al pulsar Limpiar el período volvía a hoy en los cuatro modos: el
enlace salía con el `&` escapado dos veces y solo llegaba `periodo`. Las pruebas HTTP no lo
veían porque `Request::create` decodifica la dirección.

Informe, registros y capturas de esa devolución, sin regenerar y con huellas SHA256:
[primera-revision/](evidencia/a10-verificacion-claude/primera-revision/LEEME.md).
Ahí están el [antes](evidencia/a10-verificacion-claude/primera-revision/capturas/04-semana-con-filtro-y-limpiar-escritorio.png)
y el [después con el período perdido](evidencia/a10-verificacion-claude/primera-revision/capturas/05-despues-de-limpiar-semana-escritorio.png).

## Lógica comprobada en la primera revisión y reutilizada

Vale porque el controlador es el mismo. El ensayo se volvió a correr hoy en verde (arriba);
lo esperado se calcula aparte con `DateTimeImmutable`, con el reloj fijado en un día 31.

| Qué | Cómo | Resultado |
|---|---|---|
| Intervalos | 118 movimientos en 32 fechas de borde; 48 períodos × 3 cajas × 3 tipos = 432 combinaciones, 442 pedidos | Modo, texto, ingresos, egresos, resultado en pantalla y filas de todas las páginas coinciden |
| Semana | Cuatro semanas pedidas desde cada uno de sus siete días; cruces de año y de febrero bisiesto a marzo | Siempre lunes–domingo |
| Mes y año | Febrero 2024 y 2025, abril, diciembre, enero; 2023 a 2025 | Primer y último día incluidos, vecinos fuera |
| Enlaces anteriores | `anio`, `anio`+`mes`, `mes` solo, sin parámetros | Mismo intervalo; sin parámetros, año actual |
| Filtros y paginación | Caja recorta filas y totales; Tipo solo filas; hasta 3 páginas | Sin filas repetidas ni faltantes |
| Signos | Devolución de cobro, reversión parcial de egreso, mes con solo devolución, semana negativa | 950 − 380 = 570; −48.000 − 2.000 = −50.000; 100 − 350 = −250 |
| Saldo inicial | Caja con $777.000 de saldo inicial | No aparece ni se suma en Cashflow; el saldo disponible sigue en 777.750 |
| Fecha conservada | Formulario real leído del HTML y reenviado campo por campo | 31/01 → semana → enero → febrero (29) → 2023 (28) → año → día |
| Inválidos | 25 valores rechazados y 20 raros tolerados | Ningún 500 |
| Permisos y alta | Anónimo, OPERATIVO y PROFESOR; ADMIN registra | Login, 403, 403; egreso guardado negativo; validaciones intactas |

En `app/` el único archivo cambiado es `CashflowWebController`, y solo `index`. Cashflow sigue
dentro de `ensure.admin.web`.

## Observaciones que no frenan

- **Reversión de egreso mayor que los egresos del período.** `abs()` la muestra como egreso y
  la resta: +500 y +70 dan 430 en vez de 570. No es alcanzable hoy: los tres servicios que
  escriben egresos fuerzan el negativo. Ya estaba antes de A10 y en `CashflowSaldoService`.
- **Fechas sin tope de año.** `fecha=9999-12-31` en Semana muestra «al 02/01/10000». No rompe.
- **`tipo_caja_id` no se valida.** Un valor que no es número se ignora; no rompe.

## Lo que no comprobé

- La suite completa: no la corrí en ninguna de las dos revisiones; la corre Codex.
- Abrir un desplegable con el mouse: usé flechas del teclado, y un evento de cambio en Año → Día y en el campo Fecha.
- Si el mensaje de un parámetro inválido se le muestra a la persona; solo que no hay 500.
- Un celular físico: fue el marco de 375. El servidor de ensayo quita `X-Frame-Options` para eso.
- El servidor real: nada de esto está desplegado.

Servidor de ensayo y Chrome detenidos. Sin commit, push ni despliegue.
