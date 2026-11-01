# Día 51 — domingo 29/11/2026

Codex CAB, ejecución por navegador en test.gestionar-te.com.ar, datos ficticios. Reloj 20:00; sin rutina. Todas las escrituras de esta jornada se hicieron por pantalla. Tanda 5 sigue en curso.

| # | Caso | Resultado | Lo observado | Evidencia |
|---|---|---|---|---|
| 51.1 | Hockey y Nora, T95 | no probado | «Deporte creado correctamente.», «Ya existe un deporte con ese nombre.» para « hockey ». Nora cargada con todos los datos del guion y sin tarifa: se pulsó Guardar, el formulario siguió abierto, pero no se capturó ni se acredita el rechazo «Cargá el valor por hora.». Se completó $14.000 y «Profesor creado correctamente.». Nora aparece una vez con $14.000 y seis clases futuras. Alta positiva comprobada; rechazo sin tarifa sin acreditar. | [Hockey](evidencia-dias-48-57/gw-tanda5-51.1a-hockey-creado.jpg), [repetido](evidencia-dias-48-57/gw-tanda5-51.1b-hockey-repetido-rechazado.jpg), [sin tarifa](evidencia-dias-48-57/gw-tanda5-51.1c-nora-sin-valor-hora.jpg), [atributos del campo](evidencia-dias-48-57/gw-tanda5-51.1c-validacion-tarifa.json), [creada](evidencia-dias-48-57/gw-tanda5-51.1d-nora-creada-14000.jpg) |
| 51.1b | Grupo y serie de seis clases | cumple | Grupo Hockey Principiantes 1x $30.000/2x $42.000: «Grupo creado correctamente.». Sin profesora, rechaza: «Seleccioná al menos una profesora activa del deporte de la clase.». Con Nora, filtro Hockey muestra seis clases: 3,10,17/12 16–17 y 5,12,19/12 12–13, todas Programada. | [Precios](evidencia-dias-48-57/gw-tanda5-51.1b1-hockey-grupo-antes.jpg), [grupo](evidencia-dias-48-57/gw-tanda5-51.1b2-hockey-grupo-creado.jpg), [rechazo](evidencia-dias-48-57/gw-tanda5-51.1b3-serie-sin-profesora.jpg), [seis clases](evidencia-dias-48-57/gw-tanda5-51.1b4-seis-clases-hockey.jpg) |
| 51.2 | Cuenta Torneo y validaciones | no cumple | TORNEO: «La abreviatura no puede tener más de 5 caracteres.». Saldo -1 bloqueado por validez del campo: «Ingresá un importe válido, con hasta dos decimales.», distinto del aviso del guion sobre saldo negativo. Nombre «mercado pago»: «Ya existe un tipo de caja con ese nombre.». Correcta: «Tipo de caja creado correctamente.», TOR $50.000; disponible $7.794.920,75 antes del sonido, suba exacta de $50.000. | [Larga](evidencia-dias-48-57/gw-tanda5-51.2a-abreviatura-larga.jpg), [rechazo](evidencia-dias-48-57/gw-tanda5-51.2b-abreviatura-rechazada.jpg), [negativo](evidencia-dias-48-57/gw-tanda5-51.2c-saldo-negativo-rechazado.jpg), [validación](evidencia-dias-48-57/gw-tanda5-51.2c-validacion-negativo.json), [repetido](evidencia-dias-48-57/gw-tanda5-51.2d-nombre-medio-repetido.jpg), [creada](evidencia-dias-48-57/gw-tanda5-51.2e-caja-torneo-creada.jpg), [disponible](evidencia-dias-48-57/gw-tanda5-51.2f-disponible-mas-50000.jpg) |
| 51.3 | Reparaciones menores solo dueño | cumple | En Editar Subrubro, Permitido para → Solo Admin. «Subrubro actualizado correctamente.». La oferta a Sandra se comprueba en 52.6, no se hereda aquí. | [Permiso guardado](evidencia-dias-48-57/gw-tanda5-51.3-reparaciones-solo-admin.jpg) |
| 51.4 | Cuota Mensual reservada | cumple | Su fila no tiene links ni botones para editar/desactivar. No se forzó el rechazo técnico. | [Sin acciones](evidencia-dias-48-57/gw-tanda5-51.4-cuota-mensual-reservada.jpg) |
| 51.5 | Vanina Directora y propia cuenta | cumple | Al apagar: «Profesora inactiva. El usuario vinculado quedó desactivado y sus sesiones cerradas.». Al prender: «Profesora activa. Su usuario conserva el estado anterior; activalo en Usuarios si corresponde.». Cuenta propia: «Tu cuenta», sin interruptor para apagarla. No se reactivó aparte el usuario de Vanina. | [Inactiva](evidencia-dias-48-57/gw-tanda5-51.5a-vanina-inactiva.jpg), [reactivada](evidencia-dias-48-57/gw-tanda5-51.5b-vanina-reactivada.jpg), [propia cuenta](evidencia-dias-48-57/gw-tanda5-51.5c-propia-cuenta-protegida.jpg) |
| 51.6 | Segunda dueña y corrección ajena | cumple | «Usuario creado correctamente.», Vanina Socia Administrador. Admin Prueba registra Eventos/Alquileres $15.000 BGA, «Sonido del torneo». Vanina ingresa por separado y cambia a $12.000 con «El presupuesto final fue $12.000». «Movimiento actualizado. El historial se conserva.». Listado conserva Registró Admin Prueba; detalle del historial muestra edición Vanina Socia, antes $-15,000.00/después $-12,000.00 y motivo. Ambos autores están acreditados en sus respectivas pantallas, no juntos en una misma tarjeta. | [Usuario](evidencia-dias-48-57/gw-tanda5-51.6a-vanina-socia-creada.jpg), [gasto](evidencia-dias-48-57/gw-tanda5-51.6b-sonido-15000-admin.jpg), [corrección](evidencia-dias-48-57/gw-tanda5-51.6c-socia-corrige-12000.jpg), [historial](evidencia-dias-48-57/gw-tanda5-51.6d-historial-dos-autores.jpg), [autor original](evidencia-dias-48-57/gw-tanda5-51.6e-listado-con-autor-original.jpg) |
| 51.7 | T97: transferencia de octubre | cumple | BGA→Nación $50.000, 20/10. Primer envío pide «Atención: fecha de un mes anterior» y «Confirmar». «Transferencia #7 registrada: salida y entrada guardadas.». Se localizó por filtro Día 20/10 y anuló con «Ya estaba cargada: me confundí». «Transferencia #7 anulada. Los originales y los dos asientos en contra se conservan.». Octubre sigue $3.417.850,75/$341.350. | [Confirmación](evidencia-dias-48-57/gw-tanda5-51.7a-transferencia-octubre-confirmacion.jpg), [registrada](evidencia-dias-48-57/gw-tanda5-51.7b-transferencia-octubre-registrada.jpg), [anulada](evidencia-dias-48-57/gw-tanda5-51.7c-transferencia-octubre-anulada.jpg), [octubre](evidencia-dias-48-57/gw-tanda5-51.7d-octubre-intacto.jpg) |
| 51.8 | Acciones sobre orígenes protegidos | cumple | Caja validada del 28/11: solo Ver. Transferencia #5 Anulada: solo Ver. Cobro del dueño Rossi, Morena 23/11: el vigente ofrece Anular, nunca Editar/Eliminar; originales anulados solo Ver. Liquidaciones pagadas #4/#5/#6 del 3/11 y #2/#3 del 2/11: solo Ver. Se recorrió la paginación y luego el filtro Día 23/11; no se forzaron pedidos. | [Caja/anulada](evidencia-dias-48-57/gw-tanda5-51.8-pagina-1.jpg), [pagadas del 3/11](evidencia-dias-48-57/gw-tanda5-51.8-real-pagina-9.jpg), [pagadas del 2/11](evidencia-dias-48-57/gw-tanda5-51.8-real-pagina-10.jpg), [cobros propios](evidencia-dias-48-57/gw-tanda5-51.8-cobros-dueno-23nov.jpg), [acciones leídas](evidencia-dias-48-57/gw-tanda5-51.8-acciones-observadas.json) |

## Cierre por pantalla

| Concepto | Observado y esperado | Resultado |
|---|---:|---|
| Deuda | $207.910 | cumple |
| Activos, Al día / En plazo / Morosos / Deudores | 116, 109 / 2 / 5 / 0 | cumple |
| Ingresos noviembre | $4.399.900 | cumple |
| Egresos noviembre | $2.879.280 | cumple |
| Resultado noviembre | $1.520.620 | cumple |
| Disponible | $7.782.920,75 | cumple |
| Efectivo | $1.159.500 | cumple |
| MP | $3.951.270,75 | cumple |
| Galicia | $2.564.150 | cumple |
| Nación | $58.000 | cumple |
| Caja Torneo | $50.000 | cumple |
| Sin asistencia / cajas por validar / Revisión | 0 / 0 / 0 | cumple |

[Inicio y medios](evidencia-dias-48-57/gw-tanda5-51.9-inicio-cierre.jpg) · [Cobranza](evidencia-dias-48-57/gw-tanda5-51.9-cobranza-cierre.jpg) · [Reportes](evidencia-dias-48-57/gw-tanda5-51.9-reportes-cierre.jpg).

## Límites y continuidad

- Rechazo sin tarifa de Nora no acreditado; no se lo declara verde por haber creado después a $14.000. Saldo negativo con aviso distinto. No se forzaron rutas técnicas ni datos por consola.
- El detalle del sonido muestra $-12,000.00, mientras Cashflow muestra −$12,000.00: diferencia visual registrada, sin alterar el importe.
- Las capturas iniciales de paginación 51.8-pagina-2 y los primeros registros del JSON tienen etiqueta de herramienta desfasada; se conservan sin usarlas para acreditar una página concreta. Las referencias de esta tabla usan DOM/paginación reales y recaptura específica del cobro propio.
- Hockey, Nora, seis clases, Caja Torneo, socia y cambio de subrubro YA guardados: no repetir. Sigue día52 a 30/11 09:00, rutina de mañana; Hernán y Lucía NO toman las listas del 30/11. Noche cierra SIN validar. Clara sigue con saldo futuro $270.000 sin condonar (49.2).
- ADMIN en Edge6 pestaña8394474, Reportes. Vanina Socia en Chrome3 pestaña1320328782, Cashflow. T14 en_curso; faltan días52–57, informe final y revisión independiente.
