# T77 — Actualización PHP del VPS — 22/10/2026

Ejecuta: Codex CyE. Pedido de Carlos: actualizar PHP y dejar funcionando producción y prueba.

## Resultado

- PHP de ambos sitios: **8.2.33 → 8.2.35-1.el9.remi**, 11 paquetes actualizados desde Remi mediante DNF. PHP-FPM reiniciado a las 13:17:38 UTC (10:17:38 Argentina).
- Apache y PHP-FPM activos. Ambos Laravel 12.68.0 arrancan con PHP 8.2.35 y consultan `SELECT 1` correctamente en su conexión propia.
- Producción y prueba: HTTPS `/login` 200, CSS y JavaScript 200. Ingreso real y dashboard de ambos comprobados en Chrome con las credenciales ya guardadas, sin copiar claves ni modificar datos de negocio.
- Las pantallas se observaron en Chrome. Las capturas temporales se descartaron: incluían datos del autocompletado del navegador y no se incorporan al repositorio.
- No se desplegó código, no se corrieron migraciones ni se cambió el reloj de prueba. No se ejecutó la suite de aplicación: mantenimiento de paquetes del servidor, con comprobaciones reales de ambos sitios.

## Límite pendiente: PHP de CWP

El panel usa otro PHP, **7.4.33**, en `/usr/local/cwp/php71`. El PHP por defecto de consola/CGI también es 7.4.33. Una consulta DNF con metadatos renovados no ofreció versiones más nuevas de `cwpphp` ni `cwp-php`: ambos siguen en `7.4.33-1`.

Se agregó `session.upload_progress.enabled = Off` en:

- `/usr/local/cwp/php71/php.d/99-php2shell.ini`
- `/usr/local/php/php.d/99-php2shell.ini`

Configuraciones de ambos servicios CWP comprobadas antes de reiniciar `cwpsrv-phpfpm` y `cwp-phpfpm`. PHP-FPM informa `Off`; panel local HTTPS devuelve 200 y los servicios permanecen activos. Una advertencia de extensión `intl` ya existía antes de intervenir y no se modificó en esta tarea.

**Es mitigación parcial de una vía de CVE-2026-109619; no corrige CVE-2026-109618 ni permite declarar protegido todo el VPS.** Pendiente un parche compatible del proveedor para el PHP de CWP. No se reemplazó su motor por otro incompatible ni se cambiaron sus accesos.

## Respaldo y revisión

Respaldo privado en el VPS: `/root/php-security-20261022-131649/` (directorio y archivos privados de root).

- `php82-before.tgz`: runtime y configuración Remi anteriores.
- `cwp-php-before.tgz`: runtimes y configuración PHP antiguos de CWP/default.
- `cwpsrv-conf-before/`, `packages-before.txt`, `php82-update.log`.

Los archivos privados permanecen en el servidor. Las versiones anteriores son vulnerables: recuperar el respaldo sería una medida de contingencia, no una solución de seguridad. No fue necesario volver atrás.

Verificación independiente por el subagente `verificar_php_vps`: versión 8.2.35 en el ejecutable del proceso maestro `/proc/419533/exe`, sin módulos borrados aún cargados; servicios activos; HTTPS, CSS y JavaScript de los dos sitios correctos desde Windows y VPS. No hizo login: el recorrido autenticado lo hizo el ejecutor principal. La tarea general permanece abierta por el parche pendiente de CWP.

Segunda verificación independiente de CWP: archivos de mitigación y lectura efectiva `Off → Off` en el binario FPM de ambos servicios, sin overrides en sus configuraciones; servicios activos y panel local 200. Confirma mitigación parcial, no actualización del PHP 7.4.33.

## Fuentes primarias consultadas

- [PHP: versión 8.2.35](https://www.php.net/releases/8_2_35.php).
- [PHP: CVE-2026-109618](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w).
- [PHP: CVE-2026-109619](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).
- [Hostinger: los VPS requieren mantenimiento propio](https://statuspage.hostinger.com/incidents/pj2zs1lx3n9d).
