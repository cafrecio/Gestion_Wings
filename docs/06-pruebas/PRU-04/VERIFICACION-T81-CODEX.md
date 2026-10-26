# T81 — Verificación independiente de lógica

**Veredicto: APROBADA.** Codex CAB, 25/10/2026 (corrida UTC del 26/10, 01:58–02:00).
T81 conserva el estado `a_verificar`; esta entrega registra `verifica=Codex` y no la cierra.

Se leyeron la [implementación de Gemini](IMPLEMENTACION-T81.md) y los tres commits:
`d5c4df00` (accessor y dos vistas), `0f6ebbd5` (capturas pobladas) y `d55dcc02`
(aprobación visual de Carlos y pase de lógica). Checkout verificado: `c6555ea0`.
Carlos ya aprobó el aspecto: «Las imagenes estan OK». Esta revisión comprueba lógica;
no modifica producto ni vuelve a aprobar el diseño.

## Resultado por requisito

| Requisito | Evidencia actual | Resultado |
|---|---|---|
| Priorizar celular del alumno | Celular `+54 9 11-1234-5678` y tutor distinto `011-8765-4321`: devuelve y muestra el primero, conservando formato | Aprobado |
| Recurrir al tutor | Celular vacío y tutor cargado: devuelve y muestra el tutor. También se ejercitaron NULL y espacios con el modelo en memoria | Aprobado |
| Ningún teléfono | Accessor devuelve NULL. Cobranza deja solo el nombre, sin número, DNI, guión ni «Sin teléfono». Alumnos conserva su marcador visual `–`, ya aprobado | Aprobado |
| Cobranza sin cambios numéricos | Mismo fixture con deuda real: vista actual contra la vista obtenida del padre de T81. Coinciden todos los importes, filas y celdas posteriores a Alumno, incluidos estados y acciones | Aprobado |
| Búsqueda por DNI | GET de Alumnos devuelve solo el alumno buscado, no el control de DNI distinto; probado con ADMIN y OPERATIVO | Aprobado |
| Autocompletado por DNI | Prefijo numérico devuelve un único ID correcto y conserva DNI en `sub`; ambos roles | Aprobado |
| Ficha | Respuesta HTTP conserva DNI y teléfono del tutor; controlador y vista sin cambios frente al padre de T81 | Aprobado |
| Recibo | PDF real generado por `ReciboService`: DNI `58183001`, alumno e importe `$20,000.00` conservados; el teléfono no reemplaza DNI | Aprobado |
| Serialización y salidas Excel | `toArray()` conserva `dni`, `celular` y `telefono_tutor`, sin agregar `telefono_contacto`. Plantilla real conserva DNI, Celular y Teléfono tutor | Aprobado |

La prueba de pantallas ejercita los tres teléfonos tanto con ADMIN como con OPERATIVO,
en el listado real y en Cobranza por defecto con alumnos endeudados. Las lecturas y los GET
no cambiaron las filas de alumnos ni de deuda: se contrastaron sus datos completos antes/después.

## Código y alcance de impacto

- [`Alumno::telefonoContacto`](../../../app/Models/Alumno.php:83) solo lee `celular`, luego
  `telefono_tutor` mediante `blank()`, y finalmente devuelve NULL. No tiene setter ni escribe;
  no se incorporó a `$appends`, `$fillable` ni columnas de base.
- Los consumidores nuevos en producto son únicamente
  [Alumnos](../../../resources/views/alumnos/index.blade.php:168) y
  [Cobranza](../../../resources/views/cobranza/index.blade.php:145). La segunda sustituye
  el DNI debajo del nombre; conserva siete columnas y los cálculos existentes.
- [`AlumnoWebController::index`](../../../app/Http/Controllers/AlumnoWebController.php:85)
  y [`autocomplete`](../../../app/Http/Controllers/AlumnoWebController.php:125) siguen
  filtrando por `dni`. El JS envía el texto del buscador como `q` y el formulario GET
  conserva `search`; no se cambiaron estos archivos.
- La ficha usa los campos originales `celular` y `telefono_tutor`. El recibo prepara
  explícitamente `alumno['dni']` y lo imprime como Documento; no usa el accessor nuevo.
- En las rutas y código activos revisados no se encontró una exportación de padrón que
  consuma este accessor. Las salidas XLSX de primera carga usan columnas explícitas;
  `plantilla()` conserva sus encabezados. El respaldo manual `scripts/db-export.sh`
  trabaja con columnas SQL y excluye `users`: no instancia `Alumno`. No se ejecutó ningún dump.

Se compararon **14 archivos reales** con el checkout y el padre de T81: únicamente cambian
el modelo y las dos vistas declaradas. Controladores de búsqueda/Cobranza, servicio económico,
ficha, recibo, JS y primera carga permanecen iguales. [Huellas y alcance](evidencia-t81-codex/fuentes-verificadas.json).

Se usó `codebase-memory-mcp` en Tier 2: `index_status`, `search_graph`, `trace_path` en ambos
sentidos, `get_code_snippet`, reindexación y `check_index_coverage` sobre los archivos usados.
El grafo encontró `telefonoContacto`, pero su acceso dinámico en Blade no produjo aristas
de llamadas. La cobertura siguió indicando `metadata_changed`/`coverage_unavailable` aun tras
reindexar; la ficha tiene además rangos parciales 3–4. Por eso el grafo se usó como pista y
se verificaron los cuerpos reales, completando el rastreo literal en `app`, `resources`,
`routes`, `scripts` y `tests`. No se deduce ausencia de usos a partir de las cero aristas.
[Registro del MCP](evidencia-t81-codex/codebase-memory.json).

## Pruebas y evidencia

| Corrida | Base | Resultado |
|---|---|---|
| Verificador independiente, fuera de `tests/` | `wings_testing_codex` | **4 aprobadas, 142 aserciones**, 16,89 s |
| `php artisan test --exclude-group=lento --colors=never` | `wings_testing_codex` | **926 aprobadas, 6 omitidas, 0 fallidas, 7.425 aserciones**, 170,53 s, salida 0 |

La suite rápida incluye las cuatro pruebas permanentes de T81, búsqueda/autocomplete,
recibos, Cobranza, seguridad de exportación y guardianes de tablero/documentación/CSP.
No se ejecutó la suite completa ni se acredita el grupo `lento` con esta corrida.
No se agregó ninguna prueba permanente ni se cambió su contador.

- [Verificador reproducible](evidencia-t81-codex/VerificarT81Test.php), con guardas de
  entorno `testing`, conexión MySQL y base exacta **antes de migrar**.
- [Salida independiente](evidencia-t81-codex/verificacion-independiente.txt).
- [Vista anterior utilizada como control](evidencia-t81-codex/cobranza-antes-t81.blade.php):
  copia de Git del padre de `d5c4df00`, comprobada byte por byte salvo fin de línea y líneas vacías finales.
- [Suite rápida](evidencia-t81-codex/suite-rapida.txt) y [fechas/salida](evidencia-t81-codex/suite-rapida-meta.json).
- [Recibo PDF real](evidencia-t81-codex/recibo-contacto.pdf),
  [texto extraído](evidencia-t81-codex/recibo-contacto.txt) y
  [render revisado](evidencia-t81-codex/recibo-contacto.png).

Observación sobre la cobertura original: la prueba del tutor de Gemini carga el mismo
número en `celular` y `telefono_tutor`; no ejercita realmente la segunda rama. El verificador
independiente cubrió ese hueco con celular vacío, sin modificar la prueba ajena.

La primera corrida propia tuvo tres fallos de fixture al intentar persistir celular NULL:
la columna es NOT NULL. Se conservó [esa salida](evidencia-t81-codex/verificacion-independiente-inicial-fixture.txt)
y se corrigió únicamente el fixture a cadena vacía. Los casos NULL del accessor se prueban
en memoria. La repetición final pasó; no se atribuye al producto ese error de preparación.
El sandbox tampoco permitía al proceso hijo leer `vendor/autoload.php`; se ejecutó con el
acceso necesario y siempre en la base descartable indicada.

Se comprobó que no hubiese otra suite antes de iniciarla. La suite regeneró el HTML
histórico de T34 mediante su prueba existente; se conservó esa salida en esta evidencia
y se devolvió el archivo original a HEAD. [Registro de restauración](evidencia-t81-codex/t34-restauracion.json).

## Entrega

Lógica aprobada, sin hallazgos que requieran corrección de T81. Estado `a_verificar`,
`hizo=Gemini`, `verifica=Codex`, con la aprobación visual de Carlos conservada.
Sin cierre, despliegue, cambios de producto/tabla de pruebas, datos del club ni push.
