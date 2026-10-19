# T34 — Reverificación de C1/C2, Codex CyE

19/10/2026. Corrección de Gemini: `c017bac3`. Pedido directo de Carlos: volver a verificar T34.

**Resultado: APROBADA. T34 CERRADA.** C1/C2 y las regresiones dirigidas pasan. No se modifica producto, diseño ni base del servidor. T67 permanece cerrada.

## Qué se comprobó

| Área | Comprobación independiente |
|---|---|
| C1: consultas por tarjeta | GET real de Clases, calentado antes de medir. Con 2 tarjetas: **20 consultas, 1 COUNT de badge**; con 16: **20 consultas, 1 COUNT**. Antes de corregir eran 33→75 y 14→56 COUNT. SQL completo guardado. |
| C1: valor fresco | Secuencia web real: GET con 1 pendiente → PATCH de validación → GET con 0 → PATCH de desvalidación → GET con 1. La marca persistida se comprueba después de cada PATCH. Las pruebas permanentes cubren también cero→uno entre peticiones. |
| C2: reproducción original | Septiembre cerrado, clase no validada de octubre y POST de septiembre con incluir_hoy: error de duplicado; ambas clases conservan sus marcas. El mes ajeno queda fuera de la selección. |
| C2: rollback real | Dos casos, HORA y COMISIÓN: liquidación ABIERTA de octubre ya existente; clase nueva de hoy; POST del mismo octubre. Se registra **un UPDATE real de clases** antes del rechazo por duplicado y luego se leen de nuevo las filas: marca false y atributos originales intactos. Liquidación, detalles y cantidad de liquidaciones también intactos. Esto comprueba la transacción después de entrar a la escritura. |
| C2: generación exitosa | Incluir hoy valida únicamente las candidatas del profesor. Cancelada, otra profesora, ayer y futura conservan false; la presente conserva false y entra naturalmente por asistencia; la ya validada conserva true. Detalles exactos: candidata, presente y previamente validada. |
| H1 | Pendiente sin filas; Sin presentes con fila ausente; Cargada con presente. DOM de cada tarjeta y datos/aviso de ficha contrastados con registros reales. |
| H2 | CERRADA pendiente/pagada, HORA/COMISIÓN, marca inicial true/false y pedidos JSON/formulario: rechazo y conservación íntegra de clase, liquidación y detalle. Sin liquidación, ABIERTA y CANCELADA permiten alternar. |
| Permisos | ADMIN opera; OPERATIVO/PROFESOR reciben 403 al validar y conservan la marca. Listado/ficha siguen accesibles a esos roles. Anónimo e inactivo vuelven al login. Inyección del campo en edición web no cambia la validación. |

Fuentes leídas: `AppServiceProvider.php:42–92`, `LiquidacionWebController.php:120–174`, `Clase.php:138–163`, `LiquidacionService.php:29–81,401–418` y `ClaseWebController.php:655–676`; rutas y contrato PERMISOS-ROLES contrastados. El request conserva el contador usando una comprobación estricta de null, de modo que cero también se reutiliza. La selección de incluir_hoy exige mismo mes/año, profesor, hoy, no cancelada, marca false y ausencia de presentes; cada candidata comprueba la guarda de cerrada dentro de la transacción común con generación.

La prueba permanente de duplicado en septiembre, por sí sola, **no demuestra el rollback después de una escritura**: con la nueva restricción de período ya no entra a validar octubre. Los dos casos propios de octubre completan esa evidencia. Es una limitación de cobertura de aquel caso, no un defecto del producto.

## Ejecuciones y alcance

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:VIEW_COMPILED_PATH='C:/xampp/htdocs/Gestion_Wings/storage/app/t34-final-runtime/views'
php artisan test docs/06-pruebas/PRU-04/evidencia-t34-c1-c2-codex/CierreT34CodexTest.php tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php tests/Feature/CspSinCodigoIncrustadoTest.php
```

**27 aprobadas, 321 aserciones, 18,62 s, salida 0:** 14 casos propios, 11 permanentes de Gemini y 2 CSP. [Salida definitiva](evidencia-t34-c1-c2-codex/dirigidas.txt) · [Instrumento propio](evidencia-t34-c1-c2-codex/CierreT34CodexTest.php) · [SQL y filas](evidencia-t34-c1-c2-codex/observaciones.jsonl).

El primer intento tuvo 26 aprobadas/1 error del instrumento: al crear otra profesora faltaba el DNI obligatorio. Se completó únicamente ese fixture y se repitió la selección. [Primera salida](evidencia-t34-c1-c2-codex/dirigidas-iniciales.txt). Las observaciones conservan ambas ejecuciones; C1 y los dos rollbacks dieron los mismos resultados en ambas. No se atribuye ese error al producto.

**Suite rápida actual: 820 aprobadas, 4 omitidas, 6657 aserciones, 404.38 s, salida 0.** Ejecutada con `--exclude-group=lento`, misma base y caché separada. [Salida](evidencia-t34-c1-c2-codex/suite-rapida.txt).

**Regresión completa ya ejecutada en este chat:** 935 aprobadas, 4 omitidas, 8243 aserciones, 1283,46 s, salida 0, terminada a las 13:07:42 en wings_testing_codex. Incluyó expresamente las once pruebas de `ClaseSinAsistenciaYDniT34T67Test`, incluidos C1/C2. [Salida completa](evidencia-t74-codex/suite-completa.txt) · [Contexto: cero pruebas ajenas al comenzar](evidencia-t74-codex/suite-contexto.json) · [Resultado terminal](evidencia-t74-codex/suite-resultado.json).

Se conserva esa corrida como regresión general de la corrección: proveedor, controlador de liquidaciones, servicio, modelo, controlador de Clases y prueba permanente siguen idénticos a c017bac3. El proveedor ya consta con su hash corregido en el registro de aquella corrida; el cuerpo transaccional y las cinco regresiones nuevas ya estaban presentes antes de comenzarla. No se repite una completa idéntica para esos cambios. La suite rápida y las dirigidas actuales cubren además el árbol posterior. La completa anterior no se presenta como certificación de los cambios posteriores de T73 ni como una auditoría de ese trabajo.

## Pantalla, evidencias y límites

GET reales de Laravel guardados y abiertos en Chrome dentro de iframe a 375 y 1366: tres fichas, listado con los tres estados y login de control, **10 combinaciones** sin desborde horizontal ni divs desbalanceados. Se inspeccionaron listado de escritorio y ficha sin presentes a 375. No se fabricaron maquetas ni se modificó el diseño. La aprobación visual anterior de Carlos para T34, registrada en `fb88bd3c`, se conserva; esta revisión no aprueba T73.

[Mediciones](evidencia-t34-c1-c2-codex/mediciones.json) · [Listado](evidencia-t34-c1-c2-codex/listado-estados-1366.png) · [Ficha sin presentes](evidencia-t34-c1-c2-codex/ficha-0-375.png).

Los datos son sintéticos. El instrumento impide correr fuera de testing/wings_testing_codex antes de preparar tablas. Los pagos de las liquidaciones CERRADAS de la matriz son estados de fixture; no se ejecutó un pago ni se certifica el circuito completo de comisiones. No se ensayó concurrencia entre generación y cierre. Las peticiones y filas descritas sí se comprobaron; el grafo no se usa como prueba de exhaustividad (cobertura metadata_changed/no disponible; se confirmó en archivos reales).

Sin cambios de producto, permisos, contratos, suite permanente ni contador. Únicamente evidencia, informe, continuidad y estado de T34. El HTML histórico `evidencia-correccion-t34/sin-presentes.html` es reescrito como efecto secundario de la prueba original y queda fuera de la entrega.

Guardianes de documentos y tablero tras cerrar T34: **12 aprobados, 55 aserciones, 0,42 s**. Sintaxis PHP del instrumento correcta; vistas compiladas y limpiadas correctamente en almacenamiento exclusivo. [Salida final](evidencia-t34-c1-c2-codex/guardianes-finales.txt). Diff de vistas/CSS vacío; ningún cambio de aplicación pertenece a esta entrega.

[Fuentes al inicio](evidencia-t34-c1-c2-codex/fuentes-inicio.json) · [Fuentes finales](evidencia-t34-c1-c2-codex/fuentes-fin.json) · [Contexto y comparación con c017bac3](evidencia-t34-c1-c2-codex/contexto.json). Los seis archivos de código y prueba de la corrección permanecen idénticos a ese commit.
