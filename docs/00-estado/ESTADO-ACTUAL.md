# Wings - Estado Actual

> Actualizado: 2026-08-09
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
| Tests | Suite base operativa sobre SQLite en memoria | `php artisan test`: 14 tests y 28 aserciones pasan |

## Contradicciones Detectadas y Resolucion

| Contradiccion | Estado real | Resolucion documental |
|---|---|---|
| La aceptación de condonación afirmaba sin condición que el alumno dejaba de figurar como DEUDOR | `CobranzaEstadoService` y el contrato de estados §3 clasifican como DEUDOR a todo alumno que nunca pagó, aunque no tenga deudas impagas | Resuelto: la aceptación usa un alumno con historial de pago; al condonar su única deuda pendiente pasa a AL_DIA. Un test separado preserva que quien nunca pagó sigue DEUDOR. |
| La orden web de condonación pedía proteger con el middleware ADMIN existente y exigía 403 para el operativo | `ensure.admin.web`, usado por las rutas web exclusivas de ADMIN, redirige al operativo a Caja con 302 | Resuelto: se conserva `ensure.admin.web` y la matriz uniforme. El test comprueba el resultado de seguridad —la deuda no cambia— sin acoplarse al código HTTP. |
| La orden de acceso web para condonar afirmaba que `PagoCuotaService::condonarDeuda()` exigía un motivo obligatorio | El cuerpo del servicio aceptaba cualquier `string`, incluida la cadena vacía; el mínimo de 10 caracteres existía solo en el validador de la API apagada | Resuelto: el rango de 10 a 500 caracteres ahora es invariante del servicio y también se valida con mensajes en castellano en la entrada web. |
| La tarea de primera cuota declara correcto e intocable a `PagoCuotaService` y exige que un alumno nuevo quede al día al autocrear la deuda | Con una regla de primer pago menor a 100%, `ajustarDeudas()` corre antes de `obtenerOcrearDeuda()`: no encuentra la deuda todavía, luego se crea por el precio completo y el pago descontado la deja `PENDIENTE`. Caso reproducido por test web con plan de $28.000 y regla de 70%: deuda $28.000, pago $19.600, saldo $8.400 | Pendiente de decisión: autorizar corregir el servicio; crear la deuda desde el controlador antes de llamar al servicio (contradice el enfoque pedido); o cambiar el criterio de aceptación para descuentos. |
| Documentos viejos dicen Laravel 11 | `composer.json` usa `laravel/framework ^12.0` | Actualizar referencias nuevas a Laravel 12. |
| Estado anterior dice motor mensual no implementado | Existe `cobranza:generar-deudas` | Marcar como implementacion parcial, no cerrado. |
| Estado anterior dice rutas API de deudas publicas | En `routes/api.php` estan bajo `auth:sanctum` | Considerar corregido, mantener vigilancia. |
| Estado anterior dice PDFs sin endpoints | Hay servicio, vistas y rutas API de recibos | Marcar como parcial hasta validar flujo web completo. |
| Documento dice boton Cobrar siempre visible y accionable en alumnos | En `alumnos/index` figura deshabilitado | Pendiente funcional/UI. |
| `CLAUDE.md` apuntaba a `Contratos/` y docs viejos | Documentos se reorganizaron bajo `docs/` | `CLAUDE.md` debe apuntar a rutas nuevas. |
| `wings-design/SKILL.md` dice `ds-content` con `max-width` 1200px | `app.css` no implementa ese tope; las vistas anchas se estiran al monitor completo | Decidir: implementar el tope en `app.css` o corregir SKILL.md. Mientras tanto `caja/historial` usa tope propio de 980px. |

## Pendientes Inmediatos

1. Ampliar la suite de tests recuperada.
   - Las migraciones con `MODIFY` exclusivo de MySQL ya están condicionadas por driver y la suite corre completa en SQLite.
   - Falta agregar cobertura de los flujos críticos de cobranza, seguridad, caja y cancelaciones.

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
| Cobertura automatizada todavía escasa | La suite corre, pero aún faltan pruebas de varios flujos críticos de dinero y seguridad. |
| Locks de concurrencia sin prueba paralela sobre MariaDB | Caja y pagos usan `lockForUpdate`, cubierto estructuralmente y por idempotencia secuencial; falta una prueba con dos conexiones reales sobre MariaDB. |
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
