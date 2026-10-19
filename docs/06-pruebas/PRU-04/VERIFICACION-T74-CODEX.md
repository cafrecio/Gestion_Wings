# T74 — Verificación de lógica y corrección visual

19/10/2026 · Codex CyE. Implementación de Claude CAB: `c1b761e8` y `09409f9f`.

**Lógica: APROBADA**, criterios a–g comprobados en fuente y solicitudes reales. Suite completa: **935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0**. Visual corregido y aprobado por Carlos el 19/10: «Apruebo el visual T74 actualizado». Sin despliegue.

La toma y el rechazo visual se registraron mediante el tablero oficial y se publicaron antes de modificar la vista: `5bbf5047`. Se conserva a Claude como implementador de la lógica. La aprobación del visual corresponde a Carlos.

## Lógica comprobada

| Criterio | Evidencia leída y contrastada |
|---|---|
| a. Vencidos y En plazo | `OperativoDashboardController.php:111` obtiene una colección con `listadoCobranza('TODOS')`; Vencidos selecciona MOROSO/DEUDOR, En plazo selecciona saldo positivo fuera de esos estados. `CobranzaEstadoService.php:348` usa activos y asigna `deuda_total` desde `saldoDeAlumnos`; su filtro por defecto en :449 selecciona los mismos vencidos. |
| b. Saldo común y bordes | `CajaWebController.php:725` usa `saldoDeAlumnos` de alumnos activos; `conDeuda` cuenta total positivo. Se leyeron los cuerpos de `saldoDeAlumnos` (:136), inscripción (:219), recargos (:264), `estaImpaga` (:602) y clasificación (:614, :786). Cuotas, inscripción y punitorios integran el saldo; períodos futuros y cuotas pagadas/condonadas se excluyen. Los casos concretos están debajo. |
| c. En plazo y enlaces | Pedidos HTTP reales de Inicio, Cobranza por defecto, EN_PLAZO y Cobrar. Se comparan cantidades concretas y conjuntos de IDs; DOM de cada tarjeta comprueba su propio destino: Vencidos → Cobranza; En plazo → EN_PLAZO; Posibles inactivos → Revisión. Los tres destinos responden 200 para OPERATIVO. |
| d. Cobranza conservada | Diff de `c1b761e8^` a `09409f9f` vacío para servicio, controlador y vistas de Cobranza. El controlador actual (:14–48) conserva filtros, listado y resumen. Codex no modifica esos archivos. |
| e. Permisos | Contrato PERMISOS-ROLES y ruta `/operativo` bajo `reject.profesor.web` en `routes/web.php:107`. HTTP: ADMIN/OPERATIVO 200; PROFESOR 403; anónimo y usuario inactivo al login. Usuario inactivo confirmado en la fila persistida. |
| f. Consultas por alumno | Lectura de consultas agrupadas y precarga de relaciones, más medición HTTP con 1 y 12 alumnos: **24 consultas en ambos casos**. SQL íntegro guardado en observaciones. Esta medición acota el crecimiento por alumnos; no certifica el rendimiento de todas las pantallas o tamaños del padrón. |
| g. Pruebas originales | Se leyeron completos los cinco casos de `InicioVencidosEnPlazoT74Test`: padrón mixto con resultados 2/2 y 4/0, inscripción, rótulos/enlaces y exclusión de no vencidos. Comparan números concretos y respuestas reales. El instrumental propio refuerza el vínculo de cada enlace con su tarjeta. |

`CuotaAltaEstadoTest.php:100` conserva el padrón de 60 activos: 40 sin deuda y 20 con agosto pendiente, sin pagos. La aserción anterior `alumnosConDeuda=20` se reemplaza por `vencidos=20`, que cubre el mismo conjunto, y agrega `enPlazo=0`. El cambio es equivalente para ese padrón y añade una comprobación.

## Casos propios y filas observadas

Datos sintéticos, exclusivamente `wings_testing_codex`. No se inspeccionó ni modificó la base del servidor.

| Caso | Vencidos | En plazo | Cobrar |
|---|---:|---:|---:|
| Gracia 10 días, 09/10 | 2 | 2 | 4 |
| Último día, 10/10 | 2 | 2 | 4 |
| Primer día vencido, 11/10 | 4 | 0 | 4 |
| Inscripción antigua sola + inscripción actual con cuota parcial | 1 | 1 | 2 |
| Alta 28/09, plazo propio de 7 días: 04/10 | 0 | 1 | 1 |
| Misma alta: 05/10 | 1 | 0 | 1 |
| Cobro real de noviembre desde octubre | 0 | 0 | 0 |

Se miraron las filas, además de los agregados: parcial corriente de $48.000 menos $20.000 da saldo $28.000; inscripción sola antigua da $5.000; inscripción actual más cuota parcial pagada $20.000,50 da $32.999,50. Una cuota futura pendiente tiene saldo corriente cero. La alumna inactiva existe con deuda pero está fuera de las tres cuentas.

El adelanto se ensaya mediante POST a `web.caja.pagar`, con confirmación y período noviembre: se verifica la deuda persistida PAGADA por $48.000 y luego las tres cuentas en cero. Los otros pagos parciales son fixtures guardados; no se presentan como cobros ejecutados por navegador.

[Instrumental propio: 10 casos de datos](evidencia-t74-codex/VerificacionT74CodexTest.php) · [Filas y SQL](evidencia-t74-codex/observaciones.jsonl). El instrumento protege aplicación testing y base Codex antes de preparar tablas. Permanece fuera de la suite permanente.

## Ejecuciones

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:VIEW_COMPILED_PATH='C:/xampp/htdocs/Gestion_Wings/storage/app/t74-runtime/views'
$env:T74_FASE='despues'
php artisan test docs/06-pruebas/PRU-04/evidencia-t74-codex/VerificacionT74CodexTest.php tests/Feature/InicioVencidosEnPlazoT74Test.php tests/Feature/CuotaAltaEstadoTest.php tests/Feature/CspSinCodigoIncrustadoTest.php
```

**32 aprobadas, 251 aserciones, 94,15 s, salida 0.** Son 10 propias, 5 originales T74, 15 de CuotaAltaEstado y 2 CSP. [Salida dirigida](evidencia-t74-codex/dirigidas.txt).

Captura antes: 1 prueba/5 aserciones aprobadas, 27,93 s. Ambas fases provienen de GET reales a Wings; el navegador lee el HTML devuelto, con enlaces de assets locales y token CSRF sintético omitido. No se fabricaron maquetas de las pantallas.

**Suite completa: 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0.** Inició 12:46:17 y terminó 13:07:42, exclusivamente en `wings_testing_codex`, sin `--exclude-group=lento`. La otra suite (artisan PID 13120/phpunit PID 3376) había dejado de aparecer en Win32_Process; se comprobó cero pruebas ajenas inmediatamente antes de lanzar la propia y se confirmó solo la propia activa durante la corrida. No se atribuyen resultados de la suite ajena a T74.

[Salida completa](evidencia-t74-codex/suite-completa.txt) · [Resultado terminal](evidencia-t74-codex/suite-resultado.json) · [Contexto y HEAD inicial](evidencia-t74-codex/suite-contexto.json) · [Huellas al inicio](evidencia-t74-codex/fuentes-inicio-suite.json) · [Huellas al terminar](evidencia-t74-codex/fuentes-fin-suite.json). Las once fuentes registradas permanecen idénticas. Durante la corrida se publicó `c017bac3` de T34; sus cambios ya estaban presentes al comenzar y no alteraron esas fuentes. No se certifica aquí la corrección de T34. El corte de la suite permanente es 939; el instrumento propio permanece fuera de `tests/` y no suma pruebas permanentes.

## Visual corregido y aprobado por Carlos

[Galería antes/después](APROBAR-T74.html). Capturas guardadas a 1366 y 375 reales dentro de iframe, con tramos móviles de 720 px y solapamiento de 120 px. Se recorrió desde el inicio hasta el pie. Se inspeccionaron escritorio, los tres tramos de caja propia y login móvil; login entra sin recortes del formulario.

- Escritorio: Alumnos terminaba 21 px después de la última acción del cajón. Ahora coincide: diferencia 0 en caja propia, caja de compañera, cierre pendiente y sin caja.
- Celular: ambos bordes ya coincidían antes y siguen coincidiendo, diferencia 0. Se conserva el margen móvil del design system.
- Encabezado y acciones quedan en filas separadas, conforme a DESIGN-RULES. La barra superior usa el margen interior de la tarjeta (20 px + borde 1 px) en escritorio.
- Carlos observó después de la primera presentación: «Che, por que tenemos botones de diferentes medidas? Cuando cambiaron las reglas?». No es aprobación. Se confirmó la inconsistencia anterior a T74: accesos 96 × 32 y tres acciones del cajón de 36 px de alto, con anchos variables. La primera corrección solo alineaba bordes y omitió regularizar esas medidas.
- Se retiraron los estilos manuales de Cobrar, Registrar, Resumen y los dos Abrir. Usan `ds-btn` y sus variantes existentes: los seis botones principales y Abrir miden ahora 96 × 32 en los dos anchos. Sin CSS compartido nuevo, sin JavaScript y sin alterar las tarjetas o sus destinos.
- No se detectó desborde horizontal en las 20 combinaciones antes/después (cuatro variantes más login, dos anchos).

[Medidas antes](evidencia-t74-codex/antes/mediciones.json) · [Medidas después](evidencia-t74-codex/despues/mediciones.json) · [Capturador](evidencia-t74-codex/medir.mjs) · [Vista original](evidencia-t74-codex/dashboard-original.blade.txt).

## Límites y siguiente paso

Codebase-memory utilizado como pista; cobertura no disponible y metadata_changed en los archivos consultados. Se confirma en fuente real; el grafo no se usa para afirmar exhaustividad. No se corrige el controlador ni se auditan cálculos ajenos a T74. Hay cambios paralelos de T34 en proveedor, liquidaciones y pruebas: fuera de esta entrega.

Guardianes finales de documentos y tableros: **12 aprobados, 55 aserciones, 0,34 s, salida 0**. `view:cache` y `view:clear` correctos sobre la vista final, en almacenamiento exclusivo. [Salida](evidencia-t74-codex/guardianes-finales.txt) · [Control de entrega](evidencia-t74-codex/control-entrega.json).

El medidor registra también la altura del documento en el marco de 720 px para comprobar la cobertura de tramos: el login usa altura mínima relativa al marco, por lo que su captura larga de 900 px de marco no sirve como referencia para sumar los tramos de 720. Se verificó con la altura del mismo contexto. Las cinco capturas principales aprobadas (escritorio, móvil completo y tres tramos de caja propia) permanecen idénticas por SHA-256. El detalle de botones y la captura larga de sin caja se volvieron a inspeccionar tras recapturar; conservan las medidas comprobadas. Después de la suite aparecieron cambios ajenos en otras vistas y JavaScript: se preservan y se excluyen del commit T74.

Sintaxis PHP del instrumento y de la vista final correctas. Diff visual: solo la vista autorizada, sin cambios de CSS compartido. La galería carga sus diez imágenes y todos sus enlaces locales existen; se inspeccionó también su presentación en navegador. Tras unificar botones se regeneraron y revisaron las capturas; el GET real pasó 1 prueba/5 aserciones, 13,37 s. Dos intentos restringidos no llegaron a ejecutar pruebas por el control de lectura de autoload; la corrida con acceso normal pasó. No se presentan esos intentos como fallas del producto.

El primer visual se presentó a Carlos y recibió la observación sobre medidas. Se corrigió y renovó la galería. Carlos aprobó explícitamente la versión actualizada: **«Apruebo el visual T74 actualizado»**. Según AGENTS §6a, el aspecto queda verificado por Carlos; Codex verifica la lógica de Claude. El cierre del tablero y la publicación se realizan después del resultado terminal verde, con sus palabras en el pie Diseno-autorizado. La bitácora y los estados documentales se actualizan en la misma entrega; no se modifican reglas de negocio ni contratos.
