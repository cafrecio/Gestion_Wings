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
| **FIN-04** | Significado de balance — **DEFINICIÓN CERRADA 22/09** | Alta | [Contrato de Reportes, enmienda FIN-04](../02-contratos/Wings-Contrato-Reportes-V1.md): saldo acumulado separado del resultado del período y proyecciones; confirmados/sin confirmar. Gastos del club fuera del resultado por deporte, dentro del total del negocio. Implementación de Reportes pendiente en POS-01 |
| **FIN-05** | Pago concurrente de liquidacion (AUD-018) — **CERRADA 12/09** | Antes del 25/09 | Dos conexiones reales producen un solo pago y un solo egreso. La red de seguridad en la base **se descarta**: ver abajo |
| **FIN-06** | Comision historica (AUD-020) — **CERRADA 13/09** | Antes del 25/09 | Cambios posteriores del alumno no alteran liquidacion historica |
| **FIN-07** | Cobrar contra condonar simultaneamente — **IMPLEMENTADA Y PROBADA 12/09** | Alta | Carlos define perdonar solo saldo, conservando pago e imputaciones. Dos conexiones reales: parcial, completo y condonación primero; sin escrituras del segundo mientras espera. Regresión falla sin bloqueo. Suite 169/1065; [evidencia](../06-pruebas/FIN-07-CONCURRENCIA-2026-09-12.md). Sin deploy |
| **FIN-08** | Reglas de revision de cobranza — **CERRADA 21/09** | Media | Carlos 17/09: la revision es 100% tarea del OPERATIVO. **19/09:** "Inactivo" ya no condona; la cola pregunta una sola cosa —¿le generamos la cuota del mes?— y no toca lo anterior (`RevisionCobranzaNoTocaElPasadoTest`). **21/09:** ruta y menu abiertos a ADMIN y OPERATIVO (grupo `reject.profesor.web`, enlace en "Dia a dia"); condonar sigue solo ADMIN. Carlos 21/09: el pago parcial ya no aplica (nada lo pisa) y "Continua" usa el precio vigente del plan, igual que la generacion mensual. `RevisionCobranzaOperativoTest` 6 pruebas, 4 fallan con la ruta vieja. Sin deploy. Planteo futuro: [suspender la cuota sin dar de baja](../05-pendientes/SUSPENSION-TEMPORAL-Y-VACACIONES.md) **Verificada por Gemini 21/09** ([informe](../06-pruebas/FIN-08-VERIFICACION-2026-09-21.md)): 9 puntos OK. **Retoques de index.blade.php autorizados por Carlos e implementados 22/09 (Gemini):** banner visible de `$errors` (`ds-flash--error`), filtros con `filtros-row` y `flex-wrap` apilables en celular, y retiro del `<script>` duplicado (`BLOQUES_SCRIPT_PERMITIDOS` baja a 21 en CSP test). |
| **FIN-09** | Limites de fechas manuales — **IMPLEMENTADA Y PROBADA 19/09** | Media | Carlos 17/09, precisado el 19/09: sin fechas futuras (`before_or_equal:today`); **solo el mes en curso se carga sin aviso**. Desde el mes anterior inclusive exige confirmación en pantalla antes de guardar: en caja y cashflow mediante banner y botón `Confirmar` con campo oculto `confirmar_fecha_vieja` (sin scripts nuevos); en cobro de cuotas mediante 409 y confirmación del usuario. El movimiento conserva su fecha real y entra en la caja abierta actual; las cajas ya controladas no se modifican. Punto marcado con comentario TODO para futuras alertas por Mail/Telegram al ADMIN. 8 pruebas nuevas en `LimiteFechasMovimientosTest`. Consecuencia aceptada por Carlos: el reporte del mes de la fecha real cambia. Sin deploy **Revision cruzada Claude CyE 21/09:** caja (alta y edicion), cobro de cuota y Cashflow cumplen la regla y avisan. Pagar una liquidacion aceptaba cualquier fecha; **Carlos 21/09: entra en FIN-09**. Aplicado: futura rechazada, mes anterior pide confirmar y avisa (`PagoLiquidacionFechaTest`, 3 de 4 fallan con el codigo anterior). Regla escrita en el contrato de Caja-Cashflow §3.6. La API ignora la regla pero esta apagada. |
| **FIN-10** | Solapamiento al editar clases — **IMPLEMENTADA 13/09** | Alta | Edición atómica, todos los profesores y presentes; fechas pasadas protegidas, horario pasado con motivo salvo liquidación CERRADA. Sin deploy; [evidencia](../06-pruebas/FIN-10-EDICION-2026-09-13.md) |
| **FIN-11** | Concurrencia real de cobrar/cancelar/validar — **IMPLEMENTADA Y PROBADA 13/09** | Alta | Seis cruces con dos conexiones pasan; MoneyLockingTest descrito como estructural. Suite completa reejecutada: 187 pruebas / 1206 aserciones. Pausa levantada; sin deploy. Recálculo/cierre de liquidaciones sigue pendiente separado. [Evidencia](../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md) |
| **FIN-12** | Cancelar liquidación cerrada no pagada — **CERRADA 21/09** | Hoy: 13/09/2026 | Solo ADMIN cancela con motivo obligatorio (mínimo 5 caracteres); conserva historial y auditoría; pagadas intocables; asistencias bloqueadas mientras esté cerrada (HORA y COMISIÓN) y desbloqueadas al cancelar; nueva liquidación vinculada por `reemplazada_por_id`; concurrencia con pago protegida con bloqueo en dos órdenes (`CancelarLiquidacionCerradaTest` y `CancelarLiquidacionConcurrenteTest`, 11 tests nuevos). Sin deploy |
| **FIN-13** | Liquidacion por hora segun duracion — **CERRADA 17/09** | Antes de la primera liquidacion real | Decision de Carlos: tarifa x minutos / 60 (clase de 1,5 h a $5.000 = $7.500). Calculo unico para liquidacion y vista previa; tarifa congelada en `valor_hora_aplicado` y minutos en cada detalle; lo liquidado antes no se recalcula; clase sin duracion valida frena. Diseno autorizado por Carlos: pantalla y recibo muestran formato "1 h 20 min" y subtotales/totales con centavos. Pruebas `LiquidacionHoraPorDuracionTest` y `LiquidacionHoraVistaYReciboTest` (238/1429). Sin deploy |
| **FIN-14** | Punitorios por mora — **PENDIENTE; el contrato ya esta escrito** | Media | Carlos lo señaló el 21/09 al mirar Configuración: falta el recargo por pagar tarde. El [contrato](../02-contratos/Wings-Contrato-Punitorios-Mora-V1.md) quedo escrito el 06/09 —dos claves nuevas (`mora_dia_desde` en 10, `mora_porcentaje` en 0), recargo congelado el dia que se aplica, condonable, visible en el recibo y en un rubro reservado `Punitorios`— pero **la tarea nunca entro a este plan**, por eso se perdio dos semanas. El propio contrato advierte que **no se da por cumplido si las claves quedan sin motor que las lea**, como `dia_generacion_deuda`: agregar las dos claves sueltas seria repetir ese defecto. Enmienda aprobada 22/09: §5 usa `cargos_alumno`, origen `punitorio:deuda:{id}`; ENT-01 incorpora estructura, no motor ni configuración de mora |

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
| **SEG-02** | Revocar sesiones y `remember_token` al cambiar clave — **CERRADA 12/09** | Alta | Se borran las filas de `sessions` del usuario y se rota su `remember_token`; quien se cambia la clave a si mismo sigue autenticado con sesion nueva. Dientes: sin la revocacion, las dos pruebas que la cubren se ponen en rojo y las otras seis siguen verdes. **Falta desplegar** |
| **SEG-03** | Unificar minimo de contraseña web/consola — **CERRADA 12/09** | Media | Unificado en 12 en `UsuarioWebController::MINIMO_CONTRASENA`; consola y vista lo toman de ahi. Ademas de la coherencia se fija el piso: `test_el_minimo_no_baja_de_doce`, porque las pruebas escritas contra la constante pasaban con cualquier valor. **Falta desplegar** |
| **SEG-04** | Ejecutar preflight antes de `artisan up` — **CERRADA 12/09** | Alta | Un preflight fallido nunca publica el release. Prueba propia: `tests/Deployment/deploy_preflight_antes_de_publicar_test.sh`. Dientes comprobados: con el orden viejo la prueba se pone en roja. **Falta desplegar** |
| **SEG-05** | Ocultar excepciones crudas de recibos — **CERRADA 12/09** | Alta | Usuario ve mensaje util; detalle queda solo en log. Probado en ReciboErrorSanitizadoTest |
| **SEG-06** | Restauracion integral — **IMPLEMENTADA 13/09** | Alta | `restaurar.sh` repone las tres piezas: antes reponia solo la base y descartaba `storage.tgz` con el directorio temporal, asi que un servidor perdido volvia sin sus recibos. El ensayo compara todas las tablas de las dos bases (antes una lista fija de 15 de 35, sin `pago_deuda_cuota`), verifica archivos y exige `APP_KEY`. Prueba propia con dientes: con el script anterior fallan 5 comprobaciones. **Ensayo real hecho el 22/09** contra el respaldo del dia: se restaura completo (base, archivos, configuracion). Sin deploy |
| **SEG-07** | Verificacion fuerte del backup — **IMPLEMENTADA 13/09** | Alta | El ensayo compara importes, no cantidad de filas: pagos por estado, imputaciones, deuda pendiente y cobrada, movimientos, cashflow y liquidaciones. Suma cuatro invariantes (deudas descuadradas, imputaciones huerfanas, cashflow sin caja, liquidaciones descuadradas) corridas en las dos bases: si la incoherencia esta en ambas, el respaldo copio bien y se avisa como problema de **datos**, no de respaldo. Dientes: con solo SEG-06, un respaldo con importes truncados y el mismo conteo pasaba en verde. **Ensayo real hecho el 22/09**: correcto en 28 tablas, importes, archivos y configuracion, despues de corregir dos fallas del propio script que encontro (una consulta contra `saldo_pendiente`, que no es columna, y contar `sessions`, que cambia sola). Pendiente: cuando el club opere, comparar contra la base viva de dia va a dar diferencias legitimas (cobros posteriores a las 03:15); hay que guardar un manifiesto en el propio respaldo y comparar contra eso. Sin deploy |
| **SEG-08** | Fallo de copia externa observable | Cerrada 09/09, FDS-02 | Copia fallida alertada y recuperacion Drive comprobada |
| **SEG-09** | Monitoreo de sitio, scheduler y backup | Cerrada 09/09, FDS-02 | HTTPS Up; fallos scheduler/backup recibidos por Carlos en email y Telegram |
| **SEG-10** | Integracion continua | Media | Push/PR ejecuta suite MariaDB y build sin depender de memoria humana |
| **SEG-11** | CSP definitiva | Supervisada | Report-only, inventario cero de JS bloqueable, recorrido visual y recien luego bloqueo |
| **SEG-12** | Credenciales del servidor: renovar, guardar y acceso de Carlos al panel — **POSTERGADA por Carlos 22/09** para una sesion dedicada despues de terminar Wings; explicar sin tecnicismos | Media | **Relevado 22/09:** SSH solo con clave, contrasenas apagadas, firewall activo con politica de rechazo (el servicio `csf` figura `failed` desde junio pero las 189 reglas estan cargadas). Punto mas expuesto: **el panel CWP abierto a todo internet** (2030/2031 y 2082-2087). Se entra como `root`. Claves privadas en CAB y CyE sin contrasena propia: cifrar disco. **Destino decidido por Carlos: administrador de contrasenas de Google** (portable, no se pierde), importando un CSV que se borra enseguida; Google no permite escribir ahi por programa. Requiere verificacion en dos pasos en la cuenta de Google. La clave de descifrado de respaldos ya esta fuera del servidor (`VPS/CREDENCIALES.txt`); **no rotarla**, deja ilegibles los respaldos viejos. Propuesta: usuario propio para Carlos y panel solo para sus computadoras. | Pedido de Carlos, 22/09: (1) renovar todas las credenciales del servidor y guardarlas juntas en una carpeta fuera del repositorio (hoy estan en `VPS/CREDENCIALES.txt`, fuera del repo; el checklist ya pedia pasarlas a un administrador de contrasenas); (2) cambiar el nombre de usuario — **falta precisar cual**: el de entrada al panel o el del sistema; (3) que Carlos entre directo al panel. El panel del servidor es **CWP (Control Web Panel)**, no cPanel. Ninguna credencial entra al repositorio. Al cambiar claves, revisar que no se corten el respaldo, el monitoreo ni el acceso `ssh vps` de CAB y CyE |

`SEG-11` nunca habilita CSP bloqueante en un solo paso. Los estilos inline siguen la
decision vigente de `AGENTS.md`.

## 6. Bloque 4 — prueba integral y gate

### PRU-01 · Base conocida para la prueba — CERRADA 17/09

Definir una base descartable reproducible, sin copiar datos reales ni depender de un
dump. No confundir la carga humana de entrega con un seeder decorativo.

Cierre (Carlos, 17/09): la base es `PrimeraCargaCompletaSeeder` sobre una base recien
migrada — 60 alumnos con plan, 4 profesores, 7 cuentas, 2 cajas con saldo, sin deudas ni
movimientos; 12 pruebas en `PrimeraCargaCompletaSeederTest`. Instructivo:
[SEEDER-PRIMERA-CARGA.md](../06-pruebas/SEEDER-PRIMERA-CARGA.md). El primer paso de PRU-02
es cargar la deuda con el padron (`wings:exportar-padron` / `wings:importar-padron`,
[instructivo](../06-pruebas/CARGA-PADRON-SALDO-INICIAL.md)): son comandos de consola.

### PRU-02 · Recorrido humano completo

Ejecutar los flujos de ADMIN, OPERATIVO y PROFESOR: catalogos, alumno, deuda, cobro,
parcial, cancelacion, caja, validacion, cashflow, clase, asistencia, liquidacion y
recibos. Retomar los pasos antes bloqueados, pero sobre la version corregida.

**Carlos 21/09:** es esperable retocar diseno durante la prueba. Todo retoque respeta
siempre el design system (`docs/03-diseno-ui/wings-design/SKILL.md`, `DESIGN-RULES.md`,
componentes `ds-*` y `x-ds.*`) y necesita su autorizacion de diseno.

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
| **ENT-01** | Inscripción configurable — IMPLEMENTADA, sin deploy | Aprobada 22/09: una por DNI, corte fijo 23/09/2026 por ingreso real; $5.000 obligatorio, inscripción primero, sin comisión. Corrección sin pagos auditada; con pagos rechazada. [Regla](../05-pendientes/ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md) y [evidencia](../06-pruebas/ENT-01-INSCRIPCION-2026-09-22.md). Última incorporación a PRU-02; Claude revisa y actualiza servidor |
| **ENT-02** | Rediseño completo del recibo — HECHO 16/09 | Cuota: normal, multi-mes y anulado verificados en pantalla el 13/09. Liquidación: plantilla aprobada el 11/09 aplicada en `f86c730` (16/09), dos hojas A5 con anexo de clases o alumnos; motivo de anulación ya no sale duplicado. Verificado por Gemini; ajuste de duración y decimales en FIN-13 (`29f2858`) |
| **ENT-03** | Favicon | CERRADO 12/09/2026: Patín alado aprobado por Carlos; implementado en public/ y ds-app.blade.php |
| **ENT-04** | Ojo de contraseña en alta/edicion de usuarios | CERRADO 12/09/2026: Botones de ojo independientes en contraseña y confirmar contraseña (_form.blade.php); manejador en ds-app.js sin alterar CSP (26 scripts); diseño autorizado el 07/09 |
| **ENT-05** | Acceso directo al recibo despues de cobrar y desde la ficha | CERRADO 13/09/2026: Acceso a 1 clic mediante botón Recibo (target=_blank, inline=1) en la confirmación de cobro (ds-flash) y en cada fila del historial de pagos en la ficha del alumno; diseño autorizado el 07/09 |
| **ENT-06** | Avisos de cajas/revisiones/liquidaciones para ADMIN — IMPLEMENTADA 22/09 | Decisión 22/09: resumen diario a las 08:00 (hora Argentina) vía AvisoAdminService (email y Telegram), activo durante PRU-02. Contenido: cajas cerradas sin validar (total y más vieja), revisiones pendientes (más vieja) y liquidaciones cerradas sin pagar y abiertas (sin sumar al total a pagar). Comando avisos:resumen-diario en scheduler. 5 pruebas en AvisoAdminResumenDiarioTest |
| **ENT-07** | Lista de cobranza util para llamar | Despues de prueba humana; importe, antiguedad, periodos y contacto |
| **ENT-08** | Tablero administrativo util | Despues de prueba humana; no copiar sin criterio el tablero operativo |
| **ENT-09** | Carga del saldo inicial de todo el padron | Herramienta lista 10/09. **Le toca a Carlos**: exportar el padron, Vanina marca DEBE por alumno, reimportar. Cierra el mes de corte. Procedimiento en `docs/06-pruebas/CARGA-PADRON-SALDO-INICIAL.md` |
| **ENT-10** | Manuales de primera carga — PENDIENTE | Destacar ingreso real frente a fecha de carga y corte de inscripción; ejemplos para usuarios no técnicos. Ver detalle debajo |
| **ENT-11** | Excepciones justificadas (contrato de cobranza §8, §9 y §9b) | Separada de ENT-06 el 22/09. El contrato pide motivo obligatorio y aviso al ADMIN en cobro parcial, cobro que deja impago un mes anterior, deudor o alumno nuevo desde la 3ª clase y exceso de plan. No existe en el código: ninguno pide motivo. Unificar los motivos dispersos en un registro único (§11). Después de PRU-02 |

### ENT-10 — Manuales de primera carga — PENDIENTE

Guías prácticas para usuarios y preparación. **MUY IMPORTANTE:** explicar fecha real de ingreso, corte fijo e inscripción; nunca confundir ingreso con día de carga. [Alcance y aceptación](../05-pendientes/ENT-01-INSCRIPCION-Y-PRIMERA-CARGA.md). Depende de ENT-01 y pantallas verificadas.

## 8. Bloque 6 — posterior, no bloquea la entrega inicial

| ID | Tema | Alcance |
|---|---|---|
| **POS-01** | Reportes acordados | Implementar despues de la prueba humana segun `Wings-Contrato-Reportes-V1.md` |
| **POS-02** | Auditoria e historial de contactos | Trazar cambios administrativos y gestiones de cobranza |
| **POS-03** | Recuperacion de acceso | Evaluar autoservicio y MFA segun necesidad real |
| **POS-04** | Tarifas | Historial de precios y aumentos masivos |
| **POS-05** | Evolucion de producto | Evaluar ficha medica, familias, portal, pagos online, WhatsApp, bancos, multi-sede, torneos e indumentaria; no son compromisos actuales |
| **POS-06** | Clases particulares — PENDIENTE de implementación | [Contrato V1](../02-contratos/Wings-Contrato-Clases-Particulares-V1.md), decisiones 13/09; criterios y bordes en el contrato. [Ficha](../05-pendientes/CLASES-PARTICULARES.md). Sin implementación ni despliegue en este turno |
| **POS-07** | Ubicaciones, canchas y liquidaciones de clubes — PLAN DOCUMENTADO; implementación pendiente | [Plan de acción v2026-09-21](PLAN-CANCHAS-LIQUIDACIONES-v2026-09-21.md), ocho etapas y matriz de aceptación. Bloques horarios completos, total manual protegido, costos solo ADMIN. Mes o fechas elegidas: solo clases dictadas al corte y pendientes de liquidar; sin repetir las de otro período, aunque estén sin pagar. Reporte Club → Deporte → Nivel. No confundir costo, liquidación y egreso; no implementar SaaS ni Reportes general por arrastre |

Medir antes de corregir cualquier riesgo de precision por `float`. AUD-025 solo entra
antes de agregar rutas destructivas hoy inexistentes.

## 9. Definiciones que este plan no reabre

- API REST apagada a proposito.
- Recibo no fiscal aceptado.
- Exportables fuera de la version inicial.
- El OPERATIVO trabaja sobre todo el dominio operativo, no solo “lo suyo”.
- Sueldos por persona/deporte es una decision del modelo.
- Carga inicial real es humana; no se inventan datos del club.
- Enmienda expresa 13/09, POS-06 pendiente: ADMIN cancela cerrada no pagada para revisar asistencias; pagada intacta, corrección compensatoria posterior.
- CSP gradual y diseño protegido.

## 10. Como marcar avances

Cada tarea pasa por: `PENDIENTE` → `EN CURSO` → `VERIFICADA` → `CERRADA`.

- **VERIFICADA:** otro agente o Carlos reprodujo el criterio de aceptacion.
- **CERRADA:** commit subido, documentos sincerados y entorno sincronizado.
- El HTML de Carlos es un tablero local; la fuente versionada del estado sigue siendo
  este archivo mas las bitacoras y los commits.
