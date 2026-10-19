# Verificación independiente de T34 y T67 — Codex CAB

> Estado vigente T34: [reverificación de C1/C2 sobre c017bac3](VERIFICACION-T34-C1-C2-CODEX.md). Las secciones siguientes conservan las revisiones históricas y sus hallazgos anteriores. T67 sigue cerrada.

19/10/2026. Base exclusiva: `wings_testing_codex`. Producto sin modificaciones, sin despliegue.

## Resultado

- **T34: DEVUELTA a Gemini.** La distinción de consultas y permisos funciona, pero quedan dos huecos funcionales reproducidos: la tarjeta llama pendiente a una lista guardada y una clase de una liquidación cerrada puede volver a la cola «Sin presentes» mediante la ruta de validación.
- **T67: APROBADA y CERRADA por Codex.** Los tres roles reciben solo los sufijos; controles de acceso, homónimos, ceros iniciales y DNI ausente comprobados. Se conserva la aprobación visual de Carlos informada en el pedido de verificación; no se reabre el diseño.

Alcance revisado: `9fbec9b`, `ae7c7d9`, `c5727f0`; asignación a Codex `b6b29cb`.
El árbol estaba limpio salvo `.codex/` al iniciar. Gemini cerró T44 en `3490105` durante esta auditoría: esa verificación es ajena y no se vuelve a certificar aquí.

## Hallazgos de T34

### H1 — Una lista tomada sigue diciendo «Asistencia: Pendiente»

Reproducción con una clase pasada y dos alumnos, ambos ausentes registrados:

1. Guardar la asistencia por el POST real de Clases; se guardan las filas con presente=false.
2. Abrir Clases con `estado=sin_presentes` como ADMIN.
3. La clase sale del contador y de «Sin asistencia», pero su tarjeta muestra **«Asistencia: Pendiente»**.
4. Abrir su ficha: reconoce **«Estás corrigiendo una lista ya tomada»** y exige motivo.

La ficha y la tarjeta atribuyen estados incompatibles a la misma lista. Debe distinguirse lista no tomada de lista tomada sin presentes; no se prescribe un nuevo diseño ni una etiqueta particular.

Origen: [tarjeta compartida](../../../resources/views/clases/_card.blade.php), condición basada en la cantidad de **presentes**, no en la existencia de registros. Esa condición ya existía antes de T34; al crear el filtro nuevo quedó sin adaptar este consumidor.

Alcance: visible por navegación normal del dueño al nuevo filtro. No es un hallazgo de desbordes ni una revocación de la aprobación visual.

Prueba roja: `test_auditoria_tarjeta_no_debe_marcar_pendiente_una_lista_ya_tomada`.
[Captura real a 375](evidencia-verificacion-t34-t67-codex/capturas/sin-presentes-375-inicio.png).
[La ficha reconoce lista tomada](evidencia-verificacion-t34-t67-codex/capturas/asistencia-profesor-375-inicio.png).

### H2 — Una clase ya liquidada puede volver a «Sin presentes»

Reproducción persistida, en septiembre para respetar que el mes haya terminado:

1. Clase con lista tomada y todos ausentes; el dueño la valida.
2. `generarLiquidacionMensual` crea una liquidación por $1.000 con detalle de esa clase; `cerrarLiquidacion` la deja CERRADA.
3. Un ADMIN envía `PATCH /clases/{id}/validar`.
4. Responde 302, cambia `validada_para_liquidacion` a false y la clase reaparece en `estado=sin_presentes`, aunque su detalle cerrado sigue existiendo.
5. Se repitió con un fixture CERRADA/PAGADA: mismo resultado; el total cerrado sigue en $1.000.

Origen: [toggleValidada](../../../app/Http/Controllers/ClaseWebController.php) invierte la marca sin controlar `integraLiquidacionCerrada`; la consulta nueva no excluye clases ya liquidadas. El toggle sin esa guarda **es anterior a T34**: la auditoría encontró un hueco de integración, no afirma que Gemini haya agregado ese método.

Alcanzabilidad: ruta web activa exclusiva de ADMIN; OPERATIVO y PROFESOR obtienen 403. No se encontró un botón para desmarcar en la ficha ya validada: la reproducción es por pedido directo autenticado del dueño. No se demuestra ni se afirma pago duplicado o modificación del importe cerrado. En el segundo caso se construyó el estado PAGADA como fixture, sin ejecutar un pago.

La cola ya no representa solamente decisiones pendientes: mezcla una clase cuyo pago fue resuelto y cuyo cálculo está cerrado. Respetar la inmutabilidad y evitar esa reaparición requiere resolver la integración; no se corrige producto en esta verificación.

Pruebas rojas:

- `test_auditoria_clase_cerrada_no_debe_reaparecer_para_decidir_pago`.
- `test_auditoria_clase_pagada_no_debe_reaparecer_para_decidir_pago`.

Evidencia de filas/campos leídos después del PATCH y del total conservado: [observaciones](evidencia-verificacion-t34-t67-codex/observaciones.jsonl), casos `clase_en_liquidacion_cerrada` y `clase_en_liquidacion_pagada`.

## Matriz de requisitos y controles independientes

| Requisito / borde | Evidencia actual | Resultado |
|---|---|---|
| Cuatro consumidores de pendientes | Cuerpos de provider, index de Clases, avisos y **create** de Liquidaciones; HTTP real y datos de las vistas | Usan ausencia de registros; coinciden sin filtros adicionales |
| Clase sin lista / todos ausentes / con presente / validada / cancelada | Matriz de fixtures distintos e IDs exactos en consultas | Correcto |
| Clases de hoy, futuras y muy antiguas | Límite del día; clase de enero; acceso real con los tres roles | No se cuentan hoy/futuras; el atajo Sin asistencia permite llegar a la clase antigua |
| Paginación y filtros combinados | 25 clases, ambas páginas, fecha+deporte+grupo+profesor | Sin pérdidas ni duplicados; total y enlace conservan estado |
| Filtro Sin presentes exclusivo del dueño | ADMIN ve sus IDs; OPERATIVO/PROFESOR no ven la opción y conservan los resultados de un estado no aplicado | Correcto; se comparan conjuntos reales |
| Guardar todos ausentes por el flujo web | POST de OPERATIVO con dos alumnos, filas persistidas, menú, listado, avisos y selección de liquidación | Sale de pendientes; aparece en Sin presentes; todavía no liquida |
| Corregir ausente→presente→ausente | Profesor sin motivo: 422, sin cambios; con motivo: cambia, paga $1.000 en preview; reversión con motivo | Correcto, reclasificación comprobada |
| Decisión de pagar por ADMIN | PATCH, exclusión del filtro, generación real de liquidación y detalle referenciando la clase | Correcto en liquidación abierta |
| Prohibición de validar a OPERATIVO/PROFESOR | PATCH real, 403 y marca persistida intacta | Correcto |
| Cancelar y reactivar | Pedidos web, conservación de asistencia y regreso al filtro correspondiente | Correcto |
| Tanda con alumno ajeno | POST con un alumno del grupo y otro fuera; 422, sin inserciones parciales ni cambio de contador | Correcto |
| Asistencia futura o cancelada | POST real, 422, sin filas nuevas | Correcto |
| DNI recortado en ADMIN/OPERATIVO/PROFESOR | Respuestas HTML completas: sufijos y ausencia de cadenas completas para tres alumnos | Correcto |
| Homónimos y ceros iniciales | Dos alumnos de igual nombre con sufijos diferentes; `00123000` se presenta `…000` | Correcto |
| DNI ausente | Alumno con DNI null | La vista funciona, sin sufijo inventado |
| Privacidad fuera de Clases | PROFESOR recibe 403 en 13 accesos a Alumnos, previews/autocomplete, Caja/Cobranza, Profesores, Usuarios y Reportes | Sin DNI completo en las respuestas |
| Anónimo y usuario inactivo | GET de ficha: redirección a login, inactivo confirmado en base | Correcto |
| Estructura y funcionamiento tras cambio de tarjeta | DOM: tres tarjetas con sus casillas; navegador a 360/375/1366: contador 0→1→2→0 y sufijos | Correcto; no se introduce JS |
| Etiqueta de lista tomada | HTML y navegador | **H1, no pasa** |
| Cola luego del cierre/pago | Persistencia y HTTP real | **H2, no pasa** |

Las cuatro consultas no tienen idéntico dominio: la selección de Liquidaciones agrega el profesor; los filtros de Clases pueden acotar fecha/deporte/grupo/profesor. La comparación del contador global es contra el atajo sin esos filtros, no contra cualquier búsqueda acotada.

## Qué faltaba en las pruebas y en el prompt de Gemini

- La comparación `assertNotSame('sin_presentes', $respuesta->viewData('clasesFiltradas'))` enfrenta un texto con un paginador. Pasa sin demostrar el filtrado. Se reemplazó en la auditoría por IDs exactos y comparación con un estado no aplicado para cada rol.
- No se comprobaban la etiqueta de la tarjeta, clases cerradas/pagadas, paginación, lista antigua ni cambios reales de asistencia entre estados.
- La comprobación original de T67 era solo ADMIN. Se agregó respuesta completa para los tres roles, homónimos, sufijos con ceros, DNI ausente, accesos directos, usuario inactivo y estructura DOM/interacción.
- El conteo por profesor está en `LiquidacionWebController::create`, no en `index`, como decía el prompt.
- El informe original enumeraba PATCH de profesores entre los accesos del rol PROFESOR. La ruta está bajo autenticación, pero el cuerpo del controlador rechaza ese rol con 403. Se corrigió esa afirmación documental; no se cambió el permiso.

## Límites observados, sin inventar reglas

- Guardar `items=[]` responde success=true y no crea registros; la clase sigue pendiente. Un POST parcial con un ausente de dos alumnos basta para que se considere tomada. El navegador envía todas las tarjetas, y la enmienda actual define lista tomada por existencia de registros. Se informa el límite, sin imponer una regla nueva de completitud ni cambiar el contrato.
- No existe resolución explícita «no pagar». Mantener sin validar da importe cero y conserva la clase en Sin presentes. Se comprobó el comportamiento; no se atribuye a Carlos una aceptación de un nuevo estado de rechazo.
- No se ensayó concurrencia real entre validación y cierre: H2 ya es reproducible en secuencia. No se afirma protección concurrente.
- La verificación de importes descrita es de liquidación **por hora**. No se presenta como una auditoría completa del cálculo de comisiones.

## Ejecuciones y reproducción

Instrumental propio fuera de la suite permanente: [18 casos ampliados](evidencia-verificacion-t34-t67-codex/VerificacionAmpliadaT34T67Test.php). No cambia el contador de pruebas versionadas de `tests/`.

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:LARAVEL_STORAGE_PATH=(Join-Path (Get-Location) 'storage/app/t34-t67-auditoria-runtime')
php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t34-t67-codex/VerificacionAmpliadaT34T67Test.php tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php tests/Feature/CspSinCodigoIncrustadoTest.php
```

Resultado definitivo: **24 pruebas, 21 aprobadas, 3 fallidas, 227 aserciones, 15,36 s; salida 1**.

- Ampliadas: 18 casos, 15 pasan y 3 reproducen H1/H2; 194 aserciones, sin errores de ejecución.
- Originales de Gemini: 4 aprobadas, 31 aserciones.
- CSP: 2 aprobadas, 2 aserciones; no aumenta el código incrustado.
- [Salida definitiva](evidencia-verificacion-t34-t67-codex/auditoria-definitiva.txt).
- [Primera ejecución ampliada](evidencia-verificacion-t34-t67-codex/auditoria-inicial.txt). La extensión posterior tuvo un falso positivo de fixture: `User::update` no permite asignar `activo`; se corrigió únicamente el instrumental con `forceFill` y comprobación de la fila. El control de inactivo pasó al corregir el fixture; no se reporta como fallo del producto.

Navegador: nueve combinaciones (dos pantallas y login de control en 360/375/1366), cero desbordes y tres comprobaciones de interacción. HTML pedido a rutas reales de Laravel y capturado dentro de iframe de ancho real. No se fabrican maquetas. El POST/persistencia se verificó con solicitudes reales de las pruebas; **no se simuló un cobro ni un envío de asistencia desde el navegador estático**.

[Respuestas y hashes](evidencia-verificacion-t34-t67-codex/respuestas.json) · [Medidas, interacciones y capturas](evidencia-verificacion-t34-t67-codex/mediciones.json) · [Fuentes verificadas](evidencia-verificacion-t34-t67-codex/fuentes-iniciales.json).

Durante la suite apareció un cambio paralelo de T74 en Inicio operativo, y luego comenzaron T56/T68, fuera de las trece fuentes verificadas (doce de producto y la prueba original). No se incluye en el commit ni se certifica su comportamiento.

**Suite completa permanente: 882 aprobadas, 3 omitidas, 7823 aserciones, 1399.67 s, salida 0; corte de 885 métodos.** [Salida](evidencia-verificacion-t34-t67-codex/suite-completa.txt). No incorpora los instrumentos adicionales ni los nuevos tests paralelos posteriores al comienzo. Una suite permanente verde no invalida los tres casos rojos de esta auditoría adicional.

## Controles adicionales al terminar la suite

[Dos controles de dominio y persistencia](evidencia-verificacion-t34-t67-codex/DominioClasesT34T67Test.php): **2 aprobados, 19 aserciones, 27,67 s**, exclusivamente en wings_testing_codex. Ejecutados con `--filter=test_dominio_` para no repetir los métodos heredados del instrumental principal.

- Dos profesores comparten una clase y solo el segundo dicta otra: el contador global es 2, el primero tiene 1 pendiente y el segundo 2. Se evita inferir igualdad entre el total global y la suma de los contadores individuales.
- El profesor abre una clase asignada solo a otro profesor, como permite el contrato de dominio compartido, y sigue recibiendo únicamente el sufijo del DNI.
- Tras el PATCH de H2, tanto CERRADA/PENDIENTE como CERRADA/PAGADA conservan **todos los atributos de la fila de detalle y el total**. El problema demostrado es la marca y su regreso a la cola, no una modificación del cálculo cerrado. [Filas comprobadas](evidencia-verificacion-t34-t67-codex/detalle-economico.json).

[Salida final](evidencia-verificacion-t34-t67-codex/dominio-final.txt). La primera variante del instrumental reusaba un modelo de Clase sin refrescar al reponer la marca entre escenarios; se corrigió únicamente ese fixture con `refresh()`. No se reporta ese fallo del instrumento como defecto del producto.

**Total de controles específicos: 26 casos, 23 aprobados y 3 fallidos por H1/H2; 246 aserciones.** Son 20 propios y los 6 originales/CSP. Los dos instrumentos permanecen fuera de la suite permanente; las pruebas rojas se entregan para convertirlas en regresiones al corregir los defectos.

Sintaxis del instrumental correcta; vistas compiladas y limpiadas sin errores en almacenamiento exclusivo. Guardianes finales del tablero: **8 aprobados / 36 aserciones, 0,52 s**. No se repite el contador global de pruebas sobre los tests paralelos sin commit de otros agentes.

## Entrega

T34 queda devuelta a Gemini con H1/H2 y sus reproducciones; no se aplican correcciones de producto sin su entrega posterior. T67 queda cerrada con verifica=Codex, conservando la aprobación visual informada por Carlos en el pedido. No se enviaron mensajes a otros chats ni se desplegó.

## Verificación de la corrección

19/10/2026, **Codex CyE**. Resultado: **CON HALLAZGOS** en los criterios c/f. La corrección original de H1 y el bloqueo de H2 en el PATCH pasan. T67 permanece cerrada; no se amplía su auditoría. Sin correcciones de producto ni despliegue.

Referencias verificadas: `f2b065f7` y `c5c0f41f`, contenidos en `fb88bd3c` al comenzar; `git pull` devolvió actualizado. Se registró la toma con el script oficial del tablero. El único archivo modificado al comenzar era `evidencia-correccion-t34/sin-presentes.html`; se preservó una copia previa en almacenamiento local y se restauró al terminar la selección final, comprobando su huella. Se excluye de la entrega. La prueba permanente vuelve a escribir ese HTML como efecto secundario.

### H1 y H2 originales: comprobados

| Criterio | Comprobación actual |
|---|---|
| a. Tres estados | `_card.blade.php:83–89` usa la colección precargada: cero filas → Pendiente; filas sin presentes → Sin presentes; con un presente → Cargada. Se inspecciona el texto de cada tarjeta real, no el texto de una opción del filtro. |
| b. Tarjeta y ficha | GET reales de tres clases: sin filas, `correccionAsistencia=false`, cero presentes y sin aviso de corrección; con filas ausentes, aviso de lista tomada y cero presentes; con presente, lista tomada y contador uno. La ficha no repite literalmente el badge de la tarjeta: comunica el mismo estado mediante el aviso y el contador (`ClaseWebController.php:303–329`, `clases/show.blade.php:284,394–404`). |
| c. Consultas | La expresión nueva de la etiqueta no consulta la base; `index` precarga asistencias para hoy y listado (`ClaseWebController.php:35,63`). Pero la página completa mantiene consultas por tarjeta: hallazgo C1 abajo. |
| d. CERRADA / PENDIENTE y PAGADA, HORA / COMISIÓN | Cuatro escenarios, cada uno con marca inicial true y false, tanto JSON como formulario: **16 PATCH rechazados**. JSON 422 con success=false; formulario redirige con error. Se comparan todos los atributos persistidos de clase, liquidación y detalle antes/después; permanecen idénticos. HORA usa detalle de clase; COMISIÓN, detalle de alumno y profesor/mes. |
| e. Sin cerrada | Sin liquidación, ABIERTA y CANCELADA, tanto HORA como COMISIÓN: doce alternancias reales true/false persisten y conservan la respuesta habitual de éxito. |
| f. Otras escrituras | Grafo y barrido completo de referencias en `app/` y `routes/`, confirmados en fuente: creación web inicializa false; toggle tiene guarda; edición web solo guarda fecha/horario e ignora el campo inyectado. Hay otra escritura activa sin esa guarda al generar con incluir_hoy: hallazgo C2. El update API acepta el campo, pero su router está deshabilitado en `bootstrap/app.php:17`; no se presenta como acceso web alcanzable. |
| g. Permisos | ADMIN puede alternar fuera de cerradas; OPERATIVO y PROFESOR reciben 403 por PATCH y conservan la fila. Ambos acceden a listado/ficha del dominio compartido. Anónimo e ADMIN inactivo vuelven al login, sin cambiar la marca. Ruta bajo `ensure.admin.web`, `routes/web.php:216–221`; contrastado con PERMISOS-ROLES.md. |
| h. Pruebas de Gemini | Leídos completos los dos casos agregados. H2 usa `fresh()` para la marca, importe, detalle y pago; comprueba IDs de la cola: sí lee la base, no solo HTTP. H1 crea filas y contrasta filtros, pero su `assertSee` global es menos preciso que inspeccionar cada tarjeta. La prueba permanente de H2 solo arma HORA; COMISIÓN y JSON quedan cubiertos por el instrumento independiente. |

Los estados PAGADA y los importes de la matriz independiente son fixtures sintéticos; no se ejecutó un pago ni se certifica el circuito de comisión completo. El cierre sí se realiza con `cerrarLiquidacion` sobre septiembre, con reloj de prueba en octubre. Para ensayar ambos sentidos del bloqueo se prepara directamente la marca antes de cada petición; esa preparación no es una ruta del producto.

### C1 — La página repite el contador global por tarjeta

**Origen:** `app/Providers/AppServiceProvider.php:42,67–71`: `View::composer('*')` ejecuta el COUNT de clases pendientes para cada vista/componente, incluidas `clases._card` y sus dos botones.

**Reproducir:** como ADMIN, pedir `/clases?fecha=2026-10-10` con una clase de hoy y una del listado; calentar el render y medir SQL. Repetir con ocho de hoy y ocho del listado, con las mismas relaciones y sin superar la página de veinte. Se pasa de **33 a 75 consultas**; el COUNT idéntico pasa de **14 a 56**: 42 consultas adicionales al agregar 14 tarjetas, tres por tarjeta. Las dos lecturas agrupadas de asistencias siguen siendo dos; no hay consulta individual de asistencia introducida por la etiqueta.

[SQL completo y filas](evidencia-reverificacion-t34-codex/observaciones.jsonl) · [Resumen por consulta](evidencia-reverificacion-t34-codex/consultas-resumen.json) · [Repetición aislada roja](evidencia-reverificacion-t34-codex/consultas-repeticion.txt). El criterio de que el listado no tenga consultas por tarjeta no pasa. No se midió la latencia en producción ni se atribuye una severidad por el número de consultas.

### C2 — Otra escritura de validación persiste aunque la generación se rechace

**Origen:** `app/Http/Controllers/LiquidacionWebController.php:134–143`. `store` actualiza a true las clases de hoy de ese profesor antes de intentar generar la liquidación, sin usar `integraLiquidacionCerrada()` ni una transacción común con la generación.

**Reproducir:** generar/cerrar septiembre de un profesor, agregar una clase de hoy no validada y enviar como ADMIN `POST /liquidaciones` con ese profesor, mes=9, anio=2026, incluir_hoy=1. El servicio rechaza la liquidación duplicada y se vuelve con error, pero la clase de hoy queda validada en la base. No corresponde al mes pedido y no hubo generación exitosa. Ruta activa solo ADMIN; se comprobó por petición real.

La clase de septiembre cerrada queda intacta en esa reproducción. **No se demostró un bypass del PATCH para una clase cerrada por el flujo normal de cierre**: el otro escritor solo alcanza hoy, mientras el cierre normal exige un mes terminado. Lo demostrado es la escritura alternativa sin el control solicitado y su persistencia ante un rechazo; no se afirma un pago duplicado ni un cambio del importe cerrado.

C1 y C2 ya existían antes de `f2b065f7`: los archivos del provider y del controlador de liquidaciones coinciden con su padre `d7b0a5c8` (normalizando CRLF). [Comparación SHA-256](evidencia-reverificacion-t34-codex/antecedentes.json). Son hallazgos del alcance c/f de esta verificación, no regresiones atribuidas a Gemini. No se corrigen aquí.

### Ejecuciones, evidencia y límites

Instrumental propio fuera de `tests/`, para no modificar la suite permanente ni su contador: [CorreccionT34CodexTest.php](evidencia-reverificacion-t34-codex/CorreccionT34CodexTest.php). Rechaza cualquier aplicación/base distinta de testing/wings_testing_codex antes de preparar las tablas.

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:VIEW_COMPILED_PATH='C:/xampp/htdocs/Gestion_Wings/storage/framework/views-t34-reverificacion-codex'
php artisan test docs/06-pruebas/PRU-04/evidencia-reverificacion-t34-codex/CorreccionT34CodexTest.php tests/Feature/ClaseSinAsistenciaYDniT34T67Test.php tests/Feature/CspSinCodigoIncrustadoTest.php
```

- Primera selección: **16 aprobadas, 1 fallida, 236 aserciones, 27,10 s, salida 1**. El rojo es C1. [Salida](evidencia-reverificacion-t34-codex/dirigidas-iniciales.txt). El caso incluir_hoy inicialmente comprueba la persistencia observada; después se agrega la exigencia de no dejar validaciones nuevas tras un rechazo, para que C2 también quede rojo.
- Repetición de C1 con SQL guardado: **1 fallida, 6 aserciones, 14,02 s**. Mismos valores 33/75; no se cambió producto.
- Selección final: **16 aprobadas, 2 fallidas, 259 aserciones, 17,49 s, salida 1**. [Salida final](evidencia-reverificacion-t34-codex/dirigidas-finales.txt). Los dos rojos son C1/C2; los seis casos permanentes de Gemini y los dos CSP pasan.
- Suite completa permanente: **921 aprobadas, 4 omitidas, 0 fallidas, 8179 aserciones, 1343,15 s, salida 0**. [Salida íntegra](evidencia-reverificacion-t34-codex/suite-completa.txt). Se comprobó con Win32_Process que no había otra prueba activa inmediatamente antes de lanzarla. Sin `--exclude-group=lento`, exclusivamente `wings_testing_codex`. Los instrumentos rojos de esta auditoría no pertenecen a esa suite.

Sintaxis PHP del instrumento y JavaScript del medidor correctas; `view:cache` y `view:clear` correctos en el directorio exclusivo indicado. Guardianes finales del tablero y contador: **12 aprobadas, 55 aserciones, 1,01 s**, salida 0. [Salida](evidencia-reverificacion-t34-codex/guardianes-finales.txt).

Diez controles de navegador: listado con tres estados, tres fichas y login, cada uno a 1366 y 375 px reales mediante iframe. HTML obtenido por rutas reales; sin maqueta. Sin desbordes horizontales ni cierres div desbalanceados en esa muestra. Se miraron listado escritorio/móvil, ficha de ausentes y login móvil; no se emite aprobación estética. [Mediciones](evidencia-reverificacion-t34-codex/mediciones.json) · [Listado 375](evidencia-reverificacion-t34-codex/listado-estados-375.png) · [Listado escritorio](evidencia-reverificacion-t34-codex/listado-estados-1366.png).

Codebase-memory se usó primero para símbolos, referencias y trazado. El reindexado encontró otra operación activa; cobertura parcial/no disponible y frescura metadata_changed. Se confirmaron los archivos reales y se buscaron todas las referencias del campo en el árbol de aplicación/rutas; no se considera el grafo prueba de exhaustividad ni sus enlaces inferidos una llamada real. No se ensayó concurrencia entre cierre y validación. No se inspeccionó ni modificó la base wings del servidor.

[Huellas de fuentes al iniciar](evidencia-reverificacion-t34-codex/fuentes-inicio.json). Las once fuentes registradas permanecen idénticas tras la corrida. [Huellas finales de la suite](evidencia-reverificacion-t34-codex/fuentes-fin-suite.json). Se comprueban otra vez idénticas después de la selección y capturas finales: [cierre](evidencia-reverificacion-t34-codex/fuentes-finales.json).

Durante esta verificación apareció el commit documental paralelo `04bb9228` (T47), además de trabajo ajeno de T47/T59 en el tablero. Se conserva; el commit de esta entrega prepara únicamente T34 mediante una copia del generador oficial sobre HEAD, dejando los otros cambios sin incluir. Las once fuentes de aplicación/prueba registradas permanecen iguales.

**Entrega:** T34 se devuelve a Gemini por C1/C2, con H1 y PATCH H2 aprobados en el alcance anterior. La aprobación visual de Carlos del 19/10 («T34 visual aprobado») está registrada en `fb88bd3c` y se conserva. T67 no se reabre. No se envían mensajes a otros chats ni se aplican arreglos de producto.
