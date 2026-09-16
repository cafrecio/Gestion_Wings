# Wings — Contrato de clases particulares V1

**Fecha:** 13/09/2026. **Origen:** entrevista y decisiones expresas de Carlos.
**Estado:** definición funcional documentada; implementación PENDIENTE (POS-06).
Este documento no certifica comportamiento actual, pruebas ni despliegue.
El pedido de este turno es documental; no ejecutar cambios funcionales por leerlo.

## 1. Alcance y precedencia

Incluye agenda individual, participantes, asistencia, deudas, cobros, saldo a favor,
recibos, pago del profesor, rentabilidad, permisos y avisos de particulares.
Reportes se diseña por separado; la entrevista se retoma después de este contrato.

Complementa [Clases y Asistencias](Wings-Contrato-Clases-Asistencias-V1.md),
[Liquidaciones](LIQUIDACIONES_CONTRATO_V2.md), [Permisos](PERMISOS-ROLES.md),
[Recibos V2](Wings-Contrato-Recibos-PDF-V2.md) y
[Cuotas, deudas y pagos](Wings-contrato-cuotas-deudas-pagos-V1.md).
Las excepciones siguientes son decisiones nuevas, pendientes de implementación:

- Particular: un solo profesor, pago por duración o comisión diferenciada,
  participación propuesta por profesor y cancelación con decisión económica.
- Para todas las clases: el cierre de liquidación impide modificar asistencia a
  cualquier perfil; solo ADMIN cancela una cerrada no pagada para revisarla (§8).
- La regla anterior de cierre irreversible se sustituye en ese alcance concreto.
  No habilita modificar ni cancelar una liquidación pagada.
- El cálculo por duración aquí acordado corresponde a particulares; no resuelve
  el cálculo pendiente de las clases ordinarias.

## 2. Agenda y participantes

- Las particulares se crean clase por clase, sin programación recurrente automática.
- Una clase tiene deporte, fecha, horario, duración, exactamente un profesor y una
  o más alumnas registradas, activas e inscriptas en ese deporte. Se mantienen las
  condiciones de elegibilidad de las clases ordinarias. Una inactiva debe activarse antes.
- Se impiden superposiciones del profesor y de las alumnas al crear, reprogramar
  o modificar asignaciones. La propia clase no constituye un conflicto consigo misma;
  se conservan los bordes contiguos permitidos por el contrato general.
- ADMIN crea; una configuración habilita al OPERATIVO a crear como secretaría.
  Delegar creación incluye definir el precio de la clase.
- Solo ADMIN cambia fecha, horario o duración. Reprogramar conserva deuda y pago.
- ADMIN y OPERATIVO pueden cambiar profesor y agregar alumnas. El profesor asignado
  puede proponer alumnas al tomar asistencia, con confirmación administrativa (§6).
- Estos permisos no permiten alterar información protegida por liquidación cerrada.

## 3. Precio y generación de deuda

- Configuración ofrece un valor base. Al crear se guarda el precio acordado,
  modificable por quien tiene permiso de creación.
- El precio es fijo **por alumna y por clase**, y debe indicarse expresamente.
  No se multiplica por duración: una clase de $10.000 cuesta $10.000 por alumna
  aunque dure una hora y media.
- El club cobra. Cada participante confirmada genera su deuda al agendarse;
  la alumna puede pagar antes o después de la clase mediante Cobranzas.
- Los ingresos se identifican en la cuenta reservada **Cuotas → Clases particulares**.
- Cualquier pago, incluso parcial, congela el precio. Solo ADMIN puede modificarlo
  excepcionalmente con advertencia visible; se conserva el pago y se registra la
  diferencia como deuda adicional si aumenta o saldo a favor si disminuye.
- No se debe confundir el saldo pendiente de un pago parcial con dinero cobrado:
  la conciliación de precio, deuda y crédito debe conservar el dinero real recibido.

## 4. Cancelaciones de clase o de participación

Cancelar la participación de una alumna no cancela necesariamente toda la clase.
Se registra motivo, responsable y resultado económico de la decisión.

- ADMIN decide mantener o cancelar la deuda de cada alumna y, por separado,
  cuánto corresponde al profesor; puede dejar su importe en cero.
- OPERATIVO puede cancelar una participación sin cargo dentro del plazo configurado:
  inicialmente **24 horas de anticipación al inicio**.
- Fuera del plazo, OPERATIVO registra la cancelación manteniendo el cobro. Solo
  ADMIN decide excepciones. No se impone una regla automática por clima o común acuerdo.
- Si se mantiene el cobro, lo abonado sigue aplicado a esa clase; no se libera
  como saldo a favor por el solo hecho de cancelar la asistencia.
- Si se cancela la deuda y ya había dinero abonado, lo liberado queda a favor
  de la alumna, vinculado al recibo original. No se registra un segundo ingreso.
- Cancelar una alumna no recalcula automáticamente el pago del profesor,
  ni por hora ni por comisión: ADMIN decide ese ajuste.
- Si OPERATIVO cancela, se avisa al dueño: clase, alumna, motivo y resultado
  (deuda cancelada, pago a favor, deuda pendiente o pago mantenido en la clase).

## 5. Saldo a favor y recibos

- El recibo original acredita dinero ya recibido. Cancelar la clase no equivale
  a anular ese cobro: se libera el importe correspondiente para otra aplicación.
- El saldo se muestra en el estado de cuenta de la alumna y al cobrar.
- ADMIN y OPERATIVO eligen utilizarlo **solo desde Cobranzas**, al cobrar cuotas
  o particulares. No se asigna previamente desde el estado de cuenta ni automáticamente.
- Puede cubrir cualquier deuda de la misma alumna, incluidas cuotas mensuales.
- Se aplica hasta cubrir la deuda; si sobra, solo el remanente sigue disponible.
- El nuevo recibo identifica el importe aplicado y el recibo de origen de la clase
  cancelada, separado del dinero nuevo recibido. El original conserva su trazabilidad.
- Si el saldo cubre todo, se confirma el cobro y se emite un nuevo recibo con
  saldo aplicado y **dinero recibido: $0**.
- El saldo se consume exactamente una vez: nunca queda disponible lo ya utilizado,
  ni puede consumirse dos veces mediante reintento o dos cobros simultáneos.
- Aplicación, consumo, deuda y comprobante deben quedar consistentes; un fallo
  no puede consumir saldo sin cancelar la parte correspondiente de la deuda.

**Ejemplo acordado:** el 13/09 se paga una clase del 15/09 y se emite recibo.
El 15/09 se cancela sin cargo: queda crédito vinculado a ese recibo. El 17/09
puede usarse para otra particular, o el 01/10 para la cuota mensual. El nuevo
recibo referencia el anterior y solo queda disponible el importe no utilizado.
Estas son alternativas de uso del mismo saldo, no dos créditos distintos.

## 6. Asistencia e incorporaciones por el profesor

- La toma de asistencia confirma la realización, como en las demás clases.
  Pueden tomarla ADMIN, OPERATIVO y el profesor asignado a esa particular.
- El profesor puede agregar una alumna elegible al tomar asistencia. Queda
  pendiente de confirmación de ADMIN u OPERATIVO; solo al confirmar se genera deuda.
  La clase queda marcada para que ambos perfiles revisen la incorporación.
- El profesor puede corregir su asistencia hasta dos horas desde la primera carga.
  Cada corrección conserva ese inicio: el plazo no vuelve a empezar.
- Pasado el plazo debe recurrir a ADMIN. El cierre de liquidación bloquea
  correcciones aun si no transcurrieron las dos horas (§8).
- Si el profesor modifica una incorporación ya confirmada, debe indicar motivo.
  Se avisa a ADMIN, se conserva la deuda y el cambio queda pendiente de revisión.
  **Solo ADMIN resuelve esta revisión**, aunque OPERATIVO haya confirmado el alta.

## 7. Importe del profesor

- Modalidad por hora: valor horario del profesor proporcional a duración,
  independiente de la cantidad de alumnas.
- Modalidad por comisión: porcentaje específico de particulares, configurable
  en el profesor y que puede ser 100%, sobre el total acordado de la clase.
  Dos alumnas a $10.000 y 40% corresponden inicialmente a $8.000.
- En la liquidación se separan comisiones de cuotas y comisiones de particulares.
- La particular se liquida cuando fue dictada, aunque las alumnas no hayan pagado:
  la cobranza es riesgo del club, no del profesor.
- Si el profesor fue y estuvo disponible, corresponde pagarle aunque ninguna
  alumna asista. ADMIN puede ajustar el importe, dejarlo en cero o cancelar la clase.
- ADMIN puede fijar un importe manual antes del cierre de liquidación.
- Cambiar profesor recalcula según modalidad y valores del nuevo profesor,
  **salvo que ADMIN haya fijado un importe manual: ese importe se conserva**.
- Cancelar participantes no reduce automáticamente el importe (§4).
- OPERATIVO **nunca ve lo que cobra un profesor**: importes, valor horario,
  porcentajes, ajustes, rentabilidad ni señales que permitan deducirlos.

## 8. Liquidaciones y bloqueo general de asistencias

Esta decisión afecta clases ordinarias y particulares y todos los perfiles.

| Situación | Regla acordada |
|---|---|
| Liquidación abierta | Correcciones sujetas a permisos; profesor además limitado a dos horas |
| Cerrada no pagada | Nadie modifica asistencia; solo ADMIN puede cancelar primero la liquidación para revisarla |
| Pagada | No se cancela ni modifica; corrección económica como ajuste en la próxima liquidación |

La cancelación administrativa de una cerrada no pagada debe conservar la historia;
no se interpreta como permiso de borrar datos ni de reabrir una pagada.
El ajuste posterior se vincula a la clase original, conserva intacta la liquidación
pagada y se incorpora al recálculo de rentabilidad de la clase.

## 9. Rentabilidad y avisos

- Se evalúa lo que queda para el club del precio total de la clase después del
  importe del profesor. No es rentabilidad contable: el costo del espacio no se mide.
- Mínimo inicial: **30%**, modificable en Configuración. Menos del mínimo genera aviso.
- OPERATIVO habilitado puede cargar la clase igualmente. No ve margen, importe
  del profesor, advertencia económica ni señal de baja rentabilidad.
- Se avisa al dueño por correo y Telegram, con destinatarios configurables;
  el destinatario previsto inicialmente es Vanina. No guardar credenciales en este contrato.
- El listado marca la clase con baja rentabilidad **solo para ADMIN**.
- Cancelaciones, cambios y ajustes recalculan rentabilidad. Si queda por debajo
  del mínimo se vuelve a avisar, incluso cuando ya existió una advertencia anterior.
- Los avisos operativos de incorporaciones pendientes se distinguen de los
  económicos; nunca revelan remuneraciones a OPERATIVO.

## 10. Configuración acordada

- Habilitación de creación de particulares por OPERATIVO.
- Valor base por alumna para particulares.
- Plazo para cancelación sin cargo: inicialmente 24 horas.
- Margen mínimo de la clase: inicialmente 30%.
- Correo y destinatario de Telegram de los avisos al dueño.
- Porcentaje de particulares en el profesor que trabaja por comisión.

## 11. Criterios de aceptación para la implementación pendiente

1. Agenda individual, elegibilidad, un profesor y rechazos sin cambios parciales
   ante superposiciones, incluidos cambios de profesor o participantes.
2. Deudas por alumna, reprogramación sin duplicación, congelamiento ante pago
   parcial/completo y excepción de precio exclusiva de ADMIN con advertencia.
3. Cancelación individual con plazo configurable, excepciones ADMIN, motivo y
   avisos; pago del profesor independiente de cancelación de participantes.
4. Crédito trazable al cobro original; uso parcial/total desde Cobranzas; recibo
   con origen y dinero nuevo separado, incluido cero; sin duplicación de ingresos.
5. Dos cobros concurrentes y reintentos no consumen dos veces el mismo saldo.
   Verificar importes reales en base descartable y rollback ante fallo.
6. Incorporación propuesta sin deuda hasta confirmación; corrección posterior
   con motivo, aviso y resolución exclusiva de ADMIN, manteniendo la deuda mientras tanto.
7. Dos horas desde primera carga sin reinicio; cierre bloquea todos los perfiles;
   cancelación de cerrada no pagada solo ADMIN; pagada intacta y ajuste posterior.
8. Hora proporcional, comisión diferenciada, pago pese a falta de cobro, importe
   manual conservado al cambiar profesor y recálculo de rentabilidad con ajustes.
9. OPERATIVO no obtiene remuneraciones ni rentabilidad por pantalla, recibo,
   respuesta de datos o aviso. ADMIN sí dispone del detalle y señal correspondiente.
10. Avisos por ambos canales, destinatarios configurables y nueva alerta al cambiar
    una clase que queda bajo el umbral. Evidencia de recepción antes de darlo por operativo.

## 12. Detalles que no deben inventarse al implementar

La entrevista cierra las reglas anteriores; no define todos los casos de borde.
Antes de implementar el camino afectado, precisar tratamiento de precio cero
(margen no divisible), reversión de un cobro que consumió crédito, cambios de
duración con importe manual, ni cómo confirmar presencia/disponibilidad del
profesor si todas las alumnas faltan. Conservar por separado la corrección de
asistencia y la decisión económica. No cambiar historia pagada para resolverlos.

El diseño técnico debe definir trazabilidad de cancelación de liquidación y de
créditos, consistencia transaccional, entrega/reintento de avisos y protección de
datos. Este contrato no elige tablas, rutas ni estados nuevos por anticipado.
