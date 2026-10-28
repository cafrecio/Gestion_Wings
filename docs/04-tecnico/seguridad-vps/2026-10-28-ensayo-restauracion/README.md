# Ensayo de restauración de Wings (T82) — 28/10/2026

Claude CyE. Script: [scripts/servidor/ensayo-restauracion.sh](../../../../scripts/servidor/ensayo-restauracion.sh), corrido en el VPS como root. No toca `/home/wings`, `/home/wingstest` ni las bases `wings` y `wingstest`; al final borra su base, su usuario y su carpeta.

## Resultado: Wings vuelve con lo guardado, en 52 segundos

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

## Qué no prueba este ensayo

- **Volumen real:** producción tiene 0 alumnos y 0 pagos. Se probó la estructura, la configuración y las cuentas, no una base cargada. Repetir con el club operando.
- **Un servidor nuevo:** se usó este mismo VPS, que ya tiene PHP 8.2, MariaDB, Node y Composer. Instalar todo eso en una máquina vacía no se midió.

## Qué no está en el respaldo

Se rearma a mano o desde otro lado. Lo que dice «repo» está en `scripts/servidor/`.

| Pieza | Dónde está hoy |
|---|---|
| Sitio web y pool PHP de `wings` | Panel CWP; receta parecida en `montar-test.sh` (repo) |
| Certificado | Let's Encrypt, se emite de nuevo (necesita el DNS apuntando) |
| Cron del scheduler (usuario `wings`) y del respaldo (root) | Crontab del servidor; los scripts sí están en el repo |
| `rclone.conf` (acceso a Drive) | Solo en el servidor |
| Monitoreo y Telegram | `monitoreo.env` solo en el servidor; ejemplo en el repo |
| Cloudflare y firewall (`csf.allow`) | Cloudflare y el servidor; rangos documentados en `SERVIDOR.md` |
| Acceso SSH de los agentes | Se rearma solo desde GitHub (`acceso-agentes.sh`, repo) |

## Pendientes

1. **rclone usa la credencial compartida de Google, que se retira durante 2026.** Si se corta, el respaldo queda solo en el servidor (avisa por Telegram). Crear una credencial propia: 5 minutos de Carlos en la consola de Google.
2. **La clave está en la misma carpeta de Drive que los respaldos.** Quien entra a esa carpeta tiene las dos cosas. Pasarla al administrador de contraseñas (SEG-12) y sacarla de ahí.
3. **El repositorio es público** (comprobado con `gh repo view`). El historial conserva 33 versiones de `database/dump.sql` con la tabla `users`; las miradas (la de febrero y la del 05/07) tienen cuentas de demostración con dominios `example.com`, `test.com`, `wings.com` y `wing.com`, y sus contraseñas cifradas. No se comprobó si alguna contraseña de esas se reusa en producción (la consulta a producción no se hizo). Decidir si el repositorio pasa a privado; para un servidor nuevo, eso obliga a clonar con credencial.
