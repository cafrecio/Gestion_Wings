# Verificación T82 — Codex CAB, 01/11/2026

> **Resultado vigente: CERRADA en segunda vuelta**, al final de este archivo.
> El control original que sigue conserva sus resultados históricos y sus límites.

**Resultado: DEVUELTA a Claude por hallazgos medios.** Se recuperaron datos, configuración, archivos y aplicación de los dos entornos ensayados. Eso no certifica reconstruir un VPS vacío ni restaurar el volumen real del club. El script de T82 puede anunciar éxito sin comparar liquidaciones y no limpia al fallar; además conviven dos restauradores distintos.

Respuesta acotada a «si se pierde este servidor, ¿Wings vuelve con lo guardado?»: **la recuperación de las piezas de Wings ensayadas funciona, pero todavía no está demostrada la reconstrucción integral sin este VPS y sus configuraciones externas**. Los tres pendientes de Carlos se mantienen abiertos y no se usan como hallazgos nuevos para devolver la tarea.

## Alcance y resguardos

Lecturas, ejecuciones y tiempos del 01/11, entre 13:53 y 14:23 UTC; las salidas conservan UTC (CAB: UTC−3). `main` actualizado al iniciar; scripts leídos completos y sin editar. El grafo dio pistas y cobertura no disponible/metadatos cambiados: se verificaron las fuentes directamente. No se ejecutaron suites PHP locales, migraciones de las bases vivas, despliegues, reinicios ni el modo `EN-SERIO`.

Antes de cada corrida se exigieron nombres libres, puerto8099 libre, carga de un núcleo menor que1, ausencia de sesiones en la ventana real y registros de acceso de prueba sin actividad en diez minutos. Las 136 sesiones de prueba llevan fechas futuras: por eso también se revisó la hora real de sus accesos, no solo `last_activity >= ahora−10min`. Todas las corridas quedaron fuera de03:00–03:30UTC. Caches de Composer/npm/rclone y temporales propios se dirigieron a `/root/control-t82`; las copias, bases y usuario usados fueron únicamente los autorizados.

**Excepción de ejecución que no se oculta:** el archivo protegido `/root/.config/rclone/rclone.conf` registra una reescritura a las14:00:06UTC, durante la primera descarga. Es compatible con la renovación automática OAuth de rclone; no se conservó versión anterior para probar qué campos cambiaron. No se hizo edición manual, no se revirtió ni se volvió a usar rclone tras detectarlo. No se declara cumplimiento absoluto del límite de archivos protegidos. Véase H4.

## Siete controles — ejecutado e inferido por separado

| # | Comprobado por ejecución actual | Inferido y límites | Evidencia |
|---|---|---|---|
| 1. Producción, respaldo externo más nuevo | Drive entregó `wings_2026-11-01_0315.tgz.enc`,36KB. Descifra, restaura35 tablas, instala/compila y responde `/login`200 y `/`302. Control repetido válido: **40s del script,45,19s con envoltura**. La primera corrida demoró90s y su invariancia propia fue inválida por un error de mi comparador, corregido y repetido. | Producción tiene0 alumnos y0 pagos: no prueba operación ni volumen real. Su consulta de liquidaciones falla, pese a terminar0. | [Corrida válida](evidencia-codex/original-produccion-control-corregido.txt), [tiempo/código](evidencia-codex/original-produccion-control-corregido.json), [incidente propio](evidencia-codex/incidente-control-inicial.md). |
| 2. Prueba, `FUENTE=wingstest` | Paquete propio del script85MB;117 alumnos,229 pagos,56 tablas,65 archivos. Instala/compila; `/login`200, `/`302. **59s del script,64,98s con control externo**. Código del script0. | Su «diferencias0» no compara dinero de liquidaciones: `SUM(monto_final)` esNULL en ambas. Los cinco rechazos de preflight corresponden al perfil staging/HTTP local, no acreditan producción segura. | [Corrida](evidencia-codex/original-prueba.txt), [tiempo/código](evidencia-codex/original-prueba.json). |
| 3. Consultas propias, datos e importes | En prueba, **56/56 tablas coinciden en columnas y contenido completo**, no solo cantidad o suma.29 comparaciones decimales de9 tablas financieras, incluidas imputaciones, pérdidas y liquidaciones. Producción:34/35 tablas iguales; solo sesiones difieren de la copia nocturna (121 frente a42 en la comparación final). | Las sesiones vivas siguieron recibiendo bots después del respaldo. No se presenta esa diferencia temporal como pérdida de negocio. Producción vacía no ofrece evidencia monetaria positiva: las sumasNULL están marcadas «sin valores». | [Prueba completa](evidencia-codex/independiente-prueba.txt), [producción/datos](evidencia-codex/independiente-produccion-datos.txt), [comparador independiente](evidencia-codex/auditar-datos.py). |
| 4. Funcionamiento de la aplicación recuperada | En prueba, modelos leen alumno, pago y liquidación con sus relaciones. Peticiones reales al kernel restaurado, con administrador copiado: ficha y liquidación200, contenido identifica el registro leído; recibo guardado200, MIME PDF, cabecera/cierre PDF y **1.696.172 bytes idénticos al archivo previo**. La clave cargada viene del respaldo y descifra un texto de control cifrado con la clave viva; se probó en ambos entornos. Archivos completos:3/3 en producción y65/65 en prueba, contenido igual. | Son peticiones internas del kernel; no login por contraseña ni recorrido en navegador. No se exportaron HTML con personas ni PDF. En producción no hay alumno/pago/liquidación/recibo para ese control funcional de negocio. No se abrió el documento de la clave de Drive ni se certifica que su contenido coincida. | [Funcional](evidencia-codex/funcionamiento.php), [prueba ejecutada](evidencia-codex/independiente-prueba.txt), [producción](evidencia-codex/independiente-produccion.txt). |
| 5. `restaurar.sh ... ensayo` | Se ejecutó la versión del repo, idéntica a `/home/wings/app/scripts/servidor/restaurar.sh`. Producción desde Drive: script0,28 tablas contadas y verificaciones financieras sin datos; prueba con paquete armado por Codex y `WINGS_DB=wingstest`, `WINGS_APP_DIR=/home/wingstest/app`: script0,49 tablas contadas y sumas con datos, incluida liquidación1.370.280. Ambas corridas borran `wings_ensayo` y `/tmp/restaurar-wings-*`. | Las otras siete tablas de framework solo se exigen presentes, según el propio script. Su comparación de archivos es por cantidad; el control independiente compara contenido. No se ejecutó la rama real ni la secuencia de vuelta atrás sobre `/home/wings`. | [Producción](evidencia-codex/restaurar-produccion.txt), [prueba](evidencia-codex/restaurar-prueba.txt). |
| 6. Limpieza y conservación | Tras cada corrida se borraron nombres autorizados; comprobación final:0 carpetas,0 bases,0 usuarios MySQL/sistema,0 procesos,0 puertos8099 y0 temporales del restaurador. En fallo provocado de GitHub, el script dejó paquete descifrado/SQL/`.env`/base/usuario; se comprobó ese estado y la limpieza externa posterior. `wingstest` no cambió durante ninguna comparación válida; todo negocio de `wings` permaneció igual. | No se afirma que todas las sesiones de producción quedaran inmóviles: cambiaron en dos ventanas con tráfico externo de bots, sin usuario autenticado ni agente curl. La primera invariancia propia inválida se conserva y no demuestra un cambio real. La reescritura de rclone se trata aparte. | [Fallo y limpieza](evidencia-codex/fallo-intermedio.txt), [bots sin datos personales](evidencia-codex/sesiones-produccion.txt), [final](evidencia-codex/comprobacion-final.txt). |
| 7. Piezas externas al paquete | Verificadas ubicaciones de vhost/pool, certificado Let's Encrypt, dos cron, rclone, monitoreo, reglas CSF y acceso SSH. Google Drive confirma solo por metadatos el documento en `BackUp VPS/Credenciales`. Repositorio consultado sin autenticación: público. | Esas piezas no se recuperan con los tres miembros del paquete. La receta del repo ayuda, pero no sustituye el montaje medido en servidor vacío ni acredita una copia externa de todos los secretos operativos. Detalle abajo. | [Metadatos del servidor y migraciones](evidencia-codex/independiente-produccion-datos.txt), [Drive sin contenido](evidencia-codex/ubicacion-clave-metadata.json). |

## Dinero de prueba, comparación propia

Son sumas de columnas concretas para acreditar fidelidad de copia; no se presentan como saldo operativo ni como nueva evaluación de reglas de negocio.

| Columna | Viva = restaurada |
|---|---:|
| `pagos.monto_final` | $9.013.600,00 |
| `pago_deuda_cuota.monto_aplicado` | $8.222.200,00 |
| `deuda_cuotas.monto_original` / `monto_pagado` / `monto_perdido` | $8.874.500,00 / $8.222.200,00 / $77.000,00 |
| `movimientos_operativos.monto`, todos los estados | $9.706.600,00 |
| `cashflow_movimientos.monto` | $5.871.520,75 |
| **`liquidaciones.total_calculado`**, ocho valores no nulos | **$1.370.280,00** |
| `liquidacion_detalles.monto`,102 filas | $1.370.280,00 |
| `liquidaciones.monto_final` | NULL;0 valores no nulos: **no prueba dinero** |

Método: leer todas las columnas de cada fila, codificar valores/NULL sin ambigüedad y comparar el multiconjunto completo; huellas internas solo en memoria/archivos privados temporales, luego borrados y **nunca publicados**. Las sumas se calcularon con DECIMAL, sin sumar importes en floats de Python. Dos errores del control propio se corrigieron y repitieron; la primera salida incompleta de importes de producción no se acredita como comparación monetaria.

## Hallazgos

| ID | Gravedad | Archivo:línea y evidencia | Alcance/propuesta sin corregir |
|---|---|---|---|
| H1 | Media | `scripts/servidor/ensayo-restauracion.sh:51–57`: usa `liquidaciones.SUM(monto_final)`; en producción falla por columna ausente, en prueba comparaNULL/NULL. La corrida válida de producción imprime diferencias1 pero termina0 (`:89–93`). | El ensayo original no permite concluir «plata idéntica,0 diferencias» sobre liquidaciones ni usar solo el código0 como aprobación. Comparar el campo monetario correcto y hacer fallar las comprobaciones fallidas. La consulta propia con datos sí acredita1.370.280,00. |
| H2 | Media | `scripts/servidor/ensayo-restauracion.sh:62`: salida temprana al fallar clone; limpieza solo al final (`:89–93`), sin trap. Fallo de conexión provocado solo en ese proceso: quedaron SQL,configuración descifrada,base y usuario, antes de que mi envoltura los borrara. | Añadir limpieza garantizada a todo camino de salida y comprobar residuos. No hubo fuga pública comprobada; los restos estaban bajo `/root`. |
| H3 | Media | `/root/wings-backup/restaurar.sh:30–34,39,43–57`, leído sin ejecutar: versión modificada30/08,solo importaSQL y compara15 tablas; no tiene recuperación de `storage/app`. El `env.txt` temporal que manda revisar se borra al salir por el trap (`:22`). | Quien, apurado, ejecute el homónimo junto al script del cron en `/root/wings-backup` usaría ese viejo camino: **inferido por lectura**, repondría solo la base y perdería los recibos/configuración en la restauración automática. El canónico del repo ensayado sí contempla las tres piezas. Resolver la ambigüedad sin dar por probado un modo real que no se ejecutó. [Lectura](evidencia-codex/lectura-legacy-sin-mascara-flags.txt). |
| H4 | Media, operativa | `scripts/servidor/ensayo-restauracion.sh:9,23–24`, config fijo de rclone; `/root/.config/rclone/rclone.conf` fue reescrito14:00:06UTC en la primera descarga. [Fecha final](evidencia-codex/comprobacion-final.txt). | El ensayo original no queda confinado a los nombres descartables si rclone renueva sus credenciales. La causa OAuth es inferida; no hay copia anterior para certificar el diff. Debí prever ese efecto antes de correrlo. No se revirtió ni se cambió manualmente. Proponer configuración temporal para el ensayo; los siguientes controles dejaron de usar rclone al detectarlo. |

## «Qué no está en el respaldo»: dónde está y qué faltaría sin este VPS

| Pieza del informe de Claude | Comprobado hoy | Lo necesario para rearmar sin este servidor — inferido |
|---|---|---|
| Sitio y pool PHP | `/usr/local/apache/conf.d/wings.conf`; `/etc/opt/remi/php82/php-fpm.d/wings.conf`. Existe la receta `scripts/servidor/montar-test.sh`; usa CWP/Remi,rutas y socket propios. | Acceso inicial al proveedor/panel y una receta de instalación compatible de CWP,PHP,MariaDB,Node y Composer; recrear usuario,pool,vhost,permisos y build. La instalación desde cero no fue ensayada. |
| Certificado | `/etc/pki/tls/wings/wings.crt`, analizable y emitido por Let's Encrypt. No se leyó/copió su clave privada. | Control de DNS y posibilidad de reemitir/configurar el certificado; no se ensayó renovación ni emisión. |
| Cron | Root03:15UTC: `/root/wings-backup/respaldar.sh`. Usuario `wings`,cada minuto: `/home/wings/app/scripts/servidor/ejecutar-scheduler.sh`. | Reinstalar ambas entradas y dependencias, además de copiar scripts; no vienen en el paquete. No se cambiaron los cron. |
| Acceso Drive | `/root/.config/rclone/rclone.conf`, cliente propio no configurado; rclone avisa que usa el cliente compartido. | Acceso a la cuenta de Google y autorización nueva de rclone si se pierde este archivo; credencial propia sigue pendiente. No se publica/copió su contenido. |
| Monitoreo/Telegram | Config operativa `/etc/wings-monitor/alertas.env`; el nombre `monitoreo.env` del informe no es su ruta actual. Ejemplo en repo. | Recuperar o regenerar tokens y URLs de servicios externos desde cuentas controladas fuera del VPS; el ejemplo no contiene esos secretos. No se probó que haya otra copia externa de esa configuración. |
| Cloudflare/CSF | `/etc/csf/csf.allow` existe. Las15 redesIPv4 del código figuran; las7IPv6 no. CSF tieneIPv6 desactivado y el sistemaIPv6 habilitado. No se probó alcanzabilidad IPv6 ni se califica eso como defecto de seguridad en este control. `SERVIDOR.md` remite al documento de plataforma; no contiene toda una receta de firewall nuevo. | Acceso a la zona/DNS de Cloudflare, reglas y decisiones IPv4/IPv6. Reconstituirlas y verificar acceso después; nada de eso se restauró ni cambió aquí. [Contexto](evidencia-codex/contexto-firewall.txt). |
| SSH de agentes | `/etc/cron.d/wings-acceso-agentes` apunta a `/root/acceso-agentes.sh`, que existe; `authorized_keys` y archivo de claves propias presentes. El código actual del repo trae el script; el commit viejo de producción aún no lo trae. | Acceso inicial por consola/llave del proveedor antes de poder instalar el script; claves privadas de las máquinas y publicación de sus claves públicas en GitHub. El sincronizador no abre por sí solo un servidor vacío sin ese acceso inicial. No se imprimieron claves ni huellas. |

No se modificó el informe de Claude. Las observaciones de ubicación actuales quedan aquí; no se toma su tabla histórica como inventario suficiente para una máquina vacía.

## Código antiguo y cuándo repetir

**Comprobado:** producción ensayada `314e485d` (22/09),74 migraciones aplicadas y7 pendientes de su propio código. Prueba ensayada `6214fd4a` (30/10),110 aplicadas y0 pendientes de ese código. En el corte de `main` usado por el control hay114 archivos de migración: **40 no aplicados en producción y4 no aplicados en prueba**. La lista está en la salida de metadatos; no se ejecutaron esas migraciones.

**Inferido:** estos ensayos siguen probando esos respaldos y esas versiones, no el estado que dejará un despliegue nuevo. Antes de desplegar corresponde ensayar el código/migraciones candidatos sobre una copia; después del despliegue, repetir la recuperación con el primer respaldo externo que ya incluya el esquema nuevo y el mismo código desplegado. Repetir también tras cambios del circuito de respaldo/restauración o de dependencias que afecten al arranque. No se entrega una orden de despliegue ni se autoriza a ejecutarla.

## Tres pendientes existentes: continúan abiertos

1. **Credencial propia Google:** no configurada; comprobado por configuración sin exponer valores y por el aviso de rclone. No se creó ni cambió.
2. **Clave junto a respaldos:** metadatos del conector confirman `BackUp VPS/Credenciales/Credenciales Back-ups`. No se abrió ni descargó el documento, no se comprobó su contenido ni una migración al administrador de contraseñas. Sigue pendiente. Las primeras listas de rclone de un solo nivel no lo mostraban; no se interpretaron como inexistencia.
3. **Repositorio público:** API de GitHub consultada sin autenticación devolvió `private=false`; clonado en las copias. No se alteró visibilidad, historial ni credenciales. Sigue pendiente la decisión de Carlos.

**No son hallazgos nuevos ni frenan por sí mismos el cierre de T82.** La devolución se debe a H1–H4.

## Entrega y límites finales

- T82 se devuelve a Claude; no se corrigieron scripts ni producto. La copia de informe original permanece intacta.
- Servidor vacío y volumen real del club **no comprobados**, aunque los controles de datos/APP_KEY/archivos de prueba hayan pasado.
- Limpieza final comprobada; sin nombres, bases, usuario, procesos o puerto de ensayo restantes. Los datos de negocio vivos permanecieron iguales; se registran las variaciones de sesiones y la excepción de rclone, sin inventar un «todo intacto».
- Evidencia revisada para no publicar personas, volcados, credenciales, huellas ni contenido de `.env`/`rclone.conf`; solo código de control, metadatos y resultados agregados. Los originales descifrados solo estuvieron en los nombres privados autorizados y fueron borrados.

## Segunda vuelta — Codex CAB, 01/11/2026

**Resultado: CERRADA, verifica Codex.** H1–H4 corregidos y ejecutados nuevamente;
sin hallazgos medios ni graves en este control. Esto cierra el ensayo de las
piezas de Wings en el VPS existente, con los límites de servidor vacío y volumen
real que se explican abajo. No es una aprobación de despliegue.

Ensayos y limpieza entre **00:20 y 00:33 UTC del 02/11** (21:20–21:33 del 01/11 en CAB),
fuera de 03:00–03:30 UTC. Copia de código limpia de `main` `6be8f76d` más el
commit documental T119 `146bb6fc`. El script ensayado coincide con `a3c3b902`:
no se corrigió ni modificó. Se ejecutó desde el directorio privado
`/root/control-t82-codex-v2`, retirado al terminar.

### H1 a H4: comprobado por ejecución

| Control | Resultado actual y evidencia |
|---|---|
| H1 — dinero real | `FUENTE=wingstest`: **14 liquidaciones y 223 detalles**, `SUM(total_calculado)` y `SUM(monto)` dan **$3.026.280,00**, viva = copia. Ensayo corregido **54 s**, salida **0**. [Salida](evidencia-codex-v2/wingstest-ensayo.txt). |
| H1 — consulta rota | Copia del script fuera del repo: solo se reemplazó `liquidaciones:SUM(total_calculado)` por una columna inexistente. Imprime **NO COINCIDE**, diferencias 1 y salida **1**; limpia. [Salida](evidencia-codex-v2/consulta-rota.txt). El original no fue editado. |
| H2 — descarga fallida | Otra copia externa usa `/bin/false` como comando de descarga, sin modificar la configuración real ni Drive. Sale **1** y elimina carpeta, bases y usuario; cero procesos en la copia y puerto libre. [Salida](evidencia-codex-v2/descarga-falla.txt) y [limpieza](evidencia-codex-v2/descarga-falla-limpieza.json). |
| H2 — clonado fallido | Script original con `REPO=file:///root/control-t82-codex-v2/repositorio-que-no-existe.git`. Git falla después de restaurar la base; salida **1**, limpieza completa. [Salida](evidencia-codex-v2/clonado-falla.txt) y [limpieza](evidencia-codex-v2/clonado-falla-limpieza.json). |
| H2 — interrupción real | Script original en grupo de procesos propio, comprobado antes de señalizar. Se envió `kill -TERM` cuando **8099 ya estaba escuchando**: salida **130**, carpeta/base/usuario/procesos/puerto en cero. No hizo falta limpieza externa del grupo. [Control](evidencia-codex-v2/control-ejecuciones.txt), [salida](evidencia-codex-v2/terminado.txt) y [residuos](evidencia-codex-v2/terminado-limpieza.json). No se probó SIGKILL ni apagado abrupto. |
| H3 — restaurador retirado | `/root/wings-backup/restaurar.sh` ausente; `restaurar.sh.viejo-20260830-no-usar` presente. Lectura final de **17 archivos** de respaldo, crontabs y carpetas periódicas: sin referencias activas al restaurador. El canónico de `/home/wings/app/scripts/servidor/restaurar.sh` coincide con el repo y es el que indica `DESPLIEGUE-PRODUCCION.md:61`. El viejo no se ejecutó. [Metadatos](evidencia-codex-v2/metadatos-finales.txt) y [barrido de cron](evidencia-codex-v2/cron-completo.json). |
| H4 — Drive y configuración | Producción desde **Drive**, paquete `wings_2026-11-01_0315.tgz.enc`, **52 s**, salida **0**, `/login` 200. Huella, fecha en nanosegundos, modo y dueño de `rclone.conf` real iguales antes/después. La copia temporal desapareció con el ensayo. Se publican solo booleans, ninguna huella de clave/configuración. [Salida](evidencia-codex-v2/wings-ensayo.txt), [control](evidencia-codex-v2/control-ejecuciones.txt), [final](evidencia-codex-v2/comprobacion-final.txt). |

Las correcciones se ubicaron leyendo los cuerpos actuales: comparación de dinero
en `ensayo-restauracion.sh:69–76` y salida en `:115`; limpieza y señales en
`:15–23`; configuración temporal en `:26–28` y comprobación en `:112`.
El grafo no acreditó cobertura de estos archivos; se contrastaron con sus fuentes
reales. No se heredan como ejecutadas las corridas del informe de Claude.

### Regresión de los otros siete controles

| Control original | Reejecutado en esta vuelta y límites |
|---|---|
| 1. Producción desde respaldo externo | Descarga, descifrado, restauración de **35 tablas**, Composer 0, build 104 archivos, `/login` 200 y `/` 302. Producción sigue sin alumnos/pagos/liquidaciones: los ceros **no** son prueba monetaria positiva. |
| 2. Prueba con datos | Script 0; **56 tablas**, Composer 0, build 129 archivos, `/login` 200 y `/` 302. Código recuperado igual al sitio que se tomó como fuente. |
| 3. Comparador independiente | Prueba **56/56 tablas con columnas y contenido completo idénticos**, **29 comparaciones decimales** iguales. Producción **34/35** idénticas; solo difiere `sessions` respecto del respaldo nocturno. Sus 10 comparaciones monetarias no tienen datos positivos. [Prueba](evidencia-codex-v2/wingstest-datos.jsonl) y [producción](evidencia-codex-v2/wings-datos.jsonl). |
| 4. Aplicación/configuración/archivos | Configuración recuperada idéntica salvo los cuatro campos de aislamiento DB/URL; clave recuperada cargada y descifra texto de control en ambos entornos. Prueba: alumno y liquidación 200 con contenido del registro; recibo guardado 200, PDF válido y **1.696.172 bytes iguales**. Archivos **66/66** de prueba y **3/3** de producción, contenido igual. No se exportaron registros, HTML de personas ni PDF. [Funcional prueba](evidencia-codex-v2/wingstest-funcional.jsonl), [producción](evidencia-codex-v2/wings-funcional.jsonl), [archivos prueba](evidencia-codex-v2/wingstest-archivos.jsonl), [archivos producción](evidencia-codex-v2/wings-archivos.jsonl). |
| 5. Restaurador canónico | Repo, solo modo **ensayo**, con ambos paquetes: salida 0; **49 tablas** contadas en prueba y **28** en producción, más presencia de las siete de framework, importes/invariantes, archivos y configuración. [Prueba](evidencia-codex-v2/wingstest-canonico.txt) y [producción](evidencia-codex-v2/wings-canonico.txt). No se ejecutó `EN-SERIO`. |
| 6. Conservación y limpieza | Tras cada fallo y después de producción: cero carpeta, bases, usuario, procesos en la copia y puerto. En la ventana completa `wingstest` conservó sus 56 tablas sin cambios; en `wings` solo varió `sessions`. No se atribuye esa variación a bots en esta vuelta: no se repitió la auditoría de accesos. Directorio de control y temporales del restaurador retirados; conjunto de nombres de `/root` igual al inicial, rclone intacto. [Bases vivas](evidencia-codex-v2/vivas-despues.jsonl) y [final](evidencia-codex-v2/comprobacion-final.txt). |
| 7. Piezas externas | Se revalidó presencia de vhost/pool, certificado, configuración de monitoreo, CSF y sincronizador SSH; cron de respaldo y scheduler presentes. Cliente Google personalizado no configurado; API GitHub sin autenticación confirma repositorio público. No se reemitieron certificados, reconstruyó firewall, probó renovación de tokens ni abrió el documento de credenciales de Drive. Esto conserva esas limitaciones del primer control. [Inventario leído](evidencia-codex-v2/control-prueba-inicial.txt) y [metadatos](evidencia-codex-v2/metadatos-finales.txt). |

No se detectó una regresión en lo reejecutado. Se usaron peticiones al kernel de
la aplicación recuperada y HTTP local; no se afirma un ingreso con contraseña
ni un recorrido de navegador en esta vuelta.

Preflight conserva sus rechazos del perfil de copia: **cinco** en prueba y
**uno** en producción por la URL HTTP local. No se los presenta como controles
de seguridad aprobados; tampoco se cambiaron perfiles para obtener un verde.

### Incidencias del control propio

- El primer comparador se ejecutó después del GET de login: comparó **69 sesiones
  vivas contra 70 copiadas**, por la sesión creada por mi petición. Se conservó
  esa [comparación](evidencia-codex-v2/prueba-comparacion-despues-login.jsonl) y el
  [primer resultado](evidencia-codex-v2/control-prueba-inicial.txt). Se movió la
  inspección antes del curl y se repitió el ensayo completo: 56/56 idénticas.
  No era pérdida ni cambio de datos de negocio.
- La primera comprobación final comparó listas ordenadas por `sort` del servidor
  y por Python: se detuvo antes de borrar, aunque los conjuntos eran iguales.
  Comprobados cero nombres agregados/quitados, se corrigió solo el comparador y
  se completó la limpieza. [Intento conservado](evidencia-codex-v2/comprobacion-final-intento.txt)
  y [resultado final](evidencia-codex-v2/comprobacion-final.txt).
- El comando inicial de metadatos terminó al no encontrar referencias en un
  `grep` bajo `pipefail`. Sus controles posteriores no se acreditaron hasta
  reejecutarlos en los metadatos finales. [Primera salida](evidencia-codex-v2/inicial.txt).

Código de apoyo utilizado en el control: [comparador](evidencia-codex-v2/auditar-datos.py),
[funcionamiento](evidencia-codex-v2/funcionamiento.php),
[inspección antes de limpiar](evidencia-codex-v2/inspeccionar-copia.sh),
[interposición del curl real](evidencia-codex-v2/curl-control.sh) y
[fallos/señal](evidencia-codex-v2/segunda-vuelta.sh). El comando de inspección
agrega lecturas y peticiones sobre la copia; el script de producto permanece
idéntico. Las salidas versionadas solo normalizan espacios finales.

### Cierre, alcance y repetición

**Sí se puede cerrar T82 como ensayo del circuito existente:** quedaron
comprobadas las correcciones H1–H4 y la recuperación de las piezas guardadas.
**No quedó probado un VPS vacío ni el volumen real del club.** Las piezas
externas al paquete de la primera tabla siguen requiriendo reconstrucción y
acceso por fuera de este servidor. El cierre no convierte esa parte inferida en
una recuperación integral ya demostrada.

Código fuente de los sitios ensayados: producción `314e485`, prueba `219f5d59`.
Producción tiene **74 migraciones aplicadas y 7 pendientes de su propio código**;
prueba, **114 y 0**. No se aplicaron migraciones sobre ninguna base viva.
Por eso se mantiene obligatorio **repetir antes del despliegue**, con código y
migraciones candidatos sobre una copia, y **después**, con el primer respaldo
externo del esquema y código desplegados. También después de modificar el
circuito de respaldo/restauración.

Los tres pendientes de Carlos continúan abiertos y **no frenan este cierre**:
cliente Google propio, ubicación de la clave y decisión de visibilidad del repo.
No se cambió ninguno. No se modificó producto, las bases vivas ni sus hogares; no se
desplegó. La suite rápida PHP fue la de T119 ya entregada; no se repitió por
anexar este informe documental.
