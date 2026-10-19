# T74 — Inicio de la operativa: vencidos y, aparte, los que están en plazo

19/10/2026 · Claude CAB. Decisión de Carlos (DU-60, 16/10): el recuadro de deuda del Inicio de
la operativa muestra los vencidos —como Cobranza— y aparte los que deben pero están en plazo;
vencidos más en plazo es lo que anuncia el buscador de Cobrar. Cobranza no cambia.
Caso de la tabla: CBZ-022.

## Qué pasaba

El Inicio mostraba una sola tarjeta, «Con deuda», con morosos y deudores (lo mismo que
Cobranza). El buscador de Cobrar anuncia a todos los que deben, incluidos los que todavía
están en plazo. Entre el 1 y el 10 de cada mes la operativa veía dos números distintos para
«los que deben», sin forma de saber por qué.

## Qué cambió

| Dónde | Cambio |
|---|---|
| `OperativoDashboardController::index` | Una sola pasada de `CobranzaEstadoService::listadoCobranza('TODOS')`. **Vencidos** = estado MOROSO o DEUDOR, el mismo filtro que la lista por defecto de Cobranza. **En plazo** = deben (saldo mayor a cero, el mismo saldo que usa el buscador) y no están vencidos. La vista recibe `vencidos` y `enPlazo` en lugar de `alumnosConDeuda`. |
| Inicio de la operativa (`operativo/dashboard`) | «Atención a alumnos» pasa de dos tarjetas a tres: «Vencidos» (rojo si hay, lleva a Cobranza), «En plazo» (azul si hay, lleva a Cobranza filtrada En plazo) y «Posibles inactivos» sin cambios. En celular se apilan con la clase existente `resumen-operativo-grid` (T75), igual que «Recaudación de hoy»: en tres columnas, «Ir a cobranza →» se partía en tres renglones. Sin JavaScript ni CSS nuevo. |
| `CuotaAltaEstadoTest` | La aserción que miraba `alumnosConDeuda` mira ahora `vencidos` (20) y `enPlazo` (0). |

**Por qué «en plazo» no es solo el estado EN_PLAZO:** se cuenta como «debe y no está vencido»
para que la suma con vencidos salga, por construcción, del mismo saldo que cuenta el buscador.
Las pruebas comprueban además que coincide con la lista En plazo de Cobranza.

**Cobranza no se tocó.**

## Pruebas

`tests/Feature/InicioVencidosEnPlazoT74Test.php`, 5 pruebas, con un padrón de 2 vencidos,
2 en plazo (uno con pago parcial), uno al día, uno sin cuotas, uno con un mes que no empezó y
una inactiva con deuda:
1. El 05/10: Vencidos 2 = Cobranza; En plazo 2 = Cobranza filtrada En plazo; 2 + 2 = buscador de Cobrar.
2. El 15/10 (pasó la gracia): Vencidos 4, En plazo 0, la suma sigue igual al buscador.
3. Quien debe solo la inscripción cuenta una vez, y la suma sigue cerrando.
4. La pantalla muestra «Vencidos», «En plazo», «Posibles inactivos», ya no «Con deuda», y cada tarjeta lleva a su lista.
5. Cobranza por defecto sigue sin En plazo ni Al día.

Suite completa sola en `wings_testing_claude` (19/10, antes de apilar en celular, cambio que
no toca lógica): **886 aprobadas, 3 omitidas, 1 fallida, 7856 aserciones, 1435 s**. La fallida
era `DocumentacionNoMienteTest`, que contó 895 porque en la carpeta compartida había, sin
commitear, un archivo de pruebas de Gemini (T56/T68) además de las 5 de T74. En HEAD había 885;
con T74 son **890**, y así quedan declaradas.

## Navegador

Chrome real sobre `wings_testing_claude`, operativa sintética, fecha simulada 05/10/2026, mismo
padrón que la prueba (`evidencia-t74-claude/preparar-local.php` y `recorrer-local.mjs`;
resultado en `resultado-navegador.json`):

| Captura | Qué muestra |
|---|---|
| `01-inicio-1366.png` | Vencidos **2**, En plazo **2**, Posibles inactivos 0. |
| `02-inicio-375.png` | Las tres tarjetas apiladas en celular, sin desborde horizontal. |
| `03-cobranza-vencidos-1366.png` | «Ir a cobranza» de Vencidos: Cobranza por defecto, 2 alumnos. |
| `04-cobranza-en-plazo-1366.png` | «Ir a cobranza» de En plazo: Cobranza filtrada En plazo, 2 alumnos. |
| `05-cobrar-buscador-1366.png` | El buscador de Cobrar anuncia **4** alumnos con deuda pendiente = 2 + 2. |

Para entrar con el reloj simulado se levantó el servidor con `SESSION_EXPIRE_ON_CLOSE=true`:
con el reloj en el 05/10 la cookie de sesión nace vencida para el navegador, que vive el 19/10.
Es una condición del entorno de prueba, no del cambio.

## Qué no se verificó

- La altura de las tarjetas (con espacio libre abajo) es la que ya tenían; no se tocó.

- No se probó en el servidor ni con datos reales: base descartable `wings_testing_claude`.
