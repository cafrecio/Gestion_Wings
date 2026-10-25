# Verificación independiente de T79 y T80

Codex CAB, 25/10/2026. Implementaciones de Claude: `514aefbb` (T79) y `04df7c0a` (T80).
Fuentes actuales equivalentes a esos commits en los seis archivos de aplicación examinados.
No se corrige su código, no se despliega y no se cierra ninguna de las dos tareas.

**T79: APROBADA. T80: APROBADA CON OBSERVACIÓN DOCUMENTAL** sobre los avisos anteriores a T73.
La comparación, las dirigidas, la suite completa y el recorrido de Chrome están aprobados.
Ambas tareas permanecen `a_verificar`, con `verifica=Codex`, por instrucción de Carlos.
[Huellas de fuentes verificadas](evidencia-t79-t80-codex/fuentes-verificadas.json).

## Entorno y método

- Solo `wings_testing_codex`: [conexión efectiva por `SELECT DATABASE()`](evidencia-t79-t80-codex/conexion-efectiva.json).
- Se leyó el código real después del barrido MCP: símbolos, llamadores y cobertura. El índice
  devolvió `coverage_unavailable / metadata_changed`, también después de indexar. Por eso las
  conclusiones se apoyan en cuerpos leídos y búsqueda exhaustiva de los nombres en `app/`, y en
  lectura de `routes/web.php` y `routes/api.php`; el grafo no certifica ausencia de llamadores.
- Las versiones anteriores de los dos servicios se extrajeron de `514aefbb^` a `storage/app/`,
  cambiando únicamente el nombre de clase. No se reemplazó código del checkout compartido.
  Los tres scripts originales se copiaron allí y se adaptó solo su base a Codex; los comparadores
  seleccionan la clase anterior o actual con `T79_VERSION`.
  [Preparación reproducible de copias](evidencia-t79-t80-codex/preparar-copias.ps1).
- Antes del volumen: `DB_DATABASE=wings_testing_codex php artisan migrate:fresh --seeder=CatalogosSeeder`.
  [Salida](evidencia-t79-t80-codex/migracion-volumen.txt). Luego, copia de `volumen-generar.php`:
  300 alumnos ficticios, 7.044 cuotas, 6.999 cobros, 532 cajas, 1.914 clases y 89.519 asistencias.
  [Recuento](evidencia-t79-t80-codex/volumen.json). No son datos de personas reales.
- No se usó `wings`, `wingstest` ni la base de otro agente. Del servidor se leyeron únicamente
  nombre de host y archivos de reportes CSP por SSH; sin escrituras remotas.

## T79: caché acotada, saldos y deudas

### Lote y datos nuevos

`ReporteMensualService::enLote()` activa el lote y limpia tanto la bandera como la colección en
`finally`. El lote anidado aprovecha el exterior. `movimientos()` llama a `leerMovimientos()`
directamente fuera del lote. Los únicos usos de producción de `enLote()` son `evolucion()` y
`ReporteWebController::index()`: sus callbacks leen; no cambian datos dentro del lote.

Se leyeron también los usos de `ReporteMensualService` en Cashflow y las pruebas que conservan
una instancia, validan una caja o anulan un cobro y vuelven a pedir números. El caso `$antes` /
`$despues` de `ReporteMensualTest::test_validar_caja_solo_traslada_pendiente_a_confirmado_sin_duplicar`
pasó en la corrida dirigida. La prueba T79 cuenta una sola lectura de la historia tanto en
`evolucion()` como al pedir `/reportes`.

Control independiente adicional: lote anidado, excepción después de leer, escritura de $12,37
dentro de una transacción y nueva consulta con la misma instancia: ve exactamente 1.237 centavos
más; la transacción se revierte. [Resultado y código](evidencia-t79-t80-codex/auditoria-adicional.json),
[control](evidencia-t79-t80-codex/auditar-adicional.php).

### Saldos: cálculo anterior frente al nuevo

Se compararon las salidas completas de los dos servicios, no el nuevo reporte contra su propio
atajo. El anterior suma sus filas y el actual usa SQL. Se verificaron en el código:

| Regla | Resultado |
|---|---|
| Pendientes | `reporte_tipo` prevalece; si es null se usa `rubro.tipo`. EGRESO resta el absoluto; el resto suma el absoluto, igual que `fila()`. |
| Cancelaciones | El `activos()` anterior y el `m.estado = ACTIVO` nuevo excluyen CANCELADO. |
| Cajas validadas | Ambos excluyen sus movimientos operativos; su reflejo confirmado sigue en Cashflow. |
| Corte | Ambos incluyen hasta la fecha indicada, con `whereDate` para las sumas SQL. |
| Inicio del historial | Antes de `reporte_cobertura.desde`, ambos devuelven null. |
| Centavos | El anterior usa `round(monto * 100)` por fila; el nuevo `SUM(ROUND(monto * 100))`. Los importes se guardan como DECIMAL(12,2). |

Los comparadores agregan temporalmente saldos iniciales, cajas abierta y cerrada, signos con
y sin tipo guardado, importe negativo, cancelación, fecha futura y centavos. Revierten todo.
**52 reportes mensuales**, con y sin deporte, **26 cortes de saldo** y **dos evoluciones**:
solo cambian los `id` de las filas agregadas por el comparador (todos desde 7.000, fuera de las
6.999 filas originales). Ninguna diferencia de importes, signos, fechas, conceptos o estados.
El corte previo al historial también está incluido. El informe de implementación decía 25
cortes; el script realmente produce 26.

- [Comparación detallada, con cada diferencia de ID](evidencia-t79-t80-codex/comparacion-json.json).
- [Reportes anteriores](evidencia-t79-t80-codex/reportes-antes.json.gz) / [actuales](evidencia-t79-t80-codex/reportes-ahora.json.gz), JSON completos comprimidos sin pérdida; descompresión y hash comprobados.
- [Salida de ejecución](evidencia-t79-t80-codex/comparaciones.txt).
- [Comparador independiente de JSON](evidencia-t79-t80-codex/comparar-json.php).

### Columnas del pago y anulación

Se leyó `PartesPagoCuotaService::partes()`, la relación `Pago::deudasCuota()` y el accessor
`getMontoCuotaAttribute()`. Se conservan `id`, `mes`, `anio`, `fecha_pago`, `estado`,
`detalle_anulacion`, `monto_cuota` y `monto_final`; este último es necesario cuando `monto_cuota`
es null. De las deudas se usan ID y período, y la relación agrega `pivot.monto_aplicado`.

Las partes de los **6.999 pagos**, cargados completos o con las columnas reducidas, coinciden
exactamente. Además se comprobó un pago anulado sin pivotes, con un mes atrasado y otro adelantado:
se reconstruyen 1.029 y 555 centavos desde `detalle_anulacion`, iguales en ambos modelos.
No se omite ninguna columna leída por `partes()` ni por el accessor que invoca.

### Llamadores de `saldoDeAlumnos()`

En `app/` se encontraron tres llamadas:

| Llamador | Colección recibida |
|---|---|
| `CobranzaEstadoService::listadoCobranza()` | Alumnos activos con `deudaCuotas` completa, sin closure ni filtro de relación. Reutiliza los punitorios calculados para ese mismo conjunto. |
| `CajaWebController::cobrarCuotaSelect()` | Alumnos activos con `id`/`dni`, sin precargar deudas; conserva la consulta interna. |
| `AlumnoWebController::show()` | Registros activos del mismo DNI más el alumno de la ficha; ninguno se obtiene con `deudaCuotas` precargada. |

El filtro de `deudaCuotas` de `CajaWebController::cobrar()` no alimenta a este método.
La relación `Alumno::deudaCuotas()` tampoco agrega filtros. No se encontró un llamador de
producción que le pase una relación parcial.

La comparación de listados TODOS, DEUDORES y por defecto, resumen y saldos sin relación dio
**JSON idéntico byte por byte**: [antes](evidencia-t79-t80-codex/cobranza-antes.json),
[ahora](evidencia-t79-t80-codex/cobranza-ahora.json). La dirigida T79 también compara alumnos
con y sin relación precargada. No se introduce caché de deudas entre llamadas.

## T80: política, registros y rutas

### Política vigente

El literal anterior y el actual tienen **280 caracteres idénticos**, comprobados con comparación
sensible a mayúsculas: [literales](evidencia-t79-t80-codex/politica-comparada.json).
Ahora se envía `Content-Security-Policy`; no se envía el header de solo reporte.
Las 59 respuestas recorridas en Chrome llevan exactamente ese mismo literal.
No se quitaron ni agregaron excepciones, y `style-src 'unsafe-inline'` permanece.

### Observación documental: no todos los reportes eran del ingreso

Lectura directa por SSH de los archivos conservados `csp-*.log` del sitio de prueba:
**21 registros entre el 17 y el 21/10**. Diez son del script inline de `/login`, línea 145.
Los otros **11 son del 19/10**: Alumnos (2), Clases (5, incluyendo filtro y detalle), Grupos (2)
y Profesores (2). Por lo tanto, la frase de IMPLEMENTACION-T80 y AGENTS.md §2 que los atribuye
todos a Login no coincide con los registros.

El último registro conservado sigue siendo **21/10 a las 00:07:12**, Login. No hay registros
posteriores al corte de despliegue T73 indicado en la orden, **21/10 14:04**, ni archivos CSP
de días posteriores. [Lectura resumida, con cada registro](evidencia-t79-t80-codex/csp-servidor-solo-lectura.json).

T73 (`b05bda91`) retiró bloques de esas otras vistas además del ingreso. Que los 11 avisos
correspondan precisamente a esos bloques es compatible con el cambio, pero los logs no
conservan el código del fragmento: esa atribución exacta es **inferida**. La ausencia posterior
en los archivos presentes y el recorrido actual en bloqueo sí están comprobados.
Esta observación no demuestra una falla funcional de la política actual. No se corrigió código
ni se reescribió el informe de Claude para ocultar la diferencia.

### Rutas con números y con texto

Se leyeron ambas tablas de rutas y `AppServiceProvider::boot()`. Los nueve nombres restringidos
representan IDs en las declaraciones que los usan; no hay una clave textual usando uno de esos
nombres. `{clave}`, `{dia}`, `{mes}`, `{anio}` y los nombres de recursos de la API no incluidos
en la lista conservan sus reglas. La API sigue deshabilitada en `bootstrap/app.php`; no se activó.

En el router web registrado se probaron **97 casos parámetro/ruta**: cada uno acepta `1` y
rechaza `abc`. `PATCH /configuraciones/mora_dia_desde` sigue resolviendo al controlador de
Configuración con `clave=mora_dia_desde` y sin patrón numérico para esa clave.
Este control verifica el emparejamiento, sin ejecutar escrituras del controlador.
[Resultados](evidencia-t79-t80-codex/rutas-comprobadas.json) / [control](evidencia-t79-t80-codex/auditar-rutas.php).
Las pruebas dirigidas verifican HTTP 404 para texto donde corresponde un ID y HTTP 200 para
`/liquidaciones/crear` y `/alumnos/create`; Chrome recorrió estas rutas y otros textos estáticos.

## Chrome real: 59 pantallas y control positivo

Se recorrieron **42 pantallas ADMIN, 14 OPERATIVO, 2 PROFESOR y Login**, en la aplicación local
con el volumen sintético. Todas HTTP 200: **cero eventos `securitypolicyviolation`, cero errores
de JavaScript/consola y cero páginas sin instrumento de observación**.
[Recorrido por pantalla y política de cada respuesta](evidencia-t79-t80-codex/recorrido.json).

El acceso CDP del conector rechazó la inyección inicial. Se usó un router de diagnóstico propio
que recibe la respuesta del kernel real y antepone un script externo propio al `<head>`.
No cambia los headers de la aplicación ni el código de T79/T80. Ese script registra los eventos
antes de cargar el resto. Control positivo, separado del recorrido: solo en Login con una marca
de prueba se agregó un script inline; Chrome lo **bloqueó**, el espía registró `enforce`, el
script no se ejecutó y no hubo error JavaScript. [Control](evidencia-t79-t80-codex/control-positivo-csp.json),
[router](evidencia-t79-t80-codex/router-local.php), [espía](evidencia-t79-t80-codex/espia-csp.js).
Las capturas provienen de esa aplicación real, sin alterar el diseño ni simular pantallas.

[Inicio](evidencia-t79-t80-codex/inicio-admin.png) · [Caja](evidencia-t79-t80-codex/caja.png) ·
[Reportes](evidencia-t79-t80-codex/reportes-sueldos.png) · [Liquidación](evidencia-t79-t80-codex/liquidacion-crear.png) ·
[Mostrador](evidencia-t79-t80-codex/movimiento-operativo.png) · [Grupos](evidencia-t79-t80-codex/grupos-operativo.png) ·
[Clase como profesor](evidencia-t79-t80-codex/clase-profesor.png).

## Pruebas y límites

- Dirigidas pedidas: **38 aprobadas, 271 aserciones, 190,92 s, salida 0**.
  [Salida](evidencia-t79-t80-codex/dirigidas.txt).
- Completa: **1.041 aprobadas, 6 omitidas, 8.996 aserciones, 1.059,07 s, salida 0**,
  una sola suite por máquina y en `wings_testing_codex`. Corte de 1.047 métodos permanentes.
  Se esperó una prueba ajena detectada antes del inicio; al lanzar esta corrida había cero
  procesos `artisan test`/`phpunit`. [Salida](evidencia-t79-t80-codex/suite-completa.txt).
- La primera ejecución restringida de dirigidas no pudo abrir el bootstrap del proceso hijo;
  se repitió con permisos de proceso y esa es la salida válida. No se atribuye a la aplicación.
- La equivalencia y la lectura única están medidas. Los tiempos HTTP 22,3→2,6 s y 3→0,1 s son
  mediciones del autor; esta verificación no repite su benchmark de medianas ni los certifica.
- No se probó el sitio remoto en bloqueo: solo se leyeron sus logs. No se verificó despliegue.
- No se agregaron métodos a `tests/Feature`: los controles propios viven en esta evidencia;
  el contador permanente no cambia por esta verificación.

## Entrega

Resultado completo incorporado; ambos veredictos registrados con `verifica=Codex`, sin cerrarlas.
HTML T34 devuelto con `git checkout --` después de revisar que el diff era la salida generada por
la suite y guardar copia recuperable de hash idéntico en `storage/app/verificacion-t79-t80-codex/`.
Sintaxis de los cuatro controles PHP, JavaScript y PowerShell revisada; enlaces y diff comprobados.
No hay cambios en `resources/views` ni `resources/css`.
Claves temporales fuera del repositorio y archivo local retirado; servidor y sesión cerrados.
Se sincronizó además la aprobación visual previa de T77 en ESTADO-ACTUAL al quedar ese archivo
libre de cambios ajenos; Gemini ya registró su verificación. T77 conserva su estado, sin autocierre.
Solo informe, evidencia, continuidad y tablero propios; sin corregir T79/T80 ni push manual.
[Integridad final y huellas de los artefactos](evidencia-t79-t80-codex/integridad-final.json).
