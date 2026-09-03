# Wings - Estado Actual

> Actualizado: 2026-08-31
> Fuente de verdad del estado del proyecto. Si otro documento lo contradice, se corrige
> el otro documento o se registra la contradiccion aca antes de implementar.

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
| Repositorio | 17 commits mas adelante |
| PHP / Laravel | 8.2.33 / 12.68.0 |
| Migraciones pendientes | 0 |

**Lo que el servidor NO tiene todavia**, aunque figure como hecho mas abajo:

| Falta | Commit |
|---|---|
| Primera cuota con descuento | `846347f` |
| Cobro de la primera cuota de un alumno nuevo | `f066c42` |
| Condonacion de deuda | `5f77c85` |
| Precio de plan mayor a cero | `824fdd8` |

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
| Clases y asistencias | Guardado transaccional y validacion de pertenencia al grupo | commit `dab369f` |
| Liquidaciones | Implementado con pago y recibos | `LiquidacionService` |
| Cobranza mensual | Implementada. **El primer mes se carga a mano**: una base nueva no tiene mes anterior | `GenerarDeudasMensualesCommand:84-101` |
| Seeder de catalogos | `CatalogosSeeder` unico e idempotente. Base nueva: 0 usuarios, 0 cashflow | verificado 26/08 sobre base descartable |
| Design system | Implementado, protegido por regla dura | `AGENTS.md` §1 |
| **Tests** | **81 pruebas, en verde** | verificado 03/09 |
| Dependencias | **0 avisos de seguridad** (eran 44) | `composer audit` |
| Servidor | AlmaLinux 9, PHP 8.2 por Remi, TLS Let's Encrypt, base con usuario minimo | `LOG-CLAUDE.md` 30/08 |
| Backups | Diarios, cifrados, rotados, subidos a Drive. **Restauracion probada** | `LOG-CLAUDE.md` 30/08 |
| Despliegue | `deploy.sh` atomico con vuelta atras probada | commit `0d39acc` |
| Preflight | 12 verificaciones, aprobado en el servidor | `PreflightCommand` |
| CSP | **En modo reporte.** 55 de 85 violaciones cerradas | `SecurityHeaders.php:22` |

## Lo que falta

Detalle y prioridades en `docs/00-estado/PLAN-PRODUCCION.md`.

### Bloqueantes del go-live

1. **CSP definitiva.** Quedan 30 violaciones y sigue en modo reporte.
2. **Seeder de prueba.** `DATASET-SEEDER-V1.md` especificado y sin implementar.
   `DemoSeeder` no lo cumple; `TestSeeder` tiene 21 lineas.
3. **Simulador de tres meses.** `SIMULADOR-TRES-MESES-V1.md`, especificado y sin implementar.
4. **Prueba humana completa**, rehecha desde `wings_test` limpia.
5. **`dump.sql` fuera del repo, por las dos puertas.** Sigue versionado, y ademas
   `DemoSeeder.php:691-692` lo reexporta solo con `mysqldump`.
6. **Suite sobre MariaDB.** Hoy corre en SQLite: `phpunit.xml`.
7. **Gate del servidor**: exposicion de archivos, scheduler real, monitoreo de errores.
8. **Carga productiva**: usuarios reales, alumnos, deuda del primer mes, primera caja.

### No bloquean

- Integracion continua en GitHub Actions: no existe.
- Prueba de concurrencia con dos conexiones reales sobre MariaDB.
- Smoke de rutas con metodos que escriben.
- `formas_pago` sobrevive en la base local de CyE.

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

`AUD-018`, `AUD-019`, `AUD-020`, `AUD-021`, `AUD-025`.

## Contradicciones abiertas

| Contradiccion | Estado real | Resolucion |
|---|---|---|
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
