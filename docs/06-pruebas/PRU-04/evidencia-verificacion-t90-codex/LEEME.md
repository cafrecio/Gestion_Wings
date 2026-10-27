# Evidencia de verificación T90 — Codex CAB

[Informe](../VERIFICACION-T90-CODEX.md). Solo `testing / wings_testing_codex`; sin servidor ni despliegue.

## Resultados definitivos

- [Propias, 77/528](independientes-final.txt) · [JUnit](independientes-final.xml) · [salida 0](independientes-final-exit.txt).
- [Área, 160/1160](area.txt) · [JUnit](area.xml) · [archivos](archivos-area.txt) · [salida 0](area-exit.txt). Incluye T90 4/21 y archivos lentos.
- [Técnica día23, 1/7](tecnica-dia23.txt) · [JUnit](tecnica-dia23.xml) · [salida 0](tecnica-dia23-exit.txt).
- [Rápida, 933 aprobadas/6 omitidas, 7465 aserciones](rapida.txt) · [JUnit](rapida.xml) · [salida 0](rapida-exit.txt).
- [JS del panel, 2/2](panel-cancelar.txt) · [resultado estructurado](resultado-panel.json) · [respuesta del controlador](respuesta-rechazo.json).
- [Conexión efectiva y motor](entorno-efectivo.json) · [fuentes sin cambios frente a563749b2](fuentes-t90.json) · [resumen JUnit](resultados-pruebas.json).
- [Grafo](grafo-cancelaciones.txt) y [cobertura insuficiente completada con lectura](cobertura-mcp.txt).

## Reproducción

Desde la raíz del repositorio, con PHP/XAMPP y la base descartable existente. El bootstrap rechaza otro destino, DB_URL o configuración cacheada antes de ejecutar casos.

```powershell
$env:APP_ENV='testing'
$env:DB_DATABASE='wings_testing_codex'
$env:DB_HOST='127.0.0.1'
$env:DB_URL=''
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'
$bootstrapT90=Join-Path (Get-Location).Path 'docs/06-pruebas/PRU-04/evidencia-verificacion-t90-codex/bootstrap.php'
php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t90-codex/VerificacionT90CodexTest.php --bootstrap=$bootstrapT90
php artisan test docs/06-pruebas/PRU-04/evidencia-verificacion-t90-codex/SeguridadDia23T90CodexTest.php --filter=test_dia_23_una_clase_de_una_liquidacion_cerrada_no_se_cancela_ni_forzando --bootstrap=$bootstrapT90
$areaT90=@(Get-ChildItem tests/Feature -File | Where-Object Name -match 'Clase|Clases|Liquidacion|Comision|Asistencia|Profesor' | ForEach-Object FullName)
php artisan test @areaT90 --bootstrap=$bootstrapT90
php artisan test --exclude-group=lento --bootstrap=$bootstrapT90
node docs/06-pruebas/PRU-04/evidencia-verificacion-t90-codex/panel-cancelar.test.cjs
```

Ejecutar las corridas PHP secuencialmente. En esta máquina el entorno restringido de Codex dio falsos de `is_readable`; se corrieron con acceso normal a los archivos, manteniendo las guardias.

La [copia técnica](SeguridadDiasPruebaFuente.php) conserva todos los bytes del original; el [adaptador](SeguridadDia23T90CodexTest.php) cambia solo la guardia antes de migrar y ejecuta únicamente el método filtrado. `__DIR__` deja sus salidas en este directorio propio. La copia y las pruebas auxiliares no forman parte del contador permanente.

## Intentos anteriores, conservados como tales

- [Bootstrap relativo](intento-bootstrap-relativo.txt), [bootstrap absoluto](independientes.txt) y [diagnóstico del lanzador](comprobar-lanzador.txt): abortos anteriores a los casos.
- [Primera corrida efectiva](independientes-inicial-fixture.txt) y [JUnit inicial](independientes-inicial-fixture.xml): 76 aprobadas, 1 expectativa propia incorrecta de404 frente a405. No es un fallo del bloqueo T90.
- [Salida lateral de T34 restaurada](salida-lateral-t34.json).

No usar esos intentos como resultado final ni sumar corridas repetidas para inflar cobertura. Los resultados y límites están en el informe.
