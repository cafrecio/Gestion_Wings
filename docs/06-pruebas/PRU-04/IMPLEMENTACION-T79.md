# T79 — Reportes, Cashflow y Cobranza rápidos con dos años de datos

**Claude, CAB, 25/10/2026. Verificada por Codex. Desplegada en el sitio de prueba el 25/10 (`b816d181`), no en producción. Cerrada.**

## Qué cambia para la persona del club

Con dos años de historia, **Reportes tardaba 22 segundos en abrir**. Ahora tarda unos 2,6 s, y
Cashflow pasó de 3 s a 0,1 s. **Los números que se ven son los mismos**: se comprobó comparando la
salida completa antes y después.

## Causa (medida)

- **Reportes** releía la historia entera ocho veces por pedido: una para el mes, una para el gráfico y
  una por cada uno de los seis meses del gráfico. Con 7.000 movimientos y 7.000 cobros, cada lectura
  tarda más de un segundo, y no por la base: son 7 consultas. El tiempo se iba en armar miles de objetos.
- **Cashflow** calculaba el reporte entero solo para mostrar el saldo de cada medio.
- **Cobranza** volvía a leer las deudas que ya tenía cargadas, y calculaba dos veces los punitorios.

## Cambios

| Archivo | Cambio |
|---|---|
| `app/Services/ReporteMensualService.php` | `enLote()`: dentro de un pedido la historia se lee una sola vez; fuera de él, cada llamada lee la base, así nadie recibe datos viejos. `saldosPorCaja()`: el saldo de cada medio sumado en la base; lo usan el reporte y Cashflow, así es un solo cálculo. Al leer la historia se trae del cobro solo lo que usa `PartesPagoCuotaService::partes()`. |
| `app/Http/Controllers/ReporteWebController.php` | El mes y su gráfico se piden en un mismo lote. |
| `app/Http/Controllers/CashflowWebController.php` | Los saldos salen de `saldosPorCaja()`. |
| `app/Services/CobranzaEstadoService.php` | `saldoDeAlumnos()` usa las deudas ya cargadas, si vinieron con los alumnos, y recibe los punitorios que el listado ya calculó. |
| `tests/Feature/ReportesRapidosT79Test.php` | 4 pruebas nuevas (grupo `lento`). |

## Los números no cambiaron

- **Reportes y saldos** (`prueba-tecnica/comparar-t79.php`). Sobre dos años de datos se agregaron, dentro de una transacción que se revierte, los casos que mueven los saldos:
  - historial desde 2024 y saldos iniciales;
  - una caja cerrada y una abierta, con ingresos y egresos con y sin tipo guardado;
  - un movimiento cancelado y uno con fecha futura;
  - centavos sueltos.

  Se volcaron 52 reportes mensuales (todos los meses, con y sin deporte), dos evoluciones y 25 cortes de saldo, con el código viejo y con el nuevo. **Iguales**: solo difiere el número interno de las filas agregadas, porque MariaDB no devuelve el contador al revertir.
- **Cobranza** (`prueba-tecnica/comparar-cobranza-t79.php`): listados `TODOS`, `DEUDORES` y por defecto, resumen y saldos, con el código viejo y con el nuevo. **Archivos idénticos.**
- **Partes de cuota:** las de los 6.999 cobros, leídos con y sin recorte de columnas, son idénticas.

## Tiempos con dos años de datos

Base descartable `wings_testing_claude`, 300 alumnos, 6.999 cobros, 532 cajas (`performance-pantallas.php`, mediana de 3 corridas). La máquina se comparte con Chrome, Codex y Gemini, y los tiempos sin cambios varían hasta un 30 % entre corridas. Las cifras de antes son la medición de la mañana; las de después, la mejor de dos corridas.

| Pantalla | Antes | Después | Consultas |
|---|---|---|---|
| Reportes · Finanzas | 22.280 ms | **2.613 ms** | 173 → 121 |
| Cashflow | 2.976 ms | **101 ms** | 80 → 57 |
| Cobranza (dueño) | 1.329 ms | 1.147 ms | 32 → 30 |
| Cobranza (operativa) | 1.371 ms | 1.193 ms | 31 → 29 |
| Inicio de la operativa | 851 ms | 659 ms | 24 → 22 |
| Inicio del dueño | 1.497 ms | 1.389 ms | 32 → 35 |
| Reportes · Sueldos / Alumnos | 1.797 / 2.133 ms | sin cambio | — |

Detalle: `prueba-tecnica/performance-volumen-2-anios-t79.json`.

## Pruebas

- **Nuevas** (`ReportesRapidosT79Test`), 4/4 verdes:
  - la historia se lee una vez, también en la pantalla `/reportes`;
  - fuera del lote, el mismo servicio ve un movimiento recién cargado;
  - `saldosPorCaja` coincide con el reporte, con pendientes en el escenario;
  - Cobranza da lo mismo con y sin las deudas precargadas.
- **Suite completa** en `wings_testing_claude`, corrida antes de agregar el archivo nuevo: **1.037 aprobadas, 6 omitidas, 0 fallidas, 8.982 aserciones, 974,7 s**. Después: el archivo nuevo y `DocumentacionNoMienteTest`, 5/5.
- **Contador:** 1043 → **1047**.

## Qué queda

Con dos años de datos y tres veces los alumnos del club:

- **Inicio del dueño (1,4 s):** necesita la historia una vez. Bajarlo más exige que el reporte guarde sus sumas en vez de recalcularlas, y eso es un cambio de diseño.
- **Cobranza (1,1 s):** el resumen vuelve a recorrer a todos los alumnos.
- **Reportes de Alumnos y de Sueldos (1,7–2 s):** no se tocaron.

Con los 101 alumnos de hoy todo abre por debajo de 150 ms. Inferido, sin medir: con dos años de datos y el tamaño real del club, deberían rondar un tercio de estas cifras.
