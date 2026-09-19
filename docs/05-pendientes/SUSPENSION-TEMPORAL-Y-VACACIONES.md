# Suspender la cuota sin dar de baja al alumno — planteo

> **No es una tarea. No se implementa ni se diseña todavía.**
> Planteado por Carlos el 19/09/2026 para que no se pierda. Cuando llegue el
> momento, esto es el punto de partida de la conversación, no una solución.

## Qué problema resuelve

Hoy un alumno está **activo o inactivo**, y no hay nada en el medio. Si el alumno
se va dos meses —una lesión, un viaje, los finales de la facultad— las dos
opciones que hay son malas:

- **Dejarlo activo:** se le sigue generando la cuota todos los meses y después hay
  que perdonarla una por una.
- **Darlo de baja:** deja de figurar como alumno del club, sale de los listados y
  de las clases, y cuando vuelve hay que reconstruir su situación a mano.

Lo que falta es un estado intermedio: **sigue siendo alumno del club, pero este mes
no se le genera cuota.**

## Los dos casos

**1. Suspensión de un alumno, por un tiempo definido.** Enfermedad, vacaciones,
estudios. El alumno no deja de pertenecer al club: se le pausa la cuota desde tal
mes hasta tal mes.

**2. Vacaciones del club.** El club cierra —enero, receso de invierno— y **a nadie**
se le genera cuota ese mes. Es el mismo mecanismo pero para todos a la vez, y sin
tener que tocar alumno por alumno.

## Lo que ya sabemos que hay que decidir cuando se encare

No responder ahora; son las preguntas que van a aparecer:

- ¿La suspensión tiene fecha de fin, o se levanta a mano? Si tiene fecha, ¿qué pasa
  el mes que vuelve: se le genera la cuota completa o proporcional?
- Un alumno suspendido, ¿aparece en el listado de alumnos? ¿Puede asistir a una
  clase? ¿Entra en la liquidación del profesor si asiste?
- ¿Qué pasa con lo que ya debía antes de suspenderse? (Por lo decidido en FIN-08:
  el pasado no se toca.)
- Las vacaciones del club, ¿son un mes sin generación o un mes con cuota en cero?
  No es lo mismo para los reportes.
- ¿El profesor cobra en un mes de vacaciones del club?

## Por qué no se hace ahora

Toca el proceso mensual de generación de deuda, que es lo que decide cuánto se le
cobra a cada alumno. No se mete una funcionalidad así antes de la prueba grande.

## Relacionado

- [FIN-08](../07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md) — decisión del 19/09:
  la cola de revisión mira hacia adelante y no toca lo ya generado.
- `PagoCuotaService::crearDeudaSiNoExiste()` y el comando `cobranza:generar-deudas`
  son los dos lugares que habría que tocar.
