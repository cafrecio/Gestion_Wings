# Base del control integral — 21/10/2026

Codex CyE, equipo `Ventas_CyE`. Entrega documental de T14; requiere otro verificador.

## Comprobado en este turno

- Carpeta limpia al iniciar. HEAD local leído: `325e0a1f0e371bae7181f9c8ba716e7b9d0bb834`.
- SSH disponible. Commit del sitio de prueba leído como usuario `wingstest`: `56379295a33782858ce9c986e200f13382d61cc4`.
- Comando de consulta del reloj: sin fecha simulada, 21/10/2026 16:24 Argentina. No se cambió reloj ni configuración.
- Navegador Chrome, sesión existente ADMIN, sitio `https://test.gestionar-te.com.ar`. Se abrió pestaña propia y se recorrieron los enlaces del menú: Inicio, Alumnos, Clases, Cobranza, Caja, Movimientos, Cashflow, Liquidaciones, Reportes, Revisión, Deportes, Niveles, Grupos, Profesores, Rubros, Tipos de Caja, Configuración y Usuarios.
- Es un relevamiento de entrada/listado de esas 18 pantallas. No es ejecución de formularios ni comprobación integral de sus efectos. Reportes se volvió a leer cuando terminó de cargar; el primer texto vacío no se tomó como defecto.
- Se leyeron filas visibles concretas además de los totales: movimientos de cuotas/inscripción del 10/10, grupos con sus frecuencias y precios, profesores con sus tarifas y usuarios con sus roles. No se consultó la base del sitio.
- Inicio/Cobranza/Reportes mostraban deuda $2.010.000. Inicio/Reportes mostraban ingresos $164.000 y egresos $0; Cashflow mostraba disponible $1.734.000. Son lecturas de pantalla, sin certificación contra base ni libro independiente.
- Se comprobó por lectura de JSON que la copia tiene 331 códigos únicos en 20 módulos. Se conservaron los criterios como referencias identificadas, sin cambiar esos archivos ni las respuestas DU.
- Los 141 casos clasificados por la fuente como `normal` y `vigente` tienen recorrido propuesto. En total hay 181 casos vinculados y 150 sin recorrido asignado; siguen visibles para planificar variantes o revisar vigencia. Esto no mide cobertura ejecutada.
- Chrome perdió la conexión al intentar abrir Sueldos: no se cuenta esa pantalla como relevada. El visor HTML local no se pudo abrir en el navegador integrado por su restricción al protocolo de archivos; se revisan estructura, JavaScript y enlaces, sin afirmar revisión visual del visor.

## Código utilizado como orientación

Se buscó primero con `codebase-memory`: símbolos de controladores/servicios/modelos; trazado de `registrarPagoCuotaOperativo` en ambos sentidos; cuerpo de `CajaService::validarCaja`. Después se confirmó el material relevante en archivos reales.

Fuentes principales leídas para el mapa:

- [Rutas web](../../../../routes/web.php) y [arranque/middlewares](../../../../bootstrap/app.php).
- [Ingreso/salida y tablero administrativo](../../../../app/Http/Controllers/WebController.php).
- [Alta, edición y reactivación de alumno](../../../../app/Http/Controllers/AlumnoWebController.php), especialmente `store`, `update`, `toggleActivo`.
- [Inscripción](../../../../app/Services/InscripcionService.php), `sincronizar` y decisiones de perdón.
- [Cobro por operativo y por dueño](../../../../app/Services/PagoCuotaService.php), cuerpos de ambos métodos de registro, no solo sus comentarios.
- [Validación de caja](../../../../app/Services/CajaService.php), `validarCaja`, y [reflejo](../../../../app/Services/CashflowIntegracionCajaService.php), `reflejarCajaEnCashflow`.
- [Transferencia indivisible](../../../../app/Services/TransferenciaCashflowService.php), incluidos validación, registro y creación de ambas patas.
- [Lista por fecha y validaciones de asistencia](../../../../app/Http/Controllers/ClaseWebController.php), `show` y primera parte de `storeAsistencias`; [registro individual](../../../../app/Services/ClaseService.php), `registrarAsistencia`.
- [Liquidaciones](../../../../app/Services/LiquidacionService.php): generación, cálculo por hora, cierre, recálculo y decisiones de atrasadas; [pago](../../../../app/Services/LiquidacionPagoService.php), `marcarComoPagada`.
- [Revisión de cobranza](../../../../app/Services/RevisionCobranzaService.php), `resolver`.
- [Historial de precios](../../../../app/Models/GrupoPlan.php), `registrarPrecio`, y [constancia de generación](../../../../app/Models/GeneracionCuotas.php), `faltaGenerar`.
- [Generación mensual](../../../../app/Console/Commands/GenerarDeudasMensualesCommand.php), ramas de cuota/revisión y constancia; [horarios del scheduler](../../../../routes/console.php).
- [Headers](../../../../app/Http/Middleware/SecurityHeaders.php), cuerpo de `handle`.
- Nombres de tablas/pivotes contrastados por búsqueda literal en `app/Models/` y `database/migrations/`. La lista de una fila INT es el conjunto que se debe inspeccionar, no una declaración de que cada acción escribe todas esas tablas.

**Límite del índice:** `check_index_coverage` devolvió `coverage_unavailable / metadata_changed` para los archivos consultados. Se solicitó reindexación; el resultado siguió informando la misma generación y sin cobertura acreditable. Por eso el grafo fue solo localizador y se usó lectura directa. No hay afirmación de exhaustividad de llamadas, tablas o ausencia de defectos.

## Pendiente antes de usar los relatos como aceptación

- Revisión independiente de los 39 recorridos y del vínculo a los criterios de cada caso.
- Precisar ruta, función, filas que cambian/no cambian y valores esperados para cada ejecución seleccionada. Los rótulos «código orientativo» no equivalen a haber auditado todas sus ramas.
- Revisar vigencia de cada letra del catálogo; `vigente`/`no_implementado` copiados son estados declarados de la fuente, no dictámenes actuales de Codex.
- Elegir datos del cuento con los nombres de grupos, frecuencias, horarios y precios que muestre el sistema al ejecutar. «Patín inicial» del ejemplo no debe convertirse en un grupo inventado si el sitio ofrece «Principiantes».
- Ingresar y operar como OPERATIVO y PROFESOR; revisar sus pantallas y rechazos. Esta sesión no cambió de usuario.
- Escritorio/móvil a 375, acciones por fila, PDF, consola, tiempos y consultas con evidencia. No medidos ni aprobados aquí.
- Completar el libro esperado por día, cierre de octubre, generación de noviembre, revisiones y liquidaciones.
- Coordinar avisos reales y cualquier prueba con porcentaje positivo de mora. No se enviaron mensajes ni se cambió porcentaje al preparar el control.
- Primera carga, restauración, concurrencia y carga masiva requieren entorno aislado; no se ejecutaron aquí.
- Suite completa, estado actual de producción, preflight, respaldo y autorización de despliegue siguen siendo controles de entrega separados. No se verificó producción ni se desplegó.

## Resultado de esta entrega

Plan y tabla preparados. Ejecuciones: **cero**. T14 continúa en curso; preparar este control no cierra la prueba integral.
