# Corrida dirigida de seguridad, validaciones e integridad — 23/10/2026

Codex CyE, 08:44–08:45 Argentina. Versión local `a15d6095000e0605cae71fc89211f4db111e6983`.
**41 pruebas aprobadas, 331 aserciones, salida 0; PHPUnit 43,623 s.**

Preflight de solo lectura: `APP_ENV=testing`, conexión mysql local, nombre configurado y
`SELECT DATABASE()` iguales a `wings_testing_codex`. Se anuló DB_URL y se apuntó la caché de
configuración a un archivo inexistente antes de arrancar. La base descartable se preparó con
las migraciones de las pruebas; no se tocó wingstest, producción ni su reloj.
Consulta previa de procesos PHP: no había artisan test/phpunit corriendo.

Archivos ejecutados (sin modificar):

- `tests/Feature/AccesoSinPermisoTest.php`: 14 métodos.
- `tests/Feature/SeguridadRutasProfesorTest.php`: 2 métodos.
- `tests/Feature/CobroReciboAccesoTest.php`: 3 métodos.
- `tests/Feature/CajaVerificacionMovimientosT36Test.php`: 12 métodos.
- `tests/Feature/CajaRechazoMotivoT37Test.php`: 8 métodos.
- `tests/Feature/ReciboErrorSanitizadoTest.php`: 2 métodos.

Los nombres exactos, aserciones y duraciones están en `junit.xml`, enlazados por registro en
[el seguimiento único](../CONTROL-INTEGRAL.html#seguridad). Incluyen permisos negativos,
validaciones, recorridos positivos y consistencia; no son 41 ataques distintos.
Las peticiones corren por el kernel HTTP de Laravel o por servicios, con datos de fixtures.
No certifican el navegador, configuración del servidor remoto ni CSRF real del middleware.
El error del generador PDF se simula para comprobar que no se exponga su traza.

Incidencias del arnés, anteriores a la corrida válida: ruta de arranque corregida antes de
conectar; PHPUnit en entorno restringido no pudo abrir su bootstrap. No hubo pruebas ejecutadas
en esos intentos. Corrida válida fuera de esa restricción, con el mismo preflight y conexión.
No se atribuyen esos fallos del arnés a Wings ni se ocultan en el resultado de producto.

Faltan seguridad de otras acciones POST, sesiones/cambio de clave, CSRF/XSS, headers/rate limit,
concurrencia y comparación con más volumen. Ver pendientes en la tabla. Sin aceptación integral,
despliegue ni cierre de T14. Esta corrida/consolidación requiere revisión independiente.
