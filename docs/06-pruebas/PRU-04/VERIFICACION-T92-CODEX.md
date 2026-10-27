# Verificación independiente de T92 — Codex CAB

**Dictamen vigente: APROBADA en segunda vuelta sobre `6a06cf09`.** Ver sección «Segunda vuelta» al final.

**Primera vuelta: DEVUELTA a Claude.** Verificación del 27/10/2026 sobre `c30bb7c2`, sin modificar producto. La corrección conserva las cuotas creadas y la hora de la primera corrida, pero infla `a_revision` al repetir alumnos que ya estaban en Revisión. La constancia no representa los nuevos envíos reales.

## Caso concreto que falla

Un alumno activo con plan, alta anterior a los últimos 15 días y sin presentes en octubre:

1. `cobranza:generar-deudas --si-falta`, 01/11 a las 06:00: una fila de Revisión; constancia `a_revision=1`.
2. `cobranza:generar-deudas`, 01/11 a las 08:00, sin novedades: sigue la misma fila; constancia `a_revision=2`. **Esperado: 1.**
3. En el caso de tres corridas, se agrega otro alumno sin asistencia antes de la tercera: hay dos filas de Revisión, pero las constancias sucesivas dicen **1, 2, 4**. **Esperado: 1, 1, 2.**

Las pruebas inspeccionan las filas completas con alumno, período y estado; no se deduce su identidad de un conteo. [Caso repetido](evidencia-verificacion-t92-codex/revision-repetida.json) · [Caso con un alumno nuevo](evidencia-verificacion-t92-codex/revision-nueva.json) · [Prueba independiente](evidencia-verificacion-t92-codex/VerificacionT92CodexTest.php).

**Causa leída en el archivo real:** `GenerarDeudasMensualesCommand.php:161` usa `firstOrCreate` para Revisión y aumenta `$contRevision` en la línea 171 aunque la fila ya existía. La actualización añadida en T92 (`:205`) suma ese contador a `a_revision`. La segunda corrida ahora convierte la misma revisión en un nuevo incremento. Las deudas no se duplican en estos casos; el defecto demostrado es la constancia.

## Los seis puntos pedidos

| Punto | Resultado comprobado |
|---|---|
| Primera corrida, repetición manual y otra que crea algo | **Parcial.** Con dos cuotas nuevas: `creadas=2,2,3` y tres deudas de $30.000, una por alumno/período. La repetición conserva `ya_existian` de la primera corrida y el contador `sin_plan` actual. Con Revisión falla como se detalla arriba. |
| La hora no se mueve | **Pasa.** Las tres corridas en días/horas distintos mantienen `terminada_at=2026-11-01 06:00:00`. El control real de MariaDB demuestra que una actualización sin repetir el valor sí activa `ON UPDATE`. |
| Corrida cortada a mitad | **Pasa.** Excepción deliberada al crear la segunda cuota, después de persistir la primera: salida fallida y ninguna constancia de noviembre. El reintento crea solo la faltante; queda una deuda por alumno. |
| Aviso de generación con atraso | **Pasa en la recuperación inicial.** Al recuperar el 03/11: mes `11/2026`, fecha `03/11/2026`, dos cuotas creadas y un enviado a Revisión. Se comprueba el objeto de notificación y su texto Telegram. El siguiente `--si-falta` no envía otro aviso. No se envían mensajes reales. |
| No hay dos constancias del mes | **Pasa.** Tres ejecuciones dejan una fila. El índice único de `periodo` también rechaza un segundo INSERT con SQLSTATE `23000`, error MariaDB `1062`. No se declara probado el acumulado de contadores en carreras simultáneas. |
| Otros efectos de `ON UPDATE` | **Revisado en esquema y fuentes.** Hay otras dos columnas afectadas, detalladas debajo. No se cambia ninguna migración ni tabla. |

[Tres corridas con cuotas](evidencia-verificacion-t92-codex/tres-corridas-cuotas.json) · [Corte y recuperación](evidencia-verificacion-t92-codex/corrida-interrumpida.json) · [Aviso y texto](evidencia-verificacion-t92-codex/aviso-atraso.json).

En la recuperación de una corrida cortada, la primera constancia exitosa mantiene el comportamiento previo de T40: `creadas=1` en ese intento y `ya_existian=1` por la cuota persistida antes del corte. No afirma que esas dos cuotas se hayan creado en el mismo intento. La prueba permanente ya exige esa separación; no se cambia esa regla por esta revisión.

## `ON UPDATE`: comprobación e impacto

Consulta real de `information_schema.COLUMNS` en **`wings_testing_codex`, MariaDB 10.4.32**:

| Tabla y columna | Escrituras de aplicación revisadas |
|---|---|
| `generaciones_cuotas.terminada_at` | El generador inserta la primera constancia y actualiza las posteriores. Repetir explícitamente el valor evita que MariaDB lo cambie. |
| `cargo_alumno_eventos.created_at` | `InscripcionService::evento` y `PunitorioService::evento` insertan eventos con hora explícita. `PrimeraCargaExcelService::deshacer` puede eliminarlos al deshacer la carga. No encontré actualizaciones de filas existentes en esos caminos ni en las referencias de aplicación buscadas. `ReporteMensualService` usa esa fecha para el corte histórico de punitorios: una futura actualización sin conservarla podría mover ese corte. |
| `reporte_eventos.registrado_en` | `HistorialReporteObserver::registrar` inserta un nuevo delta al cambiar el saldo, con hora explícita; no reescribe el evento anterior. `MovimientoReporteObserver` consulta la primera fila para atribuir deporte. `ReporteMensualService::saldoHistorico` corta por `fecha`, no por `registrado_en`. No encontré una actualización de esa tabla en las referencias revisadas. |

[Columnas observadas](evidencia-verificacion-t92-codex/columnas-on-update.json) · [Todas las coincidencias revisadas en app/database/routes](evidencia-verificacion-t92-codex/rutas-revisadas-on-update.txt). Las tres migraciones declaran un `timestamp` sin desactivar esa propiedad implícita de este MariaDB. Este resultado describe la base descartable migrada y el código local; no certifica el esquema de un servidor al que no se accedió.

## Pruebas y evidencia

| Corrida | Resultado |
|---|---|
| Independientes finales | **6 aprobadas, 2 fallidas**, 44 aserciones, 12,795 s, salida 1. Los dos rojos son las expectativas de Revisión anteriores. |
| Área: generación, Revisión, avisos, reportes, inscripción y punitorios | **91 aprobadas**, 461 aserciones, 183,819 s, salida 0. Incluye archivos lentos del área y las diez pruebas de `GeneracionCuotasReintentoT40Test`, incluida la nueva de T92. |
| Suite rápida | **933 aprobadas, 6 omitidas**, 7.465 aserciones, 163,75 s, salida 0. |

[JUnit propias](evidencia-verificacion-t92-codex/propias-final.xml) · [Salida propias](evidencia-verificacion-t92-codex/propias-final.txt) · [JUnit área](evidencia-verificacion-t92-codex/area.xml) · [Salida área](evidencia-verificacion-t92-codex/area.txt) · [JUnit rápida](evidencia-verificacion-t92-codex/rapida.xml) · [Salida rápida](evidencia-verificacion-t92-codex/rapida.txt).

La primera ejecución independiente tuvo los mismos dos fallos (8 casos/42 aserciones). La final añade dos comprobaciones del texto renderizado del aviso: no cambia los esperados de Revisión. Sus salidas se conservan separadas. Las pruebas auxiliares están fuera de `tests/` y no alteran el contador permanente.

Entorno: `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`, `DB_HOST=127.0.0.1`, `DB_URL` vacío, caché/sesión en memoria. El [bootstrap](evidencia-verificacion-t92-codex/bootstrap.php) rechaza cualquier otro entorno/base, URL de conexión o configuración cacheada. Cada escenario independiente vuelve a comprobar la configuración y la conexión efectiva **antes de migrar**. Las notificaciones están simuladas. Se ejecutaron las corridas secuencialmente, comprobando que no hubiera otra suite PHP. No se ejecutó una suite completa ni se desplegó.

Reproducción desde la raíz, con esas variables de entorno:

```powershell
php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml --bootstrap=C:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/VerificacionT92CodexTest.php
php artisan test tests/Feature/GeneracionCuotasReintentoT40Test.php
php artisan test --exclude-group=lento --bootstrap=C:/xampp/htdocs/gestion-wings/docs/06-pruebas/PRU-04/evidencia-verificacion-t92-codex/bootstrap.php
```

## Fuentes, límites y entrega

`git pull --ff-only`: sin novedades al iniciar. MCP reindexado completo, luego búsqueda, traza y fragmento del generador; las respuestas de cobertura fueron `coverage_unavailable/metadata_changed`. Por eso el grafo se usa como pista y se completó con lectura de cuerpos y búsqueda directa, sin inferir ausencias del índice. [Grafo y cobertura](evidencia-verificacion-t92-codex/grafo-y-cobertura.json). Las [19 fuentes](evidencia-verificacion-t92-codex/fuentes.json) coinciden en contenido con `c30bb7c2`; dos archivos locales difieren solo en CRLF. Gemini publicó T32 durante esta vuelta; no se incorpora como trabajo propio.

**T92 devuelta, `tiene=Claude`, `verifica=Codex`.** Próximo paso: corregir el incremento por revisiones ya existentes y repetir ambos casos rojos conservando la hora, el acumulado de cuotas y las regresiones de T40. No se modifica el producto ni la tabla externa de pruebas. Sin servidor, sitio de prueba, base del club, despliegue ni push.

T90 conserva su dictamen aprobado y su cierre. En esta vuelta se corrige únicamente su integridad documental tras el commit concurrente que había incorporado la entrega con cambios ajenos de T85; no se reescribe ese commit. [Informe T90](VERIFICACION-T90-CODEX.md).

Los cambios concurrentes de T85 en `resources/js/ds-app.js` y las asignaciones de T86/T93 quedan fuera de esta verificación y del commit propio; sus registros del tablero se conservan. [Auditor de la evidencia](evidencia-verificacion-t92-codex/verificar-entrega.py) · [Manifiesto](evidencia-verificacion-t92-codex/INTEGRIDAD.json). El auditor no conecta a una base; distingue evidencia propia de documentos compartidos que continúan cambiando.

## Segunda vuelta — 27/10/2026, Codex CAB

**APROBADA.** `git pull --ff-only` sin novedades y MCP reindexado. Leído el cuerpo actualizado: `firstOrCreate` busca alumno y período sin filtrar el estado; `a_revision` suma solo si `wasRecentlyCreated`. La fila existente produce «ya estaba en revisión». El acumulado de cuotas, la hora explícita y el índice único se mantienen.

- **8/8 propias, 44 aserciones**, 13,721 s, salida 0: se ejecutó una copia idéntica byte por byte de la prueba original, sin cambiar ningún esperado. Sus salidas se guardan aparte: Revisión **1→1** y luego **1→1→2**; cuotas **2→2→3**. Los rojos de la primera vuelta se conservan como evidencia histórica.
- **3/3 adicionales, 24 aserciones:** filas RESUELTO con CONTINUA, INACTIVO y REACTIVADO_AUTO. Estado, resolución, nota, autor y fechas intactos tras repetir; una sola fila, sin nuevo incremento ni deuda. Fixture deliberado con alumno activo y sin deuda para alcanzar la rama de Revisión, sin convertir esas combinaciones en una nueva regla de negocio.
- **Área 45/151**, 17,210 s, salida 0, incluyendo los tres casos adicionales. Generación, Revisión y avisos; permanente T40/T92 **11/54**, incluida la regresión de Claude.
- **Rápida 934 aprobadas/6 omitidas, 7.472 aserciones**, 220,18 s, salida 0, antes de implementar T86. Todas las corridas secuenciales en `wings_testing_codex`, con guardias y notificaciones simuladas.

Corte a mitad sin constancia, reintento sin duplicados, aviso de atraso y ausencia de un segundo aviso, hora fija y rechazo SQL de otra constancia: pasaron los mismos controles originales. No se accedió al sitio ni al servidor; no se reparó la constancia histórica del sitio. T85 y los cambios concurrentes de T93 quedan fuera de esta revisión.

[Resultados y fuentes](evidencia-segunda-verificacion-t92-codex/resultados.json) · [Prueba original copiada](evidencia-segunda-verificacion-t92-codex/VerificacionT92CodexTest.php) · [JUnit propias](evidencia-segunda-verificacion-t92-codex/propias.xml) · [Revisiones resueltas](evidencia-segunda-verificacion-t92-codex/RevisionResueltaT92Test.php) · [JUnit área](evidencia-segunda-verificacion-t92-codex/area-y-resueltas.xml) · [JUnit rápida](evidencia-segunda-verificacion-t92-codex/rapida.xml).

Las fuentes actuales difieren de c30bb7c2 por la corrección revisada. El auditor de primera vuelta verifica ahora las fuentes históricas de ese commit; no exige que el código siga roto. **T92 cerrada, verifica=Codex, tiene=nadie.** Sin push manual ni despliegue. Siguiente tarea autorizada: T86.
