# A10 — Verificación independiente de la lógica

08/10/2026 · Claude CyE, que no lo implementó. Hizo: Codex CyE. Orden: [VERIFICAR-A10.md](VERIFICAR-A10.md).
Sobre la carpeta de trabajo sin commitear (base `1e00ac9`), base `wings_testing_claude`. No se tocó código, vistas ni CSS.

## Veredicto: DEVUELTO a Codex

Los intervalos, las validaciones, los signos, la separación del saldo inicial, la paginación,
los permisos y el alta están bien y quedaron comprobados. **Falla una cosa: el botón Limpiar
pierde el período.** La entrega dice «Limpiar conserva el período y elimina caja/tipo»; en un
navegador no lo conserva.

## El defecto: Limpiar vuelve a hoy

**Reproducción** (ADMIN, cualquier navegador):

1. Abrir `/cashflow?periodo=semana&fecha=2024-02-29`. Dice «Del 26/02/2024 al 03/03/2024».
2. Elegir Tipo = Egresos. Aparece Limpiar; el período sigue igual.
3. Pulsar Limpiar. **Dice «Del 05/10/2026 al 11/10/2026»**: la semana de hoy.

Pasa en los cuatro modos, comprobado en Chrome pulsando el botón con el mouse:

| Modo | Antes de Limpiar | Después de Limpiar |
|---|---|---|
| Día | 29 de febrero de 2024 | 8 de octubre de 2026 |
| Semana | Del 26/02/2024 al 03/03/2024 | Del 05/10/2026 al 11/10/2026 |
| Mes | Febrero 2024 | Octubre 2026 |
| Año | Año 2024 completo | Año 2026 completo |

[Antes](evidencia/a10-verificacion-claude/capturas/04-semana-con-filtro-y-limpiar-escritorio.png) ·
[después](evidencia/a10-verificacion-claude/capturas/05-despues-de-limpiar-semana-escritorio.png) ·
[registro del recorrido](evidencia/a10-verificacion-claude/resultado-navegador.json) (los cuatro «MAL» son este defecto).

**Causa, leída en el HTML que devuelve la aplicación.** `cashflow/index.blade.php:75` pasa la
dirección a `<x-ds.button href="{{ route(...) }}">`. Blade la escapa al recibirla y el
componente la vuelve a escapar al escribirla: sale `periodo=semana&amp;amp;anio=2024&amp;amp;mes=2&amp;amp;fecha=2024-02-29`.
El navegador pide `?periodo=semana&amp;anio=2024&amp;mes=2&amp;fecha=…` y PHP recibe `amp;anio`,
`amp;mes` y `amp;fecha`: solo `periodo` llega bien, y la fecha cae en hoy. El mismo botón con
una dirección de un solo parámetro no lo sufre; en `resources/views` es el único con varios.

**Por qué no lo vieron las pruebas.** Las pruebas HTTP de Laravel pasan la dirección por
`Symfony\Request::create`, que decodifica entidades (`html_entity_decode`, `Request.php:439`).
La prueba recibe la fecha bien; el navegador no. Mi propio ensayo HTTP también pasó en verde
este punto: lo delató el texto del enlace y lo confirmó Chrome. El recorrido del autor no
pulsaba Limpiar.

**Comprobado, sin aplicar:** el mismo componente con `:href="$url"` escribe un solo `&amp;`.
Cómo arreglarlo lo decide Codex; toca una vista con diseño aprobado, sin cambio visible.
Al corregir conviene una comprobación que no pase por `Request::create` (mirar el texto del
`href`, o el navegador).

## Lo que sí quedó comprobado

**Ensayo HTTP propio**, fuera de la suite: 9 pruebas, 6.697 aserciones, 64,96 s
([código](evidencia/a10-verificacion-claude/VerificacionA10ClaudeTest.php) ·
[resultados](evidencia/a10-verificacion-claude/resultado-http.json)). Lo esperado se calcula
aparte con `DateTimeImmutable`, sin el controlador ni `startOfWeek`; reloj fijado en un día 31.

| Qué | Cómo | Resultado |
|---|---|---|
| Intervalos | 118 movimientos en 32 fechas de borde; 48 períodos × 3 cajas × 3 tipos = 432 combinaciones, 442 pedidos | Modo, texto, ingresos, egresos, resultado en pantalla y filas de todas las páginas coinciden |
| Día | 31/12 y 01/01, 29/02, vecinos | Solo ese día |
| Semana | Cuatro semanas pedidas desde cada uno de sus siete días; cruces de año y de febrero bisiesto a marzo | Siempre lunes–domingo, misma respuesta los siete días |
| Mes y año | Febrero 2024 y 2025, abril, diciembre, enero; 2023 a 2025 | Primer y último día incluidos, vecinos fuera |
| Enlaces anteriores | `anio`, `anio`+`mes`, `mes` solo, sin parámetros | Mismo intervalo que con selector; sin parámetros, año actual |
| Filtros | Caja recorta filas y totales; Tipo solo filas | Coincide en las 432 |
| Paginación | Enlace real a la página siguiente, hasta 3 páginas | Sin filas repetidas ni faltantes; período y totales iguales |
| Signos | Devolución de cobro (ingreso negativo), reversión parcial de egreso, mes con solo devolución, semana negativa | 950 − 380 = 570; −48.000 − 2.000 = −50.000; 100 − 350 = −250 |
| Saldo inicial | Caja con $777.000 de saldo inicial | No aparece ni se suma en Cashflow; el saldo disponible sigue siendo 777.750 |
| Fecha conservada | Formulario real leído del HTML y reenviado campo por campo | 31/01 → semana → enero → febrero (29) → 2023 (28) → año → día |
| Inválidos | 25 valores rechazados y 20 raros tolerados (listas, fechas del año 1 y 9999, página −1) | Ningún 500; los 25 vuelven con error en su campo |
| Permisos | Anónimo, OPERATIVO y PROFESOR sobre las tres rutas | Anónimo al login; los otros 403; ninguno registra |
| Alta | ADMIN registra egreso e ingreso; fecha futura, monto 0, sin observación, mes cerrado | Egreso guardado negativo; validaciones y aviso intactos |

**Navegador**, Chrome por CDP contra la aplicación en el puerto 8811, entrando por el login
([recorrido](evidencia/a10-verificacion-claude/recorrer-navegador.mjs) · [12 capturas](evidencia/a10-verificacion-claude/capturas/)):
importes de nueve consultas leídos en pantalla; Día → Semana → Mes → Febrero → Año elegidos
con las flechas del teclado y Año → Día con un evento de cambio; campo Fecha; Nuevo pulsado
hasta «Nuevo movimiento»; página 2 pulsada (30 + 11 filas, mismos totales); cuatro modos y
una selección dentro de un marco de 375 sin desborde. Calibración: el login entra en el marco.

**Código.** En `app/` el único archivo cambiado es `CashflowWebController`, y solo `index`.
Rutas y middleware sin cambios: Cashflow sigue dentro de `ensure.admin.web`.

## Observaciones que no frenan

- **Reversión de egreso mayor que los egresos del período.** `abs()` sobre la suma la muestra
  como egreso y la resta: +500 y +70 dan 430 en vez de 570. No es alcanzable hoy: los tres
  servicios que escriben egresos fuerzan el negativo y no existe reversión de egresos. Ya
  estaba antes de A10 y también en `CashflowSaldoService`. La fila de un egreso positivo se
  dibuja con «−». Queda anotado para cuando exista esa reversión.
- **Fechas sin tope de año.** `fecha=9999-12-31` en Semana muestra «al 02/01/10000». No rompe.
- **`tipo_caja_id` no se valida.** Un valor que no es número se ignora; no rompe.

## Lo que no comprobé

- La suite completa: no la corrí. Corrí en mi base las nueve del autor, las de saldo inicial y las de celular de Cashflow: 20 aprobadas, 214 aserciones.
- Elegir una opción abriendo el desplegable con el mouse: usé teclado o evento de cambio.
- Si el mensaje de un parámetro inválido se le muestra a la persona; solo que no hay 500.
- Un celular físico: fue el marco de 375. El servidor de ensayo quita `X-Frame-Options` para eso.
- El servidor real: nada de esto está desplegado.

Servidor de ensayo y Chrome detenidos. Sin commit, push ni despliegue.
