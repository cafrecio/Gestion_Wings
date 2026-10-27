# Verificación independiente de T90 — Codex CAB

**Resultado: APROBADA.** La clase protegida por una liquidación cerrada o pagada no se cancela por el recorrido web activo. No encontré un defecto reproducible de T90 en los siete puntos pedidos.

Implementación revisada: `563749b2`. Ejecución iniciada el 26/10/2026 y entrega el 27/10, en CAB. `git pull --ff-only`: al día. Durante la verificación Claude publicó T92 en `c30bb7c2`; las catorce fuentes revisadas de T90 siguen idénticas a `563749b2`, comprobadas byte por byte en [fuentes-t90.json](evidencia-verificacion-t90-codex/fuentes-t90.json). No se incorporó ni corrigió código de producto.

Al preparar el commit apareció la prueba ajena no versionada `tests/Feature/VerificacionT32GeminiTest.php`. Se conserva fuera de la entrega. No está en la corrida rápida acreditada de 933 aprobadas ni se usa para afirmar cobertura de T90.

## Entorno y alcance

Solo Laravel local en `testing`, conexión `mysql`, **base efectiva `wings_testing_codex`**, MariaDB **10.4.32**. Confirmado con `SELECT DATABASE(), VERSION()` después de una guardia de configuración y conexión: [resultado](evidencia-verificacion-t90-codex/entorno-efectivo.json). Los fixtures se crean con `RefreshDatabase`; la guardia propia verifica el destino antes de migrar. El bootstrap también rechaza `DB_URL` y configuración cacheada para evitar un destino heredado.

No se accedió al sitio de prueba, al servidor ni a la base del club. Sin despliegue ni push. No se modificaron vistas, CSS, JS de producto, contratos ni copia de la tabla de Carlos/Claude. Las pruebas auxiliares quedan fuera de `tests/` y no aumentan el contador permanente.

## Resultado de cada punto

| Punto pedido | Qué se comprobó | Resultado |
|---|---|---|
| 1. HORA cerrada; operativa y dueño; JSON, formulario y serie | Liquidación generada y cerrada con el servicio real. Ambos roles reciben JSON 422 con `success:false` y el mensaje exacto; formulario vuelve a la ficha con `error`. También rechaza XHR sin `Accept`. `cancelar_serie=true` desde la clase protegida no modifica tampoco las futuras de esa serie. | Aprobado. |
| 2. Pagada | Se pagó mediante `LiquidacionPagoService`, con un egreso real en la base descartable. Probado para HORA y COMISIÓN, ambos roles, tres formatos y con/sin serie. Clase, relaciones, asistencias, liquidación, detalles y cashflow quedan idénticos después del rechazo. | Aprobado. |
| 3. COMISIÓN cerrada del mes | Generada a partir de pago y asistencia: base $52.000, comisión 35%, total $18.200. Una segunda clase del mes sin detalle individual también se bloquea. Otro mes y otra profesora sin esa liquidación sí permiten cancelar. | Aprobado. |
| 4. Abierta, sin liquidación, reactivar y serie futura | 24 combinaciones de modalidades/estados/roles/formatos permitidas. Reactivación por ADMIN funciona y conserva la lista; OPERATIVO no reactiva. Serie libre cancela hoy/futuras, conserva pasada y no toca otra serie. HORA no bloquea una clase sin detalle por tener el mismo profesor/mes. | Aprobado, conservando el comportamiento anterior. |
| 5. CANCELADA por FIN-12 | Primero se cierra; luego ADMIN cancela la liquidación por la ruta real con motivo. Se acredita estado `CANCELADA` y responsable. HORA y COMISIÓN vuelven a permitir cancelar la clase, por ambos roles y formatos. Detalles e historial de la liquidación se conservan. | Aprobado. |
| 6. Otros caminos | Grafo primero; después lectura y búsqueda en `app/` y `routes/`. Única ruta activa de cancelación: `PATCH clases/{id}/cancelar`, hacia `toggleCancelada`. El `update` web no admite `cancelada`. El update de la API sí acepta ese campo, pero la API está deshabilitada: cero rutas `api/` cargadas y el PUT de prueba devuelve 405, sin cambiar filas. No apareció otro comando ni servicio activo que cancele clases. | Aprobado dentro del alcance de rutas/código actual. La API apagada no se declara segura para una futura reactivación. |
| 7. Mensaje en el panel | La ficha renderiza `panel-cancelar` y `flash-cancelar` y carga `clases-show.js`. La respuesta real 422 se guardó y se pasó al JS real con un DOM mínimo: para ambos botones muestra `✗ No se puede cancelar: la clase está en una liquidación cerrada o pagada.`, mantiene abierto el panel y restaura los botones. | Aprobado; lectura y prueba de JS, sin afirmar recorrido visual en navegador. |

Los 48 rechazos combinan HORA/COMISIÓN × CERRADA/PAGADA × ADMIN/OPERATIVO × JSON/formulario/XHR × individual/serie. Cada rechazo compara filas completas de seis tablas, no solo un contador ni el booleano de la clase. El [verificador propio](evidencia-verificacion-t90-codex/VerificacionT90CodexTest.php) agrega las 24 combinaciones permitidas y cinco controles de límites, reactivación, serie, rutas y ficha: **77 pruebas**.

## Lectura del código

- `ClaseWebController::toggleCancelada`, líneas 555–613: el control de liquidación se ejecuta dentro de la rama de cancelar, antes de validar motivo y antes de la actualización individual o masiva de serie. La reactivación conserva su rama y su autorización anterior.
- `Clase::integraLiquidacionCerrada`, líneas 138–163: HORA se determina por detalle de clase; COMISIÓN, por profesora/período y tipo de liquidación. No confunde otro mes ni otra profesora.
- Una liquidación pagada conserva `estado=CERRADA`; el pago se marca con `estado_pago=PAGADA`. Se leyó `LiquidacionPagoService::marcarComoPagada` y se comprobó pagando, no creando un estado inventado.
- `LiquidacionService::cancelarLiquidacion` cambia a `CANCELADA`, conserva el documento y su detalle; entonces el helper deja de bloquear. La prueba usa también la ruta administrativa FIN-12.
- `resources/js/clases-show.js`, panel de cancelación, toma el cuerpo JSON aunque el HTTP sea 422. Con `success:false` muestra `d.message` en `flash-cancelar`; no oculta el panel ni cambia el estado visual a Cancelada. [Respuesta real](evidencia-verificacion-t90-codex/respuesta-rechazo.json) · [prueba JS](evidencia-verificacion-t90-codex/panel-cancelar.test.cjs) · [resultado](evidencia-verificacion-t90-codex/resultado-panel.json).

Reindexación completa hecha al iniciar, antes de explorar. El rastreo encontró el método, el helper y sus consumidores. `check_index_coverage` devolvió `coverage_unavailable / metadata_changed`, incluso tras indexar: no se usó una ausencia del grafo como prueba. Se completó leyendo cuerpos y buscando escrituras en los archivos y ámbitos señalados. [Grafo acotado](evidencia-verificacion-t90-codex/grafo-cancelaciones.txt) · [cobertura y límite](evidencia-verificacion-t90-codex/cobertura-mcp.txt).

## Pruebas ejecutadas

| Corrida | Resultado | Aserciones | Duración CLI |
|---|---:|---:|---:|
| Propias, matriz y límites | 77 aprobadas | 528 | 21,62 s |
| Área de clases, asistencias, profesores y liquidaciones, incluidos archivos lentos | 160 aprobadas | 1.160 | 40,79 s |
| T90 permanente, incluida en la fila anterior | 4 aprobadas | 21 | 0,42 s en JUnit |
| Método técnico del día 23 | 1 aprobada | 7 | 13,06 s |
| Suite rápida `--exclude-group=lento` | 933 aprobadas, 6 omitidas, 0 fallidas | 7.465 | 204,82 s |
| JS real del panel con respuesta real y DOM mínimo | 2 aprobadas | Controles de mensaje, petición, panel y botones | Sin medición de rendimiento |

No se suman esos recuentos: T90 está incluida en el área y en la rápida. Los archivos y métodos lentos del área se ejecutaron sin excluirlos. No se ejecutó la suite completa; no hay cambios de implementación ni despliegue en esta verificación.

[Salidas y JUnit](evidencia-verificacion-t90-codex/LEEME.md) · [resumen de resultados](evidencia-verificacion-t90-codex/resultados-pruebas.json) · [lista exacta del área](evidencia-verificacion-t90-codex/archivos-area.txt).

La prueba técnica original exige la base de Claude y escribe resultados junto a su fuente. Para ejecutarla solo en Codex y sin pisar evidencia ajena, se conservó una copia byte por byte en el directorio propio y una subclase que sustituye únicamente `beforeRefreshingDatabase` por una guardia estricta Codex. Se ejecutó solo `test_dia_23_una_clase_de_una_liquidacion_cerrada_no_se_cancela_ni_forzando`, sin cambiar su cuerpo. [Huellas de la copia](evidencia-verificacion-t90-codex/fuente-tecnica.json) · [adaptador](evidencia-verificacion-t90-codex/SeguridadDia23T90CodexTest.php).

### Incidentes de ejecución y limpieza

Tres intentos abortaron antes de ejecutar casos porque PHP `is_readable` devolvía falso dentro del entorno restringido, incluso para `vendor/autoload.php`. Fuera de esa restricción la lectura funcionó; se mantuvieron las mismas guardias y base local. No fue un fallo de T90.

La primera corrida efectiva dio 76/77: mi expectativa de 404 para una API deshabilitada no coincidió con el 405 del router actual. Se corrigió el verificador propio para exigir 405 y, además, cero rutas API y conservación completa de filas. También se reforzó el fixture de reactivación con una asistencia real. La siguiente corrida dio 77/77; salida inicial conservada, sin atribuir ese rojo al producto.

La prueba de T34 del área y de la rápida regeneró su HTML histórico. Se registraron sus huellas y se devolvió ese archivo exactamente a HEAD al terminar; no se incorpora como evidencia de T90. [Registro de la salida lateral](evidencia-verificacion-t90-codex/salida-lateral-t34.json).

## Fuera de T90 y propuesta para la tabla

CLA-012 (aviso con lista tomada) y CLA-015 (choques y motivo al reactivar) siguen en **T62**. La reactivación actual limpia el motivo; se comprobó como compatibilidad, no como cumplimiento de CLA-015. No se devolvió T90 por esos pendientes. No se probó una carrera entre cancelar y cerrar/pagar simultáneamente; esta entrega acredita los estados ya cerrados/pagados y los caminos pedidos, no todas las intercalaciones concurrentes.

La copia `tabla-de-pruebas/catalogo/CLA.json` sigue en solo lectura. Para que sus responsables trasladen el resultado, propongo para **CLA-013**:

| Campo y línea actual | Texto actual | Texto propuesto |
|---|---|---|
| `fuente`, línea 573 | «Decidido por Carlos (DU-30, 13/10): impedirlo. Falta programar: T62. Antes: ClaseWebController::toggleCancelada no mira liquidaciones…» | «DU-30 implementado por Claude en T90, commit 563749b2. Verificación independiente de Codex aprobada: HORA/COMISIÓN, cerrada/pagada, ambos roles, formulario/JSON/XHR y serie. No desplegado por esta verificación.» |
| `tarea`, línea 580 | `T62` | `T90`; T62 conserva CLA-012 y CLA-015. |

No se cambió la respuesta de DU-30 ni la tabla externa o su copia. **T90 cerrada en el tablero, `verifica=Codex`, `tiene=nadie`.** El despliegue queda fuera de esta orden.

## Entrega e integridad documental — 27/10

El commit concurrente `691b1f83`, titulado «tablero: T85 opcion 1 para Gemini», incorporó los 50 archivos preparados para esta entrega junto con el cambio ajeno de T85 en los archivos compartidos. No se reescribe ese commit ni se atribuye a Codex su título o el cambio de T85. Las huellas originales de `tareas.json` y `TABLERO.html` quedaron desactualizadas por esa incorporación concurrente.

El auditor y el manifiesto corregidos distinguen la evidencia propia actual de los seis documentos compartidos **tal como quedaron en `691b1f83`**. Así se verifica ese corte sin congelar sus siguientes ediciones de T32/T85/T92. El estado actual de T90 también se comprueba por separado. No se repiten las pruebas de T90 por esta corrección documental, ni se cambia su dictamen. La verificación agregada de T92 tiene [informe propio](VERIFICACION-T92-CODEX.md).
