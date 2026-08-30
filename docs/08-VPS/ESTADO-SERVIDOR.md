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

## Lo que falta para que Wings corra

1. Crear el subdominio en CWP. El certificado sale solo.
2. Crear la base de datos y un usuario propio, sin privilegios de administrador.
3. Subir el código y instalar dependencias.
4. Apuntar la raíz del sitio a la carpeta `public` de Laravel, **no** a la raíz
   del proyecto.
5. Hacer que ese sitio use PHP 8.2 y no el 7.4 del panel.
6. Configuración de producción y `wings:preflight` en verde.
7. Tarea programada para el proceso mensual.

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
