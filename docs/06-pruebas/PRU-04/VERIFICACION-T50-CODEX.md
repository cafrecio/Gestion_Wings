# T50 — Verificación independiente de lógica

**Resultado: DEVUELTA a Gemini.** Codex CAB, 21/10/2026. Tres hallazgos reproducidos; no se corrigió producto ni se modificó la tabla de pruebas.

Implementación examinada: `37040633ad0104a3025a3b69b94f2fc4fcde8f54`, cuyo título menciona T52 pero cuyo diff contiene T50. Informe de origen: [IMPLEMENTACION-T50.md](IMPLEMENTACION-T50.md). Corte probado: `b25b2f4a0c2d3b0f07b521e3dba210852058998e`. Base exclusiva: `wings_testing_codex`; datos ficticios. Sin acceso al servidor ni a bases del club, producción o sitio de prueba.

## Diez puntos de la orden

La [carpeta de evidencia](evidencia-verificacion-t50-codex/) contiene controles ejecutables fuera de `tests/`, salidas y recorrido real. Los números de control corresponden a métodos de `ControlT50Test.php`.

| Punto | Resultado | Comprobación y evidencia |
|---|---|---|
| 1. Límite, diciembre y Buenos Aires | Pasa | Control 01: último día del mes siguiente, enero del año siguiente y segundos anteriores/posteriores a medianoche BA. `RelojSimulado::aplicar` y `Carbon::now()` comprobados; `controles.jsonl`, casos `limite_zona`. |
| 2. Alta sin escrituras ante exceso | Pasa | Controles 02–03: ADMIN y OPERATIVO reciben 422 en castellano; tablas de alumnos, planes, cuotas, cargos e inscripciones iguales antes/después del rechazo. Día límite aceptado y persistido. |
| 3. Edición y motivo | Falla H2 | Controles 04–06: misma fecha en formatos equivalentes e histórico fuera de rango permiten otros cambios; exceso precede al motivo y motivo obligatorio funciona. Fecha mal formada produce 500 antes de validar. |
| 4. Previsualizaciones | Pasa en servidor | Control 07: ambas rutas HTTP responden 422 con aviso en castellano y el servicio lanza `ValidationException`. El aviso del cliente puede ser reemplazado después por otro genérico; ver observación de navegador. |
| 5. Excel: revisar y confirmar | Pasa | Controles 08–10: texto y serial Excel aceptan límite y marcan E2 al excederlo; `cargar` revalida aunque se saltee revisión o cambie el reloj desde la revisión. Sin escrituras ante error. |
| 6. Puertas de `fecha_alta` | Relevadas; límites abajo | Grafo primero, cobertura y lectura real de los cuerpos; alta, edición, Excel, modelo, reactivación, API apagada y cinco seeders. Tabla específica debajo. |
| 7. Alumno del mes siguiente | Falla H1 | Control 11: cuota futura excluida, pero inscripción pendiente $5.000 lo deja EN_PLAZO, suma 1 en Cobrar y 1 en Inicio en plazo. Control 16: cuota $30.000 empieza a contar el 1/11. |
| 8. T52: ingreso y grupo | Pasa | Control 12: scope exige ingreso aunque `grupo_desde` sea anterior; GET real de clase anterior no incluye alumno. Cuerpos y salida verde de `ClaseListaHaciaAdelanteT52Test`: H3/H5 envían POST de asistencia, exigen 422 y ausencia de marca en BD. No se repitió esa misma prueba ya ejecutada en la completa. |
| 9. Chrome contra app local | Falla H3 | Build previo; Chrome real en localhost, sin marcos ni HTML guardado. `max`, validez y mensaje leídos del DOM; desfase servidor/navegador reproducido. Continuación: pulsaciones reales por segmento e incremento nativo ArrowUp generan fecha fuera de rango y aviso en castellano; Guardar muestra «No se guardó» con el error, conserva datos y no envía POST de alta. Envío directo al servidor cubierto por controles 02/15. Consola observada sin errores ni avisos. Ambos JSON de recorrido, `servidor-*.json`, `servidor-peticiones.jsonl` y tres PNG. |
| 10. CSP y archivos protegidos | Pasa | `CspSinCodigoIncrustadoTest` incluido verde en suite completa; diff de vistas/CSS vacío. Ningún cambio propio en `app/`, `resources/`, `tests/`, rutas o configuración. |

## H1 — Una inscripción hace deudor a quien aún no ingresó

**Reproducción:** fijar Wings en 20/10/2026; configurar inscripción $5.000; dar de alta mediante POST `/alumnos` un alumno ficticio con ingreso 01/11/2026 y plan $30.000. Consultar Cobranza con `estado=TODOS`, selección de Cobrar e Inicio operativo.

**Esperado por el punto 7:** AL_DIA; no sumar en Cobrar, vencidos ni en plazo. **Obtenido:** EN_PLAZO; saldo $5.000; `conDeuda=1`, alumno incluido en Cobrar, `vencidos=0`, `en_plazo=1`. La cuota de noviembre sí queda excluida (`cuotas=0`); el saldo proviene de la inscripción (`inscripcion=5000`). Se guardaron todos esos resultados antes de ejecutar la primera aserción fallida.

Evidencia: control 11 y `controles.jsonl`, caso `futuro_antes_mes`. Cuerpo leído: `CobranzaEstadoService::inscripcionPendientePorAlumno` (219), `calcularEstadoInscripcion` (786), `saldoDeAlumnos` y `estaImpaga`. La inscripción no tiene el corte de mes futuro que sí aplica a la cuota; `CajaWebController::cobrarCuotaSelect` y `OperativoDashboardController::index` consumen esos resultados. Es un incumplimiento de la orden de verificación; no se decidió una regla nueva sobre cuándo cobrar inscripción.

## H2 — Fecha mal formada al editar devuelve 500

**Reproducción:** alumno con ingreso 20/10/2026; PUT JSON a `/alumnos/{id}` como ADMIN, con demás datos válidos y `fecha_alta=no-es-fecha`.

**Esperado:** rechazo de validación 422 en castellano, sin escrituras. **Obtenido:** 500 por `Carbon\\Exceptions\\InvalidFormatException`; fecha de BD conservada. `AlumnoWebController::validationRules`, línea 532, intenta `Carbon::parse($fechaNueva)` para comparar antes de que actúe la regla `date`. La comparación fue incorporada en el commit revisado.

Evidencia: control 06, `controles.jsonl` (`edicion_fecha_mal_formada`) y salida/JUnit. La excepción se registró en entorno de pruebas; esto no demuestra exposición de traza en producción.

## H3 — El navegador usa otro reloj para el límite

**Reproducción en Chrome real:** reloj de la computadora en octubre de 2026; servidor local con `FECHA_SIMULADA=2026-11-01 10:00:00`. Entrar con usuario ficticio y abrir `/alumnos/create`; ingresar 01/12/2026.

**Esperado:** límite 31/12/2026, coherente con Wings. **Obtenido:** atributo `max=2026-11-30`, `rangeOverflow=true`, `customError=true`, aviso «La fecha de ingreso no puede ser posterior al 30/11/2026.». Esa misma fecha es válida y se persiste al enviarla directamente al servidor bajo reloj 01/11 (control 15). La divergencia es visible aunque la validación del servidor sea correcta.

Cuerpos leídos: `resources/js/alumnos-form.js` (163–206) usa `new Date()` si falta `dataset.max`; el input de `resources/views/alumnos/_form.blade.php` (62–73) no aporta ese dato. `AppServiceProvider` aplica el reloj simulado fuera de unit tests; el servidor del recorrido usó entorno local y guardia de base propia por petición.

Evidencia: `servidor-desfase.json`, `servidor-peticiones.jsonl`, `recorrido-navegador.json`, `02-desfase-fecha-invalida.png`. En fase normal, servidor y navegador concordaron en 30/11 y el día límite quedó válido. Después del primer mensaje específico, la previsualización asíncrona ante 422 lo reemplazó por «No se pudo verificar la cuota. Revisá la conexión y la fecha antes de guardar.»; ambos estados quedan distinguidos en el JSON. No se simuló el reloj del navegador mediante código inyectado.

## Puertas que escriben o podrían escribir fecha de ingreso

| Puerta / cuerpo leído | Valida T50 | Alcance comprobado |
|---|---|---|
| `AlumnoWebController::store` | Sí | Web alcanzable; rechazo previo a la transacción comprobado. |
| `AlumnoWebController::update` / `validationRules` | Sí si cambia | Web alcanzable; equivalencias e históricos pasan, fecha mal formada falla H2. |
| `PrimeraCargaExcelService::revisar` / `cargar` | Sí | Revisión nuevamente dentro de cargar, antes de altas; casos texto, serial y revisión vieja comprobados. |
| `AlumnoWebController::toggleActivo` / `PagoCuotaService::crearCuotaReingreso` | No escriben `fecha_alta` | Reactivación ejecutada: conserva ingreso futuro, no crea cuota de octubre. La fecha de reingreso es otro campo. |
| `Alumno::boot` al crear | No como invariante global | Completa fecha actual si falta; no bloquea por sí mismo una fecha explícita fuera del límite. Revisado en fuente. |
| `AlumnoController`, `Admin/AlumnoController` y sus FormRequests | Sin regla T50; no admiten `fecha_alta` en datos validados | API deshabilitada en bootstrap; sin rutas hacia esos controladores. POST `/api/alumnos` da 405 por fallback web GET, no crea registros. |
| `DemoSeeder` | No | Fecha explícita; ejecución CLI, bloquea producción. No ejecutado. |
| `PrimeraCargaCompletaSeeder` | No | Fecha explícita; ejecución CLI, bloquea producción. No ejecutado. |
| `PrimeraCargaAlumnosSeeder` | No | `updateOrCreate` con fecha explícita; comprobación propia de julio de 2026, no límite T50; bloquea producción. No ejecutado. |
| `ReportesEscenarioSeeder` | No | Fecha fija 01/03/2026; guardia de prefijo de base testing y alumnos vacíos. No ejecutado. |
| `EjemploCompletoSeeder` | No | Fechas fijas enero de 2026, sin guardia T50. CLI; no se encontró llamada HTTP en el barrido de fuente. No ejecutado. |
| `DatabaseSeeder` | No crea alumnos | Solo catálogos; no ejecuta los cinco seeders anteriores. |

El inventario combinó `search_graph`, `trace_path`, `get_code_snippet`, búsquedas de `fecha_alta` y de creaciones/actualizaciones de Alumno en `app/` y `database/`, y lectura de los cuerpos reales. El índice devolvió cobertura no disponible/metadatos cambiados y Blade no utilizable: por eso las conclusiones se apoyan en fuente y pruebas, no en ausencia de nodos. `cobertura-indice.json` conserva el resultado inicial. No se afirma exhaustividad del grafo, de herramientas externas ni de SQL manual fuera del repositorio.

## Resultados y reproducción

- Suite completa existente: **1017 aprobadas, 6 omitidas, 8791 aserciones, 984,61 s, salida 0**. `suite-completa.txt`, `contexto-suite.json` y `resultado-suite.json`. Una sola suite: 179 observaciones cada 5 s sin procesos ajenos; `control-procesos-suite.json`. Huellas de fuentes antes/después: cero cambios; T34 HTML generado por suite restaurado y excluido.
- Controles propios finales: **16 pruebas, 115 aserciones, 2 fallas, 20,240 s, salida 1**. Las fallas son H1 y H2; H3 se comprobó en navegador. La primera corrida tenía además una expectativa propia incorrecta (404 en vez de 405 para API apagada): se corrigió el control según rutas reales y se ejecutaron los 16 nuevamente. Se conserva la salida final; se eliminan duplicados de la corrida preliminar por higiene.
- Build: `npm run build`, Vite 7.3.6, **salida 0**, 13,50 s; `build.txt`.
- Guardianes documentales posteriores al cambio de tablero: **7 aprobadas, 34 aserciones, salida 0**, 0,42 s; `guardianes-documentales.txt`. Sintaxis de los cinco PHP auxiliares sin errores. Salidas de consola normalizadas a UTF-8 sin colores ni espacios finales; contenido de los resultados conservado.
- Chrome local: usuario/datos ficticios, base comprobada por cada petición real. Primera fase sin simulación; segunda con servidor en noviembre y sesiones de base de datos para el reloj simulado. Los intentos iniciales con sesiones de archivo volvieron al login; no se atribuye causa sin prueba. Servidor auxiliar detenido al terminar.
- Continuación del mismo pedido (adjunto idéntico, SHA-256 `C076862169837F60121E0AF926E96989CE830DA45DC6A805A91154FAF153759B`): se completó teclado real y selección incremental nativa. `recorrido-teclado-nativo.json` conserva estado inmediato y el posterior aviso genérico asíncrono; `03-fecha-tipeada-rechazada.png` muestra el rechazo visible tras Guardar. No hay POST `/alumnos` en el registro de ese intento; las respuestas 422 de previsualización son esperadas. Consola observada nuevamente vacía. Las 612 fuentes de la completa siguen con la misma huella; no corresponde repetir esa suite por evidencia adicional.
- [Auditoría final del alcance](evidencia-verificacion-t50-codex/auditoria-final.json): diez puntos contrastados con controles, cuerpos de las regresiones H3/H5, salidas, recorridos y tablero vigente. El resultado es terminar la verificación y devolver la implementación; no aprobarla.

Ejecutar desde `evidencia-verificacion-t50-codex/` (MariaDB local descartable; la suite rehace su esquema):

```powershell
$env:APP_ENV='testing'
$env:APP_BASE_PATH=(Resolve-Path ../../../..).Path
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_USERNAME='root'
$env:DB_PASSWORD=''
php ../../../../vendor/phpunit/phpunit/phpunit --configuration ../../../../phpunit.xml --colors=never --testdox ControlT50Test.php
```

Se espera salida 1 mientras H1/H2 no estén corregidos. `preparar-navegador.php`, `verificar-entorno.php`, `verificar-servidor.php` y `router-local.php` quedan como arnés reproducible, con guardias de entorno/base, no como cambios de aplicación. La preparación requiere base propia sin usuarios/alumnos y rehúsa sobreescribirlos; no ejecutarla sobre los datos del club. Las credenciales del arnés son ficticias; no hay exportación de usuarios ni hashes.

## No verificado e inferencias

No se aprobó diseño ni se heredaron como recorrido real los HTML/capturas offline de Gemini. No se consultó a Carlos nuevamente por ese aspecto: las fallas de lógica ya obligan a devolver. No se recorrieron todas las pantallas, navegadores o anchos. El calendario emergente con ratón no quedó verificable: icono, clic por coordenadas y Alt+Down no lo expusieron en AX/capturas; eso no demuestra un defecto del producto. Sí se verificaron tipeo por segmentos y elección mediante incremento nativo del mes. No se ejecutaron seeders de alumnos, API legacy, despliegues o acciones contra otras bases. El barrido de puertas no prueba qué hacen integraciones fuera del repo. La fuente explica H1–H3, pero los hechos reportados son respuestas/filas/DOM realmente observados.

La copia de ALU-007 permanece intacta. Su inciso c (`catalogo/ALU.json`, línea 389) dice que el alumno figura Al día hasta que empiece el mes. **No se propone cambiar ese esperado**: H1 impide cumplirlo cuando existe inscripción pendiente. Propuesta documental para Carlos/Claude, sin modificar la tabla: en `fuente`, línea 394, conservar la implementación histórica y agregar «Verificación Codex CAB 21/10: devuelta por inscripción futura como deuda, fecha mal formada en edición y desfase de límite en navegador; ver VERIFICACION-T50-CODEX.md».

## Higiene y publicación

Carlos pidió conservar solo lo útil y publicar exclusivamente lo propio. Se eliminan cuatro auxiliares propios no versionados: las tres salidas preliminares repetidas y `diagnostico-login.php`, ya resuelto. Se conservan controles finales, trazabilidad de suite, arnés local, recorrido y capturas. No se eliminan archivos ajenos o evidencia histórica ajena. La limpieza no cambia producto ni exige repetir una suite completa ya finalizada.

Dos commits previos locales son de Gemini (`04ee617`, `4da3662`); no pertenecen a esta entrega. La entrega inicial propia se publicó aislada en `codex/t50-verificacion-cab-20261021` (remoto comprobado en `a0dd9e3`), sin subirlos por arrastre. Este complemento se guarda localmente; el pedido nuevamente adjuntado dice «No corras git push a mano». Sin despliegue. Próximo paso: Gemini corrige H1–H3 y entrega a una nueva verificación independiente.
