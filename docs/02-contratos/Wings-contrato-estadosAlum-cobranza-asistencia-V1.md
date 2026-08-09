# Wings-Contrato-EstadosAlum-Cobranza-Asistencia-V1.md

**Caso de uso (Index):** 10) Motor de estados + Cobranza + Control de asistencia/plan
**Versión:** V1
**Estado:** CERRADO como acuerdo de negocio · **NO implementado en su mayor parte** (ver §10)
**Fecha:** 2026-08-09
**Origen:** definido con el usuario en conversación, a partir del hallazgo DOC-01 de la auditoría v02/v03: esta regla gobierna el módulo de Cobranza y el color de estado de cada alumno, pero **no estaba escrita en ningún lado**. Por eso quedó implementada tres veces con criterios divergentes.

> ⚠️ **Advertencia de lectura.** Este documento describe **lo que el negocio necesita**, no lo que el sistema hace hoy. La sección §10 dice, punto por punto, qué está implementado y qué no. No asumir que algo funciona porque está acá.

---

## 1. Modelo de pago

El servicio se paga **por adelantado**, entendido así:

- La cuota de un mes se paga **durante ese mes, a partir del día 1**.
- La cuota de agosto se paga en agosto. La de septiembre se paga a partir del 1 de septiembre.
- **No** se paga un mes antes de que empiece.

**Días de gracia:** existe un margen configurable desde el principio del mes. La gente no cobra su sueldo el día 1, y se trata con clientes, no con morosos presuntos.

- El valor es **configuración del sistema**, no una constante en el código.
- Valor actual en el código: 10 días (hardcodeado — ver §10).

**Pago de varios meses juntos:** fuera de alcance. Si alguna vez ocurre, se registran cobros separados, uno por mes. No se construye funcionalidad para esto.

---

## 2. Estados de cobranza

### Para qué existen estos estados

**Categorizar a alguien como MOROSO o DEUDOR sirve para activar la cobranza y el seguimiento del pago.** No es una etiqueta descriptiva ni un juicio sobre el cliente: es el disparador de una gestión.

De ahí se desprende todo lo demás. La gente paga cuando cobra, y eso no suele caer el día 1 del mes. Marcar como moroso a alguien el día 2 no tendría ningún sentido: no hay nada que gestionar, todavía no se atrasó nadie. Por eso existen los días de gracia — no son una concesión, son el reconocimiento de cómo cobra la gente.

**Regla para resolver dudas futuras:** ante cualquier caso ambiguo, preguntarse *"¿corresponde salir a reclamar este pago?"*. Si la respuesta es no, el alumno está AL DÍA.

### Los cuatro estados

| Estado | Definición | ¿Hay que reclamar? |
|---|---|---|
| **AL DÍA** | Pagó el mes anterior y el corriente. | No |
| **EN PLAZO** | Pagó el mes anterior, debe el corriente, y **todavía está dentro de los días de gracia**. | No, todavía no |
| **MOROSO** | Pasó el día de gracia configurado y debe el **mes corriente**. | Sí |
| **DEUDOR** | Arrastra un mes **ya cerrado** sin pagar, **o nunca pagó ninguna cuota**. | Sí, con más urgencia |

La escala parte al medio: los dos primeros son *"no hay nada que hacer"*, los dos últimos son *"hay que salir a cobrar"*. Ese corte es el que usa quien trabaja la cobranza.

### Orden de evaluación (importa)

Los estados se evalúan **en este orden**, y el primero que da verdadero gana:

1. **¿Arrastra algún mes cerrado sin pagar, o nunca pagó?** → DEUDOR
2. **¿Debe el mes corriente y ya pasó el día de gracia?** → MOROSO
3. **¿Debe el mes corriente pero sigue dentro de la gracia?** → EN PLAZO
4. Si no → AL DÍA

**DEUDOR gana sobre todo lo demás.** Alguien que pagó agosto pero debe julio, el 5 de agosto es DEUDOR — no EN PLAZO — porque arrastra un mes cerrado. Sin este orden explícito, es un error casi garantizado al implementar.

### Ejemplos concretos (con gracia configurada en 10 días)

| Fecha | Situación del alumno | Estado |
|---|---|---|
| 5 de agosto | Pagó julio y agosto | **AL DÍA** |
| 5 de agosto | Pagó julio, no pagó agosto | **EN PLAZO** |
| 15 de agosto | Pagó julio, no pagó agosto | **MOROSO** |
| 5 de agosto | No pagó julio | **DEUDOR** (arrastra mes cerrado, sin importar agosto) |
| 1 de septiembre | Pagó agosto, no pagó septiembre | **EN PLAZO** |
| 1 de septiembre | No pagó agosto | **DEUDOR** |
| 15 de septiembre | Pagó agosto, no pagó septiembre | **MOROSO** |
| 15 de septiembre | Debe agosto y septiembre | **DEUDOR** |
| Cualquier fecha | Alumno nuevo que nunca pagó | **DEUDOR** |

**El paso de MOROSO a DEUDOR es automático:** un moroso de agosto se convierte en deudor el 1 de septiembre, sin que nadie haga nada, porque agosto pasa a ser un mes cerrado.

> El día de gracia es **configuración**, nunca un número fijo en el código. Los ejemplos usan 10 porque es el valor actual; si mañana pasa a 15, los estados tienen que acompañar solos.

---

## 3. Alumno nuevo vs alumno con historia

**Un alumno es NUEVO hasta que paga su primera cuota.** Punto. En cuanto registra su primer pago, deja de serlo para siempre.

No se hace ninguna distinción adicional por antigüedad. Un alumno de dos años y uno de dos meses son lo mismo: alumnos con historia. **Fuera de alcance.**

*Nota técnica: el sistema ya puede derivar esto sin campos nuevos — es "no tiene pagos registrados".*

---

## 4. Acceso a clase — alumno NUEVO

Un alumno nuevo que no pagó tiene un margen de dos clases:

| Clase | Qué pasa |
|---|---|
| **1ª** | Entra. Es la clase de prueba. |
| **2ª** | Entra. El pago puede estar registrándose durante la clase. |
| **3ª en adelante** | **No hay motivo para que entre sin haber pagado.** Si igual se lo deja entrar, hay que justificarlo (ver §7). |

El contador de clases es **interno**: no se muestra en pantalla. Solo se lleva para alumnos nuevos, y deja de importar en cuanto pagan.

---

## 5. Acceso a clase — alumno CON HISTORIA

Un cliente que se atrasa es normal y pasa. No se lo trata como al nuevo.

| Estado | Al tomar asistencia |
|---|---|
| **AL DÍA** | Entra. Nada que mostrar. |
| **EN PLAZO** | Entra normalmente. |
| **MOROSO** | **Entra normalmente, sin justificar.** Quien toma asistencia lo ve, nada más. |
| **DEUDOR** | **Hay que justificar** para dejarlo entrar (ver §7). |

O sea: **el mes corriente impago se ve pero no traba. El mes cerrado impago sí.**

La lógica es la misma que la de los estados: mientras el reclamo todavía no corresponde —o recién empieza— no tiene sentido frenar a nadie. Cuando el alumno arrastra un mes cerrado, ahí sí alguien tiene que hacerse cargo de la decisión de dejarlo pasar.

---

## 6. Qué ve quien toma asistencia

**Siempre**, sin excepción: quien toma asistencia —profesor u operativo— **ve la condición de cada alumno** al lado de su nombre.

Nadie debería tener que ir a otra pantalla a averiguar si el alumno está en condiciones de tomar la clase.

---

## 7. Excepción justificada — mecanismo único

Cuatro situaciones distintas usan **exactamente el mismo mecanismo**. No son cuatro funciones, es una:

| Situación | Cuándo aplica |
|---|---|
| Alumno nuevo entra a la 3ª clase o posterior sin haber pagado | §4 |
| Alumno con historia en estado DEUDOR asiste a clase | §5 |
| Alumno asiste a más clases de las que su plan cubre | control de plan |
| Se registra un cobro parcial de una cuota | §8 |

**En todos los casos:**

1. La acción **no se impide**. Se permite.
2. Quien la ejecuta debe dejar un **motivo obligatorio**.
3. El **administrador es notificado**: al abrir su usuario, o por mail.

La lógica es: el que está en el mostrador o en la cancha sabe lo que está haciendo y tiene contexto que el sistema no tiene. No se le bloquea el trabajo — se deja registro y se avisa a quien tiene que enterarse.

---

## 8. Pago parcial

**Está permitido.** Un cliente puede tener un problema económico y pagar una parte.

- La cuota queda con parte pagada y parte pendiente.
- Requiere **justificación** y **notificación al administrador**, igual que §7.

---

## 9. Visibilidad: movimientos vs caja

Son dos cosas distintas y no deben confundirse:

| Qué | Quién lo ve | Por qué |
|---|---|---|
| **Movimientos** (historial de cobros y gastos) | Cualquier operativo, sin restricción de propiedad | Es información operativa. Viene una madre y dice "pagué ayer a otra persona": el operativo de hoy tiene que poder verlo. |
| **La caja** (sus movimientos como conjunto, resumen, si está validada, si se rindió) | Solo quien la abrió. El admin ve todas. | La caja es la rendición de una persona. |

> Esto **corrige** el hallazgo P-04 de la auditoría v02, que trató ambas cosas como una sola y reportó una contradicción que no existía. `PERMISOS-ROLES.md` habla de los **movimientos**, y ahí no hay restricción de propiedad — está bien como está.

---

## 10. Estado de implementación

**Nada de lo anterior debe darse por funcionando.** Estado real al 2026-08-09:

| Regla | Estado |
|---|---|
| Los cuatro estados de cobranza | ⚠️ Hoy se calculan **tres** (AL_DIA / MOROSO / DEUDOR), con criterio distinto al de §2 y **escritos tres veces** con divergencias. **EN PLAZO no existe**: hoy se mezcla dentro de AL DÍA |
| Orden de evaluación con DEUDOR primero | ⚠️ El orden actual coincide, pero no está documentado en el código ni cubierto por ningún test |
| Días de gracia configurables | ❌ Hardcodeado (constante `DIA_GRACIA = 10`) |
| DEUDOR cuando nunca pagó ninguna cuota | ❌ No contemplado como disparador explícito |
| El estado se usa para algo | ❌ **Se calcula y no se consume.** No bloquea, no advierte, no condiciona nada |
| Quien toma asistencia ve la condición del alumno | ❌ La pantalla muestra el plan semanal, no la deuda |
| Concepto de alumno nuevo | ⚠️ Derivable (`tienePagos()`), pero no se usa para esto |
| Contador de clases del alumno nuevo | ❌ No existe |
| Justificación al dejar entrar a un DEUDOR | ❌ No existe |
| Justificación por exceso de plan | ⚠️ Existe parcialmente (motivo EXTRA/RECUPERA), no unificada |
| Justificación en cobro parcial | ❌ No existe |
| Notificación al administrador | ❌ **No existe en ninguna forma.** El sistema no notifica nada, por ningún medio |
| Movimientos visibles para todos | ⚠️ El historial sí; `/movimientos` filtra por caja propia y **no debería** |
| Caja restringida a su dueño | ✅ Implementado correctamente |

**Deuda técnica asociada:** hoy existen tres campos de "motivo" dispersos (exceso de plan, corrección de asistencia, cambio de profesor). Al implementar §7 conviene unificarlos en un registro único de excepciones, no agregar un cuarto.

---

## 11. Reglas relacionadas, ya acordadas

Definidas en la misma conversación, se registran acá para que no se pierdan:

- **Descuento de primera cuota:** aplica **solo a la primera cuota**. Si se pagan varios meses juntos, los demás van completos.
- **Comisiones a profesores:** se cierran **por mes**.

---

## 12. Reglas Freeze

- Un alumno es nuevo únicamente hasta su primer pago registrado.
- El alumno nuevo tiene dos clases de margen; a partir de la tercera sin pagar, se justifica.
- Son cuatro estados: AL DÍA, EN PLAZO, MOROSO y DEUDOR. Se evalúan en orden y DEUDOR gana sobre todos.
- AL DÍA, EN PLAZO y MOROSO no requieren justificación para entrar a clase; DEUDOR sí.
- Ninguna de las cuatro excepciones bloquea la operación: todas permiten, exigen motivo y notifican al admin.
- Los días de gracia son configuración, nunca un valor fijo en el código.
- Ver movimientos ajenos es legítimo; ver la caja ajena no.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 13. Estado del documento

🔒 **CERRADO como acuerdo de negocio.** Define qué debe hacer el sistema.

**No cerrado como implementación:** la mayor parte de §2 a §8 no está construida. Al implementar cada punto, actualizar la tabla de §10 con una de estas cuatro salidas, nunca otra:

**implementado y verificado** · **pendiente** · **implementado sin verificar (y por qué)** · **descartado (y por qué)**
