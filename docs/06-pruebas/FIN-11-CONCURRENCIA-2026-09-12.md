# FIN-11 — Cobrar, cancelar y validar con dos conexiones

Codex CAB, 12/09/2026. Base MariaDB exclusiva y descartable:
`wings_testing_fin11_20260912`. No se usaron datos del club ni se desplegó.

## Prueba y evidencia anterior al arreglo

Cada caso parte de una cuota de 60.000 con un cobro confirmado de 10.000 en caja
ABIERTA. Un segundo cobro, cuando corresponde, es por otros 10.000. Los servicios
reales se ejecutan en dos conexiones; el primero queda sin confirmar mientras el
segundo intenta operar, con `innodb_lock_wait_timeout = 1`.

| Primero → segundo | Antes del arreglo | Después del arreglo |
|---|---|---|
| Cobrar → cancelar | FALLA: cancelación espera recién en UPDATE de deuda, después de leer saldo viejo | PASA: espera en lectura bloqueada; al reintentar conserva el segundo cobro: deuda pagada 10.000, primer pago ANULADO y segundo vigente |
| Cancelar → cobrar | PASA: no reveló defecto en este caso | PASA: deuda pagada e imputada 10.000, primer pago ANULADO |
| Cobrar → validar | PASA: no reveló defecto en este caso | PASA: deuda pagada 20.000 y cashflow 20.000, caja VALIDADA |
| Validar → cobrar | FALLA: espera recién al insertar movimiento en la caja; ya había escrito pago/deuda dentro de la transacción | PASA: espera antes de escribir; luego abre otra caja ABIERTA, conserva un movimiento en cada caja y cashflow de la validada en 10.000 |
| Cancelar → validar | FALLA: ambos avanzan sin esperar; pago ANULADO, movimiento CANCELADO, deuda pagada 0 y caja VALIDADA con cashflow 10.000 | PASA: espera; luego caja VALIDADA sin cashflow, deuda pagada 0 |
| Validar → cancelar | FALLA: mismo estado inconsistente del caso anterior | PASA: espera; después rechaza cancelar caja validada, sin cambios; deuda y cashflow 10.000 |

Corrida anterior a cambios funcionales: **4 fallas y 2 aprobadas**. Los dos cruces
cancelar/validar también se confirmaron hasta el final en ambas conexiones: los
estados e importes inconsistentes de la tabla se leyeron de la base después de los
dos commits. La limpieza posterior retiró exclusivamente las filas de prueba.
Las otras dos fallas demuestran espera tardía; el timeout revierte su transacción,
no se afirma que ese timeout haya dejado dinero persistido.

## Corrección

- El cobro toma alumno, deudas existentes y caja antes de modificar dinero.
- La apertura consulta y bloquea la caja vigente dentro de la transacción: si una
  validación la cerró mientras esperaba, no reutiliza una lectura vieja. Se conserva
  la apertura automática y el rechazo de caja vieja del contrato Caja/Cashflow V4.
- La cancelación toma alumno, caja, movimiento, pago, imputaciones y deudas; decide
  estados y montos con lecturas bloqueadas. Conserva el detalle de anulación FIN-03.
- La validación ya bloqueaba la caja; se conservó ese método. Ahora los otros
  recorridos comparten ese bloqueo antes de escribir.
- `MoneyLockingTest` permanece, con docblock que declara expresamente que solo
  verifica texto y no comportamiento.

No se cambiaron permisos, reglas contables, vistas ni CSS. El recálculo/cierre de
liquidaciones mencionado en el barrido histórico de FIN-05 queda pendiente separado:
no forma parte de los tres cruces pedidos en esta orden de FIN-11.

## Alcance comprobado

`CobrarCancelarValidarConcurrenteTest`: **6 pruebas / 103 aserciones** en corrida
aislada. Comprueba el SQL real que espera (`SELECT ... FOR UPDATE`), ausencia de
INSERT/UPDATE/DELETE exitosos antes de obtenerlo, rollback sin cambios mediante
fotografías completas de seis tablas y nivel transaccional cero después del timeout.
Después de confirmar el primero se reintenta el segundo, se verifican estados,
pagos e imputaciones, y se revalida la caja para comprobar que no duplica cashflow.

Es una prueba determinista de espera hasta timeout y reintento tras commit. No se
presenta como una solicitud HTTP reanudada automáticamente ni como cobertura de
toda combinación de cierre, rechazo, movimiento manual o liquidación.

## Pausa histórica del 12/09 (levantada el 13/09)

Suite completa del 12/09: **181 pasan / 5 fallan (1187 aserciones)**, 59,17 segundos.
Los seis casos FIN-11 y los casos FIN-05/FIN-07 pasaron. Las fallas aparecen en
archivos de prueba nuevos, todavía sin versionar, de otras tareas simultáneas:

- `CambioDeContrasenaRevocaAccesosTest`: tres casos no encuentran el rubro Sueldos
  requerido al actualizar al operativo.
- `ReciboErrorSanitizadoTest`: dos casos intentan crear un profesor sin el campo
  obligatorio `direccion` y fallan antes de probar el recibo.

No se modificaron esos trabajos. Se pausa el cierre por AGENTS §6b: falta resolver
esas fallas y reejecutar la suite completa. Ambos tableros quedan sin checked,
con pausa explícita. No había commit de FIN-11 ni despliegue en ese corte.

## Revalidación y cierre técnico — 13/09, Codex CAB

Por pedido de Carlos, git pull comprobó que el checkout estaba actualizado en
18aa14a, con 5f65dbd integrado. Se revisaron los cuerpos de los servicios y las
pruebas, sin cambiar las reglas del contrato Caja/Cashflow V4.

Suite completa ejecutada con DB_DATABASE=wings_testing explícita: **187 pruebas
aprobadas / 1206 aserciones**, 42,40 segundos. Pasan los seis cruces FIN-11,
FIN-05, FIN-07 y los archivos SEG antes fallidos. La pausa queda levantada.
Se conserva el detalle original de las cinco fallas: incluía contraseñas y recibos.

Sintaxis correcta en CajaService, PagoCuotaService, MoneyLockingTest y
CobrarCancelarValidarConcurrenteTest; view:cache y view:clear correctos.
Sin cambios en resources/views ni resources/css. Ambos tableros sincronizados.
Sin deploy; revisión cruzada y despliegue pendientes. El recálculo/cierre de
liquidaciones queda fuera de este cierre, como en el alcance original.
