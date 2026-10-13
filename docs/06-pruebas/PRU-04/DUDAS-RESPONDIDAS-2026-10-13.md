# PRU-04 · Respuestas de Carlos a las 56 dudas — qué hay que modificar

13/10/2026 · Claude CyE. Las preguntas, el contexto y las respuestas textuales están en la
[Tabla de pruebas](https://claude.ai/artifact/Dc1Ya2fCaxGGiZxGcDgA46), pestaña Dudas.
Este archivo es la lectura de Claude: qué cambia en el sistema por cada respuesta y cuándo
conviene hacerlo. **Es una propuesta de orden; lo decide Carlos.**

El «hoy» de cada duda lo escribió otra sesión de Claude leyendo el código. Solo reverifiqué
lo marcado con (v).

## 1. Antes de seguir con la prueba

Son las que cambian lo que la prueba mira todos los días, o la traban.

| Duda | Qué se modifica | Tamaño |
|---|---|---|
| DU-46 | Reloj simulado: que al frenarse no deje a todos afuera. Sin esto no se puede avanzar de día. | Chico |
| DU-08 + T31 | Estado por inscripción y 7 días propios del alumno nuevo. Ya está con Gemini. | Medio |
| DU-25 | El dueño puede perdonar la inscripción, con motivo. Va pegado a T31: si no, un becado queda deudor para siempre. | Chico |
| DU-33 | Clase con lista tomada y cero presentes: sale del contador «Sin asistencia» y queda para que el dueño decida si la paga. Cambia el número que se mira cada día. | Chico |
| DU-35 | No se toma asistencia de una clase que todavía no ocurrió. | Chico |
| DU-54 | Validar una caja exige todos los movimientos tildados. Cambia el paso diario de validación; los tildes hoy no se guardan. | Medio |
| DU-18 | Rechazar una caja exige motivo. | Chico |
| DU-12 | Reactivar a un alumno le genera la cuota del mes con el porcentaje de reingreso, si no la tiene; su deuda anterior se mantiene. | Medio |
| DU-21 | Anular un cobro del dueño: el asiento en contra lleva la fecha del original. Cambia a qué mes va la resta. | Chico |

## 2. Antes de que la prueba llegue a fin de mes y al día 1

| Duda | Qué se modifica | Tamaño |
|---|---|---|
| DU-52 | El generador de cuotas del día 1 se reintenta los días siguientes y avisa si no corrió. | Medio |
| DU-37 | No se puede cerrar una liquidación antes de que termine el mes. | Chico |
| DU-36 | En la liquidación, avisar al dueño qué comisión viene de deudas viejas o adelantados; él valida o no, y el recibo sale según lo que decida. | Grande |
| DU-19 | Un mes cobrado por adelantado es ingreso y comisión del mes que paga, no del mes en que se cobró. **Choca con DU-36: ver sección 6.** | Grande |
| DU-40 | Un aumento de precio rige desde el mes siguiente para los alumnos que ya están; a los nuevos se les aplica desde que cambia. Hoy el plan tiene un solo precio. | Grande |
| DU-42 | Cambiar hora/comisión de un deporte: solo sin liquidaciones abiertas y rige desde el mes siguiente. | Medio |
| DU-44 | Desactivar a una profesora: avisar, pedir reasignar clases futuras, permitir liquidarle lo dictado y desactivar su usuario. | Medio |

## 3. Sin apuro: se hacen en paralelo y se prueban cuando toque su caso

Cuidados al guardar y correcciones:

| Duda | Qué se modifica | Tamaño |
|---|---|---|
| DU-02 | Página en castellano al abrir a mano la dirección de una acción. | Chico |
| DU-04 | Al renombrar a una operativa se renombra su rubro de sueldo. | Chico |
| DU-05 | No se desactiva ni cambia de rol a una operativa con la caja abierta: avisa y pide cerrarla. | Chico |
| DU-06 | Fecha de ingreso futura: solo hasta el mes siguiente. | Chico |
| DU-07 | Editar o eliminar un movimiento manual de caja deja rastro, igual que en Cashflow. | Medio |
| DU-09 | Las clases pasadas muestran también a quien tiene una marca guardada, aunque haya cambiado de grupo o se haya dado de baja. | Medio |
| DU-10 | El dueño corrige un DNI mal cargado, con motivo, aunque ya tenga inscripción. | Medio |
| DU-13 | Buscar alumnos: con y sin acento es lo mismo, por cualquier parte del nombre y ofreciendo opciones desde la primera letra. | Medio |
| DU-14 | Si dos personas editan al mismo alumno o la misma lista, se avisa al segundo que el dato cambió. | Medio |
| DU-15 | Un alumno activo no puede quedar sin plan. | Medio |
| DU-16 | No se puede desactivar el medio de pago elegido como efectivo del cajón. | Chico |
| DU-23 | El dueño puede crear, desde la ficha, la cuota de un mes pasado que nunca se generó. | Medio |
| DU-24 | El dueño que entra a «Nuevo movimiento de caja» va directo a Cashflow. | Chico |
| DU-28 | Cada medio de pago dice si admite saldo negativo (descubierto). Si no admite, se impide; si admite, avisa y deja seguir. | Medio |
| DU-29 | Pasar plata entre medios: un egreso y un ingreso con rubros que no cuenten. Necesita una cuarta clasificación, «Traspaso», además de del club, aporte y retiro. | Medio |
| DU-30 | Cancelar una clase con lista tomada: avisa y pide confirmar; se impide si está en una liquidación cerrada o pagada. | Medio |
| DU-31 | Reactivar una clase cancelada: controla choques de horario y conserva el motivo de la cancelación. | Medio |
| DU-41 | No se desactiva un deporte o un grupo con alumnos activos o clases futuras. | Chico |
| DU-45 | Las reglas de primer pago no pueden superponerse ni dejar días sin cubrir. | Chico |
| DU-48 | El dueño puede eliminar un alumno o una clase creados por error, si no tienen cobros ni asistencias. | Medio |
| DU-55 | Una clase necesita al menos una profesora. | Chico |
| DU-56 | La lista de asistencia muestra solo los tres últimos números del DNI. | Chico |
| DU-57 | Quitar los carteles «Próximamente». | Chico |

Reportes:

| Duda | Qué se modifica | Tamaño |
|---|---|---|
| DU-17 | Resumen de faltantes y sobrantes por operativa y por mes. | Medio |
| DU-39 | Reporte de Alumnos: cantidad de altas y de bajas del mes. Hoy no se guarda la fecha de baja. | Medio |

## 4. No cambia nada en el sistema

| Duda | Por qué |
|---|---|
| DU-01 | Sesión vencida: se acepta que lo cargado se pierde. |
| DU-22 | Cobro con fecha de un mes anterior: confirmado como está. |
| DU-26 | Cuota del mes siguiente al que ingresó y pagó en los últimos 15 días: confirmado. |
| DU-32 | Suplencias: lo corrige la operativa cambiando la profesora, o el dueño al liquidar. |
| DU-34 | Asistencias de más: solo se anotan. |
| DU-38 | Liquidación pagada de más: no se revierte, se compensa en la siguiente. |
| DU-43 | (v) La clasificación de un rubro ya rige solo hacia adelante: cada movimiento guarda la que tenía al registrarse (`ReporteMensualService.php:150`). El «hoy» de la duda estaba mal. |
| DU-50 | Reportes por grupo: alcanza por profesor y deporte. |
| DU-53 | Motivo al corregir en Cashflow: sigue opcional. |

Van al manual o la ayuda (T12), sin tocar el sistema:

- DU-20: el dueño nunca usa la plata del cajón; si la usa, lo registra la operativa en su caja.
- DU-27: un movimiento mal cargado en una caja ya validada se corrige con un ajuste directo en Cashflow, con motivo.
- DU-29: cómo registrar que se lleva efectivo al banco.

Mejora futura: DU-47 (que cada usuario cambie su clave y recuperarla por mail).

## 5. Preguntas rehechas, esperan respuesta en la tabla

Carlos no pudo contestar tres; se volvieron a redactar el 13/10 con opciones concretas:

- **DU-11** — un alumno se da de baja debiendo plata: dónde tiene que quedar esa deuda.
- **DU-49** — si quiere una lista de alumnos que están faltando, para llamarlos.
- **DU-51** quedó respondida por otras (DU-27, DU-38, DU-48, DU-10). Las dos que faltaban
  son preguntas nuevas: **DU-58** (deshacer una cuota perdonada por error) y **DU-59**
  (cambiar el medio del cajón si se eligió mal).

## 6. La contradicción entre DU-19 y DU-36, resuelta por Carlos el 13/10

«No son el mismo caso, por eso las respuestas son distintas.»

- **Adelantado (DU-19):** noviembre cobrado en octubre es ingreso y comisión de noviembre.
  Se le paga a la profesora en la liquidación de noviembre.
- **Cuotas atrasadas (DU-36):** se cobran en el mes y entran en la liquidación de ese mes,
  pero se le avisa al dueño, que valida o no; el recibo sale según lo que decida.
