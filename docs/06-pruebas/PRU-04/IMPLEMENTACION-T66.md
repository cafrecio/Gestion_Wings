# T66 — Al menos una profesora por clase

Codex CAB, 19/10/2026. Implementación local; sin despliegue ni reparación de datos históricos. Revisión independiente: Claude. Aspecto: Carlos.

## Decisiones y alcance

DU-55 exige al menos una profesora activa del deporte del grupo. La verificación anterior de T34/T67 está entregada en [su informe](VERIFICACION-T34-T67-CODEX.md); esta tarea no reemplaza ni certifica la corrección posterior de Gemini.

Se detectó antes de implementar una contradicción entre el pedido/FIN-12 y la excepción de reasignación pagada del contrato de Clases §4.d/§4.i. Se registró en ESTADO-ACTUAL.md y Carlos resolvió: **«Bloquear clases en liquidaciones pagadas. En caso de que la liquidacion este cerrada, Hay que cancelar la liquidacion antes de modificar»**. La decisión se incorpora al contrato; no se inventó una excepción nueva.

## Cambios reales

- `ClaseWebController::store`: rechaza ausencia, null o selección vacía de profesoras antes del calendario, aviso y transacción de creación. Única y serie comparten validación de profesoras activas del deporte. La serie sigue siendo todo o nada.
- `validarProfesoresDelDeporte`: exige lista no vacía y conserva la comprobación de todos los IDs, actividad y deporte. Se usa en alta, edición de hoy/futuro y reasignación.
- `update`: ya no permite vaciar la selección; el rechazo revierte también cambios de fecha/horario. En clases pasadas conserva la selección histórica y el flujo de corrección desde la ficha.
- `actualizarProfesores`: transacción con bloqueo de clase; conserva roles, motivo retroactivo y choques de horario. Rechaza clases canceladas y las vinculadas a liquidaciones cerradas o pagadas. Ya no existe el permiso del servidor para forzar un cambio mediante `confirmar=true`.
- `validarCambioProfesores`: lee bajo bloqueo las liquidaciones relacionadas por detalle HORA o profesora/período COMISIÓN. Una cerrada pendiente se cancela primero; una pagada permanece intacta. No modifica detalle, total ni pago. La edición general usa esa misma protección si cambia la selección.
- Formularios Crear, Editar y panel de la ficha: indicador obligatorio y explicación del mínimo. No se agrega ni modifica JavaScript en Blade, JavaScript externo ni CSS compartido. El mensaje JSON de validación lo muestra el manejador existente de la ficha. La rama antigua de confirmación del script legado ya no tiene respuesta habilitante del servidor; su extracción general sigue fuera de T66.

## Barrido de otros caminos

| Camino encontrado | Resultado |
|---|---|
| Alta única y serie web | Corregidas antes de cualquier escritura; `ProgramacionClasesService` solo valida/genera calendario, la transacción y la selección viven en el controlador |
| Editar clase hoy/futura | Corregido el vaciado; mantiene chequeo de disponibilidad y rollback |
| Reasignar desde ficha | Corregido; clases canceladas/cerradas/pagadas protegidas, motivo de pasada conservado |
| Primera asignación a pasada histórica sin profesora | Permitida con al menos una válida y sin motivo, como §4.d; no se ejecutó reparación automática |
| Desactivar única profesora | `ProfesorWebController::toggleActivo` y `update` permiten desactivar; mantienen el vínculo pero deja de haber profesora activa. **Pendiente T46**, sin tocar ese controlador |
| Cambiar deporte de profesora existente | `ProfesorWebController::update` permite cambiar deporte, sin migrar clases anteriores. Encontrado y documentado; sin modificar ese flujo ajeno a T66 |
| `ClaseService::crearClase` y controladores API de clases | Admiten creación/asignación separadas; su consumidor HTTP está deshabilitado en `bootstrap/app.php`. No se habilitó ni modificó la API; revisar esa regla antes de reactivarla |
| Seeders de escenario y pruebas | Crean directamente por modelo; Demo/Reportes asignan profesoras a las clases activas. `ReportesAsistenciaEscenarioSeeder` tiene una cancelada sin profesora deliberada. Fixtures históricos pueden representar esos datos, sin convertirlos en altas web válidas |
| Borrado de profesora desde web | No se encontró ruta de borrado de profesora en `routes/web.php`; no se añadió ninguna |

El índice del código dio pistas de llamadas; la cobertura informó metadatos cambiados/no disponibles y se confirmó mediante archivos reales y búsqueda acotada en app, rutas y seeders. No se afirma una restricción universal a SQL o modelos: el mínimo se aplica en los flujos web activos solicitados.

## Clases existentes sin profesora — solo lectura

Se ejecutó `relevar-sin-profesora.php` con transacción READ ONLY, sin pruebas en la base del sitio ni exportación de datos personales.

| Entorno consultado el 19/10/2026 | Total | Pasadas | Hoy/futuras | Canceladas | Detalle HORA asociado |
|---|---:|---:|---:|---:|---:|
| Base descartable `wings_testing_codex`, antes de pruebas T66 | 0 | 0 | 0 | 0 | 0 |
| Sitio de prueba, base `wings` | 0 | 0 | 0 | 0 | 0 |

Se consultaron también hasta cinco filas concretas; ambas muestras están vacías. No se infieren clases reales a partir de fixtures ni se reconstruye historia COMISIÓN sin vínculo de profesora. El programa conserva ese límite explícito.

Evidencias: [local](evidencia-t66-codex/relevamiento-local.json) y [sitio de prueba](evidencia-t66-codex/relevamiento-sitio-prueba.json). SSH comprobado y consulta ejecutada con `/usr/bin/php82`; el PHP por defecto del servidor es anterior y no se usó para emitir el resultado. Ninguna clase fue corregida.

## Pruebas

16 pruebas nuevas en `tests/Feature/ClaseProfesoraObligatoriaT66Test.php`: entradas vacías/malformadas, alta única, serie sin escrituras, activa/deporte/existencia, vaciado por edición y PATCH, reemplazo válido, omisión de campo, primera asignación histórica, rollback de serie con solape posterior, motivo, canceladas, liquidación cerrada→cancelada, pagada con confirmación, COMISIÓN, permisos y formularios.

Pruebas existentes ajustadas: `EditarClaseTest::test_hoy_permite_contiguas_sin_autosolape_y_quitar_todos` pasa a exigir una profesora; la prueba de alumnos presentes ya no vacía profesoras para llegar al conflicto de alumno. Se conservan sus comprobaciones de disponibilidad, bordes y rollback. Fixtures directos de modelos para historia o API no se reescriben como si fueran altas web.

- Dirigidas: **65 aprobadas, 610 aserciones, 21,25 s**, base exclusiva Codex. [Salida](evidencia-t66-codex/pruebas-dirigidas.txt).
- Exportación: **1 aprobada, 11 aserciones**; POST/PUT inválidos y GET reales de Laravel. [Salida](evidencia-t66-codex/exportacion.txt).
- Suite completa: **920 aprobadas, 4 omitidas, 0 fallidas, 8155 aserciones, 960,48 s**, salida 0; corte de **924 métodos**, base exclusiva `wings_testing_codex`. [Salida](evidencia-t66-codex/suite-completa.txt), [JUnit](evidencia-t66-codex/suite.xml), [resultado del proceso](evidencia-t66-codex/suite-resultado.json). Nueve fuentes relevantes conservadas desde el inicio de la suite, con hashes originales y equivalencia LF para traslados de Git.
- Guardianes tras el pase del tablero y actualización documental: **9 aprobados, 43 aserciones, 7,53 s**, salida 0. [Salida](evidencia-t66-codex/guardianes-finales.txt).
- Sintaxis PHP/JavaScript de los archivos tocados y compilación/limpieza de vistas correctas; build correcto. [Sintaxis y vistas](evidencia-t66-codex/sintaxis-y-vistas.txt), [build](evidencia-t66-codex/build.txt).

No se mutó deliberadamente código para probar y no se corrieron dos procesos de prueba sobre `wings_testing_codex` a la vez. Las pruebas financieras conservan filas de detalle, marcas e importes; PAGADA se representa como fixture, sin afirmar un pago real. Los bloqueos del nuevo método no constituyen por sí solos prueba de toda la concurrencia de cierre/recalcular; no se amplía ese alcance.

## Capturas reales

[Galería de las tres vistas afectadas](evidencia-t66-codex/GALERIA.html). Nueva clase con error, Editar con error y panel de cambio de profesoras, a **1366, 360 y 375**. Control adicional del ingreso a esos anchos. **12 mediciones, 19 PNG**, sin desborde horizontal. Panel abierto con el botón real; no se simuló un guardado AJAX sobre HTML estático. La persistencia/rechazos se verifican por las rutas HTTP de las pruebas.

Método: respuestas de Laravel con datos sintéticos guardadas por TestCase, recursos compilados propios preservados y Chrome en un iframe del ancho exacto. Se adaptan solamente las URLs de recursos al servidor local y al CSS del build actual; no se fabrica markup de la aplicación. Se preservan recursos con hashes y se usa el ingreso para controlar que el método no recorte. Inspeccionadas capturas de las tres vistas en móvil/escritorio y del ingreso móvil.

Reproducir desde el repositorio: ejecutar por separado `CapturasT66Test.php --filter=test_exportar_capturas_t66` con `APP_ENV=testing`, `DB_DATABASE=wings_testing_codex`, luego `node .../conservar-recursos.mjs`, servir con PHP en 8766 usando `servidor.php` y ejecutar `capturar.mjs`. No ejecutar la exportación mientras esté corriendo una suite sobre esa base. El servidor de capturas solo sirve archivos, no operaciones de aplicación.

## Entrega y límites

Toma registrada en `bc02cef`, solo los tres archivos del tablero. T66 se entrega a Claude para revisión de código y comportamiento; Carlos aprueba el aspecto de las tres vistas. No se autocierra. No se tocaron los archivos reservados a T56/T68 ni T74. Cambios contemporáneos de otros agentes se conservan sin incorporarlos como autoría propia; la modificación ajena de `evidencia-correccion-t34/sin-presentes.html` queda fuera del commit. Sin despliegue.

Integridad comprobada por [verificar.mjs](evidencia-t66-codex/verificar.mjs): nueve fuentes, 12 medidas, 19 PNG, 102 recursos con hash, enlaces de entrega/galería, JUnit sin errores/fallos y T66 a verificar por Claude. [Resultado](evidencia-t66-codex/integridad-final.json). El script comprueba el corte entregado, no sustituye la revisión independiente ni certifica cambios posteriores.

**Verificado:** reglas y rechazos anteriores mediante rutas reales y filas persistidas, capturas y fuentes del corte. **No verificado aquí:** aprobación visual de Carlos, revisión independiente de Claude, despliegue o concurrencia exhaustiva con generación/recálculo de liquidaciones. No se heredan como hechos de otros informes.
