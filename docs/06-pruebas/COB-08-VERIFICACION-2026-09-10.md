# COB-08 — verificada en navegador, 10/09/2026

Codex CyE. Commit d61cf42, copia aislada, Chrome 1440x1000.
Base nueva wings_cob08_20260910_browser; sin usar gestion_wings ni datos reales.
Reloj Carbon de la copia fijado al 25/08/2026, como la matriz automatizada.
Cookie de sesion sin vencimiento al cerrar necesaria por el reloj historico.
No se modifica reloj del equipo ni codigo del arbol compartido.

| Caso | Campo inicial | Total anunciado / pago registrado | Deuda luego del cobro |
|---|---:|---:|---|
| Alta 20/08, deuda existente | 42.000 | 10.000 / 10.000 | Original 42.000, pagado 10.000, saldo 32.000, PENDIENTE |
| Alta 20/08, fila virtual sin deuda | 42.000 | 10.000 / 10.000 | Original 42.000, pagado 10.000, saldo 32.000, PENDIENTE |
| Segundo cobro del primer alumno | 32.000 | 32.000 / 32.000 | Original 42.000, pagado 42.000, saldo 0, PAGADA |
| Alta 25/08, tramo 40% | 24.000 | 10.000 / 10.000 | Original 24.000, pagado 10.000, saldo 14.000, PENDIENTE |

Tres alumnos sinteticos, plan 60.000. Sin editar inputs ocultos ni llamar al
servicio para cobrar: seleccion de periodo, importe, medio y boton por Chrome.
Consultas de filas concretas despues de cada POST y capturas de pantalla.
Cartel 70% visible en ambos primeros pagos; 40% en el cuarto caso. En el segundo
cobro no aparece cartel ni se vuelve a descontar, porque ya existe un pago.

Suite completa en otra base nueva wings_cob08_20260910_suite:
147 pruebas, 805 aserciones, verde (46,36 s). El reloj sintetico no se activa
para esa base. Vistas compilan, cache limpiada. Diff vistas/CSS compartidas vacio.

Alcance: los cuatro casos pedidos, sin cambio de plan durante estos cobros.
No acredita barrido completo de combinaciones COB-07 ni despliegue.
Scripts, capturas y resultados locales ignorados: storage/app/cob08-20260910.
