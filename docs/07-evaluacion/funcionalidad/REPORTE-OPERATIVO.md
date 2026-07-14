# Evaluación de usuario — Perfil OPERATIVO (recepción, hora pico)

Soy quien atiende el mostrador. En hora pico tengo cola de padres que pagan la cuota, algunos preguntan si ya pagaron, otros quieren cambiar de frecuencia. Necesito cobrar rápido y sin equivocarme, y manejar mi caja del día.

## Vista: Inicio / dashboard operativo (`/operativo`)
Está bueno: apenas entro veo el estado de mi caja de hoy (abierta/cerrada), lo cobrado, y si el admin me rechazó alguna caja me salta un aviso rojo. También veo cuántos alumnos deben y cuántos son posibles inactivos. Me orienta bien para arrancar el día. Lo cobrado hoy y las clases del día me sirven de un vistazo.

## Vista: Caja — listado (`/caja`)
Veo mis cajas de los últimos 30 días con ingresos, egresos y neto de cada una, y un botón "Historial". Bien. El estado (abierta/cerrada/validada/rechazada) se ve por color. Lo que me falta a veces es entrar y que ya me deje cobrar sin dar vueltas.

## Vista: Cobrar — elegir alumno (`/caja/cobrar`)
Busco al alumno por nombre o DNI. Funciona, pero es un paso extra: primero busco, después entro a cobrar. Con cola, cada segundo cuenta.

## Vista: Cobrar — formulario (`/caja/cobrar/{alumno}`)
Este es el corazón de mi trabajo y quedó claro: veo la deuda total, las cuotas pendientes para tildar, y si el grupo tiene varias frecuencias puedo cambiar el plan ahí mismo (y me aclara que rige de este mes en adelante, no para atrás). El medio de pago es un solo campo (antes había dos que confundían). El total a cobrar se actualiza solo. Puedo cobrar sin salir a otra pantalla.

## Vista: Historial de movimientos (`/caja/historial`)
Acá contesto "¿pagué mayo?": busco al alumno y veo sus pagos de los últimos 3 meses, con fecha y monto. Me saca de apuros cuando un padre pregunta. Está bien filtrado.

## Vista: Ficha de alumno (`/alumnos/{id}`)
Veo los datos del alumno, su estado de cobranza (al día / moroso / deudor), el historial de pagos y las asistencias del mes. Para responder dudas en el mostrador me alcanza.

## Vista: Registrar gasto (`/caja/movimiento`)
Cuando pago algo chico (librería, limpieza) elijo el subrubro y el monto. Simple.

---

### UO1.0 — Cobrar lleva dos pantallas (buscar y después cobrar)
**Vista:** Cobrar (elegir alumno → formulario)
**Severidad:** Media
**Qué me pasa:** Con gente esperando, primero busco al alumno en una pantalla y recién en la siguiente cobro. Es un clic y una carga de más por cada persona de la cola.
**Soluciones:**
- SUO1.1 ⭐ Que al elegir al alumno del buscador me lleve directo al formulario de cobro ya cargado, sin pantalla intermedia.
- SUO1.2 Un buscador de cobro en el propio dashboard, para arrancar el cobro desde ahí.

### UO2.0 — "¿Pagó mayo?" se puede responder en dos lados y no sé cuál es el bueno
**Vista:** Ficha de alumno / Historial
**Severidad:** Baja
**Qué me pasa:** Puedo ver los pagos del alumno tanto en su ficha como en el historial de caja. Con el apuro no sé cuál abrir; a veces uno tiene lo que busco y el otro no.
**Soluciones:**
- SUO2.1 ⭐ Que desde el buscador o la ficha del alumno haya un acceso directo y obvio a "sus pagos", uno solo, que sea el de referencia.

### UO3.0 — Al entrar a una caja no siempre puedo cobrar de una
**Vista:** Caja (listado / resumen)
**Severidad:** Baja
**Qué me pasa:** Entro a mi caja del día y para cobrar tengo que buscar el botón; me gustaría que el cobrar esté siempre a mano, arriba, sin buscarlo.
**Soluciones:**
- SUO3.1 ⭐ Botón "Cobrar" siempre visible y destacado en la caja abierta y en el dashboard, como acción principal.

### UO4.0 — Confirmación clara después de cobrar
**Vista:** Cobrar (formulario)
**Severidad:** Baja
**Qué me pasa:** Cuando cobro quiero estar seguro, con la cola encima, de que quedó registrado y por cuánto, sin tener que ir a chequear.
**Soluciones:**
- SUO4.1 ⭐ Un cartel claro de "Cobrado $X a Fulano" después de confirmar, y ofrecerme el recibo ahí mismo.

## Tabla resumen

| ID | Severidad | Vista | Problema |
|----|-----------|-------|----------|
| UO1 | Media | Cobrar | Cobrar lleva dos pantallas (buscar y cobrar) |
| UO2 | Baja | Ficha / Historial | "¿Pagó mayo?" se responde en dos lados |
| UO3 | Baja | Caja | El botón Cobrar no está siempre a mano |
| UO4 | Baja | Cobrar | Falta confirmación clara post-cobro + recibo |
