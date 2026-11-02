# Subir Wings a producción — plan del 30/10/2026

**Decisión de Carlos (30/10/2026, 02:16):** «Con lo que tenemos, el usuario se va a encontrar con un programa
sólido. Cuando Codex termine la prueba, subimos a producción.» La prueba es la verificación por pantalla de
MF12, MF13 y los dos arreglos de la tanda 2 ([prompt](../06-pruebas/PRU-04/PROMPT-CODEX-VERIFICACION-MF12-MF13.md)).
La tanda 3 (cierre de noviembre y Revisión del 1/12) sigue en el sitio de prueba en paralelo, antes del 1/12 real.

## Dónde está producción

Leído el 27/10 (solo lectura; desde el 30/10 el entorno de Claude bloquea hasta las lecturas de producción, así
que lo de abajo se confirma el día del despliegue): `wings.gestionar-te.com.ar`, `/home/wings/app`, usuario
`wings`, base `wings`. Código `314e485` (22/09/2026); **36 migraciones por correr** (7 de su propio código y
29 nuevas). Base sin operación: 0 alumnos, 0 cajas, 0 Cashflow, 0 medios de pago, 2 deportes, 7 grupos, 12
planes, 3 subrubros. **Los 4 usuarios son reales (Carlos, 30/10): no se tocan ni se recrean.**

## Qué se hace, en orden

**Pasos 1 a 7 y 9 hechos el 02/11/2026** por Claude, por orden de Carlos: producción en `b9394e7d`. Falta confirmar el paso 8 con el primer aviso. Siguen los pasos 10 a 12.

T97 (01/11/2026) agrega `2026_11_01_020000_agregar_anulacion_transferencias_cashflow`: fecha/hora, administrador y motivo de anulación en la cabecera. Debe correr en el paso 5 antes de habilitar el nuevo código de Cashflow; las cabeceras existentes quedan sin anulación y no se reescriben movimientos. Su `down()` conserva la auditoría financiera, como la cabecera T61: no elimina esos datos. Para una vuelta atrás se usa el respaldo, no se borra el rastro de una anulación.
Las **36 pendientes** de arriba son el corte leído el 27/10, no un conteo actual: se vuelve a obtener `migrate:status` durante el ensayo, incluyendo las migraciones posteriores de T96/T97 y otras tareas. Este trabajo no desplegó ni consultó producción o test; no desplegar mientras Cora trabaja en el sitio de prueba.

El script [`scripts/servidor/desplegar-produccion.sh`](../../scripts/servidor/desplegar-produccion.sh) hace los
pasos 2 a 9 y se frena ante el primer error. Antes y después hay trabajo a mano.

| # | Paso | Quién | Cómo se comprueba |
|---|---|---|---|
| 1 | **Ensayo de las migraciones sobre una copia de la base real.** Restaurar el respaldo de `wings` en una base `wings_ensayo`, apuntar una copia del código a ella y correr `migrate` + `CatalogosSeeder`. Si el usuario de la base no puede crear ni alterar tablas («solo lee y escribe datos», SERVIDOR.md), se le dan los permisos solo para el despliegue y se le retiran después | Claude | 0 pendientes en `migrate:status`; los 4 usuarios, 2 deportes, 7 grupos y 12 planes siguen |
| 2 | Respaldo de la base y del `.env` | script | archivo en `/root/respaldos`, `gzip -t` bien, más de 2 KB |
| 3 | Controles del entorno: `APP_ENV=production`, `APP_DEBUG=false`, sin `FECHA_SIMULADA`, `APP_URL` de producción, sin cambios locales | script | se frena si falta algo |
| 4 | Mantenimiento, código a `origin/main`, dependencias, build | script | `git log -1` es el commit esperado |
| 5 | Migraciones y catálogos (`CatalogosSeeder` usa `updateOrCreate`: no borra ni duplica; **no toca usuarios**) | script | «Migraciones pendientes después: 0» |
| 6 | Cachés, `wings:preflight` (APP_DEBUG, LOG_LEVEL y lo demás que controla), arriba | script | «Preflight aprobado.»; `/login` 200 |
| 7 | Programador: el cron de producción (`ejecutar-scheduler.sh`) corre cada minuto; cuotas 06:00, recargos 06:15, resumen 08:00 | Claude | el cron existe; al día siguiente, el resumen de las 08:00 llega |
| 8 | **Correo y Telegram.** Producción no manda correo (SERVIDOR.md §correo): el `.env` no viaja con el código, así que el script agrega las líneas `MAIL_` con remitente propio si faltan (paso 2b). El chat de avisos se configura en Configuración → avisos | script; Carlos confirma que le llega el primer aviso | «Probar» en Configuración de avisos |
| 9 | Respaldos y monitoreo: comprobar que `respaldar.sh` incluye `wings` y que el monitoreo HTTPS mira `wings.gestionar-te.com.ar` | Claude | última corrida del respaldo del día; `probar-monitoreo.sh` |
| 10 | Primer ingreso de Carlos con su usuario real: acepta términos y privacidad en nombre del club. **Después, Caja → Configurar: saldo inicial de Efectivo (T96); se carga una sola vez y hasta entonces no se puede abrir caja.** Después Tipos de Caja: saldo inicial de las demás cuentas. Configuración: medios de pago, día de generación, gracia, mora (0 hasta que decida), inscripción | Carlos | las pantallas que usa el dueño abren y guardan; en Caja aparece **Abrir** |
| 11 | Carga del club por el circuito acordado: primera carga por Excel (plantilla, revisión, carga, deshacer) con el primer mes de deuda; después alumnos sueltos por pantalla | Carlos / el club | Cobranza y Reportes dicen lo que dice la planilla |
| 12 | Primera caja real acompañada | el club, con Claude mirando | apertura, cobros, cierre y validación del primer día |

## Lo que no es técnico y sigue abierto

- **T10 — legal:** lo lleva Claude, a la medida de una pyme informal: cada persona acepta términos y privacidad dentro de Wings la primera vez que entra, y el club avisa a las familias
  (`docs/01-producto/legal/README.md`). No frena el despliegue ni la carga. Sin revisión de un abogado.
- **T82 — restauración:** ensayada en este servidor, sin probar un servidor vacío ni el volumen real. Repetir el ensayo
  antes de desplegar, con el código nuevo sobre una copia, y después, con el primer respaldo del esquema nuevo. Pendientes de
  Carlos: credencial propia de rclone y sacar la clave de la carpeta de respaldos.
- **Cada sitio tiene solo lo que usa (orden de Carlos, 02/11/2026):** `git sparse-checkout` en `/home/wings/app` (de 1,6 GB a 613 MB) y `/home/wingstest/app` (de 1,7 GB a 757 MB). Afuera: `docs`, `tests`, instrucciones de agentes y `scripts` salvo `scripts/servidor`. El sitio de prueba conserva además `scripts/pruebas/rutina` y `docs/06-pruebas/PRU-04/rutina`. Los despliegues y el `git merge` lo respetan; `.git` sigue con la historia. Se revierte con `git sparse-checkout disable`.
- **Ayuda (T12/T13):** sube como está (etapas A y B); la C sigue después.
- **Mejoras futuras** del tablero (MF): siguen siendo futuras. La única que toca plata es MF15, un cajón por cajero (Carlos, 01/11/2026).

## Riesgo asumido y cómo se cubre

Producción arranca sin operación, así que el despliegue no pone plata ni datos en juego. Lo único que
producción todavía no vivió es el **cierre de mes real** (generación del 1/12 con Revisión): se ensaya en el
sitio de prueba con la tanda 3 antes del 1/12, y lo que salga se sube con la misma rutina. Si el club carga
alumnos en noviembre, la primera generación real es el 1/12 a las 06:00; el resumen de las 08:00 avisa si
falló, por Telegram y correo.

## Vuelta atrás

El script deja el respaldo con fecha y hora. Volver atrás es: `artisan down`, `git reset --hard <commit anterior>`,
restaurar la base desde ese respaldo (`/home/wings/app/scripts/servidor/restaurar.sh`; el viejo de `/root/wings-backup/` se retiró el 01/11), cachés, `artisan up`. Como la base arranca vacía de
operación, el riesgo real de la vuelta atrás es nulo hasta que el club empiece a cargar.
