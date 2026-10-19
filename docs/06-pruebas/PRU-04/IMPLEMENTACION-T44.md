# T44 — Tarifas con vigencia y cambios de plan

Codex CAB · 18/10/2026 · implementación local entregada para verificar, sin despliegue.

## Decisiones de Carlos

DU-40: el aumento rige desde el mes siguiente para quienes ya estaban. Las altas nuevas
usan la tarifa vigente el día de su fecha real de ingreso, con su porcentaje. Un aumento
no modifica cuotas ya generadas, incluidas las cobradas adelantadas.

Carlos permite varios cambios en un mes: $40.000 → $45.000 el 20/10 → $50.000 el 25/10.
Los existentes pagan octubre a $40.000 y noviembre a $50.000. Un alta del 22/10 usa
$45.000 en octubre; una del 26/10, $50.000. Ambos usan $50.000 desde noviembre.

La consulta sobre cambios de frecuencia quedó precisada por las respuestas posteriores:

- **Bajar:** siempre desde el primer día del mes siguiente, con o sin asistencias.
- **Subir con cuota pendiente:** se aplica ahora y ajusta la cuota del mes.
- **Subir con pago parcial:** «Se aplica ahora y ajusta el saldo pendiente». Se conserva lo pagado.
- **Subir con el mes saldado:** desde el primer día del mes siguiente.

Esta precisión reemplaza la respuesta intermedia «siempre desde el mes siguiente».
La excepción por cambio inmediato de plan no autoriza a recalcular cuotas por un aumento
simple del catálogo. El alta/reingreso conserva su porcentaje al ajustar una subida.

## Implementación

`grupo_plan_precios` guarda cada tarifa con su fecha; el precio inicial queda vigente desde
siempre, también para los planes preexistentes. `grupo_planes.precio_mensual` conserva el
catálogo editable. El guardado del plan y su historial es transaccional.

`GrupoPlan::precioParaPeriodo` resuelve la tarifa por mes y fecha de alta original. Lo usan
alta y vista previa, reingreso, meses pasados T58, Revisión «Continúa», generador, Cobrar y
sus descuentos/adelantados, catálogo Excel, formularios y servicio legado. Las deudas de
primera carga siguen valiendo el saldo declarado, sin recalcularse por el plan.

`CambioPlanService` comparte fecha, precio anunciado y ajuste entre Cobrar y la edición
de ficha. La relación del plan vigente respeta fechas; el programado conserva su selección
para que editar otros datos no lo cambie. Las cuotas históricas y las futuras ya generadas
no se recalculan al programar el cambio.

`GrupoWebController::update` guarda mediante el modelo para registrar la tarifa. El formulario
de planes informa desde cuándo rige para altas y existentes; usa los componentes y botones
existentes, sin JavaScript incrustado ni cambios de CSS. En T58 solo cambian las consultas
de precio; la ficha conserva su bloque y su flujo. La API continúa deshabilitada.

También se adaptó DemoSeeder: limpia su historial al recrear datos y consulta la función
común para sus importes. No se afirma haber ejecutado ese seeder legado.

## Evidencia verificada

- 19 casos nuevos, incluidos en la selección final de 24 pruebas / 251 aserciones, 45.90 s,
  exclusivamente `wings_testing_codex`; incluye los cinco casos existentes de formato T30.
- Regresión intermedia: 89 aprobadas / 455 aserciones; incluye alta, reingreso, T58,
  generador, cobro adelantado y las matrices de cambio de plan.
- Migración sobre un plan preexistente: 1 aprobada / 8 aserciones; rollback y nueva
  aplicación conservan $40.000,50 como tarifa inicial, sin fecha inferior.
  [Resultado](evidencia-t44/migracion.json).
- 11 respuestas de rutas reales de Laravel; 33 medidas a 1366/360/375, sin desbordes.
  El login comprueba el método de marco móvil y todos cargan el CSS real de Wings.
- Chrome: al seleccionar una subida de $40.000 a $60.000 con $10.000 pagados, el campo,
  el tope y el total cambian de $30.000 a $50.000 y Cobrar se habilita. Tres anchos.
  [Interacción](evidencia-t44/interaccion.json). No confirma el pago desde el HTML guardado;
  la prueba de servidor registra esos $50.000 y verifica la cuota en $60.000 pagados.
- Suite definitiva: **878 aprobadas, 3 omitidas, 0 fallidas; 7792 aserciones, 1102.93 s**, salida 0 en `wings_testing_codex`. [Salida completa](evidencia-t44/suite-final.txt).
- Build correcto; sintaxis de las 22 fuentes correcta y vistas compiladas/limpiadas correctamente.
- Guardianes finales de documentación y tablero: 9 aprobadas / 43 aserciones, 0.37 s.
- Integridad: 22 fuentes idénticas a la suite, 11 respuestas reales, 58 capturas y enlaces comprobados.

[Capturas de planes](evidencia-t44/GALERIA.html). El resto de pantallas afectadas queda
guardado en la evidencia interna; la galería principal muestra solo Crear/Editar planes.

## Pruebas existentes adaptadas

- `CambioPlanCobroTest`: bajar sin asistencias ahora conserva el plan/cuota del mes y
  empieza al siguiente. El doble del servicio simula únicamente el fallo del cobro;
  las consultas reales de fecha/precio siguen ejecutándose para verificar rollback.
- `CambioPlanConDescuentoMatrizTest`: bajar sin asistencias ya no reduce el mes de alta
  a $14.000; conserva $28.000 y el cambio empieza al mes siguiente. Las subidas con saldo
  continúan siendo inmediatas y mantienen el porcentaje.
- La primera suite completa detectó el fixture T30 de presentación: creaba el plan a
  $48.000,50 y lo cambiaba en octubre para comprobar otros importes sobre alumnos de agosto.
  Con T44 eso protege la tarifa anterior. El fixture `ImportesT24` recibe ahora un precio inicial opcional (mismo valor por defecto),
  e `ImportesT30` lo pasa al crear, conservando todas sus comprobaciones de centavos y millones.

## Fuentes, límites y reproducción

Las fuentes de aplicación y pruebas se registran con huellas CRLF→LF al iniciar cada suite.
Las salidas de consola eliminan espacios finales; las respuestas HTML conservan su espaciado
y tienen una excepción local de whitespace para mantener la evidencia literal.
El primer barrido añadió después su último ajuste al snapshot de precios de DemoSeeder;
no se altera código para provocar fallas. Se conserva el resultado inicial y se repitió
la suite completa tras ajustar el fixture T30.

Durante la corrida definitiva comenzaron cambios de Gemini en T34/T67, fuera de estas 22 fuentes.
La suite T44 descubre 881 métodos al comenzar; los nuevos casos paralelos no se incorporan a
esta entrega ni se certifican aquí. Las 22 fuentes propias se contrastan con sus huellas finales.

Solo datos sintéticos y `wings_testing_codex`, con almacenamiento exclusivo `storage/app/t44-runtime`.
Los instrumentos quedan fuera de la suite permanente. `CapturaT44Test.php` se ejecuta con
`--filter=test_capturas_reales_t44`; `MigracionT44Test.php` recrea únicamente esa base descartable.
No ejecutar dos corridas sobre ella. `servidor.php`, `capturar.mjs` e `interaccion.mjs` usan
respuestas reales y marcos de ancho exacto; no fabrican pantallas de Wings.

[Planes y precios V5](../../02-contratos/Wings-contrato-alumno-grupo-deporte-deuda-v5.md) ·
[Cuotas y cambios de plan V2](../../02-contratos/Wings-contrato-cuotas-deudas-pagos-V2.md).

Entregado a Claude para verificar reglas y código; T44 permanece a_verificar.
Lo visual de T44 lo aprueba Carlos; no se hereda la aprobación de T75. Sin publicación ni despliegue.
