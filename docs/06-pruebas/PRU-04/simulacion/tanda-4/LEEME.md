# Tanda 4 (días 41 a 47, 19 al 25/11): simulada de nuevo, con guion — Claude CyE, 30/10/2026

**El guion es [GUION-DIAS-41-47.md](../../GUION-DIAS-41-47.md)**: ahí está el plan, los esperados y lo que tiene que dar el 1/12.
La primera simulación (Claude CAB) se rehízo porque no preparaba todas las salidas del generador y sumaba poco volumen.

**Falta para entregarla a Codex:** el prompt, subir las rutinas y `rutina.php` al sitio de prueba y correr ahí
`dia-41-manana.json --simular`. **Ojo:** el sitio está en `d488b3f5` y `git merge --ff-only origin/main` también le lleva
97 archivos de `resources/` y `public/` que hoy no tiene.

## Cómo retomar en otra máquina

1. Bajar la base del sitio al 18/11 (el reloj sigue ahí hasta que Codex corra la tanda 4):
   `ssh vps "mysqldump --single-transaction wingstest | gzip" > fotos/d40.sql.gz`
2. `bash correr.sh 41`, después 42 … 47. Cada día arranca de la foto del anterior y deja la suya en `fotos/`
   (no se sube). Las salidas quedan en `salida-*.txt`; las de esta corrida ya están subidas.
3. `cierres-41-47.txt`: las cifras de cada cierre, como las lee Codex.
4. `esperado-antes-de-simular.txt`: las cuentas hechas a mano antes de correr. `ensayo-1-12.php` corre la generación
   del 1/12 sobre la foto del día 47: restaurarla en `wings_testing_guion` y correrlo con `FECHA_SIMULADA="2026-12-01 06:00:00"`.

## Hallazgos de la segunda simulación

- **T100, defecto:** un adelanto cobrado con un cero de más y cancelado deja diciembre pendiente en $300.000. Los $30.000
  correctos entran como cobro parcial y quedan $270.000 de saldo, que el 1/12 pasan a deuda.
- **T101, defecto:** el gasto que el dueño carga en el turno de una operativa queda a nombre de ella (`CajaService.php:550`);
  CAJ-041 pide que figure quien lo cargó. Leído en el código; la pantalla la mira Codex.
- **Cerrar con la pantalla vieja** (46.7): si entra un cobro mientras el arqueo está a la vista, cierra sin avisar y el
  cobro queda como faltante. No se pierde plata en el sistema; el aviso es lo que falta. Se anota en el guion.
- **Segundo deporte de quien vino de la primera carga** (46.4): cobra inscripción porque esa persona no tiene una
  registrada. Calculé que no la cobraría. No es seguro que sea un defecto: lo mira la revisión de la tanda.

## Hallazgos de la primera simulación (siguen en pie)

- **Error del servidor al crear un grupo con dos planes de la misma frecuencia** (dos filas «1 vez/semana»): no
  guarda nada, pero el dueño ve la página de error. La edición sí filtra repetidas; el alta no
  (`GrupoWebController::store`). Se llega por pantalla. Anotado como T99.
- **Avisos con nombres internos**, solo forzando el envío: «El campo planes.0.clases_por_semana no puede ser mayor
  que 7.», «El campo montos_cuota.2026-12 debe ser al menos 0.01.»; y por pantalla «El campo motivo fecha alta es
  obligatorio.» al cambiar la fecha de ingreso. Faltan nombres en `lang/es/validation.php`. En T99.
- «**la profesora** Hernán Quintana ya tiene asignada la clase…»: el aviso de choque nombra en femenino a un profesor.
- **Menús:** la profesora ve Clases y Ayuda; la operativa no tiene «Sin asistencia» en el menú; el dueño ve «Tipos
  de Caja» donde el catálogo dice «Medios de pago». El catálogo (SIS-013) está viejo en eso.
- **Historial:** muestra solo lo que pasó por las cajas, sin límite de 90 días y sin los movimientos directos ni
  los cobros del dueño. El catálogo (MOV-013/014) espera otra cosa. Semana 16 a 21/11: Historial ingresos
  $217.800 y egresos $28.300; Cashflow egresos $50.300 (la diferencia es el Internet del dueño).
- **Mi guion de la tanda 3 decía que el recargo de Emma corría el 25/11:** corre el 24/11 a las 06:15 (corregido).
- **El ejecutor de rutinas** tomaba por buena una serie que pedía confirmar el aviso de cancha y no creaba nada.
  Corregido en `scripts/pruebas/rutina/rutina.php`: ahora falla y pide hacerla por pantalla.
- Diciembre tiene clases el **8/12 y el 25/12 (feriados)**: para la tanda 5, el dueño las cancela.

## Preparado para el 1/12 (tanda 5)

- **Revisión:** Pilar Ramos, Alma Navarro, Facundo Ríos, Valentina Suárez y **Emma Villalba** (alta del 17/11, sin
  pago ni presentes, Morosa con recargo).
- **No va a Revisión aunque no vino:** **Valentino Godoy** (ingresó el 23/11 y pagó; CBZ-017 a).
- **Diciembre ya existe:** Renzo ($25.000 pendiente, ingreso 1/12, inscripción pagada), y pagados Joaquín Vega
  ($48.000), Emma Fernández ($45.000), Dante ($52.000), Clara Iglesias ($30.000) y Morena ($44.000, cobrada dos
  veces por el dueño: la primera anulada).
- **Deudor el 1/12:** Liam (viene y no paga) y los morosos que no pagaron.
- **Liquidación de noviembre:** la clase de Intermedias del 20/11 es de Mariela (cambiada con motivo); Federadas
  del 21/11 tiene dos profesoras; Fútbol Avanzadas del 21/11 cancelada por lluvia.
