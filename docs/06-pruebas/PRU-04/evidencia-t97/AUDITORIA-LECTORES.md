# T97 — Lectores y elección de referencias

Revisión de Codex CAB, 01/11/2026. Se buscó primero en codebase-memory y luego se contrastaron las lecturas en la fuente. El índice informó `coverage_unavailable`/`metadata_changed` y CSS parcial; no se usó como prueba de exhaustividad. El intento de reindexado encontró otra indexación activa. La búsqueda literal cubrió `app/`, `resources/`, `routes/`, `config/`, `bootstrap/`, `database/seeders/` y `scripts/`.

Antes de implementar se encontraron 16 archivos de aplicación con `referencia_tipo` o `contra_origen_id`. No se interpreta la coincidencia textual como un lector: se separan filtros, productores y metadatos.

| Archivo en app/ | Lectura/uso contrastado | Tratamiento en T97 |
|---|---|---|
| `Models/CashflowMovimiento.php` | `esDirecto()` exige ambas referencias, origen operativo y contraorigen nulos; relación al original | Se agrega la constante de referencia propia. No se cambia la condición de directo ni `puedeCorregirse()` |
| `Services/CashflowCorreccionService.php` | Rechaza no directos; crea `CORRECCION_DIRECTO` y contraorigen único | Intacto. El par y sus inversos quedan fuera de ese circuito |
| `Http/Controllers/CashflowWebController.php` | Resuelve alumnos solo para pagos, cajas solo para `REF_CAJA`, acciones por origen; totales por rubro sobre importes firmados | Distingue ambos tipos de transferencia, consulta cabeceras del listado en lote y agrega motivo/Anular solo al original vigente |
| `Http/Controllers/CajaWebController.php` | Excluye `REF_CAJA` de la segunda fuente de Historial; resuelve alumno solo con referencia de pago; neto EGRESO = menos monto, INGRESO = monto | Intacto. Original e inverso comparten subrubro y se compensan también en las tarjetas de Historial |
| `Services/ReporteMensualService.php` | Resuelve pagos solo por `REF_PAGO_CUOTA`, por vínculo operativo o legado `REF_CAJA`; conserva importe firmado y fotos históricas; saldos suman todo el libro | Intacto. Referencia propia y origen operativo nulo evitan confundir el ID de cabecera con un pago. Misma fecha/cuenta/fotos conservan los cortes |
| `Services/ReporteSueldosService.php` | Pagos operativos: subrubros de Sueldos, sin docentes, origen operativo nulo y referencia nula o `CORRECCION_DIRECTO` | Intacto. No se reutiliza `CORRECCION_DIRECTO` |
| `Services/ReciboService.php` | Busca primero operativo por pago; luego Cashflow por `REF_PAGO_CUOTA` e ID de pago | Intacto. `ANULACION_TRANSFERENCIA` no se toma como medio de un recibo aunque coincidan los IDs |
| `Observers/MovimientoReporteObserver.php` | Respeta fotos explícitas; deriva deporte solo de operativo, pago o liquidación | Intacto. Se copian explícitamente las tres fotos del original al inverso |
| `Services/CashflowService.php` | Rechaza TRASPASO por `exigirSinTraspaso`; normaliza signo por rubro; admite referencia en sus datos | Intacto. No se pasa por él para crear los inversos del par |
| `Services/CashflowIntegracionCajaService.php` | Idempotencia por `REF_CAJA`; productor de reflejos; saldo suma montos | Intacto. No se recicla esa referencia ni se agrega vínculo operativo |
| `Services/PagoCuotaService.php` | Productor de `REF_PAGO_CUOTA`; anulación selecciona únicamente esa referencia y ese pago, con originales positivos | Intacto por T97. No selecciona las nuevas anulaciones. Los cambios concurrentes de T100 son ajenos |
| `Services/LiquidacionPagoService.php` | Busca egreso existente exclusivamente con `REF_LIQUIDACION` e ID; productor de esa referencia | Intacto. No se reutiliza su referencia |
| `Models/Liquidacion.php` | Impide ajustar si ya existe `REF_LIQUIDACION` de esa liquidación | Intacto. La coincidencia de un ID de transferencia no alcanza para disparar ese rechazo |
| `Http/Controllers/CashflowMovimientoController.php` | API: transporte de referencia al servicio, filtro por referencia, listado/detalle | Intacto. API deshabilitada en `bootstrap/app.php`, no se reactiva |
| `Http/Requests/StoreCashflowMovimientoRequest.php` | API: valida referencia como texto de hasta 50 caracteres | Intacto. La nueva referencia cabe en ese límite; eso no habilita el API |
| `Services/TransferenciaCashflowService.php` | Productor del par `TRANSFERENCIA` con cabecera; reenvío de creación retorna el ID existente | Intacto. El circuito nuevo no cambia los importes ni las condiciones de creación |

Fuera de aplicación:

- `database/seeders/DemoSeeder.php`: busca reflejos de demostración con `REF_CAJA` y produce directos con referencia nula. No se ejecutó ese seeder.
- `scripts/pruebas/generar-capturas-t12-t13.php`: produce directos con referencia nula para capturas. No se ejecutó.
- `scripts/servidor/restaurar.sh`: comprueba orfandad de caja con predicado literal `referencia_tipo='CAJA_OPERATIVA'`. No se ejecutó ni se accedió al servidor.
- Las migraciones definen el contraorigen único y normalizan referencias antiguas; T97 no las modifica.
- No aparecieron consumidores de esos dos campos en las vistas, JavaScript, configuración o rutas del barrido literal.

## Decisión aplicada

Se conserva `TRANSFERENCIA` en los originales. Los inversos usan `CashflowMovimiento::REF_ANULACION_TRANSFERENCIA = 'ANULACION_TRANSFERENCIA'`, referencia de cabecera y `contra_origen_id` del original. El índice único existente impide duplicar un inverso de una misma pata. La auditoría de motivo, administrador y momento se guarda en la cabecera, sin usar el historial de correcciones de directos.

El guardado nuevo vive en `AnulacionTransferenciaCashflowService`, separado de los productores generales. Solo admite una cabecera con su par íntegro y vigente. Bloquea cuentas en orden de ID (como registrar), después cabecera y patas. No se añade una excepción a `esDirecto()` ni a `exigirSinTraspaso()`.

Las comprobaciones conductuales se entregan en `IMPLEMENTACION-T97.md` y sus salidas. Esta lectura por sí sola no certifica cifras, concurrencia ni suite. Tampoco resuelve T106: las transferencias vigentes siguen sumando a entradas/salidas y solo su anulación debe compensarlas.

## Alcance real de los filtros

Cashflow ofrece período, cuenta y tipo. Historial ofrece fechas, subrubro, tipo y búsqueda; no tiene filtro de cuenta. Reportes ofrece mes y deporte, con saldos de todas las cuentas; Inicio usa el mes actual y no acepta un filtro de mes/cuenta. Las pruebas deben respetar esos caminos existentes: filtrar por cuenta donde existe y contrastar sus saldos/filas donde no existe. No se agregan filtros a otras pantallas en T97.
