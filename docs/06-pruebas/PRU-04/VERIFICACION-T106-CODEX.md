# Verificación T106 — Codex CAB, 01/11/2026

**Resultado: CERRADA, con una observación menor de texto.** La decisión de Carlos se cumple en los cinco casos. Solo `wings_testing_codex`, copia aislada de fuentes y navegador Chrome en 127.0.0.1:8111. Sin producto, catálogo ni despliegue.

Se hizo pull antes de la etapa; tablero en curso. [Fuentes comprobadas contra el checkout](evidencia-control-t106-codex/fuentes.json). Grafo consultado y cobertura comprobada: informó metadatos cambiados/cobertura no disponible; las funciones citadas se leyeron directamente. No se heredan conclusiones del índice.

| Caso | Pedidos directos y filas concretas | Pantalla real |
|---|---|---|
| 17. Transferencia $1.500.000 | Ingresos $421.200; egresos $65.200; diferencia $356.000. Ambas patas listadas, sin sumarse a los totales generales. | Transferir desde Efectivo a Banco Galicia, importe confirmado y guardado. [Cashflow](evidencia-control-t106-codex/gw-control-t106-caso17-cashflow.jpg), [Historial](evidencia-control-t106-codex/gw-control-t106-caso17-historial.jpg). |
| 18. Cada cuenta | Efectivo: $421.200 de ingreso, $1.565.200 de egreso, neto −$1.144.000. Banco: ingreso $1.500.000, egreso $0. | [Efectivo](evidencia-control-t106-codex/gw-control-t106-caso18-efectivo.jpg), [Banco](evidencia-control-t106-codex/gw-control-t106-caso18-banco.jpg). Con saldo inicial $2.000.000, Efectivo queda $856.000; Banco $1.500.000; total disponible $2.356.000. El control directo sin saldo inicial da −$1.144.000 y $1.500.000 respectivamente. |
| 19. Historial, Salida a otro medio | Una fila, egreso $1.500.000, ingreso $0. | [Filtro aplicado](evidencia-control-t106-codex/gw-control-t106-caso19-salida.jpg); se comprobó la opción seleccionada y la cifra posterior a la navegación. |
| 20. Anulada y mes anterior | Original y dos inversos, neto $0. Totales generales siguen $421.200/$65.200. Transferencia completa de octubre tampoco suma en noviembre; octubre sin negocio muestra $0/$0. | Anulación completa desde su formulario y [resultado](evidencia-control-t106-codex/gw-control-t106-caso20-anulada-cashflow.jpg); [noviembre con transferencia anterior excluida](evidencia-control-t106-codex/gw-control-t106-caso20-mes-anterior-excluido.jpg), [Historial](evidencia-control-t106-codex/gw-control-t106-caso20-anulada-historial.jpg). La transferencia anterior se preparó por servicio, no se presenta como carga humana. |
| 21. Saldos, Reportes e Inicio | Ingresos, egresos, resultado, resultado global, disponible, deuda y por pagar se conservan antes/después de la transferencia completa. Inicio comprueba las cifras devueltas por el reporte. Saldos cambian entre cuentas por exactamente $1.500.000. | [Inicio](evidencia-control-t106-codex/gw-control-t106-caso21-inicio.jpg), [Reportes](evidencia-control-t106-codex/gw-control-t106-caso21-reportes.jpg). Disponible $2.356.000, resultado $356.000. |

## Histórico con una sola pata en el mes

Fixture explícito: salida antigua independiente −$1.500.000 el 31/10 y entrada independiente +$1.500.000 el 1/11, sin cabecera de transferencia. No se enlazaron ni reescribieron. [Filas y reportes completos](evidencia-control-t106-codex/historico.json).

Cashflow general octubre: $0/$0, diferencia $0, aunque lista la salida. Noviembre: $421.200/$65.200, diferencia $356.000, aunque lista la entrada. Historial sigue la misma exclusión; filtrando Entrada desde otro medio suma $1.500.000. Reporte conserva `resultado_global` octubre −$1.500.000 y noviembre $1.856.000; su resultado del negocio es $0/$356.000. **El resultado global es un campo del pedido directo; no se afirma que esa cifra se haya visto como tarjeta en pantalla.** [Octubre](evidencia-control-t106-codex/gw-control-t106-historico-octubre.jpg), [noviembre](evidencia-control-t106-codex/gw-control-t106-historico-noviembre.jpg), [Reportes](evidencia-control-t106-codex/gw-control-t106-historico-reportes.jpg).

**H1, Baja, texto:** `resources/views/cashflow/index.blade.php:134` dice siempre «resultado del período». Al filtrar Banco muestra $1.500.000 por la transferencia interna; con la pata histórica de octubre muestra $0 mientras su flujo neto es −$1.500.000. El número cumple la nueva regla; la etiqueta no explicita su alcance. Propuesta: con cuenta «Movimiento neto de la cuenta»; sin cuenta «Ingresos menos egresos · sin transferencias», con ayuda que distinga disponible, arrastre, resultado del negocio y cargas históricas. No se cambió la vista ni se pidió autorización visual.

## Pruebas y manual

- Área: [55 aprobadas, 598 aserciones](evidencia-control-t106-codex/pruebas-area.txt): nueve T106 y regresiones T61/T97.
- Propias: [2 aprobadas, 72 aserciones](evidencia-control-t106-codex/pruebas-propias.txt), sobre pedidos web, servicios y tablas. Primera corrida utilizó por error `/inicio` y dio 404; corregida únicamente la ruta del control a `/admin/dashboard`. Salida fallida conservada.
- [Diff de expectativas ajenas](evidencia-control-t106-codex/revision-expectativas.txt): T61 $320.000/$307.000 pasa a $20.000/$7.000 y T97 $312.000,50/$300.000 pasa a $12.000,50/$0. Únicamente resta la transferencia de $300.000 en ambos lados; no cambia acciones, saldos ni demás condiciones. Justificado por la decisión del 01/11.
- Manual Cashflow, Historial y contrato V5 contrastados con `CashflowWebController::index` y `CajaWebController::historial`: exclusión por clasificación TRASPASO, excepción cuenta/subrubro, cancelados visibles sin sumar, filas conservadas, clasificación NULL sumada. La excepción no modifica los saldos calculados por los servicios.
- Verificador mecánico: [59 páginas, 1 problema ajeno a T106](evidencia-control-t106-codex/manual-verificador.txt), texto antiguo de recibo de liquidación impaga (`resources/manual/admin/descargar-recibo-liquidacion.md:44`) tras T109; no se atribuye a transferencias ni se corrige en este control. Las dos páginas de T106 no aparecen como fallidas.
- Capturas y DOM guardados después de cada consulta; un POST puede mostrar momentáneamente el formulario anterior y se esperó su resultado. Las capturas son del sistema andando. Se conservan salidas originales en ZIP y TXT normalizados a LF/sin espacios finales para Git.
- Suite completa única pendiente para el final de T102 por orden expresa de Carlos. Sin suites completas adicionales en esta etapa.

Sin graves ni medios: T106 cerrada por Codex; sigue T102. Propuesta menor de etiqueta solo en este informe; tabla de pruebas intacta.
