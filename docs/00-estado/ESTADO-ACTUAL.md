# Wings — Estado actual

> **Actualizado:** 09/09/2026
> **Plan vigente:** `docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md`,
> version 2026-09-08.v4.
> Si otro documento contradice este estado, no improvisar: verificar y corregir.

## 1. Estado general

Las ocho evaluaciones historicas se conservaron sin cambios de contenido en
`docs/07-evaluacion/Evaluaciones previas/`; el plan vigente permanece en la carpeta padre.

Wings esta publicado en `https://wings.gestionar-te.com.ar`, pero el gate final de
produccion no esta firmado. La base del servidor quedo preparada para que el club
cargue sus datos por pantalla y Vanina ya tiene una cuenta ADMIN. Carlos informo
el 09/09 que el club ya carga datos reales; el alcance no fue inspeccionado.

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

**El club ya esta cargando datos reales.** Carlos lo informo el 09/09. Desde ese momento
la base del servidor deja de ser un estado de entrega vacio y pasa a contener personas,
cobros y operacion real. No fue revalidada por SSH desde esta computadora: el alcance de
lo cargado es desconocido para la documentacion.

Consecuencias inmediatas: no correr `CatalogosSeeder` ni ningun seeder contra esa base
(FIN-01 hoy pega sobre datos reales), no usarla para pruebas destructivas, y tratar el
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
| **Tests** | **140 pruebas**, 753 aserciones, verde sobre MariaDB el 10/09 |
| Roles | ADMIN, OPERATIVO y PROFESOR; superadmin protegido |
| Cobranza | ADMIN y OPERATIVO entran; PROFESOR rechazado |
| Alumnos | CRUD, plan vigente, fecha de alta y grupo validado contra deporte |
| Cobros | COB-01 verificada por navegador; alcance confirmado el 10/09 (resumen por medio, no arqueo nuevo). Quedan COB-02 a COB-04 |
| Caja | Apertura, movimientos, cierre, rechazo, validacion y cancelacion |
| Cashflow | Integra cajas validadas y saldo inicial; significado de “Balance” pendiente de decision |
| Clases | Asistencias atomicas; editar clase no repite control de superposicion |
| Liquidaciones | Generacion, cierre, pago y recibos; concurrencia e historia pendientes antes del 25/09 |
| Carga inicial | **Dos importadores, a proposito.** `wings:importar-padron` (10/09) es el del arranque: lleva todo el padron con DEBE por alumno y cierra el mes de corte. `wings:importar-deuda-inicial` sigue para cargar deuda suelta sobre una base en marcha; no sirve para el arranque porque el alumno ausente se asume sin deuda |
| Dump | Fuera de Git e ignorado; `DemoSeeder` ya no lo exporta |
| PHP | `composer audit` sin avisos el 08/09 |
| JavaScript | `npm audit` informa 11 paquetes; falta clasificar uso y alcanzabilidad |
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

1. **FDS-04:** pantallas corregidas. FDS-03 pausada hasta redefinir su objetivo.
2. **COB-02 a COB-05:** cambio de plan, parcial con descuento y
   cancelacion/reintento.
3. **FIN:** recibos, historia, balance y concurrencia financiera.
4. **SEG:** npm, sesiones, despliegue, recuperacion, alertas, CI y CSP.
5. **PRU:** recorrido humano completo, proceso mensual y gate.
6. **ENT:** pedidos concretos de Carlos.
7. **POS:** reportes y evolucion posterior; no bloquean por aparecer en una evaluacion.

Los criterios y dependencias estan en el plan vigente. La version HTML marcable es
`docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html`.

## 7. Decisiones pendientes de Carlos

- Si un pago anulado cuenta como primer pago comercial, despues de reproducir COB-04.
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
