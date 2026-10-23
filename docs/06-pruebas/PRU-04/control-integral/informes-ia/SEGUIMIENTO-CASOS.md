# Seguimiento por casos — corte 23/10/2026

Estado: entrega para revisión independiente. No aprueba producción.

## Modelo y ámbito de evidencia

Evidencia consolidada del Día 4; jornadas 1–3 por conciliar. Sin probar significa sin ejecución vinculada aquí, no una afirmación sobre todo el historial.
Caso completo solo si todos sus criterios tienen evidencia de aceptación; operación coincidente no equivale a caso OK. Apoyo aislado excluido. Revisión independiente pendiente.

Fuente de aceptación: https://test.gestionar-te.com.ar, base wingstest, versión 56379295a33782858ce9c986e200f13382d61cc4. Ejecución y contraste: 22/10/2026; calendario del club 13/10/2026.
La generación de este informe no vuelve a ejecutar cobros ni revalida el servidor actual. La corrida local del 23/10 es auxiliar y sus resultados siguen disponibles en el anexo original.

## Recuento

```json
{
  "casos": 331,
  "modulos": 20,
  "probados": 15,
  "sin_probar": 316,
  "intentos": 18,
  "operaciones": 16,
  "operaciones_sin_caso": 1,
  "parciales": 15,
  "planes": 3,
  "metodos_aislados": 41,
  "OK": 0,
  "Fallo": 0,
  "Reparado": 0,
  "salida_seleccionados": 69,
  "salida_probados": 15
}
```

Un intento es la asociación única (caso, operación). No se cuenta una vez por letra, SQL o captura. Dos casos pueden compartir una operación; dos consultas de un mismo alta pueden ser dos intentos parciales del mismo caso.
Todos los vínculos actuales son parciales. Los contadores OK/Fallo/Reparado son resultados del caso completo, no los 14 resultados Coincide del relato. No se transforma una diferencia del relato en un fallo formal de todos los criterios.

## Historial por módulo y caso

### Acceso y seguridad

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [ACC-001 · Ingresar con usuario y clave correctos](../casos/ACC-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-002 · Clave equivocada o mail que no existe](../casos/ACC-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-003 · Más de cinco intentos de ingreso en un minuto](../casos/ACC-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-004 · Intenta entrar una cuenta desactivada](../casos/ACC-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-005 · Desactivan a un usuario mientras tiene la sesión abierta](../casos/ACC-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-006 · Se vence la sesión con un formulario a medio llenar](../casos/ACC-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-007 · Abrir una dirección interna sin haber ingresado](../casos/ACC-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-008 · Salir y volver atrás con el navegador](../casos/ACC-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-009 · La operativa fuerza direcciones que son solo del dueño](../casos/ACC-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-010 · La profesora fuerza direcciones que no le corresponden](../casos/ACC-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-011 · Forzar una acción del dueño sin pasar por la pantalla](../casos/ACC-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-012 · La profesora intenta cancelar, reasignar o editar clases por pedido directo](../casos/ACC-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-013 · Dirección que no existe o registro que no existe](../casos/ACC-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-014 · Usar una dirección de acción como si fuera una pantalla](../casos/ACC-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-015 · Escribir código o símbolos raros en los campos de texto](../casos/ACC-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-016 · Cambiar a mano el número de la dirección para ver lo de otro](../casos/ACC-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-017 · El mismo usuario en dos dispositivos y la opción Recordarme](../casos/ACC-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-018 · Abrir el ingreso estando ya adentro (aplicación instalada en el teléfono)](../casos/ACC-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-019 · Aparece un error interno del sistema](../casos/ACC-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-020 · Cabeceras de seguridad y política de contenido](../casos/ACC-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ACC-021 · Consultas internas y direcciones viejas](../casos/ACC-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Alumnos

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [ALU-001 · Alta de un adulto con ingreso de hoy](../casos/ALU-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-002 · Alta de un menor de edad](../casos/ALU-002.html) | Plan de acción | 3 | 0/0/0/3 | Día 5: diagnóstico propuesto; retest sin fecha hasta implementar. |
| [ALU-003 · Alta con datos mal cargados](../casos/ALU-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-004 · La misma persona dos veces en el mismo deporte](../casos/ALU-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-005 · La misma persona se anota en un segundo deporte](../casos/ALU-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-006 · Alta con fecha de ingreso de un mes ya cerrado](../casos/ALU-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-007 · Alta con fecha de ingreso futura](../casos/ALU-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-008 · Guardar dos veces la misma alta](../casos/ALU-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-009 · Cambia el precio mientras el alta está abierta](../casos/ALU-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-010 · Alta sin importe de inscripción configurado](../casos/ALU-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-011 · Salir del formulario con datos sin guardar](../casos/ALU-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-012 · Corregir nombre, teléfono o tutor de un alumno con historia](../casos/ALU-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-013 · Cambiar a un alumno de grupo dentro del mismo deporte](../casos/ALU-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-014 · Cambiar el plan de un alumno desde la ficha](../casos/ALU-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-015 · Corregir la fecha de ingreso](../casos/ALU-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-016 · Corregir el DNI de un alumno](../casos/ALU-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-017 · Dar de baja a un alumno que debe](../casos/ALU-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-018 · Reactivar a un alumno dado de baja](../casos/ALU-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-019 · Forzar un plan o un grupo que no corresponde](../casos/ALU-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-020 · Buscar y filtrar en el listado de alumnos](../casos/ALU-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-021 · Qué ve y qué puede hacer cada rol en la ficha](../casos/ALU-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-022 · Dos personas editan al mismo alumno a la vez](../casos/ALU-022.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-023 · Alumno que quedó sin plan](../casos/ALU-023.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-024 · Alumno cargado por error](../casos/ALU-024.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-025 · Dar de baja con un toque desde el listado](../casos/ALU-025.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ALU-026 · Lo que muestra el listado de alumnos](../casos/ALU-026.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Asistencia

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [ASI-001 · Tomar la asistencia de una clase de hoy](../casos/ASI-001.html) | Parcial | 1 | 0/0/0/1 | Día 5: Próxima clase del miércoles: tomar lista por pantalla y contrastar marcas, grupo y efectos. Propuesta para la historia; todavía no ejecutada. |
| [ASI-002 · Suplencia sin cambiar la profesora asignada](../casos/ASI-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-003 · No vino nadie](../casos/ASI-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-004 · Corregir una asistencia ya guardada](../casos/ASI-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-005 · Asistencia en una clase cancelada](../casos/ASI-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-006 · Asistencia de una clase ya liquidada](../casos/ASI-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-007 · Alumno que viene más veces de las que paga](../casos/ASI-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-008 · El mismo alumno presente en dos clases a la misma hora](../casos/ASI-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-009 · Quién aparece en la lista después de un cambio](../casos/ASI-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-010 · Forzar una asistencia adulterada](../casos/ASI-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-011 · Tomar asistencia de una clase que todavía no ocurrió](../casos/ASI-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-012 · Dos personas guardan la misma lista](../casos/ASI-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-013 · El deudor que viene a clase](../casos/ASI-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-014 · Tomar asistencia desde el teléfono](../casos/ASI-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-015 · Guardar la lista sin conexión](../casos/ASI-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [ASI-016 · Qué ve la profesora de cada alumno en la lista](../casos/ASI-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Caja

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CAJ-001 · Elegir cuál medio de pago es el efectivo del cajón](../casos/CAJ-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-002 · Querer cambiar el medio del cajón después de elegido](../casos/CAJ-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-003 · Abrir caja sin mostrador configurado](../casos/CAJ-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-004 · Primera apertura del club](../casos/CAJ-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-005 · Apertura que hereda el cambio del cierre anterior](../casos/CAJ-005.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-006 · Lo recibido no coincide con lo que dejó el turno anterior](../casos/CAJ-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-007 · Un cero de más en la apertura](../casos/CAJ-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-008 · Apertura con el importe mal escrito o sin confirmar](../casos/CAJ-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-009 · Abrir con otro turno todavía abierto](../casos/CAJ-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-010 · Dos aperturas al mismo tiempo](../casos/CAJ-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-011 · Abrir con una pantalla vieja](../casos/CAJ-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-012 · El dueño abre la caja a nombre de una operativa](../casos/CAJ-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-013 · Rectificar lo declarado en la apertura](../casos/CAJ-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-014 · Rectificar cuando no corresponde](../casos/CAJ-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-015 · Turno abierto de un día anterior](../casos/CAJ-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-016 · El dueño cierra el turno que una operativa dejó abierto](../casos/CAJ-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-017 · Qué muestra la caja del día](../casos/CAJ-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-018 · Cierre sin diferencia](../casos/CAJ-018.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-019 · Cierre con faltante](../casos/CAJ-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-020 · Cierre con sobrante](../casos/CAJ-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-021 · Cierre con datos imposibles](../casos/CAJ-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-022 · Dejar más cambio del que se recibió](../casos/CAJ-022.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-023 · Entra un cobro mientras se cierra](../casos/CAJ-023.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-024 · Cerrar dos veces](../casos/CAJ-024.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-025 · Validar una caja cerrada](../casos/CAJ-025.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-026 · Validar o rechazar una caja que sigue abierta](../casos/CAJ-026.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-027 · Validar dos veces](../casos/CAJ-027.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-028 · Una operativa intenta validar o rechazar](../casos/CAJ-028.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-029 · Rechazar una caja](../casos/CAJ-029.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-030 · Corregir una caja rechazada y volver a cerrarla](../casos/CAJ-030.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-031 · Cambiar lo contado al volver a cerrar una rechazada](../casos/CAJ-031.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-032 · Varias cajas pendientes validadas fuera de orden](../casos/CAJ-032.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-033 · Detalle de una caja cerrada con diferencia](../casos/CAJ-033.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-034 · Abrir la caja de otra operativa](../casos/CAJ-034.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-035 · Listado de cajas según quién mira](../casos/CAJ-035.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-036 · Lo que no es efectivo no entra al arqueo](../casos/CAJ-036.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-037 · Abrir y cerrar sin ningún movimiento](../casos/CAJ-037.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-038 · Dos turnos en el mismo día](../casos/CAJ-038.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-039 · Abrir caja un domingo o un feriado](../casos/CAJ-039.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-040 · Los tildes de verificación al validar una caja](../casos/CAJ-040.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-041 · El dueño opera sobre la caja abierta de una operativa](../casos/CAJ-041.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAJ-042 · Cajas anteriores al sistema de conteo](../casos/CAJ-042.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Catálogos

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CAT-001 · Deportes: alta y edición](../casos/CAT-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-002 · Cambiar cómo liquida un deporte que ya tiene liquidaciones](../casos/CAT-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-003 · Desactivar un deporte o borrar un nivel en uso](../casos/CAT-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-004 · Niveles: alta, edición y nombre repetido](../casos/CAT-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-005 · Medios de pago: alta y saldo inicial](../casos/CAT-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-006 · Desactivar un medio de pago](../casos/CAT-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-007 · Crear un subrubro y dejarlo listo para usar](../casos/CAT-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-008 · Cambiar un subrubro que ya tiene movimientos](../casos/CAT-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-009 · Rubros y subrubros reservados](../casos/CAT-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CAT-010 · Catálogos y configuración para quien no es dueño](../casos/CAT-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Cobranza

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CBZ-001 · Cobranza: listado, filtros y totales](../casos/CBZ-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-002 · Vence el plazo: de En plazo a Moroso](../casos/CBZ-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-003 · Cambia el mes: de Moroso a Deudor](../casos/CBZ-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-004 · Paga el mes nuevo y sigue debiendo el viejo](../casos/CBZ-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-005 · El que debe solo la inscripción también es moroso o deudor](../casos/CBZ-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-006 · La misma persona debe en dos deportes](../casos/CBZ-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-007 · Condonar el saldo de una cuota](../casos/CBZ-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-008 · Una operativa intenta condonar](../casos/CBZ-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-009 · Condonaciones que no corresponden](../casos/CBZ-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-010 · Perdonar la inscripción](../casos/CBZ-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-011 · El día 1 se generan las cuotas del mes](../casos/CBZ-011.html) | Sin probar | 0 | 0/0/0/0 | Día 23: 1/11: comprobar generación, unicidad y conservación del adelanto. Preparar los demás alumnos y variantes antes de la fecha. La segunda corrida requiere control técnico, no simularla desde la pantalla. |
| [CBZ-012 · La generación mensual corre dos veces](../casos/CBZ-012.html) | Sin probar | 0 | 0/0/0/0 | Día 23: 1/11: comprobar generación, unicidad y conservación del adelanto. Preparar los demás alumnos y variantes antes de la fecha. La segunda corrida requiere control técnico, no simularla desde la pantalla. |
| [CBZ-013 · Sin asistencias el mes pasado: va a Revisión](../casos/CBZ-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-014 · Resolver una revisión](../casos/CBZ-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-015 · Resolver dos veces la misma revisión](../casos/CBZ-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-016 · El alumno en Revisión vuelve a clase o paga](../casos/CBZ-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-017 · Ingreso reciente sin asistencias todavía](../casos/CBZ-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-018 · Cuadre de fin de día: todos los números dicen lo mismo](../casos/CBZ-018.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-019 · Cobranza como lista para llamar](../casos/CBZ-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-020 · El proceso del día 1 no corrió](../casos/CBZ-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-021 · Revisión: filtros e historia](../casos/CBZ-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CBZ-022 · Vencidos y en plazo en el Inicio de la operativa](../casos/CBZ-022.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Configuración

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CFG-001 · Cambiar los días de gracia](../casos/CFG-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CFG-002 · Valores imposibles en Configuración](../casos/CFG-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CFG-003 · Cambiar el importe de la inscripción](../casos/CFG-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CFG-004 · Reglas del primer pago según el día de ingreso](../casos/CFG-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CFG-005 · Avisos por mail y por Telegram](../casos/CFG-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CFG-006 · La pantalla de Configuración](../casos/CFG-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Clases

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CLA-001 · Listado de clases según quién mira](../casos/CLA-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-002 · Crear una clase única](../casos/CLA-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-003 · Crear una serie con horarios distintos por día](../casos/CLA-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-004 · Clase que ocupa dos bloques de cancha](../casos/CLA-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-005 · Clase con datos imposibles](../casos/CLA-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-006 · Serie con un choque de horario](../casos/CLA-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-007 · Profesora de otro deporte o inactiva](../casos/CLA-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-008 · Lo que la operativa puede y no puede con las clases](../casos/CLA-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-009 · Cambiar la profesora de una clase de hoy](../casos/CLA-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-010 · Cambiar la profesora de una clase pasada](../casos/CLA-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-011 · Cancelar una clase de hoy](../casos/CLA-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-012 · Cancelar una clase que ya tiene la lista tomada](../casos/CLA-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-013 · Cancelar una clase que ya está en una liquidación](../casos/CLA-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-014 · Cancelar una serie](../casos/CLA-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-015 · Volver a activar una clase cancelada](../casos/CLA-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-016 · Editar el horario de una clase](../casos/CLA-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-017 · Validar a mano una clase sin asistencia](../casos/CLA-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-018 · Cargar la asistencia de una clase muy vieja](../casos/CLA-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-019 · Crear dos veces la misma clase o serie](../casos/CLA-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-020 · Clase con dos profesoras](../casos/CLA-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-021 · Clase o serie creada por error](../casos/CLA-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-022 · El estado de una clase a lo largo del día](../casos/CLA-022.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-023 · Clase sin profesora asignada](../casos/CLA-023.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-024 · Validar una clase por error](../casos/CLA-024.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CLA-025 · La operativa y la cancelación de series](../casos/CLA-025.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Cobrar

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [COB-001 · El buscador de Cobrar muestra a quién hay que cobrarle](../casos/COB-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-002 · Cobrar sin caja abierta](../casos/COB-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-003 · Cobro de la cuota del mes en efectivo](../casos/COB-003.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-004 · Cobro por Mercado Pago o transferencia](../casos/COB-004.html) | Parcial | 2 | 0/0/0/2 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-005 · Primer cobro de un alumno nuevo: inscripción y cuota](../casos/COB-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-006 · Cobro de dos meses adeudados juntos](../casos/COB-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-007 · Pagar el mes nuevo dejando impago el viejo](../casos/COB-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-008 · Pago parcial de una cuota](../casos/COB-008.html) | Plan de acción | 1 | 0/0/0/1 | Día 5: diagnóstico propuesto; retest sin fecha hasta implementar. |
| [COB-009 · El pago parcial tiene que pedir motivo](../casos/COB-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-010 · Cobrar de más](../casos/COB-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-011 · Importes mal escritos](../casos/COB-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-012 · Cobro adelantado del mes que viene](../casos/COB-012.html) | Plan de acción | 1 | 0/0/0/1 | Día 5: diagnóstico propuesto; retest sin fecha hasta implementar.; Día 23: 1/11: comprobar generación, unicidad y conservación del adelanto. Preparar los demás alumnos y variantes antes de la fecha. La segunda corrida requiere control técnico, no simularla desde la pantalla. |
| [COB-013 · Adelantado fuera de lo permitido](../casos/COB-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-014 · Cobrar dos veces lo mismo](../casos/COB-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-015 · Viene a pagar un alumno dado de baja](../casos/COB-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-016 · El dueño cobra una cuota sin caja](../casos/COB-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-017 · Cancelar un cobro del turno](../casos/COB-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-018 · Cancelar un cobro de una caja cerrada o validada](../casos/COB-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-019 · Cancelar un cobro adelantado](../casos/COB-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-020 · Cancelar un cobro que incluía la inscripción](../casos/COB-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-021 · El dueño anula un cobro propio hecho sin caja](../casos/COB-021.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-022 · Cobro con fecha de pago de un mes anterior](../casos/COB-022.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-023 · Cobrar una cuota que ya no se puede cobrar](../casos/COB-023.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-024 · Cambiar el plan en el momento de cobrar](../casos/COB-024.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-025 · Descuento del mes de ingreso](../casos/COB-025.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-026 · Forzar un cobro adulterado](../casos/COB-026.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-027 · La operativa y el dueño cobran la misma cuota a la vez](../casos/COB-027.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-028 · El mes en curso todavía no tiene cuota generada](../casos/COB-028.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-029 · Cobrar un mes pasado que nunca se generó](../casos/COB-029.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-030 · Muchos cobros seguidos en un turno](../casos/COB-030.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-031 · Cobrar solo la inscripción, o plata que no la alcanza](../casos/COB-031.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-032 · Doble clic en un pago parcial](../casos/COB-032.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-033 · Avisos y confirmaciones dentro del cobro](../casos/COB-033.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [COB-034 · Cómo se comporta la pantalla de cobro](../casos/COB-034.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Cashflow

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [CSH-001 · Cashflow: lo que muestra y cómo se filtra](../casos/CSH-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-002 · El dueño carga un gasto directo](../casos/CSH-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-003 · Importes con centavos](../casos/CSH-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-004 · Aporte y retiro del dueño](../casos/CSH-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-005 · Fechas del movimiento directo](../casos/CSH-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-006 · Movimiento directo mal cargado](../casos/CSH-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-007 · Corregir un movimiento directo](../casos/CSH-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-008 · Eliminar un movimiento directo](../casos/CSH-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-009 · Lo que no se corrige desde Cashflow](../casos/CSH-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-010 · Corregir dos veces o con la pantalla vieja](../casos/CSH-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-011 · Un dueño corrige el movimiento de otro dueño](../casos/CSH-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-012 · Entrar a Cashflow sin ser dueño](../casos/CSH-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-013 · Fin de mes: saldos contra la cuenta a mano](../casos/CSH-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-014 · Quién pagó, quién cobró y quién validó](../casos/CSH-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-015 · Pagar el sueldo de una operativa](../casos/CSH-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-016 · Gasto mayor que el saldo del medio](../casos/CSH-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-017 · Corregir llevando el movimiento a otra fecha](../casos/CSH-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-018 · Llevar el efectivo del cajón al banco](../casos/CSH-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [CSH-019 · Períodos de Cashflow](../casos/CSH-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Grupos y planes

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [GRU-001 · Crear un grupo con sus planes](../casos/GRU-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-002 · Grupo mal cargado](../casos/GRU-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-003 · Aumentar el precio de un plan](../casos/GRU-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-004 · Quitar un plan en uso o el único plan](../casos/GRU-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-005 · Editar un grupo con la pantalla vieja](../casos/GRU-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-006 · Desactivar un grupo con alumnos y clases](../casos/GRU-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [GRU-007 · Grupos para quien no es dueño](../casos/GRU-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Liquidaciones

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [LIQ-001 · Liquidación de una profesora que cobra por hora](../casos/LIQ-001.html) | Sin probar | 0 | 0/0/0/0 | Día 24: 2/11: liquidaciones, con asistencias y pagos conciliados. Ejecutar cada variante del catálogo; una liquidación aislada no cubre todo el caso. |
| [LIQ-002 · Liquidación de un profesor que cobra por comisión](../casos/LIQ-002.html) | Sin probar | 0 | 0/0/0/0 | Día 24: 2/11: liquidaciones, con asistencias y pagos conciliados. Ejecutar cada variante del catálogo; una liquidación aislada no cubre todo el caso. |
| [LIQ-003 · Liquidar con clases del mes todavía sin asistencia](../casos/LIQ-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-004 · Generar dos veces la liquidación del mismo mes](../casos/LIQ-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-005 · Liquidar un mes que todavía no terminó](../casos/LIQ-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-006 · Liquidación que no se puede generar](../casos/LIQ-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-007 · Recalcular una liquidación abierta](../casos/LIQ-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-008 · Cerrar una liquidación](../casos/LIQ-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-009 · Pagar una liquidación](../casos/LIQ-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-010 · Pagos que no corresponden](../casos/LIQ-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-011 · Pagar dos veces](../casos/LIQ-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-012 · Cancelar una liquidación cerrada sin pagar](../casos/LIQ-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-013 · Tocar una liquidación pagada](../casos/LIQ-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-014 · Eliminar una liquidación abierta](../casos/LIQ-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-015 · Ajustar el importe final de una comisión](../casos/LIQ-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-016 · Entrar a Liquidaciones sin ser dueño](../casos/LIQ-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-017 · Cambia la tarifa después de liquidar](../casos/LIQ-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-018 · Listado de liquidaciones](../casos/LIQ-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-019 · La pantalla de Nueva liquidación](../casos/LIQ-019.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [LIQ-020 · Lo que muestra la liquidación al pagar](../casos/LIQ-020.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Movimientos de caja

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [MOV-001 · Pagar un gasto chico con efectivo del cajón](../casos/MOV-001.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-002 · Registrar un ingreso que no es una cuota](../casos/MOV-002.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-003 · Rubros que puede usar la operativa](../casos/MOV-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-004 · Gasto en efectivo mayor que la plata del cajón](../casos/MOV-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-005 · Movimiento con datos mal cargados](../casos/MOV-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-006 · El dueño quiere cargar un movimiento de caja](../casos/MOV-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-007 · Corregir un movimiento manual del turno](../casos/MOV-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-008 · Eliminar un movimiento manual del turno](../casos/MOV-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-009 · Modificar movimientos según el estado de la caja](../casos/MOV-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-010 · Agregar a una caja rechazada un gasto que se había olvidado](../casos/MOV-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-011 · Los cobros de cuota los ven todas las operativas](../casos/MOV-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-012 · Historial de la operativa](../casos/MOV-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-013 · Historial del dueño: sin límites](../casos/MOV-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-014 · El Historial dice lo mismo que Caja y Cashflow](../casos/MOV-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-015 · Guardar dos veces el mismo movimiento](../casos/MOV-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-016 · Tocar movimientos de una caja ajena](../casos/MOV-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-017 · Editar o eliminar un cobro de cuota como si fuera un movimiento manual](../casos/MOV-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [MOV-018 · El enlace a la caja desde Movimientos](../casos/MOV-018.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Primera carga

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [PCA-001 · Primera carga completa desde el Excel](../casos/PCA-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-002 · Excel con errores](../casos/PCA-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-003 · Archivo que no corresponde](../casos/PCA-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-004 · Cambia algo entre la revisión y la carga](../casos/PCA-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-005 · Cargar dos veces](../casos/PCA-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-006 · Deshacer la primera carga](../casos/PCA-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-007 · Alumno cargado con inscripción pendiente y sin cuotas](../casos/PCA-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PCA-008 · La primera carga es solo del dueño](../casos/PCA-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Reglas definidas y todavía no programadas

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [PEN-001 · Recargo por mora](../casos/PEN-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PEN-002 · Clases particulares](../casos/PEN-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PEN-003 · Justificar una excepción de acceso a clase](../casos/PEN-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Profesores

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [PRO-001 · Alta de una profesora](../casos/PRO-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PRO-002 · Profesora mal cargada](../casos/PRO-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PRO-003 · Cambiar la tarifa o el nombre de una profesora](../casos/PRO-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PRO-004 · Desactivar a una profesora con clases futuras](../casos/PRO-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [PRO-005 · Ficha de una profesora](../casos/PRO-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Recibos

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [REC-001 · Recibo de un cobro de cuota](../casos/REC-001.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REC-002 · Recibo con inscripción, varios meses o pago parcial](../casos/REC-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REC-003 · Recibo de un cobro cancelado o anulado](../casos/REC-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REC-004 · Quién puede abrir un recibo de cuota](../casos/REC-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REC-005 · Recibo de una liquidación pagada](../casos/REC-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REC-006 · Abrir muchas veces el mismo recibo](../casos/REC-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Reportes e Inicio

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [REP-001 · Inicio del dueño: los números del mes](../casos/REP-001.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-002 · Inicio de la operativa](../casos/REP-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-003 · Reporte de Finanzas del mes](../casos/REP-003.html) | Parcial | 1 | 0/0/0/1 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-004 · Reporte con cajas todavía sin validar](../casos/REP-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-005 · El mes cerrado no cambia](../casos/REP-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-006 · Reporte de Alumnos](../casos/REP-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-007 · Reporte de Sueldos](../casos/REP-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-008 · Entrar a Reportes o al Inicio del dueño sin serlo](../casos/REP-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-009 · Movimiento en un subrubro sin clasificar](../casos/REP-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-010 · Cuadre diario de plata](../casos/REP-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-011 · Finanzas filtrado por deporte](../casos/REP-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-012 · Comparación con el mes anterior y evolución](../casos/REP-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-013 · Una caja de octubre validada en noviembre](../casos/REP-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-014 · Alumnos que dejaron de venir](../casos/REP-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-015 · Cómo le va a cada grupo](../casos/REP-015.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-016 · El Inicio de la operativa a lo largo del día](../casos/REP-016.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [REP-017 · Los avisos del Inicio del dueño llevan a su lista](../casos/REP-017.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Transversales

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [SIS-001 · Los importes se escriben igual en todas las pantallas](../casos/SIS-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-002 · Los importes se muestran igual en todas las pantallas](../casos/SIS-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-003 · Un día entero desde el teléfono](../casos/SIS-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-004 · Fechas y horas en hora de Argentina](../casos/SIS-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-005 · Doble clic y botón atrás en todos los formularios](../casos/SIS-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-006 · Textos, idioma y mensajes](../casos/SIS-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-007 · El sistema con mucho volumen](../casos/SIS-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-008 · Dos pestañas, dos equipos, dos personas](../casos/SIS-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-009 · El reloj simulado del sitio de prueba](../casos/SIS-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-010 · Lo que no tiene vuelta atrás](../casos/SIS-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-011 · Respaldo y vuelta atrás de los datos](../casos/SIS-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-012 · Fichas sin «Próximamente»: datos reales](../casos/SIS-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-013 · El menú de cada rol](../casos/SIS-013.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [SIS-014 · Los interruptores Activo de los listados](../casos/SIS-014.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

### Usuarios

| Caso | Estado | Intentos | OK/Fallo/Reparado/Parcial | Día propuesto |
|---|---|---:|---|---|
| [USU-001 · Alta de una operativa nueva](../casos/USU-001.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-002 · Alta de usuario con datos mal cargados](../casos/USU-002.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-003 · Alta de un usuario para una profesora](../casos/USU-003.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-004 · Cambiar el nombre de un usuario que ya trabajó](../casos/USU-004.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-005 · Cambiarle la clave a otro usuario](../casos/USU-005.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-006 · El dueño intenta cambiarse el rol o desactivarse](../casos/USU-006.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-007 · Desactivar a una operativa que tiene la caja abierta](../casos/USU-007.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-008 · Cambiarle el rol a alguien que ya trabajó](../casos/USU-008.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-009 · La cuenta de dueño protegida](../casos/USU-009.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-010 · Reactivar un usuario desactivado](../casos/USU-010.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-011 · Crear dos veces el mismo usuario o guardar dos veces](../casos/USU-011.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |
| [USU-012 · Cambiar la propia clave u olvidarla](../casos/USU-012.html) | Sin probar | 0 | 0/0/0/0 | Sin programar. No hay una jornada asignada para completar el caso. |

## Reconciliación de vínculos

[
  {
    "ejecucion": "D4-T03",
    "caso": "ALU-002",
    "letra": "e",
    "alcance": "Consulta de la nueva alumna en Cobranza: parte del efecto indirecto del alta. No prueba todos los cruces ni el buscador textual."
  },
  {
    "ejecucion": "D4-T15",
    "caso": "ALU-002",
    "letra": "e",
    "alcance": "Consulta de matrícula por admin: parte del efecto del alta en Reportes. No prueba todas las pantallas ni un reporte de altas mensuales."
  }
]
Estos dos vínculos se justifican en ALU-002.e (efectos en Cobranza y Reportes). Se registran el 23/10 retrospectivamente; no son nuevas operaciones. D4-T12 queda sin código hasta resolver su cobertura. No se modifica el catálogo Carlos/Claude.

## Planes, soluciones y retests

### PLAN-D4-02 / ALU-002

Diferencia de presentación respecto del guion. La ficha muestra días de gracia, sin la fecha explícita y el total conjunto esperados en el relato. Los cargos y el estado sí fueron contrastados.
El operador debe interpretar el vencimiento y sumar los cargos. No se demostró un error del cálculo de deuda.
Solución propuesta: Contrastar el texto del guion con la regla vigente y presentar la información necesaria para el operador. Cualquier cambio de pantalla requiere el protocolo de diseño.

- Revisar ficha, Cobranza y regla de vencimiento en test sin volver a dar de alta a Mora.
- Determinar si corresponde corregir la expectativa del guion o presentar fecha y total en pantalla.
- Implementar la decisión si corresponde; registrar versión y evidencia.
- Reprobar la variante y completar los criterios todavía pendientes del alta de menor.

Aceptación: Vencimiento y cargos comprobables por el operador y coherentes entre pantallas; alta de menor completa según el catálogo.
Responsable: Codex: contraste del guion y propuesta; implementación por asignar
Implementación, commit, retest, fecha y probador de retest: pendientes. No hay reparación acreditada.

### PLAN-D4-03 / COB-008

Cobertura incompleta; no falla demostrada. Dante recibió imputaciones manuales por mes. Esto acredita la entrega parcial, no un reparto automático global por FIFO. Faltan segundo pago y comisión.
Dar por aprobado el caso ocultaría comportamientos que todavía no se probaron.
Solución propuesta: Distinguir el ingreso global del importe y la imputación por mes. Preparar la prueba pendiente con otra operación real de la jornada; no repetir el cobro ya registrado.

- Revisar qué acción ofrece la pantalla y documentar el alcance real del caso.
- Programar el saldo pendiente y verificar sus dos pagos y recibos cuando corresponda en la historia.
- Comprobar saldo, caja y comisión con fechas y versión; si aparece un fallo, abrir incidencia separada.

Aceptación: Saldo correcto, recibos e imputaciones íntegros y comisión verificada; sin afirmar FIFO automático a partir de una distribución manual.
Responsable: Codex: diseñar comprobación; ejecutor manual por asignar
Implementación, commit, retest, fecha y probador de retest: pendientes. No hay reparación acreditada.

### PLAN-D4-01 / COB-012

Incidencia observada; regla a resolver. La alumna figura Al día, pero Cobrar ofrece octubre. El adelanto se hizo sobre Julieta, por $30.000; no se acredita la variante completa del catálogo.
El operativo puede interpretar como exigible un importe que no figura en la deuda guardada. No se demostró una deuda duplicada persistida.
Solución propuesta: Acordar si Cobrar debe ofrecer una cuota potencial cuando el estado dice Al día; luego alinear explicación y cálculo conforme a esa regla. No crear ni borrar deuda para hacer coincidir el reporte.

- Reproducir por consulta la discrepancia en el sitio de test, con rol y versión registrados, sin cobrar otra vez.
- Presentar la regla y alternativas para resolver la discrepancia antes de cambiar comportamiento.
- Asignar implementación con evidencia del cambio; si afecta vistas, aplicar el protocolo de diseño.
- Repetir el caso en test y comprobar deuda, estado, adelanto, caja y reportes; completar generación y comisión en noviembre.

Aceptación: Estado y explicación de Cobrar coherentes con la regla decidida, sin cobros ni cuotas duplicadas. Completar cada criterio del catálogo para aprobar el caso.
Responsable: Codex: análisis y coordinación; implementación por asignar
Implementación, commit, retest, fecha y probador de retest: pendientes. No hay reparación acreditada.

## Metas y mediciones

Las metas funcionales proceden del guion o de invariantes posteriores identificados en cada fila. El valor de una variante no reemplaza el importe ni las condiciones originales del catálogo.
Rendimiento CLI: 35,83 ms, 29 SQL, 11,37 ms SQL, n=1. Óptimo comparable no definido; sin percentiles ni carga. ≤1.000 ms visible es una propuesta de criterio, no una medición ni una meta aprobada.

## Archivos y continuación

- [Datos por caso](../seguimiento-casos.json): historial, criterios, ambiente, evidencias, contadores, metas, planes y agenda.
- [Día 4 técnico original](DIA-04.md): operaciones, código, datos y métodos auxiliares. Sus recuentos históricos mezclaban apoyo aislado: para aceptación usar la proyección por casos de esta entrega.
- [Dashboard](../CONTROL-INTEGRAL.html): lectura humana; una página por módulo, caso y jornada.
Para nuevas jornadas: incorporar operaciones y vínculos comprobados, con fecha, entorno, rol, versión, esperado previo y evidencia; extender la importación más allá del Día 4. Nunca copiar los resultados de este día a otro.
Reparado requiere falla previa, solución identificada y nueva ejecución OK en el sitio, con fecha, probador y evidencia. Plan no equivale a solución implementada. Otro agente debe revisar esta entrega; no firmar por otro Codex sin identidad y cuenta distinta confirmadas.
