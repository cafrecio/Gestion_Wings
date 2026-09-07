# Wings - Estado Actual

> Actualizado: 2026-09-06 (frecuencia obligatoria verificada localmente; demas verificaciones conservan sus fechas)
> Fuente de verdad del estado del proyecto. Si otro documento lo contradice, se corrige
> el otro documento o se registra la contradiccion aca antes de implementar.
> El indice de pendientes incorpora la verificacion de `PENDIENTES-260901.md`,
> conservando las verificaciones posteriores del repositorio.

## Resumen Ejecutivo

Wings es una aplicacion Laravel para gestionar un club deportivo: alumnos, deportes,
grupos, planes, cuotas, deudas, pagos, caja operativa, cashflow, clases, asistencias,
profesores, liquidaciones, usuarios y configuracion.

**El sistema esta publicado en `https://wings.gestionar-te.com.ar`, pero NO en
produccion.** Esta en linea para pruebas: sin datos reales, sin usuarios del club y sin
el gate firmado.

## Que version corre en el servidor

**Verificado por SSH el 06/09.** Lo que dice esta pagina sobre funcionalidades hechas
se refiere al **repositorio**, no a lo que esta publicado.

| Que | Valor |
|---|---|
| Commit desplegado | **`7abf327`** (07/09 02:46) |
| Repositorio | **al dia**: el servidor corre exactamente lo mismo que `main` |
| PHP / Laravel | 8.2.33 / 12.68.0 |
| Migraciones pendientes | 0 |

**El atraso de 34 commits se cerro el 06/09.** El servidor ya tiene la primera cuota
con descuento (`846347f`), el cobro de la primera cuota de un alumno nuevo
(`f066c42`), la condonacion de deuda (`5f77c85`), el precio de plan mayor a cero
(`824fdd8`) y el saldo inicial por tipo de caja (`c09a3e1`).

### El sitio esta detras de Cloudflare desde el 06/09

Los tres pasos estan hechos y verificados: la aplicacion confia en Cloudflare
(`trustProxies`), el subdominio esta con proxy, y **el servidor solo acepta trafico
web que venga de Cloudflare** — entrando por la IP directa no responde.

Detalle y forma de revertir en `VPS/ESTADO-SERVIDOR.md`. Lo que toca al codigo esta
en `docs/04-tecnico/SERVIDOR.md`.

Plan vigente: `docs/00-estado/PLAN-PRODUCCION.md`.

## Definicion de estado

| Termino | Que significa |
|---|---|
| **Publicado para pruebas** | Estado actual. Responde por HTTPS, con datos de prueba. Nadie del club lo usa |
| **En produccion** | El club opera ahi a diario y es la fuente de verdad de la plata. Requiere gate firmado, datos reales y usuarios creados |

## Estado Confirmado del Repo

| Area | Estado | Evidencia |
|---|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB, Blade, Tailwind/Vite | `composer.json` |
| Autenticacion web | Implementada, throttle 5 por minuto | `routes/web.php:31` |
| Roles | ADMIN, OPERATIVO, PROFESOR, con superadmin protegido | middlewares |
| Matriz de permisos | **Verificada 25/08**: 268 pruebas GET x 4 roles, 0 errores 500, 0 accesos indebidos | ADMIN 59 rutas, OPERATIVO 19, PROFESOR solo clases, anonimo solo login |
| Alumnos | CRUD con planes, fecha de alta editable, toggle activo | `AlumnoWebController` |
| Cuotas, deudas y pagos | Core implementado. **Primera cuota con descuento corregida el 31/08** | commit `846347f` |
| Condonacion de deuda | Por web, solo ADMIN, motivo obligatorio de 10 a 500 caracteres | commit `5f77c85` |
| Caja operativa | Apertura, movimientos, cierre, rechazo, validacion, cancelacion | `CajaService` |
| Cashflow | Movimientos admin, reflejo desde caja validada y saldo inicial por tipo de caja | `CashflowIntegracionCajaService` |
| Edicion del saldo inicial | Implementada localmente segun §4.3; pendiente cierre de verificacion y commit por cambio paralelo del motor de tests | `TipoCajaWebController::update()`, `LOG-CODEX.md` 05/09 |
| Clases y asistencias | Guardado transaccional y validacion de pertenencia al grupo | commit `dab369f` |
| Liquidaciones | Implementado con pago y recibos | `LiquidacionService` |
| Cobranza mensual | Implementada. **El primer mes se carga a mano**: una base nueva no tiene mes anterior | `GenerarDeudasMensualesCommand:84-101` |
| Seeder de catalogos | `CatalogosSeeder` unico e idempotente. Base nueva: 0 usuarios, 0 cashflow | verificado 26/08 sobre base descartable |
| Design system | Implementado, protegido por regla dura | `AGENTS.md` §1 |
| **Tests** | **129 pruebas, 705 aserciones**, verde completo sobre MariaDB (06/09) | `phpunit.xml`, LOG-CODEX 06/09 |
| Grupos con frecuencia obligatoria | Alta/edicion verificadas en navegador; rechazo de eliminar la ultima visible en captura aportada por Carlos. Base intacta en los tres casos. Sin grupos vacios en wings_test (06/09) | `GrupoFrecuenciaObligatoriaTest`, LOG-CODEX 06/09 |
| Dependencias | **0 avisos de seguridad** (eran 44) | `composer audit` |
| Servidor | AlmaLinux 9, PHP 8.2 por Remi, TLS Let's Encrypt, base con usuario minimo | `LOG-CLAUDE.md` 30/08 |
| Backups | Diarios, cifrados, rotados, subidos a Drive. **Restauracion probada** | `LOG-CLAUDE.md` 30/08 |
| Despliegue | `deploy.sh` atomico con vuelta atras probada | commit `0d39acc` |
| Preflight | 12 verificaciones, aprobado en el servidor | `PreflightCommand` |
| CSP | **En modo reporte.** 55 de 85 violaciones cerradas | `SecurityHeaders.php:22` |

## Lo que falta

### Deuda inicial: pasos 1–4 completos (06/09, Codex CAB)

Carlos resolvió la pausa: solo el importador, sin seeders. El Paso 0 se retiró
en dde9357; los catálogos faltantes se cargarán por pantalla en otra tarea que
Carlos todavía no definió. El defecto de CatalogosSeeder que desprotege Cuotas
y Sueldos queda separado, sin corregir durante esta prueba.

Verificado directamente en wings_test: 81 deudas PENDIENTE por $2.997.000,
48 alumnos con deuda y 12 sin ninguna; monto_pagado cero y sin imputaciones.
La duplicación fue rechazada sin cambios; la reversión dejó cero filas y la
revalidación/recarga volvió a dejar 81. Alumnos (60), planes (60), usuarios (7)
y pagos (0) conservaron su contenido.

Paso 5 ejecutado parcialmente después: ADMIN muestra 0 AL_DIA, 0 EN_PLAZO,
0 MOROSO y 60 DEUDOR, coincidiendo uno a uno con el servicio, incluidos los 12
sin deuda. **H-DI-01 abierto:** OPERATIVO autenticado no accede a /cobranza;
el navegador redirige a /caja. La ruta está dentro de ensure.admin.web y su
middleware confirma esa redirección, contrariando el dominio de cobranza del
OPERATIVO definido en PERMISOS-ROLES.md. Prueba detenida: PROFESOR no observado,
paso 6 no iniciado. Las seis tablas verificadas conservaron su contenido.
Pendiente de Carlos: corrección separada y revalidación del acceso.
Evidencia: `docs/06-pruebas/RESULTADO-DEUDA-INICIAL-V1.md`.

Fuente del orden: `docs/00-estado/PENDIENTES-260901.md`. Detalle de produccion:
`docs/00-estado/PLAN-PRODUCCION.md`.

### A · Tener con que probar

| # | Pendiente | Estado verificado |
|---|---|---|
| **A1** | Seeder de prueba | `TestSeeder` tiene 21 lineas. `DemoSeeder` crea 19 alumnos; la especificacion pide 15 y verificaciones de estados que hoy no existen |
| **A2** | Simulador de tres meses | No existe codigo. Solo `SIMULADOR-TRES-MESES-V1.md` |
| **A3** | Base de prueba limpia | `wings_test` no existia en CyE al 01/09 |

**Orden:** A3 y A1 van juntas. A2 es independiente y usa su propia base descartable.

### B · Probar de verdad

| # | Pendiente | Estado verificado |
|---|---|---|
| **B1** | Recorrido humano completo | Bloqueado por A |
| **B2** | Suite sobre MariaDB | Motor migrado; corrida completa verificada 05/09: 86 pruebas, 537 aserciones en wings_testing. La regresion especifica de duplicados con acentos sigue pendiente; no se infiere de un test ASCII |
| **B3** | Concurrencia con dos conexiones reales | Sin hacer |
| **B4** | Smoke de rutas que escriben | Sin hacer |

### C · Cerrar lo que quedo a medias

| # | Pendiente | Estado verificado |
|---|---|---|
| **C1** | CSP definitiva | Sigue en modo reporte. Quedan 26 bloques `<script>` en 24 vistas y **24** manejadores `on...=` (eran 40; el 06/09 se cerraron los 6 de filtros y los 10 de efectos de mouse). Protegido por `CspSinCodigoIncrustadoTest`, que no deja que el numero crezca |
| **C2** | Sacar `dump.sql` por las dos puertas | Cerrado 05/09: retirado de Git, ignorado y exportacion de DemoSeeder eliminada. Corrida completa del seeder en MariaDB descartable sin recrear el archivo; sesiones y tokens locales invalidados. El historial anterior no se purgo |

### D · Entregar

| # | Pendiente |
|---|---|
| **D1** | Gate del servidor |
| **D2** | Datos reales del club |
| **D3** | Usuarios reales |
| **D4** | Deuda del primer mes |
| **D5** | Primera caja acompanada |

### E · Despues de entregar

| # | Pendiente |
|---|---|
| **E1** | Cuatro defectos con vencimiento: `AUD-018`, `AUD-019`, `AUD-020`, `AUD-025` |
| **E2** | Reportes que el sistema todavia no entrega |
| **E3** | Deuda tecnica conocida |
| **E4** | Eliminar `formas_pago`, que seguia en la base de CyE al 01/09 |

### G · Pedidos de Carlos del 07/09

| # | Pendiente | Lo que hay que saber antes |
|---|---|---|
| **G1** | **Rediseñar el recibo entero**, con los colores del club y el logo | **Falta definir los dos**: no hay logo en el repositorio ni una paleta del club escrita. Toca `ReciboService` y su plantilla |
| **G2** | **Costo de inscripcion del alumno nuevo**, hoy $5.000, dentro de la regla de alumno nuevo y **configurable desde la pantalla** | La pantalla de configuracion **edita claves pero no las crea**: la clave nueva tiene que nacer de una migracion. Y `Configuracion::set()` sobre una fila inexistente **no hace nada y no avisa** |
| **G3** | **Falta el favicon** | — |
| **G4** | **El ojo para ver la contraseña mientras se tipea** | Va en login, alta y edicion de usuario. Ojo con C1: la CSP no admite JavaScript incrustado en la vista, asi que el manejador va en un archivo `.js` aparte |

### H · Credenciales en el historial de Git — cerrado el 07/09

Se probaron las seis credenciales del archivo contra **todo** el historial
(`git log --all -S`): **ninguna aparece**, y el archivo nunca se commiteo. Esta cubierto
por `.gitignore`.

El viejo `database/dump.sql` si dejo en commits anteriores el mail, nombre, DNI y
telefono de dos alumnas cargadas en marzo. **Carlos lo evaluo el 07/09 y decidio que no
amerita accion**: una casilla de correo no es un dato reservado. Queda escrito para que
no se vuelva a levantar como hallazgo nuevo.

### F · Punitorios por mora — contrato escrito el 06/09, sin implementar

`docs/02-contratos/Wings-Contrato-Punitorios-Mora-V1.md`. Hasta el 06/09 estas
decisiones existian **solo en el chat**: no habia una linea escrita en ningun lado.

**No hay nada implementado**: ni las dos claves de configuracion, ni los campos en
`deuda_cuotas`, ni el subrubro. Con `mora_porcentaje = 0` el sistema se comporta como
hoy, y ese es el estado en que se entrega.

**Resuelto el 06/09:** la plata entra por un rubro reservado nuevo, `Punitorios`, con
un unico subrubro `Punitorio Cuota` (`OPERATIVO`, `afecta_caja = true`,
`es_reservado_sistema = true`), igual que `Cuota Mensual`. Reemplaza la idea previa
de ponerlo bajo Intereses, que no podia funcionar: los subrubros de ese rubro son de
`ADMIN` y no afectan la caja (`CatalogosSeeder.php:54-61`).

**No requiere cambios de codigo.** El comportamiento de "rubro reservado" ya existe:
`SubrubroWebController.php:23` impide agregarle subrubros a un rubro cuyos subrubros
son todos reservados, y los selectores de caja y cashflow filtran los reservados.
Alcanza con crearlo en el seeder de catalogos, **marcandolo `es_reservado_sistema`
tambien a nivel de rubro** (ver abajo).

Queda **un punto abierto** que decide Carlos: si se guarda `recargo_pagado` o se
deduce (§5 del contrato).

**Cerrado el 06/09, commit `b2868ea`.** El agujero que este contrato dejaba anotado
—`RubroWebController::update()` no comprobaba nada— ya no existe: los rubros tienen
`es_reservado_sistema`, y uno reservado no se renombra, no cambia de tipo y no se
borra (solo la observacion se edita). Marcados `Sueldos` y `Cuotas`. Cubierto por
`RubroReservadoTest`, 8 pruebas con dientes comprobados. La busqueda del rubro por
nombre quedo centralizada en `app/Services/SubrubroSueldoService.php`, que lanza
excepcion si falta en vez de seguir de largo en silencio.

**Consecuencia para punitorios:** el rubro `Punitorios` tiene que nacer del seeder ya
marcado como reservado, igual que `Sueldos` y `Cuotas`.

Ademas hay un hallazgo que condiciona este contrato: **`dia_generacion_deuda` es una
configuracion que nadie lee**. Aparece solo en su migracion; el dia esta escrito en
`routes/console.php:12`. El criterio 3 de aceptacion del contrato existe para que las
claves nuevas no terminen igual.

### Estado del servidor — verificado por SSH el 06/09

| Que | Resultado |
|---|---|
| Ultimo despliegue | `7abf327`, 07/09 02:46, en `storage/logs/despliegues.log` |
| Respaldos diarios | **Corriendo.** 8 archivos en `/var/backups/wings`, cron 03:15. Los del 05/09 y 06/09 estan en el Drive: la falla silenciosa de subida quedo cerrada |
| Proceso mensual | **Registrado y activo**: `cobranza:generar-deudas`, `0 6 1 * *`, proxima corrida el 01/10. El `schedule:run` corre cada minuto |
| Monitoreo | **No existe.** 0 servicios de monitoreo corriendo. Si el sitio se cae, nadie se entera |

**Lo unico que no se pudo comprobar** es si la corrida del 01/09 a las 06:00 hizo
algo: no hay `laravel.log` en el servidor, asi que no hay rastro ni a favor ni en
contra. La tarea esta bien registrada y el ejecutor activo.

## Deuda Tecnica Conocida

| Item | Riesgo |
|---|---|
| `AlumnoPlan` corrige planes activos solo en `creating()` | Un `update()` directo puede dejar dos planes activos |
| Montos tratados como float en parte del dominio | Riesgo de precision contable |
| `View::composer('*')` para el badge de clases | Query global en cada render |
| Locks de concurrencia sin prueba paralela real | Cubiertos estructuralmente; falta probarlos con dos conexiones |
| Integracion web completa de PDFs | Parcial |
| README raiz generico de Laravel | No usar como documentacion del proyecto |

## Riesgos que salen a produccion con el defecto adentro

Cada uno verificado como no alcanzable, o de un modulo que todavia no se usa. Detalle y
fechas de vencimiento en `PLAN-PRODUCCION.md` seccion 6.

`AUD-018`, `AUD-019`, `AUD-020`, `AUD-025`.

## Contradicciones abiertas

| Contradiccion | Estado real | Resolucion |
|---|---|---|
| Cambio paralelo del motor de pruebas durante la tarea de saldo inicial | NombreUnico ya fue adaptado con autorizacion. phpunit.xml paso a MariaDB mientras corria la regresion y el helper previo sqliteCreateFunction dejo de ser compatible | Pausa para coordinar B2; pendiente adaptar helper y corrida completa. Ver LOG-CODEX 05/09 |
| Documentos viejos dicen Laravel 11 | `composer.json` usa `^12.0` | Corregir al tocarlos |
| `wings-design/SKILL.md` dice `ds-content` con tope de 1200px | `app.css` no lo implementa | Decidir: implementar el tope o corregir el SKILL |
| Boton Cobrar en `alumnos/index` | Figura deshabilitado | Pendiente funcional |

## Rutas Documentales Vigentes

| Necesidad | Ruta |
|---|---|
| Plan de produccion | `docs/00-estado/PLAN-PRODUCCION.md` |
| Reglas para agentes | `AGENTS.md` |
| Bitacora de Claude Code | `docs/00-estado/LOG-CLAUDE.md` |
| Bitacora de Codex | `docs/00-estado/LOG-CODEX.md` |
| Pendientes del duenio | `docs/00-estado/CHECKLIST-CARLOS.md` |
| Contratos de negocio | `docs/02-contratos/` |
| Design system | `docs/03-diseno-ui/` |
| Pruebas funcionales | `docs/06-pruebas/` |
| Historico | `docs/99-archivo/` |
