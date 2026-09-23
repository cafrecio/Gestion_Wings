# A2/B2 — cuota al alta y estado basado en deuda

23/09/2026 · Codex CAB · **Implementada localmente, pendiente de verificación independiente.**

## Cambio

- `AlumnoWebController::store` crea la cuota del mes corriente dentro de la misma
  transacción que alumno, plan e inscripción. Usa el precio del plan elegido y la regla
  configurada para el día de `fecha_alta`. No recorre alumnos existentes ni crea meses anteriores.
- `deuda_cuotas.porcentaje_alta` conserva el porcentaje usado; `monto_original` conserva
  el importe. La migración solo agrega una columna nullable: las deudas históricas no
  reciben una cuota ni una marca retroactiva.
- `PagoCuotaService::calcularReglaPrimerPago` reconoce esa marca y no recalcula ni ajusta
  la deuda del alta. Conserva el porcentaje como metadato del primer cobro. El importe
  cobrado se aplica al saldo guardado; también conserva su valor tras parcial/anulación/recobro.
- La vista previa de Caja consulta ese mismo método; no se modificó ningún Blade, CSS
  ni JavaScript. Las deudas históricas conservan el camino anterior y el reingreso
  posterior conserva su regla. El cambio explícito de plan conserva el contrato vigente.
- `CobranzaEstadoService` elimina todas las consultas de pagos para decidir el estado:
  individual, listado masivo, filtro y resumen usan las mismas deudas. DEUDOR requiere
  saldo impago en un período anterior; luego se evalúan MOROSO, EN PLAZO y AL_DIA.

## Evidencia de pruebas

Antes de modificar la aplicación, `php artisan test --filter=CuotaAltaEstadoTest`:
**7 fallos / 1 correcta, 23 aserciones**. Fallaban la ausencia de cuota al alta, el padrón
conciliado como DEUDOR, el alumno nuevo dentro de la gracia, el intento de insertar cuota
para probar rollback y la cuota única tras reintentar. Deuda anterior conservaba DEUDOR.

Diez pruebas nuevas cubren los porcentajes 100/70/40, una regla configurable al 65%, cuota
solo del mes corriente con ingreso antiguo, congelamiento ante cambios de precio/reglas,
preview y cobro concordantes, alta el 30 y generación repetida el 1, padrón de 60 con 40
conciliados y 20 con deuda anterior sin pagos, los cuatro estados en todos los consumidores,
rollback después de insertar la cuota, reintento, alumno preexistente intacto y
parcial/anulación/recobro. La generación mensual conserva una cuota por período: septiembre
no se duplica y octubre se genera una vez para el alumno elegible.

Se adecuaron las expectativas antiguas que exigían DEUDOR sin deuda, y el fixture de
concurrencia de inscripción que insertaba una cuota ya creada por el alta. Se mantiene
la comprobación con dos conexiones de que el segundo cobro espera sin escribir.

**Suite completa final:** `php artisan test` → **331 pruebas / 1900 aserciones**,
94,24 s, todas verdes. Sintaxis PHP de los archivos modificados correcta; compilación y
limpieza de vistas correctas; diff de vistas/CSS vacío. Actualizados los tres documentos
controlados por `DocumentacionNoMienteTest`.

## Alcance y revisión pendiente

- Las pruebas usan MariaDB descartable `wings_testing`. No se ejecutaron migraciones,
  seeders ni modificaciones sobre la base local de trabajo o el servidor.
- El padrón de 60 de la prueba es un fixture, no una nueva consulta a los datos del servidor.
  En general, “con deuda” incluye EN_PLAZO, MOROSO y DEUDOR; no se debe exigir que la tarjeta
  DEUDOR iguale siempre el total con deuda. En el fixture de deuda anterior coinciden en 20.
- Otro agente debe verificar código y pantalla: alta, cuota previa al cobro, cobro sin
  nuevo descuento, ficha/listado y tarjetas con padrón conciliado. A2/B2 no se cierran
  como verificados por quien implementa.
- Sin despliegue. Al desplegar deberá aplicarse la nueva migración junto con el código.
- No se resolvió el pendiente separado sobre descuentos de deudas históricas importadas
  de su propio mes de ingreso ni el significado de `pagos.monto_base` en parciales/multimes.
