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

Tres estados, y solo tres:

| Estado | Definición |
|---|---|
| **AL DÍA** | No debe nada vencido. Incluye al que todavía no pagó el mes corriente pero está dentro de los días de gracia. |
| **MOROSO** | Pasaron los días de gracia y debe el **mes corriente** (solo ese). |
| **DEUDOR** | Debe algún mes **ya cerrado** (anterior al corriente), **o nunca pagó ninguna cuota**. |

### Ejemplo concreto (gracia = 10 días)

| Fecha | Situación del alumno | Estado |
|---|---|---|
| 5 de agosto | No pagó agosto | **AL DÍA** (dentro de gracia) |
| 15 de agosto | No pagó agosto | **MOROSO** |
| 1 de septiembre | Sigue debiendo agosto | **DEUDOR** |
| 15 de septiembre | Pagó agosto, no pagó septiembre | **MOROSO** |
| 15 de septiembre | Debe agosto y septiembre | **DEUDOR** |
| Cualquier fecha | Alumno nuevo que nunca pagó | **DEUDOR** |

> **Confirmar este cuadro antes de implementar.** Es la forma más rápida de detectar si la regla quedó mal entendida.

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

- **MOROSO** (debe el mes corriente): **entra normalmente, sin justificar.** Quien toma asistencia lo ve, nada más.
- **DEUDOR** (debe un mes ya cerrado): **hay que justificar** para dejarlo entrar (ver §7).

O sea: el mes corriente impago se ve pero no traba. El mes cerrado impago sí.

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
| Los tres estados de cobranza se calculan | ✅ Implementado, pero con criterio distinto al de §2 y **escrito tres veces** con divergencias |
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
- MOROSO no requiere justificación; DEUDOR sí.
- Ninguna de las cuatro excepciones bloquea la operación: todas permiten, exigen motivo y notifican al admin.
- Los días de gracia son configuración, nunca un valor fijo en el código.
- Ver movimientos ajenos es legítimo; ver la caja ajena no.

Cualquier cambio futuro requiere versión V2 explícita.

---

## 13. Estado del documento

🔒 **CERRADO como acuerdo de negocio.** Define qué debe hacer el sistema.

**No cerrado como implementación:** la mayor parte de §2 a §8 no está construida. Al implementar cada punto, actualizar la tabla de §10 con una de estas cuatro salidas, nunca otra:

**implementado y verificado** · **pendiente** · **implementado sin verificar (y por qué)** · **descartado (y por qué)**
