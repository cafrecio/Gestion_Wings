# Verificación independiente T65 — Codex CAB, 27/10/2026

**Resultado: DEVUELTA a Gemini.** Verificado el commit de implementación `13b85f68` sobre `10cbaaa019bb2f4fd756fbc66490ca14227a45b7`, después de `git pull --ff-only` («Already up to date»). Dos defectos alcanzables impiden aprobar: un recargo automático impago produce 500 al eliminar y la cuota eliminada sigue sumando en Sueldos. Una tercera regresión cubre la excepción sin origen, estado permitido por el esquema pero sin camino de creación identificado en la aplicación actual.

No se corrigió producto. Se agregaron **14 pruebas permanentes** (12 funcionales y dos de concurrencia), con tres regresiones deliberadamente rojas antes del arreglo. Única base utilizada: **`wings_testing_codex`**. Sin sitio de prueba, servidor, base del club, despliegue ni push manual. El aspecto lo aprueba Carlos; esta revisión comprueba reglas y comportamiento.

## 1. Defectos reproducidos y devolución concreta

### H1 — Recargo impago: botón habilitado y DELETE termina en 500 — VERIFICADO

Prueba `test_recargo_automatico_impago_no_produce_500_al_eliminar_alumno`, en [las regresiones](../../../tests/Feature/VerificacionEliminarT65CodexTest.php#L25).

1. Alumno sintético activo con cuota octubre $15.000, sin pagos ni asistencias.
2. `PunitorioService::aplicar()` genera realmente un recargo de $1.500, configuración 10%, día10, reloj27/10.
3. `esEliminable()` devuelve true. La solicitud ADMIN `DELETE /alumnos/{id}` devuelve **500**, SQLSTATE23000/1451, `cargos_alumno_deuda_cuota_id_foreign`.
4. Antes de fallar la aserción se comprueba que el rollback conserva plan y deuda: no se observó borrado parcial.

Causa comprobada: `AlumnoWebController.php:662` borra las cuotas antes del recorrido de cargos, que empieza en670. `cargos_alumno.deuda_cuota_id` es RESTRICT (`2026_09_22_180000_create_cargos_alumno.php:19`). No es un cargo pagado: se creó con el servicio automático actual, `PunitorioService.php:23–73`.

**Arreglo a cargo de Gemini:** tratar los cargos ligados a la cuota en orden compatible con sus referencias; conservar el rechazo de cualquier cobro/condonación/historia protegida y la atomicidad. La regresión espera eliminación correcta de este registro sin cobros ni asistencias, según ALU-024.a/c.

### H2 — Sueldos conserva $15.000 de una cuota eliminada — VERIFICADO

Prueba `test_el_borrado_retira_la_cuota_del_reporte_actual_de_sueldos`, [línea47](../../../tests/Feature/VerificacionEliminarT65CodexTest.php#L47).

GET real de `web.reportes.sueldos`, mesoctubre: `reporte.sin_asistencia=1500000` centavos antes de eliminar. DELETE devuelve200 y desaparece la fila `deuda_cuotas`. Otro GET del mismo mes **sigue devolviendo1500000**, cuando debe ser0. Es el importe de cuotas sin asistencia de Sueldos, distinto del contador de clases pendientes del menú.

Causa comprobada: borrado masivo `DB::table('deuda_cuotas')->delete()` en `AlumnoWebController.php:662` omite los eventos del modelo. El observer analítico sí tiene `deleted()` (`HistorialAnaliticoReportesObserver.php:34`), pero no se ejecuta para ese borrado. El último snapshot analítico queda vigente; `HistorialAnaliticoReportesService.php:127–138` lo devuelve y `ReporteSueldosService.php:62–83` lo suma sin exigir que el alumno o cuota sigan existiendo. La FK de `reporte_eventos` está definida para borrar esa otra historia en cascada: **son dos historiales diferentes**.

**Arreglo a cargo de Gemini:** registrar la eliminación en el historial analítico de manera atómica y respetar los cortes históricos. No borrar indiscriminadamente snapshots antiguos para hacer pasar el mes actual. Revalidar Sueldos, Ingresos/egresos e Inicio después del arreglo.

### H3 — Excepción sin origen: falta rechazo explicativo, termina en 500 — VERIFICADO con alcance limitado

Prueba `test_excepcion_sin_origen_debe_rechazar_con_explicacion_y_no_con_500`, [línea61](../../../tests/Feature/VerificacionEliminarT65CodexTest.php#L61). Crea una excepción cuya `registro_id` no existe; el esquema lo permite. DELETE devuelve500/1451 por `excepciones_cobranza_alumno_id_foreign`, en vez de422 con motivo. La prueba espera rechazo, **nunca borrar la excepción para habilitar el borrado**.

`Alumno::motivoNoEliminable()` (`Alumno.php:218–257`) no consulta excepciones. Sin embargo, **se refuta que todas las excepciones actuales produzcan ese500**: una excepción de admisión creada desde la ruta de asistencia conserva su origen, y el alumno se rechaza con422 por asistencia antes de llegar a la FK. La prueba de línea71 lo verifica. Los pagos, incluso anulados, también bloquean antes por `pagos()->exists()`.

**No se identificó un recorrido actual de interfaz que cree una excepción huérfana o elimine su origen conservándola.** Este es un defecto de defensa ante datos admitidos por el esquema; no se presenta como pérdida alcanzable de dinero ni como un incidente del sitio. La protección explícita y su explicación quedan a Gemini.

## 2. Los nueve puntos del objetivo

| Punto | Estado y comprobación |
|---|---|
| 1. Excepciones B3/T88 | **VERIFICADO / pista general REFUTADA.** Con asistencia real,422 y origen intacto. Sin origen,500 reproducido H3. `registro_tipo/registro_id` no tienen FK: migración `2026_10_27_150000_crear_excepciones_cobranza.php:17–18`; el inventario real de MariaDB lo confirma. |
| 2. Inscripción compartida | **VERIFICADO.** Se eliminó el alumno que era ancla del cargo, caso que no recorre la prueba original del segundo deporte. Se conserva un cargo, mismo id, importe, clave, estado, cálculo, eventos y timestamps; solo cambia `alumno_id`. Otra sincronización no duplica. Cobranza del alumno sobreviviente mantiene el saldo antes/después. **Inferencia contractual explícita:** preserva la obligación compartida aunque no deja la fila idéntica; el literal «no se toca» no describe la reasignación técnica. No se encontró duplicación ni cambio del saldo cobrable. El deporte al que se atribuye esa inscripción en Reportes sigue al nuevo ancla (`ReporteMensualService.php:171–189`): no se promete identidad de ese filtro. |
| 3. Todas las referencias | **VERIFICADO en migraciones y esquema migrado.** Inventario y tratamiento por tabla en §3; se halló el RESTRICT transitivo que causa H1 y el historial sin FK de H2. No se acepta como suficiente el inventario del informe de implementación. |
| 4. Concurrencia | **VERIFICADO con dos conexiones MariaDB.** Ocho cruces: cuota, inscripción, asistencia frente al alumno y asistencia frente a la clase, en ambos órdenes. Identificadores de sesión distintos, timeout real1s sobre SELECT FOR UPDATE de la tabla esperada, cero INSERT/UPDATE/DELETE de la segunda sesión durante la espera. Ver §4. |
| 5. Qué impide borrar | **VERIFICADO.** Pago completado y ANULADO bloquean; inscripción imputada desde otro deporte también bloquea por cargo. Cuota de septiembre sin cobros, todavía PENDIENTE, permite borrar: vencimiento no cambia por sí solo el estado a otro valor. Movimiento vinculado por `alumno_id` bloquea (original); movimiento genérico creado por la ruta de caja con nombre en observaciones y `alumno_id=NULL` **no bloquea**, y se conserva íntegro al eliminar. El nombre libre no es una relación verificable. |
| 6. Clase y serie | **VERIFICADO.** Detalle en liquidación ABIERTA/CERRADA y validación manual rechazan. Serie: cualquiera de esas tres condiciones en una sola clase devuelve422, fecha28/10/2026 en el mensaje, conserva ambas clases y ambos vínculos de profesora. Las originales cubren serie limpia y serie con asistencia, también ausencia registrada. El caso COMISIÓN cerrada sin detalle directo está comprobado por lectura de `Clase.php:138–163`; no se atribuye a una nueva ejecución aislada. |
| 7. Roles | **VERIFICADO.** ADMIN puede borrar limpio. OPERATIVO/PROFESOR forzando DELETE reciben403 y conservan registros. No hay activador en sus páginas accesibles; la profesora no accede a ficha de alumno. Rutas DELETE dentro de grupo ADMIN y defensa adicional dentro de cada destroy. |
| 8. Después de borrar | **VERIFICADO con fallo en Sueldos.** Cobranza filtrada, alumnos, clases filtradas por grupo/«finalizada», badge Sin asistencia, Reportes de alumnos, deuda en Ingresos/egresos e Inicio dejan de contar el registro. El DNI se pudo reutilizar creando otro alumno en el mismo deporte; no se afirma haber hecho un alta humana por navegador. Sueldos incumple: H2. |
| 9. Reglas de pantalla | **VERIFICADO por HTML real y archivos.** Un activador Eliminar por ficha ADMIN elegible; panel oculto dentro de la página con sus acciones de confirmación. Formularios DELETE sin `data-confirmar`, `data-confirmar-envio` ni eventos inline; manejadores externos en `alumnos-show.js:27–51` y `clases-show.js:319–334`. CSP comprueba cero bloques/eventos inline. El `data-confirmar` de crear una cuota pasada es otro flujo preexistente, no el borrado. No se aprobó aspecto ni se modificaron vistas/CSS. |

**Estado AJUSTADA, calificado:** el guard rechaza cualquier cuota cuyo estado no sea PENDIENTE (`Alumno.php:235–241`). `PagoCuotaService::ajustarDeuda()` puede producir AJUSTADA sin cobro (`:407–424`), pero su entrada está en la API deshabilitada; los usos web leídos aceptan AJUSTADA, no se verificó que la creen actualmente. No se presenta como un fallo de cuota vencida ni como un recorrido web reproducido. Gemini debe evitar confundir estado de ajuste con cobro al revisar el guard; esta observación no sustituye H1/H2.

## 3. AUD-025 — referencias reales y cascadas

Fuente completa: [claves-foraneas.tsv](evidencia-verificacion-t65-codex/claves-foraneas.tsv), consulta `information_schema.KEY_COLUMN_USAGE` + `REFERENTIAL_CONSTRAINTS` filtrada por `DATABASE()=wings_testing_codex`; [localizaciones en migraciones](evidencia-verificacion-t65-codex/migraciones.txt). No son conteos de una base del club.

| Padre | Tabla / relación | ON DELETE | Decisión de T65 |
|---|---|---|---|
| alumnos | pagos.alumno_id | CASCADE | Cualquier pago bloquea; no depende de estado. |
| alumnos | asistencias.alumno_id | CASCADE | Cualquier fila bloquea, presente o ausente. |
| alumnos | asistencia_excesos.alumno_id | CASCADE | Bloquea por existencia. |
| alumnos | movimientos_operativos.alumno_id | SET NULL | Bloquea por vínculo; nombre escrito libremente no cuenta como vínculo. |
| alumnos | deuda_cuotas.alumno_id | CASCADE | Borrado manual de las consideradas impagas; H1/H2. |
| alumnos | cargos_alumno.alumno_id | RESTRICT | Pago/condonación bloquean; sin ellos se borra o se reasigna al otro DNI. El recorrido no restringe tipo a INSCRIPCION; H1 impide llegar en el caso de recargo ligado. |
| alumnos | alumno_planes.alumno_id | CASCADE | Borrado manual. |
| alumnos | alumnos_revision_cobranza.alumno_id | CASCADE | Borrado manual. |
| alumnos | excepciones_cobranza.alumno_id | RESTRICT | Sin guard explícito: H3; con origen real el guard de ese origen evita llegar a la FK. |
| clases | asistencias.clase_id | CASCADE | Cualquier asistencia bloquea. |
| clases | clase_profesor.clase_id | CASCADE | Borrado manual, incluida serie. |

Referencias transitivas comprobadas en el mismo inventario:

- Deuda → `cargos_alumno.deuda_cuota_id` **RESTRICT**: H1. `cargo_alumno_eventos` y `pago_cargo_alumno` restringen borrar el cargo; se borran eventos a mano solo en el camino sin cobro.
- Deuda → `liquidacion_detalles.deuda_cuota_id` **RESTRICT**. El guard de alumno no consulta ese detalle expresamente; normalmente está acompañado por un cobro. No se reprodujo su creación actual con alumno vacío, por lo que no se le asigna un defecto alcanzable independiente.
- Deuda → `pago_deuda_cuota` y `reporte_eventos` **CASCADE**. La segunda define el borrado de la historia monetaria ligada a la cuota cuando se elimina el error; no protege el historial analítico, cuya vigencia incorrecta se reproduce en H2.
- Asistencia → `asistencia_excesos.asistencia_id` **CASCADE**; no llega a borrarse una asistencia por T65 porque su mera existencia bloquea.
- Clase → `liquidacion_detalles(tipo_referencia=clase, referencia_id)` **sin FK**; hay guard explícito. Alumno → detalle polimórfico tipoalumno tampoco tiene FK ni consulta específica en el guard de alumno: advertencia de cobertura, sin camino vacío actual probado.
- `reporte_analitico_historial.persona_id/origen_id` son referencias lógicas, sin FK a alumno/cuota. Deben conservar cortes anteriores y registrar baja actual, no conservar vigente el error: H2.
- Excepción → asistencia/pago por `registro_tipo/registro_id`: sin FK. La excepción no se elimina por cascada del origen; T65 no debe usar su borrado como solución.

## 4. Concurrencia: qué se ejecutó y qué no prueba

[EliminarT65ConcurrenteCodexTest.php](../../../tests/Feature/EliminarT65ConcurrenteCodexTest.php) no usa RefreshDatabase: sus fixtures se confirman antes de abrir la segunda conexión. Asserts de base descartable propia de agente y `CONNECTION_ID()` distintos; esta ejecución usa exclusivamente wings_testing_codex. Limpieza por ids propios y rollback de transacciones abiertas; no se deshabilitan FKs.

En el primer orden A retiene la fila de alumno/clase que usa destroy, B intenta el servicio real de cobro ADMIN o el controlador real de asistencia; B espera esa fila y no escribe. Después A ejecuta el destroy real y se revierte la transacción envolvente. En el segundo orden A ejecuta el cobro/admisión real, manteniendo su transacción abierta; B ejecuta destroy real y agota el timeout sobre la misma fila, sin escribir. No hay pago que desaparezca mientras B espera.

Inscripción usa `registrarPagoCuotaAdmin` con cuota0 y entregado5000; cuota usa15000 + inscripción5000. No se reemplazan los servicios de negocio por mocks; solo almacenamiento y notificaciones se aíslan. El patrón de bloqueo del operativo se confirmó en el cuerpo de `registrarPagoCuotaOperativo()` (`PagoCuotaService.php:38–58`); la ejecución concurrente de esta verificación usa ADMIN.

**Límites explícitos:** no simula dos navegadores reanudándose tras un timeout, no prueba toda combinación de altas/bajas simultáneas entre dos deportes del mismo DNI, ni generación concurrente de liquidaciones/series. La prueba original llamada «concurrencia» solo creaba un pago antes del DELETE, en una conexión (`EliminarAlumnoYClaseT65Test.php:557–573`); no se la usa como evidencia de espera real. El archivo nuevo se marcó `lento` por tiempo medido >10s, no por sus resultados: las dos pruebas pasan y se corrieron expresamente.

## 5. Casos del catálogo, leídos línea por línea

La copia de la tabla y DU-48 se leyeron, **no se modificaron**. Sus sellos históricos «no_implementado» son responsabilidad de Carlos/Claude, no se reescriben desde esta verificación.

| Expectativa | Resultado |
|---|---|
| ALU-024.a dueño elimina vacío | **Parcial:** limpio pasa; vacío con recargo automático impago falla H1. |
| ALU-024.b cobro/asistencia rechaza, explica y ofrece baja | **Verificado:** originales4/5/6/9 y excepción real; mensaje incluye inactivo. |
| ALU-024.c no deja deuda en Cobranza; inscripción compartida no se borra | **Verificado en Cobranza y preservación de saldo/cargo.** Reasignación de ancla explícita en §2; Sueldos falla H2, fuera de la vista Cobranza. |
| ALU-024.d DNI libre | **Verificado en persistencia:** recreación mismo DNI/deporte, id nuevo; sin alta humana por navegador. |
| ALU-024.e operativa no ve opción | **Verificado**, además DELETE403. |
| CLA-021.a dueño elimina clase/serie sin asistencia | **Verificado** cuando tampoco tienen validación/liquidación protegida. |
| CLA-021.b con asistencia no elimina, cancelar con motivo | **Verificado:** rechazo menciona cancelar; no se ejecutó una cancelación porque sería otra operación. |
| CLA-021.c desaparece de listados/filtros | **Verificado** por ruta de listado, filtrofinalizada y grupo; badge0 y Reportes de alumnos. |
| CLA-021.d grupo no cambia al editar | **Verificado:** PUT con otro grupo inyectado mantiene el original. `ClaseWebController.php:521–589` valida/asigna fecha/horarios, no grupo. |

## 6. Evidencia y comprobaciones finales

- [Base original](evidencia-verificacion-t65-codex/base.txt) y [JUnit](evidencia-verificacion-t65-codex/base.xml): **18 aprobadas /105 aserciones**, salida0,35,10s.
- [Área final](evidencia-verificacion-t65-codex/final-area.txt) y [JUnit](evidencia-verificacion-t65-codex/final-area.xml): **31 aprobadas /3 fallidas /270 aserciones**, salida1,36,25s. Originales18/105 verdes; nuevas funcionales9 verdes/3 rojas; concurrencia2/74 y CSP2/2 verdes. Los únicos rojos del área final son H1/H2/H3..
- [Suite rápida](evidencia-verificacion-t65-codex/rapida.txt) y [JUnit](evidencia-verificacion-t65-codex/rapida.xml): **1039 aprobadas /6 omitidas /5 fallidas /8101 aserciones**, salida1,228,26s. Tres fallas son H1/H2/H3 y otras dos de `ReporteSueldosOperativasT86Test` (`test_la_ruta_de_sueldos_muestra_el_pago_y_la_operativa`, `test_el_filtro_de_deporte_no_reparte_el_gasto_del_club`). [Repetición aislada de T86](evidencia-verificacion-t65-codex/t86-aislada.txt):6 verdes/2 rojas,36 aserciones,15,51s, salida1. Se reproduce sin ejecutar las pruebas nuevas; no se establece la causa ni se modifica T86 en esta tarea.. Los rojos H1/H2/H3 no se ocultaron con skip, cambio del esperado ni reparación propia.
- [Fuentes congeladas](evidencia-verificacion-t65-codex/fuentes.json): SHA256 de15 fuentes de producto y HEAD del corte. El producto permanece idéntico durante la verificación.
- Sintaxis de los tres PHP propios, compilación/limpieza Blade, integridad documental, contador1171, generación de tablero y diff: correctos; ver [comprobaciones.json](evidencia-verificacion-t65-codex/comprobaciones.json). La suite solicitada es la rápida; no se ejecutó una completa ni se despliega. No hay correcciones de servicios financieros que entregar en esta verificación..

Exploración con la skill codebase-memory: búsquedas, relaciones y snippets; reindexación posterior al pull. `check_index_coverage` devolvió `metadata_changed/coverage_unavailable` y Blade parcial: las conclusiones se contrastaron leyendo archivos reales, consultando el esquema migrado y ejecutando solicitudes. No se usa el grafo para certificar ausencias exhaustivas.

## 7. Próximo paso y estado

T65 **devuelto**, `hizo=Gemini`, `tiene=Gemini`, `verifica=Codex`. Gemini corrige H1/H2 y el rechazo de H3, ejecuta las regresiones sin cambiarlas para volverlas verdes y entrega a verificación independiente. Si después pasa lógica, queda `a_verificar,tiene=Carlos` por aspecto. No se considera aprobada por las capturas de implementación ni se despliega desde esta devolución.
