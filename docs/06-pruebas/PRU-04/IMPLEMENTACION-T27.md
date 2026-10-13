# T27 — Correcciones desde Cashflow

Codex CAB, 12/10/2026. Etapa 2 implementada y comprobada en base propia.
**Sin despliegue ni cierre de T27.**

## Decisiones de Carlos

La [propuesta de etapa 1](PROPUESTA-T27.md) contiene el inventario, la comparación
de Cashflow/Movimientos/Historial y las respuestas de Carlos.

- Visual: «Sí, apruebo la propuesta visual». Cashflow, íconos por fila, sin «Corregir».
- Editar: «Modificar el registro y conservar historial».
- Eliminar: «Conservarlo y crear un asiento en contra».
- Motivo: «No, opcional en ambos casos», para los directos.
- Plazo: «De cualquier mes anterior, sin límite».
- Caja validada: «Solo consulta».
- Liquidaciones: las pagadas no se tocan; Editar de una no pagada lleva a su flujo existente.
- Mes anterior: «Actualizar el reporte del mes original, con historial».

## Resultado y recorridos

| Tipo | Acceso desde Cashflow | Escritura en T27 |
|---|---|---|
| Directo manual, ingreso o gasto | Lápiz → editor; papelera → confirmación | Mismo ID al editar. Eliminación conserva original y crea contraasiento con fecha original |
| Aporte / retiro | Mismos íconos del directo | Conserva su clasificación; no se confunde con resultado del negocio |
| Cobro del dueño sin caja | Ícono de anulación → ficha del alumno | Anulación existente de Pago, con sus reglas y motivo |
| Inscripción cobrada por el dueño | Mismo historial del alumno | Si comparte Pago con cuota, no se ofrece anulación independiente de una fila |
| Contraasiento de Pago anulado | Ojo → ficha | Consulta; no se vuelve a anular el Pago |
| Caja ABIERTA / RECHAZADA | Sección Mostrador sin validar → detalle o editor actuales | Mantiene sus reglas y controles existentes |
| Cobro de mostrador sin validar | Detalle de su caja | Su cancelación existente; no se ofrece editor de manual para un cobro reservado |
| Caja VALIDADA | Ojo → detalle | Consulta; pedidos directos de corrección como Cashflow se rechazan |
| Liquidación PAGADA | Ojo → liquidación | Consulta; permanece protegida |
| Liquidación sin pagar | Sección separada → detalle/editor existente | Comisión abre su ajuste final; otros tipos conservan sus acciones existentes |
| Original directo anulado / contraasiento | Ojo → detalle e historial | Sin edición ni otra eliminación |

Mostrador y liquidaciones sin pagar tienen paginación propia y no se suman a los
totales confirmados de Cashflow. Se conservaron las columnas Registró/Val. de T28.

El editor comparte vista y componente de pesos con el alta existente. Su lógica
de rubros se trasladó sin cambiarla a `resources/js/cashflow-movimiento.js`;
no se agregó JavaScript dentro de vistas. Los importes usan T24/T30.

## Historial, atomicidad y permisos

Migración nueva `2026_10_12_180000_create_cashflow_correcciones.php`:
versión del movimiento, fecha de anulación, vínculo único al original y tabla de
correcciones con antes/después, administrador, fecha, acción y motivo opcional.
No reemplaza el creador del movimiento por quien lo corrigió.

`CashflowCorreccionService` valida actor ADMIN activo, datos y origen; bloquea el
movimiento antes de escribir. Una clave de operación permite reconocer el reenvío
del mismo formulario; reutilizarla con otros datos se rechaza. Una versión vieja
no puede pisar un cambio. Original, contraasiento e historial comparten transacción.

Eliminar conserva el valor del original y lo contrarresta en el mismo medio,
subrubro, fecha y clasificación congelada. El original y el contraasiento quedan
visibles; no se excluye el original del total y se resta otra vez por error.
La fecha del registro de auditoría es la de la corrección; la fecha contable del
contraasiento es la del movimiento. Las anulaciones de Pago mantienen su flujo actual.

Los GET/PUT/DELETE nuevos están dentro de `ensure.admin.web`, con autenticación y
cuenta activa. Otros roles son rechazados aunque escriban directamente la URL.
El servicio también rechaza actores no autorizados. API REST continúa deshabilitada.

Regla escrita en [Caja y Cashflow V5](../../02-contratos/Wings-Contrato-Caja-Cashflow-V5.md).

## Defecto de Historial corregido para cumplir la consistencia

El código anterior mezclaba egresos operativos positivos con egresos directos
negativos y mostraba estos últimos como entradas verdes. Está en la captura real
de etapa 1: [internet +$23,500.75](evidencia-t27/capturas/historial-actual-escritorio.png).

Se normaliza el neto del egreso directo al mismo criterio del operativo. El gasto
se muestra como salida; su contraasiento, como entrada. Los totales de egresos
ahora coinciden con Cashflow/Inicio/Reportes para el mismo indicador.
No se cambió la diferencia existente entre resultado del negocio, aportes/retiros,
saldo inicial y fondos todavía sin validar.

## Pruebas y evidencia

- Antes de implementar: **13 pruebas, 13 fallas**, incluyendo rutas ausentes,
  otros roles por pedido directo y el signo/suma de egreso de Historial.
- T27: **16 pruebas / 168 aserciones** de recorrido, datos, importes, permisos,
  historial, falla de auditoría, saldo, cambio de medio/subrubro y protección de liquidaciones.
- Concurrencia: **2 pruebas / 24 aserciones**, conexiones MariaDB con IDs diferentes.
  La segunda espera en `FOR UPDATE`, no ejecuta escrituras, y tras el commit el
  reintento no duplica la edición ni el contraasiento.
- Captura Laravel: **1 prueba / 33 aserciones**, fuera de la suite permanente.
  Solicitudes reales de edición/eliminación en `wings_testing_codex`; GET sin
  escrituras, referencias protegidas intactas y 403 de otros roles.
- Suite completa: **687 aprobadas, 2 omitidas; 6588 aserciones, 536,25 s** en
  `wings_testing_codex`, 689 pruebas. Los tres números que controla
  `DocumentacionNoMienteTest` se actualizaron.
- Primera corrida completa: 686 aprobadas/2 omitidas y una falla del guardián CSP,
  porque el script extraído bajó el total real de 13 a 12. Se bajó el límite y se
  repitió la suite completa, sin fallas. No se desactivó el control.
- Navegador: **36 visitas, 6 controles y 0 fallos; 53 PNG**. Íconos 28×28,
  enlace real hacia Editar, selección guardada conservada y aviso de tercer decimal.
- Sintaxis PHP y compilación/limpieza de vistas correctas; recursos compilados.

[Visor de todas las capturas finales](evidencia-t27/final/visor.html).

- [Tabla e íconos, escritorio](evidencia-t27/final/capturas/cashflow-listado-escritorio.png).
- [Editor y botones, celular](evidencia-t27/final/capturas/editar-directo-botones-marco-360.png).
- [Historial antes/después, celular](evidencia-t27/final/capturas/historial-correcciones-marco-360.png).
- [Liquidación sin pagar, editor existente](evidencia-t27/final/capturas/liquidacion-editor-marco-360.png).
- [Historial, importes y signos](evidencia-t27/final/capturas/historial-importes-marco-360.png).
- [Login de control](evidencia-t27/final/capturas/login-marco-360.png).

Las respuestas son HTML de rutas reales de Wings, con datos ficticios. Chrome
usa los recursos guardados en `evidencia-t27/final/build/`; marco con iframe de
360 dentro de una ventana grande y login como control. El navegador solo admite
GET y recibe respuestas ya exportadas: no modifica el sitio del club.
Las liquidaciones de muestra sin detalles calculan cero y registran el monto
pactado mediante el ajuste existente; no se fabrica un total de detalles distinto.

Reproducción:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test
php vendor/phpunit/phpunit/phpunit docs/06-pruebas/PRU-04/evidencia-t27/final/CapturaImplementacionT27Test.php
$env:NODE_PATH='C:/Users/cafre/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules'
node docs/06-pruebas/PRU-04/evidencia-t27/final/tomar-capturas.mjs
```

## Límites y lo que no se probó

- Sin teléfono físico, VPS, producción, sitio de prueba ni cambio de su reloj.
- No se presentan los dos ensayos de conexiones como dos solicitudes HTTP
  paralelas: se fuerza timeout sin escritura y se reintenta después del commit.
  El doble envío HTTP se comprueba aparte en las pruebas del recorrido.
- No se cambió ni se volvió a probar íntegramente el negocio de cobro, cancelación
  de caja o cálculo de liquidaciones: se verifican sus enlaces y protecciones aquí.
- El rollback global de migraciones antiguas falló en un índice de
  `movimientos_operativos`, durante la limpieza inicial del ensayo concurrente.
  No se corrigió esa migración ajena: el ensayo reconstruye únicamente su base
  descartable y marca su preparación para el corredor siguiente.
- La diferencia previa de permisos de edición de cajas por propietario permanece
  documentada en la propuesta. T27 no altera esos permisos.
- No hubo verificación independiente de otro agente ni aprobación de Carlos a
  estas capturas finales. La aprobación anterior corresponde a la propuesta.

## Entrega

Capturas finales a Carlos; después, verificación de reglas por otro agente.
T27 debe quedar **a_verificar**, sin autocierre ni despliegue.
