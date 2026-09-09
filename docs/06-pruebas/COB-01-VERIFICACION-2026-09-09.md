# COB-01 — verificacion de navegador — 09/09/2026

Responsable: **Codex CyE**. Estado: importes verificados; cierre pendiente de
aclarar que significa «arqueo» en el pedido. No se cambio codigo funcional.

## Aislamiento y metodo

- Antes: archivo Git de `caa4976^` (`e18309e`). Despues: archivo de `main`
  (`caa4976`). El checkout compartido no se cambio de rama.
- MariaDB nuevas: `wings_cob01_20260909_antes` y
  `wings_cob01_20260909_despues`. Migraciones y fixture sintetica propia;
  sin dumps, sin conexion a `gestion_wings` ni al servidor del club.
- Chrome automatizado, login OPERATIVO, seleccion de cuota y medio de pago,
  boton Cobrar. No se inyecto un payload ni se modificaron los handlers.
- Captura del request real mediante Chrome DevTools Protocol,
  `Network.requestWillBeSent` (no panel DevTools operado manualmente).
- Recursos JS/Blade iguales en ambas revisiones; assets compilados del repo.

## Payload literal observado

```text
Content-Disposition: form-data; name="montos_cuota[2026-09]"

28.000
```

Segundo alumno:

```text
Content-Disposition: form-data; name="montos_cuota[2026-09]"

1.500.000
```

Ambos valores llegan con puntos tanto antes como despues. Confirma el hecho que
faltaba observar: el FormData enviado conserva el importe formateado. No se midio
por separado el instante de registro de cada listener.

## Resultado

| Control | Antes: 28.000 | Despues: 28.000 | Despues: 1.500.000 |
|---|---:|---:|---:|
| Respuesta del cobro | 302 | 302 | 302 |
| Deuda original | 28000.00 | 28000.00 | 1500000.00 |
| Deuda pagada | 28.00 | 28000.00 | 1500000.00 |
| Estado deuda | PENDIENTE | PAGADA | PAGADA |
| Pago monto_final | 28.00 | 28000.00 | 1500000.00 |
| Imputacion monto_aplicado | 28.00 | 28000.00 | 1500000.00 |
| Movimiento monto | 28.00 | 28000.00 | 1500000.00 |
| Detalle de caja | No capturado | $28.000 | $1.500.000 |
| PDF, periodo y total | No solicitado | $28.000,00 | $1.500.000,00 |

Antes, 1.500.000 devuelve HTTP 422: campo montos_cuota.2026-09 debe ser numerico.
Despues, caja y resumen por Efectivo Prueba y rubro Cuotas: ingresos/neto
$1.528.000, egresos $0, dos movimientos. Los dos PDF fueron obtenidos desde los
enlaces de recibo (HTTP 200), renderizados y revisados visualmente: alumno,
Septiembre 2026, importe y medio de cobro correctos.

## Freno requerido

El pedido agrega «arqueo». No aparecen campos de importe contado o diferencias
ni rutas de arqueo en app, rutas, migraciones o vistas. `resumen()` suma
movimientos por medio; `cerrarCajaOperativa()` cambia estado y fecha, sin recibir
importe contado. No llamar arqueo a ese resumen sin decision del usuario.

Opciones: confirmar que el pedido refiere al resumen ya verificado, o identificar
otro circuito concreto para probar. No implementar un arqueo como parte de COB-01.
La suite completa no se repitio en esta verificacion interrumpida; no se declara
COB-01 cerrada ni desplegada en produccion.

Evidencia local descartable conservada en `storage/app/cob01-20260909/`, ignorada
por Git (scripts, capturas, PDF y copias aisladas). Este reporte conserva los
valores observados sin tokens ni datos reales para continuar en otra computadora.
