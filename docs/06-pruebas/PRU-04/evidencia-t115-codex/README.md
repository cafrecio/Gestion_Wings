# Evidencia independiente T115 — Codex CAB

Corte de aplicación: `main` `34730b17`, 01/11/2026. No modifica los servicios,
vistas, catálogo de pruebas ni el reloj del sitio. Única base de ejecución local:
`wings_testing_codex`, descartable y distinta de las bases de los demás agentes.

En PowerShell, desde la raíz de esta copia:

```powershell
$env:DB_DATABASE='wings_testing_codex'
php artisan test tests/Feature/LiquidacionMesFuturoT115Test.php --no-ansi
php artisan test docs/06-pruebas/PRU-04/evidencia-t115-codex/ControlT115CodexTest.php --no-ansi
php artisan test docs/06-pruebas/PRU-04/evidencia-t115-codex/ComparacionReportesT115CodexTest.php --no-ansi
php artisan test tests/Feature/ReporteMensualTest.php --no-ansi
php artisan test --exclude-group=lento --no-ansi
```

Ejecutar secuencialmente. Los dos controles propios rechazan otra variable de
base antes de iniciar Laravel. Los comandos permanentes deben llevar la misma
variable. No son instrucciones para ejecutar en el sitio de prueba.

`ReporteMensualServiceAntesT115.php` reproduce íntegramente
`git show 30ea9a41^:app/Services/ReporteMensualService.php`; solo cambia la
declaración de clase para coexistir con la actual y aceptar su enlace HTTP.
El comparador compara todos los campos y filas con multiplicidad, ignorando el
orden de las listas, registra los centavos de las 21 combinaciones y comprueba
que las consultas del par de servicios son solo SELECT/SHOW. No inserta eventos
artificiales entre las lecturas anterior y actual.

`aviso-HORA.html` y `aviso-COMISION.html` son respuestas de la aplicación con
personas ficticias. La prueba redacta el CSRF y quita blancos finales de línea
antes de guardarlas. Los TXT también quitan esos blancos de la salida. Acreditan HTML
y controles conservados; no se presentan como capturas de navegador.

`sitio-solo-lectura.json` corresponde a una consulta al sitio de prueba mediante
su PHP82, arranque de aplicación y transacción SQL `READ ONLY`; selecciona los
períodos futuros y una muestra de cinco liquidaciones sin nombres ni usuarios.
No exporta tablas ni cambia la configuración del reloj. La fecha consultada es
la que la propia aplicación indica, no la fecha del sistema operativo.

[Informe y límites](../VERIFICACION-T115-CODEX.md).
