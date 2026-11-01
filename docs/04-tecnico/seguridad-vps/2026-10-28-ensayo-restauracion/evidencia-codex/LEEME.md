# Evidencia T82 — Codex CAB, 01/11/2026

Informe: [VERIFICACION-T82-CODEX.md](../VERIFICACION-T82-CODEX.md). Los archivos son código de control, salidas revisadas y metadatos agregados. No contienen volcados, recibos, configuración ni secretos. El documento de Drive solo se buscó por metadatos; su contenido no se abrió.

## Corridas y uso de sus resultados

- `original-produccion`: primer ensayo. Su restauración llegó al final, pero la invariancia propia es inválida por listas/tuplas; no atribuir a producto sus falsos cambios.
- `original-produccion-control-corregido`: repetición válida; script40s,proceso45,19s,código0 pese a error de liquidaciones. Ambas bases vivas idénticas antes/después.
- `original-prueba`:59s del script,64,98s con control. Script0;envoltura81 por sesiones de producción que variaron. Prueba completa sin cambios; falso okNULL en liquidaciones.
- `restaurar-produccion` y `restaurar-prueba`: modo `ensayo` del restaurador actual,script0 ambos. En producción la envoltura81 registra variación de sesiones; no confundir con fallo de restauración. Temporales eliminados automáticamente.
- `independiente-produccion`: columnas/contenido de35 tablas y archivos comparados,APP_KEY comprobada. Sus primeras sumas propias se cortaron por COUNT como cadena; esa parte no se acredita.
- `independiente-produccion-datos`: completa sumas de producción (sin datos) y comparación completa. Código1 por diferencia de sesiones de la copia nocturna, no por pérdida de negocio. Metadatos del servidor/migraciones/visibilidad pública en la misma salida.
- `independiente-prueba`:0;56 tablas completas iguales,29 comparaciones decimales,65 archivos iguales,funcional de aplicación/recibo/clave. Ambas bases vivas idénticas antes/después.
- `fallo-intermedio`:1 esperado; proxy solo del proceso simula caída de GitHub. Se vieron residuos antes de la limpieza externa y0 después.
- `sesiones-produccion`: observación agregada de bots sin personas/IP/agentes completos. `comprobacion-final`: sin carpetas/bases/usuarios/procesos/puerto/temporales; rclone.confmtime14:00:06UTC.
- `lectura-legacy-sin-mascara-flags`: lectura final del restaurador viejo, nunca ejecutado. La primera `lectura-legacy` tenía una máscara demasiado amplia que también tapaba flags públicos de openssl; no se usa para esos flags.
- `contexto-firewall`: datos actuales sin cambiar reglas ni probar alcanzabilidad. El conteo intermedio23 incluía un patrón inválido; la comprobación final filtra redes válidas y da15IPv4/7IPv6.

Cada archivo `.json` de corrida guarda duración/código de la envoltura, mientras la salida `.txt` distingue código del script, comparación y limpieza. No son equivalentes: el original puede terminar0 con un error y una envoltura puede señalar sesiones externas aunque el restaurador termine0.

## Alcance y privacidad

El comparador propio lee columnas/filas completas y calcula sumas DECIMAL. Solo conserva en la evidencia pública igualdad, cantidades y sumas agregadas; las huellas privadas de las bases se usaron dentro de `/root/control-t82` y se borraron. `funcionamiento.php` usa peticiones internas del kernel con un administrador de la copia, sin contraseña ni navegador.

Los scripts de envoltura se conservan como evidencia de lo ejecutado; **no son una receta aprobada para repetir el ensayo**: H4 detectó que rclone sobre su config real puede reescribirla. No se volvió a usar ese comando tras detectarlo. No se editó ningún script de producto ni el README de Claude.

Las salidas `.txt` se normalizaron solo en espacios finales, saltos de línea y blancos al final para `git diff --check`; no se quitaron casos, errores, importes ni códigos. `incidente-control-inicial.md` explica los dos errores propios y las repeticiones. `resultado-control.json` y el informe distinguen comprobado/inferido.

Revisión del diff: el generador de tablero deja una línea vacía indentada en `TABLERO.html:98` al quedar vacía la lista sin verificador. Se conserva su salida (no se edita a mano ni se corrige producto en esta tarea). La advertencia queda registrada; los blancos de archivos PRU-04 ajenos no forman parte de la entrega.
