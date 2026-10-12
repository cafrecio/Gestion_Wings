# T27 — Una sola entrada para corregir movimientos del dueño

Etapa 1, 12/10/2026. **Propuesta, no implementación de correcciones.**
Obligatoria antes de producción por decisión de Carlos. El diseño y las cinco
decisiones de abajo requieren su respuesta antes de empezar la etapa 2.

## Propuesta para mirar

**Usar Cashflow como pantalla única de entrada**, desde Plata → Cashflow.
Cada renglón identifica su origen y ofrece **íconos de acciones**, por pedido de Carlos:
lápiz para Editar, papelera para Eliminar, anulación para el pago y ojo para Ver.
Los nombres aparecen al pasar el mouse y están disponibles para lectores de pantalla.
No hay botón «Corregir». No crear otra
pantalla financiera con los mismos movimientos y totales.

- Directos, aportes y retiros: abrir su contexto desde el mismo listado; las nuevas
  acciones quedan apagadas hasta decidir edición, eliminación y trazabilidad.
- Cobros e inscripción: desde el renglón llegar al historial del alumno, que ya
  tiene la anulación con motivo. No cambiar solamente el asiento y dejar el pago
  o la deuda diciendo otra cosa.
- Cajas abiertas/rechazadas: acceso separado en la misma página, **Mostrador · sin
  validar**, hacia el detalle y la edición actuales. No sumarlas de nuevo a los
  totales confirmados de Cashflow.
- Caja validada y liquidación pagada: se muestran y se puede **Ver** su origen.
  Las correcciones de esos dos tipos están pendientes de decisión; la maqueta
  no decide que queden fuera definitivamente ni habilita una excepción.

[Visor de propuesta y pantallas actuales](evidencia-t27/visor.html).

Capturas principales, todas de Laravel renderizado en Chrome:

- [Escritorio, listado](evidencia-t27/capturas/propuesta-listado-escritorio.png).
- [Celular, listado dentro del marco de 360](evidencia-t27/capturas/propuesta-listado-marco-360.png).
- [Movimiento directo, celular](evidencia-t27/capturas/propuesta-directo-marco-360.png).
- [Cobro del dueño, celular](evidencia-t27/capturas/propuesta-cobro-del-dueno-marco-360.png).
- [Acceso a cajas sin validar, celular](evidencia-t27/capturas/propuesta-cajas-marco-360.png).
- [Login de control a 360](evidencia-t27/capturas/login-marco-360.png).

Los datos son ficticios. La propuesta conserva el diseño de Cashflow, sus filtros,
importes y las columnas Registró/Val. Agrega acceso a correcciones. Los botones
Editar/Eliminar del directo no ejecutan operaciones: están deshabilitados.
Los íconos del listado solo abren el contexto; tampoco modifican ni anulan datos.

## Qué permite hoy cada movimiento

Relevamiento del código y rutas alcanzables, corte `a4252db`. Los cuerpos de los
controladores/servicios se leyeron; no se dedujo conducta de nombres o informes viejos.

| Tipo | Dónde se registra y se ve | Corrección alcanzable hoy | Protección comprobada en el código |
|---|---|---|---|
| Directo en Cashflow, ingreso o gasto manual | `CashflowWebController::store` → `CashflowService::registrarMovimientoAdmin`; Cashflow e Historial | No hay editar/eliminar en web. Solo alta y listado | ADMIN activo; monto positivo con hasta dos decimales; fecha no futura; observación obligatoria. Subrubro reservado requiere su flujo propio. El servicio guarda egresos negativos |
| Aporte del dueño | Directo, subrubro clasificado APORTE | Misma ausencia de editar/eliminar que el directo | La clasificación se separa del resultado económico; no tratarlo como ingreso del negocio al corregir |
| Retiro del dueño | Directo, subrubro clasificado RETIRO | Misma ausencia de editar/eliminar que el directo | Misma separación del resultado; signo de egreso |
| Cuota cobrada por ADMIN sin caja | `PagoCuotaService::registrarPagoCuotaAdmin`; referencia PAGO_CUOTA en Cashflow; historial de la ficha | Botón **Anular** de la ficha → `POST /pagos/{id}/anular` | Solo ADMIN; motivo de 10–500 caracteres. Bloquea pago/alumno/imputaciones; rechaza pago ya anulado o vinculado a una caja. El pago y el asiento original se conservan; se restituye deuda y se crean asientos negativos con fecha de la anulación. Los importes de Pago son inmutables |
| Inscripción cobrada por ADMIN | Cargo y pago vinculados; también referencia PAGO_CUOTA en Cashflow, con subrubro Inscripción | Anulación del **pago**, desde la misma ficha; no existe una corrección independiente de una fila de inscripción | Si cuota e inscripción se pagaron juntas, pertenecen al mismo Pago: anularlo retira ambas imputaciones, conserva su detalle y contrarresta ambos asientos. No prometer corregir una sola parte sin decidirlo |
| Movimiento manual de caja ABIERTA/RECHAZADA | MovimientoOperativo; detalle de su caja, Movimientos e Historial | Editar desde el detalle; **Eliminar** usa DELETE y borra el manual | ADMIN puede operar todas; el controlador limita al operativo a su caja. Servicio toma bloqueo, exige ABIERTA/RECHAZADA y subrubro manual permitido OPERATIVO. No editar reservados. Eliminar rechaza movimientos con alumno o reservados; no pide motivo ni conserva ese renglón |
| Cobro/inscripción por caja ABIERTA/RECHAZADA | Pago y movimientos operativos asociados | **Cancelar** desde el detalle de esa caja | Motivo obligatorio; caja editable y pago vinculado; bloqueo de caja/pago/deudas; rechaza ya cancelado. Conserva Pago ANULADO, detalle y movimientos CANCELADO; quita imputaciones y restituye saldos. No usar eliminación manual |
| Lo reflejado de caja VALIDADA | `CashflowIntegracionCajaService`, referencia CAJA_OPERATIVA y vínculo movimiento_operativo_id | Consultar el detalle. No editar/cancelar en una validada por las rutas actuales | El reflejo solo se genera para VALIDADA y no se duplica si ya existe. **Rechazar también exige CERRADA con conteo**, no admite VALIDADA. No hay camino web actual para volver una validada a rechazado |
| Pago de liquidación | `LiquidacionPagoService::marcarComoPagada`, referencia LIQUIDACION en Cashflow; detalle del profesor | Consultar detalle/recibo. No corregir ese pago desde Cashflow | Fila bloqueada; pago idempotente; exige CERRADA, subrubro EGRESO/ADMIN y control del importe esperado. Cancelar rechaza PAGADA; eliminar rechaza CERRADA/CANCELADA. Contrato: pagada intacta; corrección económica en próxima liquidación |
| Contraasiento de anulación del dueño | Fila negativa en el rubro de ingreso original | Consultar el cobro anulado; no hay corregir ese contraasiento en web | No confundirlo con otro cobro ni anular dos veces el mismo Pago. El signo negativo resta del total |

Las cancelaciones del mostrador y las anulaciones del dueño son distintas:
movimiento CANCELADO que no suma, frente a original más asiento en contra.
La pantalla única debe respetar esa diferencia, no unificarla borrando registros.

### Alcanzabilidad y límites

- `routes/web.php` tiene para Cashflow únicamente GET listado, GET formulario y
  POST alta, dentro de `ensure.admin.web`. No hay PUT/DELETE de Cashflow web.
- La API REST está deshabilitada en `bootstrap/app.php`; métodos API existentes
  no constituyen un camino usable ni se habilitan por esta tarea.
- Fecha vieja al **crear** un directo o editar un manual de caja exige confirmar
  el aviso de que cambia el reporte de ese mes. Esto no es una decisión ya tomada
  sobre el límite temporal de las correcciones nuevas.
- `ReporteMensualService::movimientos` reúne Cashflow confirmado y operativos
  activos aún no reflejados. No sumar ambas copias de una caja validada.

## Comparación de las tres pantallas existentes

| Pantalla | Cobertura actual | Encaje para entrada única |
|---|---|---|
| Cashflow | Directos, cuotas/inscripción del dueño, liquidaciones y cajas validadas; identifica origen, registrador y validador | Recomendación: ya es solo ADMIN y permite seleccionar el asiento confirmado. Añadir acceso separado a cajas todavía editables |
| Movimientos | Solo MovimientoOperativo, no los directos ni pagos de liquidación de Cashflow | Para reunirlos habría que ampliar su fuente; además es compartida con OPERATIVO |
| Historial de caja | Operativos más Cashflow directo; excluye reflejos de caja para no duplicar; ADMIN ve cancelados y cualquier fecha | Tiene amplia cobertura, pero su DTO actual pierde IDs de origen para actuar. La usa también OPERATIVO; no restringirla globalmente al dueño ni quitar ese acceso |

La recomendación es de **interfaz**, no decide borrar, reabrir cajas, modificar
liquidaciones pagadas ni rehacer cierres. Las reglas dependen de las respuestas.

## Decisiones pendientes de Carlos

Primero: **¿aprobás Cashflow como entrada única y esta propuesta visual?**

1. **Rastro y motivo.** Para un directo mal cargado, ¿modificar el renglón guardando
   antes/después, o anular y cargar uno nuevo? Al eliminar, ¿borrado o asiento en
   contra? ¿Motivo obligatorio para ambas operaciones?
2. **Hasta cuándo.** ¿Se permite cualquier fecha o solo un período determinado?
   Poder ver cualquier fecha, ya aprobado para el dueño, no decide este permiso
   para modificarla.
3. **Caja validada.** ¿Queda solo para consulta o se define un nuevo camino de
   corrección? El supuesto de «seguir por rechazar» no existe hoy para VALIDADA:
   el servicio solo acepta CERRADA. No se habilita ese regreso sin tu decisión.
4. **Liquidación pagada.** ¿Queda solo para consulta, o entra con una regla nueva?
   El contrato vigente impide tocar una pagada; no lo saltamos por corregir Cashflow.
5. **Mes cerrado.** ¿La corrección modifica el resultado histórico con rastro, o
   se registra un ajuste en el período actual conservando aquel cierre? No se
   decide automáticamente al agregar Editar/Eliminar.

## Hallazgos que no se arreglaron en esta etapa

- VALIDADA no se puede rechazar hoy. Se deja visible en la pregunta 3 para no
  presentar ese camino como disponible.
- La edición/cancelación de caja del controlador limita al operativo a su caja;
  el contrato de permisos dice dominio compartido y solo reserva por propietario
  el cierre. Es una diferencia previa: T27 propone acceso ADMIN y no cambia esos
  permisos en esta etapa.
- Durante el relevamiento, Claude devolvió T30 por la portabilidad de sus pruebas.
  Después publicó la corrección y el cierre en `b4fdd6e`. La maqueta usa soporte
  publicado T24; no dependió de aquel archivo pendiente ni de cambios ajenos sin publicar.

## Evidencia, reproducción y lo que no se probó

`evidencia-t27/CapturaPropuestaT27Test.php` crea datos ficticios y llama controladores
y servicios existentes. Registra una ruta GET de propuesta **solo en el proceso de
prueba** y renderiza la maqueta Blade con datos reales de Cashflow y las cajas.
No instala rutas en `routes/web.php` ni modifica vistas de producción.

Ejecución propia:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php vendor/phpunit/phpunit/phpunit docs/06-pruebas/PRU-04/evidencia-t27/CapturaPropuestaT27Test.php
$env:NODE_PATH='C:/Users/cafre/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules'
node docs/06-pruebas/PRU-04/evidencia-t27/tomar-capturas.mjs
```

- 1 comprobación de captura, 19 aserciones: GET correctos, datos financieros
  iguales antes/después y 403 de OPERATIVO/PROFESOR en Cashflow y la propuesta.
- Navegador: 20 visitas sin errores JS ni fallos; 32 PNG incluyendo listado,
  paneles de consulta/corrección propuestos, cajas editables y login.
- Escritorio 1366 px; celular dentro de iframe 360 px, no recorte de ventana.
  Login como control. HTML original de Laravel en `html/`; assets congelados en `build/`.
- La prueba está fuera de la suite permanente. No cambió el conteo de 671.
  No se presenta este ensayo como la suite completa ni como prueba de etapa 2.
- No se ejecutaron correcciones nuevas, ni doble clic, ni nuevas reglas de saldo,
  fechas/cierres o permisos de escritura. No hubo teléfono físico, sitio de prueba,
  cambio de su reloj, despliegue ni envíos de mensajes reales.
- Las reglas nuevas y su contrato/pruebas/capturas finales quedan para etapa 2,
  **solo después de aprobación y decisiones de Carlos**. T27 no se cierra.
