# Wings - Estado Actual

> Actualizado: 2026-06-15  
> Alcance: documentacion y orden del repo. No modifica logica funcional.

Este archivo es la fuente de verdad para entender donde esta parado el proyecto. Si otro documento del repo contradice este archivo, se debe actualizar el documento viejo o registrar la contradiccion aca antes de implementar.

## Resumen Ejecutivo

Wings es una aplicacion Laravel para gestionar un club deportivo: alumnos, deportes, grupos, planes, cuotas, deudas, pagos, caja operativa, cashflow, clases, asistencias, profesores, liquidaciones, usuarios y configuracion.

El repo tiene mucho core implementado, pero todavia no esta listo para prueba funcional completa ni produccion. La prioridad inmediata es ordenar documentacion, recuperar tests, limpiar/normalizar datos de prueba, crear seeders utiles y despues avanzar con seguridad, despliegue VPS y cierre de pendientes funcionales.

El plan vigente para probar todo el sistema esta en `docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`. La guia compartible para colaboradores esta en `docs/06-pruebas/GUIA-PRUEBA-COLABORADOR.html`.

## Estado Confirmado del Repo

| Area | Estado | Evidencia |
|---|---|---|
| Stack | Laravel 12, PHP 8.2, MariaDB/XAMPP, Blade, Tailwind/Vite | `composer.json`, `package.json` |
| Autenticacion web | Implementada | `routes/web.php`, `WebController`, middlewares |
| Roles | ADMIN, OPERATIVO, PROFESOR | `App\Models\User`, middlewares |
| Alumnos | CRUD web implementado, con planes y toggle activo | `AlumnoWebController`, vistas `resources/views/alumnos` |
| Grupos/deportes/niveles | CRUD y relaciones implementadas | controllers, models, migrations |
| Cuotas/deudas/pagos | Core implementado con FIFO y pago admin/operativo | `PagoCuotaService` |
| Caja operativa | Apertura, movimientos, cierre, rechazo y validacion implementados | `CajaService`, `CajaWebController` |
| Cashflow | Movimientos admin y reflejo desde caja validada | `CashflowService`, `CashflowIntegracionCajaService` |
| Clases/asistencias | Modulo implementado con vistas web | `ClaseWebController`, `ClaseService` |
| Liquidaciones | Modulo implementado con pago y recibos parciales | `LiquidacionService`, `LiquidacionPagoService`, `ReciboService` |
| PDFs | Servicio y vistas existen; revisar integracion web completa | `ReciboService`, `resources/views/pdfs` |
| Cobranza mensual | Implementacion parcial | `GenerarDeudasMensualesCommand`, `CobranzaEstadoService` |
| Design system | Implementado, pero requiere disciplina estricta | `docs/03-diseno-ui/wings-design/SKILL.md`, `resources/css/app.css` |
| Tests | No confiables en este momento | `php artisan test` falla por migracion incompatible con SQLite |

## Contradicciones Detectadas y Resolucion

| Contradiccion | Estado real | Resolucion documental |
|---|---|---|
| Documentos viejos dicen Laravel 11 | `composer.json` usa `laravel/framework ^12.0` | Actualizar referencias nuevas a Laravel 12. |
| Estado anterior dice motor mensual no implementado | Existe `cobranza:generar-deudas` | Marcar como implementacion parcial, no cerrado. |
| Estado anterior dice rutas API de deudas publicas | En `routes/api.php` estan bajo `auth:sanctum` | Considerar corregido, mantener vigilancia. |
| Estado anterior dice PDFs sin endpoints | Hay servicio, vistas y rutas API de recibos | Marcar como parcial hasta validar flujo web completo. |
| Documento dice boton Cobrar siempre visible y accionable en alumnos | En `alumnos/index` figura deshabilitado | Pendiente funcional/UI. |
| `CLAUDE.md` apuntaba a `Contratos/` y docs viejos | Documentos se reorganizaron bajo `docs/` | `CLAUDE.md` debe apuntar a rutas nuevas. |

## Pendientes Inmediatos

1. Recuperar suite de tests.
   - Hoy `php artisan test` falla porque una migracion ejecuta `ALTER TABLE grupos MODIFY nivel_id...`, sintaxis MySQL que SQLite no soporta.
   - Antes de avanzar fuerte, hay que decidir si los tests corren en SQLite compatible o en MariaDB de test.

2. Validar estado real del modulo cobranza.
   - Revisar command mensual, scheduler, reglas de asistencia del mes anterior, alertas, inactivacion y deuda fantasma.
   - Comparar contra contratos y resumen de producto.

3. Integrar cobranza en vistas web de alumnos.
   - Estado de cobranza visible.
   - Dot y texto de estado.
   - Boton `Cobrar` siempre accionable.
   - Subvistas o filtros: al dia pendiente, morosos, deudores, a controlar.

4. Crear dashboard operativo.
   - Alumnos a cobrar.
   - Alumnos a controlar.
   - Alertas de revision.

5. Preparar prueba funcional completa.
   - Limpiar BD.
   - Crear seeders representativos.
   - Armar plan de pruebas manuales.
   - Cubrir caja, cobro, deuda, asistencia, liquidacion y recibos.
   - Documento base: `docs/06-pruebas/PLAN-PRUEBAS-FUNCIONALES.md`.

6. Seguridad y despliegue.
   - Revisar policies/autorizacion granular.
   - Revisar exposicion API.
   - Revisar entorno VPS, backups, permisos, HTTPS y despliegue.

## Deuda Tecnica Conocida

| Item | Riesgo |
|---|---|
| Tests rotos por SQLite/migraciones | No hay red de seguridad automatica. |
| `CajaService::abrirCajaSiNoExiste()` sin lock transaccional fuerte | Posible doble caja con requests simultaneos. |
| FIFO de pagos sin lock de filas | Posible saldo corrupto con pagos paralelos. |
| `AlumnoPlan` corrige planes activos solo en `creating()` | Un `update()` directo puede dejar dos planes activos. |
| Montos tratados como float en parte del dominio | Riesgo de precision contable. |
| `View::composer('*')` para badge de clases | Query global en cada render. |
| README raiz generico de Laravel | No usar como documentacion del proyecto. |

## Rutas Documentales Vigentes

| Necesidad | Ruta |
|---|---|
| Estado actual | `docs/00-estado/ESTADO-ACTUAL.md` |
| Mapa visual | `docs/00-mapa-proyecto/index.html` |
| Indice documental | `docs/README.md` |
| Producto | `docs/01-producto/` |
| Contratos de negocio | `docs/02-contratos/` |
| Design system | `docs/03-diseno-ui/` |
| Setup tecnico | `docs/04-tecnico/` |
| Pendientes crudos | `docs/05-pendientes/` |
| Pruebas funcionales | `docs/06-pruebas/` |
| Historico | `docs/99-archivo/` |
