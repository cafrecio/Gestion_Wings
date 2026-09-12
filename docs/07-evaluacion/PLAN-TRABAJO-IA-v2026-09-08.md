# Wings — Plan de trabajo para IA

> Edición compacta 12/09/2026 de la v4. Mismo orden y criterios; historia íntegra enlazada.
> No se han reejecutado verificaciones para esta reorganización.

> **Version:** 2026-09-08.v4
> **Fecha de corte:** 8 de septiembre de 2026
> **Commit evaluado:** `97fb840`; cruce documental hasta `ad0491f`
> **Fuentes principales:** `Evaluacion Codex 8-9-26.md`, `Evaluacion Claude 8-9-26.md`,
> bitacoras y commits del 5 al 8 de septiembre.
> **Estado:** plan vigente. Reemplaza los indices anteriores como orden de trabajo.

## 0. Reglas de uso

1. Aplicar el [protocolo común](../00-estado/PROTOCOLO-CONTINUIDAD.md): resumen, tres logs activos y este índice; después solo el detalle de la tarea.
2. Una tarea por vez y un commit por tarea. No mezclar correccion, rediseño y mejoras.
3. Reproducir primero todo hallazgo que las evaluaciones no reprodujeron.
4. Si el codigo contradice este plan, frenar segun `AGENTS.md` §6b.
5. Las tareas con vistas requieren autorizacion escrita de Carlos y las lecturas de diseño.
6. No usar `gestion_wings` ni la base del servidor para pruebas destructivas. Usar MariaDB descartable.
7. Cada cierre actualiza pruebas, contratos, estado, checklist y la bitacora del agente.
8. Claude verifica los commits de Codex y registra el resultado real en `LOG-CLAUDE.md`.

El índice HTML para Carlos conserva una copia local y permite trasladar las marcas
entre computadoras mediante un enlace o un archivo JSON exportado.

## 1. Indice ejecutivo

| Orden | Bloque | Objetivo | Salida |
|---:|---|---|---|
| 0 | Cierre del fin de semana | Sincerar servidor, base y documentos | Punto de partida reproducible |
| 1 | Cobros que pueden mentir | Reproducir y corregir tres caminos de cobro | Cobro, deuda y plan consistentes |
| 2 | Integridad financiera | Cerrar recibos, concurrencia e historia | Plata trazable e idempotente |
| 3 | Seguridad y recuperacion | Cerrar garantías exageradas y controles operativos | Recuperacion y alertas reales |
| 4 | Prueba integral | Recorrer el sistema completo con tres roles | Evidencia de aceptación actual |
| 5 | Entrega solicitada | Completar pedidos concretos de Carlos | Producto presentable y operable |
| 6 | Posterior | Reportes y evolución no bloqueante | Backlog separado |

## 2–3. FDS y COB — índice de tareas

Se conservan los estados declarados por los encabezados del corte, sin nueva verificación.
Para ejecutar una tarea, leer su ID en el [detalle íntegro](../99-archivo/bitacoras/2026-09-12/PLAN-TRABAJO-IA-v2026-09-08.md).
Allí se conservan completos criterios, límites, autorizaciones y evidencias; no leerlo entero al arrancar.
El [resumen común](../00-estado/RESUMEN-ARRANQUE.md) identifica diferencias históricas conocidas.

- FDS-01 · Sincerar documentos de estado — CERRADA 08/09
- FDS-02 · Revalidar servidor y operacion del fin de semana — CERRADA 09/09/2026
- FDS-03 · Hacer reproducible el estado minimo de entrega — PAUSADA 09/09
- FDS-04 · Revalidar lo corregido durante la prueba humana — VERIFICADA 11/09/2026
- COB-01 · Monto con separador de miles — VERIFICADA 10/09
- COB-02 · Cambio de plan elegido fuera del formulario — VERIFICADA 10/09
- COB-03 · Parcial de otro periodo durante primer pago con descuento — VERIFICADA 10/09
- COB-04 · Cancelar y volver a cobrar la primera cuota — CORREGIDA 10/09
- COB-05 · Cierre conjunto del circuito de cobro
- COB-06 · La pantalla anunciaba un total distinto del que se cobraba — CORREGIDA 10/09
- COB-07 · Al subir de plan la pantalla anunciaba menos de lo que se cobraba — CORREGIDA 10/09
- COB-08 · El descuento se aplicaba a la seña y cerraba el mes — VERIFICADA 10/09
- COB-09 · Al cambiar de plan con descuento la pantalla anunciaba otro importe — VERIFICADA 11/09

COB-05: VERIFICADA 11/09 sobre e921e5d, según reporte COB-05-CIERRE-2026-09-11.md.
Criterios de cobro: pantalla, pago, deuda, imputaciones, caja y PDF coherentes;
rechazos sin escrituras parciales. No sustituye los casos detallados del ID seleccionado.

## 4. Bloque 2 — integridad financiera e historia

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **FIN-01** | Evitar que `CatalogosSeeder` desproteja `Cuotas` y `Sueldos` — **NO APLICA 11/09** | — | Ver FIN-01 en el registro íntegro enlazado debajo |
| **FIN-02** | Vincular recibo al pago exacto, no por texto/fecha/importe — **VERIFICADA 11/09** | Alta | Dos cobros iguales con medios distintos generan recibos correctos |
| **FIN-03** | Preservar imputaciones visibles al anular — **VERIFICADA 12/09** | Alta | Revision cruzada hecha por Claude CyE: el detalle se guarda antes de borrar las imputaciones y en la misma transaccion; tres pruebas propias, incluida la que impide inventar periodos en anulaciones viejas. **Falta desplegar la migracion** |
| **FIN-04** | Definir con Carlos que significa “balance” filtrado | Alta | Contrato define saldo acumulado o resultado del periodo antes de tocar codigo |
| **FIN-05** | Pago concurrente de liquidacion (AUD-018) — **CERRADA 12/09** | Antes del 25/09 | Dos conexiones reales producen un solo pago y un solo egreso. La red de seguridad en la base **se descarta**: ver abajo |
| **FIN-06** | Comision historica (AUD-020) | Antes del 25/09 | Cambios posteriores del alumno no alteran liquidacion historica |
| **FIN-07** | Cobrar contra condonar simultaneamente | Alta | Locks compartidos; no queda deuda condonada y cobrada a la vez |
| **FIN-08** | Reglas de revision de cobranza | Media | Carlos define parciales, observaciones e importe historico; luego pruebas |
| **FIN-09** | Limites de fechas manuales | Media | Contrato y validaciones impiden imputaciones fuera del rango decidido |
| **FIN-10** | Solapamiento al editar clases (AUD-019) | Alta | Editar aplica el mismo control que crear |
| **FIN-11** | Concurrencia real de cobrar/cancelar/validar | Alta | Pruebas con dos conexiones; `MoneyLockingTest` queda descrito como estructural |

Detalles y evidencia FIN-01/02/03/05: buscar el ID en el
[registro íntegro](../99-archivo/bitacoras/2026-09-12/PLAN-TRABAJO-IA-v2026-09-08.md).
No cambia el orden ni los criterios de la tabla.

### FIN-05 · la red de seguridad en la base se descarta — 12/09

Se propuso, y quedo ofrecida a Carlos en `CHECKLIST-CARLOS.md`, una regla unica en
`cashflow_movimientos` sobre `(referencia_tipo, referencia_id)` para que la base
rechazara un segundo egreso de la misma liquidacion.

**Esa regla rompe la validacion de cajas. Verificado el 12/09 por Claude CyE.**

`CashflowIntegracionCajaService::reflejarCajaEnCashflow()` (linea 53) crea **un asiento
por cada movimiento operativo** de la caja, y **todos llevan el mismo**
`referencia_tipo = CAJA_OPERATIVA` **y el mismo** `referencia_id` (el id de la caja).
Una caja con diez cobros produce diez filas con la misma referencia. Con esa regla,
**validar cualquier caja con mas de un movimiento fallaria**.

La version que si funcionaria tendria que aplicar solo a `LIQUIDACION`. MariaDB no
admite indices unicos parciales: habria que agregar una columna generada y poner el
indice sobre ella. Es una migracion estructural sobre la tabla de plata, en la **base
definitiva**, justo antes de que entren los datos reales.

**Decision propuesta: no hacerlo.** El bloqueo de `lockForUpdate()` ya resuelve el
problema real y esta probado con dos conexiones. La red de seguridad cubre un escenario
hipotetico —que alguien retire el bloqueo— y su costo es tocar la estructura de
`cashflow_movimientos` en produccion.

Estado al 12/09: cero liquidaciones con mas de un egreso en la base local. Si alguna vez
se decide hacerlo, el momento es antes de la carga de Vanina: despues, agregar una regla
a una tabla con plata adentro es mas delicado.

## 5. Bloque 3 — seguridad, despliegue y recuperacion

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **SEG-01** | Retirar Axios y actualizar dependencias — VERIFICADA 11/09 | Alta | Audit cero, build y 154 pruebas/920 aserciones; cobros Chrome correctos. Ver SEG-01-REVISION-2026-09-11.md. Sin deploy |
| **SEG-02** | Revocar sesiones y `remember_token` al cambiar clave | Alta | Sesion y cookie anteriores dejan de autenticar |
| **SEG-03** | Unificar minimo de contraseña web/consola | Media | Una regla documentada y pruebas en ambos caminos |
| **SEG-04** | Ejecutar preflight antes de `artisan up` | Alta | Un preflight fallido nunca publica el release |
| **SEG-05** | Ocultar excepciones crudas de recibos | Alta | Usuario ve mensaje util; detalle queda solo en log |
| **SEG-06** | Restauracion integral | Alta | Ensayo aislado repone base, archivos y configuracion y deja Wings utilizable |
| **SEG-07** | Verificacion fuerte del backup | Alta | Compara contenido financiero e incluye imputaciones, no solo conteos |
| **SEG-08** | Fallo de copia externa observable | Cerrada 09/09, FDS-02 | Copia fallida alertada y recuperacion Drive comprobada |
| **SEG-09** | Monitoreo de sitio, scheduler y backup | Cerrada 09/09, FDS-02 | HTTPS Up; fallos scheduler/backup recibidos por Carlos en email y Telegram |
| **SEG-10** | Integracion continua | Media | Push/PR ejecuta suite MariaDB y build sin depender de memoria humana |
| **SEG-11** | CSP definitiva | Supervisada | Report-only, inventario cero de JS bloqueable, recorrido visual y recien luego bloqueo |

`SEG-11` nunca habilita CSP bloqueante en un solo paso. Los estilos inline siguen la
decision vigente de `AGENTS.md`.

## 6. Bloque 4 — prueba integral y gate

### PRU-01 · Base conocida para la prueba

Definir una base descartable reproducible, sin copiar datos reales ni depender de un
dump. No confundir la carga humana de entrega con un seeder decorativo.

### PRU-02 · Recorrido humano completo

Ejecutar los flujos de ADMIN, OPERATIVO y PROFESOR: catalogos, alumno, deuda, cobro,
parcial, cancelacion, caja, validacion, cashflow, clase, asistencia, liquidacion y
recibos. Retomar los pasos antes bloqueados, pero sobre la version corregida.

### PRU-03 · Estados de cobranza

Revalidar los 60 casos preparados. Antes de usar “DEUDOR” para reclamar, Carlos debe
confirmar el significado del alumno sin pagos y sin saldo pendiente.

### PRU-04 · Ensayo del proceso mensual

Ejecutar en ambiente descartable la generacion, revisiones y cambio de mes. Verificar
que la falta de asistencias produce la cola decidida y que una falla genera alerta.

### PRU-05 · Gate de entrega

No declarar listo hasta cerrar COB, FIN y SEG de prioridad alta, obtener suite verde,
recorrido humano firmado y verificacion actual del servidor.

## 7. Bloque 5 — pedidos concretos y operacion diaria

| ID | Pedido | Dependencia |
|---|---|---|
| **ENT-01** | Inscripcion configurable del alumno nuevo | Carlos confirma regla contable; migracion crea la clave |
| **ENT-02** | Rediseño completo del recibo | Bloqueado hasta recibir logo y paleta del club; requiere autorizacion de diseño |
| **ENT-03** | Favicon | Carlos entrega o aprueba el recurso grafico |
| **ENT-04** | Ojo de contraseña en alta/edicion de usuarios | Login ya lo tiene; JS externo compatible con CSP; diseño autorizado |
| **ENT-05** | Acceso directo al recibo despues de cobrar y desde la ficha | Resolver FIN-02 primero; diseño autorizado |
| **ENT-06** | Avisos de cajas/revisiones/liquidaciones para ADMIN | Definir contenido con Carlos; diseño autorizado |
| **ENT-07** | Lista de cobranza util para llamar | Despues de prueba humana; importe, antiguedad, periodos y contacto |
| **ENT-08** | Tablero administrativo util | Despues de prueba humana; no copiar sin criterio el tablero operativo |

## 8. Bloque 6 — posterior, no bloquea la entrega inicial

| ID | Tema | Alcance |
|---|---|---|
| **POS-01** | Reportes acordados | Implementar despues de la prueba humana segun `Wings-Contrato-Reportes-V1.md` |
| **POS-02** | Auditoria e historial de contactos | Trazar cambios administrativos y gestiones de cobranza |
| **POS-03** | Recuperacion de acceso | Evaluar autoservicio y MFA segun necesidad real |
| **POS-04** | Tarifas | Historial de precios y aumentos masivos |
| **POS-05** | Evolucion de producto | Evaluar ficha medica, familias, portal, pagos online, WhatsApp, bancos, multi-sede, torneos e indumentaria; no son compromisos actuales |

Medir antes de corregir cualquier riesgo de precision por `float`. AUD-025 solo entra
antes de agregar rutas destructivas hoy inexistentes.

## 9. Definiciones que este plan no reabre

- API REST apagada a proposito.
- Recibo no fiscal aceptado.
- Exportables fuera de la version inicial.
- El OPERATIVO trabaja sobre todo el dominio operativo, no solo “lo suyo”.
- Sueldos por persona/deporte es una decision del modelo.
- Carga inicial real es humana; no se inventan datos del club.
- Liquidaciones cerradas no se reabren; las correcciones son compensatorias.
- CSP gradual y diseño protegido.

## 10. Como marcar avances

Cada tarea pasa por: `PENDIENTE` → `EN CURSO` → `VERIFICADA` → `CERRADA`.

- **VERIFICADA:** otro agente o Carlos reprodujo el criterio de aceptacion.
- **CERRADA:** commit subido, documentos sincerados y entorno sincronizado.
- El HTML de Carlos es un tablero local; la fuente versionada del estado sigue siendo
  este archivo mas las bitacoras y los commits.
