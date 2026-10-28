# T89 — cuatro decisiones de Carlos, 28/10/2026

Implementación de Codex CAB. Entrega local; requiere revisión independiente de lógica y aprobación del aspecto final.

Carlos eligió por escrito: «Movimientos A, pero mostrame como queda visualmente», «Cashflow A», «Inicio B», «Detalle opcion B». Es autorización para las vistas de estas cuatro pantallas. No se cambian tokens, CSS compartido ni JavaScript.

## Comportamiento entregado

- **Movimientos A:** columna Registró con el usuario del movimiento, independiente del titular de la caja; Recibo abre el pago de esa fila. En celular, tarjetas con los mismos datos y acciones Caja/Recibo en posiciones fijas. El filtro de operativo del dueño conserva su criterio anterior por titular de caja.
- **Caja ajena desde Movimientos:** consulta para OPERATIVO, con movimientos y totales filtrados a subrubros OPERATIVO. No expone rubros ADMIN ni ofrece edición, cancelación, verificación o resumen ajeno. Volver retorna a Movimientos. Los controles de escritura de cada ruta siguen vigentes.
- **Cashflow A:** Historial junto a Editar en los directos corregibles; reutiliza la pantalla y permisos ADMIN existentes. Es el icono de reloj con texto accesible Historial. Las referencias de caja, cuota y liquidación mantienen sus recorridos originales. El formulario de edición ya mostraba historial al pie antes de T89; lo que faltaba era este acceso desde el listado.
- **Inicio B:** cero filas de asistencia significa Sin lista; filas cargadas con cero presentes muestran Todos ausentes en gris; con presentes conserva el número en verde. Un conteo agregado adicional evita consultas por cada clase.
- **Detalle B:** Hora al lado de Fecha en escritorio; en celular, hora junto a fecha. Sale de created_at en America/Argentina/Buenos_Aires; la fecha contable sigue separada. No se cambian importes, cobros ni cierres.

## Verificación y límites

La búsqueda estructural pasó por codebase-memory. Su cobertura declaró metadatos incompletos; se verificaron los cuerpos y vistas reales, incluido el JavaScript de detalle, que identifica verificaciones por atributos y no por posición de columna.

Siete pruebas permanentes en `tests/Feature/PantallasPendientesT89Test.php`: autor distinto del titular, consulta filtrada sin escrituras, rutas forzadas de cierre/edición/eliminación/cancelación, profesor prohibido, fecha/hora, historial ADMIN, tres estados de lista y recibo PDF correcto de otro turno.

La primera corrida previa al cambio dio cuatro fallas esperadas de producto, una falla de preparación del alumno y un caso verde. La preparación se corrigió; no se presenta ese primer resultado como siete regresiones válidas. El primer verde del área reunió 46 pruebas y 416 aserciones; luego se ampliaron las comprobaciones de rutas forzadas en el mismo método.

Todos los comandos usan APP_ENV=testing, DB_DATABASE=wings_testing_codex, DB_HOST=127.0.0.1 y DB_URL vacío, con bootstrap de protección. El servidor local se detuvo antes de las suites generales. Durante el primer rojo seguía levantado, sin navegación ni peticiones de aplicación concurrentes; se detuvo al comprobar el proceso. No hubo pruebas en la base del club ni despliegue.

Resultados finales: completa **1182 aprobadas, 6 omitidas, 9861 aserciones, 1087,71 s**, salida 0. Rápida: 1060 aprobadas, 6 omitidas y una falla por contador documental; actualizado de 1181 a 1188, guardia aislada 1/7 verde y completa posterior verde. No se presenta la primera rápida como íntegramente verde.

Después de la completa se amplió únicamente la columna Recibo de detalle de caja cerrada, de 72 a 96 px, porque recortaba el botón. Las otras nueve fuentes congeladas son idénticas. Área final tras ese ajuste: **53 aprobadas, 454 aserciones, 19,83 s**, salida 0; incluye las siete T89, regresiones T27/T36/T37/T78 y guardias de contador/tablero. No se volvió a ejecutar toda la suite por el ancho de una columna. Sintaxis PHP, compilación Blade y build correctos; sin CSS compartido ni JavaScript nuevos.

[Galería final APROBAR-T89](APROBAR-T89.html): diez imágenes, más calibración del ingreso. Chrome real a 1366; a 375, marco con el DOM real exportado de Wings y tokens retirados. La aplicación conserva sus encabezados contra marcos; no se modificaron. El marco tiene 375 px de contenido; Chrome reserva su canal normal de scrollbar. Botones móviles Caja/Recibo medidos en 96 × 32, con borde común. Las diez imágenes cargaron en la vista HTTP acotada de la galería; también se abrió el archivo exacto con Chrome. Se descartó una captura de Inicio tomada antes de estabilizarse la pintura y se renovó sin editar píxeles.

Navegación comprobada: Ana abre desde la fila de Mario la caja de Rocío, con dos movimientos, recibos y Volver a Movimientos; sin edición/cancelación/resumen ajeno. El dueño pulsa Historial y ve el antes -4000, después -4500, actor y motivo. Las pruebas verifican además rubros ADMIN ocultos, rutas de escritura forzadas rechazadas y PDF del pago correcto.

T89 entregada a Claude en a_verificar; aspecto final pendiente de Carlos. No se cierra por el mismo implementador. Galerías anteriores conservan las imágenes previas y enlazan el resultado. Servidor propio detenido, sesiones sintéticas cerradas. El HTML T34 ajeno conserva exactamente sus bytes sucios iniciales (huella en integridad.json); no se restauró a HEAD ni se incluyó en el commit. Sin sitio de prueba, base del club, despliegue ni push manual.

Las salidas y exportaciones de DOM conservan los resultados; se retiraron espacios al final de línea y líneas vacías finales para el control de Git. En el rojo se omitieron los tokens efímeros de prueba.
