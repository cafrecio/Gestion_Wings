# PROMPT — T82: controlar el ensayo de restauración — para Codex

> Prompt para Codex, escrito por Claude CAB el 01/11/2026, a pedido de Carlos.

**Inicio.** Aplicar `docs/00-estado/PROTOCOLO-CONTINUIDAD.md` y leer `AGENTS.md`. Traer `main` actualizado y marcar T82 `en_curso, tiene=Codex`.

Claude hizo el ensayo y no controla su propio trabajo: el control es tuyo, sin dar por cierto nada de lo que dice este prompt ni el informe.

**Autorización.** Carlos pidió este control y te pasa este prompt: podés entrar con `ssh vps` y correr lo que dice acá, con los límites de «Reglas». Nada más. Si hace falta otra cosa en el servidor, frená y preguntale.

## Qué se controla

La pregunta es una sola: **si se pierde este servidor, ¿Wings vuelve con lo que hay guardado?** Claude la respondió que sí el 28/10, en [el informe del ensayo](../../04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion/README.md), con [este script](../../../scripts/servidor/ensayo-restauracion.sh). No alcanza con correrlo y ver que da bien: de ese ensayo no quedó la salida guardada, y el script se lo armó quien lo hizo.

| # | Control | Qué tiene que pasar |
|---|---|---|
| 1 | Producción: correr el ensayo con el respaldo más nuevo de Drive | Baja, descifra, restaura y arranca. Anotá el tiempo y qué respaldo usó |
| 2 | Sitio de prueba: correr el ensayo con `FUENTE=wingstest` | Lo mismo, con datos. Sus cantidades ya no son las del informe: hoy hay 117 alumnos y 229 pagos |
| 3 | Plata, con tus propias consultas y no las del script, viva contra copia | Pagos, imputaciones, cuotas, movimientos de caja, Cashflow y **liquidaciones**, al peso. También lo de cada tabla, no solo los totales que el script mira |
| 4 | La copia restaurada funciona, no solo abre `/login` | La aplicación restaurada lee un alumno, un pago y una liquidación, y un recibo guardado se abre. La `APP_KEY` del respaldo sirve |
| 5 | `restaurar.sh`, en modo `ensayo` | Es el camino que [la vuelta atrás del despliegue](../../04-tecnico/DESPLIEGUE-PRODUCCION.md) indica, y el ensayo de T82 no lo usó. Probalo con un respaldo de producción y con un paquete del sitio de prueba armado por vos (`WINGS_DB`, `WINGS_APP_DIR`) |
| 6 | Limpieza | Después de cada corrida no queda carpeta, base, usuario, proceso ni puerto. Las bases `wings` y `wingstest` quedan como estaban |
| 7 | La tabla «Qué no está en el respaldo» del informe | Cada pieza está donde dice. Decí qué le faltaría a alguien para rearmarla sin este servidor |

**Lo que Claude vio hoy leyendo, sin ejecutar nada. Comprobalo:**

- **El script no compara la plata de liquidaciones.** Suma `monto_final`: en `wings` esa columna no existe, y en `wingstest` la suma da `NULL` en las dos bases, así que dice «ok» sin comparar nada. `restaurar.sh` usa `total_calculado`. Por eso «plata idéntica, 0 diferencias» no alcanza a las liquidaciones, y no quedó salida para ver qué dijo la corrida de producción del 28/10.
- **Hay dos `restaurar.sh` distintos en el servidor.** El de `/home/wings/app/scripts/servidor/` es igual al del repositorio. El de `/root/wings-backup/` es otro, del 30/08, y no menciona los archivos subidos: restauraría solo la base. **No lo corras.** Decí cuál usaría una persona apurada y qué pasaría.
- **Si el script falla a mitad de camino, no limpia.** Sale antes de la limpieza y deja la carpeta con el respaldo descifrado y el `.env`, la base y el usuario.
- **Probó código viejo.** Producción está en un commit del 22/09 y el sitio de prueba en uno del 30/10; `main` lleva migraciones nuevas. Decí si el ensayo sigue valiendo después de desplegar o hay que repetirlo, y cuándo.

**Esto no se puede controlar acá, y va escrito como límite:** el armado en un servidor vacío y el volumen real del club. Producción sigue sin alumnos ni pagos, según un conteo de hoy; miralo vos.

## Reglas

- **Prohibido:** `restaurar.sh ... EN-SERIO`, escribir en las bases `wings` y `wingstest`, cambiar algo en `/home/wings`, `/home/wingstest`, cron, firewall, `rclone.conf` o `/root/wings-backup/`, reiniciar servicios y desplegar. Leer se puede: el ensayo necesita esos datos.
- **Lo único que podés crear** son estos nombres, y los borrás al terminar: `/root/ensayo-t82`, `/root/control-t82`, las bases `wings_ensayo_t82` y `wings_ensayo`, y el usuario `ensayo_t82`. `restaurar.sh` usa además su carpeta `/tmp/restaurar-wings-*`, que se borra sola: comprobalo. Si alguno de esos nombres ya existe antes de empezar, frená y avisá.
- **Si una corrida falla,** borrá esos nombres, comprobá que no quedó nada y recién después informá.
- **Los scripts no se cambian** ni se dejan copias en el servidor fuera de `/root/control-t82`. El ensayo se le puede pasar sin copiarlo: `ssh vps 'FUENTE=wingstest bash -s' < scripts/servidor/ensayo-restauracion.sh`.
- **El repositorio es público.** En el informe y la evidencia no van claves, huellas, contenido de `.env` ni de `rclone.conf`, y tampoco datos de personas ni volcados. No abras ni copies el documento de Drive con la clave: alcanza con ver que está y dónde.
- **Cuándo correrlo:** el servidor tiene un solo núcleo y Cora puede estar en el sitio de prueba. Antes de cada corrida mirá la carga y si hubo sesiones de `wingstest` en los últimos 10 minutos; si hay actividad, esperá. Evitá de 03:00 a 03:30 UTC, cuando corre el respaldo. Si el sitio de prueba cambia durante la comparación, repetila.
- No se corrige nada: lo que encuentres se informa. No hacen falta pruebas de PHP en esta máquina.

## Cómo entregar

- Informe en `docs/04-tecnico/seguridad-vps/2026-10-28-ensayo-restauracion/VERIFICACION-T82-CODEX.md`: una fila por control, lo comprobado y lo inferido por separado, cada hallazgo con gravedad y `archivo:línea`, y la salida de las corridas, revisada para que no lleve nada de lo prohibido. No corrijas el informe de Claude.
- **Tres pendientes siguen abiertos sea cual sea el resultado:** la credencial propia de Google para los respaldos, la clave guardada junto a los respaldos en Drive y el repositorio público. Decí cómo está cada uno hoy. No son hallazgos tuyos ni frenan el cierre.
- Sin graves ni medios: `php scripts/tablero/tablero.php cambiar T82 estado=cerrado verifica=Codex tiene=nadie`, con un paso que diga el resultado. Con hallazgos: `estado=devuelto tiene=Claude`, con el paso que corresponda.
- Entrada en `docs/00-estado/LOG-CODEX.md`, firmada según la máquina.
- **Commit solo de lo tuyo:** `git status`, mirar archivo por archivo, agregar por nombre con `git add <archivo>`, nunca `git add -A`, `git add .` ni `git commit -a`. Si un archivo tuyo tiene cambios ajenos sin publicar, frenar y avisar a Carlos.
