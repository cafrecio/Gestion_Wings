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
| 8 | **Correo y Telegram.** Producción no manda correo (SERVIDOR.md §correo): faltan las tres líneas del remitente en `.env`, y el chat de avisos se configura en Configuración → avisos | Claude pone las líneas; Carlos confirma que le llega el primer aviso | «Probar» en Configuración de avisos |
| 9 | Respaldos y monitoreo: comprobar que `respaldar.sh` incluye `wings` y que el monitoreo HTTPS mira `wings.gestionar-te.com.ar` | Claude | última corrida del respaldo del día; `probar-monitoreo.sh` |
| 10 | Primer ingreso de Carlos con su usuario real; Configuración: medios de pago, día de generación, gracia, mora (0 hasta que decida), inscripción | Carlos | las pantallas que usa el dueño abren y guardan |
| 11 | Carga del club por el circuito acordado: primera carga por Excel (plantilla, revisión, carga, deshacer) con el primer mes de deuda; después alumnos sueltos por pantalla | Carlos / el club | Cobranza y Reportes dicen lo que dice la planilla |
| 12 | Primera caja real acompañada | el club, con Claude mirando | apertura, cobros, cierre y validación del primer día |

## Lo que no es técnico y sigue abierto

- **T10 — textos legales:** condiciones de servicio, privacidad, cookies y leyenda de inscripción esperan los
  datos de Carlos (entre corchetes), moneda, IVA, país del servidor e inscripción en la AAIP. No frena el
  despliegue; frena abrirlo a terceros sin esos textos.
- **T82 — restauración:** ensayada (52 s desde Drive). Pendientes de Carlos: credencial propia de rclone y
  sacar la clave de la carpeta de respaldos.
- **Ayuda (T12/T13):** sube como está (etapas A y B); la C sigue después.
- **13 mejoras futuras** del tablero: ninguna toca plata ni datos; siguen siendo futuras.

## Riesgo asumido y cómo se cubre

Producción arranca sin operación, así que el despliegue no pone plata ni datos en juego. Lo único que
producción todavía no vivió es el **cierre de mes real** (generación del 1/12 con Revisión): se ensaya en el
sitio de prueba con la tanda 3 antes del 1/12, y lo que salga se sube con la misma rutina. Si el club carga
alumnos en noviembre, la primera generación real es el 1/12 a las 06:00; el resumen de las 08:00 avisa si
falló, por Telegram y correo.

## Vuelta atrás

El script deja el respaldo con fecha y hora. Volver atrás es: `artisan down`, `git reset --hard <commit anterior>`,
restaurar la base desde ese respaldo (`restaurar.sh`), cachés, `artisan up`. Como la base arranca vacía de
operación, el riesgo real de la vuelta atrás es nulo hasta que el club empiece a cargar.
