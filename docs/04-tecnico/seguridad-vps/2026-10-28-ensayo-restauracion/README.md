# Ensayo de restauración de Wings (T82) — 28/10/2026

## Corrección del 01/11/2026, después del control de Codex

[Control de Codex](VERIFICACION-T82-CODEX.md). **Lo que sigue más abajo es del 28/10.** Allí «plata idéntica» no incluía liquidaciones: el script sumaba una columna vacía o inexistente y decía «ok» sin comparar.

**Script corregido** (`scripts/servidor/ensayo-restauracion.sh`) y corrido de nuevo el 01/11 en el VPS, con el código que tiene cada sitio hoy:

| Corrida | Resultado |
|---|---|
| Sitio de prueba, con los datos del 5/12 | 75 s. Todas las tablas contadas y 7 sumas iguales, incluidas liquidaciones $3.026.280,00 y sus detalles. 66 archivos. `/login` 200. Código de salida 0 |
| Producción, desde Drive, `wings_2026-11-01_0315` | 53 s. `/login` 200. Sin datos de negocio: no prueba plata. Código de salida 0 |
| GitHub inaccesible, a propósito | Código de salida 1 y limpia todo |
| Consulta de plata rota, a propósito | «NO COINCIDE», diferencias 1, código de salida 1 y limpia todo |

**Qué cambió:** compara `total_calculado` y `liquidacion_detalles`; un error de consulta cuenta como diferencia; el código de salida dice la verdad; cualquier salida limpia base, usuario y carpeta; rclone trabaja sobre una copia de su configuración, y el `rclone.conf` real quedó igual, comprobado por huella.

**Falta:**
- **El `restaurar.sh` viejo de `/root/wings-backup/` se retiró el 01/11, con el OK de Carlos:** ahora se llama `restaurar.sh.viejo-20260830-no-usar`. Era del 30/08 y solo reponía la base, sin los archivos; nada lo llamaba. El que vale es `/home/wings/app/scripts/servidor/restaurar.sh`.
- **Lo que este ensayo no prueba:** un servidor vacío ni el volumen real del club. Solo se recuperaron las piezas de Wings en este mismo servidor. Las piezas de la tabla «Qué no está en el respaldo» hay que rearmarlas a mano.
- **Repetirlo:** antes de desplegar, con el código y las migraciones nuevas sobre una copia; después, con el primer respaldo que traiga el esquema nuevo.
- Los tres pendientes de abajo siguen abiertos.

Claude CyE. Script: [scripts/servidor/ensayo-restauracion.sh](../../../../scripts/servidor/ensayo-restauracion.sh), corrido en el VPS como root. No toca `/home/wings`, `/home/wingstest` ni las bases `wings` y `wingstest`; al final borra su base, su usuario y su carpeta.

## Primer ensayo: producción, desde Drive — 52 segundos

| Paso | Resultado |
|---|---|
| Bajar de **Drive** el respaldo más nuevo (no la copia local) | `wings_2026-10-28_0315.tgz.enc`, 36 KB |
| Descifrar | La clave de Drive (documento «Credenciales Back-ups» que subió Carlos) coincide por huella con la del servidor; se descifró con esa misma clave |
| Base aparte `wings_ensayo_t82` | 35 tablas; usuarios, clases, profesores y migraciones iguales a la base viva |
| Código desde GitHub sin credenciales, en el commit que corre producción | `314e485d` |
| `.env` y `APP_KEY` | Vienen en el respaldo |
| Archivos subidos (`storage/app`) | 3 archivos restaurados |
| `composer install` y `npm run build` | Correctos |
| Arranque | `/login` 200, `/` 302; tareas programadas listadas; `wings:preflight` 11/12 (el único rechazo es `APP_URL` sin HTTPS, que el ensayo cambió a propósito a `http://127.0.0.1`) |

## Segundo ensayo, con datos: el sitio de prueba — 56 segundos

Producción está vacía, así que se repitió con `FUENTE=wingstest`: el paquete se arma igual que `respaldar.sh` (solo lectura sobre `wingstest`), se cifra con la misma clave y sigue el mismo camino. Código en el commit del sitio de prueba (`f8d71d5c`), 0 migraciones pendientes, `/login` 200.

| Tabla | Filas (viva = copia) |
|---|---|
| alumnos / pagos / deuda_cuotas | 105 / 95 / 204 |
| clases / asistencias | 81 / 909 |
| cajas_operativas / movimientos_operativos / cashflow_movimientos | 19 / 128 / 139 |
| liquidaciones / profesores / users | 8 / 6 / 9 |

Plata, al peso, idéntica en la copia: pagos $ 3.635.500,00; cuotas generadas $ 7.377.500,00 y pagadas $ 3.355.500,00; Cashflow $ 3.389.900,75; caja $ 3.929.400,00. Archivos subidos: 41. Diferencias: 0.

`wings:preflight` rechaza 5 puntos en esta copia porque el `.env` es el del sitio de prueba (`staging`, sin cookies estrictas): es lo esperado, no un defecto del respaldo.

## Qué no prueba este ensayo

- **Volumen de producción:** el de prueba tiene 105 alumnos y dos meses; el club real puede tener más historia. El respaldo de prueba pesa 51 MB y tardó 4 s en armarse.
- **Un servidor nuevo:** se usó este mismo VPS, que ya tiene PHP 8.2, MariaDB, Node y Composer. Instalar todo eso en una máquina vacía no se midió.

## Qué no está en el respaldo

Se rearma a mano o desde otro lado. Lo que dice «repo» está en `scripts/servidor/`.

| Pieza | Dónde está hoy |
|---|---|
| Sitio web y pool PHP de `wings` | Panel CWP; receta parecida en `montar-test.sh` (repo) |
| Certificado | Let's Encrypt, se emite de nuevo (necesita el DNS apuntando) |
| Cron del scheduler (usuario `wings`) y del respaldo (root) | Crontab del servidor; los scripts sí están en el repo |
| `rclone.conf` (acceso a Drive) | Solo en el servidor |
| Monitoreo y Telegram | `/etc/wings-monitor/alertas.env`, solo en el servidor; ejemplo en el repo |
| Cloudflare y firewall (`csf.allow`) | Cloudflare y el servidor; rangos documentados en `SERVIDOR.md` |
| Acceso SSH de los agentes | Se rearma solo desde GitHub (`acceso-agentes.sh`, repo) |

## Pendientes

1. **rclone usa la credencial compartida de Google, que se retira durante 2026.** Si se corta, el respaldo queda solo en el servidor (avisa por Telegram). Crear una credencial propia: 5 minutos de Carlos en la consola de Google.
2. **La clave está en la misma carpeta de Drive que los respaldos.** Quien entra a esa carpeta tiene las dos cosas. Pasarla al administrador de contraseñas (SEG-12) y sacarla de ahí.
3. **El repositorio es público** (comprobado con `gh repo view`). El historial conserva 33 versiones de `database/dump.sql` con la tabla `users`; las miradas (la de febrero y la del 05/07) tienen cuentas de demostración con dominios `example.com`, `test.com`, `wings.com` y `wing.com`, y sus contraseñas cifradas. No se comprobó si alguna contraseña de esas se reusa en producción (la consulta a producción no se hizo). Decidir si el repositorio pasa a privado; para un servidor nuevo, eso obliga a clonar con credencial.
