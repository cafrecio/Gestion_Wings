# Verificación de T97 — Claude CAB, 01/11/2026

**Resultado: aprobada y cerrada.** Sin hallazgos graves ni medios. Sin desplegar: Cora trabaja en el sitio de prueba.

Controlé `7e72b0d4` y `b261834f`, sin dar por cierto el informe. Pruebas en `wings_testing_claude`, sobre `main` con T106, por pedidos reales a la aplicación. Evidencia: [`evidencia-t97-control/ControlT97ClaudeTest.php`](evidencia-t97-control/ControlT97ClaudeTest.php).

## Comprobado (ejecutado por mí)

| Qué | Resultado |
|---|---|
| Las 31 de Codex (`AnulacionTransferenciasT97Test` y `...ConcurrentesT97Test`, con dos conexiones reales) sobre `main` | 31 aprobadas, 384 aserciones |
| Mi control | 8 aprobadas, 77 aserciones |
| Transferir $300.000,75 y anular desde la otra fila | Efectivo $299.999,25 y Banco $400.000,75; al anular, $600.000 y $100.000 |
| Con T106: tarjetas de Cashflow sin filtro, por cada cuenta, y de Historial | $0 / $0 en todas; 4 filas sin filtro y 2 por cuenta |
| Disponible en Reportes | $700.000, sin cambios |
| Las cuatro filas | Abren; editar y eliminar rechazados, sin escribir; solo **Ver** |
| Sin observaciones | Se puede anular |
| Anular en noviembre una transferencia de octubre | Noviembre no cambia; los inversos llevan la fecha de octubre |
| Cargar otra después de anular | $570.000 y $130.000; la nueva ofrece **Anular** |
| Operativa | 403 por dirección y por POST; no la ve en Historial; nada se anula |
| Formulario viejo de una ya anulada | Vuelve con «La transferencia ya está anulada.»; motivo y filas no cambian |
| Si la plata ya se gastó | No bloquea: Banco queda en −$250.000, como pedía el prompt (T104) |

## Leído, no ejecutado

- **Código:** `AnulacionTransferenciaCashflowService` toma cuentas, cabecera y patas con bloqueo, en el mismo orden que `registrar()`; escribe todo en una transacción. No debilita `esDirecto()`, `puedeCorregirse()` ni `exigirSinTraspaso`.
- **Mutaciones:** no las repetí. Leí `mutaciones/resultado.json`: 19 de 19 detectadas.
- **Suite completa de Codex:** 1.401 aprobadas, 6 omitidas, en su copia aislada. No la repetí. Mi suite rápida de T106, con T97 adentro, dio 1.294 aprobadas, 6 omitidas, 0 fallidas.
- **Capturas:** miré la pantalla anulada a 1366 y la confirmación a 375, y coinciden con lo que describe el informe. El aspecto lo aprobó Carlos, y el commit lleva su línea de autorización.

## Observaciones bajas

- **O1, documento falso, error mío, corregido:** T106 dejó falsa una frase de `consultar-cashflow.md` que decía que las transferencias vigentes siguen contando en los totales. No la vi cuando hice T106. Corregida en este commit.
- **O2:** el prompt pedía avisar en el informe que la anulación puede dejar una cuenta en negativo, y el informe no lo dice. El manual tampoco lo decía: agregué una frase en `transferir-entre-cuentas.md`.

## Lo que no comprobé

Sitio de prueba y producción: la migración se corre al desplegar. Pantalla por navegador contra la aplicación: me basé en pedidos reales y en las capturas de Codex.

## Tabla de pruebas

Pasé las propuestas de Codex a CSH-020 a CSH-025, `no_implementado` hasta desplegar. El catálogo queda en 341 casos; la planilla «Prueba por días» se actualiza con la tanda 5.
