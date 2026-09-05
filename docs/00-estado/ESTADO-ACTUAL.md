# Wings - Estado Actual

> Actualizado: 2026-09-05 (freno de saldo inicial; demas verificaciones conservan sus fechas)
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

**Verificado por SSH el 01/09.** Lo que dice esta pagina sobre funcionalidades hechas
se refiere al **repositorio**, no a lo que esta publicado.

| Que | Valor |
|---|---|
| Commit desplegado | **`b9e6af6`** (30/08 17:41) |
| Repositorio | 21 commits mas adelante respecto del ultimo commit verificado en el servidor |
| PHP / Laravel | 8.2.33 / 12.68.0 |
| Migraciones pendientes | 0 |

**Lo que el servidor NO tiene todavia**, aunque figure como hecho mas abajo:

| Falta | Commit |
|---|---|
| Primera cuota con descuento | `846347f` |
| Cobro de la primera cuota de un alumno nuevo | `f066c42` |
| Condonacion de deuda | `5f77c85` |
| Precio de plan mayor a cero | `824fdd8` |
| Saldo inicial por tipo de caja | `c09a3e1` |

**Consecuencia:** en el servidor **no se le puede cobrar la primera cuota a un alumno
nuevo**. Se corto el despliegue el 30/08 porque la suite estaba en rojo; ese defecto ya
se cerro, asi que no queda motivo tecnico para no desplegar.

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
| **Tests** | **85 pruebas, 455 aserciones**, verde completo **sobre MariaDB** (05/09). Antes corrian en SQLite | `phpunit.xml`, LOG-CLAUDE 05/09 |
| Dependencias | **0 avisos de seguridad** (eran 44) | `composer audit` |
| Servidor | AlmaLinux 9, PHP 8.2 por Remi, TLS Let's Encrypt, base con usuario minimo | `LOG-CLAUDE.md` 30/08 |
| Backups | Diarios, cifrados, rotados, subidos a Drive. **Restauracion probada** | `LOG-CLAUDE.md` 30/08 |
| Despliegue | `deploy.sh` atomico con vuelta atras probada | commit `0d39acc` |
| Preflight | 12 verificaciones, aprobado en el servidor | `PreflightCommand` |
| CSP | **En modo reporte.** 55 de 85 violaciones cerradas | `SecurityHeaders.php:22` |

## Lo que falta

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
| **B2** | Suite sobre MariaDB | phpunit.xml cambio en paralelo a mysql/wings_testing el 05/09; pendiente coordinar corridas y verificar la suite completa. Incluir duplicados con acentos: SQLite no reproduce esa colacion |
| **B3** | Concurrencia con dos conexiones reales | Sin hacer |
| **B4** | Smoke de rutas que escriben | Sin hacer |

### C · Cerrar lo que quedo a medias

| # | Pendiente | Estado verificado |
|---|---|---|
| **C1** | CSP definitiva | Sigue en modo reporte. Quedan 24 vistas con `<script>` y 40 manejadores `on...=` inline; no se puede activar el bloqueo asi |
| **C2** | Sacar `dump.sql` por las dos puertas | Sigue versionado y `DemoSeeder.php:691-692` lo reexporta con `mysqldump` |

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

### Estado del servidor que falta volver a verificar

El ultimo acceso SSH registrado fue el 01/09. Desde entonces no se comprobo:

1. Si hubo otro despliegue despues de `b9e6af6`.
2. Si los backups diarios siguen corriendo.
3. Si existe monitoreo de errores operativo.
4. Si el proceso mensual se ejecuto el 01/09 a las 06:00.

No asumir ninguno de estos cuatro puntos. El despliegue se comprueba en
`storage/logs/despliegues.log` del servidor.

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
