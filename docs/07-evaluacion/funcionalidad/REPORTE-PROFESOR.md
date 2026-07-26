# Evaluación de usuario — Perfil PROFESOR (celular, entre clases)

Soy profe de patín/fútbol. Uso esto parado en la cancha, desde el celular, entre una clase y otra. Lo único que necesito: ver mis clases y tomar lista rápido. Nada más.

## Vista: Clases — listado (`/clases`)
Entro y veo mis clases. El sidebar de la izquierda ocupa 240px fijos y no se achica en el celular, así que la lista me queda espichada en la mitad derecha de la pantalla y tengo que hacer scroll horizontal. En el playón, con sol, encima teniendo que apuntar con el dedo a una franja finita, es un bajón. El badge rojo con el número de clases pendientes está bien, me dice cuántas me faltan.

## Vista: Clase — detalle / tomar lista (`/clases/{id}`)
Acá es donde vivo. Cada alumno es una tarjeta y toco para marcarlo presente, la tarjeta se pinta de verde: eso está bueno, es un toque por alumno y se entiende quién está. Me muestra "plan semana 1 de 2" y avisa si el alumno excede su plan, útil. Pero de nuevo: la pantalla está pensada para monitor. Las tarjetas y el contador de presentes se ven, pero el sidebar fijo me come espacio y los botones de arriba (Modificar profesores, Cancelar) son cosas que a mí ni me importan y me estorban. Yo solo quiero: lista de nombres, tocar, guardar.

## Vista: (lo que NO tengo)
No tengo un "mis clases de hoy" directo: entro a Clases y están todas, tengo que buscar la de hoy. Sería ideal que lo primero que vea sea la clase que estoy por dar.

---

### UP1.0 — La pantalla no funciona en el celular (sin diseño móvil) — ✅ RESUELTO (2026-07-21)
**Vista:** todas (Clases, detalle)
**Severidad:** Alta
**Resolución (SUP1.1):** debajo de 768px el menú ahora se esconde detrás de un botón hamburguesa; se desliza como panel encima de la pantalla y se cierra solo al tocar afuera o al elegir una opción. El contenido ya usa el ancho completo. Arriba de 768px (compu) no cambió nada.
**Qué me pasaba:** El menú de la izquierda ocupaba siempre lo mismo y no se escondía en el teléfono, así que todo lo demás quedaba apretado y tenía que mover la pantalla para los costados. Yo nunca uso una computadora, uso el celular en la cancha, y así se me hacía difícil tomar lista rápido.
**Soluciones:**
- SUP1.1 ⭐ Que en el celular el menú se esconda detrás de un botón (hamburguesa) y la lista de alumnos ocupe toda la pantalla.
- SUP1.2 Que por lo menos las tarjetas de alumnos y el botón de guardar se agranden y no haya que mover la pantalla para los costados.

### UP2.0 — No veo directo la clase de hoy — ✅ RESUELTO (2026-07-21)
**Vista:** Clases (listado)
**Severidad:** Media
**Resolución (SUP2.1):** las clases de hoy ya aparecían arriba de todo, pero el problema real era que se mezclaban las de TODOS los profesores. Ahora "Tus clases de hoy" muestra solo las mías, arriba; las de otros profes quedan en "Otras clases de hoy (N)" colapsado debajo, sin ocultarlas. Admin y operativo ven exactamente lo mismo que antes, sin cambios.
**Qué me pasaba:** Cuando entraba me aparecían todas las clases de hoy de todos los profesores y tenía que buscar cuál era la mía. Con los pibes esperando, quería que lo primero fuera mi clase.
**Soluciones:**
- SUP2.1 ⭐ Que arriba de todo aparezca "Tu clase de ahora / de hoy" con el botón de tomar lista, y las demás abajo.
- SUP2.2 Ordenar la lista con las de hoy primero y resaltadas.

### UP3.0 — Me muestran botones que no son para mí
**Vista:** Clase — detalle
**Severidad:** Baja
**Qué me pasa:** Arriba de la lista hay cosas como "Modificar profesores" o cancelar la clase, que yo no uso y me distraen de lo único que vine a hacer, que es marcar presentes.
**Soluciones:**
- SUP3.1 ⭐ Esconder esas acciones para el profe (o mandarlas abajo) y dejar arriba solo la lista y el guardar.

### UP4.0 — Confirmar que la lista se guardó
**Vista:** Clase — detalle
**Severidad:** Baja
**Qué me pasa:** Marco los presentes pero quiero estar seguro, con el apuro, de que quedó guardado y no perdí la lista.
**Soluciones:**
- SUP4.1 ⭐ Un cartel bien claro de "Lista guardada" después de guardar, grande, que se vea de un vistazo.

## Tabla resumen

| ID | Severidad | Vista | Problema |
|----|-----------|-------|----------|
| UP1 | Alta | Todas | ✅ Resuelto — sidebar colapsable con hamburguesa (SUP1.1) |
| UP2 | Media | Clases | ✅ Resuelto — "Tus clases de hoy" separadas de las de otros (SUP2.1) |
| UP3 | Baja | Clase detalle | Botones que no son para mí me estorban |
| UP4 | Baja | Clase detalle | Falta confirmación clara de que la lista se guardó |
