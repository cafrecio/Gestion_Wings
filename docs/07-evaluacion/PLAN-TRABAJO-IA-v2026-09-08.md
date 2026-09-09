# Wings — Plan de trabajo para IA

> **Version:** 2026-09-08.v4
> **Fecha de corte:** 8 de septiembre de 2026
> **Commit evaluado:** `97fb840`; cruce documental hasta `ad0491f`
> **Fuentes principales:** `Evaluacion Codex 8-9-26.md`, `Evaluacion Claude 8-9-26.md`,
> bitacoras y commits del 5 al 8 de septiembre.
> **Estado:** plan vigente. Reemplaza los indices anteriores como orden de trabajo.

## 0. Reglas de uso

1. Leer `AGENTS.md`, `CLAUDE.md`, ambas bitacoras y este documento antes de ejecutar.
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

## 2. Bloque 0 — cerrar lo hecho el fin de semana

### FDS-01 · Sincerar documentos de estado — CERRADA 08/09

**Ejecuta:** Codex CAB. **Estado:** cerrada y pendiente de verificacion por Claude.
**Diseño:** no.

- Corregir `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md` contra lo
  verificado el 8/9.
- Deben reflejar: servidor en `9fdd03d`, Vanina ADMIN creada, base de entrega minima,
  129 pruebas sobre MariaDB, dump retirado, Cloudflare activo y Cobranza accesible al
  OPERATIVO.
- Quitar como abiertos: motor SQLite, dump, H-DI-01, boton Cobrar, doble plan activo y
  las cifras viejas de pruebas.
- Registrar como abiertos: npm, monitoreo, limites del backup, cobros del bloque 1 y
  procedimiento reproducible de entrega.

**Aceptacion:** ningun documento vigente contradice esos hechos ni presenta una
verificacion historica como actual.

**Resultado:** `ESTADO-ACTUAL.md`, `PLAN-PRODUCCION.md` y `CHECKLIST-CARLOS.md`
quedaron alineados al corte 08/09. Los bloques del plan de agosto dejaron de figurar
como orden vigente.

### FDS-02 · Revalidar servidor y operacion del fin de semana — CERRADA 09/09/2026

**Ejecuta:** Codex CyE; acceso por Personal recuperado el 09/09. **Prioridad:** inmediata.

- Confirmar commit desplegado, migraciones, modo produccion y preflight.
- Confirmar el estado minimo de la base sin exponer datos personales ni credenciales.
- Confirmar que scheduler y backups siguen ejecutandose.
- Confirmar por una falla controlada que la copia externa produce una señal observable.
- Verificar el monitoreo externo y operativo en funcionamiento; no inferirlo del repositorio.

**Aceptacion:** evidencia fechada en bitacora y sin secretos. Lo no comprobado queda
marcado como tal.

**Evidencia ya existente — no volver a resolverla desde cero:**

| Parte | Commit que la registro |
|---|---|
| Servidor, Cloudflare, scheduler y backups | `3470114` |
| Deploy, migraciones y preflight | `d859c6e` |
| Base minima de entrega y usuarios | `306fa19` |
| Revalidacion integral del 08/09 | `4e1674e` |

**Avance del 08/09:** monitor HTTPS externo activo en Better Stack con email; heartbeat
de scheduler y heartbeat de backup creados. En el repositorio quedaron los wrappers
para informar exito/fallo y Telegram sin versionar secretos. La prueba aislada pasa.

**Cierre 09/09:** scripts de `a3ddd7f` desplegados dentro de `81f27ef`; cron y
respaldo operativo instalados con helper. Configuracion secreta fuera de Git.
Preflight: 12 controles correctos; migraciones Ran. Fallos controlados del
scheduler (11:27 GMT-3) y copia Drive (11:28), seguidos de recuperacion real.
Copia Drive correcta 14:29:33 UTC. Monitor HTTPS y ambos heartbeats Up.
Carlos confirmo email y Telegram. Evidencia detallada en LOG-CODEX.md.
No se simulo caida HTTPS. La base minima conserva evidencia historica del 07/09,
no se volvio a inspeccionar su contenido; corrida mensual del 01/09 no demostrada.

### FDS-03 · Hacer reproducible el estado minimo de entrega — PAUSADA 09/09

Carlos ordeno frenarla: la base ya contiene carga real. Lo siguiente conserva el
alcance anterior como contexto, NO es una orden ejecutable. Requiere redefinicion.

**Ejecuta:** IA; decision de Carlos si requiere automatizacion. **Prioridad:** alta.

- Documentar exactamente que se conserva y que se limpia para preparar una entrega.
- No crear un seeder de datos reales ni borrar datos por inferencia.
- Decidir con Carlos si alcanza un procedimiento manual versionado o si hace falta un
  comando idempotente y protegido contra produccion.

**Aceptacion:** otra maquina puede reconstruir el mismo estado sin memoria del chat ni
scripts temporales.

### FDS-04 · Revalidar lo corregido durante la prueba humana

**Ejecuta:** IA y Carlos. **Prioridad:** antes del bloque 4.

- Confirmar por pantalla Cobranza para ADMIN y OPERATIVO; PROFESOR rechazado.
- Confirmar boton Cobrar funcional y rubros reservados protegidos.
- Conservar como evidencia separada lo que ya paso y lo que se vuelve a probar.

## 3. Bloque 1 — cobros que pueden informar exito con datos incorrectos

Estas tareas se ejecutan en este orden. Cada reproduccion debe mirar pantalla, respuesta,
filas concretas y saldos; un test unitario aislado no reemplaza el flujo web.

### COB-01 · Monto con separador de miles — CORREGIDA 09/09

**Estado:** reproducida y corregida por Claude CAB. Pendiente de verificacion por otro
agente o por Carlos en pantalla. **Diseño:** no se toco ninguna vista.

**Mecanismo confirmado:** el script de la vista va en `@push('scripts')` y se registra
durante el parseo; `ds-app.js` entra por `@vite` como modulo y se registra despues. Al
enviar, el handler de la vista corre primero y arma `new FormData(...)` cuando el campo
todavia dice `28.000`; la limpieza de `stripMoneyInputs` llega tarde. En el servidor
`is_numeric("28.000")` es `true` y `(float)` lo convierte en `28`. Cobraba 28 en lugar de
28.000, sin error y con mensaje de exito.

**Correccion:** normalizacion en el servidor antes de validar, en
`CajaWebController::pagar()`. No depende del orden de carga de scripts y no toca vistas.
Regresion en `CobrarPrimeraCuotaWebTest::test_el_cobro_web_interpreta_el_monto_con_separador_de_miles`,
que envia el payload real del navegador. Alcance auditado: era el unico formulario roto,
porque es el unico que arma `FormData` en un handler de `submit`.

Verificado que la normalizacion cubre uno y dos separadores y el valor ya limpio:
`28.000`, `1.500.000`, `1.234.567` y `30000` quedan correctos. El caso de dos
separadores antes lo rechazaba la validacion, porque `is_numeric("1.500.000")` es
`false`; ahora entra bien.

**Verificacion 09/09, Codex CyE:** comparacion real por Chrome en bases descartables
de `caa4976^` y `caa4976`. Request conserva `28.000` y `1.500.000`; antes guarda 28
o rechaza 422, despues importes correctos en cadena y PDF. Resumen $1.528.000.
Reporte: `docs/06-pruebas/COB-01-VERIFICACION-2026-09-09.md`.
**Pendiente:** aclarar «arqueo» del pedido (solo se encontro resumen por medio,
sin importe contado/diferencia). Freno §6b; no declarar cierre ni despliegue.

1. Reproducir un cobro mostrado como `30.000` y comprobar el importe persistido.
2. Escribir regresion que reproduzca el payload real del navegador.
3. Corregir el orden o la normalizacion para que el servidor nunca interprete `30.000`
   como 30.
4. Probar importes con uno y dos separadores, decimales y entrada sin formato.

**Aceptacion:** pantalla, pago, deuda, caja y recibo coinciden en 30.000.

### COB-02 · Cambio de plan elegido fuera del formulario

**Estado:** verificado en codigo; falta reproduccion de pantalla. **Diseño:** toca
`resources/views/caja/cobrar.blade.php`, requiere autorizacion.

1. Reproducir que la seleccion no llega como `nuevo_plan_id`.
2. Vincular el control al formulario sin alterar el diseño.
3. Probar cambio de plan mas cobro como una sola transaccion.

**Aceptacion:** el alumno termina en el plan elegido, el monto del periodo es correcto y
un rechazo del cobro no deja el plan cambiado.

### COB-03 · Parcial de otro periodo durante primer pago con descuento

**Estado:** ambos informes verificaron el camino en codigo; falta reproducirlo en base.

1. Caso: mes de alta con descuento y segundo periodo pagado parcialmente.
2. Confirmar que `ajustarDeudas()` no reduzca el monto original del periodo sin descuento.
3. Agregar regresion con saldo pendiente exacto.

**Aceptacion:** solo el mes de alta recibe descuento; el segundo periodo conserva su
monto original y saldo restante.

### COB-04 · Cancelar y volver a cobrar la primera cuota

**Estado:** informes en desacuerdo sobre la consecuencia final. **No corregir sin
reproduccion.**

1. Reproducir cobro con descuento, cancelacion y segundo cobro.
2. Separar el efecto de `existePagoPrevio()` del monto ya guardado en la deuda.
3. Carlos decide si un pago anulado cuenta como antecedente a efectos comerciales.

**Aceptacion:** regla escrita, prueba automatizada y resultado visible coherente. No
adoptar la afirmacion de que siempre cobra el mes entero sin demostrarla.

### COB-05 · Cierre conjunto del circuito de cobro

- Probar sin descuento, cada tramo de descuento, parcial, varios periodos, cambio de plan,
  cancelacion y segundo cobro.
- Verificar importes en deuda, pago, imputaciones, movimiento, recibo y estado de cobranza.

**Aceptacion:** suite completa verde sobre MariaDB y recorrido humano corto sin diferencias.

## 4. Bloque 2 — integridad financiera e historia

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **FIN-01** | Evitar que `CatalogosSeeder` desproteja `Cuotas` y `Sueldos` | Alta | Dos corridas conservan `es_reservado_sistema=true` |
| **FIN-02** | Vincular recibo al pago exacto, no por texto/fecha/importe | Alta | Dos cobros iguales con medios distintos generan recibos correctos |
| **FIN-03** | Preservar o reconstruir imputaciones visibles al anular | Alta | PDF anulado conserva periodos, importe, motivo y marca ANULADO |
| **FIN-04** | Definir con Carlos que significa “balance” filtrado | Alta | Contrato define saldo acumulado o resultado del periodo antes de tocar codigo |
| **FIN-05** | Pago concurrente de liquidacion (AUD-018) | Antes del 25/09 | Dos conexiones reales producen un solo pago y un solo egreso |
| **FIN-06** | Comision historica (AUD-020) | Antes del 25/09 | Cambios posteriores del alumno no alteran liquidacion historica |
| **FIN-07** | Cobrar contra condonar simultaneamente | Alta | Locks compartidos; no queda deuda condonada y cobrada a la vez |
| **FIN-08** | Reglas de revision de cobranza | Media | Carlos define parciales, observaciones e importe historico; luego pruebas |
| **FIN-09** | Limites de fechas manuales | Media | Contrato y validaciones impiden imputaciones fuera del rango decidido |
| **FIN-10** | Solapamiento al editar clases (AUD-019) | Alta | Editar aplica el mismo control que crear |
| **FIN-11** | Concurrencia real de cobrar/cancelar/validar | Alta | Pruebas con dos conexiones; `MoneyLockingTest` queda descrito como estructural |

## 5. Bloque 3 — seguridad, despliegue y recuperacion

| ID | Tarea | Prioridad | Condicion de cierre |
|---|---|---:|---|
| **SEG-01** | Clasificar los 11 avisos npm por uso y alcanzabilidad | Alta | Matriz paquete/uso/riesgo/version; actualizar sin romper build ni suite |
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
