# T97 — Anulación de transferencia completa

## Corte del 01/11/2026 — Codex CAB

**En curso, propuesta visual pendiente del OK de Carlos. No implementada ni entregada a verificación.**
Se cumple el freno del pedido antes de modificar `resources/views/**`.
[Galería para Carlos](APROBAR-T97.html): fila con Anular, confirmación y resultado propuesto, a 1366 y dentro de un marco de 375.

## Comprobado en esta etapa

- Se leyó continuidad, guías y contrato T61; main estaba actualizado en b594cf39 al iniciar. Otro trabajo avanzó HEAD a 9d4b3565 durante esta etapa; no se atribuye a T97.
- `TransferenciaCashflowService::registrar` crea una cabecera y dos patas atómicas, sin servicio de anulación. `routes/web.php` ofrece crear/guardar transferencia. El listado actual le ofrece Ver.
- `CashflowMovimiento::esDirecto` exige referencias y `contra_origen_id` nulos; las patas quedan fuera de la corrección de directos. No se modificó esa defensa ni `exigirSinTraspaso`.
- La propuesta renderiza la ruta real de Cashflow y la plantilla real de eliminación, con componentes de Wings. El generador cambia en memoria los datos de presentación y el formulario. No modifica las vistas originales.
- El resultado anulado se preparó **solo como fixture visual**: originales y dos inversos conservando fecha, subrubro, cuenta y clasificación. La referencia literal `ANULACION_TRANSFERENCIA` es ilustrativa; no se ha elegido un marcador de producto antes de completar el análisis de lectores.
- Renderizado final: **1 prueba, 11 aserciones, salida 0, 13,43 s**, solo `wings_testing_codex`. [Salida](evidencia-t97/propuesta/renderizado.txt). Generador fuera del descubrimiento normal de la suite: no aumenta el número de pruebas del producto.
- Capturas revisadas en Chrome: seis pantallas y login de control. Viewport de escritorio 1366 (las capturas completas de Cashflow omiten sus 15 px de scrollbar: imagen de 1351); `innerWidth` del iframe 375, contenido 360 sin desborde horizontal (15 px corresponden al espacio de scrollbar del documento). El login entra completo. [Fuentes y hashes](evidencia-t97/propuesta/origen.json).
- Se detectaron y corrigieron dos errores del generador: fecha del capital inicial posterior al escenario y sustitución de `$300` como referencia de regex. Se regeneraron HTML y capturas; no eran fallas del producto.
- T97 pasó a `en_curso`, con Codex. Carlos autorizó actualizar solo T97 preservando T96; los cambios de Gemini y la evidencia T34 ajena no se incorporan como propios.
- Sin cambios de producto, manual, catálogo de pruebas ni despliegue. No se abrió el sitio de prueba ni producción; el visor estático escucha solo en 127.0.0.1 y no inicia Laravel.

## Ejemplo de la propuesta (pesos)

| Estado | Efectivo | Banco Nación | Total |
|---|---:|---:|---:|
| Capital inicial ficticio | 600.000 | 100.000 | 700.000 |
| Transferencia de 300.000 | 300.000 | 400.000 | 700.000 |
| Resultado visual después de anular | 600.000 | 100.000 | 700.000 |

En el escenario visual las tarjetas de Cashflow pasan de 300.000/300.000 a 0/0 por los inversos en el mismo subrubro. Esto no acredita aún la anulación real ni Historial/Reportes/Inicio. No decide T106.

## Lectores: inventario inicial, auditoría pendiente

Se consultó codebase-memory primero y se reindexó; la cobertura consultada informó cambios de metadatos y archivos parciales. Se usa como pista y se verifica fuente real, sin afirmar exhaustividad del grafo.
La búsqueda literal de `referencia_tipo|contra_origen_id` bajo `app/` devolvió estas 16 rutas:

| Grupo | Archivos |
|---|---|
| Modelo/observador | `Models/CashflowMovimiento.php`, `Models/Liquidacion.php`, `Observers/MovimientoReporteObserver.php` |
| Controladores/pedido | `Http/Controllers/CashflowWebController.php`, `CajaWebController.php`, `CashflowMovimientoController.php`, `Http/Requests/StoreCashflowMovimientoRequest.php` |
| Servicios | `Services/TransferenciaCashflowService.php`, `CashflowCorreccionService.php`, `CashflowService.php`, `CashflowIntegracionCajaService.php`, `PagoCuotaService.php`, `LiquidacionPagoService.php`, `ReporteMensualService.php`, `ReporteSueldosService.php`, `ReciboService.php` |

Lecturas puntuales confirmadas: ReporteMensual distingue referencias al resolver pagos y suma saldos por cuenta; ReporteSueldos limita el cálculo operativo a referencias nulas o corrección de directo; Historial en CajaWebController filtra origen Caja y calcula neto por rubro; ReciboService busca específicamente referencia de pago de cuota. **Falta completar todos los cuerpos y consumidores indirectos antes de decidir la identificación definitiva.**

## Reglas y verificaciones que siguen tras el OK visual

| Regla del pedido | Evidencia exigida al implementar |
|---|---|
| Anular desde cualquiera de las patas | Pedidos reales a ambas entradas; dos cuentas restauradas al peso |
| Motivo obligatorio | Vacío/espacios rechazados sin escritura; motivo conservado y mostrado |
| Anular siempre el par completo | Dos inversos en una transacción; fallo intermedio revierte todo |
| No editar transferencias ni sus contraasientos | Pedidos directos por los circuitos comunes, sin alterar defensas |
| No volver a anular ni duplicar contraasientos | Repetición y dos conexiones simultáneas reales; una sola anulación |
| Fecha del original y mes anterior | Cierres del mes original y actual, con ambas cuentas |
| Lectores y cifras | Cashflow, Historial, Reportes e Inicio; filtros por mes/cuenta; recibos/sueldos sin confusión |
| Tarjetas como si no existiera transferencia | Comparación antes/después por cuenta y período, sin resolver T106 |
| Acceso del dueño | Pedidos directos de ADMIN, operativo, profesor y anónimo |

Falta una prueba por regla y contrapruebas retirando cada defensa; regresiones T61/T27 sin cambiar expectativas; suite completa exclusiva en `wings_testing_codex`. No se hereda como verde ninguna suite anterior.
Faltan manual, fichas, contrato V5, estado/plan/checklist y eventual guía de despliegue, en el commit de implementación. La copia del catálogo de pruebas sigue solo lectura; se propondrán casos concretos para Claude en este informe.

## Hallazgo fuera de alcance

En la barra móvil del listado se observa el contador junto a Transferir con poco espacio y superposición. Esa parte de la plantilla no se cambió en la propuesta. Se informa sin corregirla en T97.

## Próximo paso

Carlos revisa la galería y aprueba o pide ajustes. Orden pedida para abrirla:

```powershell
Start-Process chrome "file:///C:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/APROBAR-T97.html"
```

El navegador automatizado bloqueó `file:`; se verificó por HTTP local con un servidor limitado a la propuesta y recursos públicos necesarios. Eso no autoriza abrir otros archivos ni conectar bases adicionales.
Después del OK se implementa, se verifica lo financiero, se actualiza la documentación y se entrega a Claude con T97 `a_verificar`. Esta etapa no cierra T97.
