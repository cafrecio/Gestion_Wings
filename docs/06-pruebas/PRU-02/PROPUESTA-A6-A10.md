# A6–A10 — Propuestas por pantalla, en preparación

07/10/2026, Codex CyE. Main actualizado a `b49d997`, posterior a `7c5fa81`.
Main avanzó a `c65d628` durante la preparación (cierres documentales A14/A27/A53); Alumnos/CSS sin cambios respecto de `b49d997`.
Solo `wings_testing_codex`, actores ficticios; sin base del club, servidor remoto ni despliegue.
Carlos pidió recibir las pantallas una por una. Ningún defecto se cierra por su autor.

## A6 — Ficha de Clase, texto elegido por Carlos

[Visor ANTES/DESPUÉS vigente](evidencia/a6-a10/visor-a6.html).

La primera propuesta solo cambió Modificar por Editar y conservó la ubicación junto al
nombre del profesor. Carlos la rechazó: «No vamos a poner un boton ahi. Por que lo hariamos?
Hay alguna vista donde tengamos un boton colgado en cualquier lado?».
[Antecedente descartado](evidencia/a6-a10/visor-a6-descartado.html).

La propuesta de un bloque separado Profesores también se descarta por la decisión posterior:
**«Dejalo con el mismo formato que tiene originalmente y cambia solo el texto».**
Aplicado localmente: únicamente Modificar → Editar, con el botón, ubicación, estilos,
condiciones e identificadores originales. [Bloque descartado](evidencia/a6-a10/visor-a6-pie-descartado.html).

| Rol / ancho | ANTES | Texto elegido |
|---|---|---|
| ADMIN 375 | [Imagen](evidencia/a6-a10/capturas/a6-clase-admin-antes-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-admin-solo-texto-375.jpg) |
| ADMIN escritorio | [Imagen](evidencia/a6-a10/capturas/a6-clase-admin-antes-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-admin-solo-texto-desktop.jpg) |
| OPERATIVO 375 | [Imagen](evidencia/a6-a10/capturas/a6-clase-operativo-antes-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-operativo-solo-texto-375.jpg) |
| OPERATIVO escritorio | [Imagen](evidencia/a6-a10/capturas/a6-clase-operativo-antes-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-operativo-solo-texto-desktop.jpg) |
| PROFESOR 375 | [Imagen](evidencia/a6-a10/capturas/a6-clase-profesor-antes-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-profesor-solo-texto-375.jpg) |
| PROFESOR escritorio | [Imagen](evidencia/a6-a10/capturas/a6-clase-profesor-antes-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a6-clase-profesor-solo-texto-desktop.jpg) |

Alcance: solo `resources/views/clases/show.blade.php`. Sin app.css ni cambios en componentes
compartidos. Identificadores internos conservados para que el panel siga funcionando.
Pulsar Editar abrió el panel real «Profesores asignados a esta clase»; PROFESOR no recibe
ese control. Clase de mañana con 20 alumnos y nombres largos. No se verificaron aquí todos
los estados de una clase ni se corrigieron otros desbordes de su cabecera.
Vista sin errores de sintaxis; diff comprobado: una sola palabra. Capturas nuevas de los tres roles en ambos anchos. Suite completa pendiente de la entrega conjunta A6–A10; no se agregó ninguna clase CSS ni asset.

Barrido de vistas: el único texto visible Modificar encontrado era este botón. Los demás
resultados son identificadores/funciones, comentarios o ayuda («La fecha pasada no se puede
cambiar», «Dejar en blanco para no cambiar», «No podés cambiar tu propio rol», explicación de
corregir errores de importación y confirmación de cancelación para corregir asistencias).
Esos textos no son etiquetas alternativas de Editar; no se cambian.

## A7 — B elegida y aplicada localmente; A descartada

[Visor ANTES / B aprobada](evidencia/a6-a10/visor-a7.html). [Opciones históricas](evidencia/a6-a10/visor-a7-propuestas-historicas.html).

**Decisión literal de Carlos, 07/10/2026:**
> A- NUNCA EN LA PUTA VIDA, NO CAMBIAR ESO NUNCA
> B- OK espero que nadie le ponga un nombre tan largo al deporte ni al nivel

B aplicada localmente. La regla permanente del listado de Alumnos es **una tarjeta por fila**, también en escritorio; registrada en DESIGN-RULES.md. Se retiró del CSS todo el selector de dos tarjetas y se fijó explícitamente una columna de tarjetas. Varias columnas de **datos dentro** de la tarjeta siguen formando B. Sin cambios de validación ni límites a nombres por el comentario sobre los datos ficticios.
Build final A7 correcto: 14,91 s; sintaxis y compilación de vistas correctas. Capturas finales posteriores a la elección pendientes: Chrome devolvió «User unavailable» al crear pestaña y al consultar las pestañas existentes. Las imágenes de B siguientes son las aprobadas, no nuevas capturas finales. Suite completa y publicación siguen pendientes de la entrega A6–A10.

### Alternativas presentadas — antecedente, ya decidido

- **A, descartada por Carlos:** dos tarjetas por fila a partir de 1100 px; dos datos por fila en cada tarjeta. Permite comparar dos alumnos lado a lado sin extender una tarjeta a todo el ancho.
- **B, aprobada:** una tarjeta por fila; cuatro datos por fila en escritorio. Conserva el recorrido vertical actual y usa el ancho para los ocho datos.
- En 375 ambas coinciden: una tarjeta y una columna de datos, textos completos y acciones que se acomodan en dos filas. Se conserva el tamaño de botones y el interruptor existentes.
- Datos: deporte, grupo, plan activo (clases/semana), DNI, celular, edad, tutor y teléfono del tutor. Sin plan se muestra «Sin plan»; celular vacío se muestra «–». No se agregan campos ni reglas de negocio.

**Estado actual comprobado:** A7 cambió en parte. La tarjeta ya es blanca; Ver cabe en su fila en las capturas ADMIN/OPERATIVO de escritorio y 375. Sigue a ancho completo en escritorio, y plan/celular no aparecen. El punto identifica cobranza; el riel identifica deporte. No se reproduce como actual lo que decía el relevamiento del 23/09.

**Alcance de ambas:** únicamente `resources/views/alumnos/index.blade.php` y reglas nuevas en `resources/css/app.css`, todas subordinadas a `.alumnos-listado`, contenedor agregado solo a ese listado. No cambia la definición compartida de `alumno-card`, `ds-btn`, ni `x-ds.toggle`; tampoco sus componentes Blade. Roles afectados: ADMIN y OPERATIVO. PROFESOR conserva «Sin permiso», comprobado en pantalla.

Las propuestas se aplicaron temporalmente y se retiraron al terminar las capturas iniciales. Después de la elección se reaplicó solo B; A queda como antecedente rechazado. Código reproducible: [A](evidencia/a6-a10/opciones/a7-A.diff) / [B](evidencia/a6-a10/opciones/a7-B.diff). B aplicada localmente, aún sin commit ni push.

| Caso | ANTES | A | B |
|---|---|---|---|
| ADMIN desktop | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-a-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-b-desktop.jpg) |
| ADMIN 375 | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-enfoque-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-a-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-b-375.jpg) |
| OPERATIVO desktop | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-antes-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-a-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-b-desktop.jpg) |
| OPERATIVO 375 | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-antes-enfoque-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-a-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-operativo-b-375.jpg) |
| ADMIN desktop · nombre largo | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-enfoque-largo-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-a-largo-desktop.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-b-largo-desktop.jpg) |
| ADMIN 375 · nombre largo | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-enfoque-largo-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-a-largo-375.jpg) | [Imagen](evidencia/a6-a10/capturas/a7-alumnos-admin-b-largo-375.jpg) |

[Control PROFESOR](evidencia/a6-a10/capturas/a7-profesor-sin-permiso-375.jpg). Login previo del mismo marco enlazado en Método. Capturas reales originales y del después miradas por Codex; 21 alumnos totales, primera página con 12, filtro real para el nombre más largo. En el caso largo de una sola tarjeta, el límite inferior de desplazamiento deja el conteo arriba; no se recorta artificialmente.

**Verificado:** ambos diseños se renderizan y muestran plan/celular reales del escenario ficticio; nombres, deporte y grupo largos se leen completos, y Cobrar/Ver/Editar/Activo están dentro de las tarjetas. ADMIN y OPERATIVO vistos en ambos anchos. Build de propuestas correcto (35,24 s); un error inicial de compilación Blade fue corregido antes de guardar evidencia válida. Se comprobó que al retirar el prototipo el diff de Alumnos/CSS queda vacío y se reconstruyeron los assets originales. El primer guardado de A/B fue rechazado por nombres con mayúsculas; se repitieron las doce capturas con respuesta Guardado y comprobación de archivos/enlaces. [Inventario de 21 capturas A7 con dimensiones y huellas](evidencia/a6-a10/capturas-a7.json).

**Inferido del alcance CSS:** las otras 26 vistas consumidoras de alumno-card no reciben estas reglas porque no incorporan el contenedor nuevo. No se recorrieron todas nuevamente para esta propuesta. Inventario completo de consumidores en el apartado siguiente; no confundir inventario con revisión visual.

**No verificado todavía:** suite completa de la opción definitiva, dispositivos físicos, anchos intermedios ni ejecución de las acciones (sus componentes, rutas y datos se conservaron). Se agregaron solo campos y distribución; no se agregó JavaScript, style ni manejador inline. El código inline preexistente de búsqueda queda igual. La suite se corre al entregar el diseño elegido.

## A8 — Opción A elegida por Carlos, 07/10

Decisión literal: **«A8 Opcion A»**. Aplicada localmente: Grupos/Profesores muestran punto verde Activo o gris Inactivo; Niveles verde Con grupos o gris Sin grupos. Alumnos conserva su punto de cobranza. Niveles celular recibió «Niveles cel OK»; falta capturar el resultado actual del punto.

Se conserva íntegro el distintivo coloreado Activo/Inactivo de Profesores: Carlos pidió «escritorio, no le saques el boton de color». No se cambió ese bloque ni su formato. El interruptor también permanece.

Carlos rechazó las capturas que no muestran acciones: «A7 La captura de telefono no sirve. No muestra los botones de la tarjeta», «A8 Celular no se ven los botones» y «Profesores, la imagen no sirve». Las imágenes anteriores quedan como antecedente, sin certificar la entrega definitiva. Nuevas capturas deben incluir nombre/datos y pie con controles; en tarjetas largas, se necesita una secuencia de imágenes, sin reducir el tamaño del teléfono ni de sus textos.

Carlos recordó «los botones en el celular se alinean todos a la derecha». Aplicado en Alumnos/Profesores con `max-sm:justify-end`; Grupos retira el margen automático izquierdo del interruptor solo en celular con `max-sm:mr-0`. Escritorio conserva sus posiciones. El control a la izquierda de Grupos era el interruptor Activo/Inactivo, no una acción desconocida. Ningún tamaño, componente, permiso, ruta o manejador cambió.

**Fuente y alcance:** solo `alumnos/index`, `grupos/index`, `niveles/index` y `profesores/index` para estos ajustes. A8 no agrega reglas a app.css. Las reglas previas exclusivas de A7 permanecen. Nivel usa `grupos_count` que carga su controlador; no se le inventa estado Activo.

**Verificación realizada:** parches iniciales [A](evidencia/a6-a10/opciones/a8-a.diff) y [B](evidencia/a6-a10/opciones/a8-b.diff), y [ocho respuestas previas](evidencia/a6-a10/comprobacion-a8-http.json). A fue reaplicada tras la elección. [Diez respuestas posteriores](evidencia/a6-a10/comprobacion-elecciones-http.json): listados y roles, período anual/mensual e íconos del formulario. Sintaxis de seis vistas correcta, compilación de vistas correcta y build 10,22 s. Esto comprueba fuente/renderizado, no el aspecto final.

**Pendiente:** capturas finales de A7/A8 en escritorio/375 y revisión propia, incluidos casos inactivos. El control de Chrome no permite consultar ni crear pestañas en esta sesión. No se sustituyen por imágenes inventadas ni se cierra A8.

## A9 — Qué se propone integrar

Se trata del interruptor **Activo/Inactivo**, que activa o desactiva el registro. Grupos y Profesores ya usan el mismo componente `x-ds.toggle` que Alumnos; Niveles no tiene ese interruptor en la vista actual. No se propone agregarle uno.

El ajuste solicitado ahora es de ubicación: en celular, interruptor y acciones terminan a la derecha; en escritorio se conserva la separación que dejó A28 en Grupos. Aplicado localmente junto con A7/A8, sin cambiar tamaño o funcionamiento del interruptor. No se pide otra decisión sobre el tamaño: el componente ya es común. Falta revisar y mostrar las capturas completas del resultado antes de entregar A9.

## A10 — Período e íconos pedidos en la revisión

Cashflow no corresponde a la caja diaria: `CashflowWebController::index` filtra movimientos por el año seleccionado y, opcionalmente, por mes y tipo de caja. Por defecto usa el año actual completo. Ingresos/egresos siguen año/mes/caja; el filtro Tipo afecta las filas, mientras ambos totales permanecen visibles. Balance conserva su fórmula actual (saldo inicial configurado + ingresos − egresos); no se cambia la lógica contable.

Aplicado localmente un texto explícito: **Período: Año 2026 completo**, o **Período: Octubre 2026** al seleccionar ese mes. Las tres respuestas anual/mensuales del informe HTTP comprueban que el texto sigue los filtros; no hay JavaScript nuevo.

Carlos pidió «Escritorio movimiento los textos no tienen iconos. Mantengamos la consistencia visual». Agregados los íconos existentes a Tipo de movimiento, Fecha, Medio de pago, Rubro, Subrubro, Monto y Observaciones en `cashflow/movimiento`; sin nuevos estilos inline. No se modificó el formulario operativo de Caja.

«Celular movimiento, no se ve completo»: la captura debe renovarse mostrando la parte superior y el pie, con todos los campos y acciones. No se afirma que un formulario largo deba entrar entero en una pantalla de 667 px. Capturas pendientes por el bloqueo del control de Chrome. La ubicación de Nuevo y el nombre del destino todavía requieren propuesta visual; no se presentan como elegidos.

## Relevamiento de A7–A10 sobre main, antes de proponer

Las capturas iniciales ya están guardadas; A7 tiene las dos opciones del apartado anterior. A8 A elegida y aplicada localmente. A9 integra la alineación móvil solicitada. A10 incorpora período e íconos pedidos; falta propuesta visual sobre Nuevo y nombre del destino.
Se contrastó el código con los originales de escritorio de Grupos, Profesores y Niveles,
y el original móvil de Cashflow. Falta inspeccionar los restantes originales de este relevamiento.

| Defecto | Estado observado / límite | Capturas iniciales ADMIN |
|---|---|---|
| A7 | Cambió en parte. Tarjetas blancas, tres acciones dentro de su fila en escritorio; siguen a ancho completo y sin plan/celular del alumno. El dot calcula cobranza, no deporte. Original móvil inspeccionado; B elegida y aplicada localmente | [375](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a7-alumnos-admin-antes-desktop.jpg) |
| A8 | Cambió en parte. Grupos/Niveles mantienen punto gris; el punto de Profesores no se ve en la captura y sus clases calculan activo/inactivo. Alumnos ya identifica cobranza. A elegida; capturas completas posteriores pendientes | [Grupos 375](evidencia/a6-a10/capturas/a8-a9-grupos-admin-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a8-a9-grupos-admin-antes-desktop.jpg); [Niveles 375](evidencia/a6-a10/capturas/a8-a9-niveles-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a8-a9-niveles-antes-desktop.jpg); [Profesores 375](evidencia/a6-a10/capturas/a8-a9-profesores-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a8-a9-profesores-antes-desktop.jpg) |
| A9 | Cambió en parte. Grupos separa Activo a izquierda de las acciones de la derecha, como dejó A28. Profesores tiene Activo junto a acciones; ambos usan x-ds.toggle. Niveles no tiene interruptor en su vista actual. Integración móvil aplicada; capturas pendientes | Capturas de Grupos, Niveles y Profesores de la fila anterior |
| A10 | Cambió en parte. Nuevo está debajo de totales en celular, sin tapar el saldo; sigue dentro de esa barra. Su destino conserva Movimiento directo. Período e íconos agregados localmente; ubicación/nombre pendientes | [Cashflow 375](evidencia/a6-a10/capturas/a10-cashflow-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a10-cashflow-antes-desktop.jpg); [destino 375](evidencia/a6-a10/capturas/a10-movimiento-antes-375.jpg) · [escritorio](evidencia/a6-a10/capturas/a10-movimiento-antes-desktop.jpg) |

[Inventario de consumidores compartidos](evidencia/a6-a10/alcance-main.json): 27 vistas con
alumno-card, 51 con x-ds.button y 7 con x-ds.toggle antes de cambios propios. El índice MCP
reportó metadatos cambiados y lagunas de Blade/CSS; se usa búsqueda y lectura de fuente como
respaldo. Las opciones A7–A10 deberán declarar si cambian estas piezas o solo la vista local.

Gemini publicó su verificación A14/A27/A53 en `b49d997` y la pasó a Claude. Ese antecedente
se leyó para coordinar; no equivale a una nueva verificación independiente de Codex.
Se conserva intacto el cambio A14/A27/A53 ya verificado. A7 agrega a app.css solo reglas subordinadas al contenedor exclusivo del listado; no cambia las reglas compartidas anteriores.

## Método y siguiente paso

Laravel servido localmente en 127.0.0.1:8794. Marco original de 375 CSS ×667;
[login primero](evidencia/a6-a10/capturas/login-main-375.jpg). Las imágenes de opciones
móviles conservan la captura completa de 1280×900 con el marco dentro; el visor muestra
ese marco sin inventar contenido. Escritorio exportado: 1265×889. No se achicó Chrome.
Escenario documental propio: 1 prueba /2 aserciones, 9,56 s; 20 alumnos en la clase y 21
en el listado total, con filas ficticias concretas en escenario.json. No integra la suite permanente.

A6 y A7 ya tienen elección de Carlos aplicada localmente. A7 queda con Codex para capturas finales y entrega conjunta. A8 A también está elegida. A9/A10 incorporan los ajustes solicitados; falta la propuesta visual restante de A10 y renovar las capturas rechazadas. La autorización de publicación se reúne
con la propuesta completa; no hay commit/push ni implementación definitiva de estas opciones.

## Inventario alumno-card — 27 vistas de referencia

A7 modifica solo la primera de esta lista; los componentes compartidos permanecen iguales.

- `resources/views/alumnos/index.blade.php`
- `resources/views/alumnos/show.blade.php`
- `resources/views/caja/cobrar-cuota.blade.php`
- `resources/views/caja/cobrar.blade.php`
- `resources/views/caja/detalle.blade.php`
- `resources/views/caja/index.blade.php`
- `resources/views/caja/resumen.blade.php`
- `resources/views/cashflow/index.blade.php`
- `resources/views/clases/_card.blade.php`
- `resources/views/cobranza/index.blade.php`
- `resources/views/configuraciones/_campo.blade.php`
- `resources/views/configuraciones/_regla.blade.php`
- `resources/views/configuraciones/index.blade.php`
- `resources/views/deportes/index.blade.php`
- `resources/views/errors/403.blade.php`
- `resources/views/grupos/index.blade.php`
- `resources/views/liquidaciones/index.blade.php`
- `resources/views/liquidaciones/show.blade.php`
- `resources/views/movimientos/index.blade.php`
- `resources/views/niveles/index.blade.php`
- `resources/views/operativo/dashboard.blade.php`
- `resources/views/primera-carga/index.blade.php`
- `resources/views/profesores/index.blade.php`
- `resources/views/revision-cobranza/index.blade.php`
- `resources/views/rubros/index.blade.php`
- `resources/views/tipos-caja/index.blade.php`
- `resources/views/usuarios/index.blade.php`
