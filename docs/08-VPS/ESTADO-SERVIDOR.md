# Estado del servidor — relevado el 30/08/2026

> Relevamiento hecho por SSH sobre el servidor real. Todo lo de acá está
> verificado ejecutando comandos, no inferido.
>
> **Las credenciales NO van en este archivo.** Están en
> `docs/08-VPS/credenciales-wings.txt`, que está fuera del repositorio.

## Qué es este servidor

**No es el AlmaLinux pelado que asumía `PLAN-PRODUCCION.md`.** Es un servidor
administrado con **CWP (CentOS Web Panel)**, que maneja Apache, PHP, DNS,
certificados y correo. Meterle cosas a mano por afuera del panel rompe lo que el
panel administra.

| Qué | Valor |
|---|---|
| Proveedor | Hostinger — `srv1753195.hstgr.cloud`, IP `2.25.204.38` |
| Sistema | AlmaLinux 9.8 |
| Panel | CWP, puertos 2030/2031/2082/2083/2086/2087/2095/2096 |
| Servidor web | **Apache** 2.4.65. **No hay nginx** |
| Base de datos | MariaDB 10.5.29 |
| Disco | 42 GB libres de 50 |
| Memoria | 3.6 GB, con 2.7 GB disponibles |
| SELinux | **Deshabilitado** |
| Acceso SSH | Solo por clave. Contraseña deshabilitada |

## Lo que ya estaba resuelto y el plan daba por hacer

**El dominio `gestionar-te.com.ar` está funcionando**: sitio configurado, zona de
DNS propia, **certificado emitido** y renovación automática por tarea programada
(`acme.sh` más los cron de CWP).

Eso cierra el paso **2.5 (TLS)** y el bloqueo **B2** del checklist. Un subdominio
nuevo obtiene su certificado por el mismo mecanismo, sin trabajo extra.

## Qué hay corriendo

Apache, MariaDB, CWP, y además todo el stack de correo (Postfix, Dovecot),
servidor DNS (named), FTP (pure-ftpd), `rpcbind` y un agente antimalware.

**El servidor está prácticamente vacío:** un solo sitio
(`/home/gestiona/public_html`) con un `index.html` de 2021, ninguna base de datos
de aplicación y **ninguna cuenta de correo creada**.

## PHP: el bloqueante que se resolvió

El servidor tenía **solo PHP 7.4.33**, compilado a mano por CWP. Wings necesita
8.2 o superior, así que **no podía arrancar**.

### Por qué no se instaló desde el repositorio de AlmaLinux

`/usr/bin/php` es un **enlace simbólico** a `/usr/local/bin/php`, el PHP que CWP
compiló. Instalar el paquete `php` de AppStream habría reemplazado ese enlace y
dejado a CWP usando una versión que no es la suya.

### Lo que se hizo

Se agregó el repositorio **Remi**, que instala versiones que **conviven** sin
tocar nada de lo existente:

| Qué | Dónde |
|---|---|
| PHP nuevo | `/usr/bin/php82` — versión **8.2.33** |
| Servicio | `php82-php-fpm`, activo y habilitado al arranque |
| PHP de CWP | `/usr/bin/php` → sigue siendo **7.4.33**, intacto |

Extensiones verificadas presentes: `pdo_mysql`, `mbstring`, `openssl`,
`tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`, `zip`, `intl`,
`gd`.

**Comprobado después de instalar:** el enlace de CWP sigue apuntando a su 7.4 y
los servicios `cwpsrv`, `cwp-phpfpm`, `httpd` y `mariadb` siguen activos.

## Herramientas de despliegue

| Herramienta | Estado |
|---|---|
| git | 2.52.0 |
| unzip | presente |
| rsync | presente |
| composer | **instalado el 30/08**, corriendo sobre PHP 8.2 |

## Despliegue — hecho el 30/08/2026

**Wings está en línea en `https://wings.gestionar-te.com.ar`**, con el preflight
aprobado en sus 12 verificaciones.

### Cómo quedó armado

| Pieza | Dónde |
|---|---|
| Código | `/home/wings/app`, clonado del repositorio |
| Usuario del sistema | `wings`, con contraseña bloqueada |
| Motor PHP | Pool propio `wings` en PHP 8.2, socket `/var/opt/remi/php82/run/php-fpm/wings.sock` |
| Sitio | `/usr/local/apache/conf.d/wings.conf` — HTTP con redirección y HTTPS |
| Raíz web | `/home/wings/app/public`, **no** la raíz del proyecto |
| Certificado | Let's Encrypt, vence 28/11/2026, renovación por `acme.sh` |
| Tarea programada | Cron del usuario `wings`, cada minuto |

### Tres trampas que aparecieron y cómo se resolvieron

**El sitio no se aplicaba.** Los vhosts de CWP usan la IP explícita
(`2.25.204.38:80`) y el nuevo se creó con comodín (`*:80`). Apache los trata como
grupos separados y el comodín nunca se evaluaba: todo caía en la página por
defecto del panel. **Se corrigió usando la misma IP explícita.**

**Faltaba `proxy_fcgi`.** Estaba comentado en `httpd.conf`, así que Apache no
podía hablar con PHP 8.2. Se descomentó. Hay copia en `httpd.conf.bak-wings`.

**El socket daba permiso denegado.** El pool se creó con dueño `apache`, pero el
Apache de CWP **corre como `nobody`**. Se cambió `listen.owner` y `listen.group`
a `nobody`.

**La validación del certificado daba 404.** CWP tiene un desvío **global** en
`autossl_proxy.conf` que manda todas las validaciones a
`/usr/local/apache/autossl_tmp`, sin importar el sitio. Poner el archivo en la
carpeta de Wings no servía. Se emitió usando esa carpeta como raíz de validación,
que es la vía que el panel espera.

> **Regla general que dejan estas cuatro:** en este servidor manda CWP. Cuando algo
> no funciona, la causa suele ser una configuración global del panel, no el sitio.

### Por qué el sitio vive fuera de `conf.d/vhosts/`

Esa carpeta la reconstruye CWP. `wings.conf` está un nivel arriba, en `conf.d/`,
que Apache carga igual pero el panel no toca.

### El diseño se compila en el servidor

`public/build` está en `.gitignore`, así que los archivos compilados **no viajan
en el repositorio**. Se instaló Node 20 y el despliegue corre `npm ci` y
`npm run build`. Si eso se saltea, el sistema se ve sin estilos.

## Lo que falta

1. **Configurar Laravel para trabajar detrás de Cloudflare.** Ver abajo, es lo más
   urgente.
2. Cerrar los puertos que no se usan.
3. Backups con destino externo y restauración probada.
4. Un `deploy.sh` que repita todo esto sin pasos a mano.
5. Rotar las claves que quedan y dejar un solo archivo de credenciales.

## Cloudflare: el dominio no apunta al servidor

`gestionar-te.com.ar` **resuelve a Cloudflare**, no al servidor. El DNS lo
administra Cloudflare, no el `named` que tiene la máquina — esa zona local está de
adorno.

El subdominio de Wings se creó **sin proxy (nube gris)** para que la validación
del certificado funcione.

### Consecuencia que hay que arreglar en el código

**Laravel no está configurado para confiar en un proxy** (no hay `trustProxies` en
`bootstrap/app.php`, verificado). Si se activa el proxy de Cloudflare:

- La aplicación vería **la misma IP para todos los usuarios**. El límite de
  `throttle:5,1` del login pasaría a contar a todos juntos: **cinco intentos
  fallidos de cualquiera dejarían afuera al club entero**.
- Creería que no hay cifrado y armaría las direcciones con `http://`: enlaces de
  recuperación rotos y posibles bucles de redirección.

**No activar la nube naranja hasta que eso esté resuelto.**

## Riesgos de seguridad detectados

**Superficie expuesta.** Están abiertos a internet: FTP (21), todo el correo
(25, 110, 143, 465, 587, 993, 995, 4190), DNS (53), `rpcbind` (111) y los ocho
puertos del panel. Para un servidor que solo tiene que publicar una web, sobra
casi todo.

**El candado interno del sistema está deshabilitado.** La sección de
`PLAN-PRODUCCION.md` que configura contextos de SELinux **no aplica acá**.

**La contraseña del panel es también la de root del sistema.** Quien entre al
panel —que está publicado— tiene el servidor entero.

## Cambios que hay que hacerle al plan

| Tarea | Qué cambia |
|---|---|
| 2.1 instalar el stack | Ya estaba, salvo PHP. **Resuelto el 30/08** |
| 2.2 blindaje | La parte de SELinux no aplica. El acceso por clave ya estaba. Falta cerrar puertos |
| 2.3 usuarios de base | MariaDB ya está. Falta crear el usuario de la aplicación |
| 2.4 carpetas | Hay que apuntar la raíz a `public`, y verificar que no se vean los archivos privados |
| 2.5 certificados | **Ya resuelto** por CWP |
| 2.6 a 2.8 | Sin cambios: siguen pendientes |

Los comandos de nginx del plan **no sirven**: este servidor usa Apache.
