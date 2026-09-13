# Wings — Estado actual

> Continuidad reorganizada el 12/09, sin revalidación funcional:
> [Resumen común](RESUMEN-ARRANQUE.md) · [Protocolo](PROTOCOLO-CONTINUIDAD.md).
> Logs activos abreviados; originales íntegros en `docs/99-archivo/bitacoras/2026-09-12/`.
> Leer esta página por sección de tarea, no para reconstruir toda la historia.

> **Actualizado:** 12/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

**ENT-03 CERRADA el 12/09/2026:** Favicon definitivo de patín artístico alado aprobado por Carlos (Opción 1 - Zoom Máximo 95% superficie). Se abandonó la paleta previa negra/roja/blanca en favor de la identidad de patín artístico (bota blanca con taco, alas fucsia y cian sobre degradé claro hielo/lavanda, ruedas oscuras con contraste óptimo en pestaña). Generados `public/favicon.ico` (multi-res 16, 32, 48), `public/favicon-32x32.png`, `public/favicon-16x16.png`, `public/apple-touch-icon.png`, `public/android-chrome-192x192.png`, `public/android-chrome-512x512.png` y `public/site.webmanifest`. Vinculados en el layout raíz `resources/views/layouts/ds-app.blade.php`. Suite de 166 tests (1026 assertions) verde al 100%.

**ENT-04 CERRADA el 12/09/2026:** Ojo para ver/ocultar contraseña implementado en alta y edición de usuarios (`resources/views/usuarios/_form.blade.php`), con botones independientes para "Contraseña" y "Confirmar contraseña" (respetando simetría de columnas y localidad de control). Manejador desacoplado en `resources/js/ds-app.js` compilado con Vite sin alterar CSP (26 scripts incrustados y 24 manejadores HTML en `CspSinCodigoIncrustadoTest`). Diseño autorizado por Carlos el 07/09.

**ENT-05 CERRADA el 13/09/2026:** Acceso directo a 1 clic al recibo de cuota: 1) en la confirmación de cobro (banner flash tras volver al listado de cajas) con botón `Recibo` (`target="_blank"`, `inline=1`), y 2) en la ficha del alumno (`alumnos/show.blade.php`), con botón `Recibo` en cada fila del historial de pagos (soportando tanto pagos activos como anulados). Auto-dismiss de 3s en `ds-app.js` configurado para no descartar banners con enlaces interactivos. Protegido contra rol Profesor. Diseño autorizado por Carlos el 07/09. Suite verde con nueva prueba `CobroReciboAccesoTest`.

**SEG-11 en avance el 13/09/2026:** Migrados los 14 manejadores de evento `onclick` en línea de las vistas Blade hacia `resources/js/ds-app.js` mediante atributos `data-*` (`MANEJADORES_PERMITIDOS` bajó a 10). Unificado el validador en vivo de disponibilidad / nombre repetido en `ds-app.js` para `niveles` y `tipos-caja`, eliminando sus scripts en línea y reduciendo `BLOQUES_SCRIPT_PERMITIDOS` de 26 a 24 en `CspSinCodigoIncrustadoTest`. Preparado para campos combinados. Grupos y usuarios conservan sus scripts por dependencias específicas. Assets recompilados con Vite. Suite completa en 214 tests (1315 assertions).

**COB-05, COB-09 y FIN-02 VERIFICADAS 11/09 sobre e921e5d:** 15 cobros por
Chrome en una misma base sintetica, incluida subida/bajada con descuento y
asistencia, cancelacion/recobro y medios de pago distintos. Suite 161/977.
Supera el freno de a9795c6. Evidencia: `docs/06-pruebas/COB-05-CIERRE-2026-09-11.md`.

FDS-04 verificada el 11/09/2026: Cobranza por rol, dos cobros simples en navegador
y bloqueo manual completo de rubros reservados y sus hijos. Carlos revoco la
excepcion de editar observaciones. Evidencia: `docs/06-pruebas/FDS-04-2026-09-11.md`.

**Bloque 1 de cobros verificado al 11/09, incluido COB-05.** COB-01 a COB-04 y COB-06 a COB-08
verificadas por navegador e integradas en `main`. COB-04 incluyo una correccion
posterior verificada en copia aislada: pantalla y cobro usan ahora un solo calculo del
importe con descuento. COB-05 cruzo cambio de plan, descuento y asistencia sobre
main con COB-09 integrado. Evidencia en `docs/06-pruebas/`.

**Bloque FIN al 11/09:** FIN-02 corregida (el recibo toma el medio de pago del
movimiento exacto), verificada por navegador. FIN-01 no aplica por decision de Carlos.
FIN-03 implementada el 11/09: anulaciones nuevas conservan periodos/importes y
motivo para el PDF, sin imputaciones activas. Contrato Recibos V2; V1 historica.
Suite 166/1026 y PDF real revisado. Pendiente verificacion cruzada de Claude y deploy.

**Nada de esto esta desplegado:** el servidor sigue en `81f27ef`, anterior a las ocho
correcciones. No hay riesgo inmediato porque el club todavia no cargo alumnos.

Las ocho evaluaciones historicas se conservaron sin cambios de contenido en
`docs/07-evaluacion/Evaluaciones previas/`; el plan vigente permanece en la carpeta padre.

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Al 11/09, segun
Carlos, **todavia no hay ningun alumno cargado**; el alcance del resto de la carga no
fue inspeccionado.

## 2. Servidor — corte SSH 08/09 y avance por consola 09/09

| Que | Estado |
|---|---|
| Commit desplegado | `81f27ef`, fast-forward verificado por consola el 09/09 |
| Diferencia con `main` al corte | El servidor incorporo documentos y scripts hasta `81f27ef`; los commits posteriores requieren sincronizacion |
| Plataforma | AlmaLinux 9, PHP 8.2.33, Laravel 12.68.0 |
| HTTPS | Activo |
| Cloudflare | Proxy activo; acceso web directo al servidor cerrado |
| Migraciones | Sin pendientes en la ultima verificacion |
| Scheduler | Registrado y ejecutado cada minuto; deuda mensual programada para dia 1 a las 06:00 |
| Backups | Diarios, cifrados, rotados y copiados a Drive |
| Monitoreo | FDS-02 cerrada 09/09: cron y respaldo instalados; fallos y recuperaciones probados. Email y Telegram recibidos por Carlos. Monitor HTTPS y ambos heartbeats Up |

No se pudo demostrar que la corrida mensual del 01/09 haya producido resultado: no
quedo un log que lo pruebe o descarte.

## 3. Base de entrega del servidor

**La base del servidor tiene carga real en curso, pero todavia sin alumnos.** El 09/09
Carlos informo que el club empezo a cargar datos; el 11/09 aclaro que **Vanina aun no
subio ningun alumno**. Lo que haya son catalogos (deportes, grupos, planes y similares),
sin personas, deudas ni cobros. No fue revalidado por SSH desde esta computadora.

Consecuencias inmediatas: no correr `CatalogosSeeder` ni ningun seeder contra esa base
(puede quitar la proteccion de Cuotas y Sueldos; FIN-01 se dio por no aplica porque ningun despliegue lo corre), no usarla para pruebas destructivas, y tratar el
respaldo como la unica red — FDS-03 ya no puede "reconstruir" el estado, porque el
estado ahora incluye datos que solo existen ahi.

Estado con el que fue preparada el 07/09, con respaldo previo — **historico, ya superado
por la carga humana**:

- Sin alumnos, deudas, pagos, clases ni operacion real.
- Se conservan `Cuotas` con `Cuota Mensual` y `Sueldos` vacio porque el codigo los
  busca por nombre.
- Se conservan las configuraciones existentes y las tres reglas de primer pago.
- Deportes, niveles, grupos, planes, tipos de caja y demas catalogos los carga el
  usuario por pantalla.
- Existe una cuenta superadmin protegida y una cuenta ADMIN de Vanina.

FDS-03 esta pausada por Carlos desde el 09/09: ese estado minimo es historico.
No crear un seeder ni limpiar datos reales; redefinir la tarea antes de ejecutarla.

## 4. Estado confirmado del repositorio

| Area | Estado |
|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade y Vite |
| **Tests** | **214 pruebas**, 1311 aserciones; suite completa el 13/09 en wings_testing. Pasan contraseñas, recibos, FIN-10, FIN-11, ENT-05 y el seeder de primera carga |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | COB-05 y COB-09 verificadas en main e921e5d: 15 cobros por navegador. FIN-02 verificada: medios correctos en recibos. Evidencia COB-05-CIERRE-2026-09-11.md |
| Caja | Apertura, movimientos, cierre, rechazo, validacion y cancelacion |
| Cashflow | Integra cajas validadas y saldo inicial; significado de “Balance” pendiente de decision |
| Clases | FIN-10 implementada y probada: edición atómica con control de profesores/presentes, fechas y liquidación cerrada; migración pendiente de deploy |
| Liquidaciones | Generacion, cierre, pago y recibos. FIN-05 corregida el 11/09: dos pagos a la vez de la misma liquidacion ya no registran dos egresos. Historia (FIN-06) pendiente. El plan ataba ambas al 25/09, pero sin alumnos cargados no habra liquidacion real esa fecha |
| Carga inicial | **Dos importadores, a proposito.** `wings:importar-padron` (10/09) es el del arranque: lleva todo el padron con DEBE por alumno y cierra el mes de corte. `wings:importar-deuda-inicial` sigue para cargar deuda suelta sobre una base en marcha; no sirve para el arranque porque el alumno ausente se asume sin deuda |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | SEG-01: Axios retirado y lock actualizado; audit cero, build y 154 pruebas/920 aserciones en copia aislada el 11/09. Sin deploy |
| CSP | Report-only; quedan 26 bloques script en 24 vistas y 24 manejadores inline |
| Diseño | Protegido por `AGENTS.md` y hook de commit |

## 5. Lo cerrado del 5 al 7 de septiembre

- Suite migrada de SQLite a MariaDB.
- Dump retirado y segunda puerta de exportacion eliminada.
- Falla de copia nocturna a Drive corregida y respaldos revalidados.
- Cloudflare configurado con confianza acotada.
- Precio de planes y frecuencias obligatorias protegidos.
- Descuento de primera cuota limitado al mes de alta.
- Importador de deuda inicial validado y carga de prueba preparada.
- Rubros `Cuotas` y `Sueldos` protegidos en la aplicacion.
- Acceso y menu de Cobranza corregidos para OPERATIVO.
- Menu reorganizado y desplegado en `9fdd03d`.
- Base del servidor preparada y cuenta ADMIN de Vanina creada.

## 6. Orden de trabajo

FDS-01 y FDS-02 cerrados. Alertas reales recibidas el 09/09. El orden restante es:

1. **FDS:** FDS-04 verificada el 11/09. FDS-03 pausada hasta redefinir su objetivo.
2. **COB-05:** verificada junto a COB-09 sobre main e921e5d. Bloque 1 verificado.
3. **FIN:** FIN-02 verificada; FIN-03 implementada, pendiente revision cruzada. Siguen historia, balance y
   concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- Que significa “Balance” filtrado en Cashflow.
- Como resolver revisiones con parcial, observaciones e importe historico.
- Limites temporales de movimientos manuales.
- Significado de DEUDOR sin pagos y sin saldo pendiente.
- Tratamiento contable de la inscripcion configurable.
- Logo, paleta y favicon del club.

## 8. Riesgos y limites conocidos

- El rollback del deploy no revierte migraciones.
- Preflight corre despues de reabrir el sitio.
- La restauracion probada importa SQL; no reconstruye sola archivos y configuracion.
- El control historico del restore compara conteos, no contenido financiero completo.
- Un fallo de Drive conserva la copia local y salida 0; desde el 09/09 produce
  alerta diferenciada por Better Stack y Telegram, probada y recibida.
- `MoneyLockingTest` verifica texto del codigo, no concurrencia real.
- Cambiar contraseña no revoca por si solo sesiones y `remember_token`.
- Los errores de recibos pueden devolver el mensaje tecnico de la excepcion.
- Aritmetica monetaria usa `float` en parte del dominio; el daño no esta demostrado.
- `View::composer('*')` ejecuta una consulta global para el badge de clases.

## 9. Contradicciones abiertas

FIN-11, 12/09: reproducido y corregido cancelar/validar en ambos órdenes
(dejaba pago ANULADO y deuda pagada 0 con 10.000 en cashflow). Corregidas también
las esperas tardías de cobrar/cancelar y validar/cobrar. Seis casos pasan, 103
aserciones aisladas. Pausa levantada el 13/09: Codex reejecutó la suite completa
en wings_testing, 187 pruebas / 1206 aserciones, sin fallas. Implementada y probada;
sin deploy. Evidencia: ../06-pruebas/FIN-11-CONCURRENCIA-2026-09-12.md.

FIN-07: pausa resuelta por Carlos el 12/09. Solo ADMIN perdona el saldo pendiente,
conservando original, pagos e imputaciones. Condonación transaccional con bloqueo
de la deuda, compartido con el cobro. Tres casos con conexiones reales y prueba
negativa sin bloqueo. Contrato Wings-Contrato-Condonacion-V1.md. Sin deploy.

FIN-03: freno documental resuelto el 11/09. Carlos autorizo Recibos V2 con
FIN-02 y FIN-03; V1 queda como antecedente. No se reconstruyen periodos ya
borrados en anulaciones antiguas. Migracion probada solo en base descartable.

COB-03 y total (COB-06), verificados el 10/09 en `cob-total` (`5238825`), no mergeada:
ambos casos, septiembre existente/virtual, muestran y registran $29.600 con cartel
70%; septiembre conserva $18.000 pendientes. Sin descuento, $38.000 correctos.
Cadena financiera y PDF verificados; suite de esa rama: 133 pruebas, 726 aserciones.
El defecto sigue en main hasta integrar; no confundir verificacion con despliegue.
Evidencia: `docs/06-pruebas/COB-03-VERIFICACION-2026-09-10.md`.

| Tema | Estado real | Proxima accion |
|---|---|---|
| `dia_generacion_deuda` editable | Confirmado 09/09: existe como fila de configuracion y **ningun codigo la lee**. El scheduler usa dia 1 fijo en `routes/console.php:12` | Definir si gobierna la tarea o se retira de pantalla |
| Alumno sin plan en la corrida mensual | `GenerarDeudasMensualesCommand:77-81` lo saltea: no genera deuda, no entra a revision, solo un `warn` que muere en el cron | Que caiga en la cola de revision con motivo propio |
| Recalcular una liquidacion puede cambiar el total de una ya cerrada | `LiquidacionService::recalcularLiquidacion()` chequea si esta cerrada **afuera** de la transaccion y sin tomar la fila. Si otra pestaña la cierra en ese instante, el recalculo cambia el total igual. `cerrarLiquidacion()` y `eliminarLiquidacion()` tienen el mismo patron, con daño nulo o que requiere tres acciones a la vez. El doble clic lo frena el anti doble envio de `ds-app.js`; queda el caso de dos pestañas o dos personas | Encontrado en el barrido de FIN-05. Corresponde a FIN-11 |
| `pagos.monto_base` se guarda mal | `crearPago()` lo reconstruye dividiendo lo cobrado por el porcentaje. Con una seña en el mes de alta da 10.000 / 0,7 = 14.285; con el mes de alta y otro mes en el mismo cobro divide tambien el que no tenia descuento. **Nadie lo lee hoy**: ni pantallas, ni recibos, ni reportes. Es una trampa para el rediseño del recibo, que querria mostrar el precio sin descuento | Definir que tiene que valer en un cobro con seña o con varios meses antes de que algo lo use |
| Descuento a un alumno de carga inicial cobrado en su propio mes de alta | `calcularReglaPrimerPago()` solo exige que el mes de alta este entre los periodos cobrados. Un alumno importado con deuda inicial de su mes de alta recibe el descuento al pagarla. La prueba existente solo cubre cobrarle **otro** mes | Carlos define si un alumno traido de la carga inicial puede recibir descuento de primer pago alguna vez |
| Wings no tiene arqueo | `cajas_operativas` no guarda importe contado ni diferencia; el cierre nunca pregunta cuanta plata hay. `PERMISOS-ROLES.md:84` y `Wings-Contrato-Punitorios-Mora-V1.md:267` usan la palabra como si existiera, y el segundo tiene un criterio de aceptacion —"no hay diferencia"— que hoy no se puede evaluar | Carlos define si la caja debe pedir conteo al cerrar, o se corrigen los contratos |
| Balance filtrado de Cashflow | Mezcla saldo inicial historico con movimientos del periodo | Carlos define saldo acumulado o resultado del periodo |
| Estado minimo de entrega | El club ya carga datos reales | FDS-03 pausada por Carlos el 09/09; redefinir, no limpiar |
| Tope de 1200px en guia de diseño | `app.css` no lo implementa | Decidir guia o implementacion; no tocar sin autorizacion |

## 10. Fuentes vigentes

| Necesidad | Ruta |
|---|---|
| Plan para IA | `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md` |
| Plan marcable para Carlos | `docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html` |
| Evaluacion Codex | `docs/07-evaluacion/Evaluacion Codex 8-9-26.md` |
| Evaluacion Claude | `docs/07-evaluacion/Evaluacion Claude 8-9-26.md` |
| Bitacora Codex | `docs/00-estado/LOG-CODEX.md` |
| Bitacora Claude | `docs/00-estado/LOG-CLAUDE.md` |
| Acciones de Carlos | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos | `docs/02-contratos/` |
| Pruebas funcionales | `docs/06-pruebas/` |
