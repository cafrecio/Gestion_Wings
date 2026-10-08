# A6–A10 — Propuestas y decisiones históricas

 A6–A10 — Propuestas por pantalla, en preparación

**A10 aprobado y aplicado localmente 08/10:** Carlos: «Me gusta, A10 Aprobado». [Entrega de períodos/acciones](IMPLEMENTACION-A10-PERIODOS.md). Día/Semana/Mes/Año, Nuevo junto al contador y título Nuevo movimiento; resultado del período sin saldo inicial. Nueve pruebas de intervalos pasan; suite 507 aprobadas/2 omitidas y 24 capturas finales correctas. Las notas de propuesta siguientes conservan el corte anterior a aplicar. Aspecto verificado por Carlos; intervalos/cálculos pendientes de otro agente, sin deploy.

**Visor central actualizado 08/10:** Carlos detectó que el enlace anterior seguía mostrando solo Año/Mes. `visor-a10.html` ahora muestra la propuesta de escritorio Día/Semana al principio, Mes/Año desplegables y acceso a las cuatro maquetas móviles. [Escritorio actualizado, enlace nuevo](evidencia/a6-a10/visor-a10-escritorio.html). Antecedentes de ubicación de Nuevo conservados en un bloque separado. Enlaces/imágenes comprobados; sin cambios de aplicación.

**Antecedente A10, maquetas antes de aprobación 08/10:** [Maquetas Día / Semana / Mes / Año](evidencia/a6-a10/visor-a10-periodos.html). Veinte capturas de Laravel real, escritorio y marco 375×667; selección de fecha, semanas lunes–domingo y cruces de mes/año. Variante Blade y controlador derivados, exclusivamente en laboratorio `wings_testing_codex`; selector Día → Semana ejercitado. La propuesta muestra ingresos − egresos como Resultado del período, sin sumar saldo inicial. No implementa saldo acumulado ni modifica la aplicación. [Controles y huellas](evidencia/a6-a10/control-a10-periodos.json). Falta elección de Carlos; si se implementan intervalos/cálculos, verificar esa lógica con otro agente.

**Antecedente A10, propuesta antes de aprobación 08/10:** [ANTES/propuesta de Cashflow](evidencia/a6-a10/visor-a10.html); [destino de Nuevo, escritorio y formulario móvil completo](evidencia/a6-a10/visor-a10-movimiento.html). Contador/Nuevo en barra propia; Limpiar junto a filtros; título «Nuevo movimiento». 28 imágenes reales de los mismos controladores y datos ficticios, incluidos año completo, Octubre y Septiembre vacío. Variantes Blade derivadas del original, solo laboratorio; no aplicadas a la aplicación. No se pide aprobar leyendo: las imágenes están guardadas y enlazadas. Falta elección de Carlos.

**A9, confirmación explícita posterior:** «Perfecto, APROBADO A-9 Entonces», Carlos, 08/10. No se repite este pedido de aprobación.

**A9 aprobado condicionado a la alineación, comprobada 08/10:** «Si quedan alineados con el boton de arriba esta OK. Chequear eso». Nuevo y último botón de cada tarjeta coinciden en x=224–320 en Grupos, Niveles y Profesores; interruptores terminan en x=320. Siete capturas nuevas reales y medición de todas las tarjetas de esas páginas; no se modificó aplicación. [Evidencia del control](evidencia/a6-a10/comprobacion-a9-alineacion.json). Condición cumplida; no se vuelve a pedir aprobación. Resta entrega y verificación independiente.

**A8 aprobado visualmente, 08/10:** Carlos abrió el visor actual y respondió «A-8 APROBADO». Resultado final aprobado; se conserva el distintivo coloreado de Profesores. Sin nueva elección ni pedido de aprobación de A8. Verificación independiente y publicación del paquete pendientes.

**Suite posterior A8/A9:** 491 aprobadas/2 omitidas/7 fallas, 3944 aserciones, 293,60 s, base Codex. Seis HTTP 500 por `$bloqueo` ausente en Inicio operativo, cambiado simultáneamente fuera de esta tarea; una por contador documental 491/500. [Salida completa](evidencia/a6-a10/suite-a8-a9-2026-10-08.txt). No está verde; cambios ajenos conservados.

**A8/A9, resultado actual 08/10:** 22 capturas reales nuevas con puntos activos/inactivos, niveles con/sin grupos y controles completos. [Grupos](evidencia/a6-a10/visor-a8-grupos.html) · [Niveles](evidencia/a6-a10/visor-a8-niveles.html) · [Profesores](evidencia/a6-a10/visor-a8-profesores.html). Corregida la alineación que las utilidades del 07/10 no lograban: reglas exclusivas de estos tres pies en celular. Botones en una fila, 96×32; borde derecho común con Nuevo en x=320 e interruptor debajo a derecha. Cuatro escritorios idénticos al antes por SHA256; distintivo coloreado de Profesores intacto. A8 A ya estaba elegida; se muestra el resultado completo pantalla por pantalla. Sin autocierre ni publicación; A10 conserva lo pendiente.

**A7 aprobado visualmente, 08/10:** Carlos revisó el visor actual con Cobrar/Editar y Ver/Nuevo alineados y respondió «Muy buen trabajo, me gusta». No se vuelve a pedir esta elección; faltan los demás pasos de la entrega conjunta.

**Último ajuste A7, 08/10:** después de ver la alineación derecha, Carlos pidió Cobrar/Editar en la misma columna y Ver/Nuevo en la misma línea vertical. [Resultado actual](evidencia/a6-a10/visor-a7-columnas.html): 12 capturas reales; coincidencia exacta de ambos pares, ADMIN/OPERATIVO y nombre largo. Solo celular; tres imágenes de escritorio idénticas al antes por SHA256. Nuevo conserva su posición, se adapta el pie de cada tarjeta. Los visores anteriores se conservan como antecedentes.

07/10/2026, Codex CyE. Main actualizado a `b49d997`, posterior a `7c5fa81`.
Main avanzó a `c65d628` durante la preparación (cierres documentales A14/A27/A53); Alumnos/CSS sin cambios respecto de `b49d997`.
Solo `wings_testing_codex`, actores ficticios; sin base del club, servidor remoto ni despliegue.
Carlos pidió recibir las pantallas una por una. Ningún defecto se cierra por su autor.

## Retoma comprobada — 08/10/2026, Codex CyE

**Diferencia A7 resuelta por pedido posterior de Carlos:** «Recien vi la captura, queda feo, en celular alinealos a la derecha. No asi en escritorio». Una regla CSS exclusiva de Alumnos bajo 640 px deja ambas filas de controles a derecha en celular. [Visor nuevo](evidencia/a6-a10/visor-a7-derecha.html), 12 capturas reales; tres casos de escritorio con geometría de tarjetas y botones idéntica al antes. Build 26,41 s correcto. El relato de la contradicción siguiente conserva el estado anterior a esta corrección; ya no bloquea el trabajo. A8/A9/A10 todavía requieren su revisión.

Antecedente: primera retoma 08/10, antes de las correcciones posteriores. Trabajo local recuperado; elecciones A6, A7 B y A8 A conservadas. [Primeras capturas A7](evidencia/a6-a10/visor-a7-retoma.html): 12 imágenes reales, ADMIN/OPERATIVO, escritorio/marco 375×667 y nombre largo. Nombre, datos y controles completos, botones 96×32; login contenido. Se detectó contradicción con el pase: A7 alineaba a izquierda en celular; la clase no lograba el resultado pedido. Se frenó según §6b y Carlos pidió corregir celular. Corrección y aprobación final posteriores registradas arriba.

En esa primera retoma: build correcto (1m 3s), escenario 4 pruebas/15 aserciones, 52,24 s, solo wings_testing_codex. [Mediciones](evidencia/a6-a10/comprobacion-a7-final.json) y [generador](evidencia/a6-a10/capturar-a7-final.mjs). El filtro DNI devolvió dos registros ficticios de una persona en deportes distintos. Sin nueva comprobación de PROFESOR/acciones ni suite completa en ese corte. A8/A9/A10 todavía requerían capturas; A8/A9 ya cuentan con ellas ahora. El control CUA falló; CDP/Chrome sin ventana permitió continuar.

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
Antecedente 07/10: build A7 correcto, 14,91 s; sintaxis y compilación de vistas correctas. En ese corte faltaban capturas finales porque Chrome devolvió «User unavailable». Las imágenes de B siguientes corresponden a la elección inicial; el resultado definitivo del 08/10 está en el visor de columnas alineadas, aprobado por Carlos. La publicación sigue pendiente de la entrega A6–A10.

### Alternativas presentadas — antecedente, ya decidido

- **A, descartada por Carlos:** dos tarjetas por fila a partir de 1100 px; dos datos por fila en cada tarjeta. Permite comparar dos alumnos lado a lado sin extender una tarjeta a todo el ancho.
- **B, aprobada:** una tarjeta por fila; cuatro datos por fila en escritorio. Conserva el recorrido vertical actual y usa el ancho para los ocho datos.
- En 375 ambas coinciden: una tarjeta y una columna de datos, textos completos y acciones que se acomodan en dos filas. Se conserva el tamaño de botones y el interruptor existentes.
- Datos: deporte, grupo, plan activo (clases/semana), DNI, celular, edad, tutor y teléfono del tutor. Sin plan se muestra «Sin plan»; celular vacío se muestra «–». No se agregan campos ni reglas de negocio.

**Estado al iniciar el relevamiento 07/10, antes de B:** A7 había cambiado en parte. La tarjeta ya era blanca; Ver cabía en su fila en las capturas ADMIN/OPERATIVO de escritorio y 375. Seguía a ancho completo en escritorio y plan/celular no aparecían. El punto identifica cobranza; el riel identifica deporte. B agregó los datos como consta arriba.

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

Decisión literal: **«A8 Opcion A»**. Aplicada localmente: Grupos/Profesores muestran punto verde Activo o gris Inactivo; Niveles verde Con grupos o gris Sin grupos. Alumnos conserva su punto de cobranza. Niveles celular recibió «Niveles cel OK»; el resultado actual del punto fue capturado el 08/10.

Se conserva íntegro el distintivo coloreado Activo/Inactivo de Profesores: Carlos pidió «escritorio, no le saques el boton de color». No se cambió ese bloque ni su formato. El interruptor también permanece.

Carlos rechazó las capturas que no muestran acciones: «A7 La captura de telefono no sirve. No muestra los botones de la tarjeta», «A8 Celular no se ven los botones» y «Profesores, la imagen no sirve». Las imágenes anteriores quedan como antecedente, sin certificar la entrega definitiva. Nuevas capturas deben incluir nombre/datos y pie con controles; en tarjetas largas, se necesita una secuencia de imágenes, sin reducir el tamaño del teléfono ni de sus textos.

Carlos recordó «los botones en el celular se alinean todos a la derecha». El intento del 07/10 con utilidades no alcanzó: las capturas reales del 08/10 mostraron acciones a izquierda. Corregido con `.grupos-actions`, `.niveles-actions` y `.profesores-actions` bajo 640 px. Dos botones comparten fila; interruptor debajo a derecha. Escritorio conserva sus posiciones, comprobado con cuatro pares de imágenes idénticas. El control a la izquierda de Grupos era el interruptor Activo/Inactivo. Ningún tamaño, componente, permiso, ruta o manejador cambió.

**Fuente y alcance:** A8 modifica puntos en `grupos/index`, `niveles/index` y `profesores/index`. A9 añade una clase por pie y reglas en app.css exclusivas de esos tres pies, solo celular. Las reglas previas exclusivas de A7 permanecen. Nivel usa `grupos_count` que carga su controlador; no se le inventa estado Activo. Un nivel con un grupo inactivo sigue diciendo Con grupos.

**Verificación realizada:** parches iniciales [A](evidencia/a6-a10/opciones/a8-a.diff) y [B](evidencia/a6-a10/opciones/a8-b.diff), y [ocho respuestas previas](evidencia/a6-a10/comprobacion-a8-http.json). A fue reaplicada tras la elección. [Diez respuestas posteriores](evidencia/a6-a10/comprobacion-elecciones-http.json): listados y roles, período anual/mensual e íconos del formulario. Sintaxis de seis vistas correcta, compilación de vistas correcta y build 10,22 s. Esto comprueba fuente/renderizado, no el aspecto final.

**Control actual 08/10:** 22 imágenes reales con filas activas/inactivas, ADMIN y OPERATIVO en Grupos; ADMIN en Niveles/Profesores. Marco 375×667, sin desborde horizontal, login contenido. Todos los controles medidos: botones 96×32, filas alineadas y último borde en x=320; interruptores terminan también en x=320. Cuatro imágenes de escritorio idénticas al antes por SHA256. [Mediciones](evidencia/a6-a10/comprobacion-a8-final.json) · [Huellas y control](evidencia/a6-a10/control-a8-final.json). Build 24,21 s, sintaxis/Blade correctos; escenario 4/15 verde. Suite posterior finalizada: resultado y causas arriba. No se operaron los interruptores ni Eliminar: se conserva su funcionamiento, no se certifica aquí.

**Pendiente:** entrega conjunta y verificación independiente. A7 y A8 tienen aprobación visual del resultado actual, 08/10. No se cierra A8 por su autor.

## A9 — Qué se propone integrar

Se trata del interruptor **Activo/Inactivo**, que activa o desactiva el registro. Grupos y Profesores ya usan el mismo componente `x-ds.toggle` que Alumnos; Niveles no tiene ese interruptor en la vista actual. No se propone agregarle uno.

El ajuste solicitado es de ubicación: en celular, interruptor y acciones terminan a la derecha; en escritorio se conserva la separación que dejó A28 en Grupos. Corregido y capturado el 08/10 con reglas exclusivas de los tres pies. No se pide otra decisión sobre el tamaño: el componente ya es común. Carlos aprobó si coinciden con Nuevo; condición medida y cumplida. Falta entrega y verificación independiente antes de cerrar A9.

## A10 — Período e íconos pedidos en la revisión

Cashflow consulta movimientos por Día/Semana/Mes/Año y tipo de caja. Semana abarca lunes–domingo completos, incluso cruzando mes/año. Por defecto conserva el año actual completo y los enlaces anteriores Año/Mes. Filas e ingresos/egresos comparten el intervalo; Tipo afecta las filas y ambos totales permanecen visibles. Tras aprobación de Carlos, Resultado del período = ingresos − egresos, sin saldo inicial. El apartado de saldo acumulado de Reportes sigue pendiente.

Aplicado localmente un texto explícito: **Período: Año 2026 completo**, o **Período: Octubre 2026** al seleccionar ese mes. Las tres respuestas anual/mensuales del informe HTTP comprueban que el texto sigue los filtros; no hay JavaScript nuevo.

Carlos pidió «Escritorio movimiento los textos no tienen iconos. Mantengamos la consistencia visual». Agregados los íconos existentes a Tipo de movimiento, Fecha, Medio de pago, Rubro, Subrubro, Monto y Observaciones en `cashflow/movimiento`; sin nuevos estilos inline. No se modificó el formulario operativo de Caja.

«Celular movimiento, no se ve completo»: resuelto el registro visual con dos capturas complementarias, parte superior y campos/pie, sin achicar textos ni teléfono. [Formulario completo](evidencia/a6-a10/visor-a10-movimiento.html). Escritorio incluye todos los campos, seis etiquetas con íconos y tipo de movimiento; Cancelar/Registrar están visibles en la toma móvil inferior. Nuevo fue pulsado en escritorio y abrió el formulario real; por X-Frame-Options DENY las capturas móviles usan la respuesta Laravel en srcdoc. No se operó Registrar.

**Aprobada y aplicada localmente 08/10:** Nuevo sale del bloque de importes y queda junto al contador; componente existente 96×32 px. Limpiar está en los filtros y conserva el período; destino «Nuevo movimiento». La ampliación aprobada agrega Día/Semana/Mes/Año y Resultado del período, detallada en [la entrega funcional](IMPLEMENTACION-A10-PERIODOS.md). [Variantes iniciales](evidencia/a6-a10/opciones/a10/) y [control anterior a aplicar](evidencia/a6-a10/control-a10.json) se conservan como evidencia fechada, no como estado actual del código.

## Relevamiento de A7–A10 sobre main, antes de proponer

Las capturas iniciales se conservan como antecedente del 07/10. A7/A8/A9 ya tienen aprobación visual posterior. A10 incorpora período/íconos pedidos; propuesta sobre Nuevo/nombre del destino capturada y aprobada el 08/10; aplicada junto a los períodos, ver entrega arriba.
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

Método del relevamiento inicial 07/10: Laravel servido localmente en 127.0.0.1:8794. Marco original de 375 CSS ×667;
[login primero](evidencia/a6-a10/capturas/login-main-375.jpg). Las imágenes de opciones
móviles conservan la captura completa de 1280×900 con el marco dentro; el visor muestra
ese marco sin inventar contenido. Escritorio exportado: 1265×889. No se achicó Chrome.
Ese informe contó solo el método propio: 1 prueba /2 aserciones, 9,56 s; la ejecución completa del archivo incluye tres heredadas y devuelve 4 pruebas/15 aserciones, comprobado el 08/10. Hay 20 alumnos en la clase y 21
en el listado total, con filas ficticias concretas en escenario.json. No integra la suite permanente.

A6 ya tiene elección de Carlos aplicada localmente; A7/A8/A9 tienen aprobación visual del resultado final 08/10. A9 confirmado expresamente; [visor de controles](evidencia/a6-a10/visor-a9.html). A10 aprobado/aplicado, con [entrega funcional y capturas finales](IMPLEMENTACION-A10-PERIODOS.md); lógica pendiente de verificación independiente. La autorización de publicación se reúne
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
