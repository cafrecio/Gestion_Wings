# COB-02 verificada / COB-07 parcial, 10/09/2026

Codex CyE. Chrome real, viewport 1440x1000, copias aisladas por git archive.
Sin checkout, merge ni cambios de codigo. Bases sinteticas wings_cob02_20260910_*;
no se uso gestion_wings.

## Resultados

- Antes de COB-02 (02a5b5b): selector no viaja; elegido plan 60.000,
  persiste plan 40.000. Con deuda existente el cobro queda limitado a 40.000.
- Main f5c4b66: selector viaja y persiste plan 60.000; deuda/pago 60.000,
  pero total anunciado 40.000.
- cob-saldo 5be4970: total anunciado 60.000, deuda original/pagada 60.000,
  plan nuevo desde 10/09. Caso basico COB-07 correcto.
- Bajada con asistencia: anuncia/cobra 20.000, deuda original 40.000,
  pagado 20.000, PENDIENTE. Plan bajo desde 01/10, anterior hasta 30/09.
  Coincide con el limite conocido autorizado; no se reporta como defecto nuevo.
- Disposicion visual COB-02: capturas anteriores/posteriores al movimiento del
  form identicas (SHA256 iguales), tanto inicial como con plan seleccionado.
  Diez cajas DOM con mismas posiciones y dimensiones. En COB-07 conservan
  esas posiciones; cambia el numero del total, como se pidio.

## Freno: primer pago parcial con descuento

Caso sintetico 4, alta 08/09 y regla unica 70% para ese dia (fixture deliberada,
no catalogo del club). Deuda septiembre 40.000, sin pagos. Elegir plan 60.000 y
escribir parcial 10.000. Todo mediante controles del navegador.

Request real: nuevo_plan_id=3, periodo=2026-09, montos_cuota[2026-09]=10.000.
Pantalla anuncia 7.000; registra pago base 10.000, porcentaje 70%, final 7.000.
La deuda queda ORIGINAL 7.000, PAGADO 7.000, PAGADA: no conserva saldo.

Codigo leido: registrarPagoCuotaOperativo toma el item parcial ya descontado
como montoConDescuento y lo pasa a ajustarDeudaConDescuento para reemplazar
monto_original. No se limito a descontar el pago: reduce toda la deuda al parcial.

Si el 70% aplica al precio del plan elegido (60.000), cuota 42.000 menos pago
7.000 dejaria 35.000. Ese saldo no existe en la fila obtenida.
Freno AGENTS 6b: pedir decision sobre correccion o separacion del hallazgo,
sin improvisar logica ni modificar vistas.

Carlos separo el hallazgo: Claude corrige descuento y total, Codex termina COB-02.

## Continuacion autorizada: rollback COB-02

En 5be4970, caso 3 sin pagos, plan 40.000. Se renombro temporalmente el
subrubro sintetico Cuota Mensual para provocar excepcion dentro del servicio,
despues del cambio de plan y actualizacion de deuda del controlador.
Desde Chrome se eligio plan 60.000 y se envio el cobro. Respuesta 302 de vuelta
a cobrar. La fila original conserva plan_id=2, activo=1, desde 01/01 y sin
fecha_hasta; no hay segundo plan. Deuda 40.000, pagado 0, PENDIENTE.
Consultas de pagos y movimientos del alumno: ninguna fila. Catalogo restaurado.

COB-02 verificada: subida, bajada diferida, rollback y maquetacion.
Suite aislada 5be4970: 140 pruebas, 753 aserciones, verde (40,24 s).
Vistas compilan y se limpia cache. Diff de vistas/CSS del arbol compartido vacio.
COB-07 solo tiene verificado su caso basico; combinaciones restantes quedan
separadas de COB-02 y el descuento a cargo de Claude. Sin cambios funcionales.
Capturas y scripts locales ignorados: storage/app/cob02-20260910.
No se acredita publicacion GitHub ni despliegue.
