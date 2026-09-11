# COB-05 — FRENADA — 11/09/2026 — Codex CyE

Version probada: main a9795c6. Chrome con JavaScript real, copia aislada y una
misma base sintetica wings_cob05_20260911_browser para todos los cobros.
Reloj de la copia: 25/09/2026. Sin tocar gestion_wings ni desplegar cambios.

## Recorrido ejecutado

| Caso | Total anunciado y registrado | Resultado |
|---|---:|---|
| Sin descuento | 60.000 | Coinciden |
| Primer pago 100% | 60.000 | Coinciden |
| Primer pago 70% | 42.000 | Coinciden |
| Primer pago 40% | 24.000 | Coinciden |
| Sena con 70% | 10.000 | Deuda 42.000, pagado 10.000, pendiente 32.000 |
| Saldo posterior | 32.000 | Deuda 42.000 PAGADA |
| Agosto 70% y septiembre completo | 102.000 | Imputaciones 42.000 y 60.000 |
| Subida 40.000 a 60.000 y 70% | Anuncia 60.000; registra 42.000 | FALLA; freno |

Se contrastaron filas concretas de deuda, pago, imputaciones y movimiento, mas
texto extraido del PDF descargado por la ruta autenticada. Medio Efectivo en estos
ocho recibos. Estado calculado por CobranzaEstadoService: AL_DIA al completar,
MOROSO tras la sena (dia 25). No se acredita inspeccion de cada fila en pantalla
de Cobranza. Captura de recibo 70% renderizada e inspeccionada visualmente.

## Defecto reproducido

Alumno sintetico Subida COB05: alta 20/09, plan 40.000, deuda septiembre 40.000,
sin pagos previos. Abrir Cobrar y elegir plan de 60.000. Cartel 70% presente,
campo y total anuncian 60.000. Confirmar sin alterar ese campo.

Pago 8: 42.000; imputacion 42.000; movimiento 8: 42.000 Efectivo; deuda 8:
original 42.000, pagado 42.000, PAGADA. PDF CUOTA-8: 42.000 Efectivo.
Plan nuevo activo de 60.000. Estado de cobranza AL_DIA.
Tambien permanece 28.000 en el encabezado «Total pendiente» de la pantalla previa.

Codigo leido: el manejador de cambio de plan de cobrar.blade.php usa
nuevoPrecio - pagado para campo y data-saldo, sin aplicar el tramo. El servidor
calcula el precio descontado. No se implemento correccion.

## Pendiente por el freno solicitado

Bajada con descuento, cancelacion y recobro, FIN-02 con dos cobros iguales en
medios distintos y cancelacion Efectivo/recobro Transferencia. No verificados.
No declarar COB-05 ni FIN-02 cerradas.

Suite separada wings_cob05_20260911_suite: 154 pruebas / 920 aserciones verdes.
El verde no detecta esta diferencia de JavaScript. Artefactos locales ignorados:
storage/app/cob05-20260911 (capturas, PDFs, payloads y filas por caso).
