# ENT-01 — Inscripción y primera carga manual

Decisión de Carlos, 22/09/2026. Definición documentada; implementación pendiente.

## MUY IMPORTANTE: fecha real de ingreso

Los usuarios cargarán los alumnos manualmente para familiarizarse con Wings.
La fecha real de ingreso al club determina si se genera inscripción, comparada
con una fecha fija de inicio del sistema:

- Ingreso anterior al inicio: no generar deuda de inscripción.
- Ingreso desde esa fecha, inclusive: generar la inscripción una sola vez.
- No usar fecha de creación del registro como sustituto del ingreso real.
- No usar un modo de carga inicial que pueda quedar activado.
- No preguntar permanentemente si es alumno nuevo o antiguo.

El formulario propone hoy como fecha de ingreso; para alumnos antiguos el usuario
debe cargar su fecha real. Esto debe explicarse claramente en los manuales.
No inventar la fecha de corte ni fechas antiguas: Carlos debe confirmar el corte
concreto. Si se desconoce el ingreso de un alumno, falta definir cómo registrarlo.

## Importe y cobro acordados

- Valor obligatorio, no nulo, en Configuración: inicialmente $5.000.
- Inscripción por única vez; la deuda se crea con el alta elegible.
- Se paga junto con la primera cuota, identificable como inscripción.
- No generar automáticamente inscripción a todos los alumnos ya existentes.

## Propuesta de implementación (no código implementado)

1. Revisar el campo de ingreso existente y el flujo de alta antes de elegir tablas
   o cambios. Registrar una fecha fija de inicio una sola vez, sin fecha móvil ni
   interruptor operativo. No permitir cambios casuales del corte que alteren reglas.
2. Al guardar alumno, comparar ingreso con corte en servidor. Crear alumno y deuda
   en una misma transacción cuando corresponda; impedir duplicados por reintento.
3. Guardar en la deuda el importe de inscripción vigente al alta; futuros cambios
   de configuración no deben cambiar deudas ya creadas.
4. Mostrar antes de guardar si se generará inscripción y su importe. Reutilizar el
   diseño Wings; no modificar vistas sin autorización concreta y lecturas obligatorias.
5. Integrar con primera cuota y recibo sin duplicar ingresos ni imponer reglas de
   pago parcial aún no definidas. Precisar rubro/subrubro y tratamiento de parciales
   antes de implementar esos caminos.
6. Corregir después la fecha de ingreso no debe crear/borrar deudas o pagos a
   escondidas: definir el procedimiento de corrección antes de implementar ese caso.
7. Verificar ingreso antes/en/después del corte, carga tardía de alumno antiguo,
   primer cobro, configuración sin valor, reintento y fallo sin alta parcial.

## ENT-10 — Manuales de primera carga (PENDIENTE)

Preparar guía breve para usuarios que cargan y guía de preparación del sistema.
La preparación fija el corte; el usuario no debe mantener modos temporales.
Explicar en lugar destacado:

- Fecha de ingreso al club versus día en que se carga al sistema.
- Alumno antiguo cargado hoy: ingreso real anterior al corte, sin inscripción nueva.
- Inscripción nueva desde el corte: deuda inicial al valor configurado.
- Ejemplos antes/en/después del corte y qué revisar antes de guardar.
- Qué hacer si falta la fecha real (no inventarla; procedimiento pendiente de definir).
- Diferencia entre inscripción nueva y deuda inicial del padrón (ENT-09).

Validar los manuales contra las pantallas realmente implementadas y probar su
comprensión con un usuario no técnico. No publicar capturas o pasos inventados.
Los manuales no se consideran terminados por existir esta orden de trabajo.
