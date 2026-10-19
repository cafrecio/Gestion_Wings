# Verificación independiente de T34 y T67 — Codex CAB

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
