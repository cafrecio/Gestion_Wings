# Día 3 — lunes 12/10/2026, feriado

Sandra canceló las dos clases de las 16; el admin, la de las 17.
La plata sigue igual y Sin asistencia conserva sus 33 pendientes anteriores.
Falla: las canceladas de hoy no aparecen en Clases, tampoco para su profesora.

Expectativas anotadas antes de actuar. Sitio de prueba, menú y botones; reloj desde lunes 12, 10:00, avanza solo. No moverlo. Chrome con clics automatizados porque el control integrado falló, como los días anteriores. Sin cobros, apertura ni arreglos.

| Tarea | Esperado | Lo que pasó | ¿Coincide? |
|---|---|---|---|
| 1. Inicio y Clases, admin | Tres clases hoy: Patín Principiantes 16–17, Fútbol Principiantes 16–17, Patín Intermedias 17–18. Ingresos $164.000; deuda $2.010.000. | Inicio hasta 12/10: ingresos $164.000, deuda $2.010.000, profesores por pagar $0. Clases confirma lunes 12 y las tres Programada en sus horarios. Sin asistencia: 33 antes de cancelar. | Sí |
| 2. Sandra cancela dos | Puede cancelar las de las 16; pide confirmación o motivo; permanecen visibles como canceladas. | Sandra cancela Patín Principiantes y Fútbol Principiantes por Ver → Cancelar → motivo → Cancelar esta. La agenda baja de 3 a 1; la ficha de Patín muestra Cancelada/Guardar deshabilitado. Al volver, ambas dejan de verse; Cancelada + 12/10 devuelve 0. | Parcial |
| 3. Admin cancela tercera | Cancela Patín Intermedias 17–18; mismo resultado; comparar recorrido con Sandra. | Mismo recorrido y motivo; el admin también ofrece Cancelar serie, Sandra solo Cancelar esta. Queda Cancelada; Guardar deshabilitado. Al reabrir por Editar → Cancelar se ve el motivo y Activar. La agenda queda en 0; el filtro del día tampoco la encuentra. | Parcial |
| 4. Canceladas | Fuera de Sin asistencia y del número rojo; asistencia bloqueada. Identificar opción de deshacer y roles, sin usarla. | Menú 33 antes/después. Ambas páginas contienen solo fechas hasta 10/10, ninguna del 12. En las fichas canceladas vistas, aviso de bloqueo y Guardar deshabilitado. ADMIN ve Activar en la tercera; no se pulsó. No se pudo reabrir las dos de Sandra desde el listado. | Parcial |
| 5. Profesora | Una titular ve su clase cancelada; no puede tomar asistencia. | Lucía ve hoy 0. Profesor Lucía + Cancelada + 12/10: 0 resultados; no puede abrir la de hoy. Como contraste, abre su cancelada del 24/09: aviso de bloqueo, Guardar deshabilitado, sin Activar. | No |
| 6. Cierre, admin | Inicio/Cobranza/Reportes conservan $164.000 y $2.010.000; las canceladas no generan sueldo. | Inicio y Reportes: ingresos $164.000, deuda $2.010.000, por pagar $0. Cobranza: deuda $2.010.000 y 65/0/22/13. Reportes separa cuotas $159.000/inscripción $5.000. Sueldos: Gaitán, Salinas y Quintana $0; Mariela $18.000 por 6 asistencias. | Sí |

Las suplencias y la oferta de abrir caja en feriado están autorizadas: no son hallazgos.

Hallazgos:

- **Frena:** las canceladas de hoy desaparecen de la agenda y no se recuperan con Cancelada + 12/10: pasa con Sandra, admin y Lucía. No se puede consultar el feriado desde ese listado. [Sandra](dia03-03-canceladas-no-aparecen.png), [Admin](dia03-04-admin-canceladas-ocultas.png), [Lucía](dia03-05-profesora-no-ve-cancelada.png).
- **Molesta:** Sandra y admin reciben Demasiados intentos seguidos, sin indicar cuánto esperar. Sandra y admin lograron entrar al reintentar; volvió a bloquear al admin antes del cierre y después lo dejó ingresar. Causa no investigada. [Sandra](dia03-01-sandra-login-espera.png), [Admin](dia03-02-admin-login-espera.png).

No se intentó guardar asistencia ni pulsar Activar. La clase cancelada de hoy no es alcanzable para Lucía por menú/filtros; su bloqueo concreto no se pudo comprobar en esa ficha. Para Sandra tampoco se pudo comprobar Activar en las dos recién canceladas. No se consultó código/base ni se investigaron causas. Se completó el cierre financiero. Se dejaron las tres cancelaciones, sin cambiar pagos, caja ni reloj; T14 sigue abierta para Claude.

Tiempo: unos 14 minutos, incluida la entrega. Lo más caro fue el control del navegador y esperar/reintentar ingresos. No dispongo del costo monetario.

---

## Verificación de Claude — 10/10/2026

Comprobado contra la base del sitio de prueba: las tres clases del 12/10 están canceladas,
con el motivo «Feriado 12 de octubre: el club no abre»; siguen los 4 pagos y la caja del
sábado, sin nada nuevo.

- **Las canceladas de hoy no se ven — defecto real, confirmado en el código.**
  `ClaseWebController::index`: el bloque «hoy» deja afuera las canceladas (línea 37) y el
  listado con filtros deja afuera todo lo de hoy (línea 64). Una clase cancelada el mismo
  día no aparece en ninguno de los dos. Al día siguiente sí se la encuentra con el filtro
  Cancelada. Registrado como T21.
- **«Demasiados intentos seguidos» — ya no es el reloj.** Es el límite real de 5 ingresos
  por minuto por conexión, que el recorrido automático supera al entrar con varios
  usuarios seguidos. Se libera solo al minuto. Queda como mejora: el aviso no dice cuánto
  esperar.
